<?php
// Path: courscribe/templates/template-parts/modules-premium.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue assets for modules premium functionality
 */
if (!function_exists('courscribe_enqueue_modules_premium_assets')) {
    function courscribe_enqueue_modules_premium_assets($course_id, $curriculum_id) {
        // Get plugin URL for assets
        $plugin_url = plugin_dir_url(__FILE__);
        $plugin_url = str_replace('/templates/template-parts/', '/', $plugin_url);
        
        // Enqueue Sortable.js for drag and drop
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
            [],
            '1.15.0',
            true
        );
        
        // Enqueue our enhanced modules premium script
        wp_enqueue_script(
            'courscribe-modules-premium-enhanced',
            $plugin_url . 'assets/js/courscribe/modules/modules-premium-enhanced.js',
            ['jquery', 'sortablejs'],
            filemtime(plugin_dir_path(__FILE__) . '../../../assets/js/courscribe/modules/modules-premium-enhanced.js'),
            true
        );

        wp_enqueue_script(
            'courscribe-modules-premium-input-field-suggestions',
            $plugin_url . 'assets/js/courscribe/ai/input-field-suggestions.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . '../../../assets/js/courscribe/ai/input-field-suggestions.js'),
            true
        );
        
        // Localize script with enhanced configuration data
        wp_localize_script(
            'courscribe-modules-premium-enhanced',
            'CourScribeModulesConfig',
            [
                'courseId' => $course_id,
                'curriculumId' => $curriculum_id,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'moduleNonce' => wp_create_nonce('courscribe_module_nonce'),
                'pluginUrl' => $plugin_url,
                'isClient' => in_array('client', (array) wp_get_current_user()->roles),
                'canViewFeedback' => current_user_can('edit_posts')
            ]
        );

        // Also provide global ajaxurl for compatibility
        wp_localize_script(
            'courscribe-modules-premium-enhanced',
            'ajaxurl',
            admin_url('admin-ajax.php')
        );
        
        // Initialize the enhanced script
        wp_add_inline_script(
            'courscribe-modules-premium-enhanced',
            'jQuery(document).ready(function($) {
                if (typeof CourScribeModulesPremium !== "undefined") {
                    CourScribeModulesPremium.init(CourScribeModulesConfig);
                }
            });'
        );
    }
}

/**
 * Premium Modules template with enhanced functionality
 *
 * @param array $args {
 *     @type int    $course_id         Course post ID
 *     @type string $course_title      Course title
 *     @type int    $curriculum_id     Curriculum post ID
 *     @type object $tooltips          CourScribe_Tooltips instance
 *     @type string $site_url          Site URL for assets
 * }
 */
function courscribe_render_modules_premium($args = []) {
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
    
    // Enqueue required scripts and styles
    courscribe_enqueue_modules_premium_assets($course_id, $curriculum_id);
    
    // Determine user roles
    $current_user = wp_get_current_user();
    $is_client = in_array('client', (array) $current_user->roles);
    $is_studio_admin = in_array('studio_admin', (array) $current_user->roles);
    $is_collaborator = in_array('collaborator', (array) $current_user->roles);
    $can_view_feedback = $is_studio_admin || $is_collaborator;
    
    // Enhanced function to get modules with proper status handling
    if (!function_exists('get_modules_for_course_premium')) {
        function get_modules_for_course_premium($course_id, $include_archived = false) {
            $post_statuses = $include_archived ? ['publish', 'archived'] : ['publish'];
            
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

    <link rel="stylesheet" href="<?php echo esc_url(home_url()); ?>/wp-content/plugins/courscribe/assets/css/modules-premium-enhanced.css">
    <div class="cs-modules-premium-container">
        <!-- Course Goal Banner -->
        <div class="cs-course-goal-banner">
            <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" class="cs-goal-icon">
            <span class="cs-goal-label">Course Goal:</span>
            <span class="cs-goal-text"><?php echo $course_goal; ?></span>
        </div>

        <!-- Header with Controls -->
        <div class="courscribe-header-with-divider mb-4">
            <span class="add-curriculum-text">Modules:</span>
            <div class="courscribe-divider"></div>
            <?php if (!$is_client) : ?>
                <?php
                $generate_modules_button = '
                <button 
                id="courscribe-ai-generate-modules-button-' . esc_attr($course_id) .'"
                class="get-ai-button min-w-150" 
                data-bs-toggle="modal"
                data-bs-target="#courseGenerateModulesModal"
                aria-controls="courseGenerateModulesModal"
                data-course-id="' . esc_attr($course_id) . '"
                data-curriculum-id="' . esc_attr($curriculum_id) . '"
                data-studio-id="' . esc_attr($studio_id) . '"
                >
                    <span class="get-ai-inner">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                            <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                        </svg>
                        Generate Modules
                    </span>
                </button>
                ';

                echo $tooltips->wrap_button_with_tooltip($generate_modules_button, [
                    'title' => 'Generate modules',
                    'description' => "Generate modules with AI",
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

        <!-- Module View Toggle -->
        <?php if (!$is_client) : ?>
        <div class="cs-module-view-toggle" id="cs-toggle-<?php echo esc_attr($course_id); ?>">
            <button type="button" class="cs-toggle-btn active" data-view="active" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-list me-2"></i>Active Modules
            </button>
            <button type="button" class="cs-toggle-btn" data-view="archived" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-archive me-2"></i>Archived Modules
            </button>
        </div>
        
        <!-- Module Controls (Search, Filter, Sort) -->
        <!-- <div class="cs-module-controls" id="cs-module-controls-<?php echo esc_attr($course_id); ?>">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text" style="background: rgba(228, 178, 111, 0.1); border: 1px solid rgba(228, 178, 111, 0.3); color: #E4B26F;">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="cs-search-input" id="cs-module-search-<?php echo esc_attr($course_id); ?>" placeholder="Search modules..." data-course-id="<?php echo esc_attr($course_id); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="cs-filter-select" id="cs-module-filter-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                        <option value="all">All Modules</option>
                        <option value="with-lessons">With Lessons</option>
                        <option value="without-lessons">Without Lessons</option>
                        <option value="recent">Recently Modified</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="cs-filter-select" id="cs-module-sort-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                        <option value="default">Default Order</option>
                        <option value="title-asc">Title A-Z</option>
                        <option value="title-desc">Title Z-A</option>
                        <option value="date-asc">Oldest First</option>
                        <option value="date-desc">Newest First</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="cs-btn-primary" id="cs-reset-filters-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div> -->
        <?php endif; ?>

        <!-- Active Modules -->
        <div class="cs-modules-active" id="cs-modules-active-<?php echo esc_attr($course_id); ?>">
            <div class="cs-modules-container cs-sortable-container" id="cs-modules-container-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
            <?php
            $modules = get_modules_for_course_premium($course_id, false); // Active modules only
            
            if (!empty($modules)) {
                foreach ($modules as $module) {
                    $module_id = $module->ID;
                    $is_ai_generated = get_post_meta($module_id, '_ai_generated', true);
                    $module_goal = esc_html(get_post_meta($module_id, '_module_goal', true));
                    $module_objectives = maybe_unserialize(get_post_meta($module_id, '_module_objectives', true)) ?: [];
                    $module_methods = maybe_unserialize(get_post_meta($module_id, '_module_methods', true)) ?: [];
                    $module_materials = maybe_unserialize(get_post_meta($module_id, '_module_materials', true)) ?: [];
                    $module_media = maybe_unserialize(get_post_meta($module_id, '_module_media', true)) ?: [];
                    ?>
                    <div class="cs-module-item" id="cs-module-<?php echo esc_attr($module_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                        <?php if (!$is_client) : ?>
                        <div class="cs-module-header">
                            <div class="cs-drag-handle" title="Drag to reorder">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <!-- <div class="cs-action-buttons">
                                <button class="cs-btn-archive" 
                                data-module-id="<?php echo esc_attr($module_id); ?>" 
                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                data-module-title="<?php echo esc_attr($module->post_title); ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#cs-archiveModuleModal-<?php echo esc_attr($module_id); ?>"
                                title="Archive Module">
                                    <i class="fas fa-archive"></i>
                                    Archive
                                </button>
                                <button class="cs-btn-delete" 
                                data-module-id="<?php echo esc_attr($module_id); ?>" 
                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                data-module-title="<?php echo esc_attr($module->post_title); ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#cs-deleteModuleModal-<?php echo esc_attr($module_id); ?>"
                                title="Delete Module">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            </div> -->
                        </div>
                        <?php endif; ?>

                        <!-- Module Feedback -->
                        <div class="cs-field-group">
                            <div class="courscribe-header-with-divider mb-3">
                                <span class="courscribe-title-sm">Module Feedback</span>
                                <div class="courscribe-divider"></div>
                                <?php if ($is_client) : ?>
                                    <div 
                                        class="courscribe-client-review-submit-button"
                                        data-course-id="<?php echo esc_attr($module_id); ?>" 
                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                        data-field-name="courses-module-review[<?php echo esc_attr($module_id); ?>]"
                                        data-field-id="courses-module-review-<?php echo esc_attr($module_id); ?>"
                                        data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
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
                                        data-course-id="<?php echo esc_attr($module_id); ?>" 
                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                        data-field-name="courses-module-review[<?php echo esc_attr($module_id); ?>]"
                                        data-field-id="courses-module-review-<?php echo esc_attr($module_id); ?>"
                                        data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
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

                        <!-- Module Name -->
                        <div class="cs-field-group">
                            <label class="cs-field-label" for="cs-module-name-<?php echo esc_attr($module_id); ?>">Module Name</label>
                            <?php if ($is_client) : ?>
                                <div class="courscribe-client-review-input-group">
                                    <input 
                                        class="courscribe-client-review-input cs-field-input"
                                        name="courses-client-review-input-[<?php echo esc_attr($module_id); ?>][module_name]" 
                                        placeholder="Enter new item here" 
                                        type="text" 
                                        value="<?php echo esc_attr($module->post_title); ?>" 
                                        id="courscribe-client-review-input-field" 
                                        disabled>
                                    <div 
                                        class="courscribe-client-review-submit-button"
                                        data-course-id="<?php echo esc_attr($module_id); ?>" 
                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                        data-field-name="modules[<?php echo esc_attr($module_id); ?>][module_name]"
                                        data-field-id="module-name-<?php echo esc_attr($module_id); ?>"
                                        data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
                                        data-current-field-value="<?php echo esc_attr($module->post_title); ?>"
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
                                    <input type="text" 
                                           id="cs-module-name-<?php echo esc_attr($module_id); ?>"
                                           name="module_name[<?php echo esc_attr($module_id); ?>]"
                                           class="cs-field-input cs-module-field" 
                                           value="<?php echo esc_attr($module->post_title); ?>"
                                           placeholder="Enter module name"
                                           maxlength="100"
                                           data-module-id="<?php echo esc_attr($module_id); ?>"
                                           data-field-type="name" />
                                    
                                    <?php if ($can_view_feedback) : ?>                    
                                        <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                            data-course-id="<?php echo esc_attr($module_id); ?>" 
                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                            data-field-name="modules[<?php echo esc_attr($module_id); ?>][module_name]"
                                            data-field-id="module-name-<?php echo esc_attr($module_id); ?>"
                                            data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
                                            data-current-field-value="<?php echo esc_attr($module->post_title); ?>"
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

                        <!-- Module Goal -->
                        <div class="cs-field-group">
                            <label class="cs-field-label" for="cs-module-goal-<?php echo esc_attr($module_id); ?>">Module Goal</label>
                            <?php if ($is_client) : ?>
                                <div class="courscribe-client-review-input-group">
                                    <textarea 
                                        class="courscribe-client-review-input cs-field-textarea"
                                        name="courses-client-review-input-[<?php echo esc_attr($module_id); ?>][module_goal]" 
                                        placeholder="Enter new item here" 
                                        rows="3"
                                        id="courscribe-client-review-input-field" 
                                        disabled><?php echo esc_textarea($module_goal); ?></textarea>
                                    <div 
                                        class="courscribe-client-review-submit-button"
                                        data-course-id="<?php echo esc_attr($module_id); ?>" 
                                        data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                        data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                        data-field-name="modules[<?php echo esc_attr($module_id); ?>][module_goal]"
                                        data-field-id="module-goal-<?php echo esc_attr($module_id); ?>"
                                        data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
                                        data-current-field-value="<?php echo esc_attr($module_goal); ?>"
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
                                        <textarea id="cs-module-goal-<?php echo esc_attr($module_id); ?>"
                                                  name="module_goal[<?php echo esc_attr($module_id); ?>]"
                                                  class="cs-field-textarea cs-module-field" 
                                                  placeholder="Enter module goal"
                                                  rows="3"
                                                  maxlength="500"
                                                  data-module-id="<?php echo esc_attr($module_id); ?>"
                                                  data-field-type="goal"><?php echo esc_textarea($module_goal); ?></textarea>
                                        <?php
                                        $ai_button = '<button type="button" class="ai-suggest-button"
                                            data-field-id="cs-module-goal-' . esc_attr($module_id) . '"
                                            data-bs-toggle="modal"
                                            data-bs-target="#inputAiSuggestionsModal"
                                            data-module-id="' . esc_attr($module_id) . '"
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
                                            data-course-id="<?php echo esc_attr($module_id); ?>" 
                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                            data-field-name="modules[<?php echo esc_attr($module_id); ?>][module_goal]"
                                            data-field-id="module-goal-<?php echo esc_attr($module_id); ?>"
                                            data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
                                            data-current-field-value="<?php echo esc_attr($module_goal); ?>"
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

                        <!-- Module Objectives -->
                        <div class="cs-field-group">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="cs-field-label mb-0">Objectives:</h6>
                                <?php if (!$is_client) : ?>
                                <button type="button" class="cs-btn-primary cs-add-objective" data-module-id="<?php echo esc_attr($module_id); ?>">
                                    <i class="fas fa-plus me-2"></i>Add Objective
                                </button>
                                <?php endif; ?>
                            </div>
                            <div id="cs-objectives-list-<?php echo esc_attr($module_id); ?>" class="cs-objectives-container">
                                <?php
                                if (!empty($module_objectives) && is_array($module_objectives)) {
                                    $objective_number = 1;
                                    foreach ($module_objectives as $index => $objective) {
                                        $objective_id = 'objective-' . $module_id . '-' . $index;
                                        $thinking_skill = isset($objective['thinking_skill']) ? esc_html($objective['thinking_skill']) : '';
                                        $action_verb = isset($objective['action_verb']) ? esc_html($objective['action_verb']) : '';
                                        $description = isset($objective['description']) ? esc_html($objective['description']) : '';
                                        ?>
                                        <div class="cs-objective-item" data-objective-id="<?php echo esc_attr($objective_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <div class="cs-objective-header">
                                                <span class="cs-objective-title">Objective <?php echo esc_html($objective_number); ?>:</span>
                                                <div class="cs-objective-actions">
                                                    <?php if ($is_client) : ?>
                                                        <div 
                                                            class="courscribe-client-review-submit-button"
                                                            data-course-id="<?php echo esc_attr($module_id); ?>" 
                                                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                            data-field-name="modules[<?php echo esc_attr($module_id); ?>][objective-<?php echo esc_html($objective_number); ?>]"
                                                            data-field-id="objective-item-module-<?php echo esc_attr($module_id); ?>-<?php echo esc_html($objective_number); ?>"
                                                            data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
                                                            data-current-field-value="<?php echo esc_attr(json_encode($objective)); ?>"
                                                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                            data-post-type="crscribe_module"
                                                            data-field-type="objective"
                                                            data-bs-toggle="offcanvas"
                                                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                        ><span>Give Objective Feedback</span></div>
                                                    <?php else : ?>
                                                        <?php if ($can_view_feedback) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                                                                data-course-id="<?php echo esc_attr($module_id); ?>" 
                                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                                                                data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                                                                data-field-name="modules[<?php echo esc_attr($module_id); ?>][objective-<?php echo esc_html($objective_number); ?>]"
                                                                data-field-id="objective-item-module-<?php echo esc_attr($module_id); ?>-<?php echo esc_html($objective_number); ?>"
                                                                data-post-name="<?php echo esc_attr(get_the_title($module_id)); ?>"
                                                                data-current-field-value="<?php echo esc_attr(json_encode($objective)); ?>"
                                                                data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                                data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                                                                data-post-type="crscribe_module"
                                                                data-field-type="objective"
                                                                data-bs-toggle="offcanvas"
                                                                data-bs-target="#courscribeFieldFeedbackOffcanvas"
                                                                aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                                            >
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Objective Feedback</span>
                                                                <span class="text">5</span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <button type="button" class="cs-btn cs-btn-danger cs-remove-objective" data-objective-id="<?php echo esc_attr($objective_id); ?>">
                                                            <i class="fas fa-trash"></i> 
                                                        </button>
                                                        
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="cs-field-label">Thinking Skill</label>
                                                    <?php if ($is_client) : ?>
                                                        <div class="cs-field-input"><?php echo esc_html($thinking_skill ?: 'Not set'); ?></div>
                                                    <?php else : ?>
                                                        <select class="cs-field-input cs-thinking-skill" data-objective-id="<?php echo esc_attr($objective_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                                            <?php
                                                            $skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                                            foreach ($skills as $skill) {
                                                                echo '<option value="' . esc_attr($skill) . '"' . selected($thinking_skill, $skill, false) . '>' . esc_html($skill) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="cs-field-label">Action Verb</label>
                                                    <?php if ($is_client) : ?>
                                                        <div class="cs-field-input"><?php echo esc_html($action_verb ?: 'Not set'); ?></div>
                                                    <?php else : ?>
                                                        <select class="cs-field-input cs-action-verb" data-objective-id="<?php echo esc_attr($objective_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>" data-current-action-verb="<?php echo esc_attr($action_verb); ?>">
                                                            <!-- Populated dynamically by JavaScript -->
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="cs-field-group">
                                                <label class="cs-field-label">By the end of this Module they will: Objective <?php echo esc_html($objective_number); ?></label>
                                                <?php if ($is_client) : ?>
                                                    <div class="cs-field-input"><?php echo esc_html($description ?: 'Not set'); ?></div>
                                                <?php else : ?>
                                                    <div class="d-flex gap-2">
                                                        <textarea class="cs-field-textarea cs-objective-description" 
                                                                  data-objective-id="<?php echo esc_attr($objective_id); ?>"
                                                                  data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                  placeholder="Enter objective description"
                                                                  rows="2"><?php echo esc_textarea($description); ?></textarea>
                                                        <?php
                                                        $ai_button = '<button type="button" class="ai-suggest-button"
                                                            data-field-id="cs-objective-description-' . esc_attr($objective_id) . '"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#inputAiSuggestionsModal"
                                                            data-module-id="' . esc_attr($module_id) . '"
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
                                        <?php
                                        $objective_number++;
                                    }
                                } else {
                                    echo '<div class="text-muted text-center py-3">No objectives added yet.</div>';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Additional Details (Premium Tabs) -->
                        <?php
                        // Check if module has lessons with teaching points
                        $lessons_with_points = get_posts([
                            'post_type' => 'crscribe_lesson',
                            'post_status' => 'publish',
                            'numberposts' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_module_id',
                                    'value' => $module_id,
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
                        <div class="cs-premium-tabs" id="cs-premium-tabs-<?php echo esc_attr($module_id); ?>">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="cs-field-label mb-0">
                                    <i class="fas fa-layer-group me-2"></i>Additional Details
                                </h6>
                            </div>
                            
                            <div class="cs-tab-nav" id="cs-tab-nav-<?php echo esc_attr($module_id); ?>">
                                <button class="cs-tab-btn active" data-tab="cs-tab-methods-<?php echo esc_attr($module_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                    <i class="fas fa-cogs"></i> Methods
                                </button>
                                <button class="cs-tab-btn" data-tab="cs-tab-materials-<?php echo esc_attr($module_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                    <i class="fas fa-book"></i> Materials
                                </button>
                                <button class="cs-tab-btn" data-tab="cs-tab-media-<?php echo esc_attr($module_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                    <i class="fas fa-photo-video"></i> Media
                                </button>
                            </div>

                            <!-- Methods Tab - Enhanced Design -->
                            <div class="cs-tab-content active" id="cs-tab-methods-<?php echo esc_attr($module_id); ?>">
                                <!-- Header Section -->
                                <div class="cs-methods-header">
                                    <div class="cs-methods-header-content">
                                        <div class="cs-methods-title-group">
                                            <div class="cs-methods-icon">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                            </div>
                                            <div>
                                                <h6 class="cs-methods-title">Teaching Methods</h6>
                                                <p class="cs-methods-subtitle">Define how this module will be delivered</p>
                                            </div>
                                        </div>
                                        <?php if (!$is_client) : ?>
                                        <div class="cs-methods-actions">
                                            <button type="button" class="cs-btn cs-btn-primary cs-add-method" data-module-id="<?php echo esc_attr($module_id); ?>">
                                                <i class="fas fa-plus me-2"></i>Add Method
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Method Type Quick Filters -->
                                    <div class="cs-method-filters">
                                        <div class="cs-filter-pills">
                                            <button type="button" class="cs-filter-pill active" data-filter="all">
                                                <i class="fas fa-th-large me-1"></i>All Methods
                                            </button>
                                            <button type="button" class="cs-filter-pill" data-filter="live">
                                                <i class="fas fa-users me-1"></i>Live
                                            </button>
                                            <button type="button" class="cs-filter-pill" data-filter="webinar">
                                                <i class="fas fa-video me-1"></i>Webinar
                                            </button>
                                            <button type="button" class="cs-filter-pill" data-filter="online">
                                                <i class="fas fa-laptop me-1"></i>Online
                                            </button>
                                            <button type="button" class="cs-filter-pill" data-filter="self-paced">
                                                <i class="fas fa-clock me-1"></i>Self-Paced
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Methods Grid Container -->
                                <div id="cs-methods-list-<?php echo esc_attr($module_id); ?>" class="cs-methods-grid">
                                    <?php if (!empty($module_methods) && is_array($module_methods)) : ?>
                                        <?php foreach ($module_methods as $index => $method) : 
                                            $method_type = isset($method['method_type']) ? esc_html($method['method_type']) : '';
                                            $title = isset($method['title']) ? esc_html($method['title']) : '';
                                            $location = isset($method['location']) ? esc_html($method['location']) : '';
                                            $method_type_lower = strtolower($method_type);
                                            
                                            // Method type icons
                                            $method_icons = [
                                                'live' => 'fas fa-users',
                                                'webinar' => 'fas fa-video', 
                                                'online' => 'fas fa-laptop',
                                                'self-paced' => 'fas fa-clock'
                                            ];
                                            $icon = isset($method_icons[$method_type_lower]) ? $method_icons[$method_type_lower] : 'fas fa-chalkboard';
                                        ?>
                                            <div class="cs-method-card animate-fade-in" 
                                                 data-method-index="<?php echo esc_attr($index); ?>" 
                                                 data-module-id="<?php echo esc_attr($module_id); ?>"
                                                 data-method-type="<?php echo esc_attr($method_type_lower); ?>">
                                                
                                                <!-- Card Header -->
                                                <div class="cs-method-card-header">
                                                    <div class="cs-method-type-badge cs-method-type-<?php echo esc_attr($method_type_lower); ?>">
                                                        <i class="<?php echo esc_attr($icon); ?>"></i>
                                                        <span><?php echo esc_html($method_type); ?></span>
                                                    </div>
                                                    <?php if (!$is_client) : ?>
                                                    <div class="cs-method-actions">
                                                        <button type="button" class="cs-btn-icon cs-btn-secondary cs-edit-method" 
                                                                data-method-index="<?php echo esc_attr($index); ?>"
                                                                title="Edit method">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="cs-btn-icon cs-btn-danger cs-remove-method" 
                                                                data-method-index="<?php echo esc_attr($index); ?>" 
                                                                data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                title="Remove method">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Card Content -->
                                                <div class="cs-method-card-content">
                                                    <?php if ($is_client) : ?>
                                                        <!-- Client Read-Only View -->
                                                        <div class="cs-method-display">
                                                            <h6 class="cs-method-title-display"><?php echo esc_html($title ?: 'Untitled Method'); ?></h6>
                                                            <?php if ($location) : ?>
                                                            <div class="cs-method-location-display">
                                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                                <?php if (filter_var($location, FILTER_VALIDATE_URL)) : ?>
                                                                    <a href="<?php echo esc_url($location); ?>" target="_blank" class="cs-method-link">
                                                                        <?php echo esc_html($location); ?>
                                                                        <i class="fas fa-external-link-alt ms-1"></i>
                                                                    </a>
                                                                <?php else : ?>
                                                                    <span><?php echo esc_html($location); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else : ?>
                                                        <!-- Editable Form View -->
                                                        <div class="cs-method-form">
                                                            <div class="cs-field-group">
                                                                <label class="cs-field-label">Method Type</label>
                                                                <select class="cs-field-input cs-method-type" 
                                                                        data-method-index="<?php echo esc_attr($index); ?>" 
                                                                        data-module-id="<?php echo esc_attr($module_id); ?>">
                                                                    <option value="">Select method type...</option>
                                                                    <?php
                                                                    $method_types = [
                                                                        'Live' => 'In-person instruction',
                                                                        'Webinar' => 'Live online session', 
                                                                        'Online' => 'Interactive online content',
                                                                        'Self-Paced' => 'Independent learning'
                                                                    ];
                                                                    foreach ($method_types as $type => $description) {
                                                                        echo '<option value="' . esc_attr($type) . '"' . selected($method_type, $type, false) . '>' . esc_html($type) . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>

                                                            <div class="cs-field-group">
                                                                <label class="cs-field-label">
                                                                    <i class="fas fa-heading me-1"></i>Method Title
                                                                </label>
                                                                <div class="cs-field-with-ai">
                                                                    <input type="text" class="cs-field-input cs-method-title" 
                                                                           data-method-index="<?php echo esc_attr($index); ?>" 
                                                                           data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                           value="<?php echo esc_attr($title); ?>" 
                                                                           placeholder="e.g., Interactive Workshop, Video Tutorial..." />
                                                                    <button type="button" class="cs-btn-icon cs-ai-suggest-btn" 
                                                                            data-field-id="cs-method-title-<?php echo esc_attr($index); ?>"
                                                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                            title="Get AI suggestions">
                                                                        <i class="fas fa-magic"></i>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="cs-field-group">
                                                                <label class="cs-field-label">
                                                                    <i class="fas fa-map-marker-alt me-1"></i>Location / Link
                                                                </label>
                                                                <input type="text" class="cs-field-input cs-method-location" 
                                                                       data-method-index="<?php echo esc_attr($index); ?>" 
                                                                       data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                       value="<?php echo esc_attr($location); ?>" 
                                                                       placeholder="https://example.com or Room 101..." />
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Method Stats (if applicable) -->
                                                <div class="cs-method-card-footer">
                                                    <div class="cs-method-stats">
                                                        <div class="cs-stat-item">
                                                            <i class="fas fa-clock text-muted"></i>
                                                            <span class="text-muted">Duration: Not specified</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <!-- Empty State -->
                                        <div class="cs-methods-empty-state">
                                            <div class="cs-empty-state-content">
                                                <div class="cs-empty-state-icon">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                </div>
                                                <h6 class="cs-empty-state-title">No teaching methods defined</h6>
                                                <p class="cs-empty-state-description">Add teaching methods to specify how this module will be delivered to learners.</p>
                                                <?php if (!$is_client) : ?>
                                                <button type="button" class="cs-btn cs-btn-primary cs-add-method" data-module-id="<?php echo esc_attr($module_id); ?>">
                                                    <i class="fas fa-plus me-2"></i>Add First Method
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Method Templates Quick Access -->
                                <?php if (!$is_client) : ?>
                                <div class="cs-method-templates">
                                    <div class="cs-templates-header">
                                        <h6 class="cs-templates-title">
                                            <i class="fas fa-layer-group me-2"></i>Quick Templates
                                        </h6>
                                        <p class="cs-templates-subtitle">Add common teaching methods with one click</p>
                                    </div>
                                    <div class="cs-template-buttons">
                                        <button type="button" class="cs-template-btn" data-template="workshop" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-users"></i>
                                            <span>Workshop</span>
                                        </button>
                                        <button type="button" class="cs-template-btn" data-template="lecture" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-chalkboard"></i>
                                            <span>Lecture</span>
                                        </button>
                                        <button type="button" class="cs-template-btn" data-template="video" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-play-circle"></i>
                                            <span>Video Tutorial</span>
                                        </button>
                                        <button type="button" class="cs-template-btn" data-template="reading" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-book-open"></i>
                                            <span>Reading</span>
                                        </button>
                                        <button type="button" class="cs-template-btn" data-template="practice" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-laptop-code"></i>
                                            <span>Practice</span>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Materials Tab -->
                            <div class="cs-tab-content" id="cs-tab-materials-<?php echo esc_attr($module_id); ?>">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="cs-field-label mb-0">
                                        <i class="fas fa-book me-2"></i>Learning Materials
                                    </h6>
                                    <?php if (!$is_client) : ?>
                                    <button type="button" class="cs-btn-primary cs-add-material" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <i class="fas fa-plus me-2"></i>Add Material
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Material Upload Section -->
                                <?php if (!$is_client) : ?>
                                <div class="cs-material-upload-section mb-4">
                                    <div class="cs-upload-dropzone" id="cs-material-dropzone-<?php echo esc_attr($module_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <div class="cs-dropzone-content">
                                            <div class="cs-upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <h6 class="cs-upload-title">Upload Learning Materials</h6>
                                            <p class="cs-upload-subtitle">Drag & drop files or click to browse</p>
                                            <div class="cs-file-types">
                                                <span class="cs-file-type">PDF</span>
                                                <span class="cs-file-type">DOC</span>
                                                <span class="cs-file-type">PPT</span>
                                                <span class="cs-file-type">XLS</span>
                                                <span class="cs-file-type">TXT</span>
                                            </div>
                                        </div>
                                        <input type="file" id="cs-material-upload-<?php echo esc_attr($module_id); ?>" class="d-none" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar" multiple data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <div class="cs-upload-progress" id="cs-material-progress-<?php echo esc_attr($module_id); ?>"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div id="cs-materials-list-<?php echo esc_attr($module_id); ?>" class="cs-materials-container">
                                    <?php
                                    if (!empty($module_materials) && is_array($module_materials)) {
                                        foreach ($module_materials as $index => $material) {
                                            $material_type = isset($material['material_type']) ? esc_html($material['material_type']) : '';
                                            $title = isset($material['title']) ? esc_html($material['title']) : '';
                                            $link = isset($material['link']) ? esc_url($material['link']) : '';
                                            $file_url = isset($material['file_url']) ? esc_url($material['file_url']) : '';
                                            ?>
                                            <div class="cs-material-item" data-material-index="<?php echo esc_attr($index); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                                <div class="cs-material-card">
                                                    <div class="cs-material-header">
                                                        <div class="cs-material-type-badge cs-material-type-<?php echo esc_attr(strtolower($material_type)); ?>">
                                                            <i class="fas fa-<?php echo $material_type === 'Document' ? 'file-alt' : ($material_type === 'Video' ? 'video' : ($material_type === 'Audio' ? 'music' : ($material_type === 'Link' ? 'link' : 'box'))); ?>"></i>
                                                            <?php echo esc_html($material_type); ?>
                                                        </div>
                                                        <?php if (!$is_client) : ?>
                                                        <div class="cs-material-actions">
                                                            <button type="button" class="cs-btn-icon cs-btn-danger cs-remove-material" 
                                                                    data-material-index="<?php echo esc_attr($index); ?>" 
                                                                    data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                    title="Remove Material">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="cs-material-body">
                                                        <div class="cs-material-title-section">
                                                            <label class="cs-field-label">Title</label>
                                                            <?php if ($is_client) : ?>
                                                                <div class="cs-material-title-display"><?php echo esc_html($title ?: 'Untitled Material'); ?></div>
                                                            <?php else : ?>
                                                                <input type="text" class="cs-field-input cs-material-title" 
                                                                       data-material-index="<?php echo esc_attr($index); ?>" 
                                                                       data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                       value="<?php echo esc_attr($title); ?>" 
                                                                       placeholder="Enter material title" />
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="cs-material-link-section">
                                                            <?php if ($file_url) : ?>
                                                                <label class="cs-field-label">File</label>
                                                                <div class="cs-file-preview">
                                                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="cs-file-link">
                                                                        <i class="fas fa-download me-2"></i>
                                                                        <?php echo esc_html(basename($file_url)); ?>
                                                                    </a>
                                                                </div>
                                                            <?php elseif ($link) : ?>
                                                                <label class="cs-field-label">Link</label>
                                                                <?php if ($is_client) : ?>
                                                                    <div class="cs-link-preview">
                                                                        <a href="<?php echo esc_url($link); ?>" target="_blank" class="cs-external-link">
                                                                            <i class="fas fa-external-link-alt me-2"></i>
                                                                            <?php echo esc_html($link); ?>
                                                                        </a>
                                                                    </div>
                                                                <?php else : ?>
                                                                    <input type="url" class="cs-field-input cs-material-link" 
                                                                           data-material-index="<?php echo esc_attr($index); ?>" 
                                                                           data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                           value="<?php echo esc_attr($link); ?>" 
                                                                           placeholder="Enter material link or URL" />
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        echo '<div class="cs-empty-state">';
                                        echo '  <div class="cs-empty-icon"><i class="fas fa-book"></i></div>';
                                        echo '  <h6 class="cs-empty-title">No Materials Added Yet</h6>';
                                        echo '  <p class="cs-empty-description">Add learning materials to help students with this module</p>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Media Tab -->
                            <div class="cs-tab-content" id="cs-tab-media-<?php echo esc_attr($module_id); ?>">
                                <?php if (!$is_client) : ?>
                                <!-- Premium Upload Area -->
                                <div class="cs-media-upload-section mb-4">
                                    <div class="cs-upload-dropzone premium-dropzone" id="cs-media-dropzone-<?php echo esc_attr($module_id); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <div class="cs-dropzone-content">
                                            <div class="cs-upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <h6 class="cs-upload-title">Upload Media Files</h6>
                                            <p class="cs-upload-subtitle">Drag & drop multimedia files or click to browse</p>
                                            <div class="cs-file-types">
                                                <span class="cs-file-type">JPG</span>
                                                <span class="cs-file-type">PNG</span>
                                                <span class="cs-file-type">MP4</span>
                                                <span class="cs-file-type">MP3</span>
                                                <span class="cs-file-type">GIF</span>
                                            </div>
                                            <div class="cs-upload-limit">Max 10MB per file</div>
                                        </div>
                                        <input type="file" id="cs-media-upload-<?php echo esc_attr($module_id); ?>" class="d-none" accept="image/*,video/*,audio/*" multiple data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <div class="cs-upload-progress" id="cs-media-progress-<?php echo esc_attr($module_id); ?>"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Media Grid -->
                                <div id="cs-media-grid-<?php echo esc_attr($module_id); ?>" class="cs-media-grid-premium">
                                    <?php
                                    if (!empty($module_media) && is_array($module_media)) {
                                        foreach ($module_media as $media_url) {
                                            $file_ext = pathinfo($media_url, PATHINFO_EXTENSION);
                                            $file_name = basename($media_url);
                                            $file_size = '';
                                            
                                            // Try to get file size
                                            $file_path = str_replace(home_url('/'), ABSPATH, $media_url);
                                            if (file_exists($file_path)) {
                                                $file_size = size_format(filesize($file_path));
                                            }
                                            ?>
                                            <div class="cs-media-card" data-media-url="<?php echo esc_attr($media_url); ?>" data-module-id="<?php echo esc_attr($module_id); ?>">
                                                <!-- Media Preview -->
                                                <?php if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) : ?>
                                                    <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr($file_name); ?>" class="cs-media-preview" />
                                                <?php elseif (in_array(strtolower($file_ext), ['mp4', 'mov', 'avi', 'wmv'])) : ?>
                                                    <video class="cs-media-preview" poster="">
                                                        <source src="<?php echo esc_url($media_url); ?>" type="video/<?php echo esc_attr($file_ext); ?>">
                                                    </video>
                                                <?php elseif (in_array(strtolower($file_ext), ['mp3', 'wav', 'ogg'])) : ?>
                                                    <div class="cs-media-preview" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%); color: #fff;">
                                                        <i class="fas fa-music fa-3x"></i>
                                                    </div>
                                                <?php elseif (in_array(strtolower($file_ext), ['pdf', 'doc', 'docx', 'txt'])) : ?>
                                                    <div class="cs-media-preview" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(47, 46, 48, 0.9) 0%, rgba(53, 53, 53, 0.9) 100%); color: #E4B26F;">
                                                        <i class="fas fa-file-alt fa-3x"></i>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="cs-media-preview" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(47, 46, 48, 0.9) 0%, rgba(53, 53, 53, 0.9) 100%); color: #E4B26F;">
                                                        <i class="fas fa-file fa-3x"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Media Actions -->
                                                <div class="cs-media-actions">
                                                    <button type="button" class="cs-media-action cs-media-preview-btn" 
                                                            onclick="window.open('<?php echo esc_url($media_url); ?>', '_blank')"
                                                            title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if (!$is_client) : ?>
                                                    <button type="button" class="cs-media-action cs-media-delete-btn" 
                                                            data-media-url="<?php echo esc_attr($media_url); ?>" 
                                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Media Info -->
                                                <div class="cs-media-info">
                                                    <div class="cs-media-name" title="<?php echo esc_attr($file_name); ?>">
                                                        <?php echo esc_html(strlen($file_name) > 20 ? substr($file_name, 0, 17) . '...' : $file_name); ?>
                                                    </div>
                                                    <?php if ($file_size) : ?>
                                                    <div class="cs-media-size"><?php echo esc_html($file_size); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        if ($is_client) {
                                            echo '<div class="text-muted text-center py-5">No media files have been uploaded yet.</div>';
                                        }
                                    }
                                    ?>
                                </div>
                                
                                <?php if (empty($module_media) && !$is_client) : ?>
                                <div class="text-center py-3" style="color: rgba(255,255,255,0.6);">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Upload images, videos, audio files, or documents to enhance this module
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else : ?>
                        <div class="cs-premium-tabs-locked text-center py-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(228, 178, 111, 0.1); border-radius: 12px;">
                            <i class="fas fa-lock fa-3x mb-3" style="color: rgba(228, 178, 111, 0.5);"></i>
                            <h6 style="color: #E4B26F; margin-bottom: 12px;">Additional Details Locked</h6>
                            <p style="color: rgba(255,255,255,0.6); margin-bottom: 0;">
                                Methods, Materials, and Media fields will be unlocked once you add lessons with teaching points to this module.
                            </p>
                        </div>
                        <?php endif; ?>

                        

                        <!-- Module Actions Section -->
                        <?php if (!$is_client): 
                            // Get module status for archive functionality
                            $module_status = get_post_status($module_id);
                            $is_archived = ($module_status === 'archived');
                        ?>
                        <div class="cs-premium-actions-section" id="cs-module-actions-<?php echo esc_attr($module_id); ?>">
                            <div class="cs-action-bar">
                                <div class="cs-primary-actions">
                                    <?php if (!$is_archived): ?>
                                    <button type="button" 
                                            class="cs-btn-primary cs-save-module" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>" 
                                            data-course-id="<?php echo esc_attr($course_id); ?>">
                                        <i class="fas fa-save me-2"></i>
                                        <span class="btn-text">Save Changes</span>
                                        <div class="cs-save-spinner d-none">
                                            <div class="spinner-border spinner-border-sm" role="status"></div>
                                        </div>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" 
                                            class="cs-btn-outline cs-view-logs-btn" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                            data-bs-toggle="offcanvas" 
                                            data-bs-target="#cs-moduleLogsOffcanvas">
                                        <i class="fas fa-history me-2"></i>
                                        View Logs
                                    </button>
                                </div>
                                
                                <div class="cs-secondary-actions">
                                    <?php if (!$is_archived): ?>
                                    <button type="button" 
                                            class="cs-btn-warning cs-archive-module-btn" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                            data-module-title="<?php echo esc_attr($module->post_title); ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#cs-archiveModuleModal">
                                        <i class="fas fa-archive me-2"></i>
                                        Archive
                                    </button>
                                    
                                    <button type="button" 
                                            class="cs-btn-danger cs-delete-module-btn" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                            data-module-title="<?php echo esc_attr($module->post_title); ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#cs-deleteModuleModal">
                                        <i class="fas fa-trash me-2"></i>
                                        Delete
                                    </button>
                                    <?php else: ?>
                                    <button type="button" 
                                            class="cs-btn-success cs-restore-module-btn" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                            data-module-title="<?php echo esc_attr($module->post_title); ?>">
                                        <i class="fas fa-undo me-2"></i>
                                        Restore
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="text-center py-5"><div class="text-muted">No active modules added yet.</div></div>';
            }
            ?>
            </div>
            
            <!-- Expand/Collapse Toggle  -->
           
            <button type="button" class="cs-expand-toggle" id="cs-expand-toggle-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-chevron-down me-2"></i>
                <span class="expand-text">Show All Modules (<?php echo count($modules); ?>)</span>
            </button>
            
        </div>

        <!-- Archived Modules -->
        <?php if (!$is_client) : ?>
        <div class="cs-modules-archived d-none" id="cs-modules-archived-<?php echo esc_attr($course_id); ?>">
            <?php
            $archived_modules = get_modules_for_course_premium($course_id, true); // Include archived
            $archived_modules = array_filter($archived_modules, function($module) {
                return get_post_status($module->ID) === 'archived';
            });
            
            if (!empty($archived_modules)) :
                foreach ($archived_modules as $module) : ?>
                    <div class="cs-archived-module" data-module-id="<?php echo esc_attr($module->ID); ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-warning mb-1"><?php echo esc_html($module->post_title); ?></h6>
                                <small class="text-muted">Archived on <?php echo esc_html(get_the_modified_date('M j, Y', $module->ID)); ?></small>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="cs-btn-success cs-restore-module" data-module-id="<?php echo esc_attr($module->ID); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                                <button class="cs-btn-danger cs-permanent-delete" data-module-id="<?php echo esc_attr($module->ID); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach;
            else : ?>
                <div class="text-center py-5">
                    <div class="text-muted">No archived modules.</div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Module Logs Offcanvas -->
    <div class="offcanvas offcanvas-end cs-logs-offcanvas" tabindex="-1" id="cs-moduleLogsOffcanvas" aria-labelledby="cs-moduleLogsOffcanvasLabel">
        <div class="offcanvas-header" style="background: #2F2E30; border-bottom: 1px solid rgba(228, 178, 111, 0.1);">
            <h5 class="offcanvas-title" id="cs-moduleLogsOffcanvasLabel" style="color: #E4B26F;">
                <i class="fas fa-history me-2"></i>Module Activity Logs
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" style="background: #2F2E30; color: #fff;">
            <!-- Log Filters -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <select class="cs-filter-select" id="cs-log-filter">
                        <option value="all">All Actions</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="archived">Archived</option>
                        <option value="restored">Restored</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select class="cs-filter-select" id="cs-log-sort">
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                        <option value="action">By Action</option>
                    </select>
                </div>
            </div>
            
            <!-- Logs Container -->
            <div id="cs-logs-container">
                <!-- Logs will be loaded here -->
            </div>
            
            <!-- Pagination -->
            <div class="cs-pagination" id="cs-logs-pagination">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modals -->
    <div class="modal fade" id="cs-deleteModuleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header premium-modal-header-danger">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Delete Module?
                    </h5>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="danger-content">
                        <p><strong>Are you sure you want to delete this module?</strong></p>
                        <p class="course-name"></p>
                        <div class="alert alert-danger">
                            <i class="fas fa-warning me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. All module data, modules, lessons, and associated content will be permanently deleted.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger confirm-delete-btn">
                        <i class="fas fa-trash me-2"></i>
                        <span class="btn-text">Delete Module</span>
                        <div class="btn-spinner d-none">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="cs-archiveModuleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header premium-modal-header-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-archive me-2"></i>
                        Archive Module?
                    </h5>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to archive this module?</strong></p>
                    <p class="course-name"></p>
                    <div id="current-module-id" class="hidden"></div>
                    <p class="text-muted">Archived modules are hidden from the main view but can be restored later. No data will be lost.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning confirm-archive-btn">
                        <i class="fas fa-archive me-2"></i>
                        <span class="btn-text">Archive Module</span>
                        <div class="btn-spinner d-none">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="cs-restoreModuleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header premium-modal-header-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-undo me-2"></i>
                        Restore Module?
                    </h5>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to restore this module?</strong></p>
                    <p class="course-name"></p>
                    
                    <!-- <p class="text-muted">Archived courses are hidden from the main view but can be restored later. No data will be lost.</p> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning confirm-restore-btn">
                        <i class="fas fa-undo me-2"></i>
                        <span class="btn-text">Restore Module</span>
                        <div class="btn-spinner d-none">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Module Generation Modal -->
    <?php 
    courscribe_render_premium_module_generator([
        'course_id' => $course_id,
        'curriculum_id' => $curriculum_id,
        'studio_id' => $studio_id
    ]);
    ?>

    <!-- All JavaScript functionality has been moved to modules-premium.js and is enqueued properly -->

    <?php
}
?>
