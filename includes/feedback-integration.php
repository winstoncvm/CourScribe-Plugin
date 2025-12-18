<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
// includes/feedback-integration.php
// Create annotations table on plugin activation
function courscribe_create_annotations_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        post_type varchar(20) NOT NULL,
        field_id varchar(100) NOT NULL,
        annotation_data longtext NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    error_log('Courscribe: Annotations table created or updated: ' . $table_name);
}
register_activation_hook(__FILE__, 'courscribe_create_annotations_table');

// Register AJAX actions
add_action('wp_ajax_courscribe_save_feedback', 'courscribe_save_feedback');
add_action('wp_ajax_courscribe_get_feedback', 'courscribe_get_feedback');

// Save feedback/response
function courscribe_save_feedback() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    $user = wp_get_current_user();
    if (!$user->ID) {
        error_log('Courscribe: User not logged in');
        wp_send_json_error(['message' => 'User not logged in'], 401);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';

    if (!$post_id || !$post_type || !$field_id) {
        error_log('Courscribe: Missing required fields in feedback request');
        wp_send_json_error(['message' => 'Missing required fields'], 400);
    }

    $data = [
        'post_id' => $post_id,
        'post_type' => $post_type,
        'field_id' => $field_id,
        'annotation_data' => wp_json_encode([
            'type' => sanitize_text_field($_POST['type'] ?? 'feedback'),
            'text' => sanitize_textarea_field($_POST['text'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'open'),
            'screenshot_url' => isset($_POST['screenshot']) ? esc_url_raw($_POST['screenshot']) : null,
            'annotations' => isset($_POST['annotations']) ? json_decode(stripslashes($_POST['annotations']), true) : [],
            'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0,
        ]),
        'user_id' => $user->ID,
        'status' => 'pending',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ];

    // Save screenshot if provided
    if (!empty($_POST['screenshot']) && strpos($_POST['screenshot'], 'data:image') === 0) {
        $upload = courscribe_save_screenshot($_POST['screenshot'], $user->ID);
        if (is_wp_error($upload)) {
            wp_send_json_error(['message' => $upload->get_error_message()], 500);
        }
        $data['annotation_data'] = wp_json_encode(array_merge(json_decode($data['annotation_data'], true), ['screenshot_url' => $upload['url']]));
    }

    $result = $wpdb->insert($table_name, $data);
    if ($result === false) {
        error_log('Courscribe: Failed to save feedback: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to save feedback'], 500);
    }

    $data['id'] = $wpdb->insert_id;
    error_log('Courscribe: Feedback saved with ID ' . $data['id'] . ' for user ' . $user->ID);
    wp_send_json_success($data);
}

// Save screenshot
function courscribe_save_screenshot($data_url, $user_id) {
    $upload_dir = wp_upload_dir();
    $courscribe_dir = $upload_dir['basedir'] . '/courscribe/screenshots/';
    $courscribe_url = $upload_dir['baseurl'] . '/courscribe/screenshots/';

    if (!file_exists($courscribe_dir)) {
        wp_mkdir_p($courscribe_dir);
    }

    $filename = 'screenshot-' . $user_id . '-' . time() . '.png';
    $file_path = $courscribe_dir . $filename;

    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data_url));
    $result = file_put_contents($file_path, $image_data);

    if ($result === false) {
        error_log('Courscribe: Failed to save screenshot for user ' . $user_id);
        return new WP_Error('upload_error', 'Failed to save screenshot');
    }

    return [
        'url' => $courscribe_url . $filename,
        'path' => $file_path,
    ];
}

// Fetch feedback
function courscribe_get_feedback() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    $user = wp_get_current_user();
    if (!$user->ID) {
        error_log('Courscribe: User not logged in');
        wp_send_json_error(['message' => 'User not logged in'], 401);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';

    $query = "SELECT a.*, u.display_name, u.user_email FROM $table_name a 
              JOIN {$wpdb->users} u ON a.user_id = u.ID 
              WHERE post_id = %d AND post_type = %s AND field_id = %s";
    $params = [$post_id, $post_type, $field_id];

    $results = $wpdb->get_results($wpdb->prepare($query, $params));
    $feedback = [];

    foreach ($results as $row) {
        $data = json_decode($row->annotation_data, true);
        $feedback[] = [
            'id' => $row->id,
            'user_id' => $row->user_id,
            'user_name' => $row->display_name,
            'role' => courscribe_get_user_role($row->user_id),
            'text' => $data['text'],
            'status' => $data['status'],
            'screenshot_url' => $data['screenshot_url'] ?? null,
            'annotations' => $data['annotations'] ?? [],
            'parent_id' => $data['parent_id'] ?? 0,
            'timestamp' => $row->created_at,
        ];
    }

    error_log('Courscribe: Fetched feedback for post_id ' . $post_id . ', post_type ' . $post_type . ', field_id ' . $field_id);
    wp_send_json_success($feedback);
}

// Helper to get user role
function courscribe_get_user_role($user_id) {
    $user = get_user_by('ID', $user_id);
    if (!$user) return 'Unknown';
    $roles = $user->roles;
    if (in_array('client', $roles)) return 'Client';
    if (in_array('studio_admin', $roles)) return 'Studio Manager';
    if (in_array('collaborator', $roles)) return 'Collaborator';
    return 'Unknown';
}
add_action('wp_ajax_courscribe_get_feedback_count', 'courscribe_get_feedback_count');

function courscribe_get_feedback_count() {
    // check_ajax_referer('courscribe_nonce', 'nonce');

    $user = wp_get_current_user();
    if (!$user->ID) {
        error_log('Courscribe: User not logged in');
        wp_send_json_error(['message' => 'User not logged in'], 401);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';

    if (!$post_id || !$post_type || !$field_id) {
        error_log('Courscribe: Missing required fields in feedback count request');
        wp_send_json_error(['message' => 'Missing required fields'], 400);
    }

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND post_type = %s AND field_id = %s",
        $post_id, $post_type, $field_id
    ));

    error_log('Courscribe: Feedback count ' . $count . ' for post_id ' . $post_id . ', post_type ' . $post_type . ', field_id ' . $field_id);
    wp_send_json_success(['count' => $count]);
}
?>