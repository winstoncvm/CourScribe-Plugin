<?php
// Path: templates/template-parts/single-curriculum/course-stage.php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="course-stage-wrapper mb-4">
    <div class="courscribe-divider-row px-10">
        <span class="add-curriculum-text">Courses:</span>
        <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Rectangle-1683-300x1-1.png" alt="divider" style="width: 87%;">
        <?php if (!$permissions->is_client()): ?>
            <?php
            $generate_modules_button = '
                <button
                    id="courscribe-ai-generate-courses-button-' . esc_attr($curriculum->ID) .'"
                    class="get-ai-button min-w-150"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#generateCoursesOffcanvas"
                    aria-controls="generateModulesOffcanvas"
                    data-curriculum-id="' . esc_attr($curriculum->ID) . '"
                >
                    <span class="get-ai-inner">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                            <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                        </svg>
                        Generate Courses
                    </span>
                </button>';
            echo $tooltips->wrap_button_with_tooltip($generate_modules_button, [
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
                    'value' => $curriculum->ID,
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
                    <div class="courscribe-xy-acc-item accordion-item as-course mb-4"
                         data-course-id="<?php echo esc_attr($course->ID); ?>">
                        <div class="courscribe-xy-acc_title accordion-header" id="heading-<?php echo esc_attr($course_id); ?>">
                            <button class="accordion-button" type="button">
                                <i class="fa fa-chevron-down me-2 custom-icon"></i>
                            </button>
                            <div class="courscribe-courses-header" id="course-header-<?php echo esc_attr($course->ID); ?>">
                                <div class="header-row-courses">
                                    <?php if ($permissions->is_client()): ?>
                                        <div class="courscribe-space-between-row w-100">
                                            <span><?php echo esc_html($course->post_title); ?></span>
                                            <div class="courscribe-client-review-submit-button courscribe-flex-end min-w-300"
                                                 data-course-id="<?php echo esc_attr($course_id); ?>"
                                                 data-curriculum-id="<?php echo esc_attr($curriculum->ID); ?>"
                                                 data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum->ID)); ?>"
                                                 data-field-name="courses-course-review[<?php echo esc_attr($course_id); ?>]"
                                                 data-field-id="courses-course-review-<?php echo esc_attr($course_id); ?>"
                                                 data-post-name="<?php echo esc_attr(get_the_title($course_id)); ?>"
                                                 data-current-field-value="<?php echo esc_attr($course->post_title); ?>"
                                                 data-post-type="crscribe_course" data-field-type="post"
                                                 data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                 data-user-name="<?php echo esc_attr($current_user->display_name); ?>"
                                                 data-bs-toggle="offcanvas" data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                 aria-controls="courscribeFieldFeedbackOffcanvasLabel"><span>Give Course Feedback</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
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
                                <?php include plugin_dir_path(__FILE__) . 'feedback-controls.php'; ?>
                                <?php
                                courscribe_render_course_fields([
                                    'course_id' => $course->ID,
                                    'course_title' => $course->post_title,
                                    'curriculum_id' => $curriculum->ID,
                                    'tooltips' => $tooltips,
                                    'site_url' => $site_url,
                                ]);
                                courscribe_render_modules([
                                    'course_id' => $course->ID,
                                    'course_title' => $course->post_title,
                                    'curriculum_id' => $curriculum->ID,
                                    'tooltips' => $tooltips,
                                    'site_url' => $site_url,
                                ]);
                                courscribe_render_lessons([
                                    'course_id' => $course->ID,
                                    'course_title' => $course->post_title,
                                    'curriculum_id' => $curriculum->ID,
                                    'tooltips' => $tooltips,
                                    'site_url' => $site_url,
                                ]);
                                courscribe_render_teaching_points([
                                    'course_id' => $course->ID,
                                    'course_title' => $course->post_title,
                                    'curriculum_id' => $curriculum->ID,
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
        } else {
            echo '<p>No courses added yet.</p>';
        }
        wp_reset_postdata();
        ?>
    </div>
</div>