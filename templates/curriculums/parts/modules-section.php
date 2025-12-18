<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modules Section Component
 * Renders modules and lessons within a course
 */
function courscribe_render_modules_section($course_id, $curriculum_id, $current_user, $tooltips, $is_client) {
    ?>
    <div class="modules-section">
        <div class="modules-header d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-light mb-0">Modules</h6>
            <?php if (!$is_client) : ?>
                <button class="btn btn-sm btn-outline-light add-module-btn" 
                    data-course-id="<?php echo esc_attr($course_id); ?>"
                    data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
                    <i class="fa fa-plus"></i> Add Module
                </button>
            <?php endif; ?>
        </div>
        
        <div class="modules-container" id="modules-container-<?php echo esc_attr($course_id); ?>">
            <?php courscribe_render_course_modules($course_id, $curriculum_id, $current_user, $tooltips, $is_client); ?>
        </div>
    </div>
    <?php
}

/**
 * Render All Modules for a Course
 */
function courscribe_render_course_modules($course_id, $curriculum_id, $current_user, $tooltips, $is_client) {
    $modules_query = new WP_Query([
        'post_type' => 'crscribe_module',
        'post_status' => ['publish', 'draft', 'pending'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_course_id',
                'value' => $course_id,
                'compare' => '=',
            ],
        ],
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);

    if ($modules_query->have_posts()) {
        while ($modules_query->have_posts()) {
            $modules_query->the_post();
            $module_id = get_the_ID();
            $module = get_post($module_id);
            if ($module && $module->post_type === 'crscribe_module') {
                courscribe_render_single_module($module, $course_id, $curriculum_id, $current_user, $tooltips, $is_client);
            }
        }
    } else {
        ?>
        <div class="no-modules-message text-muted text-center py-3">
            <?php echo $is_client ? 'No modules available for this course.' : 'No modules added yet. Click "Add Module" to get started.'; ?>
        </div>
        <?php
    }
    wp_reset_postdata();
}

/**
 * Render Single Module Item
 */
function courscribe_render_single_module($module, $course_id, $curriculum_id, $current_user, $tooltips, $is_client) {
    $module_id = $module->ID;
    ?>
    <div class="module-item card bg-dark border-secondary mb-3" data-module-id="<?php echo esc_attr($module_id); ?>">
        <div class="card-header bg-transparent border-secondary">
            <div class="module-header d-flex justify-content-between align-items-center">
                <?php if ($is_client) : ?>
                    <div class="module-title-display">
                        <h6 class="text-light mb-0"><?php echo esc_html($module->post_title); ?></h6>
                    </div>
                    <div class="courscribe-client-review-submit-button"
                        data-module-id="<?php echo esc_attr($module_id); ?>"
                        data-course-id="<?php echo esc_attr($course_id); ?>"
                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
                        data-field-name="modules-module-review[<?php echo esc_attr($module_id); ?>]"
                        data-field-id="modules-module-review-<?php echo esc_attr($module_id); ?>"
                        data-post-name="<?php echo esc_attr($module->post_title); ?>"
                        data-current-field-value="<?php echo esc_attr($module->post_title); ?>"
                        data-post-type="crscribe_module" data-field-type="post"
                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"
                        data-bs-toggle="offcanvas" data-bs-target="#courscribeFieldFeedbackOffcanvas">
                        <span>Give Module Feedback</span>
                    </div>
                <?php else : ?>
                    <div class="module-title-input flex-grow-1 me-2">
                        <input type="text" 
                            id="module-name-<?php echo esc_attr($module_id); ?>"
                            class="form-control bg-dark text-light border-secondary module-title-field"
                            value="<?php echo esc_html($module->post_title); ?>"
                            placeholder="Module title..." />
                    </div>
                    <div class="module-actions d-flex align-items-center">
                        <?php
                        $delete_button = '<button class="btn btn-sm btn-outline-danger delete-module me-2" 
                            data-module-id="' . esc_attr($module_id) . '">
                            <i class="fa fa-trash"></i>
                        </button>';
                        echo $tooltips->wrap_button_with_tooltip($delete_button, [
                            'title' => 'Delete Module',
                            'description' => "Delete the '{$module->post_title}' module from this course.",
                            'required_package' => 'CourScribe Basics'
                        ]);
                        ?>
                        <span class="drag-handle text-muted" title="Drag to reorder">
                            <i class="fa fa-arrows-v"></i>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (!$is_client) : ?>
                <div class="module-content-section mb-3">
                    <textarea id="module-content-<?php echo esc_attr($module_id); ?>" 
                        class="form-control bg-dark text-light border-secondary module-content-field"
                        rows="3"
                        placeholder="Module description and content..."><?php echo esc_textarea($module->post_content); ?></textarea>
                </div>
            <?php else : ?>
                <?php if (!empty($module->post_content)) : ?>
                    <div class="module-content-display mb-3">
                        <div class="module-content-text text-light"><?php echo wp_kses_post($module->post_content); ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php courscribe_render_lessons_section($module_id, $course_id, $curriculum_id, $current_user, $tooltips, $is_client); ?>
        </div>
    </div>
    <?php
}

/**
 * Render Lessons Section within a Module
 */
function courscribe_render_lessons_section($module_id, $course_id, $curriculum_id, $current_user, $tooltips, $is_client) {
    ?>
    <div class="lessons-section">
        <div class="lessons-header d-flex justify-content-between align-items-center mb-2">
            <h6 class="text-light mb-0 small">Lessons</h6>
            <?php if (!$is_client) : ?>
                <button class="btn btn-xs btn-outline-light add-lesson-btn" 
                    data-module-id="<?php echo esc_attr($module_id); ?>"
                    data-course-id="<?php echo esc_attr($course_id); ?>"
                    data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
                    <i class="fa fa-plus"></i> Add Lesson
                </button>
            <?php endif; ?>
        </div>
        
        <div class="lessons-container" id="lessons-container-<?php echo esc_attr($module_id); ?>">
            <?php courscribe_render_module_lessons($module_id, $course_id, $curriculum_id, $current_user, $tooltips, $is_client); ?>
        </div>
    </div>
    <?php
}

/**
 * Render All Lessons for a Module
 */
function courscribe_render_module_lessons($module_id, $course_id, $curriculum_id, $current_user, $tooltips, $is_client) {
    $lessons_query = new WP_Query([
        'post_type' => 'crscribe_lesson',
        'post_status' => ['publish', 'draft', 'pending'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_module_id',
                'value' => $module_id,
                'compare' => '=',
            ],
        ],
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);

    if ($lessons_query->have_posts()) {
        while ($lessons_query->have_posts()) {
            $lessons_query->the_post();
            $lesson_id = get_the_ID();
            $lesson = get_post($lesson_id);
            if ($lesson && $lesson->post_type === 'crscribe_lesson') {
                courscribe_render_single_lesson($lesson, $module_id, $course_id, $curriculum_id, $current_user, $tooltips, $is_client);
            }
        }
    } else {
        ?>
        <div class="no-lessons-message text-muted text-center py-2 small">
            <?php echo $is_client ? 'No lessons available for this module.' : 'No lessons added yet.'; ?>
        </div>
        <?php
    }
    wp_reset_postdata();
}

/**
 * Render Single Lesson Item
 */
function courscribe_render_single_lesson($lesson, $module_id, $course_id, $curriculum_id, $current_user, $tooltips, $is_client) {
    $lesson_id = $lesson->ID;
    ?>
    <div class="lesson-item bg-secondary border rounded p-3 mb-2" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
        <div class="lesson-header d-flex justify-content-between align-items-center mb-2">
            <?php if ($is_client) : ?>
                <div class="lesson-title-display">
                    <span class="text-light small"><?php echo esc_html($lesson->post_title); ?></span>
                </div>
                <div class="courscribe-client-review-submit-button small"
                    data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
                    data-module-id="<?php echo esc_attr($module_id); ?>"
                    data-course-id="<?php echo esc_attr($course_id); ?>"
                    data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
                    data-field-name="lessons-lesson-review[<?php echo esc_attr($lesson_id); ?>]"
                    data-field-id="lessons-lesson-review-<?php echo esc_attr($lesson_id); ?>"
                    data-post-name="<?php echo esc_attr($lesson->post_title); ?>"
                    data-current-field-value="<?php echo esc_attr($lesson->post_title); ?>"
                    data-post-type="crscribe_lesson" data-field-type="post"
                    data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                    data-user-name="<?php echo esc_attr($current_user->display_name); ?>"
                    data-bs-toggle="offcanvas" data-bs-target="#courscribeFieldFeedbackOffcanvas">
                    <span>Feedback</span>
                </div>
            <?php else : ?>
                <div class="lesson-title-input flex-grow-1 me-2">
                    <input type="text" 
                        id="lesson-name-<?php echo esc_attr($lesson_id); ?>"
                        class="form-control form-control-sm bg-dark text-light border-dark lesson-title-field"
                        value="<?php echo esc_html($lesson->post_title); ?>"
                        placeholder="Lesson title..." />
                </div>
                <div class="lesson-actions d-flex align-items-center">
                    <button class="btn btn-xs btn-outline-danger delete-lesson me-1" 
                        data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                        <i class="fa fa-trash"></i>
                    </button>
                    <span class="drag-handle text-muted small" title="Drag to reorder">
                        <i class="fa fa-arrows-v"></i>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($lesson->post_content)) : ?>
            <div class="lesson-content">
                <?php if ($is_client) : ?>
                    <div class="lesson-content-text text-light small"><?php echo wp_kses_post($lesson->post_content); ?></div>
                <?php else : ?>
                    <textarea id="lesson-content-<?php echo esc_attr($lesson_id); ?>" 
                        class="form-control form-control-sm bg-dark text-light border-dark lesson-content-field"
                        rows="2"
                        placeholder="Lesson content and notes..."><?php echo esc_textarea($lesson->post_content); ?></textarea>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}