<?php
/**
 * Admin Setup Page Management for CourScribe
 * Creates and manages the secret admin setup page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create admin setup page if it doesn't exist
 */
function courscribe_create_admin_setup_page() {
    // Check if page already exists
    $existing_page = get_page_by_path('courscribe-admin-setup');
    
    if (!$existing_page) {
        $page_data = array(
            'post_title'    => 'CourScribe Admin Setup',
            'post_content'  => '[courscribe_admin_setup]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_slug'     => 'courscribe-admin-setup',
            'post_author'   => 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
            'meta_input'    => array(
                '_wp_page_template' => 'default'
            )
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Set page as private initially
            wp_update_post(array(
                'ID' => $page_id,
                'post_status' => 'private'
            ));
            
            update_option('courscribe_admin_setup_page_id', $page_id);
            error_log('CourScribe: Admin setup page created with ID: ' . $page_id);
            return $page_id;
        } else {
            error_log('CourScribe: Failed to create admin setup page');
            return false;
        }
    }
    
    return $existing_page->ID;
}

/**
 * Enable admin setup and generate secret URL
 */
function courscribe_enable_admin_setup_mode() {
    $page_id = courscribe_create_admin_setup_page();
    
    if ($page_id) {
        // Make page public temporarily
        wp_update_post(array(
            'ID' => $page_id,
            'post_status' => 'publish'
        ));
        
        // Generate secret key
        $secret = wp_generate_password(32, false);
        
        // Update options
        update_option('courscribe_admin_setup_allowed', true);
        update_option('courscribe_admin_setup_secret', $secret);
        update_option('courscribe_admin_setup_completed', false);
        
        // Generate the secret URL
        $setup_url = home_url('/courscribe-admin-setup?secret=' . $secret);
        
        error_log('CourScribe: Admin setup mode enabled. URL: ' . $setup_url);
        
        return $setup_url;
    }
    
    return false;
}

/**
 * Disable admin setup mode
 */
function courscribe_disable_admin_setup_mode() {
    $page_id = get_option('courscribe_admin_setup_page_id');
    
    if ($page_id) {
        // Make page private
        wp_update_post(array(
            'ID' => $page_id,
            'post_status' => 'private'
        ));
    }
    
    // Update options
    update_option('courscribe_admin_setup_allowed', false);
    update_option('courscribe_admin_setup_secret', '');
    
    error_log('CourScribe: Admin setup mode disabled');
}

/**
 * Check if admin setup is needed
 */
function courscribe_needs_admin_setup() {
    // Check if setup is already completed
    $setup_completed = get_option('courscribe_admin_setup_completed', false);
    if ($setup_completed) {
        return false;
    }
    
    // Check if there are any administrators
    $admins = get_users(array(
        'role' => 'administrator',
        'number' => 1
    ));
    
    // Check if there are any studio_admins
    $studio_admins = get_users(array(
        'role' => 'studio_admin',
        'number' => 1
    ));
    
    return (empty($admins) && empty($studio_admins));
}

/**
 * Admin notice for setup requirement
 */
add_action('admin_notices', 'courscribe_admin_setup_notice');

function courscribe_admin_setup_notice() {
    if (courscribe_needs_admin_setup() && current_user_can('manage_options')) {
        $setup_url = courscribe_enable_admin_setup_mode();
        
        if ($setup_url) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<h3>CourScribe Setup Required</h3>';
            echo '<p>CourScribe needs to be set up with an admin account.</p>';
            echo '<p><strong>Setup URL:</strong> <a href="' . esc_url($setup_url) . '" target="_blank">' . esc_html($setup_url) . '</a></p>';
            echo '<p><em>Please save this URL securely and share it only with the site owner.</em></p>';
            echo '</div>';
        }
    }
}

/**
 * Initialize admin setup on plugin activation
 */
register_activation_hook(COURScribe_DIR . 'courscribe.php', 'courscribe_init_admin_setup');

function courscribe_init_admin_setup() {
    if (courscribe_needs_admin_setup()) {
        courscribe_create_admin_setup_page();
        
        // Don't auto-enable setup mode, let admin notice handle it
        error_log('CourScribe: Plugin activated - admin setup may be required');
    }
}

/**
 * Cleanup on plugin deactivation
 */
register_deactivation_hook(COURScribe_DIR . 'courscribe.php', 'courscribe_cleanup_admin_setup');

function courscribe_cleanup_admin_setup() {
    courscribe_disable_admin_setup_mode();
    
    // Optionally remove the setup page
    $page_id = get_option('courscribe_admin_setup_page_id');
    if ($page_id) {
        wp_delete_post($page_id, true);
        delete_option('courscribe_admin_setup_page_id');
    }
    
    error_log('CourScribe: Admin setup cleaned up on plugin deactivation');
}

/**
 * AJAX handler to generate new setup URL
 */
// add_action('wp_ajax_courscribe_generate_setup_url', 'courscribe_ajax_generate_setup_url');

// function courscribe_ajax_generate_setup_url() {
//     // Verify user permissions
//     if (!current_user_can('manage_options')) {
//         wp_send_json_error(['message' => 'Insufficient permissions']);
//         return;
//     }
    
//     // Verify nonce
//     if (!wp_verify_nonce($_POST['nonce'], 'courscribe_admin_actions')) {
//         wp_send_json_error(['message' => 'Security check failed']);
//         return;
//     }
    
//     $setup_url = courscribe_enable_admin_setup_mode();
    
//     if ($setup_url) {
//         wp_send_json_success([
//             'message' => 'Setup URL generated successfully',
//             'setup_url' => $setup_url
//         ]);
//     } else {
//         wp_send_json_error(['message' => 'Failed to generate setup URL']);
//     }
// }