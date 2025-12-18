<?php
/*
Plugin Name: Courscribe
Plugin URI: https://www.cvmworldwide.com/
Description: A standalone plugin for curriculum development with studio management.
Version: 1.2.2
Author: CVM Worldwide
License: GPL2
*/

// Security: MVP bypass restrictions removed for production security
// define('COURSCRIBE_MVP_BYPASS_RESTRICTIONS', true); // DISABLED for security

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('COURScribe_DIR', plugin_dir_path(__FILE__));
define('COURScribe_URL', plugin_dir_url(__FILE__));
define('COURSCRIBE_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Create log tables and other database structures on plugin activation
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Log tables
    $tables = [
        'courscribe_studio_log' => "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            studio_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            changes LONGTEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX studio_id (studio_id),
            INDEX user_id (user_id)
        )",
        'courscribe_course_log' => "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            changes LONGTEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX course_id (course_id),
            INDEX user_id (user_id)
        )",
        'courscribe_curriculum_log' => "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curriculum_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            changes LONGTEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX curriculum_id (curriculum_id),
            INDEX user_id (user_id)
        )",
        'courscribe_module_log' => "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            module_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            changes LONGTEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX module_id (module_id),
            INDEX user_id (user_id)
        )",
        'courscribe_lesson_log' => "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lesson_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            data LONGTEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            ip_address VARCHAR(45) DEFAULT '',
            user_agent TEXT DEFAULT '',
            PRIMARY KEY (id),
            INDEX lesson_id (lesson_id),
            INDEX user_id (user_id)
        )",
        'courscribe_invitations' => "(
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(100) NOT NULL,
            invite_code VARCHAR(32) NOT NULL,
            studio_id BIGINT(20) UNSIGNED NOT NULL,
            invited_by BIGINT(20) UNSIGNED NOT NULL,
            role VARCHAR(50) DEFAULT 'collaborator',
            message TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY invite_code (invite_code),
            INDEX email (email),
            INDEX studio_id (studio_id),
            INDEX invited_by (invited_by),
            INDEX status (status),
            INDEX expires_at (expires_at)
        )",
    ];

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($tables as $table_name => $schema) {
        $full_table = $wpdb->prefix . $table_name;
        $sql = "CREATE TABLE $full_table $schema $charset_collate;";
        dbDelta($sql);
        error_log("Courscribe: Created/updated table $full_table");
    }



    // Create client invites table
    courscribe_create_client_invites_table();

    // Create waitlist table
    courscribe_create_waitlist_table();

    // Create auth pages
    create_courscribe_auth_pages();

    // Create landing page
    create_courscribe_landing_page();

    // Create courscribe-curriculum page
    create_courscribe_curriculum_page();

    // Flush rewrite rules
    flush_rewrite_rules();
});

// Create courscribe-curriculum page
function create_courscribe_curriculum_page() {
    $page_title = 'Courscribe Curriculum';
    $page_slug = 'courscribe-curriculum';
    $page_check = get_page_by_path($page_slug, OBJECT, 'page');

    if (!$page_check) {
        $page = array(
            'post_title'   => $page_title,
            'post_content' => '[courscribe_curriculum_manager]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
        );

        $page_id = wp_insert_post($page, true);
        if (is_wp_error($page_id)) {
            error_log('CourScribe: Failed to create courscribe-curriculum page: ' . $page_id->get_error_message());
        } else {
            error_log('CourScribe: Created courscribe-curriculum page with ID ' . $page_id);
        }
    }
}

require_once plugin_dir_path(__FILE__) . 'includes/class-courscribe-frontend.php';
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-course-actions.php';
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-module-actions.php';
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-lesson-actions.php';
require_once plugin_dir_path(__FILE__) . 'actions/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'actions/lessons-enhanced-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/single-curriculum-ajax.php';

// Premium Generation System
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-generation-premium-actions.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/generate-modules-premium.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/generate-lessons-premium.php';

require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Include necessary files
$includes = [
    'post-types.php',
    'user-roles.php',
    'studio-management.php',
    'studio-premium-ajax.php',
    'activation.php',
    'capabilities.php',
    'admin-menu.php',
    'shortcodes.php',
    'auth-handlers.php',
    'admin-setup-page.php',
    'restrictions.php',
    'woocommerce-integration.php',
    'courscribe-enqueue.php',
    'courscribe-review-system.php',
    'feedback-integration.php'
];
foreach ($includes as $file) {
    $path = COURScribe_DIR . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
        //error_log('Courscribe included: ' . $file);
    } else {
        error_log('Courscribe failed to include: ' . $file);
    }
}

// Schedule cron job for cleaning expired invites
add_action('wp', 'courscribe_schedule_cleanup'); 

require_once plugin_dir_path(__FILE__) . 'templates/template-parts/stepper.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/course-fields.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/modules.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/modules-premium.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/lessons.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/lessons-premium-enhanced.php';
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-lessons-premium-actions.php';
require_once plugin_dir_path(__FILE__) . 'actions/courscribe-generation-premium-actions.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/teaching-points.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/curriculum-preview.php';
require_once plugin_dir_path(__FILE__) . 'templates/curriculums/parts/courscribe-review-system.php';
require_once plugin_dir_path(__FILE__) . 'templates/dashboard/courscribe-expert-reviews-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'templates/curriculums/helpers/class-courscribe-assets.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/components/generation-wizard-base.php';
require_once plugin_dir_path(__FILE__) . 'templates/template-parts/components/content-preview-editor.php';

require_once plugin_dir_path(__FILE__) . 'templates/curriculum-builder/curriculum-content-builder.php';

// Include onboarding system
require_once plugin_dir_path(__FILE__) . 'actions/onboarding-handlers.php';
require_once plugin_dir_path(__FILE__) . 'templates/studio/shortcodes/courscribe_welcome_shortcode.php';



// Include tooltip class
require_once plugin_dir_path(__FILE__) . 'includes/class-courscribe-tooltips.php';
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-order-received.php';
require_once plugin_dir_path(__FILE__) . 'includes/affiliate-system.php';
require_once plugin_dir_path(__FILE__) . 'includes/affiliate-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-setup-generator.php';

// Initialize tooltips
function courscribe_tooltips_init() {
    CourScribe_Tooltips::get_instance();
}
add_action('plugins_loaded', 'courscribe_tooltips_init');

// Ensure administrators have studio_admin role on plugin load
add_action('plugins_loaded', 'courscribe_ensure_admin_studio_roles');
function courscribe_ensure_admin_studio_roles() {
    // Only run this once per plugin version to avoid unnecessary processing
    $version = '1.1.9';
    $last_sync_version = get_option('courscribe_admin_studio_role_sync_version', '');
    
    if ($last_sync_version !== $version) {
        if (function_exists('courscribe_ensure_administrators_have_studio_admin_role')) {
            courscribe_ensure_administrators_have_studio_admin_role();
            update_option('courscribe_admin_studio_role_sync_version', $version);
            error_log('CourScribe: Synced administrator roles with studio_admin for plugin version ' . $version);
        }
    }
}

function courscribe_schedule_cleanup() {
    if (!wp_next_scheduled('courscribe_cleanup_expired_invites')) {
        wp_schedule_event(time(), 'daily', 'courscribe_cleanup_expired_invites');
    }
}

add_action('courscribe_cleanup_expired_invites', 'courscribe_cleanup_expired_invites');
function courscribe_cleanup_expired_invites() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_invites';
    $result = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} WHERE expires_at <= %s",
        current_time('mysql')
    ));
    if ($result !== false) {
        error_log('Courscribe: Cleaned up ' . $result . ' expired invites');
    } else {
        error_log('Courscribe: Failed to clean up expired invites, Error: ' . $wpdb->last_error);
    }
}

// Register custom page templates
add_filter('theme_page_templates', 'register_courscribe_templates');
function register_courscribe_templates($templates) {
    $templates['template-landing-page.php'] = 'CourScribe Landing Page';
    $templates['template-courscribe-curriculum.php'] = 'CourScribe Curriculum Page';
    return $templates;
}

// Bypass Divi Theme Builder for crscribe_curriculum
add_filter('et_theme_builder_template_setting_filter', function($settings) {
    if (is_singular('crscribe_curriculum') || is_page_template('template-courscribe-curriculum.php')) {
        $settings['override'] = false;
        error_log('CourScribe: Bypassing Divi Theme Builder for crscribe_curriculum or courscribe-curriculum page');
    }
    return $settings;
}, 999);

// Debug main query
add_action('wp', function() {
    if (is_singular('crscribe_curriculum') || is_page_template('template-courscribe-curriculum.php')) {
        global $wp_query;
        error_log('CourScribe: Query vars for ' . (is_singular('crscribe_curriculum') ? 'curriculum ID ' . get_the_ID() : 'courscribe-curriculum page') . ': ' . print_r($wp_query->query_vars, true));
    }
});

// Load custom templates
add_filter('template_include', 'load_courscribe_templates', 999);
function load_courscribe_templates($template) {
    if (is_page_template('template-landing-page.php')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/landing/template-landing-page.php';
        if (file_exists($plugin_template)) {
            error_log('CourScribe: Loading landing page template: ' . $plugin_template);
            return $plugin_template;
        }
        error_log('CourScribe: Landing page template not found at ' . $plugin_template);
    }
    if (is_page_template('template-courscribe-curriculum.php')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/curriculums/template-courscribe-curriculum.php';
        if (file_exists($plugin_template)) {
            error_log('CourScribe: Loading courscribe-curriculum template: ' . $plugin_template);
            return $plugin_template;
        }
        error_log('CourScribe: Courscribe curriculum template not found at ' . $plugin_template);
    }
    if (is_singular('crscribe_curriculum')) {
        $post_status = get_post_status();
        error_log('CourScribe: Loading template for curriculum ID: ' . get_the_ID() . ', Status: ' . $post_status);
        
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/curriculums/archives/single-crscribe_curriculum.php';
        if (file_exists($plugin_template)) {
            error_log('CourScribe: Loading single-crscribe_curriculum.php for curriculum ID: ' . get_the_ID());
            return $plugin_template;
        }
        error_log('CourScribe: Single curriculum template not found at ' . $plugin_template);
        return plugin_dir_path(__FILE__) . 'templates/curriculums/archives/fallback-crscribe_curriculum.php';
    }
    return $template;
}

// Single curriculum template
add_filter('single_template', 'courscribe_load_single_curriculum_template', 999);
function courscribe_load_single_curriculum_template($single_template) {
    global $post;
    if ($post && $post->post_type === 'crscribe_curriculum') {
        error_log('CourScribe: single_template filter - Post ID: ' . $post->ID . ', Status: ' . $post->post_status);
        
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/curriculums/archives/single-crscribe_curriculum.php';
        if (file_exists($plugin_template)) {
            error_log('CourScribe: Forcing single-crscribe_curriculum.php for curriculum ID: ' . $post->ID);
            return $plugin_template;
        }
        error_log('CourScribe: Single curriculum template not found at ' . $plugin_template);
        return plugin_dir_path(__FILE__) . 'templates/curriculums/archives/fallback-crscribe_curriculum.php';
    }
    return $single_template;
}

// Handle curriculum access (published, private, and archived)
add_action('template_redirect', 'courscribe_handle_curriculum_access');
function courscribe_handle_curriculum_access() {
    // Check if we're on the curriculum page with ID or slug parameters
    if (is_page('courscribe-curriculum')) {
        global $wp_query;
        
        $curriculum_id = get_query_var('curriculum_id');
        $curriculum_slug = get_query_var('curriculum_slug');
        
        error_log('CourScribe: template_redirect - curriculum_id: ' . $curriculum_id . ', curriculum_slug: ' . $curriculum_slug);
        
        $curriculum = null;
        
        // Try to get curriculum by ID first (more reliable)
        if ($curriculum_id) {
            $curriculum = get_post($curriculum_id);
            if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
                error_log('CourScribe: Found curriculum by ID: ' . $curriculum_id);
            } else {
                $curriculum = null;
            }
        }
        
        // Fallback to slug-based lookup
        if (!$curriculum && $curriculum_slug) {
            $curriculum_query = get_posts([
                'post_type' => 'crscribe_curriculum',
                'post_status' => ['publish', 'private', 'trash'],
                'name' => $curriculum_slug,
                'posts_per_page' => 1
            ]);
            
            if (!empty($curriculum_query)) {
                $curriculum = $curriculum_query[0];
                error_log('CourScribe: Found curriculum by slug: ' . $curriculum_slug);
            }
        }
        
        if ($curriculum) {
            error_log('CourScribe: Loading curriculum - ID: ' . $curriculum->ID . ', Status: ' . $curriculum->post_status);
            
            // Set up the query to treat this as a single curriculum page
            $wp_query->is_single = true;
            $wp_query->is_singular = true;
            $wp_query->is_404 = false;
            $wp_query->queried_object = $curriculum;
            $wp_query->queried_object_id = $curriculum->ID;
            $wp_query->posts = [$curriculum];
            $wp_query->post = $curriculum;
            $wp_query->found_posts = 1;
            $wp_query->post_count = 1;
            
            // Set up the global post
            $GLOBALS['post'] = $curriculum;
            setup_postdata($curriculum);
            
            // Load the template
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/curriculums/archives/single-crscribe_curriculum.php';
            if (file_exists($plugin_template)) {
                include $plugin_template;
                exit;
            } else {
                error_log('CourScribe: Template file not found: ' . $plugin_template);
            }
        }
    }
}

// Handle curriculum builder route
add_action('template_redirect', 'courscribe_handle_curriculum_builder');
function courscribe_handle_curriculum_builder() {
    if (get_query_var('courscribe_curriculum_builder')) {
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url('/courscribe-curriculum-builder/')));
            exit;
        }
        
        // Basic permission check - user must be able to edit curriculums
        // if (!current_user_can('edit_posts')) {
        //     wp_die('You do not have permission to access the curriculum builder.');
        // }
        
        // Get curriculum ID from query parameters
        $curriculum_id = isset($_GET['curriculum_id']) ? absint($_GET['curriculum_id']) : 0;
        
        // Create a virtual page for the curriculum builder
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        
        // Load header
        get_header();
        
        // Display the curriculum builder shortcode
        echo do_shortcode('[courscribe_curriculum_content_builder curriculum_id="' . $curriculum_id . '"]');
        
        // Load footer  
        get_footer();
        exit;
    }
}

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function() {
    // Set option to flush rewrite rules on next page load
    update_option('courscribe_flush_rewrite_rules', true);
    error_log('CourScribe: Set option to flush rewrite rules on next page load');
});

// Create fallback template
register_activation_hook(__FILE__, function() {
    $fallback_template = plugin_dir_path(__FILE__) . 'templates/curriculums/archives/fallback-crscribe_curriculum.php';
    if (!file_exists($fallback_template)) {
        $content = '<?php
get_header();
?>
<div class="courscribe-curriculum-single">
    <h1><?php the_title(); ?></h1>
    <div class="content"><?php the_content(); ?></div>
    <p>Curriculum ID: <?php echo get_the_ID(); ?></p>
    <p><strong>Warning:</strong> Fallback template loaded because single-crscribe_curriculum.php is missing.</p>
</div>
<?php
get_footer();
';
        file_put_contents($fallback_template, $content);
        error_log('CourScribe: Created fallback template at ' . $fallback_template);
    }
});

// Create landing page
register_activation_hook(__FILE__, 'create_courscribe_landing_page');
function create_courscribe_landing_page() {
    $page_title = 'CourScribe Landing';
    $page_slug = sanitize_title($page_title);
    $page_check = get_page_by_path($page_slug, OBJECT, 'page');

    if (!$page_check) {
        $page = array(
            'post_title'   => $page_title,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
        );

        $page_id = wp_insert_post($page, true);
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'template-landing-page.php');
        } else {
            error_log('CourScribe: Failed to create landing page: ' . $page_id->get_error_message());
        }
    }
}

// Create welcome page for onboarding
register_activation_hook(__FILE__, 'create_courscribe_welcome_page');
function create_courscribe_welcome_page() {
    $page_title = 'Welcome to CourScribe';
    $page_slug = 'courscribe-welcome';
    $page_check = get_page_by_path($page_slug, OBJECT, 'page');

    if (!$page_check) {
        $page = array(
            'post_title'   => $page_title,
            'post_content' => '[courscribe_welcome]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
            'post_name'    => $page_slug,
        );

        $page_id = wp_insert_post($page, true);
        if (!is_wp_error($page_id)) {
            // Set the welcome page option for the onboarding system
            update_option('courscribe_welcome_page', $page_id);
            error_log('CourScribe: Created welcome page with ID ' . $page_id . ' and set option');
        } else {
            error_log('CourScribe: Failed to create welcome page: ' . $page_id->get_error_message());
        }
    } else {
        // Page exists, make sure the option is set
        update_option('courscribe_welcome_page', $page_check->ID);
        error_log('CourScribe: Welcome page already exists with ID ' . $page_check->ID . ', set option');
    }
}

// Create studio page
register_activation_hook(__FILE__, 'create_courscribe_studio_page');
function create_courscribe_studio_page() {
    $page_title = 'Studio';
    $page_slug = 'studio';
    $page_check = get_page_by_path($page_slug, OBJECT, 'page');

    if (!$page_check) {
        $page = array(
            'post_title'   => $page_title,
            'post_content' => '[courscribe_premium_studio]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
            'post_name'    => $page_slug,
        );

        $page_id = wp_insert_post($page, true);
        if (!is_wp_error($page_id)) {
            update_option('courscribe_studio_page', $page_id);
            error_log('CourScribe: Created studio page with ID ' . $page_id . ' and set option');
        } else {
            error_log('CourScribe: Failed to create studio page: ' . $page_id->get_error_message());
        }
    } else {
        update_option('courscribe_studio_page', $page_check->ID);
        error_log('CourScribe: Studio page already exists with ID ' . $page_check->ID . ', set option');
    }
}

// Create create studio page
register_activation_hook(__FILE__, 'create_courscribe_create_studio_page');
function create_courscribe_create_studio_page() {
    $page_title = 'Create Studio';
    $page_slug = 'create-studio';
    $page_check = get_page_by_path($page_slug, OBJECT, 'page');

    if (!$page_check) {
        $page = array(
            'post_title'   => $page_title,
            'post_content' => '[courscribe_create_studio]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
            'post_name'    => $page_slug,
        );

        $page_id = wp_insert_post($page, true);
        if (!is_wp_error($page_id)) {
            update_option('courscribe_create_studio_page', $page_id);
            error_log('CourScribe: Created create studio page with ID ' . $page_id . ' and set option');
        } else {
            error_log('CourScribe: Failed to create create studio page: ' . $page_id->get_error_message());
        }
    } else {
        update_option('courscribe_create_studio_page', $page_check->ID);
        error_log('CourScribe: Create studio page already exists with ID ' . $page_check->ID . ', set option');
    }
}

// Create select tribe page
register_activation_hook(__FILE__, 'create_courscribe_select_tribe_page');
function create_courscribe_select_tribe_page() {
    $page_title = 'Select Tribe';
    $page_slug = 'select-tribe';
    $page_check = get_page_by_path($page_slug, OBJECT, 'page');

    if (!$page_check) {
        $page = array(
            'post_title'   => $page_title,
            'post_content' => '[courscribe_select_tribe]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
            'post_name'    => $page_slug,
        );

        $page_id = wp_insert_post($page, true);
        if (!is_wp_error($page_id)) {
            update_option('courscribe_select_tribe_page', $page_id);
            error_log('CourScribe: Created select tribe page with ID ' . $page_id . ' and set option');
        } else {
            error_log('CourScribe: Failed to create select tribe page: ' . $page_id->get_error_message());
        }
    } else {
        update_option('courscribe_select_tribe_page', $page_check->ID);
        error_log('CourScribe: Select tribe page already exists with ID ' . $page_check->ID . ', set option');
    }
}

function load_landing_page_assets() {
    // Check if it's the landing page by template or by page slug
    $is_landing_page = is_page_template('template-landing-page.php') || 
                      is_page('courscribe-landing') || 
                      (is_front_page() && get_option('page_on_front') && get_post_meta(get_option('page_on_front'), '_wp_page_template', true) === 'template-landing-page.php');
    
    if ($is_landing_page) {
        // Load premium landing page CSS
        wp_enqueue_style('courscribe-landing-premium', plugin_dir_url(__FILE__) . 'assets/css/landing-premium.css', [], '2.0.0', 'all');
        // Load Font Awesome for icons
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0', 'all');
        // Load Google Fonts
        wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], null, 'all');
        
        // Debug logging
        error_log('CourScribe: Landing page assets loaded');
    }
    
    // Keep curriculum page assets separate
    if (is_page_template('template-courscribe-curriculum.php')) {
        wp_enqueue_style('landing-page-style', plugin_dir_url(__FILE__) . 'templates/landing/assets/css/xE8xzOJEdZEm.css', [], '1.0', 'all');
        wp_enqueue_style('landing-page-style2', plugin_dir_url(__FILE__) . 'templates/landing/assets/css/9Gwwvk3jATOl.css', [], '1.0', 'all');
        wp_enqueue_script('landing-page-script', plugin_dir_url(__FILE__) . 'templates/landing/assets/js/c5CLwfzfzMgm.js', ['jquery'], '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'load_landing_page_assets');

// Enqueue enhanced lessons assets
function courscribe_enqueue_lessons_assets() {
    // Only load on admin pages or pages that contain lessons
    if (is_admin() || 
        is_singular('crscribe_lesson') || 
        is_page('courscribe-curriculum') ||
        (function_exists('has_shortcode') && 
         (has_shortcode(get_post()->post_content ?? '', 'courscribe_curriculum_manager') ||
          has_shortcode(get_post()->post_content ?? '', 'courscribe_render_lessons')))) {
        
        // Enqueue CSS
        wp_enqueue_style(
            'courscribe-lessons-enhanced',
            plugin_dir_url(__FILE__) . 'assets/css/lessons-enhanced.css',
            ['courscribe-admin-dashboard'], // Depends on main dashboard styles
            '1.0.0',
            'all'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'courscribe-lessons-enhanced',
            plugin_dir_url(__FILE__) . 'assets/js/lessons-enhanced.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('courscribe-lessons-enhanced', 'CourScribeConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'lessonNonce' => wp_create_nonce('courscribe_lesson_nonce'),
            'objectiveNonce' => wp_create_nonce('courscribe_objective_nonce'),
            'activityNonce' => wp_create_nonce('courscribe_activity_nonce'),
            'currentUserId' => get_current_user_id(),
            'isAdmin' => current_user_can('administrator'),
            'pluginUrl' => plugin_dir_url(__FILE__)
        ]);
        
        error_log('CourScribe: Enhanced lessons assets enqueued');
    }
}
add_action('wp_enqueue_scripts', 'courscribe_enqueue_lessons_assets');
add_action('admin_enqueue_scripts', 'courscribe_enqueue_lessons_assets');

// Rewrite rules for curriculum
function courscribe_add_rewrite_rules() {
    $curriculum_page = get_page_by_path('courscribe-curriculum');
    if ($curriculum_page) {
        // Add ID-based routing for more reliable curriculum access
        add_rewrite_rule(
            '^courscribe-curriculum/([0-9]+)/?$',
            'index.php?pagename=courscribe-curriculum&curriculum_id=$matches[1]',
            'top'
        );
        // Keep slug-based routing as fallback for existing URLs
        add_rewrite_rule(
            '^courscribe-curriculum/([^/]+)/?$',
            'index.php?pagename=courscribe-curriculum&curriculum_slug=$matches[1]',
            'top'
        );
    }
    
    // Add curriculum builder rewrite rule
    add_rewrite_rule(
        '^courscribe-curriculum-builder/?$',
        'index.php?courscribe_curriculum_builder=1',
        'top'
    );
    
    add_rewrite_tag('%curriculum_id%', '([0-9]+)');
    add_rewrite_tag('%curriculum_slug%', '([^&]+)');
    add_rewrite_tag('%courscribe_curriculum_builder%', '([^&]+)');
}
add_action('init', 'courscribe_add_rewrite_rules');

// Ensure permalinks
function courscribe_fix_permalink($permalink, $post, $leavename) {
    if ($post->post_type === 'crscribe_curriculum') {
        $curriculum_page = get_page_by_path('courscribe-curriculum');
        if ($curriculum_page) {
            // Use ID-based URLs for reliability
            $permalink = get_permalink($curriculum_page->ID) . $post->ID . '/';
            error_log('CourScribe: Fixed permalink for curriculum ID ' . $post->ID . ' to ' . $permalink);
        }
    }
    return $permalink;
}
add_filter('post_type_link', 'courscribe_fix_permalink', 10, 3);

function courscribe_enqueue_pdfme_scripts() {
    wp_enqueue_script('pdfme-ui', 'https://cdn.jsdelivr.net/npm/@nightvisi0n/pdfme-generator@1.0.14-12/dist/index.min.js', [], 'latest', true);
}
add_action('admin_enqueue_scripts', 'courscribe_enqueue_pdfme_scripts');

function courscribe_add_slide_decks_meta() {
    if (get_option('courscribe_slide_decks_initialized')) {
        return;
    }

    $args = array(
        'post_type'      => 'crscribe_course',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    );

    $course_ids = get_posts($args);
    foreach ($course_ids as $course_id) {
        $slide_decks = get_post_meta($course_id, '_courscribe_slide_decks', true);
        if (!$slide_decks || !is_array($slide_decks)) {
            update_post_meta($course_id, '_courscribe_slide_decks', []);
            error_log("Added _courscribe_slide_decks to course ID: $course_id");
        }
    }
    update_option('courscribe_slide_decks_initialized', true);
}
add_action('admin_init', 'courscribe_add_slide_decks_meta');

function courscribe_create_client_invites_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_client_invites';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL,
        name VARCHAR(100) NOT NULL,
        invite_code VARCHAR(12) NOT NULL,
        curriculum_id BIGINT(20) NOT NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        created_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY invite_code (invite_code),
        KEY email (email),
        KEY curriculum_id (curriculum_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function create_courscribe_auth_pages() {
    $auth_pages = array(
        'login' => array(
            'title' => 'Courscribe Sign In',
            'slug' => 'courscribe-sign-in',
            'shortcode' => '[courscribe_signin]'
        ),
        'register' => array(
            'title' => 'Courscribe Register',
            'slug' => 'courscribe-register',
            'shortcode' => '[courscribe_register]'
        ),
        'forgot_password' => array(
            'title' => 'Courscribe Forgot Password',
            'slug' => 'courscribe-forgot-password',
            'shortcode' => '[courscribe_forgot_password]'
        ),
        'set_new_password' => array(
            'title' => 'Courscribe Set New Password',
            'slug' => 'courscribe-set-new-password',
            'shortcode' => '[courscribe_set_new_password]'
        ),
    );

    $page_ids = array();
    foreach ($auth_pages as $key => $page_data) {
        $page = get_page_by_path($page_data['slug'], OBJECT, 'page');
        if (!$page) {
            $page_id = wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_content' => $page_data['shortcode'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id() ? get_current_user_id() : 1,
            ));
            if (!is_wp_error($page_id)) {
                $page_ids[$key] = $page_id;
            } else {
                error_log('CourScribe: Failed to create ' . $key . ' page: ' . $page_id->get_error_message());
            }
        } else {
            $page_ids[$key] = $page->ID;
        }
    }
    update_option('courscribe_auth_page_ids', $page_ids);
}

function courscribe_get_auth_page_url($key) {
    $page_ids = get_option('courscribe_auth_page_ids', array());
    if (isset($page_ids[$key])) {
        return get_permalink($page_ids[$key]);
    }
    return '';
}

function courscribe_login_url($login_url, $redirect) {
    $custom_login_url = courscribe_get_auth_page_url('login');
    if ($custom_login_url) {
        if ($redirect) {
            $custom_login_url = add_query_arg('redirect_to', urlencode($redirect), $custom_login_url);
        }
        return $custom_login_url;
    }
    return $login_url;
}
add_filter('login_url', 'courscribe_login_url', 10, 2);

function courscribe_register_url($register_url) {
    $custom_register_url = courscribe_get_auth_page_url('register');
    if ($custom_register_url) {
        return $custom_register_url;
    }
    return $register_url;
}
add_filter('register_url', 'courscribe_register_url');

function courscribe_add_auth_menu_items($items, $args) {
    if (is_admin() || wp_doing_ajax() || defined('REST_REQUEST')) {
        return $items;
    }
    if (!empty($args->theme_location)) {
        // Add logic if needed
    }
    if (is_user_logged_in()) {
        $logout_url = wp_logout_url();
        $items .= '<li class="menu-item menu-item-logout"><a href="' . esc_url($logout_url) . '"><i class="fa fa-sign-out"></i> Logout</a></li>';
    } else {
        $login_url = courscribe_get_auth_page_url('login');
        $register_url = courscribe_get_auth_page_url('register');
        if ($login_url) {
            $items .= '<li class="menu-item menu-item-login"><a href="' . esc_url($login_url) . '"><i class="fa fa-sign-in"></i> Login</a></li>';
        }
        if ($register_url) {
            $items .= '<li class="menu-item menu-item-register"><a href="' . esc_url($register_url) . '"><i class="fa fa-user-plus"></i> Register</a></li>';
        }
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'courscribe_add_auth_menu_items', 20, 2);

function courscribe_enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
}
add_action('wp_enqueue_scripts', 'courscribe_enqueue_font_awesome');

class My_Custom_Login_Menu_Button {
    public function __construct() {
        add_filter('wp_nav_menu_items', [$this, 'add_custom_login_button_to_menu'], 20, 2);
    }

    public function add_custom_login_button_to_menu($items, $args) {
        $target_locations = apply_filters('my_custom_login_button_target_locations', ['primary', 'primary_navigation']);
        if (isset($args->theme_location) && in_array($args->theme_location, $target_locations)) {
            $button_html = '';
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $profile_url = get_edit_user_link();
                $logout_url = wp_logout_url(home_url());
                $profile_button_text = esc_html($current_user->display_name);
                $profile_aria_label = sprintf(esc_attr__(' % s Profile Button', 'your-text-domain'), $current_user->display_name);

                $button_html .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-profile-custom">';
                $button_html .= '  <a href="' . esc_url($profile_url) . '">';
                $button_html .= '    <div aria-label="' . $profile_aria_label . '" tabindex="0" role="button" class="user-profile">';
                $button_html .= '      <div class="user-profile-inner">';
                $button_html .= '        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">';
                $button_html .= '          <g data-name="Layer 2" id="Layer_2_Profile">';
                $button_html .= '            <path d="m15.626 11.769a6 6 0 1 0 -7.252 0 9.008 9.008 0 0 0 -5.374 8.231 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 9.008 9.008 0 0 0 -5.374-8.231zm-7.626-4.769a4 4 0 1 1 4 4 4 4 0 0 1 -4-4zm10 14h-12a1 1 0 0 1 -1-1 7 7 0 0 1 14 0 1 1 0 0 1 -1 1z"></path>';
                $button_html .= '          </g>';
                $button_html .= '        </svg>';
                $button_html .= '        <p>' . $profile_button_text . '</p>';
                $button_html .= '      </div>';
                $button_html .= '    </div>';
                $button_html .= '  </a>';
                $button_html .= '</li>';
                $button_html .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-logout-custom">';
                $button_html .= '  <a href="' . esc_url($logout_url) . '">' . esc_html__('Log Out', 'your-text-domain') . '</a>';
                $button_html .= '</li>';
            } else {
                $login_url = wp_login_url(get_permalink() ? get_permalink() : home_url());
                $register_url = wp_registration_url();
                $button_html .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-login-custom">';
                $button_html .= '  <a href="' . esc_url($login_url) . '">';
                $button_html .= '    <div aria-label="' . esc_attr__('User Login Button', 'your-text-domain') . '" tabindex="0" role="button" class="user-profile">';
                $button_html .= '      <div class="user-profile-inner">';
                $button_html .= '        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">';
                $button_html .= '          <g data-name="Layer 2" id="Layer_2_Login">';
                $button_html .= '            <path d="m15.626 11.769a6 6 0 1 0 -7.252 0 9.008 9.008 0 0 0 -5.374 8.231 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 9.008 9.008 0 0 0 -5.374-8.231zm-7.626-4.769a4 4 0 1 1 4 4 4 4 0 0 1 -4-4zm10 14h-12a1 1 0 0 1 -1-1 7 7 0 0 1 14 0 1 1 0 0 1 -1 1z"></path>';
                $button_html .= '          </g>';
                $button_html .= '        </svg>';
                $button_html .= '        <p>' . esc_html__('Log In', 'your-text-domain') . '</p>';
                $button_html .= '      </div>';
                $button_html .= '    </div>';
                $button_html .= '  </a>';
                $button_html .= '</li>';
                if (get_option('users_can_register')) {
                    $button_html .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-register-custom">';
                    $button_html .= '  <a href="' . esc_url($register_url) . '">' . esc_html__('Register', 'your-text-domain') . '</a>';
                    $button_html .= '</li>';
                }
            }
            $items .= $button_html;
        }
        return $items;
    }
}
new My_Custom_Login_Menu_Button();

function courscribe_retro_tv_error($message = 'Oops! Something went wrong.') {
    ob_start();
    $site_url = home_url();
    ?>
    <style>
        #main-content { background-color: #231F20; }
        .entry-title { display: none; }
        .laptop { transform: scale(0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .screen {
            border-radius: 20px; box-shadow: inset 0 0 0 2px #c8cacb, inset 0 0 0 10px #000; height: 318px; width: 518px;
            margin: 0 auto; padding: 9px 9px 23px 9px; position: relative; display: flex; align-items: center; justify-content: center;
            background-image: linear-gradient(15deg, #3f51b1 0%, #5a55ae 13%, #7b5fac 25%, #8f6aae 38%, #a86aa4 50%, #cc6b8e 62%, #f18271 75%, #f3a469 87%, #f7c978 100%);
            transform-style: preserve-3d; transform: perspective(1900px) rotateX(-88.5deg); transform-origin: 50% 100%;
            animation: open 4s infinite alternate;
        }
        @keyframes open {
            0% { transform: perspective(1900px) rotateX(-88.5deg); }
            100% { transform: perspective(1000px) rotateX(0deg); }
        }
        .screen::before {
            content: ""; width: 518px; height: 12px; position: absolute; background: linear-gradient(#979899, transparent);
            top: -3px; transform: rotateX(90deg); border-radius: 5px 5px;
        }
        .text {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            color: #fff; letter-spacing: 1px; text-shadow: 0 0 5px #fff;
        }
        .header {
            width: 100px; height: 12px; position: absolute; background-color: #000; top: 10px; left: 50%;
            transform: translate(-50%, -0%); border-radius: 0 0 6px 6px;
        }
        .screen::after {
            background: linear-gradient(to bottom, #272727, #0d0d0d); border-radius: 0 0 20px 20px; bottom: 2px;
            content: ""; height: 24px; left: 2px; position: absolute; width: 514px;
        }
        .keyboard {
            background: radial-gradient(circle at center, #e2e3e4 85%, #a9abac 100%); border: solid #a0a3a7;
            border-radius: 2px 2px 12px 12px; border-width: 1px 2px 0 2px; box-shadow: inset 0 -2px 8px 0 #6c7074;
            height: 24px; margin-top: -10px; position: relative; width: 620px; z-index: 9;
        }
        .keyboard::after {
            background: #e2e3e4; border-radius: 0 0 10px 10px; box-shadow: inset 0 0 4px 2px #babdbf;
            content: ""; height: 10px; left: 50%; margin-left: -60px; position: absolute; top: 0; width: 120px;
        }
        .keyboard::before {
            background: 0 0; border-radius: 0 0 3px 3px; bottom: -2px; box-shadow: -270px 0 #272727, 250px 0 #272727;
            content: ""; height: 2px; left: 50%; margin-left: -10px; position: absolute; width: 40px;
        }
    </style>
    <div class="laptop">
        <div class="screen">
            <div class="header"></div>
            <div class="v-col">
                <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
                <div class="text"><?php echo esc_html($message); ?></div>
            </div>
        </div>
        <div class="keyboard"></div>
    </div>
    <?php
    return ob_get_clean();
}

function courscribe_create_waitlist_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_waitlist';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function courscribe_join_waitlist() {
    check_ajax_referer('courscribe_waitlist', 'nonce');
    $email = sanitize_email($_POST['email']);
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_waitlist';
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE email = %s", $email));

    if ($exists) {
        wp_send_json_error(['message' => 'This email is already on the waitlist']);
    }

    $result = $wpdb->insert($table_name, ['email' => $email], ['%s']);
    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to join the waitlist. Please try again.']);
    }

    wp_send_json_success();
}
add_action('wp_ajax_courscribe_join_waitlist', 'courscribe_join_waitlist');
add_action('wp_ajax_nopriv_courscribe_join_waitlist', 'courscribe_join_waitlist');

add_action('wp_ajax_courscribe_check_duplicate_curriculum', 'courscribe_check_duplicate_curriculum_ajax');

function courscribe_check_duplicate_curriculum_ajax() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    $title = sanitize_text_field($_POST['title'] ?? '');
    $studio_id = absint($_POST['studio_id'] ?? 0);
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);

    $query_args = array(
        'post_type' => 'crscribe_curriculum',
        'post_status' => ['publish', 'draft', 'pending', 'future'],
        'title' => $title,
        'meta_query' => array(
            array(
                'key' => '_studio_id',
                'value' => $studio_id,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    );

    if ($curriculum_id) {
        $query_args['post__not_in'] = [$curriculum_id];
    }

    $existing_curriculum = get_posts($query_args);
    wp_send_json_success(['exists' => !empty($existing_curriculum)]);
}

?>