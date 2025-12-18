<?php
// courscribe-review-system.php

// Ensure direct access is prevented
if (!defined('ABSPATH')) {
    exit;
}

// Enable comments on custom post types
function courscribe_enable_comments() {
    add_post_type_support('crscribe_curriculum', 'comments');
    add_post_type_support('crscribe_course', 'comments');
}
add_action('init', 'courscribe_enable_comments');

// Meta box for per-field feedback
function courscribe_field_feedback_meta_box() {
    add_meta_box(
        'courscribe_field_feedback',
        'Field Feedback',
        'courscribe_field_feedback_callback',
        ['crscribe_curriculum', 'crscribe_course'],
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'courscribe_field_feedback_meta_box');

function courscribe_field_feedback_callback($post) {
    if (!current_user_can('edit_crscribe_studios')) {
        echo '<p>You do not have permission to edit feedback.</p>';
        return;
    }
    $fields = ['title', 'description'];
    foreach ($fields as $field) {
        $feedback = get_post_meta($post->ID, "_feedback_$field", true);
        echo "<label>$field Feedback:</label><br>";
        echo "<textarea name='feedback_$field' class='widefat'>$feedback</textarea><br>";
    }
}

// Save per-field feedback (studio admins only)
function courscribe_save_field_feedback($post_id) {
    if (!current_user_can('edit_crscribe_studios')) {
        return;
    }
    $fields = ['title', 'description'];
    foreach ($fields as $field) {
        if (isset($_POST["feedback_$field"])) {
            update_post_meta($post_id, "_feedback_$field", sanitize_textarea_field($_POST["feedback_$field"]));
        }
    }
}
add_action('save_post', 'courscribe_save_field_feedback');

// Filter comments to restrict editing
function courscribe_restrict_comment_editing($caps, $cap, $user_id, $args) {
    if ($cap === 'edit_comment') {
        $user = get_userdata($user_id);
        if (in_array('collaborator', $user->roles)) {
            $caps[] = 'do_not_allow';
        }
    }
    return $caps;
}
add_filter('map_meta_cap', 'courscribe_restrict_comment_editing', 10, 4);

// Update user-roles.php to include client role (assuming it's included elsewhere)
function courscribe_update_invite_handling($user_id) {
    if (isset($_GET['invite_code']) && isset($_GET['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_invites';
        $invite_code = sanitize_text_field($_GET['invite_code']);
        $email = sanitize_email(urldecode($_GET['email']));
        $user = get_userdata($user_id);

        if ($user->user_email === $email) {
            $invite = $wpdb->get_row($wpdb->prepare(
                "SELECT studio_id, role FROM $table_name WHERE email = %s AND invite_code = %s AND status = 'Pending' AND expires_at > %s",
                $email,
                $invite_code,
                current_time('mysql')
            ));
            if ($invite) {
                $role = $invite->role ?: 'collaborator';
                wp_update_user(['ID' => $user_id, 'role' => $role]);
                $wpdb->update($table_name, ['status' => 'Accepted'], ['invite_code' => $invite_code]);
                update_user_meta($user_id, '_courscribe_studio_id', $invite->studio_id);
            }
        }
    }
}
add_action('user_register', 'courscribe_update_invite_handling');

// Function to create the client invites table

?>