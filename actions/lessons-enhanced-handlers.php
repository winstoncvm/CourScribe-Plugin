<?php
// courscribe/actions/lessons-enhanced-handlers.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Lesson AJAX Handlers with Idempotency
 * 
 * Features:
 * - Idempotent request handling
 * - Comprehensive validation
 * - Proper error handling
 * - Archive/restore/delete operations
 * - Objectives and activities management
 * - Teaching points management
 * - Activity logging
 */

/**
 * Generate unique request hash for idempotency
 */
function courscribe_generate_request_hash($data) {
    ksort($data); // Ensure consistent ordering
    return hash('sha256', json_encode($data) . get_current_user_id());
}

/**
 * Check if request is duplicate (idempotency check)
 */
function courscribe_is_duplicate_request($hash, $expiry_minutes = 5) {
    $transient_key = 'cs_req_' . substr($hash, 0, 32); // Limit key length
    $existing = get_transient($transient_key);
    
    if ($existing) {
        return $existing; // Return cached response
    }
    
    return false;
}

/**
 * Store request response for idempotency
 */
function courscribe_store_request_response($hash, $response, $expiry_minutes = 5) {
    $transient_key = 'cs_req_' . substr($hash, 0, 32);
    set_transient($transient_key, $response, $expiry_minutes * 60);
}

/**
 * Auto-save lesson field
 */
add_action('wp_ajax_courscribe_autosave_lesson_field', 'courscribe_handle_autosave_lesson_field_handler');
function courscribe_handle_autosave_lesson_field_handler() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    // Get and validate input
    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $field_name = sanitize_key($_POST['field_name'] ?? '');
    $field_value = wp_kses_post($_POST['field_value'] ?? '');
    $timestamp = intval($_POST['timestamp'] ?? time());
    $request_hash = sanitize_text_field($_POST['request_hash'] ?? '');

    if (!$lesson_id || !$field_name) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    // Generate hash for idempotency if not provided
    if (!$request_hash) {
        $hash_data = [
            'lesson_id' => $lesson_id,
            'field_name' => $field_name,
            'field_value' => $field_value,
            'timestamp' => $timestamp
        ];
        $request_hash = courscribe_generate_request_hash($hash_data);
    }

    // Check for duplicate request
    $cached_response = courscribe_is_duplicate_request($request_hash);
    if ($cached_response) {
        wp_send_json($cached_response);
    }

    // Verify lesson exists and user can edit
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        $response = ['success' => false, 'data' => ['message' => 'Lesson not found']];
        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);
    }

    // Check if lesson is archived
    $lesson_status = get_post_meta($lesson_id, '_lesson_status', true);
    if ($lesson_status === 'archived') {
        $response = ['success' => false, 'data' => ['message' => 'Cannot edit archived lesson']];
        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);
    }

    try {
        global $wpdb;
        $wpdb->query('START TRANSACTION');

        $success = false;
        $message = '';

        switch ($field_name) {
            case 'lesson_goal':
                if (strlen($field_value) > 500) {
                    throw new Exception('Lesson goal too long (max 500 characters)');
                }
                $success = update_post_meta($lesson_id, '_lesson_goal', $field_value);
                $message = 'Lesson goal updated';
                break;

            case 'lesson_name':
                if (strlen($field_value) > 100) {
                    throw new Exception('Lesson name too long (max 100 characters)');
                }
                $success = wp_update_post([
                    'ID' => $lesson_id,
                    'post_title' => $field_value
                ]);
                $message = 'Lesson name updated';
                break;

            case 'objective_description':
                $objective_index = intval($_POST['objective_index'] ?? -1);
                if ($objective_index >= 0) {
                    $objectives = maybe_unserialize(get_post_meta($lesson_id, '_lesson_objectives', true)) ?: [];
                    if (isset($objectives[$objective_index])) {
                        $objectives[$objective_index]['description'] = $field_value;
                        $success = update_post_meta($lesson_id, '_lesson_objectives', $objectives);
                        $message = 'Objective updated';
                    }
                }
                break;

            case 'activity_title':
                $activity_index = intval($_POST['activity_index'] ?? -1);
                if ($activity_index >= 0) {
                    $activities = maybe_unserialize(get_post_meta($lesson_id, '_lesson_activities', true)) ?: [];
                    if (isset($activities[$activity_index])) {
                        $activities[$activity_index]['title'] = $field_value;
                        $success = update_post_meta($lesson_id, '_lesson_activities', $activities);
                        $message = 'Activity title updated';
                    }
                }
                break;

            case 'activity_description':
                $activity_index = intval($_POST['activity_index'] ?? -1);
                if ($activity_index >= 0) {
                    $activities = maybe_unserialize(get_post_meta($lesson_id, '_lesson_activities', true)) ?: [];
                    if (isset($activities[$activity_index])) {
                        $activities[$activity_index]['description'] = $field_value;
                        $success = update_post_meta($lesson_id, '_lesson_activities', $activities);
                        $message = 'Activity description updated';
                    }
                }
                break;

            default:
                throw new Exception('Invalid field name');
        }

        if (!$success) {
            throw new Exception('Failed to update field');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'auto_save', [
            'field' => $field_name,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        $wpdb->query('COMMIT');

        $response = [
            'success' => true,
            'data' => [
                'message' => $message,
                'lesson_id' => $lesson_id,
                'field_name' => $field_name,
                'timestamp' => current_time('mysql')
            ]
        ];

        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);

    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        
        $response = [
            'success' => false,
            'data' => ['message' => $e->getMessage()]
        ];
        
        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);
    }
}

/**
 * Add objective to lesson
 */
add_action('wp_ajax_courscribe_add_objective', 'courscribe_handle_add_objective');
function courscribe_handle_add_objective() {
    // Security checks
    if (!check_ajax_referer('courscribe_objective_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $thinking_skill = sanitize_text_field($_POST['thinking_skill'] ?? 'understand');
    $action_verb = sanitize_text_field($_POST['action_verb'] ?? 'explain');
    $description = wp_kses_post($_POST['description'] ?? '');

    if (!$lesson_id || !$description) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    // Generate request hash
    $hash_data = [
        'action' => 'add_objective',
        'lesson_id' => $lesson_id,
        'thinking_skill' => $thinking_skill,
        'action_verb' => $action_verb,
        'description' => $description,
        'timestamp' => time()
    ];
    $request_hash = courscribe_generate_request_hash($hash_data);

    // Check for duplicate
    $cached_response = courscribe_is_duplicate_request($request_hash);
    if ($cached_response) {
        wp_send_json($cached_response);
    }

    try {
        $objectives = get_post_meta($lesson_id, '_lesson_objectives', true);
        
        // Ensure objectives is an array
        if (!is_array($objectives)) {
            $objectives = [];
        }
        
        // Add new objective
        $objectives[] = [
            'thinking_skill' => $thinking_skill,
            'action_verb' => $action_verb,
            'description' => $description,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];

        $success = update_post_meta($lesson_id, '_lesson_objectives', $objectives);

        if (!$success) {
            throw new Exception('Failed to add objective');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'add_objective', [
            'objective_count' => count($objectives),
            'user_id' => get_current_user_id()
        ]);

        $response = [
            'success' => true,
            'data' => [
                'message' => 'Objective added successfully',
                'objective_index' => count($objectives) - 1,
                'objectives' => $objectives
            ]
        ];

        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);

    } catch (Exception $e) {
        $response = [
            'success' => false,
            'data' => ['message' => $e->getMessage()]
        ];
        
        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);
    }
}

/**
 * Remove objective from lesson
 */
add_action('wp_ajax_courscribe_remove_objective', 'courscribe_handle_remove_objective');
function courscribe_handle_remove_objective() {
    // Security checks
    if (!check_ajax_referer('courscribe_objective_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $objective_index = intval($_POST['objective_index'] ?? -1);

    if (!$lesson_id || $objective_index < 0) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    try {
        $objectives = maybe_unserialize(get_post_meta($lesson_id, '_lesson_objectives', true)) ?: [];
        
        if (!isset($objectives[$objective_index])) {
            throw new Exception('Objective not found');
        }

        // Remove objective
        $removed_objective = $objectives[$objective_index];
        unset($objectives[$objective_index]);
        $objectives = array_values($objectives); // Re-index array

        $success = update_post_meta($lesson_id, '_lesson_objectives', $objectives);

        if (!$success) {
            throw new Exception('Failed to remove objective');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'remove_objective', [
            'removed_objective' => $removed_objective,
            'remaining_count' => count($objectives),
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success([
            'message' => 'Objective removed successfully',
            'objectives' => $objectives
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Add activity to lesson
 */
add_action('wp_ajax_courscribe_add_activity', 'courscribe_handle_add_activity');
function courscribe_handle_add_activity() {
    // Security checks
    if (!check_ajax_referer('courscribe_activity_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $description = wp_kses_post($_POST['description'] ?? '');
    $duration = intval($_POST['duration'] ?? 15);
    $type = sanitize_text_field($_POST['type'] ?? 'individual');

    if (!$lesson_id || !$title || !$description) {
        wp_send_json_error(['message' => 'Title and description are required']);
    }

    // Generate request hash
    $hash_data = [
        'action' => 'add_activity',
        'lesson_id' => $lesson_id,
        'title' => $title,
        'description' => $description,
        'duration' => $duration,
        'type' => $type,
        'timestamp' => time()
    ];
    $request_hash = courscribe_generate_request_hash($hash_data);

    // Check for duplicate
    $cached_response = courscribe_is_duplicate_request($request_hash);
    if ($cached_response) {
        wp_send_json($cached_response);
    }

    try {
        $activities = maybe_unserialize(get_post_meta($lesson_id, '_lesson_activities', true)) ?: [];
        
        // Add new activity
        $activities[] = [
            'title' => $title,
            'description' => $description,
            'duration' => $duration,
            'type' => $type,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];

        $success = update_post_meta($lesson_id, '_lesson_activities', $activities);

        if (!$success) {
            throw new Exception('Failed to add activity');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'add_activity', [
            'activity_count' => count($activities),
            'user_id' => get_current_user_id()
        ]);

        $response = [
            'success' => true,
            'data' => [
                'message' => 'Activity added successfully',
                'activity_index' => count($activities) - 1,
                'activities' => $activities
            ]
        ];

        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);

    } catch (Exception $e) {
        $response = [
            'success' => false,
            'data' => ['message' => $e->getMessage()]
        ];
        
        courscribe_store_request_response($request_hash, $response);
        wp_send_json($response);
    }
}

/**
 * Remove activity from lesson
 */
add_action('wp_ajax_courscribe_remove_activity', 'courscribe_handle_remove_activity');
function courscribe_handle_remove_activity() {
    // Security checks
    if (!check_ajax_referer('courscribe_activity_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $activity_index = intval($_POST['activity_index'] ?? -1);

    if (!$lesson_id || $activity_index < 0) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    try {
        $activities = maybe_unserialize(get_post_meta($lesson_id, '_lesson_activities', true)) ?: [];
        
        if (!isset($activities[$activity_index])) {
            throw new Exception('Activity not found');
        }

        // Remove activity
        $removed_activity = $activities[$activity_index];
        unset($activities[$activity_index]);
        $activities = array_values($activities); // Re-index array

        $success = update_post_meta($lesson_id, '_lesson_activities', $activities);

        if (!$success) {
            throw new Exception('Failed to remove activity');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'remove_activity', [
            'removed_activity' => $removed_activity,
            'remaining_count' => count($activities),
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success([
            'message' => 'Activity removed successfully',
            'activities' => $activities
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Update lesson field (enhanced version for title editing)
 */
add_action('wp_ajax_courscribe_update_lesson_field', 'courscribe_handle_update_lesson_field');
function courscribe_handle_update_lesson_field() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $field_name = sanitize_key($_POST['field_name'] ?? '');
    $field_value = sanitize_text_field($_POST['field_value'] ?? '');

    if (!$lesson_id || !$field_name || !$field_value) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    // Verify lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Lesson not found']);
    }

    try {
        $success = false;
        $message = '';

        switch ($field_name) {
            case 'post_title':
                if (strlen($field_value) > 200) {
                    throw new Exception('Title too long (max 200 characters)');
                }
                $success = wp_update_post([
                    'ID' => $lesson_id,
                    'post_title' => $field_value
                ]);
                $message = 'Lesson title updated successfully';
                break;

            case 'lesson_goal':
                if (strlen($field_value) > 500) {
                    throw new Exception('Goal too long (max 500 characters)');
                }
                $success = update_post_meta($lesson_id, '_lesson_goal', $field_value);
                $message = 'Lesson goal updated successfully';
                break;

            default:
                throw new Exception('Invalid field name');
        }

        if (!$success) {
            throw new Exception('Failed to update field');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'update_field', [
            'field' => $field_name,
            'old_value' => $field_name === 'post_title' ? $lesson->post_title : '',
            'new_value' => $field_value,
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success([
            'message' => $message,
            'lesson_id' => $lesson_id,
            'field_name' => $field_name,
            'field_value' => $field_value
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Add teaching point to lesson (Enhanced version)
 */
add_action('wp_ajax_courscribe_add_teaching_point', 'courscribe_handle_add_teaching_point_handler');
function courscribe_handle_add_teaching_point_handler() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $point_title = sanitize_text_field($_POST['point_title'] ?? '');
    $point_description = wp_kses_post($_POST['point_description'] ?? '');
    $point_example = sanitize_text_field($_POST['point_example'] ?? '');
    $point_activity = sanitize_text_field($_POST['point_activity'] ?? '');
    $content_type = sanitize_text_field($_POST['content_type'] ?? 'structured');

    if (!$lesson_id || !$point_title || !$point_description) {
        wp_send_json_error(['message' => 'Title and description are required']);
    }

    // Validate field lengths
    if (strlen($point_title) > 100) {
        wp_send_json_error(['message' => 'Title too long (max 100 characters)']);
    }
    if (strlen($point_description) > 1000) {
        wp_send_json_error(['message' => 'Description too long (max 1000 characters)']);
    }
    if (strlen($point_example) > 200) {
        wp_send_json_error(['message' => 'Example too long (max 200 characters)']);
    }
    if (strlen($point_activity) > 200) {
        wp_send_json_error(['message' => 'Activity too long (max 200 characters)']);
    }

    try {
        $teaching_points = get_post_meta($lesson_id, '_teaching_points', true) ?: [];
        
        // Create structured teaching point
        $point_data = [
            'type' => 'structured',
            'title' => $point_title,
            'description' => $point_description,
            'example' => $point_example,
            'activity' => $point_activity,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];
        
        // Add new teaching point
        $teaching_points[] = $point_data;

        $success = update_post_meta($lesson_id, '_teaching_points', $teaching_points);

        if (!$success) {
            throw new Exception('Failed to add teaching point');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'add_teaching_point', [
            'point_count' => count($teaching_points),
            'point_title' => $point_title,
            'content_type' => $content_type,
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success([
            'message' => 'Teaching point added successfully',
            'point_index' => count($teaching_points) - 1,
            'teaching_points' => $teaching_points,
            'point_data' => $point_data
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Remove teaching point from lesson
 */
add_action('wp_ajax_courscribe_remove_teaching_point', 'courscribe_handle_remove_teaching_point');
function courscribe_handle_remove_teaching_point() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $point_index = intval($_POST['point_index'] ?? -1);

    if (!$lesson_id || $point_index < 0) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    try {
        $teaching_points = get_post_meta($lesson_id, '_teaching_points', true) ?: [];
        
        if (!isset($teaching_points[$point_index])) {
            throw new Exception('Teaching point not found');
        }

        // Remove teaching point
        $removed_point = $teaching_points[$point_index];
        unset($teaching_points[$point_index]);
        $teaching_points = array_values($teaching_points); // Re-index array

        $success = update_post_meta($lesson_id, '_teaching_points', $teaching_points);

        if (!$success) {
            throw new Exception('Failed to remove teaching point');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'remove_teaching_point', [
            'removed_point' => $removed_point,
            'remaining_count' => count($teaching_points),
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success([
            'message' => 'Teaching point removed successfully',
            'teaching_points' => $teaching_points
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Archive lesson
 */
add_action('wp_ajax_courscribe_archive_lesson', 'courscribe_handle_archive_lesson');
function courscribe_handle_archive_lesson() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);

    if (!$lesson_id) {
        wp_send_json_error(['message' => 'Invalid lesson ID']);
    }

    try {
        // Update lesson status
        $success = update_post_meta($lesson_id, '_lesson_status', 'archived');

        if (!$success) {
            throw new Exception('Failed to archive lesson');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'archive', [
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        wp_send_json_success([
            'message' => 'Lesson archived successfully',
            'lesson_id' => $lesson_id
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Restore lesson from archive
 */
add_action('wp_ajax_courscribe_restore_lesson', 'courscribe_handle_restore_lesson');
function courscribe_handle_restore_lesson() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);

    if (!$lesson_id) {
        wp_send_json_error(['message' => 'Invalid lesson ID']);
    }

    try {
        // Update lesson status
        $success = update_post_meta($lesson_id, '_lesson_status', 'active');

        if (!$success) {
            throw new Exception('Failed to restore lesson');
        }

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'restore', [
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        wp_send_json_success([
            'message' => 'Lesson restored successfully',
            'lesson_id' => $lesson_id
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Delete lesson
 */
add_action('wp_ajax_courscribe_delete_lesson', 'courscribe_handle_delete_lesson');
function courscribe_handle_delete_lesson() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    if (!current_user_can('delete_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $confirm = sanitize_text_field($_POST['confirm'] ?? '');

    if (!$lesson_id || $confirm !== 'DELETE') {
        wp_send_json_error(['message' => 'Confirmation required']);
    }

    try {
        // Get lesson info for logging
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
            throw new Exception('Lesson not found');
        }

        // Log activity before deletion
        courscribe_log_lesson_activity($lesson_id, 'delete', [
            'lesson_title' => $lesson->post_title,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        // Delete lesson
        $success = wp_delete_post($lesson_id, true);

        if (!$success) {
            throw new Exception('Failed to delete lesson');
        }

        wp_send_json_success([
            'message' => 'Lesson deleted successfully',
            'lesson_id' => $lesson_id
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Move lesson up/down in order
 */
add_action('wp_ajax_courscribe_move_lesson', 'courscribe_handle_move_lesson');
function courscribe_handle_move_lesson() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $direction = sanitize_text_field($_POST['direction'] ?? '');

    if (!$lesson_id || !in_array($direction, ['up', 'down'])) {
        wp_send_json_error(['message' => 'Invalid parameters']);
    }

    try {
        // Get current lesson
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
            throw new Exception('Lesson not found');
        }

        $module_id = get_post_meta($lesson_id, '_module_id', true);
        if (!$module_id) {
            throw new Exception('Module not found');
        }

        // Get all lessons in the module
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
            ],
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        // Find current lesson index
        $current_index = -1;
        foreach ($lessons as $index => $lesson_item) {
            if ($lesson_item->ID == $lesson_id) {
                $current_index = $index;
                break;
            }
        }

        if ($current_index === -1) {
            throw new Exception('Lesson not found in module');
        }

        // Calculate new position
        $new_index = $direction === 'up' ? $current_index - 1 : $current_index + 1;
        
        if ($new_index < 0 || $new_index >= count($lessons)) {
            throw new Exception('Cannot move lesson in that direction');
        }

        // Swap menu orders
        $current_lesson = $lessons[$current_index];
        $target_lesson = $lessons[$new_index];

        $current_order = $current_lesson->menu_order;
        $target_order = $target_lesson->menu_order;

        // Update orders
        wp_update_post([
            'ID' => $current_lesson->ID,
            'menu_order' => $target_order
        ]);

        wp_update_post([
            'ID' => $target_lesson->ID,
            'menu_order' => $current_order
        ]);

        // Log activity
        courscribe_log_lesson_activity($lesson_id, 'move', [
            'direction' => $direction,
            'old_position' => $current_index + 1,
            'new_position' => $new_index + 1,
            'user_id' => get_current_user_id()
        ]);

        wp_send_json_success([
            'message' => 'Lesson moved successfully',
            'lesson_id' => $lesson_id,
            'direction' => $direction,
            'new_position' => $new_index + 1
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Log lesson activity
 */
function courscribe_log_lesson_activity($lesson_id, $action, $data = []) {
    global $wpdb;
    
    try {
        $table_name = $wpdb->prefix . 'courscribe_lesson_log';
        
        $wpdb->insert(
            $table_name,
            [
                'lesson_id' => $lesson_id,
                'user_id' => get_current_user_id(),
                'action' => $action,
                'data' => wp_json_encode($data),
                'timestamp' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ],
            [
                '%d', // lesson_id
                '%d', // user_id
                '%s', // action
                '%s', // data
                '%s', // timestamp
                '%s', // ip_address
                '%s'  // user_agent
            ]
        );
    } catch (Exception $e) {
        error_log('CourScribe: Failed to log lesson activity - ' . $e->getMessage());
    }
}

/**
 * Get lesson activity logs
 */
add_action('wp_ajax_courscribe_get_lesson_logs', 'courscribe_handle_get_lesson_logs');
function courscribe_handle_get_lesson_logs() {
    // Security checks
    if (!check_ajax_referer('courscribe_lesson_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    // }

    $lesson_id = intval($_POST['lesson_id'] ?? 0);
    $limit = intval($_POST['limit'] ?? 50);

    if (!$lesson_id) {
        wp_send_json_error(['message' => 'Invalid lesson ID']);
    }

    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_lesson_log';
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE lesson_id = %d 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $lesson_id,
            $limit
        ));

        $formatted_logs = [];
        foreach ($logs as $log) {
            $user = get_user_by('id', $log->user_id);
            $formatted_logs[] = [
                'id' => $log->id,
                'action' => $log->action,
                'user_name' => $user ? $user->display_name : 'Unknown User',
                'timestamp' => $log->timestamp,
                'data' => json_decode($log->data, true)
            ];
        }

        wp_send_json_success([
            'logs' => $formatted_logs,
            'lesson_id' => $lesson_id
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
?>