<?php
// Path: courscribe-dashboard/templates/modules-premium.php
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
        
        // Enqueue our modules premium script
        wp_enqueue_script(
            'courscribe-modules-premium',
            $plugin_url . 'assets/js/courscribe/modules/modules-premium.js',
            ['jquery', 'sortablejs'],
            filemtime(plugin_dir_path(__FILE__) . '../../../assets/js/courscribe/modules/modules-premium.js'),
            true
        );
        
        // Localize script with configuration data
        wp_localize_script(
            'courscribe-modules-premium',
            'CourScribeModulesConfig',
            [
                'courseId' => $course_id,
                'curriculumId' => $curriculum_id,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'moduleNonce' => wp_create_nonce('courscribe_module_nonce'),
                'pluginUrl' => $plugin_url
            ]
        );
        
        // Initialize the script
        wp_add_inline_script(
            'courscribe-modules-premium',
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
function courscribe_render_modules_premium_clean($args = []) {
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
    
    // Fetch course meta
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    
    // Include all the HTML content up to line 1229 from the original file
    include(plugin_dir_path(__FILE__) . 'modules-premium-content.php');
    
    echo '<!-- All JavaScript functionality has been moved to modules-premium.js and is enqueued properly -->';
}
?>