<?php
// courscribe/actions/courscribe-lessons-premium-actions.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Lessons AJAX Handlers
 * Handles all AJAX requests for premium lessons functionality
 */

// Auto-save lesson field
add_action('wp_ajax_courscribe_autosave_lesson_field', 'courscribe_handle_autosave_lesson_field');
function courscribe_handle_autosave_lesson_field() {
    // Security check
    if (!check_ajax_referer('courscribe_lesson_premium_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    //     return;
    // }

    // Sanitize input
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $field_name = sanitize_key($_POST['field_name'] ?? '');
    $field_value = wp_kses_post($_POST['field_value'] ?? '');

    if (!$lesson_id || !$field_name) {
        wp_send_json_error(['message' => 'Missing required parameters']);
        return;
    }

    // Verify lesson exists and user can edit
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson']);
        return;
    }

    // Validate field value based on field type
    $validation = courscribe_validate_lesson_field($field_name, $field_value);
    if (!$validation['is_valid']) {
        wp_send_json_error(['message' => $validation['message']]);
        return;
    }

    // Get old value for logging
    $old_value = '';
    if ($field_name === 'lesson_name') {
        $old_value = $lesson->post_title;
    } else {
        $old_value = get_post_meta($lesson_id, $field_name === 'lesson_goal' ? 'lesson-goal' : $field_name, true);
    }

    try {
        // Save field based on type
        $result = false;
        if ($field_name === 'lesson_name') {
            $result = wp_update_post([
                'ID' => $lesson_id,
                'post_title' => $field_value
            ]);
        } elseif ($field_name === 'lesson_goal') {
            $result = update_post_meta($lesson_id, 'lesson-goal', $field_value);
        } else {
            // Handle custom fields
            $meta_key = $field_name;
            $result = update_post_meta($lesson_id, $meta_key, $field_value);
        }

        if ($result !== false) {
            // Log the change for audit trail
            courscribe_log_lesson_activity_premium($lesson_id, 'field_updated', [
                'field_name' => $field_name,
                'old_value' => $old_value,
                'new_value' => $field_value,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ]);

            wp_send_json_success([
                'message' => 'Field saved successfully',
                'field_name' => $field_name,
                'field_value' => $field_value
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to save field']);
        }
    } catch (Exception $e) {
        error_log('CourScribe Auto-save Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error occurred']);
    }
}

// Save complete lesson
add_action('wp_ajax_courscribe_save_lesson_premium', 'courscribe_handle_save_lesson_premium');
function courscribe_handle_save_lesson_premium() {
    // Security check
    if (!check_ajax_referer('courscribe_lesson_premium_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    //     return;
    // }

    // Sanitize input
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $lesson_name = sanitize_text_field($_POST['lesson_name'] ?? '');
    $lesson_goal = wp_kses_post($_POST['lesson_goal'] ?? '');

    if (!$lesson_id) {
        wp_send_json_error(['message' => 'Missing lesson ID']);
        return;
    }

    // Validate required fields
    if (empty($lesson_name) || empty($lesson_goal)) {
        wp_send_json_error(['message' => 'Lesson name and goal are required']);
        return;
    }

    // Verify lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson']);
        return;
    }

    try {
        // Update lesson post
        $result = wp_update_post([
            'ID' => $lesson_id,
            'post_title' => $lesson_name,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to update lesson: ' . $result->get_error_message()]);
            return;
        }

        // Update lesson meta
        update_post_meta($lesson_id, 'lesson-goal', $lesson_goal);

        // Process objectives if provided
        if (isset($_POST['objectives']) && is_array($_POST['objectives'])) {
            $objectives = [];
            foreach ($_POST['objectives'] as $objective) {
                $objectives[] = [
                    'id' => sanitize_text_field($objective['id'] ?? uniqid('obj_')),
                    'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
                    'action_verb' => sanitize_text_field($objective['action_verb'] ?? ''),
                    'description' => sanitize_text_field($objective['description'] ?? '')
                ];
            }
            update_post_meta($lesson_id, '_lesson_objectives', $objectives);
        }

        // Process activities if provided
        if (isset($_POST['activities']) && is_array($_POST['activities'])) {
            $activities = [];
            foreach ($_POST['activities'] as $activity) {
                $activities[] = [
                    'id' => sanitize_text_field($activity['id'] ?? uniqid('act_')),
                    'type' => sanitize_text_field($activity['type'] ?? ''),
                    'title' => sanitize_text_field($activity['title'] ?? ''),
                    'instructions' => wp_kses_post($activity['instructions'] ?? '')
                ];
            }
            update_post_meta($lesson_id, '_lesson_activities', $activities);
        }

        // Process teaching points if provided
        if (isset($_POST['teaching_points']) && is_array($_POST['teaching_points'])) {
            $teaching_points = array_map('sanitize_text_field', $_POST['teaching_points']);
            update_post_meta($lesson_id, '_teaching_points', $teaching_points);
        }

        // Log the save activity
        courscribe_log_lesson_activity_premium($lesson_id, 'lesson_saved', [
            'lesson_name' => $lesson_name,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        wp_send_json_success([
            'message' => 'Lesson saved successfully',
            'lesson_id' => $lesson_id,
            'lesson_name' => $lesson_name
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Save Lesson Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error occurred']);
    }
}

// Toggle lesson archive status
add_action('wp_ajax_courscribe_toggle_lesson_archive', 'courscribe_handle_toggle_lesson_archive');
function courscribe_handle_toggle_lesson_archive() {
    // Security check
    if (!check_ajax_referer('courscribe_lesson_premium_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    //     return;
    // }

    // Sanitize input
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $archive_action = sanitize_key($_POST['archive_action'] ?? '');

    if (!$lesson_id || !in_array($archive_action, ['archive', 'unarchive'])) {
        wp_send_json_error(['message' => 'Invalid parameters']);
        return;
    }

    // Verify lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson']);
        return;
    }

    try {
        $new_status = ($archive_action === 'archive') ? 'archived' : 'active';
        $old_status = get_post_meta($lesson_id, '_lesson_status', true) ?: 'active';
        
        // Update lesson status
        update_post_meta($lesson_id, '_lesson_status', $new_status);

        // Log the activity
        courscribe_log_lesson_activity_premium($lesson_id, 'status_changed', [
            'old_status' => $old_status,
            'new_status' => $new_status,
            'action' => $archive_action,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        $message = ($archive_action === 'archive') 
            ? 'Lesson archived successfully' 
            : 'Lesson restored successfully';

        wp_send_json_success([
            'message' => $message,
            'lesson_id' => $lesson_id,
            'new_status' => $new_status
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Archive Lesson Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error occurred']);
    }
}

// Add teaching point
add_action('wp_ajax_courscribe_add_teaching_point', 'courscribe_handle_add_teaching_point');
function courscribe_handle_add_teaching_point() {
    // Security check
    if (!check_ajax_referer('courscribe_lesson_premium_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    //     return;
    // }

    // Sanitize input
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $teaching_point = sanitize_text_field($_POST['teaching_point'] ?? '');

    if (!$lesson_id || empty($teaching_point)) {
        wp_send_json_error(['message' => 'Missing required parameters']);
        return;
    }

    // Validate teaching point
    $validation = courscribe_validate_lesson_field('teaching_point', $teaching_point);
    if (!$validation['is_valid']) {
        wp_send_json_error(['message' => $validation['message']]);
        return;
    }

    // Verify lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson']);
        return;
    }

    try {
        // Get existing teaching points
        $teaching_points = get_post_meta($lesson_id, '_teaching_points', true) ?: [];
        
        // Add new teaching point
        $teaching_points[] = $teaching_point;
        $point_index = count($teaching_points) - 1;

        // Save updated teaching points
        update_post_meta($lesson_id, '_teaching_points', $teaching_points);

        // Log the activity
        courscribe_log_lesson_activity($lesson_id, 'teaching_point_added', [
            'teaching_point' => $teaching_point,
            'point_index' => $point_index,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        wp_send_json_success([
            'message' => 'Teaching point added successfully',
            'teaching_point' => $teaching_point,
            'point_index' => $point_index,
            'teaching_points' => $teaching_points
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Add Teaching Point Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error occurred']);
    }
}

// Get lesson activity logs
add_action('wp_ajax_courscribe_get_lesson_activity_logs', 'courscribe_handle_get_lesson_activity_logs');
function courscribe_handle_get_lesson_activity_logs() {
    // Security check
    if (!check_ajax_referer('courscribe_lesson_premium_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    //     return;
    // }

    // Sanitize input
    $module_id = absint($_POST['module_id'] ?? 0);
    $limit = absint($_POST['limit'] ?? 50);
    $offset = absint($_POST['offset'] ?? 0);

    if (!$module_id) {
        wp_send_json_error(['message' => 'Missing module ID']);
        return;
    }

    try {
        // Get lessons for this module
        $lessons = get_posts([
            'post_type' => 'crscribe_lesson',
            'post_status' => ['publish', 'archived'],
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_module_id',
                    'value' => $module_id,
                    'compare' => '='
                ]
            ]
        ]);

        if (empty($lessons)) {
            wp_send_json_success([
                'html' => '<div class="alert alert-info">No lessons found for this module.</div>',
                'logs' => []
            ]);
            return;
        }

        // Get logs for all lessons in this module
        $lesson_ids = wp_list_pluck($lessons, 'ID');
        $logs = courscribe_get_lesson_activity_logs($lesson_ids, $limit, $offset);

        // Generate HTML for logs
        $html = courscribe_render_activity_logs_html($logs, $lessons);

        wp_send_json_success([
            'html' => $html,
            'logs' => $logs,
            'total_logs' => count($logs)
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Get Logs Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error occurred']);
    }
}

// Log lesson field change
add_action('wp_ajax_courscribe_log_lesson_field_change', 'courscribe_handle_log_lesson_field_change');
function courscribe_handle_log_lesson_field_change() {
    // Security check
    if (!check_ajax_referer('courscribe_lesson_premium_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // This is a logging endpoint, so we just log and return success
    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $field_name = sanitize_key($_POST['field_name'] ?? '');
    $old_value = wp_kses_post($_POST['old_value'] ?? '');
    $new_value = wp_kses_post($_POST['new_value'] ?? '');

    if ($lesson_id && $field_name) {
        courscribe_log_lesson_activity_premium($lesson_id, 'field_changed', [
            'field_name' => $field_name,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);
    }

    wp_send_json_success(['message' => 'Change logged']);
}

/**
 * Validation Functions
 */

function courscribe_validate_lesson_field($field_name, $value) {
    $rules = [
        'lesson_name' => [
            'required' => true,
            'min_length' => 3,
            'max_length' => 100,
            'pattern' => '/^[a-zA-Z0-9\s\-_.,!?()]+$/'
        ],
        'lesson_goal' => [
            'required' => true,
            'min_length' => 10,
            'max_length' => 500
        ],
        'teaching_point' => [
            'required' => true,
            'min_length' => 5,
            'max_length' => 500
        ]
    ];

    $rule = $rules[$field_name] ?? [];

    // Required check
    if (($rule['required'] ?? false) && empty(trim($value))) {
        return ['is_valid' => false, 'message' => 'This field is required.'];
    }

    // Length checks
    $length = strlen($value);
    if (isset($rule['min_length']) && $length < $rule['min_length']) {
        return ['is_valid' => false, 'message' => "Minimum {$rule['min_length']} characters required."];
    }

    if (isset($rule['max_length']) && $length > $rule['max_length']) {
        return ['is_valid' => false, 'message' => "Maximum {$rule['max_length']} characters allowed."];
    }

    // Pattern check
    if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
        return ['is_valid' => false, 'message' => 'Invalid format for this field.'];
    }

    return ['is_valid' => true, 'message' => ''];
}

/**
 * Activity Logging Functions
 */

function courscribe_log_lesson_activity_premium($lesson_id, $activity_type, $activity_data = []) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_lesson_logs';

    // Create table if not exists
    courscribe_create_lesson_logs_table();

    try {
        $result = $wpdb->insert(
            $table_name,
            [
                'lesson_id' => $lesson_id,
                'activity_type' => $activity_type,
                'activity_data' => maybe_serialize($activity_data),
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%d', '%s']
        );

        if ($result === false) {
            error_log('CourScribe: Failed to log lesson activity - ' . $wpdb->last_error);
        }

        return $result !== false;
    } catch (Exception $e) {
        error_log('CourScribe: Lesson activity log error - ' . $e->getMessage());
        return false;
    }
}

function courscribe_get_lesson_activity_logs($lesson_ids, $limit = 50, $offset = 0) {
    global $wpdb;

    if (empty($lesson_ids)) {
        return [];
    }

    $table_name = $wpdb->prefix . 'courscribe_lesson_logs';
    $lesson_ids_placeholder = implode(',', array_fill(0, count($lesson_ids), '%d'));

    $query = $wpdb->prepare(
        "SELECT l.*, u.display_name, p.post_title as lesson_title
         FROM {$table_name} l
         LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
         LEFT JOIN {$wpdb->posts} p ON l.lesson_id = p.ID
         WHERE l.lesson_id IN ({$lesson_ids_placeholder})
         ORDER BY l.created_at DESC
         LIMIT %d OFFSET %d",
        array_merge($lesson_ids, [$limit, $offset])
    );

    $results = $wpdb->get_results($query, ARRAY_A);

    // Unserialize activity data
    foreach ($results as &$result) {
        $result['activity_data'] = maybe_unserialize($result['activity_data']);
    }

    return $results;
}

function courscribe_create_lesson_logs_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_lesson_logs';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        lesson_id bigint(20) NOT NULL,
        activity_type varchar(50) NOT NULL,
        activity_data longtext,
        user_id bigint(20) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY lesson_id (lesson_id),
        KEY activity_type (activity_type),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function courscribe_render_activity_logs_html($logs, $lessons) {
    if (empty($logs)) {
        return '<div class="alert alert-info">No activity logs found.</div>';
    }

    $html = '<div class="cs-activity-logs">';
    $html .= '<div class="cs-logs-header mb-3">';
    $html .= '<div class="d-flex justify-content-between align-items-center">';
    $html .= '<h6 class="mb-0">Recent Activity</h6>';
    $html .= '<small class="text-muted">' . count($logs) . ' entries</small>';
    $html .= '</div></div>';

    $html .= '<div class="cs-logs-list">';
    
    foreach ($logs as $log) {
        $html .= courscribe_render_single_log_entry($log);
    }
    
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function courscribe_render_single_log_entry($log) {
    $activity_data = $log['activity_data'] ?? [];
    $user_name = $log['display_name'] ?? 'Unknown User';
    $lesson_title = $log['lesson_title'] ?? 'Unknown Lesson';
    $time_ago = human_time_diff(strtotime($log['created_at']), current_time('timestamp')) . ' ago';
    
    $icon_class = courscribe_get_activity_icon_lesson($log['activity_type']);
    $description = courscribe_get_activity_description($log['activity_type'], $activity_data);

    $html = '<div class="cs-log-entry mb-3">';
    $html .= '<div class="cs-log-content">';
    $html .= '<div class="cs-log-header">';
    $html .= '<div class="cs-log-icon"><i class="fas ' . $icon_class . '"></i></div>';
    $html .= '<div class="cs-log-details">';
    $html .= '<div class="cs-log-description">' . $description . '</div>';
    $html .= '<div class="cs-log-meta">';
    $html .= '<span class="user">' . esc_html($user_name) . '</span>';
    $html .= '<span class="separator">•</span>';
    $html .= '<span class="lesson">' . esc_html($lesson_title) . '</span>';
    $html .= '<span class="separator">•</span>';
    $html .= '<span class="time">' . $time_ago . '</span>';
    $html .= '</div></div>';
    $html .= '</div>';
    
    // Add restore button for certain activity types
    if (in_array($log['activity_type'], ['field_updated', 'field_changed']) && isset($activity_data['old_value'])) {
        $html .= '<div class="cs-log-actions">';
        $html .= '<button type="button" class="btn btn-sm btn-outline-secondary cs-restore-from-log" ';
        $html .= 'data-lesson-id="' . $log['lesson_id'] . '" ';
        $html .= 'data-field-name="' . ($activity_data['field_name'] ?? '') . '" ';
        $html .= 'data-old-value="' . esc_attr($activity_data['old_value'] ?? '') . '" ';
        $html .= 'title="Restore this value">';
        $html .= '<i class="fas fa-undo"></i> Restore';
        $html .= '</button>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function courscribe_get_activity_icon_lesson($activity_type) {
    $icons = [
        'field_updated' => 'fa-edit',
        'field_changed' => 'fa-edit',
        'lesson_saved' => 'fa-save',
        'status_changed' => 'fa-archive',
        'teaching_point_added' => 'fa-plus',
        'teaching_point_removed' => 'fa-minus',
        'objective_added' => 'fa-list-ol',
        'activity_added' => 'fa-tasks'
    ];

    return $icons[$activity_type] ?? 'fa-info-circle';
}

function courscribe_get_activity_description($activity_type, $activity_data) {
    switch ($activity_type) {
        case 'field_updated':
        case 'field_changed':
            $field_name = $activity_data['field_name'] ?? 'field';
            $field_display = str_replace('_', ' ', ucfirst($field_name));
            return "Updated {$field_display}";
            
        case 'lesson_saved':
            return 'Saved lesson changes';
            
        case 'status_changed':
            $action = $activity_data['action'] ?? 'changed';
            return ucfirst($action) . 'd lesson';
            
        case 'teaching_point_added':
            return 'Added teaching point';
            
        case 'teaching_point_removed':
            return 'Removed teaching point';
            
        case 'objective_added':
            return 'Added learning objective';
            
        case 'activity_added':
            return 'Added lesson activity';
            
        default:
            return 'Unknown activity';
    }
}
?>