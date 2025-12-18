<?php
// courscribe/templates/template-parts/lessons-premium-enhanced.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Premium Lessons Template with Full Functionality
 *
 * Features:
 * - Complete objectives and activities editing with idempotency
 * - Real-time validation and feedback
 * - Archive/restore/delete operations
 * - Auto-save with conflict resolution
 * - Enhanced UX with field helpers and validation
 * - Proper error handling and user feedback
 *
 * @param array $args Course and lesson parameters
 */
function courscribe_render_lessons($args = []) {
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

    // User permissions
    $current_user = wp_get_current_user();
    $is_client = in_array('client', (array) $current_user->roles);
    $is_studio_admin = in_array('studio_admin', (array) $current_user->roles);
    $is_collaborator = in_array('collaborator', (array) $current_user->roles);
    $can_edit = !$is_client && ($is_studio_admin || $is_collaborator);

    if (!$course_id || !$tooltips instanceof CourScribe_Tooltips) {
        return;
    }

    // Enqueue required assets
    courscribe_enqueue_lessons_assets_enhanced();

    // Generate nonces
    $lesson_nonce = wp_create_nonce('courscribe_lesson_nonce');
    $objective_nonce = wp_create_nonce('courscribe_objective_nonce');
    $activity_nonce = wp_create_nonce('courscribe_activity_nonce');

    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    ?>

    <div class="cs-lessons-enhanced" id="cs-lessons-enhanced-<?php echo esc_attr($course_id); ?>" 
         data-course-id="<?php echo esc_attr($course_id); ?>"
         data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
         data-can-edit="<?php echo $can_edit ? 'true' : 'false'; ?>">

        <!-- Enhanced Auto-save Status Bar -->
        <?php if ($can_edit): ?>
        <div class="cs-status-bar" id="cs-status-bar-<?php echo esc_attr($course_id); ?>">
            <div class="cs-status-content">
                <div class="cs-save-status">
                    <i class="fas fa-save cs-save-icon"></i>
                    <span class="cs-save-text">All changes saved</span>
                    <div class="cs-save-spinner d-none">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
                <div class="cs-last-saved">
                    Last saved: <span class="cs-timestamp">--</span>
                </div>
                <div class="cs-connection-status" data-status="connected">
                    <i class="fas fa-wifi"></i>
                    <span>Connected</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Enhanced Notifications -->
        <div class="cs-notifications" id="cs-notifications-<?php echo esc_attr($course_id); ?>"></div>

        <!-- Course Context Banner -->
        <div class="cs-course-context">
            <div class="cs-context-content">
                <div class="cs-context-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="cs-context-info">
                    <span class="cs-context-label">Course Goal:</span>
                    <span class="cs-context-text"><?php echo $course_goal; ?></span>
                </div>
            </div>
        </div>

        <?php
        // Fetch all modules for the course
        $modules = get_posts([
            'post_type' => 'crscribe_module',
            'post_status' => ['publish', 'archived'],
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

        if ($modules) {
            ?>
            <div class="cs-modules-container">
                <!-- Enhanced Module Navigation -->
                <div class="cs-module-nav" id="cs-module-nav-<?php echo esc_attr($course_id); ?>">
                    <?php
                    foreach ($modules as $index => $module) {
                        $module_status = get_post_meta($module->ID, '_module_status', true) ?: 'active';
                        $is_archived = ($module_status === 'archived');
                        $is_active = $index === 0;
                        ?>
                        <button type="button" 
                                class="cs-module-tab <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_archived ? 'archived' : ''; ?>" 
                                data-module-id="<?php echo esc_attr($module->ID); ?>"
                                data-tab-target="cs-module-content-<?php echo esc_attr($module->ID); ?>">
                            <div class="cs-tab-content">
                                <span class="cs-tab-number">Module <?php echo $index + 1; ?></span>
                                <span class="cs-tab-title"><?php echo esc_html($module->post_title); ?></span>
                                <?php if ($is_archived): ?>
                                <span class="cs-archived-badge">
                                    <i class="fas fa-archive"></i>
                                </span>
                                <?php endif; ?>
                                <div class="cs-lesson-count">
                                    <?php
                                    $lesson_count = count(get_posts([
                                        'post_type' => 'crscribe_lesson',
                                        'post_status' => ['publish', 'archived'],
                                        'numberposts' => -1,
                                        'meta_query' => [
                                            [
                                                'key' => '_module_id',
                                                'value' => $module->ID,
                                                'compare' => '=',
                                            ],
                                        ],
                                    ]));
                                    echo $lesson_count . ' lesson' . ($lesson_count !== 1 ? 's' : '');
                                    ?>
                                </div>
                            </div>
                        </button>
                        <?php
                    }
                    ?>
                </div>

                <!-- Enhanced Module Content -->
                <div class="cs-module-contents">
                    <?php
                    foreach ($modules as $index => $module) {
                        $module_goal = esc_html(get_post_meta($module->ID, '_module_goal', true));
                        $module_status = get_post_meta($module->ID, '_module_status', true) ?: 'active';
                        $is_module_archived = ($module_status === 'archived');
                        $is_active = $index === 0;
                        ?>
                        <div class="cs-module-content <?php echo $is_active ? 'active' : ''; ?>" 
                             id="cs-module-content-<?php echo esc_attr($module->ID); ?>"
                             data-module-id="<?php echo esc_attr($module->ID); ?>">
                             
                            <!-- Module Header -->
                            <div class="cs-module-header">
                                <?php if ($is_module_archived && $can_edit): ?>
                                <div class="cs-archived-notice">
                                    <i class="fas fa-archive"></i>
                                    <span>This module is archived.</span>
                                    <button type="button" class="cs-btn cs-btn-link cs-restore-module-btn" 
                                            data-module-id="<?php echo esc_attr($module->ID); ?>">
                                        Restore Module
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <div class="cs-module-info">
                                    <div class="cs-module-goal">
                                        <i class="fas fa-target"></i>
                                        <span class="cs-goal-label">Module Goal:</span>
                                        <span class="cs-goal-text"><?php echo $module_goal; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Lessons Section -->
                            <div class="cs-lessons-section">
                                <div class="cs-section-header">
                                    <h3 class="cs-section-title">
                                        <i class="fas fa-book-open"></i>
                                        Lessons
                                    </h3>
                                    <?php if ($can_edit && !$is_module_archived): ?>
                                    <div class="cs-section-actions">
                                        <button type="button" class="cs-btn cs-btn-ai cs-generate-lessons-btn"
                                                data-module-id="<?php echo esc_attr($module->ID); ?>"
                                                data-course-id="<?php echo esc_attr($course_id); ?>"
                                                data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
                                            <i class="fas fa-magic"></i>
                                            Generate Lessons
                                        </button>
                                        <button type="button" class="cs-btn cs-btn-primary cs-add-lesson-btn"
                                                data-module-id="<?php echo esc_attr($module->ID); ?>">
                                            <i class="fas fa-plus"></i>
                                            Add Lesson
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Lessons Container -->
                                <div class="cs-lessons-list" 
                                     id="cs-lessons-list-<?php echo esc_attr($module->ID); ?>"
                                     data-module-id="<?php echo esc_attr($module->ID); ?>">
                                    <?php
                                    // Fetch lessons for this module
                                    $lessons = get_posts([
                                        'post_type' => 'crscribe_lesson',
                                        'post_status' => ['publish', 'archived'],
                                        'numberposts' => -1,
                                        'meta_query' => [
                                            [
                                                'key' => '_module_id',
                                                'value' => $module->ID,
                                                'compare' => '=',
                                            ],
                                        ],
                                        'orderby' => 'menu_order',
                                        'order' => 'ASC',
                                    ]);

                                    if ($lessons) {
                                        foreach ($lessons as $lesson) {
                                            courscribe_render_enhanced_lesson([
                                                'lesson' => $lesson,
                                                'module' => $module,
                                                'course_id' => $course_id,
                                                'curriculum_id' => $curriculum_id,
                                                'can_edit' => $can_edit,
                                                'is_client' => $is_client,
                                                'current_user' => $current_user,
                                                'tooltips' => $tooltips,
                                                'lesson_nonce' => $lesson_nonce,
                                                'objective_nonce' => $objective_nonce,
                                                'activity_nonce' => $activity_nonce
                                            ]);
                                        }
                                    } else {
                                        ?>
                                        <div class="cs-empty-lessons">
                                            <div class="cs-empty-icon">
                                                <i class="fas fa-book-open"></i>
                                            </div>
                                            <h4>No lessons yet</h4>
                                            <p>Start by adding your first lesson to this module.</p>
                                            <?php if ($can_edit && !$is_module_archived): ?>
                                            <button type="button" class="cs-btn cs-btn-primary cs-add-lesson-btn"
                                                    data-module-id="<?php echo esc_attr($module->ID); ?>">
                                                <i class="fas fa-plus"></i>
                                                Add First Lesson
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="cs-empty-modules">
                <div class="cs-empty-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h4>No modules available</h4>
                <p>This course doesn't have any modules yet.</p>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- Enhanced CSS -->
    <style>
        /* CourScribe Dark Theme Variables */
        :root {
            /* CourScribe Brand Colors */
            --cs-primary-gold: #E4B26F;
            --cs-primary-gold-light: #F0C788;
            --cs-primary-gold-dark: #D4A05C;
            --cs-secondary-brown: #665442;
            --cs-gradient-primary: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
            --cs-gradient-secondary: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
            --cs-gradient-dark: linear-gradient(135deg, #231F20 0%, #2a2a2b 100%);
            
            /* Dark Theme Backgrounds */
            --cs-bg-primary: #231F20;
            --cs-bg-secondary: #2a2a2b;
            --cs-bg-elevated: #353535;
            --cs-bg-card: #2f2f2f;
            --cs-bg-dark: #231F20;
            
            /* Text Colors */
            --cs-text-primary: #ffffff;
            --cs-text-secondary: #e0e0e0;
            --cs-text-muted: #b0b0b0;
            
            /* Border and UI */
            --cs-border-color: #4a4a4a;
            --cs-hover-bg: rgba(228, 178, 111, 0.1);
            
            /* Status Colors */
            --cs-success: #28a745;
            --cs-warning: #ffc107;
            --cs-error: #dc3545;
            --cs-info: #17a2b8;
            
            /* Design System */
            --cs-transition: all 0.3s ease;
            --cs-border-radius: 8px;
            --cs-border-radius-sm: 4px;
            --cs-border-radius-lg: 12px;
            --cs-spacing-xs: 4px;
            --cs-spacing-sm: 8px;
            --cs-spacing-md: 16px;
            --cs-spacing-lg: 24px;
            --cs-spacing-2xl: 48px;
            --cs-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.25);
            --cs-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            --cs-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        
        .cs-lessons-enhanced {
            background: var(--cs-bg-secondary);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .cs-status-bar {
            background: linear-gradient(135deg, var(--cs-bg-primary) 0%, var(--cs-bg-secondary) 100%);
            border: 1px solid var(--cs-primary-gold);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .cs-status-content {
            display: flex;
            align-items: center;
            gap: 20px;
            width: 100%;
        }

        .cs-save-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cs-save-icon {
            color: #28a745;
            font-size: 16px;
        }

        .cs-save-text {
            color: var(--cs-text-primary);
            font-weight: 500;
        }

        .cs-last-saved {
            color: var(--cs-text-muted);
            font-size: 0.9rem;
        }

        .cs-connection-status {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--cs-text-muted);
            font-size: 0.9rem;
            margin-left: auto;
        }

        .cs-connection-status[data-status="connected"] {
            color: #28a745;
        }

        .cs-connection-status[data-status="disconnected"] {
            color: #dc3545;
        }

        .cs-notifications {
            margin-bottom: 20px;
        }

        .cs-notification {
            background: var(--cs-bg-primary);
            border-left: 4px solid;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 8px;
            margin-top: 60px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease;
        }

        .cs-notification.success {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
            color: #fff;
        }

        .cs-notification.error {
            border-left-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
            color: #fff;
        }

        .cs-notification.warning {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .cs-notification .cs-notification-close {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            opacity: 0.7;
        }

        .cs-notification .cs-notification-close:hover {
            opacity: 1;
        }

        .cs-course-context {
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-primary-gold);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .cs-context-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cs-context-icon {
            color: var(--cs-primary-gold);
            font-size: 18px;
        }

        .cs-context-label {
            color: var(--cs-primary-gold);
            font-weight: 600;
        }

        .cs-context-text {
            color: var(--cs-text-primary);
            flex: 1;
        }

        .cs-modules-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .cs-module-nav {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .cs-module-tab {
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-border-color);
            border-radius: 8px 8px 0 0;
            color: var(--cs-text-secondary);
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .cs-module-tab:hover {
            background: var(--cs-bg-elevated);
            color: var(--cs-text-primary);
            border-color: var(--cs-primary-gold);
        }

        .cs-module-tab.active {
            background: var(--cs-primary-gold);
            color: #ffffff;
            border-color: var(--cs-primary-gold);
            box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
        }

        .cs-module-tab.archived {
            opacity: 0.6;
            background: var(--cs-bg-elevated);
        }

        .cs-tab-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cs-tab-number {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .cs-tab-title {
            font-weight: 500;
        }

        .cs-lesson-count {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .cs-archived-badge {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            align-self: flex-start;
        }

        .cs-module-contents {
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-border-color);
            border-radius: 0 8px 8px 8px;
            overflow: hidden;
        }

        .cs-module-content {
            display: none;
            padding: 24px;
        }

        .cs-module-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .cs-module-header {
            margin-bottom: 24px;
        }

        .cs-archived-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffc107;
        }

        .cs-module-info {
            background: rgba(228, 178, 111, 0.1);
            border: 1px solid rgba(228, 178, 111, 0.3);
            border-radius: 8px;
            padding: 16px;
        }

        .cs-module-goal {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cs-goal-label {
            color: var(--cs-primary-gold);
            font-weight: 600;
            white-space: nowrap;
        }

        .cs-goal-text {
            color: var(--cs-text-primary);
            flex: 1;
        }

        .cs-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--cs-border-color);
        }

        .cs-section-title {
            color: var(--cs-primary-gold);
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cs-section-actions {
            display: flex;
            gap: 12px;
        }

        .cs-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .cs-btn-primary {
            background: var(--cs-gradient-primary);
            color: #ffffff;
            border: 1px solid var(--cs-primary-gold);
        }

        .cs-btn-primary:hover {
            background: var(--cs-gradient-secondary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
        }

        .cs-btn-ai {
            background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%);
            color: #ffffff;
        }

        .cs-btn-ai:hover {
            background: linear-gradient(90deg, #FBAF3F 0%, #EF4339 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
        }

        .cs-btn-link {
            background: none;
            color: var(--cs-primary-gold);
            border: none;
            padding: 4px 8px;
            text-decoration: underline;
        }

        .cs-btn-link:hover {
            color: var(--cs-primary-gold-light);
        }

        .cs-lessons-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .cs-empty-lessons,
        .cs-empty-modules {
            text-align: center;
            padding: 40px 20px;
            color: var(--cs-text-muted);
        }

        .cs-empty-icon {
            font-size: 3rem;
            color: var(--cs-border-color);
            margin-bottom: 16px;
        }

        .cs-empty-lessons h4,
        .cs-empty-modules h4 {
            color: var(--cs-text-primary);
            margin-bottom: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cs-status-content {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .cs-connection-status {
                margin-left: 0;
            }

            .cs-module-nav {
                flex-direction: column;
            }

            .cs-module-tab {
                border-radius: 8px;
            }

            .cs-modules-container {
                gap: 12px;
            }

            .cs-module-contents {
                border-radius: 8px;
            }

            .cs-section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .cs-section-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .cs-context-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        /* Modal Styles */
        .cs-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        .cs-modal {
            background: var(--cs-bg-secondary);
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: slideInModal 0.3s ease;
        }

        .cs-modal-header {
            background: var(--cs-bg-primary);
            padding: 20px 24px;
            border-bottom: 1px solid var(--cs-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
        }

        .cs-modal-header h3 {
            margin: 0;
            color: var(--cs-text-primary);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .cs-modal-close-x {
            background: none;
            border: none;
            color: var(--cs-text-secondary);
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .cs-modal-close-x:hover {
            background: var(--cs-bg-elevated);
            color: var(--cs-text-primary);
        }

        .cs-modal-content {
            padding: 24px;
        }

        .cs-form-group {
            margin-bottom: 20px;
        }

        .cs-form-group label {
            display: block;
            color: var(--cs-text-primary);
            font-weight: 500;
            margin-bottom: 6px;
        }

        .cs-form-group .required {
            color: #dc3545;
        }

        .cs-form-control {
            width: 100%;
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-border-color);
            border-radius: 6px;
            padding: 10px 12px;
            color: var(--cs-text-primary);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .cs-form-control:focus {
            border-color: var(--cs-primary-gold);
            box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
            outline: none;
        }

        .cs-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--cs-border-color);
        }

        /* Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInModal {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>

    <!-- Enhanced JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        'use strict';

        // Initialize enhanced lessons functionality
        const LessonsEnhanced = {
            courseId: <?php echo json_encode($course_id); ?>,
            curriculumId: <?php echo json_encode($curriculum_id); ?>,
            canEdit: <?php echo json_encode($can_edit); ?>,
            nonces: {
                lesson: <?php echo json_encode($lesson_nonce); ?>,
                objective: <?php echo json_encode($objective_nonce); ?>,
                activity: <?php echo json_encode($activity_nonce); ?>
            },
            saveTimeout: null,
            pendingRequests: new Map(),

            init: function() {
                this.initModuleTabs();
                this.initStatusBar();
                this.initNotifications();
                if (this.canEdit) {
                    this.initAutoSave();
                    this.initLessonManagement();
                }
                this.initNetworkMonitoring();
                
                console.log('CourScribe Enhanced Lessons initialized');
            },

            initModuleTabs: function() {
                $('.cs-module-tab').on('click', function() {
                    const $tab = $(this);
                    const targetId = $tab.data('tab-target');
                    
                    // Update active states
                    $('.cs-module-tab').removeClass('active');
                    $('.cs-module-content').removeClass('active');
                    
                    $tab.addClass('active');
                    $('#' + targetId).addClass('active');
                });
            },

            initStatusBar: function() {
                this.updateSaveStatus('saved');
            },

            initNotifications: function() {
                // Auto-hide success notifications after 5 seconds
                $(document).on('click', '.cs-notification-close', function() {
                    $(this).closest('.cs-notification').fadeOut(300, function() {
                        $(this).remove();
                    });
                });
            },

            initAutoSave: function() {
                // Auto-save for all lesson fields
                $(document).on('input change', '.cs-auto-save-field', (e) => {
                    const $field = $(e.target);
                    this.scheduleAutoSave($field);
                });
            },

            scheduleAutoSave: function($field) {
                clearTimeout(this.saveTimeout);
                this.updateSaveStatus('saving');
                
                this.saveTimeout = setTimeout(() => {
                    this.performAutoSave($field);
                }, 1500); // Save after 1.5 seconds of inactivity
            },

            performAutoSave: function($field) {
                const fieldName = $field.data('field-name');
                const fieldValue = $field.val();
                const lessonId = $field.data('lesson-id');
                const originalValue = $field.data('original-value');

                // Skip if value hasn't changed
                if (fieldValue === originalValue) {
                    this.updateSaveStatus('saved');
                    return;
                }

                // Generate unique request hash for idempotency
                const requestData = {
                    lesson_id: lessonId,
                    field_name: fieldName,
                    field_value: fieldValue,
                    timestamp: Math.floor(Date.now() / 1000)
                };
                const requestHash = this.generateRequestHash(requestData);

                // Check for duplicate request
                if (this.pendingRequests.has(requestHash)) {
                    return;
                }

                this.pendingRequests.set(requestHash, true);
                console.log('req:', {
                        action: 'courscribe_autosave_lesson_field',
                        ...requestData,
                        nonce: this.nonces.lesson,
                        request_hash: requestHash
                    } )
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_autosave_lesson_field',
                        ...requestData,
                        nonce: this.nonces.lesson,
                        request_hash: requestHash
                    },
                    success: (response) => {
                        this.pendingRequests.delete(requestHash);
                        console.log(response)
                        if (response.success) {
                            this.updateSaveStatus('saved');
                            $field.data('original-value', fieldValue);
                            this.showNotification('success', 'Changes saved automatically', 2000);
                        } else {
                            this.updateSaveStatus('error');
                            this.showNotification('error', response.data?.message || 'Auto-save failed');
                        }
                    },
                    error: () => {
                        this.pendingRequests.delete(requestHash);
                        this.updateSaveStatus('error');
                        this.showNotification('error', 'Network error during auto-save');
                    }
                });
            },

            generateRequestHash: function(data) {
                return btoa(JSON.stringify(data)).replace(/[^a-zA-Z0-9]/g, '');
            },

            updateSaveStatus: function(status) {
                const $statusBar = $('#cs-status-bar-' + this.courseId);
                const $icon = $statusBar.find('.cs-save-icon');
                const $text = $statusBar.find('.cs-save-text');
                const $spinner = $statusBar.find('.cs-save-spinner');
                const $timestamp = $statusBar.find('.cs-timestamp');

                $spinner.addClass('d-none');
                $icon.removeClass('d-none');

                switch (status) {
                    case 'saving':
                        $icon.addClass('d-none');
                        $spinner.removeClass('d-none');
                        $text.text('Saving changes...');
                        break;
                    case 'saved':
                        $icon.removeClass('fa-exclamation-triangle fa-times').addClass('fa-save').css('color', '#28a745');
                        $text.text('All changes saved');
                        $timestamp.text(new Date().toLocaleTimeString());
                        break;
                    case 'error':
                        $icon.removeClass('fa-save').addClass('fa-exclamation-triangle').css('color', '#dc3545');
                        $text.text('Save failed');
                        break;
                }
            },

            showNotification: function(type, message, autoHide = 5000) {
                const $container = $('#cs-notifications-' + this.courseId);
                const iconClass = type === 'success' ? 'fa-check-circle' : 
                                type === 'error' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle';
                
                const $notification = $(`
                    <div class="cs-notification ${type}">
                        <i class="fas ${iconClass}"></i>
                        <span class="cs-notification-message">${message}</span>
                        <button type="button" class="cs-notification-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);

                $container.append($notification);

                // Auto-hide if specified
                if (autoHide > 0) {
                    setTimeout(() => {
                        $notification.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, autoHide);
                }
            },

            initNetworkMonitoring: function() {
                // Monitor network connectivity
                window.addEventListener('online', () => {
                    this.updateConnectionStatus('connected');
                    this.showNotification('success', 'Connection restored', 3000);
                });

                window.addEventListener('offline', () => {
                    this.updateConnectionStatus('disconnected');
                    this.showNotification('warning', 'Connection lost - changes will be saved when reconnected');
                });
            },

            updateConnectionStatus: function(status) {
                const $statusElem = $('.cs-connection-status');
                $statusElem.attr('data-status', status);
                
                const $icon = $statusElem.find('i');
                const $text = $statusElem.find('span');
                
                if (status === 'connected') {
                    $icon.removeClass('fa-wifi-slash').addClass('fa-wifi');
                    $text.text('Connected');
                } else {
                    $icon.removeClass('fa-wifi').addClass('fa-wifi-slash');
                    $text.text('Offline');
                }
            },

            initLessonManagement: function() {
                this.initObjectiveManagement();
                this.initActivityManagement();
                this.initTeachingPoints();
                this.initArchiveRestore();
                this.initLessonSorting();
                this.initCharacterCounters();
            },

            initObjectiveManagement: function() {
                // Add objective
                $(document).on('click', '.cs-add-objective-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.showObjectiveModal(lessonId);
                });

                // Remove objective
                $(document).on('click', '.cs-remove-objective-btn', (e) => {
                    const $btn = $(e.target);
                    const index = $btn.data('index');
                    const lessonId = $btn.data('lesson-id');
                    
                    if (confirm('Are you sure you want to remove this objective?')) {
                        this.removeObjective(lessonId, index);
                    }
                });

                // Edit objective
                $(document).on('click', '.cs-edit-objective-btn', (e) => {
                    const $btn = $(e.target);
                    const index = $btn.data('index');
                    const lessonId = $btn.data('lesson-id');
                    this.editObjective(lessonId, index);
                });
            },

            initActivityManagement: function() {
                // Add activity
                $(document).on('click', '.cs-add-activity-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.showActivityModal(lessonId);
                });

                // Remove activity
                $(document).on('click', '.cs-remove-activity-btn', (e) => {
                    const $btn = $(e.target);
                    const index = $btn.data('index');
                    const lessonId = $btn.data('lesson-id');
                    
                    if (confirm('Are you sure you want to remove this activity?')) {
                        this.removeActivity(lessonId, index);
                    }
                });

                // Edit activity
                $(document).on('click', '.cs-edit-activity-btn', (e) => {
                    const $btn = $(e.target);
                    const index = $btn.data('index');
                    const lessonId = $btn.data('lesson-id');
                    this.editActivity(lessonId, index);
                });
            },

            initTeachingPoints: function() {
                // Add teaching point with form data
                $(document).on('click', '.cs-add-point-btn', (e) => {
                    const $btn = $(e.target);
                    const lessonId = $btn.data('lesson-id');
                    
                    // Get form values
                    const title = $(`#cs-teaching-point-title-${lessonId}`).val().trim();
                    const description = $(`#cs-teaching-point-description-${lessonId}`).val().trim();
                    const example = $(`#cs-teaching-point-example-${lessonId}`).val().trim();
                    const activity = $(`#cs-teaching-point-activity-${lessonId}`).val().trim();
                    
                    // Validation
                    if (!title || !description) {
                        this.showNotification('warning', 'Title and description are required');
                        return;
                    }
                    
                    // Disable button during processing
                    $btn.addClass('disabled').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
                    
                    const teachingPointData = {
                        title: title,
                        description: description,
                        example: example,
                        activity: activity
                    };
                    
                    this.addTeachingPoint(lessonId, teachingPointData, $btn);
                });
                
                // Clear form
                $(document).on('click', '.cs-clear-point-form-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.clearTeachingPointForm(lessonId);
                });
                
                // AI Generate teaching point
                $(document).on('click', '.cs-ai-generate-point-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.aiGenerateTeachingPoint(lessonId);
                });
                
                // Auto-resize textareas
                $(document).on('input', '.cs-teaching-point-description', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });

                // Remove teaching point
                $(document).on('click', '.cs-remove-point-btn', (e) => {
                    const $btn = $(e.target);
                    const index = $btn.data('index');
                    const lessonId = $btn.data('lesson-id');
                    
                    if (confirm('Are you sure you want to remove this teaching point?')) {
                        this.removeTeachingPoint(lessonId, index);
                    }
                });

                // Edit teaching point
                $(document).on('click', '.cs-edit-point-btn', (e) => {
                    const $btn = $(e.target);
                    const index = $btn.data('index');
                    const lessonId = $btn.data('lesson-id');
                    this.editTeachingPoint(lessonId, index);
                });

                // Enter key to add point
                $(document).on('keypress', '.cs-new-point-input', (e) => {
                    if (e.which === 13) {
                        $(e.target).siblings('.cs-add-point-btn').click();
                    }
                });
            },

            initArchiveRestore: function() {
                // Archive lesson
                $(document).on('click', '.cs-archive-lesson-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    
                    if (confirm('Are you sure you want to archive this lesson? It will be hidden from the main view but can be restored later.')) {
                        this.archiveLesson(lessonId);
                    }
                });

                // Restore lesson
                $(document).on('click', '.cs-restore-lesson-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.restoreLesson(lessonId);
                });

                // Delete lesson
                $(document).on('click', '.cs-delete-lesson-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    
                    const confirmText = prompt('This will permanently delete the lesson. Type "DELETE" to confirm:');
                    if (confirmText === 'DELETE') {
                        this.deleteLesson(lessonId);
                    }
                });

                // View logs
                $(document).on('click', '.cs-view-logs-btn', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.viewLessonLogs(lessonId);
                });
            },

            initLessonSorting: function() {
                // Move lesson up
                $(document).on('click', '.cs-sort-up', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.moveLesson(lessonId, 'up');
                });

                // Move lesson down
                $(document).on('click', '.cs-sort-down', (e) => {
                    const lessonId = $(e.target).data('lesson-id');
                    this.moveLesson(lessonId, 'down');
                });
            },

            initCharacterCounters: function() {
                // Update character counters for text inputs
                $(document).on('input', '.cs-new-point-input', (e) => {
                    const $input = $(e.target);
                    const $counter = $input.closest('.cs-add-point-form').find('.cs-char-count .current');
                    $counter.text($input.val().length);
                });

                $(document).on('input', '.cs-auto-save-field', (e) => {
                    const $field = $(e.target);
                    const $feedback = $field.siblings('.cs-field-feedback');
                    if ($feedback.length) {
                        const $counter = $feedback.find('.cs-char-count .current');
                        if ($counter.length) {
                            $counter.text($field.val().length);
                        }
                    }
                });
            },

            // Objective Management Methods
            showObjectiveModal: function(lessonId) {
                const modalHtml = `
                    <div class="cs-modal-overlay" id="cs-objective-modal">
                        <div class="cs-modal">
                            <div class="cs-modal-header">
                                <h3>Add Learning Objective</h3>
                                <button type="button" class="cs-modal-close-x">&times;</button>
                            </div>
                            <div class="cs-modal-content">
                                <form id="cs-objective-form">
                                    <div class="cs-form-group">
                                        <label>Thinking Skill (Bloom's Taxonomy) <span class="required">*</span></label>
                                        <select name="thinking_skill" class="cs-form-control" required>
                                            <option value="remember">Remember</option>
                                            <option value="understand" selected>Understand</option>
                                            <option value="apply">Apply</option>
                                            <option value="analyze">Analyze</option>
                                            <option value="evaluate">Evaluate</option>
                                            <option value="create">Create</option>
                                        </select>
                                    </div>
                                    <div class="cs-form-group">
                                        <label>Action Verb <span class="required">*</span></label>
                                        <select name="action_verb" class="cs-form-control" required>
                                            <option value="explain" selected>Explain</option>
                                            <option value="describe">Describe</option>
                                            <option value="demonstrate">Demonstrate</option>
                                            <option value="identify">Identify</option>
                                            <option value="compare">Compare</option>
                                            <option value="analyze">Analyze</option>
                                            <option value="create">Create</option>
                                            <option value="evaluate">Evaluate</option>
                                        </select>
                                    </div>
                                    <div class="cs-form-group">
                                        <label>Description <span class="required">*</span></label>
                                        <textarea name="description" class="cs-form-control" rows="3" 
                                                  placeholder="Students will be able to..." required></textarea>
                                    </div>
                                    <div class="cs-form-actions">
                                        <button type="button" class="cs-btn cs-btn-secondary cs-modal-close-x">Cancel</button>
                                        <button type="submit" class="cs-btn cs-btn-primary">Add Objective</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;

                $('body').append(modalHtml);
                this.bindModalEvents();

                $('#cs-objective-form').on('submit', (e) => {
                    e.preventDefault();
                    const formData = {
                        lesson_id: lessonId,
                        thinking_skill: e.target.thinking_skill.value,
                        action_verb: e.target.action_verb.value,
                        description: e.target.description.value,
                        nonce: this.nonces.objective
                    };
                    this.addObjective(formData);
                });
            },

            addObjective: function(data) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_add_objective',
                        ...data
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            $('#cs-objective-modal').remove();
                            this.refreshObjectivesList(data.lesson_id);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to add objective');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            removeObjective: function(lessonId, index) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_remove_objective',
                        lesson_id: lessonId,
                        objective_index: index,
                        nonce: this.nonces.objective
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            this.refreshObjectivesList(lessonId);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to remove objective');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            // Activity Management Methods
            showActivityModal: function(lessonId) {
                const modalHtml = `
                    <div class="cs-modal-overlay" id="cs-activity-modal">
                        <div class="cs-modal">
                            <div class="cs-modal-header">
                                <h3>Add Learning Activity</h3>
                                <button type="button" class="cs-modal-close-x">&times;</button>
                            </div>
                            <div class="cs-modal-content">
                                <form id="cs-activity-form">
                                    <div class="cs-form-group">
                                        <label>Activity Title <span class="required">*</span></label>
                                        <input type="text" name="title" class="cs-form-control" 
                                               placeholder="Enter activity title..." required>
                                    </div>
                                    <div class="cs-form-group">
                                        <label>Activity Type</label>
                                        <select name="type" class="cs-form-control">
                                            <option value="individual" selected>Individual</option>
                                            <option value="group">Group</option>
                                            <option value="pair">Pair</option>
                                            <option value="discussion">Discussion</option>
                                            <option value="practical">Practical</option>
                                        </select>
                                    </div>
                                    <div class="cs-form-group">
                                        <label>Duration (minutes)</label>
                                        <input type="number" name="duration" class="cs-form-control" 
                                               value="15" min="1" max="120">
                                    </div>
                                    <div class="cs-form-group">
                                        <label>Description <span class="required">*</span></label>
                                        <textarea name="description" class="cs-form-control" rows="4" 
                                                  placeholder="Describe the activity instructions..." required></textarea>
                                    </div>
                                    <div class="cs-form-actions">
                                        <button type="button" class="cs-btn cs-btn-secondary cs-modal-close-x">Cancel</button>
                                        <button type="submit" class="cs-btn cs-btn-primary">Add Activity</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;

                $('body').append(modalHtml);
                this.bindModalEvents();

                $('#cs-activity-form').on('submit', (e) => {
                    e.preventDefault();
                    const formData = {
                        lesson_id: lessonId,
                        title: e.target.title.value,
                        type: e.target.type.value,
                        duration: e.target.duration.value,
                        description: e.target.description.value,
                        nonce: this.nonces.activity
                    };
                    this.addActivity(formData);
                });
            },

            addActivity: function(data) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_add_activity',
                        ...data
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            $('#cs-activity-modal').remove();
                            this.refreshActivitiesList(data.lesson_id);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to add activity');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            removeActivity: function(lessonId, index) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_remove_activity',
                        lesson_id: lessonId,
                        activity_index: index,
                        nonce: this.nonces.activity
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            this.refreshActivitiesList(lessonId);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to remove activity');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            // Teaching Points Management
            addTeachingPoint: function(lessonId, teachingPointData, $btn) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_add_teaching_point',
                        lesson_id: lessonId,
                        point_title: teachingPointData.title,
                        point_description: teachingPointData.description,
                        point_example: teachingPointData.example,
                        point_activity: teachingPointData.activity,
                        content_type: 'structured',
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message, 3000);
                            
                            // Clear form
                            this.clearTeachingPointForm(lessonId);
                            
                            // Reset button
                            $btn.removeClass('disabled').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Teaching Point');
                            
                            // Refresh the list
                            this.refreshTeachingPointsList(lessonId);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to add teaching point');
                            $btn.removeClass('disabled').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Teaching Point');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                        $btn.removeClass('disabled').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Teaching Point');
                    }
                });
            },
            
            clearTeachingPointForm: function(lessonId) {
                $(`#cs-teaching-point-title-${lessonId}`).val('');
                $(`#cs-teaching-point-description-${lessonId}`).val('').css('height', 'auto');
                $(`#cs-teaching-point-example-${lessonId}`).val('');
                $(`#cs-teaching-point-activity-${lessonId}`).val('');
            },
            
            aiGenerateTeachingPoint: function(lessonId) {
                this.showNotification('info', 'AI generation feature coming soon');
            },

            removeTeachingPoint: function(lessonId, index) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_remove_teaching_point',
                        lesson_id: lessonId,
                        point_index: index,
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message, 2000);
                            this.refreshTeachingPointsList(lessonId);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to remove teaching point');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            // Archive/Restore Methods
            archiveLesson: function(lessonId) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_archive_lesson',
                        lesson_id: lessonId,
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            $(`#cs-lesson-${lessonId}`).addClass('archived');
                            // Refresh the view to show archived state
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to archive lesson');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            restoreLesson: function(lessonId) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_restore_lesson',
                        lesson_id: lessonId,
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            // Refresh the view to show restored state
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to restore lesson');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            deleteLesson: function(lessonId) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_delete_lesson',
                        lesson_id: lessonId,
                        confirm: 'DELETE',
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message);
                            $(`#cs-lesson-${lessonId}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to delete lesson');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            // Lesson Sorting Methods
            moveLesson: function(lessonId, direction) {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_move_lesson',
                        lesson_id: lessonId,
                        direction: direction,
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification('success', response.data.message, 2000);
                            // Refresh to show new order
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            this.showNotification('error', response.data?.message || 'Failed to move lesson');
                        }
                    },
                    error: () => {
                        this.showNotification('error', 'Network error occurred');
                    }
                });
            },

            // View Logs
            viewLessonLogs: function(lessonId) {
                // Implementation for viewing logs
                this.showNotification('info', 'Activity logs feature coming soon');
            },

            // Utility Methods
            bindModalEvents: function() {
                $('.cs-modal-close-x, .cs-modal-overlay').on('click', function(e) {
                    if (e.target === this) {
                        $(this).closest('.cs-modal-overlay').remove();
                    }
                });
            },

            refreshObjectivesList: function(lessonId) {
                // Reload objectives section
                setTimeout(() => window.location.reload(), 1000);
            },

            refreshActivitiesList: function(lessonId) {
                // Reload activities section
                setTimeout(() => window.location.reload(), 1000);
            },

            refreshTeachingPointsList: function(lessonId) {
                // Reload teaching points section
                setTimeout(() => window.location.reload(), 1000);
            },
            
            // Additional Methods for Enhanced Functionality
            initEditableFields: function() {
                // Handle editable lesson title
                $(document).on('blur keydown', '.cs-lesson-title-editable', (e) => {
                    if (e.type === 'keydown' && e.keyCode !== 13) return; // Only save on Enter or blur
                    if (e.type === 'keydown') e.preventDefault();
                    
                    const $field = $(e.target);
                    const lessonId = $field.data('lesson-id');
                    const fieldName = $field.data('field');
                    const newValue = $field.text().trim();
                    const originalValue = $field.data('original');
                    
                    if (newValue && newValue !== originalValue) {
                        this.saveLessonField(lessonId, fieldName, newValue, $field);
                        $field.data('original', newValue);
                    }
                });
                
                // Handle contenteditable styling
                $(document).on('focus', '.cs-lesson-title-editable', function() {
                    $(this).addClass('cs-editing');
                });
                
                $(document).on('blur', '.cs-lesson-title-editable', function() {
                    $(this).removeClass('cs-editing');
                });
            },
            
            saveLessonField: function(lessonId, fieldName, fieldValue, $field) {
                const $indicator = $(`#cs-save-title-${lessonId}`);
                $indicator.removeClass('cs-save-saved cs-save-error').addClass('cs-save-saving').text('Saving...').show();
                
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_update_lesson_field',
                        lesson_id: lessonId,
                        field_name: fieldName,
                        field_value: fieldValue,
                        nonce: this.nonces.lesson
                    },
                    success: (response) => {
                        if (response.success) {
                            $indicator.removeClass('cs-save-saving cs-save-error').addClass('cs-save-saved').text('Saved');
                            setTimeout(() => $indicator.fadeOut(), 2000);
                            this.showNotification('success', 'Lesson updated', 2000);
                        } else {
                            $indicator.removeClass('cs-save-saving cs-save-saved').addClass('cs-save-error').text('Error');
                            this.showNotification('error', response.data?.message || 'Failed to update lesson');
                            $field.text($field.data('original'));
                        }
                    },
                    error: () => {
                        $indicator.removeClass('cs-save-saving cs-save-saved').addClass('cs-save-error').text('Error');
                        this.showNotification('error', 'Network error occurred');
                        $field.text($field.data('original'));
                    }
                });
            }
        };

        // Initialize the enhanced lessons system
        LessonsEnhanced.init();

        // Expose to global scope for external access
        window.CourScribeLessonsEnhanced = LessonsEnhanced;
    });
    </script>

    <?php
}

/**
 * Render individual enhanced lesson
 */
function courscribe_render_enhanced_lesson($args) {
    $lesson = $args['lesson'];
    $module = $args['module'];
    $course_id = $args['course_id'];
    $curriculum_id = $args['curriculum_id'];
    $can_edit = $args['can_edit'];
    $is_client = $args['is_client'];
    $current_user = $args['current_user'];
    $tooltips = $args['tooltips'];
    $lesson_nonce = $args['lesson_nonce'];
    $objective_nonce = $args['objective_nonce'];
    $activity_nonce = $args['activity_nonce'];

    $lesson_status = get_post_meta($lesson->ID, '_lesson_status', true) ?: 'active';
    $is_archived = ($lesson_status === 'archived');
    
    // Get lesson objectives and activities
    $objectives = get_post_meta($lesson->ID, '_lesson_objectives', true) ?: [];
    $activities = get_post_meta($lesson->ID, '_lesson_activities', true) ?: [];
    $teaching_points = get_post_meta($lesson->ID, '_teaching_points', true) ?: [];
    $lesson_goal = get_post_meta($lesson->ID, '_lesson_goal', true) ?: '';
    ?>

    <div class="cs-lesson-enhanced <?php echo $is_archived ? 'archived' : ''; ?>" 
         id="cs-lesson-<?php echo esc_attr($lesson->ID); ?>"
         data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
         data-module-id="<?php echo esc_attr($module->ID); ?>"
         data-course-id="<?php echo esc_attr($course_id); ?>">

        <!-- Lesson Header -->
        <div class="cs-lesson-header">
            <?php if ($is_archived && $can_edit): ?>
            <div class="cs-archived-notice">
                <i class="fas fa-archive"></i>
                <span>This lesson is archived.</span>
                <button type="button" class="cs-btn cs-btn-link cs-restore-lesson-btn" 
                        data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                    Restore Lesson
                </button>
            </div>
            <?php endif; ?>

            <div class="cs-lesson-header-content">
                <div class="cs-lesson-sort">
                    <?php if ($can_edit && !$is_archived): ?>
                    <button type="button" class="cs-sort-btn cs-sort-up" 
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Move lesson up">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button type="button" class="cs-sort-btn cs-sort-down" 
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Move lesson down">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="cs-lesson-info">
                    <h4 class="cs-lesson-title">
                        <i class="fas fa-book-open"></i>
                        <?php if ($can_edit && !$is_archived): ?>
                        <span class="cs-lesson-title-editable" 
                              contenteditable="true" 
                              data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                              data-field="post_title"
                              data-original="<?php echo esc_attr($lesson->post_title); ?>"><?php echo esc_html($lesson->post_title); ?></span>
                        <?php else: ?>
                        <?php echo esc_html($lesson->post_title); ?>
                        <?php endif; ?>
                        <div class="cs-save-indicator" id="cs-save-title-<?php echo esc_attr($lesson->ID); ?>"></div>
                    </h4>
                </div>

                <div class="cs-lesson-actions">
                    <?php if ($can_edit && !$is_archived): ?>
                    <button type="button" class="cs-btn cs-btn-success cs-btn-sm cs-save-lesson-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Save all changes">
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                    <button type="button" class="cs-btn cs-btn-secondary cs-btn-sm cs-view-logs-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="View activity logs">
                        <i class="fas fa-history"></i>
                    </button>
                    <button type="button" class="cs-btn cs-btn-warning cs-btn-sm cs-archive-lesson-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Archive lesson">
                        <i class="fas fa-archive"></i>
                    </button>
                    <button type="button" class="cs-btn cs-btn-danger cs-btn-sm cs-delete-lesson-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Delete lesson">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lesson Content -->
        <div class="cs-lesson-content">
            <!-- Lesson Goal -->
            <div class="cs-lesson-field-group">
                <label class="cs-field-label">
                    <i class="fas fa-target"></i>
                    Lesson Goal
                    <span class="cs-required">*</span>
                </label>
                <?php if ($is_client): ?>
                <div class="cs-client-readonly">
                    <textarea class="cs-field-input" readonly><?php echo esc_textarea($lesson_goal); ?></textarea>
                    <button type="button" class="cs-btn cs-btn-feedback cs-btn-sm"
                            data-field="lesson_goal"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                        <i class="fas fa-comment"></i>
                        Give Feedback
                    </button>
                </div>
                <?php else: ?>
                <div class="cs-field-wrapper">
                    <textarea class="cs-field-input cs-auto-save-field" 
                              data-field-name="lesson_goal"
                              data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                              data-original-value="<?php echo esc_attr($lesson_goal); ?>"
                              placeholder="Describe what students will achieve in this lesson..."
                              maxlength="500"
                              rows="3"
                              <?php echo ($is_archived || !$can_edit) ? 'disabled' : ''; ?>><?php echo esc_textarea($lesson_goal); ?></textarea>
                    <div class="cs-field-feedback">
                        <div class="cs-char-count">
                            <span class="current"><?php echo strlen($lesson_goal); ?></span>/<span class="max">500</span>
                        </div>
                        <div class="cs-validation-message"></div>
                    </div>
                    <?php if ($can_edit && !$is_archived): ?>
                    <button type="button" class="cs-ai-suggest-btn" 
                            data-field="lesson_goal"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Get AI suggestions">
                        <i class="fas fa-magic"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Lesson Objectives -->
            <div class="cs-objectives-section">
                <div class="cs-section-header">
                    <h5 class="cs-section-title">
                        <i class="fas fa-list-ol"></i>
                        Learning Objectives
                    </h5>
                    <?php if ($can_edit && !$is_archived): ?>
                    <button type="button" class="cs-btn cs-btn-outline-primary cs-btn-sm cs-add-objective-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                        <i class="fas fa-plus"></i>
                        Add Objective
                    </button>
                    <?php endif; ?>
                </div>

                <div class="cs-objectives-list" id="cs-objectives-list-<?php echo esc_attr($lesson->ID); ?>">
                    <?php if (!empty($objectives)): ?>
                        <?php foreach ($objectives as $index => $objective): ?>
                            <?php courscribe_render_objective_item($objective, $index, $lesson->ID, $can_edit, $is_client, $is_archived, $objective_nonce); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cs-empty-objectives">
                            <i class="fas fa-list-ol"></i>
                            <p>No learning objectives yet. Add your first objective to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lesson Activities -->
            <div class="cs-activities-section">
                <div class="cs-section-header">
                    <h5 class="cs-section-title">
                        <i class="fas fa-tasks"></i>
                        Learning Activities
                    </h5>
                    <?php if ($can_edit && !$is_archived): ?>
                    <button type="button" class="cs-btn cs-btn-outline-primary cs-btn-sm cs-add-activity-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                        <i class="fas fa-plus"></i>
                        Add Activity
                    </button>
                    <?php endif; ?>
                </div>

                <div class="cs-activities-list" id="cs-activities-list-<?php echo esc_attr($lesson->ID); ?>">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $index => $activity): ?>
                            <?php courscribe_render_activity_item($activity, $index, $lesson->ID, $can_edit, $is_client, $is_archived, $activity_nonce); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cs-empty-activities">
                            <i class="fas fa-tasks"></i>
                            <p>No learning activities yet. Add your first activity to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teaching Points -->
            <div class="cs-teaching-points-section">
                <div class="cs-section-header">
                    <h5 class="cs-section-title">
                        <i class="fas fa-lightbulb"></i>
                        Teaching Points
                    </h5>
                    
                </div>
                <?php if ($can_edit && !$is_archived): ?>
                    <div class="cs-add-point-form">
                        <div class="cs-teaching-point-form-container">
                            <div class="cs-form-group">
                                <label class="cs-field-label">
                                    <i class="fas fa-lightbulb"></i>
                                    Teaching Point Title
                                </label>
                                <input type="text" 
                                       class="cs-field-input cs-teaching-point-title" 
                                       id="cs-teaching-point-title-<?php echo esc_attr($lesson->ID); ?>"
                                       placeholder="Enter a descriptive title for this teaching point..."
                                       maxlength="100">
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-field-label">
                                    <i class="fas fa-align-left"></i>
                                    Description
                                </label>
                                <textarea class="cs-field-textarea cs-teaching-point-description" 
                                          id="cs-teaching-point-description-<?php echo esc_attr($lesson->ID); ?>"
                                          data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                                          placeholder="Describe what students will learn in this teaching point. Be specific and clear about the concept or skill being taught..."
                                          rows="4"></textarea>
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-field-label">
                                    <i class="fas fa-lightbulb-o"></i>
                                    Example (Optional)
                                </label>
                                <input type="text" 
                                       class="cs-field-input cs-teaching-point-example" 
                                       id="cs-teaching-point-example-<?php echo esc_attr($lesson->ID); ?>"
                                       placeholder="Provide a practical example or demonstration..."
                                       maxlength="200">
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-field-label">
                                    <i class="fas fa-tasks"></i>
                                    Activity (Optional)
                                </label>
                                <input type="text" 
                                       class="cs-field-input cs-teaching-point-activity" 
                                       id="cs-teaching-point-activity-<?php echo esc_attr($lesson->ID); ?>"
                                       placeholder="Suggest a learning activity or exercise..."
                                       maxlength="200">
                            </div>
                            <div class="cs-teaching-point-actions">
                                <button type="button" class="cs-btn cs-btn-success cs-add-point-btn"
                                        data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                    <i class="fas fa-plus"></i>
                                    Add Teaching Point
                                </button>
                                <button type="button" class="cs-btn cs-btn-secondary cs-clear-point-form-btn"
                                        data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                    <i class="fas fa-times"></i>
                                    Clear Form
                                </button>
                                <button type="button" class="cs-btn cs-btn-secondary cs-ai-generate-point-btn"
                                        data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                    <i class="fas fa-magic"></i>
                                    AI Generate
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                <div class="cs-teaching-points-list" id="cs-teaching-points-list-<?php echo esc_attr($lesson->ID); ?>">
                    <?php if (!empty($teaching_points)): ?>
                        <?php foreach ($teaching_points as $index => $point): ?>
                            <div class="cs-teaching-point-item" data-index="<?php echo esc_attr($index); ?>">
                                <div class="cs-point-content">
                                    <?php if (is_array($point)): ?>
                                        <div class="cs-point-structured">
                                            <h6 class="cs-point-title"><?php echo esc_html($point['title'] ?? 'Teaching Point'); ?></h6>
                                            <?php if (!empty($point['description'])): ?>
                                            <div class="cs-point-description"><?php echo wp_kses_post($point['description']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($point['example'])): ?>
                                            <div class="cs-point-example">
                                                <strong>Example:</strong> <?php echo esc_html($point['example']); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($point['activity'])): ?>
                                            <div class="cs-point-activity">
                                                <strong>Activity:</strong> <?php echo esc_html($point['activity']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="cs-point-text"><?php echo is_string($point) ? esc_html($point) : esc_html(json_encode($point)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($can_edit && !$is_archived): ?>
                                <div class="cs-point-actions">
                                    <button type="button" class="cs-btn cs-btn-sm cs-btn-secondary cs-edit-point-btn"
                                            data-index="<?php echo esc_attr($index); ?>"
                                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-point-btn"
                                            data-index="<?php echo esc_attr($index); ?>"
                                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cs-empty-points">
                            <i class="fas fa-lightbulb"></i>
                            <p>No teaching points yet. Add your first point to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Lesson Styles -->
    <style>
        .cs-lesson-enhanced {
            background: var(--cs-bg-secondary);
            border: 1px solid var(--cs-border-color);
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .cs-lesson-enhanced:hover {
            border-color: var(--cs-primary-gold);
            box-shadow: 0 4px 16px rgba(228, 178, 111, 0.15);
        }

        .cs-lesson-enhanced.archived {
            opacity: 0.7;
            background: var(--cs-bg-elevated);
        }

        .cs-lesson-header {
            background: var(--cs-bg-primary);
            border-bottom: 1px solid var(--cs-border-color);
            padding: 16px 20px;
        }

        .cs-archived-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ffc107;
            font-size: 0.9rem;
        }

        .cs-lesson-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .cs-lesson-sort {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cs-sort-btn {
            background: var(--cs-bg-secondary);
            border: 1px solid var(--cs-border-color);
            border-radius: 4px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--cs-text-secondary);
            transition: all 0.2s ease;
        }

        .cs-sort-btn:hover {
            background: var(--cs-primary-gold);
            color: #ffffff;
            border-color: var(--cs-primary-gold);
        }

        .cs-lesson-info {
            flex: 1;
        }

        .cs-lesson-title {
            margin: 0;
            color: var(--cs-text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cs-lesson-title i {
            color: var(--cs-primary-gold);
        }

        .cs-lesson-actions {
            display: flex;
            gap: 8px;
        }

        .cs-btn-sm {
            padding: 6px 10px;
            font-size: 0.8rem;
        }

        .cs-btn-secondary {
            background: #6c757d;
            color: #ffffff;
            border: 1px solid #6c757d;
        }

        .cs-btn-secondary:hover {
            background: #5a6268;
            border-color: #545b62;
        }

        .cs-btn-warning {
            background: #ffc107;
            color: #212529;
            border: 1px solid #ffc107;
        }

        .cs-btn-warning:hover {
            background: #e0a800;
            border-color: #d39e00;
        }

        .cs-btn-danger {
            background: #dc3545;
            color: #ffffff;
            border: 1px solid #dc3545;
        }

        .cs-btn-danger:hover {
            background: #c82333;
            border-color: #bd2130;
        }

        .cs-lesson-content {
            padding: 20px;
        }

        .cs-lesson-field-group {
            margin-bottom: 24px;
        }

        .cs-field-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--cs-text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .cs-field-label i {
            color: var(--cs-primary-gold);
        }

        .cs-required {
            color: #dc3545;
        }

        .cs-field-wrapper {
            position: relative;
        }

        .cs-field-input {
            width: 100%;
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-border-color);
            border-radius: 6px;
            padding: 12px;
            color: var(--cs-text-primary);
            font-size: 0.95rem;
            transition: all 0.2s ease;
            resize: vertical;
        }

        .cs-field-input:focus {
            border-color: var(--cs-primary-gold);
            box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
            outline: none;
        }

        .cs-field-input:disabled {
            background: var(--cs-bg-elevated);
            opacity: 0.6;
            cursor: not-allowed;
        }

        .cs-field-feedback {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            font-size: 0.8rem;
        }

        .cs-char-count {
            color: var(--cs-text-muted);
        }

        .cs-validation-message {
            color: #dc3545;
            font-weight: 500;
        }

        .cs-ai-suggest-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--cs-gradient-primary);
            border: none;
            border-radius: 4px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cs-ai-suggest-btn:hover {
            background: var(--cs-gradient-secondary);
            transform: scale(1.05);
        }

        .cs-client-readonly {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .cs-client-readonly .cs-field-input {
            flex: 1;
            background: var(--cs-bg-elevated);
            border-color: var(--cs-border-color);
        }

        .cs-btn-feedback {
            background: var(--cs-gradient-primary);
            color: #ffffff;
            border: 1px solid var(--cs-primary-gold);
            white-space: nowrap;
        }

        .cs-btn-feedback:hover {
            background: var(--cs-gradient-secondary);
        }

        .cs-objectives-section,
        .cs-activities-section,
        .cs-teaching-points-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--cs-border-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .cs-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--cs-border-color);
        }

        .cs-section-title {
            margin: 0;
            color: var(--cs-primary-gold);
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cs-btn-outline-primary {
            background: transparent;
            color: var(--cs-primary-gold);
            border: 1px solid var(--cs-primary-gold);
        }

        .cs-btn-outline-primary:hover {
            background: var(--cs-primary-gold);
            color: #ffffff;
        }

        .cs-objectives-list,
        .cs-activities-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cs-empty-objectives,
        .cs-empty-activities,
        .cs-empty-points {
            text-align: center;
            padding: 24px;
            color: var(--cs-text-muted);
        }

        .cs-empty-objectives i,
        .cs-empty-activities i,
        .cs-empty-points i {
            font-size: 2rem;
            color: var(--cs-border-color);
            margin-bottom: 8px;
        }

        .cs-add-point-form {
            margin-bottom: 16px;
        }

        .cs-add-point-form .input-group {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
        }

        .cs-add-point-form .cs-field-input {
            flex: 1;
        }

        .cs-btn-success {
            background: #28a745;
            color: #ffffff;
            border: 1px solid #28a745;
        }

        .cs-btn-success:hover {
            background: #218838;
            border-color: #1e7e34;
        }
        
        /* Enhanced Editable Fields */
        .cs-lesson-title-editable {
            outline: none;
            position: relative;
            transition: all 0.3s ease;
            border-radius: 4px;
            padding: 2px 4px;
            cursor: text;
        }
        
        .cs-lesson-title-editable:hover {
            background: rgba(228, 178, 111, 0.1);
        }
        
        .cs-lesson-title-editable.cs-editing {
            background: rgba(228, 178, 111, 0.2);
            border: 2px solid var(--cs-primary-gold);
        }
        
        .cs-save-indicator {
            display: inline-block;
            margin-left: 8px;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .cs-save-indicator.cs-save-saving {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
            opacity: 1;
        }
        
        .cs-save-indicator.cs-save-saved {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
            opacity: 1;
        }
        
        .cs-save-indicator.cs-save-error {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            opacity: 1;
        }
        
        /* Enhanced Lesson Container */
        .cs-lesson-enhanced {
            background: var(--cs-bg-card);
            border: 1px solid var(--cs-border-color);
            border-radius: var(--cs-border-radius-lg);
            margin-bottom: var(--cs-spacing-lg);
            overflow: hidden;
            transition: var(--cs-transition);
            box-shadow: var(--cs-shadow);
        }
        
        .cs-lesson-enhanced:hover {
            border-color: var(--cs-primary-gold);
            box-shadow: var(--cs-shadow-lg);
        }
        
        /* Objectives, Activities, Teaching Points Sections */
        .cs-objectives-section,
        .cs-activities-section,
        .cs-teaching-points-section {
            background: var(--cs-bg-elevated);
            border: 1px solid var(--cs-border-color);
            border-radius: var(--cs-border-radius);
            padding: var(--cs-spacing-md);
            margin-bottom: var(--cs-spacing-lg);
            box-shadow: var(--cs-shadow-sm);
        }
        
        /* Enhanced Teaching Points Form */
        .cs-teaching-point-form-container {
            background: var(--cs-bg-card);
            border: 1px solid var(--cs-border-color);
            border-radius: var(--cs-border-radius);
            padding: var(--cs-spacing-lg);
            margin-top: var(--cs-spacing-md);
            box-shadow: var(--cs-shadow-sm);
        }
        
        .cs-form-group {
            margin-bottom: 16px;
        }
        
        .cs-form-group:last-child {
            margin-bottom: 0;
        }
        
        .cs-field-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: var(--cs-text-primary);
            font-size: 14px;
            font-weight: 600;
        }
        
        .cs-field-label i {
            color: var(--cs-primary-gold);
            width: 16px;
            text-align: center;
        }
        
        .cs-field-input {
            width: 100%;
            padding: var(--cs-spacing-md);
            background: var(--cs-bg-secondary);
            border: 1px solid var(--cs-border-color);
            border-radius: var(--cs-border-radius-sm);
            color: var(--cs-text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: var(--cs-transition);
            box-sizing: border-box;
        }
        
        .cs-field-textarea {
            width: 100%;
            min-height: 100px;
            padding: var(--cs-spacing-md);
            background: var(--cs-bg-secondary);
            border: 1px solid var(--cs-border-color);
            border-radius: var(--cs-border-radius-sm);
            color: var(--cs-text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            resize: vertical;
            line-height: 1.5;
            transition: var(--cs-transition);
            box-sizing: border-box;
        }
        
        .cs-field-input:focus,
        .cs-field-textarea:focus {
            border-color: var(--cs-primary-gold);
            box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
        }
        
        .cs-field-input::placeholder,
        .cs-field-textarea::placeholder {
            color: var(--cs-text-muted);
            font-style: italic;
        }
        
        .cs-teaching-point-actions {
            display: flex;
            gap: var(--cs-spacing-md);
            justify-content: flex-end;
            margin-top: var(--cs-spacing-lg);
            padding-top: var(--cs-spacing-md);
            border-top: 1px solid var(--cs-border-color);
        }
        
        .cs-teaching-point-actions .cs-btn {
            padding: 10px 16px;
            font-size: 14px;
            border-radius: var(--cs-border-radius-sm);
            transition: var(--cs-transition);
        }
        
        /* Enhanced Button Styles */
        .cs-btn-success {
            background: var(--cs-success);
            color: white;
            border: 1px solid var(--cs-success);
        }
        
        .cs-btn-success:hover {
            background: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
            box-shadow: var(--cs-shadow-sm);
        }
        
        .cs-btn-secondary {
            background: var(--cs-bg-elevated);
            color: var(--cs-text-secondary);
            border: 1px solid var(--cs-border-color);
        }
        
        .cs-btn-secondary:hover {
            background: var(--cs-hover-bg);
            color: var(--cs-text-primary);
            border-color: var(--cs-primary-gold);
        }
        
        .cs-btn-primary {
            background: var(--cs-gradient-primary);
            color: white;
            border: 1px solid var(--cs-primary-gold);
        }
        
        .cs-btn-primary:hover {
            background: var(--cs-gradient-secondary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
        }
        
        .cs-point-title {
            margin: 0 0 8px 0;
            color: var(--cs-primary-gold);
            font-size: 14px;
            font-weight: 600;
        }
        
        .cs-point-description {
            margin: 8px 0;
            color: var(--cs-text-primary);
            font-size: 13px;
            line-height: 1.5;
        }
        
        .cs-point-example,
        .cs-point-activity {
            margin: 6px 0;
            font-size: 12px;
            color: var(--cs-text-secondary);
            padding: 6px;
            background: rgba(228, 178, 111, 0.1);
            border-left: 3px solid var(--cs-primary-gold);
            border-radius: 0 4px 4px 0;
        }
        
        .cs-point-example strong,
        .cs-point-activity strong {
            color: var(--cs-primary-gold);
        }

        .cs-teaching-points-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cs-teaching-point-item {
            background: var(--cs-bg-secondary);
            border: 1px solid var(--cs-border-color);
            border-radius: var(--cs-border-radius-sm);
            padding: var(--cs-spacing-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--cs-spacing-md);
            transition: var(--cs-transition);
        }
        
        .cs-teaching-point-item:hover {
            background: var(--cs-hover-bg);
            border-color: var(--cs-primary-gold);
        }

        .cs-point-content {
            flex: 1;
        }

        .cs-point-text {
            color: var(--cs-text-primary);
        }

        .cs-point-actions {
            display: flex;
            gap: 4px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cs-lesson-header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .cs-lesson-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .cs-client-readonly {
                flex-direction: column;
            }

            .cs-section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .cs-add-point-form .input-group {
                flex-direction: column;
            }

            .cs-teaching-point-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .cs-point-actions {
                align-self: flex-end;
            }
        }
    </style>
    <?php
}

/**
 * Render objective item
 */
function courscribe_render_objective_item($objective, $index, $lesson_id, $can_edit, $is_client, $is_archived, $nonce) {
    $thinking_skill = $objective['thinking_skill'] ?? '';
    $action_verb = $objective['action_verb'] ?? '';
    $description = $objective['description'] ?? '';
    ?>
    <div class="cs-objective-item" data-index="<?php echo esc_attr($index); ?>" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
        <div class="cs-objective-content">
            <div class="cs-objective-header">
                <span class="cs-objective-number"><?php echo $index + 1; ?>.</span>
                <div class="cs-blooms-taxonomy">
                    <span class="cs-thinking-skill"><?php echo esc_html(ucfirst($thinking_skill)); ?></span>
                    <span class="cs-action-verb"><?php echo esc_html($action_verb); ?></span>
                </div>
            </div>
            <div class="cs-objective-description">
                <?php if ($is_client): ?>
                <span class="cs-description-text"><?php echo esc_html($description); ?></span>
                <button type="button" class="cs-btn cs-btn-feedback cs-btn-sm"
                        data-field="objective_<?php echo esc_attr($index); ?>"
                        data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                    <i class="fas fa-comment"></i>
                    Feedback
                </button>
                <?php else: ?>
                <textarea class="cs-field-input cs-objective-description-field cs-auto-save-field"
                          data-field-name="objective_description"
                          data-objective-index="<?php echo esc_attr($index); ?>"
                          data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
                          data-original-value="<?php echo esc_attr($description); ?>"
                          placeholder="Describe what students will be able to do..."
                          rows="2"
                          <?php echo ($is_archived || !$can_edit) ? 'disabled' : ''; ?>><?php echo esc_textarea($description); ?></textarea>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($can_edit && !$is_archived): ?>
        <div class="cs-objective-actions">
            <button type="button" class="cs-btn cs-btn-sm cs-btn-secondary cs-edit-objective-btn"
                    data-index="<?php echo esc_attr($index); ?>"
                    data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-objective-btn"
                    data-index="<?php echo esc_attr($index); ?>"
                    data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .cs-objective-item {
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-border-color);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .cs-objective-content {
            flex: 1;
        }

        .cs-objective-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .cs-objective-number {
            background: var(--cs-primary-gold);
            color: #ffffff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .cs-blooms-taxonomy {
            display: flex;
            gap: 8px;
        }

        .cs-thinking-skill {
            background: rgba(228, 178, 111, 0.2);
            color: var(--cs-primary-gold);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .cs-action-verb {
            background: var(--cs-bg-secondary);
            color: var(--cs-text-secondary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .cs-objective-description {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .cs-description-text {
            flex: 1;
            color: var(--cs-text-primary);
            line-height: 1.5;
        }

        .cs-objective-description-field {
            flex: 1;
            min-height: 60px;
        }

        .cs-objective-actions {
            display: flex;
            gap: 4px;
        }

        @media (max-width: 768px) {
            .cs-objective-item {
                flex-direction: column;
            }

            .cs-objective-actions {
                align-self: flex-end;
            }

            .cs-objective-description {
                flex-direction: column;
            }
        }
    </style>
    <?php
}

/**
 * Render activity item
 */
function courscribe_render_activity_item($activity, $index, $lesson_id, $can_edit, $is_client, $is_archived, $nonce) {
    $title = $activity['title'] ?? '';
    $description = $activity['description'] ?? '';
    $duration = $activity['duration'] ?? '';
    $type = $activity['type'] ?? 'individual';
    ?>
    <div class="cs-activity-item" data-index="<?php echo esc_attr($index); ?>" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
        <div class="cs-activity-content">
            <div class="cs-activity-header">
                <span class="cs-activity-number"><?php echo $index + 1; ?>.</span>
                <div class="cs-activity-meta">
                    <span class="cs-activity-type"><?php echo esc_html(ucfirst($type)); ?></span>
                    <?php if ($duration): ?>
                    <span class="cs-activity-duration"><?php echo esc_html($duration); ?> min</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cs-activity-title">
                <?php if ($is_client): ?>
                <span class="cs-title-text"><?php echo esc_html($title); ?></span>
                <?php else: ?>
                <input type="text" 
                       class="cs-field-input cs-activity-title-field cs-auto-save-field"
                       data-field-name="activity_title"
                       data-activity-index="<?php echo esc_attr($index); ?>"
                       data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
                       data-original-value="<?php echo esc_attr($title); ?>"
                       placeholder="Activity title..."
                       value="<?php echo esc_attr($title); ?>"
                       <?php echo ($is_archived || !$can_edit) ? 'disabled' : ''; ?> />
                <?php endif; ?>
            </div>
            <div class="cs-activity-description">
                <?php if ($is_client): ?>
                <span class="cs-description-text"><?php echo esc_html($description); ?></span>
                <button type="button" class="cs-btn cs-btn-feedback cs-btn-sm"
                        data-field="activity_<?php echo esc_attr($index); ?>"
                        data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                    <i class="fas fa-comment"></i>
                    Feedback
                </button>
                <?php else: ?>
                <textarea class="cs-field-input cs-activity-description-field cs-auto-save-field"
                          data-field-name="activity_description"
                          data-activity-index="<?php echo esc_attr($index); ?>"
                          data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
                          data-original-value="<?php echo esc_attr($description); ?>"
                          placeholder="Describe the activity instructions..."
                          rows="3"
                          <?php echo ($is_archived || !$can_edit) ? 'disabled' : ''; ?>><?php echo esc_textarea($description); ?></textarea>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($can_edit && !$is_archived): ?>
        <div class="cs-activity-actions">
            <button type="button" class="cs-btn cs-btn-sm cs-btn-secondary cs-edit-activity-btn"
                    data-index="<?php echo esc_attr($index); ?>"
                    data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-activity-btn"
                    data-index="<?php echo esc_attr($index); ?>"
                    data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .cs-activity-item {
            background: var(--cs-bg-primary);
            border: 1px solid var(--cs-border-color);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .cs-activity-content {
            flex: 1;
        }

        .cs-activity-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .cs-activity-number {
            background: #28a745;
            color: #ffffff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .cs-activity-meta {
            display: flex;
            gap: 8px;
        }

        .cs-activity-type {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .cs-activity-duration {
            background: var(--cs-bg-secondary);
            color: var(--cs-text-secondary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .cs-activity-title {
            margin-bottom: 8px;
        }

        .cs-title-text {
            font-weight: 600;
            color: var(--cs-text-primary);
        }

        .cs-activity-title-field {
            font-weight: 600;
        }

        .cs-activity-description {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .cs-description-text {
            flex: 1;
            color: var(--cs-text-primary);
            line-height: 1.5;
        }

        .cs-activity-description-field {
            flex: 1;
        }

        .cs-activity-actions {
            display: flex;
            gap: 4px;
        }

        @media (max-width: 768px) {
            .cs-activity-item {
                flex-direction: column;
            }

            .cs-activity-actions {
                align-self: flex-end;
            }

            .cs-activity-description {
                flex-direction: column;
            }
        }
    </style>
    <?php
}

/**
 * Enqueue required assets for enhanced lessons
 */
function courscribe_enqueue_lessons_assets_enhanced() {
    // Enqueue main CourScribe styles if not already loaded
    if (!wp_style_is('courscribe-main', 'enqueued')) {
        wp_enqueue_style('courscribe-main', plugin_dir_url(__FILE__) . '../../assets/css/courscribe-main.css', [], '1.0.0');
    }
    
    // Enqueue enhanced lessons styles
    wp_enqueue_style(
        'courscribe-lessons-enhanced',
        plugin_dir_url(__FILE__) . '../../assets/css/lessons-enhanced.css',
        ['courscribe-main'],
        '1.0.0'
    );
    
    // Enqueue jQuery if not already loaded
    wp_enqueue_script('jquery');
    
    // Enqueue Font Awesome for icons
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        [],
        '6.4.0'
    );
    
    // Additional libraries can be added here as needed
}
?>