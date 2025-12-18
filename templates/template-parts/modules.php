<?php
// Path: courscribe-dashboard/templates/modules.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modules template
 *
 * @param array $args {
 *     @type int    $course_id         Course post ID
 *     @type string $course_title      Course title
 *     @type int    $curriculum_id     Curriculum post ID
 *     @type object $tooltips          CourScribe_Tooltips instance
 *     @type string $site_url          Site URL for assets
 * }
 */
function courscribe_render_modules($args = []) {
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
    if ( !function_exists( 'get_modules_for_course' ) ) {
        function get_modules_for_course($course_id, $include_archived = false) {
            $post_statuses = $include_archived ? ['publish', 'archived'] : ['publish'];
            
            // Use more efficient query with meta_query
            $modules = get_posts([
                'post_type' => 'crscribe_module',
                'post_status' => $post_statuses,
                'numberposts' => -1,
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

            return $modules;
        }
    }

    if (!$course_id || !$tooltips instanceof CourScribe_Tooltips) {
        return; // Exit if required args are missing
    }

    // Fetch course meta
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    ?>
    <!-- <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/modules/modules-premium-enhanced.js"></script> -->

    <div class="courscribe-modules cs-modules-premium-container">
        <div style="background: #2F2E30; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #2F2E30; width: 100%; box-sizing: border-box;">
            <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" style="width: 24px; height: 24px;">
            <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Course Goal:</span>
            <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo $course_goal; ?>
            </span>
        </div>
        <div class="courscribe-header-with-divider mb-2 mt-2">
            <span class="add-curriculum-text">Modules:</span>
            <div class="courscribe-divider"></div>
            <?php if (!$is_client) : ?>
                <?php
            $generate_modules__button = '
            <button 
            id="courscribe-ai-generate-modules-button-' . esc_attr($course_id) .'"
            class="get-ai-button min-w-150" 
            data-bs-toggle="offcanvas"
            data-bs-target="#generateModulesOffcanvas"
            aria-controls="generateModulesOffcanvas"
            data-course-id="' . esc_attr($course_id) . '"
            data-curriculum-id="' . esc_attr($curriculum_id) . '"
            >
                <span class="get-ai-inner">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                        <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                    </svg>
                    Generate Modules
                </span>
            </button>
            ';

            echo $tooltips->wrap_button_with_tooltip($generate_modules__button, [
                'title' => 'Generate modules',
                'description' => "Generate modules with Ai",
                'required_package' => 'CourScribe Basics'
            ]);
            ?>

            <?php
            $add_module_button = '<button class="continue-application"
                    data-bs-toggle="modal"
                    data-bs-target="#addModuleModal"
                    data-course-id="' . esc_attr($course_id) . '"
                    style="margin-right: 40px;">
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
                Add New Module
            </button>';

            echo $tooltips->wrap_button_with_tooltip($add_module_button, [
                'title' => 'Add New Module',
                'description' => 'Create a new module for this course',
                'required_package' => 'CourScribe Basics'
            ]);
            ?>
            <?php endif ?>
        </div>
        <div class="cs-modules-container-<?php echo esc_attr($curriculum_id); ?>" id="cs-modules-wrapper-<?php echo esc_attr($course_id); ?>">
            <!-- Archive/Active Toggle -->
            <?php if (!$is_client) : ?>
            <div class="cs-module-view-toggle-<?php echo esc_attr($course_id); ?> mb-3">
                <div class="btn-group" role="group">
                    <button type="button" class="btn cs-toggle-btn active" data-view="active" data-course-id="<?php echo esc_attr($course_id); ?>">Active Modules</button>
                    <button type="button" class="btn cs-toggle-btn" data-view="archived" data-course-id="<?php echo esc_attr($course_id); ?>">Archived Modules</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="cs-modules-list-<?php echo esc_attr($course_id); ?>" id="cs-modules-active-<?php echo esc_attr($course_id); ?>">
            <?php

            $modules = get_modules_for_course($course_id, false); // Active modules only
            $archived_modules = get_modules_for_course($course_id, true); // Include archived
            $archived_modules = array_filter($archived_modules, function($module) {
                return get_post_status($module->ID) === 'archived';
            });

            if (!empty($modules)) {
                foreach ($modules as $module) {
                    $is_ai_generated = get_post_meta($module->ID, '_ai_generated', true);
                    //$post_id = $module->ID; // Get post ID
                    //$meta = get_post_meta($post_id); // Fetch all meta
                    //error_log("Meta for Module ID $post_id: " . print_r($meta, true));
                    $module_goal = esc_html(get_post_meta($module->ID, '_module_goal', true));
                    $module_objectives = maybe_unserialize(get_post_meta($module->ID, '_module_objectives', true)) ?: [];
                    ?>
                    <div class="cs-module-item-<?php echo esc_attr($module->ID); ?>" 
                         data-module-id="<?php echo esc_attr($module->ID); ?>" 
                         data-course-id="<?php echo esc_attr($course_id); ?>" 
                         data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
                        <div class="moodule-conteiner-body">
                            <div class="module-body">
                                <?php if (!$is_client) : ?>
                                <div class="cs-module-header-<?php echo esc_attr($module->ID); ?>">
                                    <!-- <div class="cs-sort-controls" id="cs-sort-controls">
                                        <button class="cs-sort-up-<?php echo esc_attr($module->ID); ?>" 
                                                id="cs-sort-up-<?php echo esc_attr($module->ID); ?>"
                                                title="Move Up" 
                                                data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-direction="up">
                                            <i class="fa fa-arrow-up"></i>
                                        </button>
                                        <button class="cs-sort-down" 
                                                id="cs-sort-down-<?php echo esc_attr($module->ID); ?>"
                                                title="Move Down" 
                                                data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-direction="down">
                                            <i class="fa fa-arrow-down"></i>
                                        </button>
                                    </div> -->
                                    <div class="cs-module-actions-<?php echo esc_attr($module->ID); ?>">
                                        <button class="cs-archive-module-btn-<?php echo esc_attr($module->ID); ?> btn btn-sm" 
                                                data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                title="Archive Module">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    </div>
                                </div>
                                <?php endif ?>
                                <!-- <div class="courscribe-divider-row-module">
                                    <span class="add-curriculum-text">Module:</span>
                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Rectangle-1683.png" alt="divider">

                                </div> -->
                                <div class="mb-3">
                                    <div class="courscribe-header-with-divider">
                                        <span class="courscribe-title-sm">Module Feedback</span>
                                        <div class="courscribe-divider"></div>
                                        <?php if ($is_client) : ?>
                                            <div 
                                                class="courscribe-client-review-submit-button"
                                                data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                data-field-name="courses-module-review[<?php echo esc_attr($module->ID); ?>]"
                                                data-field-id="courses-module-review-<?php echo esc_attr($module->ID); ?>"
                                                data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                data-current-field-value=""
                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                data-post-type="crscribe_module"
                                                data-field-type="post"
                                                data-bs-toggle="offcanvas"
                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                            ><span>Give Module Feedback</span></div>
                                        <?php elseif ($can_view_feedback) : ?>
                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                data-field-name="courses-module-review[<?php echo esc_attr($module->ID); ?>]"
                                                data-field-id="courses-module-review-<?php echo esc_attr($module->ID); ?>"
                                                data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                data-current-field-value=""
                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                data-post-type="crscribe_module"
                                                data-field-type="post"
                                                data-bs-toggle="offcanvas"
                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                        >
                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Module Feedback</span>
                                                <span class="text">5</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="module-name-<?php echo $module->ID; ?>">Name</label>
                                    <?php if ($is_client) : ?>
                                        <div class="courscribe-client-review-input-group">
                                            <input 
                                                class="courscribe-client-review-input"
                                                name="courses-client-review-input-[<?php echo esc_attr($module->ID); ?>][module_name]" 
                                                placeholder="Enter new item here" 
                                                type="text" 
                                                value="<?php echo esc_html($module->post_title); ?>" 
                                                id="courscribe-client-review-input-field" 
                                                disabled>
                                            <div 
                                                class="courscribe-client-review-submit-button"
                                                data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                data-field-name="modules[<?php echo esc_attr($module->ID); ?>][module_name]"
                                                data-field-id="module-name-<?php echo $module->ID; ?>"
                                                data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                data-current-field-value="<?php echo esc_html($module->post_title); ?>"
                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                data-post-type="crscribe_module"
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
                                                       id="module-name-<?php echo $module->ID; ?>"
                                                       name="module_name[<?php echo $module->ID; ?>]"
                                                       class="form-control bg-dark text-light dashed-input premium-input" 
                                                       value="<?php echo esc_attr($module->post_title); ?>"
                                                       placeholder="Enter module name"
                                                       maxlength="100"
                                                       data-module-id="<?php echo $module->ID; ?>"
                                                       data-field-type="name" />
                                            </div>
                                            
                                            <?php if ($can_view_feedback) : ?>                    
                                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                        data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                        data-field-name="modules[<?php echo esc_attr($module->ID); ?>][module_name]"
                                                        data-field-id="module-name-<?php echo $module->ID; ?>"
                                                        data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                        data-current-field-value="<?php echo esc_html($module->post_title); ?>"
                                                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                        data-post-type="crscribe_module"
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
                                <div class="mb-3">
                                    <label for="module-goal-<?php echo $module->ID; ?>">Goal</label>
                                    
                                    <?php if ($is_client) : ?>
                                        <div class="courscribe-client-review-input-group">
                                            <input 
                                                class="courscribe-client-review-input"
                                                name="courses-client-review-input-[<?php echo esc_attr($module->ID); ?>][module_goal]" 
                                                placeholder="Enter new item here" 
                                                type="text" 
                                                value="<?php echo esc_html($module_goal); ?>>" 
                                                id="courscribe-client-review-input-field" 
                                                disabled>
                                            <div 
                                                class="courscribe-client-review-submit-button"
                                                data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                data-field-name="modules[<?php echo esc_attr($module->ID); ?>][module_goal]"
                                                data-field-id="module-goal-<?php echo $module->ID; ?>"
                                                data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                data-current-field-value="<?php echo esc_html($module_goal); ?>"
                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                data-post-type="crscribe_module"
                                                data-field-type="goal"
                                                data-bs-toggle="offcanvas"
                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                            ><span>Give Goal Feedback</span></div>
                                        </div>
                                    <?php else : ?>
                                        <div class="courscribe-client-review-input-group">
                                            <div class="d-flex w-100 my-mr-1 gap2 align-center-row-div">
                                                <textarea id="module-goal-<?php echo $module->ID; ?>"
                                                          name="module_goal[<?php echo $module->ID; ?>]"
                                                          class="form-control bg-dark text-light dashed-input premium-input" 
                                                          placeholder="Enter module goal"
                                                          rows="3"
                                                          maxlength="500"
                                                          data-module-id="<?php echo $module->ID; ?>"
                                                          data-field-type="goal"><?php echo esc_textarea($module_goal); ?></textarea>
                                                <?php
                                                $ai_button = '<button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                                                    data-field-id="module-goal-' . $module->ID . '"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#inputAiSuggestionsModal"
                                                    data-module-id="' . esc_attr($module->ID) . '"
                                                    data-module-name="' . esc_attr($module->post_title) . '"
                                                    data-course-name="' . esc_attr($course_title) . '"
                                                    data-course-goal="' . esc_attr($course_goal) . '">
                                                    <i class="fa fa-magic"></i>
                                                </button>';
                                                echo $tooltips->wrap_button_with_tooltip($ai_button, [
                                                    'description' => 'Get AI-generated suggestions for your module goal (requires CourScribe Pro)',
                                                    'required_package' => 'CourScribe Pro (Agency)',
                                                    'title' => 'Get AI-generated suggestions'
                                                ]);
                                                ?>
                                            </div>
                                            
                                            <?php if ($can_view_feedback) : ?>                    
                                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                        data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                        data-field-name="modules[<?php echo esc_attr($module->ID); ?>][module_goal]"
                                                        data-field-id="module-goal-<?php echo $module->ID; ?>"
                                                        data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                        data-current-field-value="<?php echo esc_html($module_goal); ?>"
                                                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                        data-post-type="crscribe_module"
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
                                
                                <div class="mb-3 w-100">
                                    <h6 style="margin-left: 1rem;">Objectives:</h6>
                                    <ul id="module-objectives-list-<?php echo $module->ID; ?>" class="cs-objectives-list">
                                        <?php
                                        if (!empty($module_objectives) && is_array($module_objectives)) {
                                            $objective_number = 1;
                                            foreach ($module_objectives as $index => $objective) {
                                                error_log('Objective ' . $index . ' for module ' . $module->ID . ': ' . print_r($objective, true));
                                                $objective_id = 'objective-' . $module->ID . '-' . $index;
                                                $thinking_skill = isset($objective['thinking_skill']) ? esc_html($objective['thinking_skill']) : '';
                                                $action_verb = isset($objective['action_verb']) ? esc_html($objective['action_verb']) : '';
                                                $description = isset($objective['description']) ? esc_html($objective['description']) : '';
                                                ?>
                                                <li class="cs-objective-item animate-slide-in cs-objective-item-<?php echo $module->ID; ?> mb-3" 
                                                    data-objective-id="<?php echo $objective_id; ?>" 
                                                    data-module-id="<?php echo $module->ID; ?>">
                                                    
                                                    <div class="courscribe-header-with-divider mb-2 w-100">
                                                        <span class="courscribe-title-sm">Objective <?php echo esc_html($objective_number); ?>:</span>
                                                        <div class="courscribe-divider"></div>
                                                        <?php if ($is_client) : ?>
                                                            <!-- Client feedback button -->
                                                        <?php elseif ($can_view_feedback) : ?>
                                                            <!-- Feedback view button -->
                                                        <?php else : ?>
                                                            <?php if (!$is_client) : ?>
                                                                <?php
                                                                $remove_button = '<button type="button" class="remove-btn btn-sm cs-remove-objective" data-objective-id="' . $objective_id . '">Remove</button>';
                                                                echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                    'title' => 'Remove Objective',
                                                                    'description' => 'Delete this objective from your course. Available in all packages.',
                                                                    'required_package' => 'CourScribe Basics'
                                                                ]);
                                                                ?>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="objective-row mb-2 w-100">
                                                        <label for="thinking-skill-<?php echo $objective_id; ?>">Select the Thinking Skill</label>
                                                        
                                                        <?php if ($is_client) : ?>
                                                            <span><?php echo esc_html($thinking_skill ?: 'Not set'); ?></span>
                                                        <?php else : ?>
                                                            <select id="module-thinking-skill-<?php echo $objective_id; ?>" 
                                                                    class="form-control bg-dark text-light cs-thinking-skill" 
                                                                    data-objective-id="<?php echo $objective_id; ?>" 
                                                                    data-module-id="<?php echo $module->ID; ?>"
                                                                    style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                                                <?php
                                                                $skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                                                foreach ($skills as $skill) {
                                                                    $selected = ($thinking_skill == $skill) ? 'selected' : '';
                                                                    echo '<option value="' . esc_attr($skill) . '" ' . $selected . '>' . esc_html($skill) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="courscribe-header-with-divider mb-2 mt-2">
                                                        <span class="courscribe-title-sm">Forms the Objectives</span>
                                                        <div class="courscribe-divider"></div>
                                                    </div>
                                                    <div class="objective-row mb-4 w-100">
                                                        <label for="action-verb-<?php echo $objective_id; ?>">By the end of this Module they will: Objective <?php echo $objective_number; ?></label>
                                                        <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                                                            <?php if ($is_client) : ?>
                                                                <div class="courscribe-row">
                                                                    <span class="client-preview-action-verb"><?php echo esc_html($action_verb ?: 'Not set'); ?></span>
                                                                    <div class="client-preview-action-verb-description">
                                                                        <?php echo esc_attr($description); ?>
                                                                    </div>
                                                                </div>
                                                            <?php else : ?>
                                                                <select id="module-action-verb-<?php echo $objective_id; ?>" 
                                                                        class="form-control bg-dark text-light cs-action-verb" 
                                                                        data-objective-id="<?php echo $objective_id; ?>" 
                                                                        data-module-id="<?php echo $module->ID; ?>"
                                                                        style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                                                    <!-- This will be populated by JavaScript -->
                                                                    <?php if ($action_verb) : ?>
                                                                        <option value="<?php echo esc_attr($action_verb); ?>" selected><?php echo esc_html($action_verb); ?></option>
                                                                    <?php endif; ?>
                                                                </select>
                                                                <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                                                                    <textarea 
                                                                        id="module-objective-description-<?php echo $module->ID; ?>-<?php echo $objective_id; ?>"
                                                                        class="form-control bg-dark text-light cs-objective-description"
                                                                        data-objective-id="<?php echo $objective_id; ?>"
                                                                        data-module-id="<?php echo $module->ID; ?>"
                                                                        placeholder="Enter objective description"
                                                                        rows="2"
                                                                        style="flex:1"><?php echo esc_textarea($description); ?></textarea>
                                                                    <?php
                                                                    $ai_button = '<button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                                                                        data-field-id="module-objective-description-' . $module->ID . '-' . $objective_id . '"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#inputAiSuggestionsModal"
                                                                        data-module-id="' . esc_attr($module->ID) . '"
                                                                        data-module-name="' . esc_attr($module->post_title) . '"
                                                                        data-module-goal="' . esc_attr($module_goal) . '"
                                                                        data-course-name="' . esc_attr($course_title) . '"
                                                                        data-course-goal="' . esc_attr($course_goal) . '"
                                                                        data-thinking-skill="' . esc_attr($thinking_skill) . '"
                                                                        data-action-verb="' . esc_attr($action_verb) . '">
                                                                        <i class="fa fa-magic"></i>
                                                                    </button>';
                                                                    echo $tooltips->wrap_button_with_tooltip($ai_button, [
                                                                        'description' => 'Get AI-generated suggestions for your module objective (requires CourScribe Pro)',
                                                                        'required_package' => 'CourScribe Pro (Agency)',
                                                                        'title' => 'Get AI-generated suggestions'
                                                                    ]);
                                                                    ?>
                                                                </div>
                                                            <?php endif; ?>
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
                                    <button id="addModuleListObjectiveBtn" 
                                            type="button" 
                                            class="add-objective mb-4 cs-add-objective" 
                                            data-module-id="<?php echo $module->ID; ?>">
                                        <i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add Objective
                                    </button>
                                    <?php endif ?>
                                </div>
                                <?php
                                // Check if module has lessons with teaching points before showing additional fields
                                $lessons_with_points = get_posts([
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
                                
                                $has_lessons_with_teaching_points = false;
                                foreach ($lessons_with_points as $lesson) {
                                    $teaching_points = get_post_meta($lesson->ID, '_teaching_points', true);
                                    if (!empty($teaching_points) && is_array($teaching_points)) {
                                        $has_lessons_with_teaching_points = true;
                                        break;
                                    }
                                }
                                ?>
                                
                                <?php if ($has_lessons_with_teaching_points) : ?>
                                <div class="cs-module-additional-fields-<?php echo esc_attr($module->ID); ?> mt-4">
                                    <div class="cs-premium-tabs-wrapper-<?php echo esc_attr($module->ID); ?>">
                                        <h6 class="cs-additional-details-title mb-3">
                                            <i class="fas fa-layer-group me-2"></i>Additional Details
                                        </h6>
                                        <div class="cs-premium-tab-nav-<?php echo esc_attr($module->ID); ?>">
                                            <button class="cs-premium-tab-btn active" 
                                                    data-tab="cs-tab-methods-<?php echo esc_attr($module->ID); ?>" 
                                                    data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                <i class="fas fa-cogs me-2"></i>Methods
                                            </button>
                                            <button class="cs-premium-tab-btn" 
                                                    data-tab="cs-tab-materials-<?php echo esc_attr($module->ID); ?>" 
                                                    data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                <i class="fas fa-book me-2"></i>Materials
                                            </button>
                                            <button class="cs-premium-tab-btn" 
                                                    data-tab="cs-tab-media-<?php echo esc_attr($module->ID); ?>" 
                                                    data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                <i class="fas fa-photo-video me-2"></i>Media
                                            </button>
                                        </div>
                                                <div class="cs-premium-tab-contents-<?php echo esc_attr($module->ID); ?>">
                                                    <!-- Methods -->
                                                    <div class="cs-premium-tab-content active" 
                                                         id="cs-tab-methods-<?php echo esc_attr($module->ID); ?>" 
                                                         data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                        <div class="method-group">
                                                            <div class="mb-3">
                                                                
                                                                <div class="courscribe-header-with-divider mb-2">
                                                                    <span class="courscribe-title-sm">Methods:</span>
                                                                    <div class="courscribe-divider"></div>
                                                                    <?php if ($is_client) : ?>
                                                                        <div 
                                                                            class="courscribe-client-review-submit-button"
                                                                            data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                            data-field-name="methods-<?php echo esc_attr($module->ID); ?>"
                                                                            data-field-id="methods-<?php echo esc_attr($module->ID); ?>"
                                                                            data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                                            data-current-field-value="<?php echo esc_attr(json_encode(maybe_unserialize(get_post_meta($module->ID, '_module_methods', true)) ?: [])); ?>"
                                                                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                            data-post-type="crscribe_module"
                                                                            data-field-type="methods"
                                                                            data-bs-toggle="offcanvas"
                                                                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                        ><span>Give Methods Feedback</span></div>
                                                                    <?php elseif ($can_view_feedback) : ?>
                                                                        <div class="text-end">                          
                                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                                data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                                data-field-name="methods-<?php echo esc_attr($module->ID); ?>"
                                                                                data-field-id="methods-<?php echo esc_attr($module->ID); ?>"
                                                                                data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                                                data-current-field-value="<?php echo esc_attr(json_encode(maybe_unserialize(get_post_meta($module->ID, '_module_methods', true)) ?: [])); ?>"
                                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                                data-post-type="crscribe_module"
                                                                                data-field-type="methods"
                                                                                data-bs-toggle="offcanvas"
                                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                            >
                                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Methods Feedback</span>
                                                                                <span class="text">5</span>
                                                                            </div>
                                                                        </div>
                                                                        <?php if (!$is_client) : ?>
                                                                            <?php
                                                                            $remove_button = '<button type="button" style="margin-top: 12px;" data-module-id="' . esc_attr($module->ID) . '" class="add-method remove-btn btn-sm courscribe-save-button"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add New Method</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Add New Method',
                                                                                'description' => 'Add new method to this module. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                            ?>
                                                                        <?php endif; ?>
                                                                    <?php else : ?>
                                                                        <?php
                                                                         $remove_button = '<button type="button" style="margin-top: 12px;" data-module-id="' . esc_attr($module->ID) . '" class="add-method remove-btn btn-sm courscribe-save-button"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add New Method</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Add New Method',
                                                                                'description' => 'Add new method to this module. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                        ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <ul id="module-methods-list-<?php echo $module->ID; ?>" class="methods-list">
                                                                    <?php
                                                                    $methods = maybe_unserialize(get_post_meta($module->ID, '_module_methods', true)) ?: [];
                                                                    if (!empty($methods) && is_array($methods)) {
                                                                        foreach ($methods as $index => $method) {
                                                                            $method_type = isset($method['method_type']) ? esc_html($method['method_type']) : '';
                                                                            $title = isset($method['title']) ? esc_html($method['title']) : '';
                                                                            $location = isset($method['location']) ? esc_html($method['location']) : '';
                                                                            ?>
                                                                            <li class="moodule-conteiner-body methods-container mb-2" data-method-index="<?php echo $index; ?>">
                                                                                <div class="module-body">
                                                                                    <h5>Method <?php echo $index + 1; ?></h5>
                                                                                    <div class="method-group mb-4">
                                                                                        <div class="row mb-2">
                                                                                            <div class="col-md-6">
                                                                                                <label for="method-type-<?php echo $index; ?>" class="form-label">Method Type</label>
                                                                                                <select 
                                                                                                class="form-control bg-dark text-light method-type" 
                                                                                                data-method-index="<?php echo $index; ?>" 
                                                                                                <?php if($is_client) echo 'disabled'; ?>
                                                                                                >
                                                                                                    <?php
                                                                                                    $method_types = ['Live', 'Webinar', 'Online', 'Self-Paced'];
                                                                                                    foreach ($method_types as $type) {
                                                                                                        echo '<option value="' . esc_attr($type) . '"' . selected($method_type, $type, false) . '>' . esc_html($type) . '</option>';
                                                                                                    }
                                                                                                    ?>
                                                                                                </select>
                                                                                            </div>
                                                                                            <div class="col-md-6">
                                                                                                <label for="method-title-<?php echo $index; ?>" class="form-label">Title</label>
                                                                                                <input type="text" class="form-control bg-dark text-light method-title" data-method-index="<?php echo $index; ?>" value="<?php echo $title; ?>" <?php if($is_client) echo 'readonly'; ?> placeholder="Enter method title" />
                                                                                            </div>
                                                                                            <div class="col-md-12">
                                                                                                <label for="method-location-<?php echo $index; ?>" class="form-label">Location/Link</label>
                                                                                                <input type="text" class="form-control bg-dark text-light method-location" data-method-index="<?php echo $index; ?>" value="<?php echo $location; ?>" <?php if($is_client) echo 'readonly'; ?> placeholder="Enter location or link" />
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="delete-aside delete-method">
                                                                                    <?php if (!$is_client) : ?>
                                                                                    <button class="module-delete-button delete-method" type="button" data-method-index="<?php echo $index; ?>">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#E4B26F">
                                                                                            <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                                                                        </svg>
                                                                                    </button>
                                                                                    <?php endif ?>
                                                                                </div>
                                                                            </li>
                                                                            <?php
                                                                        }
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Materials -->
                                                    <div class="cs-premium-tab-content" 
                                                         id="cs-tab-materials-<?php echo esc_attr($module->ID); ?>" 
                                                         data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                        <div class="method-group">
                                                            <div class="mb-3">
                                                                <div class="courscribe-header-with-divider mb-2">
                                                                    <span class="courscribe-title-sm">Materials:</span>
                                                                    <div class="courscribe-divider"></div>
                                                                    <?php if ($is_client) : ?>
                                                                        <div 
                                                                            class="courscribe-client-review-submit-button"
                                                                            data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                            data-field-name="materials-<?php echo esc_attr($module->ID); ?>"
                                                                            data-field-id="materials-<?php echo esc_attr($module->ID); ?>"
                                                                            data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                                            data-current-field-value="<?php echo esc_attr(json_encode(maybe_unserialize(get_post_meta($module->ID, '_module_materials', true)) ?: [])); ?>"
                                                                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                            data-post-type="crscribe_module"
                                                                            data-field-type="materials"
                                                                            data-bs-toggle="offcanvas"
                                                                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                        ><span>Give Materials Feedback</span></div>
                                                                    <?php elseif ($can_view_feedback) : ?>
                                                                        <div class="text-end">                          
                                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                                data-course-id="<?php echo esc_attr($module->ID); ?>" 
                                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                                data-field-name="materials-<?php echo esc_attr($module->ID); ?>"
                                                                                data-field-id="materials-<?php echo esc_attr($module->ID); ?>"
                                                                                data-post-name="<?php echo esc_attr(get_the_title($module->ID)); ?>"
                                                                                data-current-field-value="<?php echo esc_attr(json_encode(maybe_unserialize(get_post_meta($module->ID, '_module_materials', true)) ?: [])); ?>"
                                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                                data-post-type="crscribe_module"
                                                                                data-field-type="materials"
                                                                                data-bs-toggle="offcanvas"
                                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                                            >
                                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Materials Feedback</span>
                                                                                <span class="text">5</span>
                                                                            </div>
                                                                        </div>
                                                                        <?php if (!$is_client) : ?>
                                                                            <?php
                                                                            $remove_button = '<button type="button" style="margin-top: 12px;" data-module-id="' . esc_attr($module->ID) . '" class="add-material remove-btn btn-sm courscribe-save-button"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add New Material</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Add New Material',
                                                                                'description' => 'Add new material to this module. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                            ?>
                                                                        <?php endif; ?>
                                                                    <?php else : ?>
                                                                        <?php
                                                                        $remove_button = '<button type="button" style="margin-top: 12px;" data-module-id="' . esc_attr($module->ID) . '" class="add-material remove-btn btn-sm courscribe-save-button"><i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add New Material</button>';
                                                                            echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                                                                'title' => 'Add New Material',
                                                                                'description' => 'Add new material to this module. Available in all packages.',
                                                                                'required_package' => 'CourScribe Basics'
                                                                            ]);
                                                                        ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <ul id="module-materials-list-<?php echo $module->ID; ?>" class="materials-list">
                                                                    <?php
                                                                    $materials = maybe_unserialize(get_post_meta($module->ID, '_module_materials', true)) ?: [];
                                                                    if (!empty($materials) && is_array($materials)) {
                                                                        foreach ($materials as $index => $material) {
                                                                            $material_type = isset($material['material_type']) ? esc_html($material['material_type']) : '';
                                                                            $title = isset($material['title']) ? esc_html($material['title']) : '';
                                                                            $link = isset($material['link']) ? esc_url($material['link']) : '';
                                                                            ?>
                                                                            <li class="moodule-conteiner-body material-item mb-2" data-material-index="<?php echo $index; ?>">
                                                                                <div class="module-body">
                                                                                    <h5>Material <?php echo $index + 1; ?></h5>
                                                                                    <div class="row mb-2">
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label">Material Type</label>
                                                                                            <select 
                                                                                            class="form-control bg-dark text-light material-type" 
                                                                                            data-material-index="<?php echo $index; ?>"
                                                                                            <?php if($is_client) echo 'disabled'; ?>
                                                                                            >
                                                                                                <?php
                                                                                                $material_types = ['Document', 'Video', 'Audio', 'Link', 'Physical'];
                                                                                                foreach ($material_types as $type) {
                                                                                                    echo '<option value="' . esc_attr($type) . '"' . selected($material_type, $type, false) . '>' . esc_html($type) . '</option>';
                                                                                                }
                                                                                                ?>
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <label class="form-label">Title</label>
                                                                                            <input type="text" class="form-control bg-dark text-light material-title" data-material-index="<?php echo $index; ?>" value="<?php echo $title; ?>" <?php if($is_client) echo 'readonly'; ?> placeholder="Enter material title" />
                                                                                        </div>
                                                                                        <div class="col-md-12">
                                                                                            <label class="form-label">Link</label>
                                                                                            <input type="url" class="form-control bg-dark text-light material-link" data-material-index="<?php echo $index; ?>" value="<?php echo $link; ?>" <?php if($is_client) echo 'readonly'; ?> placeholder="Enter material link" />
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="delete-aside delete-material">
                                                                                    <?php if (!$is_client) : ?>
                                                                                    <button class="module-delete-button delete-material" type="button" data-material-index="<?php echo $index; ?>">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#E4B26F">
                                                                                            <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                                                                        </svg>
                                                                                    </button>
                                                                                    <?php endif ?>
                                                                                </div>
                                                                            </li>
                                                                            <?php
                                                                        }
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Media -->
                                                    <div class="cs-premium-tab-content" 
                                                         id="cs-tab-media-<?php echo esc_attr($module->ID); ?>" 
                                                         data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                        <div class="method-group">
                                                            <div class="mb-3">
                                                                <label>Media</label>
                                                                <div class="media-upload-wrapper" id="media-dropzone-<?php echo $module->ID; ?>">
                                                                    <p>Drag & drop media here, or click to select</p>
                                                                    <input type="file" id="media-<?php echo $module->ID; ?>" name="media[<?php echo $module->ID; ?>][]" class="form-control-file" accept="image/*,video/*,audio/*" multiple>
                                                                </div>
                                                                <div id="media-preview-grid-<?php echo $module->ID; ?>" class="media-preview-grid">
                                                                    <div class="row">
                                                                        <?php
                                                                        if (!empty($module_media) && is_array($module_media)) {
                                                                            foreach ($module_media as $media_url) {
                                                                                $media_type = wp_check_filetype($media_url)['type'];
                                                                                ?>
                                                                                <div class="col-md-3 media-item">
                                                                                    <?php if (strpos($media_type, 'image') !== false): ?>
                                                                                        <img src="<?php echo esc_url($media_url); ?>" class="media-preview img-fluid" alt="Media Image" />
                                                                                    <?php elseif (strpos($media_type, 'video') !== false): ?>
                                                                                        <video controls class="media-preview">
                                                                                            <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_type); ?>">
                                                                                            Your browser does not support the video tag.
                                                                                        </video>
                                                                                    <?php elseif ($media_type === 'application/pdf'): ?>
                                                                                        <embed src="<?php echo esc_url($media_url); ?>" type="application/pdf" class="media-preview" />
                                                                                    <?php else: ?>
                                                                                        <div class="file-icon"><i class="fas fa-file"></i> <?php echo basename($media_url); ?></div>
                                                                                    <?php endif; ?>
                                                                                    <button type="button" class="btn btn-sm btn-danger delete-media">Remove</button>
                                                                                </div>
                                                                                <?php
                                                                            }
                                                                        }
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                                <div class="uploaded-files" id="media-preview-grid-<?php echo $module->ID; ?>">
                                                                    <p class="uploaded-text">Uploaded Files</p>
                                                                    <div class="row">
                                                                        <?php
                                                                        $media = maybe_unserialize(get_post_meta($module->ID, '_module_media', true)) ?: [];
                                                                        if (!empty($media) && is_array($media)) {
                                                                            foreach ($media as $media_url) {
                                                                                $file_ext = pathinfo($media_url, PATHINFO_EXTENSION);
                                                                                ?>
                                                                                <div class="col-md-3 media-item">
                                                                                    <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) : ?>
                                                                                        <img src="<?php echo esc_url($media_url); ?>" class="media-preview img-fluid" alt="Media Image" />
                                                                                    <?php elseif (in_array($file_ext, ['mp4', 'mov', 'avi'])) : ?>
                                                                                        <video controls class="media-preview">
                                                                                            <source src="<?php echo esc_url($media_url); ?>" type="video/<?php echo $file_ext; ?>">
                                                                                            Your browser does not support the video tag.
                                                                                        </video>
                                                                                    <?php elseif (in_array($file_ext, ['pdf'])) : ?>
                                                                                        <embed src="<?php echo esc_url($media_url); ?>" type="application/pdf" class="media-preview" />
                                                                                    <?php else : ?>
                                                                                        <a href="<?php echo esc_url($media_url); ?>" target="_blank">
                                                                                            <div class="file-icon"><i class="fas fa-file"></i> Download File</div>
                                                                                        </a>
                                                                                    <?php endif; ?>
                                                                                    <?php if (!$is_client) : ?>
                                                                                    <button type="button" class="btn btn-sm btn-danger delete-media" data-media-url="<?php echo esc_url($media_url); ?>">Remove</button>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <?php
                                                                            }
                                                                        } else {
                                                                            echo '<p>No media uploaded yet.</p>';
                                                                        }
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else : ?>
                                <div class="module-additional-fields-placeholder mt-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(228, 178, 111, 0.1); border-radius: 12px; padding: 20px; text-align: center;">
                                    <div class="placeholder-content">
                                        <i class="fas fa-lock" style="font-size: 48px; color: rgba(228, 178, 111, 0.5); margin-bottom: 16px;"></i>
                                        <h6 style="color: #E4B26F; margin-bottom: 12px;">Additional Details Locked</h6>
                                        <p style="color: rgba(255,255,255,0.6); margin-bottom: 0;">
                                            Methods, Materials, and Media fields will be unlocked once you add lessons with teaching points to this module.
                                        </p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="cs-module-actions-row-<?php echo esc_attr($module->ID); ?> mt-3 mb-2">
                                    <?php if (!$is_client) : ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button class="btn cs-save-module-btn-<?php echo esc_attr($module->ID); ?> courscribe-stepper-prevBtn" 
                                                data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-course-id="<?php echo esc_attr($course_id); ?>">
                                            <i class="fas fa-save me-2"></i>Save Module Changes
                                        </button>
                                        <div class="cs-module-danger-actions" id="cs-module-danger-actions-<?php echo esc_attr($module->ID); ?>">
                                            <button class="btn cs-archive-module-<?php echo esc_attr($module->ID); ?>" 
                                                    type="button" 
                                                    data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                    data-course-id="<?php echo esc_attr($course_id); ?>" 
                                                    title="Archive this module">
                                                <i class="fas fa-archive me-1"></i>Archive
                                            </button>
                                            <button class="btn btn-danger cs-delete-module-<?php echo esc_attr($module->ID); ?>" 
                                                    type="button" 
                                                    data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                    data-course-id="<?php echo esc_attr($course_id); ?>" 
                                                    title="Delete this module permanently">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="cs-no-modules-message-' . esc_attr($course_id) . ' text-center p-4"><p>No active modules added yet.</p></div>';
            }
            ?>
            </div>
            
            <!-- Archived Modules -->
            <?php if (!$is_client) : ?>
            <div class="cs-modules-list-archived-<?php echo esc_attr($course_id); ?> d-none" id="cs-modules-archived-<?php echo esc_attr($course_id); ?>">
                <?php if (!empty($archived_modules)) : ?>
                    <?php foreach ($archived_modules as $module) : ?>
                        <div class="cs-archived-module-item-<?php echo esc_attr($module->ID); ?> archived-module" 
                             data-module-id="<?php echo esc_attr($module->ID); ?>">
                            <div class="cs-archived-module-content p-3 border rounded mb-3" style="background: rgba(255,255,255,0.05); border-color: rgba(255,193,7,0.3) !important;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-warning mb-1"><?php echo esc_html($module->post_title); ?></h6>
                                        <small class="text-muted">Archived on <?php echo esc_html(get_the_modified_date('M j, Y', $module->ID)); ?></small>
                                    </div>
                                    <div class="cs-archived-actions-<?php echo esc_attr($module->ID); ?>">
                                        <button class="btn btn-success btn-sm cs-restore-module-<?php echo esc_attr($module->ID); ?>" 
                                                data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                                title="Restore this module">
                                            <i class="fas fa-undo me-1"></i>Restore
                                        </button>
                                        <button class="btn btn-danger btn-sm cs-permanent-delete-<?php echo esc_attr($module->ID); ?>" 
                                                data-module-id="<?php echo esc_attr($module->ID); ?>" 
                                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                                title="Delete permanently">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="cs-no-archived-message-<?php echo esc_attr($course_id); ?> text-center p-4">
                        <p class="text-muted">No archived modules.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Premium Additional Details Styling -->
    <style>
        .w-100 {
            width: 100% !important;
        }
        .cs-objective-item{
            flex-direction: column;
        }
        .cs-premium-tabs-wrapper-<?php echo esc_js($course_id); ?> {
            background: linear-gradient(135deg, rgba(228, 178, 111, 0.1) 0%, rgba(248, 146, 62, 0.05) 100%);
            border-radius: 16px;
            padding: 20px;
            margin: 16px 0;
            border: 1px solid rgba(228, 178, 111, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .cs-premium-tab-nav-<?php echo esc_js($course_id); ?> {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            background: rgba(47, 46, 48, 0.6);
            border-radius: 12px;
            padding: 6px;
        }
        
        .cs-premium-tab-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .cs-premium-tab-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(228, 178, 111, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .cs-premium-tab-btn:hover:before {
            left: 100%;
        }
        
        .cs-premium-tab-btn.active {
            background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(228, 178, 111, 0.3);
            transform: translateY(-1px);
        }
        
        .cs-premium-tab-btn:hover {
            color: #E4B26F;
            background: rgba(228, 178, 111, 0.1);
        }
        
        .cs-premium-tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .cs-premium-tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Module Toggle Buttons */
        .cs-toggle-btn {
            background: rgba(47, 46, 48, 0.8);
            border: 1px solid rgba(228, 178, 111, 0.3);
            color: rgba(255, 255, 255, 0.7);
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .cs-toggle-btn.active, .cs-toggle-btn:hover {
            background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
        }
        
        /* Validation States */
        .cs-field-error {
            border: 2px solid #dc3545 !important;
            background: rgba(220, 53, 69, 0.1) !important;
        }
        
        .cs-field-success {
            border: 2px solid #28a745 !important;
            background: rgba(40, 167, 69, 0.1) !important;
        }
        
        .cs-field-saving {
            border: 2px solid #ffc107 !important;
            background: rgba(255, 193, 7, 0.1) !important;
        }
    </style>
    
   <script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing CourScribe Modules...');
    
    // Check if the main object exists
    if (typeof CourScribeModulesPremium !== 'undefined') {
        // Initialize with configuration
        CourScribeModulesPremium.init({
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            courseId: <?php echo $course_id; ?>,
            curriculumId: <?php echo $curriculum_id; ?>,
            moduleNonce: '<?php echo wp_create_nonce('courscribe_module_nonce'); ?>',
            userId: <?php echo get_current_user_id(); ?>
        });
        
        // Also add to window for debugging
        window.courscribeModules = CourScribeModulesPremium;
        console.log('CourScribe Modules initialized successfully');
    } else {
        console.error('CourScribeModulesPremium object not found!');
    }
    
    // Quick debug: Check if jQuery is loaded
    console.log('jQuery loaded?', typeof jQuery !== 'undefined');
    
    // Test click handler
    $(document).on('click', '.test-objective-btn', function() {
        console.log('Objective button clicked!', this);
    });
});
</script>
    
    <?php
}