<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Course Accordion Component
 * Renders the main course accordion with modules and lessons
 */
function courscribe_render_course_accordion($curriculum_id, $current_user, $tooltips, $is_client) {
    ?>
    <!-- Accordion for courses -->
    <div class="courscribe-xy-acc" id="coursesAccordion">
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
                    courscribe_render_single_course_item($course, $curriculum_id, $current_user, $tooltips, $is_client);
                }
            }
        }
        wp_reset_postdata();
        ?>
    </div>
    <?php
}

/**
 * Render Single Course Item in Accordion
 */
function courscribe_render_single_course_item($course, $curriculum_id, $current_user, $tooltips, $is_client) {
    $course_id = $course->ID;
    ?>
    <div class="courscribe-xy-acc-item accordion-item as-course mb-4" data-course-id="<?php echo esc_attr($course->ID); ?>">
        <div class="courscribe-xy-acc_title accordion-header" id="heading-<?php echo esc_attr($course_id); ?>">
            <button class="accordion-button" type="button">
                <i class="fa fa-chevron-down me-2 custom-icon"></i>
            </button>
            <div class="courscribe-courses-header" id="course-header-<?php echo esc_attr($course->ID); ?>">
                <div class="header-row-courses">
                    <?php if ($is_client) : ?>
                        <?php courscribe_render_client_course_header($course, $curriculum_id, $current_user); ?>
                    <?php else : ?>
                        <?php courscribe_render_editable_course_header($course, $tooltips); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div id="collapse-<?php echo $course->ID; ?>" class="courscribe-xy-acc_panel courscribe-xy-acc_panel_col" aria-labelledby="heading-<?php echo $course->ID; ?>">
            <div class="accordion-body">
                <?php courscribe_render_course_content($course, $curriculum_id, $current_user, $tooltips, $is_client); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Client Course Header (Read-only with feedback button)
 */
function courscribe_render_client_course_header($course, $curriculum_id, $current_user) {
    $course_id = $course->ID;
    ?>
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
            data-post-type="crscribe_course" data-field-type="post"
            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
            data-user-name="<?php echo esc_attr($current_user->display_name); ?>"
            data-bs-toggle="offcanvas" data-bs-target="#courscribeFieldFeedbackOffcanvas"
            aria-controls="courscribeFieldFeedbackOffcanvasLabel">
            <span>Give Course Feedback</span>
        </div>
    </div>
    <?php
}

/**
 * Render Editable Course Header (Admin/Collaborator view)
 */
function courscribe_render_editable_course_header($course, $tooltips) {
    ?>
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
    <?php
}

/**
 * Render Course Content (Modules and Lessons)
 */
function courscribe_render_course_content($course, $curriculum_id, $current_user, $tooltips, $is_client) {
    $course_id = $course->ID;
    
    // Render course content textarea
    if (!$is_client) {
        ?>
        <div class="course-content-section mb-3">
            <label class="form-label text-light">Course Content:</label>
            <textarea id="course-content-<?php echo esc_attr($course_id); ?>" 
                class="form-control bg-dark text-light course-content-field"
                rows="4"
                placeholder="Enter course description and content..."><?php echo esc_textarea($course->post_content); ?></textarea>
        </div>
        <?php
    } else {
        if (!empty($course->post_content)) {
            ?>
            <div class="course-content-display mb-3">
                <div class="course-content-text"><?php echo wp_kses_post($course->post_content); ?></div>
            </div>
            <?php
        }
    }
    
    // Include modules component
    courscribe_render_modules_section($course_id, $curriculum_id, $current_user, $tooltips, $is_client);
}