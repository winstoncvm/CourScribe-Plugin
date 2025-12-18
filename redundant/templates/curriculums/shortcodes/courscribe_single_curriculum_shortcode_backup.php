<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
// Path: templates/curriculums/shortcodes/courscribe_single_curriculum_shortcode.php
// Include modular JavaScript components
require_once plugin_dir_path(__FILE__) . '../parts/feedback-javascript.php';
require_once plugin_dir_path(__FILE__) . '../parts/tour-guide-javascript.php';
require_once plugin_dir_path(__FILE__) . '../helpers/class-courscribe-assets.php';
add_shortcode('courscribe_single_curriculum_backup', 'courscribe_single_curriculum_backup_shortcode');

function courscribe_single_curriculum_backup_shortcode($atts = []) {
    error_log('CourScribe: courscribe_single_curriculum_shortcode called');
    
    $site_url = home_url();

    if (!is_user_logged_in()) {
        error_log('CourScribe: User not logged in');
        return courscribe_retro_tv_error("Please log in to view this curriculum.");
    }

    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $tooltips = CourScribe_Tooltips::get_instance();
    $is_collaborator = in_array('collaborator', $user_roles);
    $is_studio_admin = in_array('studio_admin', $user_roles);
    $is_client = in_array('client', $user_roles);

    // Get curriculum from URL parameters or shortcode attributes
    $curriculum = null;
    $view_mode = 'view'; // Default view mode
    $target_user_id = null;
    
    // Method 1: Check URL parameters (priority)
    $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
    $url_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
    $url_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
    
    if ($post_id) {
        $curriculum = get_post($post_id);
        if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
            $view_mode = $url_view ?: 'view';
            $target_user_id = $url_user_id ?: null;
            error_log('CourScribe: Found curriculum from URL parameters - ID: ' . $curriculum->ID . ', View: ' . $view_mode);
        } else {
            $curriculum = null;
            error_log('CourScribe: Invalid post_id in URL parameters: ' . $post_id);
        }
    }
    
    // Method 2: Check shortcode attributes
    if (!$curriculum && isset($atts['post_id'])) {
        $post_id = absint($atts['post_id']);
        $curriculum = get_post($post_id);
        if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
            $view_mode = isset($atts['view']) ? sanitize_text_field($atts['view']) : 'view';
            $target_user_id = isset($atts['user_id']) ? absint($atts['user_id']) : null;
            error_log('CourScribe: Found curriculum from shortcode attributes - ID: ' . $curriculum->ID);
        } else {
            $curriculum = null;
        }
    }
    
    // Method 3: Try global post object (fallback)
    if (!$curriculum) {
        global $post;
        if ($post && $post->post_type === 'crscribe_curriculum') {
            $curriculum = $post;
            error_log('CourScribe: Found curriculum from global post - ID: ' . $curriculum->ID);
        }
    }
    
    // Method 4: Try queried object (fallback)
    if (!$curriculum) {
        $queried_object = get_queried_object();
        if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'crscribe_curriculum') {
            $curriculum = $queried_object;
            error_log('CourScribe: Found curriculum from queried object - ID: ' . $curriculum->ID);
        }
    }
    
    // Method 5: If still no curriculum, try to get from URL slug as last resort
    if (!$curriculum) {
        $curriculum_slug = get_query_var('curriculum_slug') ?: basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $curriculum_posts = get_posts([
            'post_type' => 'crscribe_curriculum',
            'post_status' => ['publish', 'draft', 'pending', 'future', 'trash'], // Include trash for archived curriculums
            'name' => $curriculum_slug,
            'posts_per_page' => 1
        ]);
        
        if (!empty($curriculum_posts)) {
            $curriculum = $curriculum_posts[0];
        }
    }

    if (!$curriculum) {
        error_log('CourScribe: Curriculum not found. Global post type: ' . ($post ? $post->post_type : 'none') . ', URL: ' . $_SERVER['REQUEST_URI']);
        return courscribe_retro_tv_error("Curriculum not found or you do not have access.");
    }
    
    // Debug logging for URL parameters
    error_log('CourScribe: Successfully loaded curriculum ID: ' . $curriculum->ID);
    error_log('CourScribe: URL Parameters - post_id: ' . $post_id . ', user_id: ' . $url_user_id . ', view: ' . $url_view);
    error_log('CourScribe: Processing - View Mode: ' . $view_mode . ', Target User: ' . ($target_user_id ?: 'none'));
    
    // Check if curriculum is archived (in trash status)
    $is_archived = ($curriculum->post_status === 'trash');
    $curriculum_status = get_post_meta($curriculum->ID, '_curriculum_status', true);
    
    error_log('CourScribe: Curriculum status - Post Status: ' . $curriculum->post_status . ', Meta Status: ' . $curriculum_status . ', Is Archived: ' . ($is_archived ? 'yes' : 'no'));

    $curriculum_id = $curriculum->ID;
    $curriculum_goal = get_post_meta($curriculum_id, '_curriculum_goal', true) ?: 'No goal set';
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);

    // Access check for studio_admin and collaborator
    $can_view = current_user_can('edit_crscribe_curriculums') ||
        ($is_collaborator && in_array('edit_crscribe_curriculums', get_user_meta($current_user->ID, '_courscribe_collaborator_permissions', true) ?: [])) ||
        $is_studio_admin;

    // Access check for client
    if ($is_client) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_client_invites';
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s AND curriculum_id = %d AND status = 'Accepted'",
            $current_user->user_email,
            $curriculum_id
        ));
        if ($invite) {
            $can_view = true;
        }
    }

    // Determine if user can edit based on view mode and permissions
    $can_edit = false;
    if ($view_mode === 'edit') {
        $can_edit = ($is_studio_admin || ($is_collaborator && $can_view));
        // If user_id is specified, ensure current user matches or has admin privileges
        if ($target_user_id && $target_user_id !== $current_user->ID && !$is_studio_admin) {
            $can_edit = false;
        }
    }
    
    error_log('CourScribe: Permission check - Can View: ' . ($can_view ? 'yes' : 'no') . ', Can Edit: ' . ($can_edit ? 'yes' : 'no') . ', Is Studio Admin: ' . ($is_studio_admin ? 'yes' : 'no') . ', Is Collaborator: ' . ($is_collaborator ? 'yes' : 'no'));
    
    // if (!$can_view) {
    //     return '<p>You do not have permission to view this curriculum.</p>';
    // }

    // Studio ID validation for non-client roles
    // if (!$is_client) {
    //     if ($is_collaborator) {
    //         $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
    //         if ($studio_id != $user_studio_id) {
    //             return '<p>You do not have permission to view this curriculum.</p>';
    //         }
    //     } else {
    //         $admin_studios = get_posts([
    //             'post_type' => 'crscribe_studio',
    //             'post_status' => 'publish',
    //             'author' => $current_user->ID,
    //             'posts_per_page' => -1,
    //             'fields' => 'ids',
    //         ]);
    //         if (!in_array($studio_id, $admin_studios)) {
    //             return '<p>You do not have permission to view this curriculum.</p>';
    //         }
    //     }
    // }

    // Query for single curriculum display
    $query_args = [
        'post_type' => 'crscribe_curriculum',
        'post_status' => ['publish', 'draft', 'pending', 'future', 'trash'], // Include trash for archived
        'post__in' => [$curriculum_id], // Focus on the current curriculum
        'posts_per_page' => 1,
    ];

    // Override with all curriculums from studio for tabs if needed
    $all_curriculums_args = [
        'post_type' => 'crscribe_curriculum',
        'post_status' => ['publish', 'draft', 'pending', 'future'],
        'posts_per_page' => 10,
        'meta_query' => [
            [
                'key' => '_curriculum_status',
                'value' => 'archived',
                'compare' => '!=',
            ],
        ],
    ];

    if ($is_client) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_client_invites';
        $invited_curriculums = $wpdb->get_col($wpdb->prepare(
            "SELECT curriculum_id FROM $table_name WHERE email = %s AND status = 'Accepted'",
            $current_user->user_email
        ));
        $all_curriculums_args['post__in'] = !empty($invited_curriculums) ? $invited_curriculums : [0];
    } elseif ($studio_id) {
        $all_curriculums_args['meta_query'][] = [
            'key' => '_studio_id',
            'value' => absint($studio_id),
            'compare' => '=',
        ];
    } else {
        $all_curriculums_args['post__in'] = [0];
    }

    // Use the focused query for single curriculum display
    $query = new WP_Query($query_args);
    
    // Log query results for debugging
    error_log('CourScribe: Query found ' . $query->found_posts . ' posts for curriculum ID: ' . $curriculum_id);

    // Prepare curriculum content for filtering
    $curriculum_content = '<h1>' . esc_html($curriculum->post_title) . '</h1>';
    $curriculum_content .= apply_filters('the_content', $curriculum->post_content);

    // Apply the courscribe_curriculum_shortcode_output filter
    $atts = shortcode_atts([], $atts, 'courscribe_single_curriculum');
    $filtered_content = apply_filters('courscribe_curriculum_shortcode_output', $curriculum_content, $atts, $curriculum);

    courscribe_enqueue_single_curriculum_scripts([
        'curriculum_id' => absint($curriculum_id),
        'is_client' => $is_client,
        'studio_id' => absint($studio_id),
        'view_mode' => sanitize_text_field($view_mode),
        'can_edit' => $can_edit,
        'current_user_id' => absint($current_user->ID),
        'target_user_id' => absint($target_user_id),
        'is_studio_admin' => $is_studio_admin,
        'is_collaborator' => $is_collaborator,
        'course_id' => absint($curriculum_id),
    ]);

    ob_start();

    $steps = [
        'Curriculums Stage',
        'Courses Stage',
        'Modules Stage',
        'Lessons Stage',
        'Teaching Points Stage'
    ];
    $currentStep = 1; // Courses Stage
    $template_base_path = dirname(__FILE__, 2);

    ?>


<!-- Floating Help Button -->
    <!-- <button class="courscribe-help-toggle-single" title="Start Guided Tour">
        <i class="fa fa-question"></i>
    </button> -->
    
<div class="courscribe-single-curriculum p-i-2">
    <!-- Stepper -->
    <?php
        courscribe_render_stepper([
            'steps' => $steps,
            'currentStep' => $currentStep,
            'site_url' => $site_url,
            'icons' => [
                'curriculum' => [
                    'active' => $site_url . '/wp-content/uploads/2024/12/curriculum-active.png',
                    'complete' => $site_url . '/wp-content/uploads/2024/12/curriculum-active.png',
                 ],
                'course' => [
                    'active' => $site_url . '/wp-content/uploads/2024/12/course-active.png',
                    'complete' => $site_url . '/wp-content/uploads/2024/12/course-active.png',
                    'inactive' => $site_url . '/wp-content/uploads/2024/12/course-inactive.png',
                ],
                'module' => [
                    'active' => $site_url . '/wp-content/uploads/2024/12/module-active.png',
                    'complete' => $site_url . '/wp-content/uploads/2024/12/module-active.png',
                    'inactive' => $site_url . '/wp-content/uploads/2024/12/module-inactive.png',
                ],
                'lesson' => [
                    'active' => $site_url . '/wp-content/uploads/2024/12/lesson-active.png',
                    'complete' => $site_url . '/wp-content/uploads/2024/12/lesson-active.png',
                    'inactive' => $site_url . '/wp-content/uploads/2024/12/lesson-inactive.png',
                ],
                'teachingPoint' => [
                    'active' => $site_url . '/wp-content/uploads/2024/12/teaching-point-active.png',
                    'inactive' => $site_url . '/wp-content/uploads/2024/12/teaching-point-inactive.png',
                ],
            ],
        ]);
        ?>

    <!-- Curriculum Content (Title and Content) -->
    <!-- <div class="curriculum-content">
            <?php echo $filtered_content; ?>
        </div> -->
    <!-- <?php if (!$is_client) : ?>
    <button id="courscribe-feedback-toggle" class="courscribe-show-feedback"
        style="position: fixed; top: 100px; right: 10px; z-index: 1000; padding: 10px 20px; background: #E9B56F; color: #231f20; border: none; border-radius: 16px;">Show
        Feedback</button>
    <?php endif; ?> -->

    <?php 
    // Create separate query for tabs (all curriculums in studio)
    $tabs_query = new WP_Query($all_curriculums_args);
    if ($tabs_query->have_posts()) : ?>
    <!-- Tabs start -->
    <div class="pcss3t pcss3t-effect-scale pcss3t-theme-1">
        <?php
                $index = 1;
                $total_posts = $tabs_query->post_count;
                ?>
        <div class="scrollable-tabs">
            <?php while ($tabs_query->have_posts()) : $tabs_query->the_post();
                        $tab_post_id = get_the_ID();
                        $curriculum_slug = sanitize_title(get_the_title());
                        // Generate curriculum URL using ID for consistency
                        $curriculum_page = get_page_by_path('courscribe-curriculum');
                        if ($curriculum_page) {
                            $curriculum_link = get_permalink($curriculum_page->ID) . $tab_post_id . '/';
                        } else {
                            $curriculum_link = home_url('/courscribe-curriculum/' . $curriculum_slug);
                        }
                        $url = add_query_arg(
                            array(
                                'post_id' => $tab_post_id,
                                'user_id' => get_current_user_id(), // or any dynamic variable
                                'view' => 'edit' // optional param
                            ),
                            $site_url . '/edit-curriculum/' . $tab_post_id
                        );
                        ?>
            <a href="<?php echo esc_url($url); ?>"
                class="<?php echo ($index === 1) ? 'tab-content-first' : (($index === $total_posts) ? 'tab-content-last' : 'tab-content-' . $index); ?> <?php echo ($tab_post_id == $curriculum_id) ? 'curriculum-checked' : ''; ?>">
                <label for="tab<?php echo $index; ?>"><span>Curriculum
                        <?php echo $index; ?>:<span><?php the_title(); ?></span></span></label>
            </a>
            <?php $index++; ?>
            <?php endwhile; ?>
        </div>
    </div>
    <!-- Tabs end -->
    <?php wp_reset_postdata(); // Reset after tabs query ?>
    
    <?php if ($curriculum && $curriculum->ID) : ?>

    <!-- Course -->
    <div class="course-stage-wrapper mb-4">
        <div
            style="background: #222222; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #222222; width: 100%; box-sizing: border-box;">
            <img src="<?= home_url(); ?>/wp-content/plugins/courscribe/assets/images/Vector.png" alt="Icon"
                style="width: 24px; height: 24px;">
            <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Curriculum Goal: </span>
            <span
                style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($curriculum_goal); ?></span>
        </div>
        <div class="courscribe-divider-row px-10">
            <span class="add-curriculum-text">Courses:</span>
            <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/Rectangle-1683-300x1-1.png" alt="divider"
                style="width: 87%;">
            <?php if (!$is_client) : ?>
            <?php
                $generate_courses_button = '
                    <button
                        id="courscribe-ai-generate-courses-button-' . esc_attr($curriculum_id) .'"
                        class="get-ai-button min-w-150"
                        data-bs-toggle="modal"
                        data-bs-target="#cs-course-generation-modal"
                        data-curriculum-id="' . esc_attr($curriculum_id) . '"
                    >
                        <span class="get-ai-inner">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                                <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                            </svg>
                            Generate Courses
                        </span>
                    </button>';
                echo $tooltips->wrap_button_with_tooltip($generate_courses_button, [
                    'title' => 'AI Course Generator',
                    'description' => 'Create professional courses with our advanced AI-powered wizard featuring customizable settings and real-time editing',
                    'required_package' => 'CourScribe Basics'
                ]);

                $add_course_button = '
                    <button
                        type="button"
                        style="margin-top: 12px; min-width: 200px"
                        id="open-course-modal"
                        class="btn-sm courscribe-save-button add-objective"
                        data-curriculum-id="' . $curriculum->ID . '"
                        data-bs-toggle="modal"
                        data-bs-target="#addCourseModal"
                    >
                        <i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add New Course
                    </button>';
                echo $tooltips->wrap_button_with_tooltip($add_course_button, [
                    'title' => 'Add New Course',
                    'description' => 'Create a new course in this curriculum. Available in all packages.',
                    'required_package' => 'CourScribe Basics'
                ]);
                ?>
            <?php endif; ?>
        </div>
       
        <div class="accordion courscribe-xy-acc" id="coursesAccordion">
                <?php
                $courses_query = new WP_Query([
                    'post_type' => 'crscribe_course',
                    'post_status' => ['publish', 'draft', 'pending'],
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => '_curriculum_id',
                            'value' => $curriculum_id,
                            'compare' => '=',
                        ],
                    ],
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                ]);

                if ($courses_query->have_posts()) {
                    while ($courses_query->have_posts()) {
                        $courses_query->the_post();
                        $course_id = get_the_ID();
                        $course = get_post($course_id);
                        if ($course && $course->post_type === 'crscribe_course') {
                            ?>
                            <div class="courscribe-xy-acc-item accordion-item as-course mb-4" data-course-id="<?php echo esc_attr($course->ID); ?>">
                                <div class="courscribe-xy-acc_title accordion-header" id="cs-heading-<?php echo esc_attr($course_id); ?>-<?php echo esc_attr($curriculum_id); ?>">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#cs-collapse-<?php echo esc_attr($course->ID); ?>-<?php echo esc_attr($curriculum_id); ?>" 
                                            aria-expanded="false" 
                                            aria-controls="cs-collapse-<?php echo esc_attr($course->ID); ?>-<?php echo esc_attr($curriculum_id); ?>">
                                        <i class="fa fa-chevron-down me-2 custom-icon"></i>
                                    </button>
                                    <div class="courscribe-courses-header" id="course-header-<?php echo esc_attr($course->ID); ?>">
                                        <div class="header-row-courses">
                                            <?php if ($is_client) : ?>
                                            <div class="courscribe-space-between-row w-100">
                                                <span><?php echo esc_html($course->post_title); ?></span>
                                                <div class="courscribe-client-review-submit-button courscribe-flex-end min-w-300"
                                                    data-course-id="<?php echo esc_attr($course_id); ?>"
                                                    data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
                                                    data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>"
                                                    data-field-name="courses-course-review[<?php echo esc_attr($course_id); ?>]"
                                                    data-field-id="courses-course-review-<?php echo esc_attr($course_id); ?>"
                                                    data-post-name="<?php echo esc_attr(get_the_title($course_id)); ?>"
                                                    data-current-field-value="<?php echo esc_attr($course->post_title); ?>"
                                                    data-post-type="crscribe_course"
                                                    data-field-type="post"
                                                    data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                    data-user-name="<?php echo esc_attr($current_user->display_name); ?>"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                    aria-controls="courscribeFieldFeedbackOffcanvasLabel">
                                                    <span>Give Course Feedback</span>
                                                </div>
                                            </div>
                                            <?php else : ?>
                                            <input type="text" id="course-name-<?php echo esc_attr($course->ID); ?>"
                                                style="width: 100%; height: 40px;"
                                                class="form-control bg-dark text-light dashed-input the-input-field"
                                                value="<?php echo esc_html($course->post_title); ?>" />
                                            <span class="course-title-span"><?php echo esc_html($course->post_title); ?></span>
                                            
                                            <?php
                                            $delete_button = '
                                                <button class="remove-btn btn-sm delete-course" type="button" data-course-id="' . esc_attr($course->ID) . '">
                                                    Remove
                                                </button>';
                                            echo $tooltips->wrap_button_with_tooltip($delete_button, [
                                                'title' => 'Delete Course',
                                                'description' => "Delete the '{$course->post_title}' course from this curriculum. Available in all packages.",
                                                'required_package' => 'CourScribe Basics'
                                            ]);
                                            ?>
                                            <span class="drag-handle" title="Drag to reorder">
                                                <i class="fa fa-arrows-v"></i>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div id="cs-collapse-<?php echo esc_attr($course->ID); ?>-<?php echo esc_attr($curriculum_id); ?>"
                                    class="accordion-collapse collapse courscribe-xy-acc_panel courscribe-xy-acc_panel_col"
                                    aria-labelledby="cs-heading-<?php echo esc_attr($course_id); ?>-<?php echo esc_attr($curriculum_id); ?>"
                                    data-bs-parent="#coursesAccordion">
                                    <div class="accordion-body">
                                    <div class="courscribe-slide-deck-controls">
                                        <?php if (!$is_client) : ?>
                                        <?php
                                            $generate_slide_button = '
                                                <button
                                                    id="courscribe-generate-slide-deck-' . esc_attr($course->ID) . '"
                                                    class="btn btn-primary generate-test-slide courscribe-generate-slide-deck"
                                                    style="background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%); border-bottom-left-radius: 0px; padding: 1rem 2rem;"
                                                    data-course-id="' . esc_attr($course->ID) . '"
                                                >
                                                    Generate Slide Deck
                                                </button>';
                                            echo $tooltips->wrap_button_with_tooltip($generate_slide_button, [
                                                'title' => 'Generate Slide Deck',
                                                'description' => 'Automatically generate a full slide deck for this course using AI. Stores up to 4 slide decks.',
                                                'required_package' => 'CourScribe Pro'
                                            ]);

                                            $preview_slide_button = '
                                                <button
                                                    id="courscribe-preview-slide-deck-' . esc_attr($course->ID) . '"
                                                    class="txt-button-one preview-test-slide courscribe-preview-slide-deck"
                                                    data-text="Awesome"
                                                    data-course-id="' . esc_attr($course_id) . '"
                                                >
                                                    <span class="actual-text"> Preview Slide Deck </span>
                                                    <span aria-hidden="true" class="hover-text"> Preview Slide Deck </span>
                                            </button>';
                                            echo $tooltips->wrap_button_with_tooltip($preview_slide_button, [
                                                'title' => 'Preview Slide Deck',
                                                'description' => 'View the latest slide deck in a full-screen preview.',
                                                'required_package' => 'CourScribe Pro'
                                            ]);

                                            $edit_pdf_button = '
                                                <button
                                                    id="courscribe-edit-richtexteditor-' . esc_attr($course->ID) . '"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#courscribeEditDocumentOffcanvas"
                                                    aria-controls="courscribeEditDocumentOffcanvas"
                                                    data-course-id="' . esc_attr($course->ID) . '"
                                                    class="txt-button-two"
                                                    data-text="Awesome"
                                                >
                                                    <span class="actual-text"> edit doc </span>
                                                    <span aria-hidden="true" class="hover-text-two"> edit doc </span>
                                            </button>';
                                            echo $tooltips->wrap_button_with_tooltip($edit_pdf_button, [
                                                'title' => 'Edit PDF Template',
                                                'description' => 'Customize the PDF slide deck layout using a visual editor.',
                                                'required_package' => 'CourScribe Pro'
                                            ]);

                                            $slide_decks = get_post_meta($course_id, '_courscribe_slide_decks', true);
                                            if (!empty($slide_decks) && is_array($slide_decks)) {
                                                ?>
                                                <select id="courscribe-download-deck-<?php echo $course_id ?>"
                                                    class="form-select d-inline-block w-auto ms-2 mb-2">
                                                    <option value="">Select a slide deck to download</option>
                                                    <?php
                                                        usort($slide_decks, function($a, $b) {
                                                            return strtotime($b['date']) - strtotime($a['date']);
                                                        });
                                                        foreach ($slide_decks as $index => $deck) {
                                                            if (!empty($deck['ppt_url']) && !empty($deck['date'])) {
                                                                ?>
                                                        <option value="<?php echo esc_url($deck['ppt_url']); ?>"
                                                            data-reveal-url="<?php echo isset($deck['reveal_url']) ? esc_url($deck['reveal_url']) : ''; ?>">
                                                            <?php echo esc_html(date_i18n('F j, Y, g:i a', strtotime($deck['date']))); ?>
                                                        </option>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                                <?php
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                        <?php
                                        courscribe_render_course_fields([
                                            'course_id' => $course->ID,
                                            'course_title' => $course->post_title,
                                            'curriculum_id' => $curriculum_id,
                                            'tooltips' => $tooltips,
                                            'site_url' => $site_url,
                                        ]);
                                        courscribe_render_modules_premium([
                                            'course_id' => $course->ID,
                                            'course_title' => $course->post_title,
                                            'curriculum_id' => $curriculum_id,
                                            'tooltips' => $tooltips,
                                            'site_url' => $site_url,
                                        ]);
                                        courscribe_render_lessons([
                                            'course_id' => $course->ID,
                                            'course_title' => $course->post_title,
                                            'curriculum_id' => $curriculum_id,
                                            'tooltips' => $tooltips,
                                            'site_url' => $site_url,
                                        ]);
                                        courscribe_render_teaching_points([
                                            'course_id' => $course->ID,
                                            'course_title' => $course->post_title,
                                            'curriculum_id' => $curriculum_id,
                                            'tooltips' => $tooltips,
                                            'site_url' => $site_url,
                                        ]);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        } else {
                            echo '<p>Invalid course found.</p>';
                        }
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p>No courses added yet.</p>';
                }
                ?>
            </div>
    </div>
    <?php else : ?>
    <p>No curriculums found for your studios.</p>
    <?php endif; ?>


    
    <!-- Previous and Next buttons -->
    <div class="stepper-buttons">
        <?php if (!$is_client) : ?>
        <button id="courscribe-prevBtn" class="btn courscribe-stepper-prevBtn"><span
                class="texst">Previous</span></button>
        <button id="courscribe-nextBtn" class="btn courscribe-stepper-nextBtn">Next</button>
        <a href="<?php echo esc_url($site_url . '/preview-curriculum/?curriculum_id=' . $curriculum_id . '&preview_type=studio-preview') ?>"
            class="txt-button-one" data-text="Awesome">
            <span class="actual-text"> Preview Curriculum </span>
            <span aria-hidden="true" class="hover-text"> Preview Curriculum </span>
        </a>
        <?php endif; ?>
    </div>
    <!-- Offcanvas for RichTextEditor -->
    <?php if (!$is_client) : 
        include plugin_dir_path(__FILE__) . '../../template-parts/single-curriculum/offcanvas/edit-document-offcanvas.php';
        include plugin_dir_path(__FILE__) . '../../template-parts/single-curriculum/offcanvas/preview-offcanvas.php';
        include plugin_dir_path(__FILE__) . '../../template-parts/single-curriculum/offcanvas/pdf-editor-modal.php';
        
        ?>
    
    <?php endif; 
    include plugin_dir_path(__FILE__) .  '../../template-parts/single-curriculum/offcanvas/field-feedback-offcanvas.php';
    ?>

    <?php if (!$is_client) : ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/new-course-modal.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/input-ai-suggestions-modal.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/courscribe-loader.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/generate-modules.php'; ?>
    <?php 
    // Include premium course generation system
    require_once COURSCRIBE_PLUGIN_PATH . 'templates/template-parts/generate-courses-premium.php';
    courscribe_render_premium_course_generator([
        'curriculum_id' => $curriculum_id,
        'curriculum_title' => $curriculum->post_title,
        'curriculum_topic' => get_post_meta($curriculum_id, '_class_topic', true),
        'curriculum_goal' => get_post_meta($curriculum_id, '_class_goal', true),
        'tooltips' => $tooltips,
        'site_url' => $site_url
    ]);
    ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/generate-lessons.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/new-module-modal.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/new-lesson-modal.php'; ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/new-teachingPoint-modal.php'; ?>
    <?php endif; ?>

    <?php else : ?>
        <div class="alert alert-warning">
            <h3>Curriculum Not Found</h3>
            <p>The requested curriculum could not be found or you do not have permission to view it.</p>
        </div>
    <?php endif; ?>
</div>


<?php
    return ob_get_clean();
}