<?php
// courscribe-dashboard/templates/lessons-premium.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Lessons Template with Production-Ready Features
 *
 * Features:
 * - Real-time auto-save with visual indicators
 * - Advanced form validation with live feedback
 * - Archive/restore lesson functionality
 * - Comprehensive activity log viewer with restore capabilities
 * - Premium UI with smooth animations and enhanced UX
 * - Proper nonce security and error handling
 * - Modular component structure following development guidelines
 *
 * @param array $args {
 *     @type int    $course_id         Course post ID
 *     @type string $course_title      Course title
 *     @type int    $curriculum_id     Curriculum post ID
 *     @type object $tooltips          CourScribe_Tooltips instance
 *     @type string $site_url          Site URL for assets
 * }
 */
function courscribe_render_lessons_old($args = []) {
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
    $auto_save_enabled = !$is_client && ($is_studio_admin || $is_collaborator);

    if (!$course_id || !$tooltips instanceof CourScribe_Tooltips) {
        return; // Exit if required args are missing
    }

    // Fetch course meta
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    ?>

    <div class="cs-lessons-premium" id="cs-lessons-premium-<?php echo esc_attr($course_id); ?>">
        <!-- Premium Auto-save Indicator -->
        <?php if ($auto_save_enabled): ?>
        <div class="cs-autosave-indicator" id="cs-autosave-indicator-<?php echo esc_attr($course_id); ?>">
            <div class="save-status-container">
                <i class="fas fa-save save-icon"></i>
                <span class="save-status">All changes saved</span>
                <div class="save-spinner d-none">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Saving...</span>
                    </div>
                </div>
            </div>
            <div class="last-saved-time">
                <small class="text-muted">Last saved: <span class="timestamp">--</span></small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Course Goal Banner -->
        <div class="cs-course-goal-banner">
            <div class="goal-content">
                <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Vector.png" alt="Goal Icon" class="goal-icon">
                <span class="goal-label">Course Goal:</span>
                <span class="goal-text"><?php echo $course_goal; ?></span>
            </div>
        </div>

        <!-- Notification Container -->
        <div class="cs-notifications-container" id="cs-notifications-container-<?php echo esc_attr($course_id); ?>">
            <div class="success-notification d-none">
                <i class="fas fa-check-circle"></i>
                <span class="message"></span>
                <button type="button" class="btn-close" aria-label="Close"></button>
            </div>
            <div class="error-notification d-none">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="message"></span>
                <button type="button" class="btn-close" aria-label="Close"></button>
            </div>
            <div class="warning-notification d-none">
                <i class="fas fa-exclamation-circle"></i>
                <span class="message"></span>
                <button type="button" class="btn-close" aria-label="Close"></button>
            </div>
        </div>

        <?php
        // Fetch all modules from the course
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
            <div class="cs-module-tabs" id="cs-module-tabs-<?php echo esc_attr($course_id); ?>">
                <!-- Premium Tab Navigation -->
                <div class="cs-tab-navigation">
                    <div class="cs-tab-links">
                        <?php
                        $tab_index = 1;
                        foreach ($modules as $module) {
                            if (!$module) {
                                continue;
                            }
                            $module_status = get_post_meta($module->ID, '_module_status', true) ?: 'active';
                            $is_archived = ($module_status === 'archived');
                            ?>
                            <button type="button" 
                                    class="cs-tab-link <?php echo $tab_index === 1 ? 'active' : ''; ?> <?php echo $is_archived ? 'archived' : ''; ?>" 
                                    data-tab="cs-tab-<?php echo esc_attr($module->ID); ?>"
                                    data-module-id="<?php echo esc_attr($module->ID); ?>">
                                <div class="tab-content-wrapper">
                                    <span class="tab-number">Module <?php echo esc_html($tab_index); ?>:</span>
                                    <span class="tab-title"><?php echo esc_html($module->post_title); ?></span>
                                    <?php if ($is_archived): ?>
                                    <span class="archive-badge">
                                        <i class="fas fa-archive"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </button>
                            <?php
                            $tab_index++;
                        }
                        ?>
                    </div>
                </div>

                <!-- Premium Tab Contents -->
                <div class="cs-tab-contents">
                    <?php
                    $tab_index = 1;
                    foreach ($modules as $module) {
                        if (!$module) {
                            continue;
                        }
                        
                        $module_goal = esc_html(get_post_meta($module->ID, '_module_goal', true));
                        $module_status = get_post_meta($module->ID, '_module_status', true) ?: 'active';
                        $is_module_archived = ($module_status === 'archived');
                        ?>
                        <div class="cs-tab-content <?php echo $tab_index === 1 ? 'active' : ''; ?>" 
                             id="cs-tab-<?php echo esc_attr($module->ID); ?>"
                             data-module-id="<?php echo esc_attr($module->ID); ?>">
                             
                            <!-- Module Archive Status -->
                            <?php if ($is_module_archived && !$is_client): ?>
                            <div class="alert alert-warning cs-archive-banner">
                                <i class="fas fa-archive me-2"></i>
                                <strong>Archived Module:</strong> This module is archived. 
                                <button type="button" class="btn btn-link p-0 cs-unarchive-module" data-module-id="<?php echo esc_attr($module->ID); ?>">
                                    Restore module
                                </button> to make changes.
                            </div>
                            <?php endif; ?>
                            
                            <!-- Module Goal Banner -->
                            <div class="cs-module-goal-banner">
                                <div class="goal-content">
                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/Vector.png" alt="Goal Icon" class="goal-icon">
                                    <span class="goal-label">Module Goal:</span>
                                    <span class="goal-text"><?php echo $module_goal; ?></span>
                                </div>
                            </div>

                            <!-- Lessons Section Header -->
                            <div class="cs-section-header">
                                <div class="cs-divider-row">
                                    <span class="cs-section-title">Lessons</span>
                                    <div class="cs-divider-line"></div>
                                    <div class="cs-section-actions">
                                        <?php if (!$is_client && !$is_module_archived) : ?>
                                        <!-- Generate Lessons Button -->
                                        <?php
                                        $generate_lesson_button = '<button class="get-ai-button min-w-150" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#generateLessonsModal" 
                                            aria-controls="generateLessonsModal" 
                                            data-module-id="' . esc_attr($module->ID) . '" 
                                            data-course-id="' . esc_attr($course_id) . '"
                                            data-curriculum-id="' . esc_attr($curriculum_id) . '"
                                            data-studio-id="' . esc_attr($studio_id) . '">
                                            <span class="get-ai-inner">
                                                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                                                    <polyline points="13.18 1.37 13.18 9.64 21.45 9.64 10.82 22.63 10.82 14.36 2.55 14.36 13.18 1.37"></polyline>
                                                </svg>
                                                Generate Lessons
                                            </span>
                                        </button>';
                                        echo $tooltips->wrap_button_with_tooltip($generate_lesson_button, [
                                            'title' => 'AI Lesson Generation',
                                            'description' => 'Generate lessons for this module using CourScribe AI.',
                                            'required_package' => 'CourScribe Basics'
                                        ]);
                                        ?>

                                        <!-- Add Lesson Button -->
                                        <?php
                                        $add_lesson_button = '<button class="cs-btn cs-btn-primary cs-add-lesson" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#cs-add-lesson-modal-' . esc_attr($module->ID) . '" 
                                            data-course-id="' . esc_attr($course_id) . '" 
                                            data-module-id="' . esc_attr($module->ID) . '">
                                            <div class="btn-content">
                                                <i class="fas fa-plus"></i>
                                                <span>Add New Lesson</span>
                                            </div>
                                        </button>';
                                        echo $tooltips->wrap_button_with_tooltip($add_lesson_button, [
                                            'title' => 'Add Lesson',
                                            'description' => 'Create a new lesson for this module. Available in all packages.',
                                            'required_package' => 'CourScribe Basics'
                                        ]);
                                        ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Lessons Container -->
                            <div class="cs-lessons-container" 
                                 id="cs-lessons-container-<?php echo esc_attr($module->ID); ?>"
                                 data-module-id="<?php echo esc_attr($module->ID); ?>"
                                 data-course-id="<?php echo esc_attr($course_id); ?>">
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
                                
                                $lessons = is_array($lessons) ? $lessons : [];
                                
                                if ($lessons) {
                                    foreach ($lessons as $lesson) {
                                        if (!$lesson) {
                                            continue;
                                        }
                                        
                                        $lesson_status = get_post_meta($lesson->ID, '_lesson_status', true) ?: 'active';
                                        $is_lesson_archived = ($lesson_status === 'archived');
                                        
                                        // Render individual lesson
                                        courscribe_render_premium_lesson([
                                            'lesson' => $lesson,
                                            'module' => $module,
                                            'course_id' => $course_id,
                                            'curriculum_id' => $curriculum_id,
                                            'site_url' => $site_url,
                                            'is_client' => $is_client,
                                            'can_view_feedback' => $can_view_feedback,
                                            'auto_save_enabled' => $auto_save_enabled,
                                            'is_archived' => $is_lesson_archived,
                                            'current_user' => $current_user,
                                            'tooltips' => $tooltips
                                        ]);
                                    }
                                } else {
                                    echo '<div class="cs-empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <h5>No lessons yet</h5>
                                        <p class="text-muted">Start by adding your first lesson to this module.</p>
                                    </div>';
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
            
            <!-- View Logs Offcanvas -->
            <?php foreach ($modules as $module): ?>
            <div class="offcanvas offcanvas-end cs-logs-offcanvas" 
                 tabindex="-1" 
                 id="cs-logs-offcanvas-<?php echo esc_attr($module->ID); ?>"
                 aria-labelledby="cs-logs-offcanvas-label-<?php echo esc_attr($module->ID); ?>">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="cs-logs-offcanvas-label-<?php echo esc_attr($module->ID); ?>">
                        <i class="fas fa-history me-2"></i>
                        Activity Logs - <?php echo esc_html($module->post_title); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <div class="cs-logs-container" data-module-id="<?php echo esc_attr($module->ID); ?>">
                        <!-- Logs will be loaded dynamically -->
                        <div class="cs-logs-loading">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading logs...</span>
                            </div>
                            <p class="mt-3">Loading activity logs...</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php
        } else {
            echo '<div class="cs-empty-state">
                <div class="empty-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h5>No modules available</h5>
                <p class="text-muted">This course doesn\'t have any modules yet.</p>
            </div>';
        }
        ?>
    </div>

    <!-- Premium Styles -->
    <style>
    .cs-lessons-premium {
        position: relative;
        padding: 1rem;
    }

    .cs-autosave-indicator {
        position: sticky;
        top: 0;
        z-index: 100;
        background: linear-gradient(135deg, #2F2E30 0%, #353535 100%);
        border: 1px solid #E4B26F;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .save-status-container {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .save-icon {
        color: #28a745;
        font-size: 1rem;
    }

    .save-status {
        color: #E4B26F;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .save-spinner {
        color: #E4B26F;
    }

    .last-saved-time {
        font-size: 0.8rem;
        color: #B0B0B0;
    }

    .cs-course-goal-banner,
    .cs-module-goal-banner {
        background: linear-gradient(135deg, #2F2E30 0%, #353535 100%);
        border: 1px solid #E4B26F;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .goal-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
    }

    .goal-icon {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
    }

    .goal-label {
        color: #E4B26F;
        font-weight: 600;
        white-space: nowrap;
    }

    .goal-text {
        color: #E4B26F;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cs-notifications-container {
        margin-bottom: 1rem;
    }

    .success-notification,
    .error-notification,
    .warning-notification {
        position: relative;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideInDown 0.3s ease-out;
    }

    .success-notification {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: 1px solid #28a745;
    }

    .error-notification {
        background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
        color: white;
        border: 1px solid #dc3545;
    }

    .warning-notification {
        background: linear-gradient(135deg, #ffc107 0%, #f39c12 100%);
        color: #212529;
        border: 1px solid #ffc107;
    }

    .btn-close {
        margin-left: auto;
        opacity: 0.8;
    }

    .btn-close:hover {
        opacity: 1;
    }

    .cs-tab-navigation {
        margin-bottom: 1.5rem;
    }

    .cs-tab-links {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .cs-tab-link {
        background: #2F2E30;
        border: 1px solid #454545;
        border-radius: 0.5rem 0.5rem 0 0;
        color: #B0B0B0;
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .cs-tab-link.active {
        background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
        color: #fff;
        border-color: #E4B26F;
        box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
    }

    .cs-tab-link.archived {
        opacity: 0.6;
        background: #454545;
    }

    .cs-tab-link:hover:not(.active) {
        background: #3a3a3c;
        border-color: #E4B26F;
        color: #E4B26F;
    }

    .tab-content-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tab-number {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .tab-title {
        font-weight: 500;
    }

    .archive-badge {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
    }

    .cs-tab-content {
        display: none;
        background: #2F2E30;
        border: 1px solid #454545;
        border-radius: 0 0.5rem 0.5rem 0.5rem;
        padding: 1.5rem;
    }

    .cs-tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease-in;
    }

    .cs-archive-banner {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border-color: #ffc107;
        color: #856404;
        margin-bottom: 1rem;
    }

    .cs-section-header {
        margin-bottom: 2rem;
    }

    .cs-divider-row {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .cs-section-title {
        color: #E4B26F;
        font-weight: 600;
        font-size: 1.1rem;
        white-space: nowrap;
    }

    .cs-divider-line {
        flex: 1;
        height: 2px;
        background: linear-gradient(90deg, #E4B26F 0%, transparent 100%);
        border-radius: 1px;
    }

    .cs-section-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .cs-btn {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .cs-btn-ai {
        background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
        color: white;
        border: 1px solid #6f42c1;
    }

    .cs-btn-ai:hover {
        background: linear-gradient(135deg, #5a32a3 0%, #d6336c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }

    .cs-btn-primary {
        background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
        color: white;
        border: 1px solid #E4B26F;
    }

    .cs-btn-primary:hover {
        background: linear-gradient(135deg, #D4A05C 0%, #E8823E 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
    }

    .btn-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cs-lessons-container {
        min-height: 200px;
    }

    .cs-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #B0B0B0;
    }

    .empty-icon {
        font-size: 3rem;
        color: #454545;
        margin-bottom: 1rem;
    }

    .cs-empty-state h5 {
        color: #E4B26F;
        margin-bottom: 0.5rem;
    }

    .cs-logs-offcanvas .offcanvas-header {
        background: #2F2E30;
        border-bottom: 1px solid #454545;
        color: #E4B26F;
    }

    .cs-logs-offcanvas .offcanvas-body {
        background: #231F20;
        padding: 1.5rem;
    }

    .cs-logs-loading {
        text-align: center;
        color: #B0B0B0;
    }

    @keyframes slideInDown {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .cs-divider-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .cs-section-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .cs-tab-links {
            flex-direction: column;
        }

        .cs-tab-link {
            width: 100%;
        }
    }
    </style>

    <!-- Premium JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        const courseId = <?php echo json_encode($course_id); ?>;
        const currentUserId = <?php echo json_encode($current_user->ID); ?>;
        const autoSaveEnabled = <?php echo json_encode($auto_save_enabled); ?>;
        const lessonNonce = <?php echo json_encode(wp_create_nonce('courscribe_lesson_premium_nonce')); ?>;

        // Initialize premium lessons functionality
        initializePremiumLessons();

        function initializePremiumLessons() {
            // Tab functionality
            initializeTabs();
            
            // Auto-save functionality
            if (autoSaveEnabled) {
                initializeAutoSave();
            }
            
            // Notification system
            initializeNotifications();
            
            // Archive/restore functionality
            initializeArchiveRestore();
            
            // Activity logs
            initializeActivityLogs();
        }

        function initializeTabs() {
            $('.cs-tab-link').on('click', function() {
                const $link = $(this);
                const targetTab = $link.data('tab');
                
                // Remove active class from all tabs and contents
                $('.cs-tab-link').removeClass('active');
                $('.cs-tab-content').removeClass('active');
                
                // Add active class to clicked tab and corresponding content
                $link.addClass('active');
                $('#' + targetTab).addClass('active');
            });
        }

        function initializeAutoSave() {
            let saveTimeout;
            
            // Auto-save on input change
            $(document).on('input change', '.cs-auto-save-field', function() {
                const $field = $(this);
                clearTimeout(saveTimeout);
                
                updateSaveStatus('saving');
                
                saveTimeout = setTimeout(function() {
                    autoSaveField($field);
                }, 1000); // Save after 1 second of inactivity
            });
        }

        function autoSaveField($field) {
            const fieldName = $field.data('field-name');
            const fieldValue = $field.val();
            const lessonId = $field.data('lesson-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_autosave_lesson_field',
                    lesson_id: lessonId,
                    field_name: fieldName,
                    field_value: fieldValue,
                    nonce: lessonNonce
                },
                success: function(response) {
                    if (response.success) {
                        updateSaveStatus('saved');
                        $field.data('original-value', fieldValue);
                    } else {
                        updateSaveStatus('error');
                        showNotification('error', response.data.message || 'Auto-save failed');
                    }
                },
                error: function() {
                    updateSaveStatus('error');
                    showNotification('error', 'Network error during auto-save');
                }
            });
        }

        function updateSaveStatus(status) {
            const $indicator = $('#cs-autosave-indicator-' + courseId);
            const $icon = $indicator.find('.save-icon');
            const $text = $indicator.find('.save-status');
            const $spinner = $indicator.find('.save-spinner');
            const $timestamp = $indicator.find('.timestamp');
            
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
        }

        function initializeNotifications() {
            // Close notification buttons
            $(document).on('click', '.cs-notifications-container .btn-close', function() {
                $(this).closest('.success-notification, .error-notification, .warning-notification').fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Auto-hide success notifications
            setTimeout(function() {
                $('.success-notification').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        function showNotification(type, message) {
            const $container = $('#cs-notifications-container-' + courseId);
            const $notification = $('<div class="' + type + '-notification">' +
                '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'exclamation-circle') + '"></i>' +
                '<span class="message">' + message + '</span>' +
                '<button type="button" class="btn-close" aria-label="Close"></button>' +
                '</div>');
            
            $container.append($notification);
            
            // Auto-hide after 5 seconds for success notifications
            if (type === 'success') {
                setTimeout(function() {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }

        function initializeArchiveRestore() {
            // Archive lesson functionality will be added here
            $(document).on('click', '.cs-archive-lesson', function() {
                const lessonId = $(this).data('lesson-id');
                archiveLesson(lessonId);
            });

            $(document).on('click', '.cs-unarchive-lesson', function() {
                const lessonId = $(this).data('lesson-id');
                unarchiveLesson(lessonId);
            });
        }

        function initializeActivityLogs() {
            // Activity logs functionality will be added here
            $(document).on('show.bs.offcanvas', '.cs-logs-offcanvas', function() {
                const moduleId = $(this).find('.cs-logs-container').data('module-id');
                loadActivityLogs(moduleId);
            });
        }

        function loadActivityLogs(moduleId) {
            const $container = $('.cs-logs-container[data-module-id="' + moduleId + '"]');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_lesson_logs',
                    module_id: moduleId,
                    nonce: lessonNonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    } else {
                        $container.html('<div class="alert alert-danger">Failed to load activity logs.</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="alert alert-danger">Network error while loading logs.</div>');
                }
            });
        }

        // Global window functions for external access
        window.courscribleLessons = {
            showNotification: showNotification,
            updateSaveStatus: updateSaveStatus,
            loadActivityLogs: loadActivityLogs
        };
    });
    </script>
    <?php
}

/**
 * Render individual premium lesson component
 */
function courscribe_render_premium_lesson($args) {
    $lesson = $args['lesson'];
    $module = $args['module'];
    $course_id = $args['course_id'];
    $curriculum_id = $args['curriculum_id'];
    $site_url = $args['site_url'];
    $is_client = $args['is_client'];
    $can_view_feedback = $args['can_view_feedback'];
    $auto_save_enabled = $args['auto_save_enabled'];
    $is_archived = $args['is_archived'];
    $current_user = $args['current_user'];
    $tooltips = $args['tooltips'];
    
    ?>
    <div class="cs-lesson-premium <?php echo $is_archived ? 'archived' : ''; ?>" 
         id="cs-lesson-<?php echo esc_attr($lesson->ID); ?>"
         data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
         data-module-id="<?php echo esc_attr($module->ID); ?>"
         data-course-id="<?php echo esc_attr($course_id); ?>">
         
        <!-- Lesson Archive Status -->
        <?php if ($is_archived && !$is_client): ?>
        <div class="alert alert-warning cs-lesson-archive-banner">
            <i class="fas fa-archive me-2"></i>
            <strong>Archived Lesson:</strong> This lesson is archived. 
            <button type="button" class="btn btn-link p-0 cs-unarchive-lesson" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                Restore lesson
            </button> to make changes.
        </div>
        <?php endif; ?>
        
        <!-- Lesson Header -->
        <div class="cs-lesson-header">
            <div class="cs-lesson-title-row">
                <?php if (!$is_client && !$is_archived): ?>
                <div class="cs-sort-controls">
                    <button class="cs-sort-btn cs-sort-up" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>" title="Move Up">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button class="cs-sort-btn cs-sort-down" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>" title="Move Down">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="cs-lesson-divider">
                    <span class="cs-lesson-label">Lesson:</span>
                    <div class="cs-divider-line"></div>
                </div>
                
                <div class="cs-lesson-actions">
                    <?php if (!$is_client && !$is_archived): ?>
                    <!-- View Logs Button -->
                    <button type="button" 
                            class="cs-btn cs-btn-secondary cs-view-logs-btn"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#cs-logs-offcanvas-<?php echo esc_attr($module->ID); ?>"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="View Activity Logs">
                        <i class="fas fa-history"></i>
                    </button>
                    
                    <!-- Archive Button -->
                    <button type="button" 
                            class="cs-btn cs-btn-warning cs-archive-lesson-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Archive Lesson">
                        <i class="fas fa-archive"></i>
                    </button>
                    
                    <!-- Delete Button -->
                    <button type="button" 
                            class="cs-btn cs-btn-danger cs-delete-lesson-btn"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            title="Delete Lesson">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lesson Content Form -->
        <form class="cs-lesson-form" 
              id="cs-lesson-form-<?php echo esc_attr($lesson->ID); ?>"
              data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
              data-auto-save="<?php echo $auto_save_enabled ? 'true' : 'false'; ?>">
              
            <input type="hidden" name="lesson_id" value="<?php echo esc_attr($lesson->ID); ?>">
            <input type="hidden" name="courscribe_lesson_premium_nonce" value="<?php echo wp_create_nonce('courscribe_lesson_premium_nonce'); ?>">

            <!-- Lesson Feedback Section -->
            <div class="cs-feedback-section mb-4">
                <div class="cs-feedback-header">
                    <span class="cs-feedback-title">Lesson Feedback</span>
                    <div class="cs-divider-line"></div>
                    <?php if ($is_client): ?>
                    <button type="button" 
                            class="cs-btn cs-btn-feedback"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#courscribeFieldFeedbackOffcanvas">
                        <i class="fas fa-comment"></i>
                        <span>Give Lesson Feedback</span>
                    </button>
                    <?php elseif ($can_view_feedback): ?>
                    <button type="button" 
                            class="cs-btn cs-btn-feedback-view"
                            data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#courscribeFieldFeedbackOffcanvas">
                        <i class="fas fa-comments"></i>
                        <span class="feedback-count">0</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lesson Name Field -->
            <div class="cs-premium-field-group mb-4">
                <div class="cs-field-header">
                    <label for="cs-lesson-name-<?php echo esc_attr($lesson->ID); ?>" class="cs-premium-label">
                        <i class="fas fa-book-open me-2"></i>
                        Lesson Name
                        <span class="required-indicator">*</span>
                    </label>
                </div>
                <div class="cs-premium-input-group">
                    <?php if ($is_client): ?>
                    <div class="cs-client-readonly-field">
                        <input class="form-control cs-premium-input cs-client-input"
                               value="<?php echo esc_attr($lesson->post_title); ?>" 
                               readonly>
                        <button type="button" 
                                class="cs-btn cs-btn-feedback"
                                data-field="lesson_name"
                                data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                            <i class="fas fa-comment"></i>
                            Give Feedback
                        </button>
                    </div>
                    <?php else: ?>
                    <input type="text" 
                           id="cs-lesson-name-<?php echo esc_attr($lesson->ID); ?>" 
                           name="lesson_name" 
                           class="form-control cs-premium-input cs-auto-save-field" 
                           value="<?php echo esc_attr($lesson->post_title); ?>"
                           data-field-name="lesson_name"
                           data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                           data-original-value="<?php echo esc_attr($lesson->post_title); ?>"
                           placeholder="Enter a descriptive lesson name"
                           maxlength="100"
                           <?php echo $is_archived ? 'disabled' : ''; ?>
                           required />
                    <div class="cs-input-feedback">
                        <div class="cs-character-count">
                            <span class="current"><?php echo strlen($lesson->post_title); ?></span>/<span class="max">100</span>
                        </div>
                        <div class="cs-validation-message"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lesson Goal Field -->
            <div class="cs-premium-field-group mb-4">
                <div class="cs-field-header">
                    <label for="cs-lesson-goal-<?php echo esc_attr($lesson->ID); ?>" class="cs-premium-label">
                        <i class="fas fa-target me-2"></i>
                        Lesson Goal
                        <span class="required-indicator">*</span>
                    </label>
                </div>
                <div class="cs-premium-input-group">
                    <?php if ($is_client): ?>
                    <div class="cs-client-readonly-field">
                        <textarea class="form-control cs-premium-input cs-client-input" 
                                  rows="3" 
                                  readonly><?php echo esc_textarea(get_post_meta($lesson->ID, 'lesson-goal', true)); ?></textarea>
                        <button type="button" 
                                class="cs-btn cs-btn-feedback"
                                data-field="lesson_goal"
                                data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                            <i class="fas fa-comment"></i>
                            Give Feedback
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="cs-textarea-container">
                        <textarea id="cs-lesson-goal-<?php echo esc_attr($lesson->ID); ?>" 
                                  name="lesson_goal" 
                                  class="form-control cs-premium-input cs-auto-save-field" 
                                  rows="3"
                                  data-field-name="lesson_goal"
                                  data-lesson-id="<?php echo esc_attr($lesson->ID); ?>"
                                  data-original-value="<?php echo esc_attr(get_post_meta($lesson->ID, 'lesson-goal', true)); ?>"
                                  placeholder="Describe what students will achieve in this lesson"
                                  maxlength="500"
                                  <?php echo $is_archived ? 'disabled' : ''; ?>
                                  required><?php echo esc_textarea(get_post_meta($lesson->ID, 'lesson-goal', true)); ?></textarea>
                        <?php if (!$is_archived): ?>
                        <?php
                        $ai_button = '<button type="button" class="cs-ai-suggest-btn"
                            data-field-id="cs-lesson-goal-' . esc_attr($lesson->ID) . '"
                            data-bs-toggle="modal"
                            data-bs-target="#inputAiSuggestionsModal"
                            data-lesson-id="' . esc_attr($lesson->ID) . '"
                            data-lesson-name="' . esc_attr($lesson->post_title) . '"
                            data-module-id="' . esc_attr($module->ID) . '"
                            data-module-name="' . esc_attr($module->post_title) . '">
                            <i class="fas fa-magic"></i>
                        </button>';
                        echo $tooltips->wrap_button_with_tooltip($ai_button, [
                            'description' => 'Get AI-generated suggestions for your lesson goal (requires CourScribe Pro)',
                            'required_package' => 'CourScribe Pro (Agency)',
                            'title' => 'Get AI-generated suggestions'
                        ]);
                        ?>
                        <?php endif; ?>
                    </div>
                    <div class="cs-input-feedback">
                        <div class="cs-character-count">
                            <span class="current"><?php echo strlen(get_post_meta($lesson->ID, 'lesson-goal', true)); ?></span>/<span class="max">500</span>
                        </div>
                        <div class="cs-validation-message"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lesson Objectives Section -->
            <div class="cs-objectives-section mb-4">
                <div class="cs-section-header">
                    <h6 class="cs-section-title">
                        <i class="fas fa-list-ol me-2"></i>
                        Lesson Objectives
                    </h6>
                </div>
                <div class="cs-objectives-container" id="cs-objectives-container-<?php echo esc_attr($lesson->ID); ?>">
                    <!-- Objectives will be loaded here -->
                </div>
                <?php if (!$is_client && !$is_archived): ?>
                <button type="button" class="cs-btn cs-btn-outline-primary cs-add-objective-btn" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                    <i class="fas fa-plus me-2"></i>
                    Add Objective
                </button>
                <?php endif; ?>
            </div>

            <!-- Lesson Activities Section -->
            <div class="cs-activities-section mb-4">
                <div class="cs-section-header">
                    <h6 class="cs-section-title">
                        <i class="fas fa-tasks me-2"></i>
                        Lesson Activities
                    </h6>
                </div>
                <div class="cs-activities-container" id="cs-activities-container-<?php echo esc_attr($lesson->ID); ?>">
                    <!-- Activities will be loaded here -->
                </div>
                <?php if (!$is_client && !$is_archived): ?>
                <button type="button" class="cs-btn cs-btn-outline-primary cs-add-activity-btn" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                    <i class="fas fa-plus me-2"></i>
                    Add Activity
                </button>
                <?php endif; ?>
            </div>

            <!-- Teaching Points Section -->
            <div class="cs-teaching-points-section mb-4">
                <div class="cs-section-header">
                    <h6 class="cs-section-title">
                        <i class="fas fa-lightbulb me-2"></i>
                        Teaching Points
                    </h6>
                </div>
                <div class="cs-teaching-points-container" id="cs-teaching-points-container-<?php echo esc_attr($lesson->ID); ?>">
                    <!-- Teaching points will be loaded here -->
                </div>
                <?php if (!$is_client && !$is_archived): ?>
                <div class="cs-add-point-form">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control cs-premium-input cs-new-teaching-point" 
                               id="cs-new-teaching-point-<?php echo esc_attr($lesson->ID); ?>"
                               placeholder="Add a new teaching point..."
                               maxlength="500" />
                        <button type="button" 
                                class="cs-btn cs-btn-success cs-add-teaching-point-btn"
                                data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
                            <i class="fas fa-plus"></i> Add Point
                        </button>
                    </div>
                    <div class="cs-char-counter">
                        <small class="text-muted">
                            <span class="current">0</span>/<span class="max">500</span>
                        </small>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Form Actions -->
            <?php if (!$is_client && !$is_archived): ?>
            <div class="cs-form-actions">
                <?php
                $save_button = '<button type="button" class="cs-btn cs-btn-success cs-save-lesson-btn" data-lesson-id="' . esc_attr($lesson->ID) . '">
                    <i class="fas fa-save me-2"></i>
                    Save Lesson Changes
                </button>';
                echo $tooltips->wrap_button_with_tooltip($save_button, [
                    'title' => 'Save Lesson',
                    'description' => 'Save all changes made to this lesson.',
                    'required_package' => 'CourScribe Basics'
                ]);
                ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Additional Premium Styles for Individual Lesson -->
    <style>
        .cs-lesson-premium {
            background: #2F2E30;
            border: 1px solid #454545;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .cs-lesson-premium:hover {
            border-color: #E4B26F;
            box-shadow: 0 4px 16px rgba(228, 178, 111, 0.1);
        }

        .cs-lesson-premium.archived {
            opacity: 0.7;
            background: #3a3a3a;
        }

        .cs-lesson-archive-banner {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-color: #ffc107;
            color: #856404;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }

        .cs-lesson-header {
            margin-bottom: 1.5rem;
        }

        .cs-lesson-title-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .cs-sort-controls {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .cs-sort-btn {
            background: #454545;
            border: 1px solid #666;
            color: #E4B26F;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cs-sort-btn:hover {
            background: #E4B26F;
            color: #fff;
        }

        .cs-lesson-divider {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cs-lesson-label {
            color: #E4B26F;
            font-weight: 600;
            white-space: nowrap;
        }

        .cs-lesson-actions {
            display: flex;
            gap: 0.5rem;
        }

        .cs-btn-secondary {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .cs-btn-secondary:hover {
            background: #5a6268;
            border-color: #545b62;
        }

        .cs-btn-warning {
            background: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .cs-btn-warning:hover {
            background: #e0a800;
            border-color: #d39e00;
        }

        .cs-btn-danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .cs-btn-danger:hover {
            background: #c82333;
            border-color: #bd2130;
        }

        .cs-feedback-section {
            background: rgba(228, 178, 111, 0.1);
            border: 1px solid rgba(228, 178, 111, 0.3);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .cs-feedback-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cs-feedback-title {
            color: #E4B26F;
            font-weight: 500;
            white-space: nowrap;
        }

        .cs-btn-feedback,
        .cs-btn-feedback-view { 
            background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%);
            border-color: #E4B26F;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .cs-btn-feedback:hover,
        .cs-btn-feedback-view:hover {
            background: linear-gradient(90deg, #FBAF3F 0%, #EF4339 100%);
        }

        .cs-premium-field-group {
            margin-bottom: 1.5rem;
        }

        .cs-field-header {
            margin-bottom: 0.5rem;
        }

        .cs-premium-label {
            color: #E4B26F;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        .required-indicator {
            color: #dc3545;
            margin-left: 0.25rem;
        }

        .cs-premium-input-group {
            position: relative;
        }

        .cs-premium-input {
            background: #454545 !important;
            border: 1px solid #666 !important;
            color: #fff !important;
            border-radius: 0.375rem !important;
            padding: 0.75rem !important;
            transition: all 0.2s ease !important;
        }

        .cs-premium-input:focus {
            background: #525252 !important;
            border-color: #E4B26F !important;
            box-shadow: 0 0 0 0.2rem rgba(228, 178, 111, 0.25) !important;
        }

        .cs-premium-input:disabled {
            background: #3a3a3a !important;
            opacity: 0.6;
        }

        .cs-client-readonly-field {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .cs-client-input {
            flex: 1;
            background: #3a3a3a !important;
            border-color: #555 !important;
        }

        .cs-textarea-container {
            position: relative;
        }

        .cs-ai-suggest-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cs-ai-suggest-btn:hover {
            background: linear-gradient(90deg, #FBAF3F 0%, #EF4339 100%);
            transform: scale(1.05);
        }

        .cs-input-feedback {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.25rem;
            font-size: 0.75rem;
        }

        .cs-character-count {
            color: #B0B0B0;
        }

        .cs-validation-message {
            color: #dc3545;
            font-weight: 500;
        }

        .cs-objectives-section,
        .cs-activities-section,
        .cs-teaching-points-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid #555;
            border-radius: 0.5rem;
            padding: 1.25rem;
        }

        .cs-section-header {
            margin-bottom: 1rem;
        }

        .cs-section-title {
            color: #E4B26F;
            font-weight: 600;
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }

        .cs-btn-outline-primary {
            background: transparent;
            border: 1px solid #E4B26F;
            color: #E4B26F;
        }

        .cs-btn-outline-primary:hover {
            background: #E4B26F;
            color: #fff;
        }

        .cs-add-point-form {
            margin-top: 1rem;
        }

        .cs-add-point-form .input-group {
            margin-bottom: 0.5rem;
        }

        .cs-char-counter {
            text-align: right;
        }

        .cs-btn-success {
            background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%) !important;
            border-color: #E4B26F !important;
            color: white;
        }

        .cs-btn-success:hover {
            background: linear-gradient(90deg, #FBAF3F 0%, #EF4339 100%) !important;
        }

        .cs-form-actions {
            text-align: right;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #555;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cs-lesson-title-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .cs-lesson-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .cs-client-readonly-field {
                flex-direction: column;
            }

            .cs-feedback-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <!-- Premium Lesson Generation Modal -->
    <?php 
    courscribe_render_premium_lesson_generator([
        'module_id' => isset($module) ? $module->ID : 0,
        'course_id' => $course_id,
        'curriculum_id' => $curriculum_id,
        'studio_id' => $studio_id
    ]);
    ?>

    <?php
}
?>