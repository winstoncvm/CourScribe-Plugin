<?php
// Path: courscribe-dashboard/templates/lessons.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lessons template
 *
 * @param array $args {
 *     @type int    $course_id         Course post ID
 *     @type string $course_title      Course title
 *     @type int    $curriculum_id     Curriculum post ID
 *     @type object $tooltips          CourScribe_Tooltips instance
 *     @type string $site_url          Site URL for assets
 * }
 */
function c($args = []) {
    // Default values
    $defaults = [
        'course_id'     => 0,
        'course_title'  => '',
        'curriculum_id' => 0,
        'tooltips'      => null,
        'site_url'      => home_url(),
    ];

    $args = wp_parse_args($args, $defaults);
    $course_id = absint($args['course_id']);
    $course_title = esc_html($args['course_title']);
    $curriculum_id = absint($args['curriculum_id']);
    $tooltips = $args['tooltips'];
    $site_url = esc_url_raw($args['site_url']);

    // Determine user roles
    $current_user = wp_get_current_user();
    $is_client = in_array('client', (array) $current_user->roles);
    $is_studio_admin = in_array('studio_admin', (array) $current_user->roles);
    $is_collaborator = in_array('collaborator', (array) $current_user->roles);
    $can_view_feedback = $is_studio_admin || $is_collaborator;

    if (!$course_id || !$tooltips instanceof CourScribe_Tooltips) {
        return; // Exit if required args are missing
    }

    // Fetch course meta
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    ?>

    <div class="courscribe-lessons">
        <div style="background: #2F2E30; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #2F2E30; width: 100%; box-sizing: border-box;">
            <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" style="width: 24px; height: 24px;">
            <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Course Goal:</span>
            <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo $course_goal; ?>
            </span>
        </div>
        <?php
        // Fetch all modules from the course meta (assuming 'modules' is a meta key storing an array of module IDs)
        $modules = get_posts([
            'post_type' => 'crscribe_module',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_course_id',
                    'value' => $course_id,
                    'compare' => '=',
                ],
            ],
        ]);

        if ($modules) {
            ?>
            <div class="module-tabs">
                <div class="tab-links">
                    <?php
                    $tab_index = 1;
                    foreach ($modules as $module) {
                        if (!$module) {
                            continue;
                        }
                        ?>
                        <div type="button" class="tab-link <?php echo $tab_index === 1 ? 'active' : ''; ?>" data-tab="tab-<?php echo esc_attr($module->ID); ?>">
                            <span>Module <?php echo esc_html($tab_index); ?>: <span><?php echo esc_html($module->post_title); ?></span></span>
                        </div>
                        <?php
                        $tab_index++;
                    }
                    ?>
                </div>
                <div class="tab-contents">
                    <?php
                    $tab_index = 1;
                    foreach ($modules as $module) {
                        if (!$module) {
                            continue;
                        }
                        $module_goal = esc_html(get_post_meta($module->ID, 'module-goal', true));
                        ?>
                        <div class="tab-content <?php echo $tab_index === 1 ? 'active' : ''; ?>" id="tab-<?php echo esc_attr($module->ID); ?>">
                            <div style="background: #2F2E30; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #2F2E30; width: 100%; box-sizing: border-box;">
                                <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" style="width: 24px; height: 24px;">
                                <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Module Goal:</span>
                                <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo $module_goal; ?>
                                </span>
                            </div>
                            <div class="courscribe-divider-row">
                                <span class="add-curriculum-text">Lessons</span>
                                <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 32%;">
                                <?php if (!$is_client) : ?>
                                <?php
                                $generate_lesson_button = '<button class="get-ai-button min-w-150" data-bs-toggle="offcanvas" data-bs-target="#offcanvasLessons" aria-controls="offcanvasLessons" data-module-id="' . esc_attr($module->ID) . '" data-course-id="' . esc_attr($course_id) . '">
                                    <span class="get-ai-inner">
                                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                                            <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                                        </svg>
                                        Generate Lessons
                                    </span>
                                </button>';
                                echo $tooltips->wrap_button_with_tooltip($generate_lesson_button, [
                                    'title' => 'Get Lesson Suggestions',
                                    'description' => 'Generate lessons for this module with Courscribe AI.',
                                    'required_package' => 'CourScribe Basics'
                                ]);
                                ?>
                                <?php
                                $add_lesson_button = '<button class="continue-application add-lesson" data-bs-toggle="modal" data-bs-target="#addLessonModal" data-course-id="' . esc_attr($course_id) . '" data-module-id="' . esc_attr($module->ID) . '" style="margin-right: 40px;">
                                    <div>
                                        <div class="pencil"></div>
                                        <div class="folder">
                                            <div class="top">
                                                <svg viewBox="0 0 24 27">
                                                    <path d="M1,0 L23,0 C23.5522847,-1.01453063e-16 24,0.44771525 24,1 L24,8.17157288 C24,8.70200585 23.7892863,9.21071368 23.4142136,9.58578644 L20.5857864,12.4142136 C20.2107137,12.7892863 20,13.2979941 20,13.8284271 L20,26 C20,26.5522847 19.5522847,27 19,27 L1,27 C0.44771525,27 6.76353751e-17,26.5522847 0,26 L0,1 C-6.76353751e-17,0.44771525 0.44771525,1.01453063e-16 1,0 Z"></path>
                                                </svg>
                                            </div>
                                            <div class="paper"></div>
                                        </div>
                                    </div>
                                    Add New Lesson
                                </button>';
                                echo $tooltips->wrap_button_with_tooltip($add_lesson_button, [
                                    'title' => 'Add Lesson',
                                    'description' => 'Create a new lesson for this module. Available in all packages.',
                                    'required_package' => 'CourScribe Basics'
                                ]);
                                ?>
                                <?php endif ?>
                            </div>
                            <div class="lessons" id="lessonsSection-<?php echo esc_attr($module->ID); ?>">
                                <?php
                                $lessons = get_posts([
                                    'post_type' => 'crscribe_lesson',
                                    'post_status' => 'publish',
                                    'numberposts' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => '_module_id',
                                            'value' => $module->ID,
                                            'compare' => '=',
                                        ],
                                    ],
                                ]);
                                $lessons = is_array($lessons) ? $lessons : [];
                                if ($lessons) {
                                    foreach ($lessons as $lesson) {
                                        if (!$lesson) {
                                            continue;
                                        }
                                        ?>
                                        <div class="lesson-body">
                                            <div class="lesson-content">
                                                <div class="lesson-header">
                                                    <?php if (!$is_client) : ?>
                                                    <div class="sort-controls">
                                                        <button class="sort-up" title="Move Up"><i class="fa fa-arrow-up"></i></button>
                                                        <button class="sort-down" title="Move Down"><i class="fa fa-arrow-down"></i></button>
                                                    </div>
                                                    <?php endif ?>
                                                </div>
                                                <div class="courscribe-divider-row-module">
                                                    <span class="add-curriculum-text">Lesson:</span>
                                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Rectangle-1683.png" alt="divider">
                                                </div>
                                                <div class="mb-3">
                                                    <div class="courscribe-header-with-divider">
                                                        <span class="courscribe-title-sm">Lesson Feedback</span>
                                                        <div class="courscribe-divider"></div>
                                                        <?php if ($is_client) : ?>
                                                            <div 
                                                                class="courscribe-client-review-submit-button"
                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="courses-lesson-review[<?php echo esc_attr($lesson->ID); ?>]"
                                                                data-field-id="courses-lesson-review-<?php echo esc_attr($lesson->ID); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                data-current-field-value=""
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                data-post-type="crscribe_lesson"
                                                                data-field-type="post"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                            ><span>Give Lesson Feedback</span></div>
                                                        <?php elseif ($can_view_feedback) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="courses-lesson-review[<?php echo esc_attr($lesson->ID); ?>]"
                                                                data-field-id="courses-lesson-review-<?php echo esc_attr($lesson->ID); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                data-current-field-value=""
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                data-post-type="crscribe_lesson"
                                                                data-field-type="post"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                        >
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Lesson Feedback</span>
                                                                <span class="text">5</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                <div class="mb-3 col-12">
                                                    <label for="lesson-name-<?php echo esc_attr($lesson->ID); ?>">Name</label>
                                                    
                                                     <?php if ($is_client) : ?>
                                                        <div class="courscribe-client-review-input-group">
                                                            <input 
                                                                class="courscribe-client-review-input"
                                                                name="courses-client-review-input-[<?php echo esc_attr($lesson->ID); ?>][lesson_name]" 
                                                                placeholder="Enter new item here" 
                                                                type="text" 
                                                                value="<?php echo esc_attr($lesson->post_title); ?>" 
                                                                id="courscribe-client-review-input-field" 
                                                                disabled>
                                                            <div 
                                                                class="courscribe-client-review-submit-button"
                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="lesson[<?php echo esc_attr($lesson->ID); ?>][lesson_name]"
                                                                data-field-id="lesson-name-<?php echo esc_attr($lesson->ID); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                data-current-field-value="<?php echo esc_attr($lesson->post_title); ?>"
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                                data-post-type="crscribe_lesson"
                                                                data-field-type="name"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                            ><span>Give Name Feedback</span></div>
                                                        </div>
                                                    <?php else : ?>
                                                        <div class="courscribe-client-review-input-group">
                                                            <div class="d-flex w-100 my-mr-1 gap2 align-center-row-div">
                                                                <input type="text" 
                                                                       id="lesson-name-<?php echo esc_attr($lesson->ID); ?>"
                                                                       name="lesson_name[<?php echo esc_attr($lesson->ID); ?>]"
                                                                       class="form-control bg-dark text-light dashed-input premium-input" 
                                                                       value="<?php echo esc_attr($lesson->post_title); ?>"
                                                                       placeholder="Enter lesson name"
                                                                       maxlength="100"
                                                                       data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                                                                       data-field-type="name" />
                                                            </div>
                                                            
                                                            <?php if ($can_view_feedback) : ?>                    
                                                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                        data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                        data-field-name="lesson[<?php echo esc_attr($lesson->ID); ?>][lesson_name]"
                                                                        data-field-id="lesson-name-<?php echo esc_attr($lesson->ID); ?>"
                                                                        data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                        data-current-field-value="<?php echo esc_attr($lesson->post_title); ?>"
                                                                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                                        data-post-type="crscribe_lesson"
                                                                        data-field-type="name"
                                                                        data-bs-toggle="offcanvas"
                                                                        data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                        aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                    >
                                                                        <span class="courscribe-client-review-end-adrnment-tooltip">View Name Feedback</span>
                                                                        <span class="text">5</span>
                                                                    </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mb-3 col-12">
                                                    <label for="lesson-goal-<?php echo esc_attr($lesson->ID); ?>">Goal</label>
                                                    
                                                    <?php if ($is_client) : ?>
                                                        <div class="courscribe-client-review-input-group">
                                                            <input 
                                                                class="courscribe-client-review-input"
                                                                name="courses-client-review-input-[<?php echo esc_attr($lesson->ID); ?>][lesson_name]" 
                                                                placeholder="Enter new item here" 
                                                                type="text" 
                                                                value="<?php echo esc_attr(get_post_meta($lesson->ID, 'lesson-goal', true)); ?>" 
                                                                id="courscribe-client-review-input-field" 
                                                                disabled>
                                                            <div 
                                                                class="courscribe-client-review-submit-button"
                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="lesson[<?php echo esc_attr($lesson->ID); ?>][lesson_goal]"
                                                                data-field-id="lesson-goal-<?php echo esc_attr($lesson->ID); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                data-current-field-value="<?php echo esc_attr($lesson->post_title); ?>"
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                                data-post-type="crscribe_lesson"
                                                                data-field-type="goal"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                            ><span>Give Goal Feedback</span></div>
                                                        </div>
                                                    <?php else : ?>
                                                        <div class="courscribe-client-review-input-group">
                                                            <div class="d-flex w-100 my-mr-1 gap2 align-center-row-div">
                                                                <textarea id="lesson-goal-<?php echo esc_attr($lesson->ID); ?>"
                                                                          name="lesson_goal[<?php echo esc_attr($lesson->ID); ?>]"
                                                                          class="form-control bg-dark text-light dashed-input premium-input" 
                                                                          placeholder="Enter lesson goal"
                                                                          rows="3"
                                                                          maxlength="500"
                                                                          data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                                                                          data-field-type="goal"><?php echo esc_textarea(get_post_meta($lesson->ID, 'lesson-goal', true)); ?></textarea>
                                                                <?php
                                                                $ai_button = '<button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                                                                    data-field-id="lesson-goal-' . esc_attr($lesson->ID) . '"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#inputAiSuggestionsModal"
                                                                    data-lesson-id="' . esc_attr($lesson->ID) . '"
                                                                    data-lesson-name="' . esc_attr($lesson->post_title) . '"
                                                                    data-module-id="' . esc_attr($module->ID) . '"
                                                                    data-module-name="' . esc_attr($module->post_title) . '"
                                                                    data-module-goal="' . esc_attr($module_goal) . '"
                                                                    data-course-name="' . esc_attr($course_title) . '"
                                                                    data-course-goal="' . esc_attr($course_goal) . '">
                                                                    <i class="fa fa-magic"></i>
                                                                </button>';
                                                                echo $tooltips->wrap_button_with_tooltip($ai_button, [
                                                                    'description' => 'Get AI-generated suggestions for your lesson goal (requires CourScribe Pro)',
                                                                    'required_package' => 'CourScribe Pro (Agency)',
                                                                    'title' => 'Get AI-generated suggestions'
                                                                ]);
                                                                ?>
                                                            </div>
                                                            
                                                            <?php if ($can_view_feedback) : ?>                    
                                                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                        data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                        data-field-name="lesson[<?php echo esc_attr($lesson->ID); ?>][lesson_goal]"
                                                                        data-field-id="lesson-goal-<?php echo esc_attr($lesson->ID); ?>"
                                                                        data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                        data-current-field-value="<?php echo esc_attr(get_post_meta($lesson->ID, 'lesson-goal', true)); ?>"
                                                                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                                        data-post-type="crscribe_lesson"
                                                                        data-field-type="goal"
                                                                        data-bs-toggle="offcanvas"
                                                                        data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                        aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                    >
                                                                        <span class="courscribe-client-review-end-adrnment-tooltip">View Goal Feedback</span>
                                                                        <span class="text">5</span>
                                                                    </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                </div>
                                                <h6 class="mt-3" style="margin-left: 1rem;">Lesson Objectives:</h6>
                                                <ul id="lesson-objectives-list-<?php echo esc_attr($lesson->ID); ?>" class="lesson-list-objectives-container">
                                                    <?php
                                                    $lesson_objectives = get_post_meta($lesson->ID, '_lesson_objectives', true);
                                                    if (!empty($lesson_objectives) && is_array($lesson_objectives)) {
                                                        $objective_number = 1;
                                                        foreach ($lesson_objectives as $index => $objective) {
                                                            $objective_id = isset($objective['id']) ? esc_attr($objective['id']) : 'objective-' . esc_attr($lesson->ID) . '-' . $index;
                                                            $thinking_skill = isset($objective['thinking_skill']) ? esc_html($objective['thinking_skill']) : '';
                                                            $action_verb = isset($objective['action_verb']) ? esc_html($objective['action_verb']) : '';
                                                            $description = isset($objective['description']) ? esc_html($objective['description']) : '';
                                                            ?>
                                                            <li class="lesson-list-objective-container objective-item-lesson-<?php echo esc_attr($lesson->ID); ?> mb-3" data-objective-id="<?php echo esc_attr($objective_id); ?>">
                                                               
                                                                <div class="courscribe-header-with-divider mb-2">
                                                                    <span class="courscribe-title-sm">Objective <?php echo esc_html($objective_number); ?>:</span>
                                                                    <div class="courscribe-divider"></div>
                                                                    <?php if ($is_client) : ?>
                                                                        <div 
                                                                            class="courscribe-client-review-submit-button"
                                                                            data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                            data-field-name="lessons[<?php echo esc_attr($lesson->ID); ?>][objective-<?php echo esc_html($objective_number); ?>]"
                                                                            data-field-id="objective-item-lesson-<?php echo esc_attr($lesson->ID); ?>-<?php echo esc_html($objective_number); ?>"
                                                                            data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                            data-current-field-value="<?php echo esc_attr(json_encode($objective)); ?>"
                                                                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                            data-post-type="crscribe_lesson"
                                                                            data-field-type="objective"
                                                                            data-bs-toggle="offcanvas"
                                                                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                        ><span>Give Objective Feedback</span></div>
                                                                    <?php elseif ($can_view_feedback) : ?>
                                                                        <div class="text-end">                          
                                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                                data-field-name="lessons[<?php echo esc_attr($lesson->ID); ?>][objective-<?php echo esc_html($objective_number); ?>]"
                                                                                data-field-id="objective-item-lesson-<?php echo esc_attr($lesson->ID); ?>-<?php echo esc_html($objective_number); ?>"
                                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                                data-current-field-value="<?php echo esc_attr(json_encode($objective)); ?>"
                                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                                data-post-type="crscribe_lesson"
                                                                                data-field-type="objective"
                                                                                data-bs-toggle="offcanvas"
                                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                            >
                                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Objective Feedback</span>
                                                                                <span class="text">5</span>
                                                                            </div>
                                                                        </div>
                                                                        <?php if (!$is_client) : ?>
                                                                            <?php
                                                                             $remove_button = '<button type="button" class="remove-btn btn-sm delete-objective" data-objective-id="' . esc_attr($objective_id) . '">Remove</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Remove Objective',
                                                                                'description' => 'Delete this objective from the lesson. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                            ?>
                                                                        <?php endif; ?>
                                                                    <?php else : ?>
                                                                        <?php
                                                                         $remove_button = '<button type="button" class="remove-btn btn-sm delete-objective" data-objective-id="' . esc_attr($objective_id) . '">Remove</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Remove Objective',
                                                                                'description' => 'Delete this objective from the lesson. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                        ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="mb-2" style="padding-inline:1rem;">
                                                                    <label for="lesson-thinking-skill-<?php echo esc_attr($objective_id); ?>">Select the Thinking Skill</label>
                                                                    <select <?php if($is_client) echo 'disabled'; ?> id="lesson-thinking-skill-<?php echo esc_attr($objective_id); ?>" class="form-control bg-dark text-light thinking-skill-objective-lessons" data-objective-id="<?php echo esc_attr($objective_id); ?>" style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                                                        <?php
                                                                        $skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                                                        foreach ($skills as $skill) {
                                                                            echo '<option value="' . esc_attr($skill) . '"' . selected($thinking_skill, $skill, false) . '>' . esc_html($skill) . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="text-dividerr">
                                                                    <span class="divider-textt">Forms the Objectives</span>
                                                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 72%; padding-inline: 24px;">
                                                                </div>
                                                                <div class="objective-row mb-4">
                                                                    <label for="lesson-objective-description-<?php echo esc_attr($objective_id); ?>">By the end of this Lesson they will: Objective <?php echo esc_html($objective_number); ?></label>
                                                                    <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                                                                        <select <?php if($is_client) echo 'disabled'; ?> id="lesson-current-action-verb-<?php echo esc_attr($objective_id); ?>" class="form-control bg-dark text-light action-verb-objective-lessons" data-objective-id="<?php echo esc_attr($objective_id); ?>" data-current-action-verb="<?php echo esc_attr($action_verb); ?>" style="min-width: 90px; max-width: 140px">
                                                                            <!-- Populated dynamically by JavaScript -->
                                                                        </select>
                                                                        <input
                                                                                class="form-control bg-dark text-light objective-description"
                                                                                id="lesson-objective-description-<?php echo esc_attr($lesson->ID); ?>-<?php echo esc_attr($objective_id); ?>"
                                                                                name="lesson-objective-description-<?php echo esc_attr($lesson->ID); ?>-<?php echo esc_attr($objective_id); ?>"
                                                                                value="<?php echo esc_attr($description); ?>"
                                                                                <?php if($is_client) echo 'disabled'; ?>
                                                                        ></input>
                                                                        <?php if (!$is_client) : ?>
                                                                        <?php
                                                                        $ai_button = '<button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                                                                            data-field-id="lesson-objective-description-' . esc_attr($objective_id) . '"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#inputAiSuggestionsModal"
                                                                            data-lesson-id="' . esc_attr($lesson->ID) . '"
                                                                            data-lesson-name="' . esc_attr($lesson->post_title) . '"
                                                                            data-lesson-goal="' . esc_attr(get_post_meta($lesson->ID, 'lesson-goal', true)) . '"
                                                                            data-module-name="' . esc_attr($module->post_title) . '"
                                                                            data-course-name="' . esc_attr($course_title) . '"
                                                                            data-thinking-skill="' . esc_attr($thinking_skill) . '"
                                                                            data-action-verb="' . esc_attr($action_verb) . '"
                                                                            >
                                                                            <i class="fa fa-magic"></i>
                                                                        </button>';
                                                                        echo $tooltips->wrap_button_with_tooltip($ai_button, [
                                                                            'description' => 'Get AI-generated suggestions for your lesson objective (requires CourScribe Pro)',
                                                                            'required_package' => 'CourScribe Pro (Agency)',
                                                                            'title' => 'Get AI-generated suggestions'
                                                                        ]);
                                                                        ?>
                                                                        <?php endif ?>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                            <?php
                                                            $objective_number++;
                                                        }
                                                    } else {
                                                        echo '<li>No objectives added yet.</li>';
                                                    }
                                                    ?>
                                                </ul>
                                                <?php if (!$is_client) : ?>
                                                <?php
                                                $add_objective_button = '<button id="addLessonListObjectiveBtn" type="button" class="add-objective mb-3" data-lesson-id="' . esc_attr($lesson->ID) . '"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add Objective</button>';
                                                echo $tooltips->wrap_button_with_tooltip($add_objective_button, [
                                                    'title' => 'Add Objective',
                                                    'description' => 'Create a new objective for this lesson. Available in all packages.',
                                                    'required_package' => 'CourScribe Basics'
                                                ]);
                                                ?>
                                                <?php endif ?>
                                                <h6 class="mt-3" style="margin-left: 1rem;">Lesson Activities:</h6>
                                                <ul id="lesson-activities-list-<?php echo esc_attr($lesson->ID); ?>" class="lesson-list-activities-container mt-4">
                                                    <?php
                                                    $lesson_activities = get_post_meta($lesson->ID, '_lesson_activities', true);
                                                    if (!empty($lesson_activities) && is_array($lesson_activities)) {
                                                        $activity_number = 1;
                                                        foreach ($lesson_activities as $index => $activity) {
                                                            $activity_id = isset($activity['id']) ? esc_attr($activity['id']) : 'activity-' . esc_attr($lesson->ID) . '-' . $index;
                                                            $type = isset($activity['type']) ? esc_html($activity['type']) : '';
                                                            $title = isset($activity['title']) ? esc_html($activity['title']) : '';
                                                            $instructions = isset($activity['instructions']) ? esc_html($activity['instructions']) : '';
                                                            ?>
                                                            <li class="lesson-list-activity-container mb-3" data-activity-id="<?php echo esc_attr($activity_id); ?>">
                                                               
                                                                <div class="courscribe-header-with-divider mb-2">
                                                                    <span class="courscribe-title-sm">Activity <?php echo esc_html($activity_number); ?>:</span>
                                                                    <div class="courscribe-divider"></div>
                                                                    <?php if ($is_client) : ?>
                                                                        <div 
                                                                            class="courscribe-client-review-submit-button"
                                                                            data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                            data-field-name="lessons[<?php echo esc_attr($lesson->ID); ?>][activity-<?php echo esc_html($activity_number); ?>]"
                                                                            data-field-id="activity-item-lesson-<?php echo esc_attr($lesson->ID); ?>-<?php echo esc_html($activity_number); ?>"
                                                                            data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                            data-current-field-value="<?php echo esc_attr(json_encode($activity)); ?>"
                                                                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                            data-post-type="crscribe_lesson"
                                                                            data-field-type="activity"
                                                                            data-bs-toggle="offcanvas"
                                                                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                        ><span>Give Activity Feedback</span></div>
                                                                    <?php elseif ($can_view_feedback) : ?>
                                                                        <div class="text-end">                          
                                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                                data-field-name="lessons[<?php echo esc_attr($lesson->ID); ?>][activity-<?php echo esc_html($activity_number); ?>]"
                                                                                data-field-id="activity-item-lesson-<?php echo esc_attr($lesson->ID); ?>-<?php echo esc_html($activity_number); ?>"
                                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                                data-current-field-value="<?php echo esc_attr(json_encode($activity)); ?>"
                                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                                data-post-type="crscribe_lesson"
                                                                                data-field-type="activity"
                                                                                data-bs-toggle="offcanvas"
                                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                            >
                                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Activity Feedback</span>
                                                                                <span class="text">5</span>
                                                                            </div>
                                                                        </div>
                                                                        <?php if (!$is_client) : ?>
                                                                            <?php
                                                                             $remove_button = '<button type="button" class="remove-btn btn-sm delete-activity" data-activity-id="' . esc_attr($activity_id) . '">Remove</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Remove Activity',
                                                                                'description' => 'Delete this activity from the lesson. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                            ?>
                                                                        <?php endif; ?>
                                                                    <?php else : ?>
                                                                        <?php
                                                                         $remove_button = '<button type="button" class="remove-btn btn-sm delete-activity" data-activity-id="' . esc_attr($activity_id) . '">Remove</button>';
                                                                        echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                            'title' => 'Remove Activity',
                                                                            'description' => 'Delete this activity from the lesson. Available in all packages.',
                                                                            'required_package' => 'CourScribe Basics'
                                                                        ]);
                                                                        ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="mb-2" style="padding-inline:1rem;">
                                                                    <label for="lesson-activity-type-<?php echo esc_attr($activity_id); ?>">Activity Type</label>
                                                                    <select <?php if($is_client) echo 'disabled'; ?> id="lesson-activity-type-<?php echo esc_attr($activity_id); ?>" class="form-control bg-dark text-light activity-type" data-activity-id="<?php echo esc_attr($activity_id); ?>" style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                                                        <?php
                                                                        $activity_types = ['Quiz', 'Discussion', 'Assignment', 'Presentation', 'Group Work', 'Other'];
                                                                        foreach ($activity_types as $activity_type) {
                                                                            echo '<option value="' . esc_attr($activity_type) . '"' . selected($type, $activity_type, false) . '>' . esc_html($activity_type) . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-2" style="padding-inline:1rem;">
                                                                    <label for="lesson-activity-title-<?php echo esc_attr($activity_id); ?>">Title</label>
                                                                    <input type="text" <?php if($is_client) echo 'disabled'; ?> id="lesson-activity-title-<?php echo esc_attr($activity_id); ?>" class="form-control bg-dark text-light activity-title" value="<?php echo esc_attr($title); ?>" placeholder="Enter activity title" style="flex:1;"/>
                                                                </div>
                                                                <div class="mb-2" style="padding-inline:1rem;">
                                                                    <label for="lesson-activity-instructions-<?php echo esc_attr($activity_id); ?>">Instructions</label>
                                                                    <textarea <?php if($is_client) echo 'disabled'; ?> id="lesson-activity-instructions-<?php echo esc_attr($activity_id); ?>" class="form-control bg-dark text-light activity-instructions" placeholder="Enter instructions" rows="4" style="flex:1;"><?php echo esc_textarea($instructions); ?></textarea>
                                                                </div>
                                                            </li>
                                                            <?php
                                                            $activity_number++;
                                                        }
                                                    } else {
                                                        echo '<li>No activities added yet.</li>';
                                                    }
                                                    ?>
                                                </ul>
                                                <?php if (!$is_client) : ?>
                                                <?php
                                                $add_activity_button = '<button id="addLessonListActivityBtn" type="button" class="add-objective mt-3" data-lesson-id="' . esc_attr($lesson->ID) . '"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add Activity</button>';
                                                echo $tooltips->wrap_button_with_tooltip($add_activity_button, [
                                                    'title' => 'Add Activity',
                                                    'description' => 'Create a new activity for this lesson. Available in all packages.',
                                                    'required_package' => 'CourScribe Basics'
                                                ]);
                                                ?>

                                                <!-- Teaching Points Section -->
                                                <div class="teaching-points-section mt-4">
                                                    <div class="courscribe-header-with-divider mb-3">
                                                        <span class="courscribe-title-sm">Teaching Points</span>
                                                        <div class="courscribe-divider"></div>
                                                        <?php if ($is_client) : ?>
                                                            <div 
                                                                class="courscribe-client-review-submit-button"
                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="lessons[<?php echo esc_attr($lesson->ID); ?>][teaching_points]"
                                                                data-field-id="teaching-points-<?php echo esc_attr($lesson->ID); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                data-current-field-value="<?php echo esc_attr(json_encode(get_post_meta($lesson->ID, '_teaching_points', true) ?: [])); ?>"
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                data-post-type="crscribe_lesson"
                                                                data-field-type="teaching_points"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                            ><span>Give Teaching Points Feedback</span></div>
                                                        <?php elseif ($can_view_feedback) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                data-course-id="<?php echo esc_attr($lesson->ID); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="lessons[<?php echo esc_attr($lesson->ID); ?>][teaching_points]"
                                                                data-field-id="teaching-points-<?php echo esc_attr($lesson->ID); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($lesson->ID)); ?>"
                                                                data-current-field-value="<?php echo esc_attr(json_encode(get_post_meta($lesson->ID, '_teaching_points', true) ?: [])); ?>"
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                data-post-type="crscribe_lesson"
                                                                data-field-type="teaching_points"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                            >
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Teaching Points Feedback</span>
                                                                <span class="text">5</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <ul id="teaching-points-list-<?php echo esc_attr($lesson->ID); ?>" class="teaching-points-container mt-3">
                                                        <?php
                                                        $teaching_points = get_post_meta($lesson->ID, '_teaching_points', true);
                                                        if (!empty($teaching_points) && is_array($teaching_points)) {
                                                            $point_number = 1;
                                                            foreach ($teaching_points as $index => $point) {
                                                                $point_id = 'point-' . esc_attr($lesson->ID) . '-' . $index;
                                                                ?>
                                                                <li class="teaching-point-item mb-2" data-point-id="<?php echo esc_attr($point_id); ?>">
                                                                    <div class="teaching-point-content">
                                                                        <div class="point-number">
                                                                            <span><?php echo $point_number; ?></span>
                                                                        </div>
                                                                        <div class="point-text">
                                                                            <?php if ($is_client) : ?>
                                                                                <span class="teaching-point-readonly"><?php echo esc_html($point); ?></span>
                                                                            <?php else : ?>
                                                                                <input type="text" 
                                                                                       class="form-control bg-dark text-light teaching-point-input w-100" 
                                                                                       style="flex: 1 !important; width: 100% !important;"
                                                                                       value="<?php echo esc_attr($point); ?>" 
                                                                                       data-point-index="<?php echo $index; ?>"
                                                                                       placeholder="Enter teaching point..." />
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <?php if (!$is_client) : ?>
                                                                        <div class="point-actions">
                                                                            <button type="button" class="btn-sm btn-outline-danger delete-teaching-point" 
                                                                                    data-point-index="<?php echo $index; ?>" 
                                                                                    data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                                                                                    title="Remove teaching point">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </li>
                                                                <?php
                                                                $point_number++;
                                                            }
                                                        } else {
                                                            echo '<li class="no-points-message">No teaching points added yet.</li>';
                                                        }
                                                        ?>
                                                    </ul>
                                                    
                                                    <?php if (!$is_client) : ?>
                                                    <div class="teaching-points-actions mt-3">
                                                        <div class="add-point-form mb-3">
                                                            <div class="input-group">
                                                                <input type="text" 
                                                                       class="form-control bg-dark text-light" 
                                                                       id="new-teaching-point-<?php echo esc_attr($lesson->ID); ?>"
                                                                       placeholder="Add a new teaching point..."
                                                                       maxlength="500" />
                                                                <button type="button" 
                                                                        class="btn btn-outline-success add-teaching-point-btn"
                                                                        data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                                                    <i class="fas fa-plus"></i> Add Point
                                                                </button>
                                                            </div>
                                                            <div class="char-counter text-muted mt-1">
                                                                <span class="current">0</span>/<span class="max">500</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="objective-row mt-4 mb-2">
                                                    <?php
                                                    $save_button = '<button class="btn courscribe-stepper-nextBtn save-lesson" data-lesson-id="' . esc_attr($lesson->ID) . '" data-module-id="' .esc_attr($module->ID) .'" data-course-id="' .esc_attr($course_id) .'">Save Lesson Changes</button>';
                                                    echo $tooltips->wrap_button_with_tooltip($save_button, [
                                                        'title' => 'Save Lesson',
                                                        'description' => 'Save all changes made to this lesson.',
                                                        'required_package' => 'CourScribe Basics'
                                                    ]);
                                                    ?>
                                                </div>
                                                <?php endif ?>
                                            </div>
                                            <div class="delete-aside-lesson">
                                                <?php if (!$is_client) : ?>
                                                <?php
                                                $delete_button = '<button class="lesson-delete-button delete-lesson" type="button" data-lesson-id="' . esc_attr($lesson->ID) . '">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#222222">
                                                        <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                                    </svg>
                                                </button>';
                                                echo $tooltips->wrap_button_with_tooltip($delete_button, [
                                                    'title' => 'Delete Lesson',
                                                    'description' => 'Remove this lesson from the module.',
                                                    'required_package' => 'CourScribe Basics'
                                                ]);
                                                ?>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    echo '<p>No lessons added yet.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                        $tab_index++;
                    }
                    ?>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Tab functionality
                    const tabLinks = document.querySelectorAll('.tab-link');
                    const tabContents = document.querySelectorAll('.tab-content');

                    tabLinks.forEach(link => {
                        link.addEventListener('click', function () {
                            tabLinks.forEach(item => item.classList.remove('active'));
                            tabContents.forEach(content => content.classList.remove('active'));

                            link.classList.add('active');
                            document.getElementById(link.getAttribute('data-tab')).classList.add('active');
                        });
                    });

                    // Teaching Points functionality
                    const teachingPointNonce = '<?php echo wp_create_nonce('courscribe_teaching_point_nonce'); ?>';
                    
                    // Character counter functionality
                    function updateCharCounter($field) {
                        const current = $field.val().length;
                        const max = parseInt($field.attr('maxlength'));
                        const $counter = $field.closest('.add-point-form').find('.char-counter');
                        
                        if ($counter.length) {
                            $counter.find('.current').text(current);
                            $counter.removeClass('warning danger');
                            
                            if (current > max * 0.8) {
                                $counter.addClass(current > max * 0.95 ? 'danger' : 'warning');
                            }
                        }
                    }

                    // Add teaching point
                    $(document).on('click', '.add-teaching-point-btn', function() {
                        const $btn = $(this);
                        const lessonId = $btn.data('lesson-id');
                        const $input = $('#new-teaching-point-' + lessonId);
                        const teachingPoint = $input.val().trim();

                        if (!teachingPoint) {
                            alert('Please enter a teaching point.');
                            return;
                        }

                        // Show loading state
                        const originalText = $btn.html();
                        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');

                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'add_teaching_point',
                                lesson_id: lessonId,
                                teaching_point: teachingPoint,
                                nonce: teachingPointNonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Clear input
                                    $input.val('');
                                    updateCharCounter($input);
                                    
                                    // Reload the teaching points section or add the new point dynamically
                                    const $container = $('#teaching-points-list-' + lessonId);
                                    const pointIndex = response.data.point_index;
                                    const pointNumber = response.data.teaching_points.length;
                                    
                                    // Remove "no points" message if it exists
                                    $container.find('.no-points-message').remove();
                                    
                                    // Add new teaching point
                                    const pointHtml = `
                                        <li class="teaching-point-item mb-2" data-point-id="point-${lessonId}-${pointIndex}">
                                            <div class="teaching-point-content">
                                                <div class="point-number">
                                                    <span>${pointNumber}</span>
                                                </div>
                                                <div class="point-text">
                                                    <input type="text" 
                                                           class="form-control bg-dark text-light teaching-point-input" 
                                                           value="${teachingPoint}" 
                                                           data-point-index="${pointIndex}"
                                                           placeholder="Enter teaching point..." />
                                                </div>
                                                <div class="point-actions">
                                                    <button type="button" class="btn-sm btn-outline-danger delete-teaching-point" 
                                                            data-point-index="${pointIndex}" 
                                                            data-lesson-id="${lessonId}"
                                                            title="Remove teaching point">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    `;
                                    
                                    $container.append(pointHtml);
                                    
                                    // Show success message
                                    $('<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">' +
                                        '<i class="fas fa-check-circle me-2"></i>' + response.data.message +
                                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                      '</div>').insertAfter($btn.closest('.teaching-points-actions')).delay(3000).fadeOut();
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            },
                            error: function() {
                                alert('Network error. Please try again.');
                            },
                            complete: function() {
                                // Reset button state
                                $btn.prop('disabled', false).html(originalText);
                            }
                        });
                    });

                    // Update teaching point on blur
                    $(document).on('blur', '.teaching-point-input', function() {
                        const $input = $(this);
                        const lessonId = $input.closest('[id*="teaching-points-list-"]').attr('id').replace('teaching-points-list-', '');
                        const pointIndex = $input.data('point-index');
                        const teachingPoint = $input.val().trim();

                        if (!teachingPoint) {
                            alert('Teaching point cannot be empty.');
                            $input.focus();
                            return;
                        }

                        // Check if value changed
                        if ($input.data('original-value') === teachingPoint) {
                            return;
                        }

                        // Show saving indicator
                        $input.addClass('saving');

                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'update_teaching_point',
                                lesson_id: lessonId,
                                point_index: pointIndex,
                                teaching_point: teachingPoint,
                                nonce: teachingPointNonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    $input.data('original-value', teachingPoint);
                                    $input.removeClass('saving').addClass('saved');
                                    setTimeout(() => $input.removeClass('saved'), 1000);
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            },
                            error: function() {
                                alert('Network error. Please try again.');
                            },
                            complete: function() {
                                $input.removeClass('saving');
                            }
                        });
                    });

                    // Store original value when input is focused
                    $(document).on('focus', '.teaching-point-input', function() {
                        $(this).data('original-value', $(this).val());
                    });

                    // Delete teaching point
                    $(document).on('click', '.delete-teaching-point', function() {
                        if (!confirm('Are you sure you want to delete this teaching point?')) {
                            return;
                        }

                        const $btn = $(this);
                        const lessonId = $btn.data('lesson-id');
                        const pointIndex = $btn.data('point-index');

                        // Show loading state
                        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'delete_teaching_point',
                                lesson_id: lessonId,
                                point_index: pointIndex,
                                nonce: teachingPointNonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Remove the teaching point item
                                    $btn.closest('.teaching-point-item').fadeOut(300, function() {
                                        $(this).remove();
                                        
                                        // Renumber remaining points
                                        const $container = $('#teaching-points-list-' + lessonId);
                                        $container.find('.teaching-point-item').each(function(index) {
                                            $(this).find('.point-number span').text(index + 1);
                                            $(this).find('.teaching-point-input').data('point-index', index);
                                            $(this).find('.delete-teaching-point').data('point-index', index);
                                        });
                                        
                                        // Show "no points" message if empty
                                        if ($container.find('.teaching-point-item').length === 0) {
                                            $container.html('<li class="no-points-message">No teaching points added yet.</li>');
                                        }
                                    });
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            },
                            error: function() {
                                alert('Network error. Please try again.');
                            },
                            complete: function() {
                                $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                            }
                        });
                    });

                    // Character counter for new teaching point inputs
                    $(document).on('input', '[id*="new-teaching-point-"]', function() {
                        updateCharCounter($(this));
                    });

                    // Initialize character counters
                    $('[id*="new-teaching-point-"]').each(function() {
                        updateCharCounter($(this));
                    });

                    // Lesson field update functionality
                    const lessonFieldNonce = '<?php echo wp_create_nonce('courscribe_lesson_field_nonce'); ?>';
                    
                    // Handle lesson field updates on blur. lets make the selectors unique to avoid conflict with module field update
                    // $(document).on('blur', '[data-field-type="name"], [data-field-type="goal"]', function() {
                    //     const $field = $(this);
                    //     const lessonId = $field.data('lesson-id');
                    //     const fieldType = $field.data('field-type');
                    //     const fieldValue = $field.val().trim();
                    //     const originalValue = $field.data('original-value') || '';

                    //     // Skip if value hasn't changed
                    //     if (fieldValue === originalValue) {
                    //         return;
                    //     }

                    //     // Validate required fields
                    //     if (!fieldValue) {
                    //         alert('This field cannot be empty.');
                    //         $field.val(originalValue).focus();
                    //         return;
                    //     }

                    //     // Show saving indicator
                    //     $field.addClass('saving').prop('disabled', true);

                    //     $.ajax({
                    //         url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    //         type: 'POST',
                    //         data: {
                    //             action: 'update_lesson_field',
                    //             lesson_id: lessonId,
                    //             field_type: fieldType,
                    //             field_value: fieldValue,
                    //             nonce: lessonFieldNonce
                    //         },
                    //         success: function(response) {
                    //             if (response.success) {
                    //                 $field.data('original-value', fieldValue);
                    //                 $field.removeClass('saving').addClass('saved');
                    //                 setTimeout(() => $field.removeClass('saved'), 1000);
                                    
                    //                 // Show success message briefly
                    //                 const $message = $('<div class="alert alert-success alert-dismissible fade show mt-2" style="font-size: 12px; padding: 8px 12px;">' +
                    //                     '<i class="fas fa-check-circle me-1"></i>' + response.data.message +
                    //                     '</div>');
                    //                 $message.insertAfter($field).delay(2000).fadeOut(500, function() {
                    //                     $(this).remove();
                    //                 });
                    //             } else {
                    //                 alert('Error: ' + response.data.message);
                    //                 $field.val(originalValue);
                    //             }
                    //         },
                    //         error: function() {
                    //             alert('Network error. Please try again.');
                    //             $field.val(originalValue);
                    //         },
                    //         complete: function() {
                    //             $field.removeClass('saving').prop('disabled', false);
                    //         }
                    //     });
                    // });

                    // Store original value when input is focused (for lesson fields)
                    $(document).on('focus', '[data-field-type="name"], [data-field-type="goal"]', function() {
                        $(this).data('original-value', $(this).val());
                    });

                    // Initialize original values for lesson fields
                    $('[data-field-type="name"], [data-field-type="goal"]').each(function() {
                        $(this).data('original-value', $(this).val());
                    });
                });
            </script>
            <?php
        } else {
            echo '<p>No modules available for this course.</p>';
        }
        ?>
    </div>
    <?php
}
?>