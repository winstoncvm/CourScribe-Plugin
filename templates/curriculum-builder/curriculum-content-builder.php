<?php
/**
 * CourScribe Curriculum Content Builder - Main Shortcode
 * 
 * Creates a comprehensive modern document-like interface for curriculum content development
 * with drag-and-drop functionality, AI integration, templates, and PDF export capabilities.
 * 
 * @package CourScribe
 * @since 1.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register the shortcode
add_shortcode('courscribe_curriculum_content_builder', 'courscribe_curriculum_content_builder_shortcode');

/**
 * Enqueue assets for curriculum content builder
 */
function courscribe_enqueue_curriculum_builder_assets($curriculum_id) {
    // Get the main plugin directory URL correctly
    $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
    
    // Enqueue Font Awesome for icons
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
        [],
        '6.0.0'
    );
    
    // Enqueue Sortable.js for drag and drop
    wp_enqueue_script(
        'sortablejs',
        'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
        [],
        '1.15.0',
        true
    );
    
    // Enqueue Editor.js with specific stable versions for better reliability
    wp_enqueue_script(
        'editorjs',
        'https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.28.2/dist/editorjs.umd.js',
        [],
        '2.28.2',
        true
    );
    
    // Enqueue Editor.js plugins with specific versions
    wp_enqueue_script(
        'editorjs-header',
        'https://cdn.jsdelivr.net/npm/@editorjs/header@2.7.0/dist/header.umd.js',
        ['editorjs'],
        '2.7.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-list',
        'https://cdn.jsdelivr.net/npm/@editorjs/list@1.8.0/dist/list.umd.js',
        ['editorjs'],
        '1.8.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-paragraph',
        'https://cdn.jsdelivr.net/npm/@editorjs/paragraph@2.9.0/dist/paragraph.umd.js',
        ['editorjs'],
        '2.9.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-quote',
        'https://cdn.jsdelivr.net/npm/@editorjs/quote@2.5.0/dist/quote.umd.js',
        ['editorjs'],
        '2.5.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-checklist',
        'https://cdn.jsdelivr.net/npm/@editorjs/checklist@1.5.0/dist/checklist.umd.js',
        ['editorjs'],
        '1.5.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-delimiter',
        'https://cdn.jsdelivr.net/npm/@editorjs/delimiter@1.3.0/dist/delimiter.umd.js',
        ['editorjs'],
        '1.3.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-warning',
        'https://cdn.jsdelivr.net/npm/@editorjs/warning@1.3.0/dist/warning.umd.js',
        ['editorjs'],
        '1.3.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-code',
        'https://cdn.jsdelivr.net/npm/@editorjs/code@2.8.0/dist/code.umd.js',
        ['editorjs'],
        '2.8.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-marker',
        'https://cdn.jsdelivr.net/npm/@editorjs/marker@1.3.0/dist/marker.umd.js',
        ['editorjs'],
        '1.3.0',
        true
    );
    
    wp_enqueue_script(
        'editorjs-inline-code',
        'https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1.4.0/dist/inline-code.umd.js',
        ['editorjs'],
        '1.4.0',
        true
    );
    // This script is not needed - removed undefined $base_path variable
    
    // Enqueue curriculum builder CSS
    $css_path = plugin_dir_path(__FILE__) . 'assets/css/curriculum-builder.css';
    $css_url = $plugin_url . 'templates/curriculum-builder/assets/css/curriculum-builder.css';
    
    // Debug: Log the paths for troubleshooting
    error_log('CourScribe: CSS file path: ' . $css_path);
    error_log('CourScribe: CSS file URL: ' . $css_url);
    error_log('CourScribe: CSS file exists: ' . (file_exists($css_path) ? 'YES' : 'NO'));
    
    wp_enqueue_style(
        'courscribe-curriculum-builder-css',
        $css_url,
        ['font-awesome'],
        file_exists($css_path) ? filemtime($css_path) : '1.0.0'
    );
    
    // If the CSS file exists, also add it as inline styles as a backup
    if (file_exists($css_path)) {
        $css_content = file_get_contents($css_path);
        wp_add_inline_style('courscribe-curriculum-builder-css', $css_content);
    }
    
    // Enqueue Editor.js manager
    $editorjs_manager_path = plugin_dir_path(__FILE__) . 'assets/js/editorjs-manager.js';
    wp_enqueue_script(
        'courscribe-editorjs-manager',
        $plugin_url . 'templates/curriculum-builder/assets/js/editorjs-manager.js',
        ['jquery', 'editorjs', 'editorjs-header', 'editorjs-list', 'editorjs-paragraph', 'editorjs-quote', 'editorjs-checklist', 'editorjs-delimiter', 'editorjs-warning', 'editorjs-code', 'editorjs-marker', 'editorjs-inline-code'],
        file_exists($editorjs_manager_path) ? filemtime($editorjs_manager_path) : '1.0.0',
        true
    );
    
    
    // Enqueue curriculum builder JavaScript
    $curriculum_builder_js_path = plugin_dir_path(__FILE__) . 'assets/js/curriculum-builder.js';
    wp_enqueue_script(
        'courscribe-curriculum-builder-js',
        $plugin_url . 'templates/curriculum-builder/assets/js/curriculum-builder.js',
        ['jquery', 'sortablejs', 'courscribe-editorjs-manager'],
        file_exists($curriculum_builder_js_path) ? filemtime($curriculum_builder_js_path) : '1.0.0',
        true
    );
    
    // Localize script with configuration data
    wp_localize_script(
        'courscribe-curriculum-builder-js',
        'CourScribeCurriculumBuilder',
        [
            'curriculumId' => $curriculum_id,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_curriculum_builder_nonce'),
            'pluginUrl' => $plugin_url,
            'currentUser' => [
                'id' => get_current_user_id(),
                'name' => wp_get_current_user()->display_name,
                'roles' => wp_get_current_user()->roles
            ]
        ]
    );
}

/**
 * Main curriculum content builder shortcode function
 */
function courscribe_curriculum_content_builder_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts([
        'curriculum_id' => 0,
        'mode' => 'edit' // edit, preview, export
    ], $atts, 'courscribe_curriculum_content_builder');
    
    $curriculum_id = absint($atts['curriculum_id']);
    $mode = sanitize_text_field($atts['mode']);
    
    // Get curriculum ID from URL if not provided in shortcode
    if (!$curriculum_id) {
        $curriculum_id = isset($_GET['curriculum_id']) ? absint($_GET['curriculum_id']) : 0;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="ccb-error">Please log in to access the curriculum content builder.</div>';
    }
    
    // Get current user and permissions
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $is_studio_admin = in_array('studio_admin', $user_roles);
    $is_collaborator = in_array('collaborator', $user_roles);
    $is_client = in_array('client', $user_roles);
    
    // Validate curriculum exists and user has access
    if (!$curriculum_id) {
        return '<div class="ccb-error">No curriculum specified.</div>';
    }
    
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        return '<div class="ccb-error">Curriculum not found.</div>';
    }
    
    // Check permissions
    if (!$is_studio_admin && !$is_collaborator) {
        return '<div class="ccb-error">You do not have permission to edit this curriculum.</div>';
    }
    
    // Get studio ID for access validation
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $user_studio_id = 0;
    
    if ($is_collaborator) {
        $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
    } elseif ($is_studio_admin) {
        $admin_studios = get_posts([
            'post_type' => 'crscribe_studio',
            'post_status' => 'publish',
            'author' => $current_user->ID,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        $user_studio_id = !empty($admin_studios) ? $admin_studios[0] : 0;
    }
    
    // Validate studio access
    // if ($studio_id && $user_studio_id && $studio_id != $user_studio_id) {
    //     return '<div class="ccb-error">You do not have access to this curriculum\'s studio.</div>';
    // }
    
    // Enqueue assets
    courscribe_enqueue_curriculum_builder_assets($curriculum_id);
    
    // Get curriculum data
    $curriculum_title = $curriculum->post_title;
    $curriculum_goal = get_post_meta($curriculum_id, '_curriculum_goal', true) ?: 'No goal set';
    $curriculum_topic = get_post_meta($curriculum_id, '_curriculum_topic', true) ?: 'General';
    $curriculum_status = get_post_meta($curriculum_id, '_curriculum_status', true) ?: 'draft';
    
    // Get courses for this curriculum
    $courses = get_posts([
        'post_type' => 'crscribe_course',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_curriculum_id',
                'value' => $curriculum_id,
                'compare' => '='
            ]
        ],
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);
    
    // Start output buffering
    ob_start();
    ?>
    
    <!-- Fallback CSS in case external file doesn't load -->
    <style>
        .entry-title{
            display: none;
        }
        body:not(.et-tb) #main-content .container, body:not(.et-tb-has-header) #main-content .container {
            padding-top: 0 !important;
        }
        .container {
            width: 100% !important;
        }
    /* Basic fallback styles for curriculum builder */
    .ccb-curriculum-builder-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background: #231F20;
        color: #FFFFFF;
        min-height: 100vh;
    }
    
    .ccb-main-container {
        display: flex;
        min-height: 100vh;
    }
    
    .ccb-sidebar {
        width: 280px;
        background: #2a2a2b;
        border-right: 1px solid #404040;
        padding: 24px;
    }
    
    .ccb-content-area {
        flex: 1;
        padding: 32px;
        overflow-y: auto;
    }
    
    .ccb-logo {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 8px 0;
        background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .ccb-nav-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        margin-bottom: 4px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #B0B0B0;
    }
    
    .ccb-nav-item:hover {
        background: rgba(228, 178, 111, 0.1);
        color: #E4B26F;
    }
    
    .ccb-nav-item i {
        margin-right: 12px;
        width: 16px;
        text-align: center;
    }
    
    .ccb-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    
    .ccb-btn-primary {
        background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
        color: white;
    }
    
    .ccb-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(248, 146, 62, 0.3);
    }
    
    .ccb-page-title {
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px 0;
        color: #FFFFFF;
    }
    
    .ccb-page-subtitle {
        color: #B0B0B0;
        margin: 0 0 32px 0;
    }
    </style>
    <!-- Font Awesome 5 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css">
    <div class="ccb-curriculum-builder-container" data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
        
        <!-- Main Layout -->
        <div class="ccb-main-container">
            
            <!-- Sidebar Navigation -->
            <div class="ccb-sidebar" id="ccbSidebar">
                <?php include plugin_dir_path(__FILE__) . 'components/sidebar-navigation.php'; ?>
            </div>
            
            <!-- Content Area -->
            <div class="ccb-content-area">
                
                <!-- Header -->
                <div class="ccb-content-header">
                    <div class="ccb-header-left">
                        <h1 class="ccb-content-title"><?php echo esc_html($curriculum_title); ?></h1>
                        <div class="ccb-header-meta">
                            <span class="ccb-status-badge ccb-status-<?php echo esc_attr($curriculum_status); ?>">
                                <?php echo esc_html(ucfirst($curriculum_status)); ?>
                            </span>
                            <span class="ccb-last-edited">
                                Last edited <?php echo human_time_diff(get_post_modified_time('U', false, $curriculum), current_time('timestamp')); ?> ago
                            </span>
                        </div>
                    </div>
                    <div class="ccb-header-actions">
                        <button class="ccb-btn ccb-btn-secondary" id="ccbPreviewBtn">
                            <i class="fas fa-eye"></i>
                            Preview
                        </button>
                        <button class="ccb-btn ccb-btn-secondary" id="ccbTemplatesBtn">
                            <i class="fas fa-th-large"></i>
                            Templates
                        </button>
                        <button class="ccb-btn ccb-btn-secondary" id="ccbAIAssistantBtn">
                            <i class="fas fa-magic"></i>
                            AI Assistant
                        </button>
                        <button class="ccb-btn ccb-btn-primary" id="ccbSaveBtn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <button class="ccb-btn ccb-btn-primary" id="ccbExportBtn">
                            <i class="fas fa-file-pdf"></i>
                            Export PDF
                        </button>
                    </div>
                </div>
                
                <!-- Document Canvas -->
                <div class="ccb-document-canvas" id="ccbDocumentCanvas">
                    <?php include plugin_dir_path(__FILE__) . 'components/document-canvas.php'; ?>
                </div>
                
            </div>
            
        </div>
        
        <!-- AI Assistant Panel -->
        <div class="ccb-ai-panel" id="ccbAIPanel">
            <?php include plugin_dir_path(__FILE__) . 'components/ai-assistant-panel.php'; ?>
        </div>
        
        <!-- Templates Modal -->
        <div class="ccb-templates-modal" id="ccbTemplatesModal" style="display: none;">
            <?php include plugin_dir_path(__FILE__) . 'components/template-library.php'; ?>
        </div>
        
        <!-- Loading Overlay -->
        <div class="ccb-loading-overlay" id="ccbLoadingOverlay" style="display: none;">
            <div class="ccb-loading-content">
                <div class="ccb-loading-spinner"></div>
                <p class="ccb-loading-text">Processing...</p>
            </div>
        </div>
        
    </div>
    
    <!-- Initialize the curriculum builder -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof CourScribeCurriculumBuilderApp !== 'undefined') {
                window.ccbApp = new CourScribeCurriculumBuilderApp();
                window.ccbApp.init({
                    curriculumId: <?php echo $curriculum_id; ?>,
                    mode: '<?php echo esc_js($mode); ?>',
                    curriculum: {
                        id: <?php echo $curriculum_id; ?>,
                        title: '<?php echo esc_js($curriculum_title); ?>',
                        goal: '<?php echo esc_js($curriculum_goal); ?>',
                        topic: '<?php echo esc_js($curriculum_topic); ?>',
                        status: '<?php echo esc_js($curriculum_status); ?>'
                    },
                    courses: <?php echo json_encode(array_map(function($course) {
                        return [
                            'id' => $course->ID,
                            'title' => $course->post_title,
                            'content' => $course->post_content,
                            'goal' => get_post_meta($course->ID, '_class_goal', true),
                            'order' => $course->menu_order
                        ];
                    }, $courses)); ?>,
                    permissions: {
                        canEdit: <?php echo ($is_studio_admin || $is_collaborator) ? 'true' : 'false'; ?>,
                        canDelete: <?php echo $is_studio_admin ? 'true' : 'false'; ?>,
                        canExport: true,
                        canUseAI: <?php echo ($is_studio_admin || $is_collaborator) ? 'true' : 'false'; ?>
                    }
                });
            }
        });
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for saving individual curriculum fields
 */
add_action('wp_ajax_courscribe_save_curriculum_field', 'courscribe_save_curriculum_field_ajax');
function courscribe_save_curriculum_field_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $field_name = isset($_POST['field_name']) ? sanitize_key($_POST['field_name']) : '';
    $field_value = isset($_POST['field_value']) ? wp_kses_post($_POST['field_value']) : '';
    
    if (!$curriculum_id || !$field_name) {
        wp_send_json_error(['message' => 'Invalid data provided']);
    }
    
    // Validate user permissions
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    if (!in_array('studio_admin', $user_roles) && !in_array('collaborator', $user_roles)) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Verify curriculum exists and user can edit
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Curriculum not found']);
    }
    
    try {
        $success = false;
        
        // Handle different field types
        switch ($field_name) {
            case 'curriculum_title':
                $success = wp_update_post([
                    'ID' => $curriculum_id,
                    'post_title' => $field_value
                ]);
                break;
                
            case 'curriculum_goal':
                $success = update_post_meta($curriculum_id, '_curriculum_goal', $field_value);
                break;
                
            case 'curriculum_topic':
                $success = update_post_meta($curriculum_id, '_curriculum_topic', $field_value);
                break;
                
            case 'curriculum_notes':
                $success = update_post_meta($curriculum_id, '_curriculum_notes', $field_value);
                break;
                
            case 'curriculum_status':
                $success = update_post_meta($curriculum_id, '_curriculum_status', $field_value);
                break;
                
            default:
                // Handle other meta fields
                $success = update_post_meta($curriculum_id, '_' . $field_name, $field_value);
                break;
        }
        
        if ($success !== false) {
            // Update last modified
            update_post_meta($curriculum_id, '_curriculum_last_modified', current_time('mysql'));
            update_post_meta($curriculum_id, '_curriculum_modified_by', $current_user->ID);
            
            wp_send_json_success([
                'message' => 'Field saved successfully',
                'field_name' => $field_name,
                'timestamp' => current_time('mysql')
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to save field']);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * AJAX handler for saving curriculum content
 */
add_action('wp_ajax_courscribe_save_curriculum_content', 'courscribe_save_curriculum_content_ajax');
function courscribe_save_curriculum_content_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $content_data = isset($_POST['content_data']) ? wp_unslash($_POST['content_data']) : '';
    
    if (!$curriculum_id || !$content_data) {
        wp_send_json_error(['message' => 'Invalid data provided']);
    }
    
    // Validate user permissions
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    if (!in_array('studio_admin', $user_roles) && !in_array('collaborator', $user_roles)) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Save the content data
    $content_data = json_decode($content_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Invalid JSON data']);
    }
    
    // Update curriculum meta with content data
    update_post_meta($curriculum_id, '_curriculum_content_data', $content_data);
    update_post_meta($curriculum_id, '_curriculum_content_last_modified', current_time('mysql'));
    
    wp_send_json_success(['message' => 'Content saved successfully']);
}

/**
 * AJAX handler for generating content with AI
 */
add_action('wp_ajax_courscribe_generate_ai_content', 'courscribe_generate_ai_content_ajax');
function courscribe_generate_ai_content_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : '';
    $context = isset($_POST['context']) ? sanitize_textarea_field($_POST['context']) : '';
    
    if (!$curriculum_id || !$content_type) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }
    
    // Validate user permissions
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    if (!in_array('studio_admin', $user_roles) && !in_array('collaborator', $user_roles)) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // TODO: Integrate with existing Google Gemini AI system
    // For now, return placeholder content
    $generated_content = courscribe_generate_placeholder_content($content_type, $context);
    
    wp_send_json_success([
        'content' => $generated_content,
        'type' => $content_type
    ]);
}

/**
 * Generate placeholder content for AI functionality
 * TODO: Replace with actual AI integration
 */
function courscribe_generate_placeholder_content($type, $context) {
    $templates = [
        'module' => [
            'title' => 'Introduction to ' . $context,
            'description' => 'This module provides a comprehensive overview of ' . $context . ', covering fundamental concepts and practical applications.',
            'objectives' => [
                'Understand the basic principles of ' . $context,
                'Apply key concepts in real-world scenarios',
                'Analyze different approaches and methodologies'
            ]
        ],
        'lesson' => [
            'title' => 'Getting Started with ' . $context,
            'content' => 'In this lesson, we will explore the fundamentals of ' . $context . '. You will learn about key concepts, best practices, and practical applications.',
            'teaching_points' => [
                'Definition and core concepts',
                'Historical context and evolution',
                'Current trends and applications'
            ]
        ],
        'objective' => 'Students will be able to demonstrate understanding of ' . $context . ' principles and apply them effectively in practical scenarios.',
        'content_block' => 'This section covers important aspects of ' . $context . '. We will examine various approaches and provide practical examples to enhance your understanding.'
    ];
    
    return isset($templates[$type]) ? $templates[$type] : 'Generated content for ' . $context;
}

/**
 * AJAX handler for updating course order
 */
add_action('wp_ajax_courscribe_update_course_order', 'courscribe_update_course_order_ajax');
function courscribe_update_course_order_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $course_order = isset($_POST['course_order']) ? $_POST['course_order'] : [];
    
    if (!$curriculum_id || empty($course_order)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
    }
    
    // Validate user permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $success_count = 0;
    
    foreach ($course_order as $item) {
        $course_id = absint($item['id']);
        $order = absint($item['order']);
        
        if ($course_id && $order) {
            // Update the menu_order in wp_posts
            $result = wp_update_post([
                'ID' => $course_id,
                'menu_order' => $order
            ], true);
            
            if (!is_wp_error($result)) {
                $success_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        wp_send_json_success([
            'message' => "Updated order for {$success_count} courses",
            'updated_count' => $success_count
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update course order']);
    }
}

/**
 * AJAX handler for updating module order
 */
add_action('wp_ajax_courscribe_update_module_order', 'courscribe_update_module_order_ajax');
function courscribe_update_module_order_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $module_order = isset($_POST['module_order']) ? $_POST['module_order'] : [];
    
    if (!$course_id || empty($module_order)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
    }
    
    // Validate user permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $success_count = 0;
    
    foreach ($module_order as $item) {
        $module_id = absint($item['id']);
        $order = absint($item['order']);
        
        if ($module_id && $order) {
            // Update the menu_order in wp_posts
            $result = wp_update_post([
                'ID' => $module_id,
                'menu_order' => $order
            ], true);
            
            if (!is_wp_error($result)) {
                $success_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        wp_send_json_success([
            'message' => "Updated order for {$success_count} modules",
            'updated_count' => $success_count
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update module order']);
    }
}

/**
 * AJAX handler for updating objective order
 */
add_action('wp_ajax_courscribe_update_objective_order', 'courscribe_update_objective_order_ajax');
function courscribe_update_objective_order_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $objective_order = isset($_POST['objective_order']) ? $_POST['objective_order'] : [];
    
    if (!$course_id || empty($objective_order)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
    }
    
    // Validate user permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    // Get current objectives
    $objectives = get_post_meta($course_id, '_course_objectives', true);
    if (!is_array($objectives)) {
        $objectives = [];
    }
    
    // Reorder objectives array based on new order
    $reordered_objectives = [];
    
    foreach ($objective_order as $item) {
        $obj_index = absint($item['id']);
        $new_order = absint($item['order']) - 1; // Convert to 0-based index
        
        if (isset($objectives[$obj_index])) {
            $reordered_objectives[$new_order] = $objectives[$obj_index];
        }
    }
    
    // Fill any gaps and sort by key
    ksort($reordered_objectives);
    $final_objectives = array_values($reordered_objectives);
    
    // Update the course objectives meta
    $result = update_post_meta($course_id, '_course_objectives', $final_objectives);
    
    if ($result !== false) {
        wp_send_json_success([
            'message' => 'Objective order updated successfully',
            'objectives_count' => count($final_objectives)
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update objective order']);
    }
}

/**
 * AJAX handler for saving Editor.js content
 */
add_action('wp_ajax_courscribe_save_editor_content', 'courscribe_save_editor_content_ajax');
function courscribe_save_editor_content_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
    $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
    
    if (!$field || !$content) {
        wp_send_json_error(['message' => 'Invalid data provided']);
    }
    
    // Validate user permissions
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    if (!in_array('studio_admin', $user_roles) && !in_array('collaborator', $user_roles)) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Validate and parse JSON content
    $content_data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'Invalid JSON content']);
    }
    
    // Determine target post and meta field based on data
    $post_id = null;
    $meta_key = null;
    
    // Handle different content types
    if (isset($_POST['curriculumId'])) {
        $post_id = absint($_POST['curriculumId']);
        $meta_key = '_' . $field;
    } elseif (isset($_POST['courseId'])) {
        $post_id = absint($_POST['courseId']);
        if ($field === 'content') {
            // Update post content directly
            $result = wp_update_post([
                'ID' => $post_id,
                'post_content' => $content
            ]);
        } else {
            $meta_key = '_' . $field;
        }
    } elseif (isset($_POST['moduleId'])) {
        $post_id = absint($_POST['moduleId']);
        if ($field === 'content') {
            $result = wp_update_post([
                'ID' => $post_id,
                'post_content' => $content
            ]);
        } else {
            $meta_key = '_' . $field;
        }
    } elseif (isset($_POST['lessonId'])) {
        $post_id = absint($_POST['lessonId']);
        if ($field === 'content') {
            $result = wp_update_post([
                'ID' => $post_id,
                'post_content' => $content
            ]);
        } else {
            $meta_key = '_' . $field;
        }
    }
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }
    
    // Save to meta field if needed
    if ($meta_key) {
        $result = update_post_meta($post_id, $meta_key, $content);
    }
    
    // Also update last modified timestamp
    update_post_meta($post_id, '_content_last_modified', current_time('mysql'));
    update_post_meta($post_id, '_content_modified_by', $current_user->ID);
    
    if (isset($result) && $result !== false) {
        wp_send_json_success([
            'message' => 'Content saved successfully',
            'field' => $field,
            'timestamp' => current_time('mysql')
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to save content']);
    }
}

/**
 * AJAX handler for image upload (for Editor.js Image tool)
 */
add_action('wp_ajax_courscribe_upload_image', 'courscribe_upload_image_ajax');
function courscribe_upload_image_ajax() {
    check_ajax_referer('courscribe_curriculum_builder_nonce', 'nonce');
    
    // Validate user permissions
    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'No valid image uploaded']);
    }
    
    // Handle the upload
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_upload('image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => $attachment_id->get_error_message()]);
    }
    
    $attachment_url = wp_get_attachment_url($attachment_id);
    $attachment_meta = wp_get_attachment_metadata($attachment_id);
    
    wp_send_json_success([
        'success' => 1,
        'file' => [
            'url' => $attachment_url,
            'name' => basename($attachment_url),
            'size' => filesize(get_attached_file($attachment_id)),
            'title' => get_the_title($attachment_id),
            'width' => $attachment_meta['width'] ?? null,
            'height' => $attachment_meta['height'] ?? null
        ]
    ]);
}

?>