<?php
//includes/user-roles.php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add custom roles on plugin activation
function courscribe_add_custom_roles() {
    $tier = get_option('courscribe_tier', 'basics');
    error_log('Courscribe: Adding custom roles. Current tier: ' . $tier);

    // Define capabilities
    $studio_caps = [
        'edit_crscribe_studio' => true,
        'read_crscribe_studio' => true,
        'delete_crscribe_studio' => true,
        'edit_crscribe_studios' => true,
        'edit_others_crscribe_studios' => true,
        'publish_crscribe_studios' => true,
        'read_private_crscribe_studios' => true,
        'delete_crscribe_studios' => true,
        'delete_private_crscribe_studios' => true,
        'delete_published_crscribe_studios' => true,
        'delete_others_crscribe_studios' => true,
        'edit_private_crscribe_studios' => true,
        'edit_published_crscribe_studios' => true,
        'create_crscribe_studios' => true,
    ];

    $curriculum_caps = [
        'edit_crscribe_curriculum' => true,
        'read_crscribe_curriculum' => true,
        'delete_crscribe_curriculum' => true,
        'edit_crscribe_curriculums' => true,
        'edit_others_crscribe_curriculums' => true,
        'publish_crscribe_curriculums' => true,
        'read_private_crscribe_curriculums' => true,
        'delete_crscribe_curriculums' => true,
        'delete_private_crscribe_curriculums' => true,
        'delete_published_crscribe_curriculums' => true,
        'delete_others_crscribe_curriculums' => true,
        'edit_private_crscribe_curriculums' => true,
        'edit_published_crscribe_curriculums' => true,
        'create_crscribe_curriculums' => true,
    ];

    $course_caps = [
        'edit_crscribe_course' => true,
        'read_crscribe_course' => true,
        'delete_crscribe_course' => true,
        'edit_crscribe_courses' => true,
        'edit_others_crscribe_courses' => true,
        'publish_crscribe_courses' => true,
        'read_private_crscribe_courses' => true,
        'delete_crscribe_courses' => true,
        'delete_private_crscribe_courses' => true,
        'delete_published_crscribe_courses' => true,
        'delete_others_crscribe_courses' => true,
        'edit_private_crscribe_courses' => true,
        'edit_published_crscribe_courses' => true,
        'create_crscribe_courses' => true,
    ];

    // Add Studio Admin role (all tiers)
    $studio_admin_caps = array_merge(
        $studio_caps,
        $curriculum_caps,
        $course_caps,
        [
            'read' => true,
            'access_courscribe' => true,
        ]
    );
    $result = add_role('studio_admin', 'Studio Admin', $studio_admin_caps);
    error_log('Courscribe: Studio Admin role creation: ' . ($result ? 'Success' : 'Failed or exists'));

    // Add Collaborator role (Plus and Pro tiers only)
    if (in_array($tier, ['plus', 'pro'])) {
        $collaborator_caps = [
            'read' => true,
            'access_courscribe' => true,
            'edit_crscribe_curriculum' => true,
            'edit_crscribe_curriculums' => true,
            'publish_crscribe_curriculums' => true,
            'create_crscribe_curriculums' => true,
            'read_crscribe_curriculum' => true,
            'read_crscribe_curriculums' => true,
            'edit_crscribe_course' => true,
            'edit_crscribe_courses' => true,
            'publish_crscribe_courses' => true,
            'create_crscribe_courses' => true,
            'read_crscribe_course' => true,
            'read_crscribe_courses' => true,
        ];
        $result = add_role('collaborator', 'Collaborator', $collaborator_caps);
        error_log('Courscribe: Collaborator role creation: ' . ($result ? 'Success' : 'Failed or exists'));
    }

    // Add Client role (always for testing, revert to 'pro' condition in production)
    $client_caps = [
        'read' => true,
        'access_courscribe' => true,
        'read_crscribe_curriculum' => true,
        'read_crscribe_curriculums' => true,
        'read_crscribe_course' => true,
        'read_crscribe_courses' => true,
        'edit_comments' => true,
    ];
    $result = add_role('client', 'Client', $client_caps);
    error_log('Courscribe: Client role creation: ' . ($result ? 'Success' : 'Failed or exists'));

    // Ensure admin has all capabilities
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $all_caps = array_unique(array_merge(array_keys($studio_caps), array_keys($curriculum_caps), array_keys($course_caps), ['access_courscribe']));
        foreach ($all_caps as $cap) {
            $admin_role->add_cap($cap);
        }
        error_log('Courscribe: Admin role updated with all Courscribe capabilities');
    } else {
        error_log('Courscribe: Administrator role not found');
    }

    // Add studio_admin role to all existing administrators so they can manage their own studios
    courscribe_add_studio_admin_role_to_administrators();
}

/**
 * Add studio_admin role to all administrators so they can manage their own studios
 */
function courscribe_add_studio_admin_role_to_administrators() {
    $administrators = get_users(['role' => 'administrator']);
    $count = 0;
    
    foreach ($administrators as $admin) {
        $user = new WP_User($admin->ID);
        
        // Check if user already has studio_admin role
        if (!in_array('studio_admin', $user->roles)) {
            $user->add_role('studio_admin');
            $count++;
            error_log('Courscribe: Added studio_admin role to administrator: ' . $admin->user_login . ' (ID: ' . $admin->ID . ')');
        }
    }
    
    if ($count > 0) {
        error_log('Courscribe: Added studio_admin role to ' . $count . ' administrator(s)');
    } else {
        error_log('Courscribe: All administrators already have studio_admin role');
    }
}

/**
 * Hook to automatically add studio_admin role when a user is promoted to administrator
 */
add_action('set_user_role', 'courscribe_auto_add_studio_admin_to_new_administrators', 10, 3);
function courscribe_auto_add_studio_admin_to_new_administrators($user_id, $role, $old_roles) {
    // If user was promoted to administrator, add studio_admin role
    if ($role === 'administrator') {
        $user = new WP_User($user_id);
        if (!in_array('studio_admin', $user->roles)) {
            $user->add_role('studio_admin');
            error_log('Courscribe: Auto-added studio_admin role to new administrator: ' . $user->user_login . ' (ID: ' . $user_id . ')');
        }
    }
}

/**
 * Hook to automatically add studio_admin role when administrator role is added to a user
 */
add_action('add_user_role', 'courscribe_auto_add_studio_admin_on_add_role', 10, 2);
function courscribe_auto_add_studio_admin_on_add_role($user_id, $role) {
    // If administrator role was added, also add studio_admin role
    if ($role === 'administrator') {
        $user = new WP_User($user_id);
        if (!in_array('studio_admin', $user->roles)) {
            $user->add_role('studio_admin');
            error_log('Courscribe: Auto-added studio_admin role when administrator role was added to user: ' . $user->user_login . ' (ID: ' . $user_id . ')');
        }
    }
}

/**
 * Utility function to manually ensure all administrators have studio_admin role
 * Can be called from admin interface or during updates
 */
function courscribe_ensure_administrators_have_studio_admin_role() {
    // First make sure the studio_admin role exists
    courscribe_add_custom_roles();
    
    // Then add it to all administrators
    courscribe_add_studio_admin_role_to_administrators();
    
    return true;
}

/**
 * Admin action to manually sync administrator roles
 */
add_action('wp_ajax_courscribe_sync_admin_roles', 'courscribe_ajax_sync_admin_roles');
function courscribe_ajax_sync_admin_roles() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        wp_die();
    }
    
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_sync_admin_roles')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }
    
    $result = courscribe_ensure_administrators_have_studio_admin_role();
    
    if ($result) {
        wp_send_json_success(['message' => 'Successfully synced administrator roles with studio_admin']);
    } else {
        wp_send_json_error(['message' => 'Failed to sync administrator roles']);
    }
    
    wp_die();
}

// Remove roles on plugin activation for clean setup
function courscribe_clean_roles_on_activation() {
    remove_role('studio_admin');
    remove_role('collaborator');
    remove_role('client');
    error_log('Courscribe: Removed existing roles for clean setup');
    courscribe_add_custom_roles();
}

// Register activation hook
register_activation_hook(__FILE__, 'courscribe_clean_roles_on_activation');

// Handle collaborator registration via invite
add_action('user_register', 'courscribe_handle_collaborator_registration');
function courscribe_handle_collaborator_registration($user_id) {
    if (isset($_GET['invite_code']) && isset($_GET['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_invites';
        $invite_code = sanitize_text_field($_GET['invite_code']);
        $email = sanitize_email(urldecode($_GET['email']));
        $user = get_userdata($user_id);

        if ($user->user_email !== $email) {
            error_log('Courscribe: Invite email mismatch for user ' . $user_id . '. Expected: ' . $email . ', Got: ' . $user->user_email);
            return;
        }

        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT id, studio_id FROM $table_name WHERE email = %s AND invite_code = %s AND status = 'Pending' AND expires_at > %s",
            $email,
            $invite_code,
            current_time('mysql')
        ));

        if ($invite) {
            $user = wp_update_user(['ID' => $user_id, 'role' => 'collaborator']);
            if (is_wp_error($user)) {
                error_log('Courscribe: Failed to assign collaborator role to user ' . $user_id . ': ' . $user->get_error_message());
            } else {
                $wpdb->update(
                    $table_name,
                    ['status' => 'Accepted'],
                    ['id' => $invite->id],
                    ['%s'],
                    ['%d']
                );
                update_user_meta($user_id, '_courscribe_studio_id', $invite->studio_id);
                error_log('Courscribe: User ' . $user_id . ' registered as collaborator for studio ' . $invite->studio_id . ', invite status updated to Accepted');
            }
        } else {
            error_log('Courscribe: Invalid or expired invite code ' . $invite_code . ' for email ' . $email);
        }
    }
}

// Handle client registration via invite
add_action('user_register', 'courscribe_handle_client_registration');
function courscribe_handle_client_registration($user_id) {
    if (isset($_GET['client_invite_code']) && isset($_GET['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_client_invites';
        $invite_code = sanitize_text_field($_GET['client_invite_code']);
        $email = sanitize_email(urldecode($_GET['email']));
        $user = get_userdata($user_id);

        if ($user->user_email !== $email) {
            error_log('Courscribe: Client invite email mismatch for user ' . $user_id . '. Expected: ' . $email . ', Got: ' . $user->user_email);
            return;
        }

        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT id, curriculum_id FROM $table_name WHERE email = %s AND invite_code = %s AND status = 'Pending' AND expires_at > %s",
            $email,
            $invite_code,
            current_time('mysql')
        ));

        if ($invite) {
            $user = wp_update_user(['ID' => $user_id, 'role' => 'client']);
            if (is_wp_error($user)) {
                error_log('Courscribe: Failed to assign client role to user ' . $user_id . ': ' . $user->get_error_message());
            } else {
                $wpdb->update(
                    $table_name,
                    ['status' => 'Accepted'],
                    ['id' => $invite->id],
                    ['%s'],
                    ['%d']
                );
                error_log('Courscribe: User ' . $user_id . ' registered as client for curriculum ' . $invite->curriculum_id . ', invite status updated to Accepted');
            }
        } else {
            error_log('Courscribe: Invalid or expired client invite code ' . $invite_code . ' for email ' . $email);
        }
    }
}
?>