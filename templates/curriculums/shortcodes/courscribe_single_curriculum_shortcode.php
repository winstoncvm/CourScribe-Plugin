<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
// Path: templates/curriculums/shortcodes/courscribe_single_curriculum_shortcode.php

// Include modular JavaScript components
// require_once plugin_dir_path(__FILE__) . '../parts/feedback-javascript.php';
// require_once plugin_dir_path(__FILE__) . '../parts/tour-guide-javascript.php';
require_once plugin_dir_path(__FILE__) . '../helpers/class-courscribe-assets.php';
function courscribe_single_curriculum_shortcode($atts = []) {
    error_log('CourScribe: courscribe_single_curriculum_shortcode called');

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

    $curriculum = null;
    $view_mode = 'view';
    $target_user_id = null;

    $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
    $url_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
    $url_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';

    if ($post_id) {
        $curriculum = get_post($post_id);
        if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
            $view_mode = $url_view ?: 'view';
            $target_user_id = $url_user_id ?: null;
            error_log('CourScribe: Found curriculum from URL parameters - ID: ' . $curriculum->ID . ', View: ' . $view_mode . ', Target User: ' . ($target_user_id ?: 'none'));
        } else {
            error_log('CourScribe: Invalid post_id in URL parameters: ' . $post_id);
        }
    }

    if (!$curriculum && isset($atts['post_id'])) {
        $post_id = absint($atts['post_id']);
        $curriculum = get_post($post_id);
        if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
            $view_mode = isset($atts['view']) ? sanitize_text_field($atts['view']) : 'view';
            $target_user_id = isset($atts['user_id']) ? absint($atts['user_id']) : null;
            error_log('CourScribe: Found curriculum from shortcode attributes - ID: ' . $curriculum->ID);
        }
    }

    if (!$curriculum) {
        global $post;
        if ($post && $post->post_type === 'crscribe_curriculum') {
            $curriculum = $post;
            error_log('CourScribe: Found curriculum from global post - ID: ' . $curriculum->ID);
        }
    }

    if (!$curriculum) {
        $queried_object = get_queried_object();
        if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'crscribe_curriculum') {
            $curriculum = $queried_object;
            error_log('CourScribe: Found curriculum from queried object - ID: ' . $curriculum->ID);
        }
    }

    if (!$curriculum) {
        $curriculum_slug = get_query_var('curriculum_slug') ?: basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $curriculum_posts = get_posts([
            'post_type' => 'crscribe_curriculum',
            'post_status' => ['publish', 'draft', 'pending', 'future', 'trash'],
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

    $user_studio_id = 0;
    if ($is_collaborator || $is_client) {
        $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($is_client && !$user_studio_id) {
            global $wpdb;
            $invite_table = $wpdb->prefix . 'courscribe_client_invites';
            $first_invite = $wpdb->get_row($wpdb->prepare(
                "SELECT curriculum_id FROM $invite_table WHERE email = %s AND status = 'Accepted' ORDER BY created_at ASC LIMIT 1",
                $current_user->user_email
            ));
            if ($first_invite) {
                $user_studio_id = get_post_meta($first_invite->curriculum_id, '_studio_id', true);
            }
        }
    } elseif ($is_studio_admin || current_user_can('administrator')) {
        $studios = get_posts([
            'post_type' => 'crscribe_studio',
            'author' => $current_user->ID,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);
        if (!empty($studios)) {
            $user_studio_id = $studios[0];
        }
    }

    $curriculum_id = $curriculum->ID;
    $curriculum_goal = get_post_meta($curriculum_id, '_curriculum_goal', true) ?: 'No goal set';
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);

    $can_view = current_user_can('edit_crscribe_curriculums') ||
        ($is_collaborator && in_array('edit_crscribe_curriculums', get_user_meta($current_user->ID, '_courscribe_collaborator_permissions', true) ?: [])) ||
        $is_studio_admin;

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

    $can_edit = false;
    if ($view_mode === 'edit') {
        $can_edit = ($is_studio_admin || ($is_collaborator && $can_view));
        if ($target_user_id && $target_user_id !== $current_user->ID && !$is_studio_admin) {
            $can_edit = false;
        }
    }

    error_log('CourScribe: Permission check - Can View: ' . ($can_view ? 'yes' : 'no') . ', Can Edit: ' . ($can_edit ? 'yes' : 'no') . ', Is Studio Admin: ' . ($is_studio_admin ? 'yes' : 'no') . ', Is Collaborator: ' . ($is_collaborator ? 'yes' : 'no'));

    if (!$can_view) {
        return '<p>You do not have permission to view this curriculum.</p>';
    }

    if (!$is_client) {
        if ($is_collaborator) {
            $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
            if ($studio_id != $user_studio_id) {
                return '<p>You do not have permission to view this curriculum.</p>';
            }
        } else {
            $admin_studios = get_posts([
                'post_type' => 'crscribe_studio',
                'post_status' => 'publish',
                'author' => $current_user->ID,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);
            if (!in_array($studio_id, $admin_studios)) {
                return '<p>You do not have permission to view this curriculum.</p>';
            }
        }
    }

    $query_args = [
        'post_type' => 'crscribe_curriculum',
        'post_status' => ['publish', 'draft', 'pending', 'future', 'trash'],
        'post__in' => [$curriculum_id],
        'posts_per_page' => 1,
    ];

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

    $query = new WP_Query($query_args);
    error_log('CourScribe: Query found ' . $query->found_posts . ' posts for curriculum ID: ' . $curriculum_id);

    $curriculum_content = '<h1>' . esc_html($curriculum->post_title) . '</h1>';
    $curriculum_content .= apply_filters('the_content', $curriculum->post_content);
    $atts = shortcode_atts(['post_id' => 0, 'view' => 'view', 'user_id' => 0], $atts, 'courscribe_single_curriculum');
    $filtered_content = apply_filters('courscribe_curriculum_shortcode_output', $curriculum_content, $atts, $curriculum);

    $steps = [
        'Curriculums Stage',
        'Courses Stage',
        'Modules Stage',
        'Lessons Stage',
        'Teaching Points Stage'
    ];
    $currentStep = 1;
    $site_url = home_url();

    courscribe_enqueue_single_curriculum_scripts([
        'curriculum_id' => absint($curriculum_id),
        'is_client' => $is_client,
        'studio_id' => absint($user_studio_id),
        'view_mode' => sanitize_text_field($view_mode),
        'can_edit' => $can_edit,
        'current_user_id' => absint($current_user->ID),
        'target_user_id' => absint($target_user_id),
        'is_studio_admin' => $is_studio_admin,
        'is_collaborator' => $is_collaborator,
        'course_id' => absint($curriculum_id),
    ]);

    $template_base_path = dirname(__FILE__, 2); // Points to wp-content/plugins/courscribe/templates/
    $templates_root = dirname($template_base_path); // This goes to /templates/


    ob_start();
    ?>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/feedback.js"></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/html2canvas.js" defer></script>
    <style>
        /* Course Accordion Styles */
        .courscribe-xy-acc-item .courscribe-xy-acc_panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .courscribe-xy-acc-item .courscribe-xy-acc_panel.show {
            max-height: 10000px;
            transition: max-height 0.5s ease-in;
        }

        .courscribe-xy-acc-item .accordion-button {
            background: transparent;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        

        .courscribe-xy-acc-item .accordion-button .custom-icon {
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .courscribe-xy-acc-item .accordion-button.collapsed .custom-icon {
            transform: rotate(0deg);
        }

        .courscribe-xy-acc-item .accordion-button:not(.collapsed) .custom-icon {
            transform: rotate(180deg);
        }

        /* Ensure nested accordions don't interfere */
        .courscribe-xy-acc-item .accordion-body {
            padding: 1rem;
        }

        /* Smooth accordion body reveal */
        .courscribe-xy-acc_panel {
            opacity: 0;
            transition: opacity 0.3s ease, max-height 0.3s ease-out;
        }

        .courscribe-xy-acc_panel.show {
            opacity: 1;
        }
    </style>
    <div class="courscribe-single-curriculum p-i-2">
        <!-- <button class="courscribe-help-toggle-single" title="Start Guided Tour">
            <i class="fa fa-question"></i>
        </button> -->
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
        <!-- <?php if (!$is_client) : ?>
        <button id="courscribe-feedback-toggle" class="courscribe-show-feedback">Show Feedback</button>
        <?php endif; ?> -->

        <?php
        $tabs_query = new WP_Query($all_curriculums_args);
        if ($tabs_query->have_posts()) : ?>
        <div class="pcss3t pcss3t-effect-scale pcss3t-theme-1">
            <div class="scrollable-tabs">
                <?php
                $index = 1;
                $total_posts = $tabs_query->post_count;
                while ($tabs_query->have_posts()) : $tabs_query->the_post();
                    $tab_post_id = get_the_ID();
                    $curriculum_slug = sanitize_title(get_the_title());
                    $curriculum_page = get_page_by_path('courscribe-curriculum');
                    $curriculum_link = $curriculum_page ? get_permalink($curriculum_page->ID) . $tab_post_id . '/' : home_url('/courscribe-curriculum/' . $curriculum_slug);
                    ?>
                    <a href="<?php echo esc_url($curriculum_link); ?>"
                        class="<?php echo ($index === 1) ? 'tab-content-first' : (($index === $total_posts) ? 'tab-content-last' : 'tab-content-' . $index); ?> <?php echo ($tab_post_id == $curriculum_id) ? 'curriculum-checked' : ''; ?>">
                        <label for="tab<?php echo $index; ?>"><span>Curriculum <?php echo $index; ?>:<span><?php the_title(); ?></span></span></label>
                    </a>
                    <?php $index++; ?>
                <?php endwhile; ?>
            </div>
        </div>
        <?php wp_reset_postdata(); ?>
        <?php endif; ?>

        <?php if ($curriculum && $curriculum->ID) : ?>
        <div class="course-stage-wrapper mb-4">
            <div style="background: #222222; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #222222; width: 100%; box-sizing: border-box;">
                <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/Vector.png" alt="Icon" style="width: 24px; height: 24px;">
                <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Curriculum Goal: </span>
                <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($curriculum_goal); ?></span>
            </div>
            <div class="courscribe-divider-row px-10">
                <span class="add-curriculum-text">Courses:</span>
                <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Rectangle-1683-300x1-1.png" alt="divider" style="width: 87%;">
                <?php if (!$is_client) : ?>
                <?php
                $generate_courses_button = '
                    <button
                        id="courscribe-ai-generate-courses-button-' . esc_attr($curriculum_id) .'"
                        class="get-ai-button min-w-150"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#generateCoursesOffcanvas"
                        aria-controls="generateCoursesOffcanvas"
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
                    'title' => 'Generate Courses',
                    'description' => 'Generate Courses with AI',
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
            <div class="courscribe-xy-acc accordion" id="coursesAccordion">
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
                                <div class="courscribe-xy-acc_title accordion-header" id="heading-<?php echo esc_attr($course_id); ?>">
                                    <button class="accordion-button" type="button" id="accordion-button-<?php echo esc_attr($course_id); ?>">
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
                                            $accordion_button = '
                                                <button class="remove-btn btn-sm delete-course" type="button" data-course-id="' . esc_attr($course->ID) . '">
                                                    Remove
                                                </button>';
                                            echo $tooltips->wrap_button_with_tooltip($accordion_button, [
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
                                <div id="collapse-<?php echo $course->ID; ?>"
                                    class="courscribe-xy-acc_panel courscribe-xy-acc_panel_col"
                                    aria-labelledby="heading-<?php echo $course->ID; ?>">
                                    <div class="accordion-body">
                                        <?php
                                        courscribe_render_course_fields([
                                            'course_id' => $course->ID,
                                            'course_title' => $course->post_title,
                                            'curriculum_id' => $curriculum_id,
                                            'tooltips' => $tooltips,
                                            'site_url' => $site_url,
                                        ]);
                                        courscribe_render_modules([
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
        <div class="alert alert-warning">
            <h3>Curriculum Not Found</h3>
            <p>The requested curriculum could not be found or you do not have permission to view it.</p>
        </div>
        <?php endif; ?>

        <div class="stepper-buttons">
            <?php if (!$is_client) : ?>
            <button id="courscribe-prevBtn" class="btn courscribe-stepper-prevBtn"><span class="texst">Previous</span></button>
            <button id="courscribe-nextBtn" class="btn courscribe-stepper-nextBtn">Next</button>
            <a href="<?php echo esc_url($site_url . '/preview-curriculum/?curriculum_id=' . $curriculum_id . '&preview_type=studio-preview') ?>"
                class="txt-button-one" data-text="Awesome">
                <span class="actual-text"> Preview Curriculum </span>
                <span aria-hidden="true" class="hover-text"> Preview Curriculum </span>
            </a>
            <?php endif; ?>
        </div>

        <?php if (!$is_client) : ?>
        <?php
        // Use adjusted base path for template includes
        include $templates_root . '/template-parts/new-course-modal.php';
        include $templates_root . '/template-parts/input-ai-suggestions-modal.php';
        include $templates_root . '/template-parts/courscribe-loader.php';
        include $templates_root . '/template-parts/generate-courses.php';
        include $templates_root . '/template-parts/generate-modules.php';
        include $templates_root . '/template-parts/generate-lessons.php';
        include $templates_root . '/template-parts/new-module-modal.php';
        include $templates_root . '/template-parts/new-lesson-modal.php';
        include $templates_root . '/template-parts/new-teachingPoint-modal.php';
        include $templates_root . '/template-parts/single-curriculum/offcanvas/edit-document-offcanvas.php';
        include $templates_root . '/template-parts/single-curriculum/offcanvas/preview-offcanvas.php';
        include $templates_root . '/template-parts/single-curriculum/offcanvas/pdf-editor-modal.php';
        include $templates_root . '/template-parts/single-curriculum/offcanvas/field-feedback-offcanvas.php';
                ?>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const coursesAccordion = document.getElementById('coursesAccordion');
            
            if (!coursesAccordion) return;
            
            // Handle course accordion toggle
            coursesAccordion.addEventListener('click', function(e) {
                // Find the accordion button that was clicked
                const button = e.target.closest('.accordion-button');
                
                if (!button) return;
                
                // Get the course item
                const courseItem = button.closest('.courscribe-xy-acc-item');
                if (!courseItem) return;
                
                // Prevent event from bubbling to nested accordions
                e.stopPropagation();
                
                // Get the panel ID from the course item
                const courseId = courseItem.dataset.courseId;
                const panel = document.getElementById(`collapse-${courseId}`);
                
                if (!panel) return;
                
                // Toggle the panel
                const isExpanded = panel.classList.contains('show');
                
                if (isExpanded) {
                    // Collapse
                    panel.classList.remove('show');
                    button.classList.add('collapsed');
                    button.setAttribute('aria-expanded', 'false');
                    
                    // Rotate icon
                    const icon = button.querySelector('.custom-icon');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                } else {
                    // Expand
                    panel.classList.add('show');
                    button.classList.remove('collapsed');
                    button.setAttribute('aria-expanded', 'true');
                    
                    // Rotate icon
                    const icon = button.querySelector('.custom-icon');
                    if (icon) {
                        icon.style.transform = 'rotate(180deg)';
                    }
                }
            });
            
            // Initialize all course accordions as collapsed
            const allCourseButtons = coursesAccordion.querySelectorAll('.accordion-button');
            allCourseButtons.forEach(button => {
                const courseItem = button.closest('.courscribe-xy-acc-item');
                if (!courseItem) return;
                
                const courseId = courseItem.dataset.courseId;
                const panel = document.getElementById(`collapse-${courseId}`);
                
                if (panel) {
                    panel.classList.remove('show');
                    button.classList.add('collapsed');
                    button.setAttribute('aria-expanded', 'false');
                    
                    const icon = button.querySelector('.custom-icon');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                        icon.style.transition = 'transform 0.3s ease';
                    }
                }
            });
        });
    </script>
    <?php
    $output = ob_get_clean();
    error_log('CourScribe: Shortcode output length: ' . strlen($output));
    return $output;
}
add_shortcode('courscribe_single_curriculum', 'courscribe_single_curriculum_shortcode');