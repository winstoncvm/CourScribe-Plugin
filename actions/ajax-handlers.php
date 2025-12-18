<?php
// courscribe/includes/ajax-handlers.php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_courscribe_create_curriculum', 'courscribe_create_curriculum_ajax');
add_action('wp_ajax_courscribe_update_curriculum', 'courscribe_update_curriculum_ajax');
add_action('wp_ajax_courscribe_archive_curriculum', 'courscribe_archive_curriculum_ajax');
add_action('wp_ajax_courscribe_unarchive_curriculum', 'courscribe_unarchive_curriculum_ajax'); 
add_action('wp_ajax_courscribe_delete_curriculum', 'courscribe_delete_curriculum_ajax');

function courscribe_create_curriculum_ajax() {
    check_ajax_referer('courscribe_curriculum', 'nonce');

    $current_user = wp_get_current_user();
    $title = sanitize_text_field($_POST['title'] ?? '');
    $topic = sanitize_text_field($_POST['topic'] ?? '');
    $goal = wp_kses_post($_POST['goal'] ?? '');
    $notes = wp_kses_post($_POST['notes'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'review', 'approved']) ? $_POST['status'] : 'draft';
    $studio_id = absint($_POST['studio_id'] ?? 0);

    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($topic)) $errors[] = 'Topic is required.';
    if (empty($studio_id)) $errors[] = 'Studio is required.';

    // Check for duplicate curriculum
    $existing_curriculum = get_posts(array(
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
    ));
    if (!empty($existing_curriculum)) {
        wp_send_json_error(['message' => 'A curriculum with this title already exists for this studio.']);
    }

    if (!empty($errors)) {
        wp_send_json_error(['message' => implode('<br>', $errors)]);
    }

    $post_data = array(
        'post_title' => $title,
        'post_type' => 'crscribe_curriculum',
        'post_status' => $status === 'published' ? 'publish' : 'draft',
        'post_author' => $current_user->ID,
        'meta_input' => array(
            '_curriculum_topic' => $topic,
            '_curriculum_goal' => $goal,
            '_curriculum_notes' => $notes,
            '_curriculum_status' => $status,
            '_studio_id' => $studio_id,
            '_creator_id' => $current_user->ID,
        ),
    );

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_curriculum_log',
        array(
            'curriculum_id' => $post_id,
            'user_id' => $current_user->ID,
            'action' => 'create',
            'changes' => wp_json_encode(array(
                'title' => ['new' => $title],
                'topic' => ['new' => $topic],
                'goal' => ['new' => $goal],
                'notes' => ['new' => $notes],
                'status' => ['new' => $status],
                'studio_id' => ['new' => $studio_id],
            )),
            'timestamp' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to log curriculum changes.']);
    }

    wp_send_json_success(['message' => 'Curriculum created successfully!', 'post_id' => $post_id, 'permalink' => get_permalink($post_id)]);
}

function courscribe_update_curriculum_ajax() {
    check_ajax_referer('courscribe_curriculum', 'nonce');

    $current_user = wp_get_current_user();
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);
    $title = sanitize_text_field($_POST['title'] ?? '');
    $topic = sanitize_text_field($_POST['topic'] ?? '');
    $goal = wp_kses_post($_POST['goal'] ?? '');
    $notes = wp_kses_post($_POST['notes'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'review', 'approved']) ? $_POST['status'] : 'draft';
    $studio_id = absint($_POST['studio_id'] ?? 0);

    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($topic)) $errors[] = 'Topic is required.';
    if (empty($studio_id)) $errors[] = 'Studio is required.';
    if (empty($curriculum_id)) $errors[] = 'Invalid curriculum ID.';

    if (!empty($errors)) {
        wp_send_json_error(['message' => implode('<br>', $errors)]);
    }

    $post_data = array(
        'ID' => $curriculum_id,
        'post_title' => $title,
        'post_type' => 'crscribe_curriculum',
        'post_status' => $status === 'published' ? 'publish' : 'draft',
        'post_author' => $current_user->ID,
        'meta_input' => array(
            '_curriculum_topic' => $topic,
            '_curriculum_goal' => $goal,
            '_curriculum_notes' => $notes,
            '_curriculum_status' => $status,
            '_studio_id' => $studio_id,
            '_creator_id' => get_post_meta($curriculum_id, '_creator_id', true),
        ),
    );

    $post_id = wp_update_post($post_data, true);
    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => $post_id->get_error_message()]);
    }

    global $wpdb;
    $changes = array(
        'title' => ['new' => $title, 'old' => get_post($curriculum_id)->post_title],
        'topic' => ['new' => $topic, 'old' => get_post_meta($curriculum_id, '_curriculum_topic', true)],
        'goal' => ['new' => $goal, 'old' => get_post_meta($curriculum_id, '_curriculum_goal', true)],
        'notes' => ['new' => $notes, 'old' => get_post_meta($curriculum_id, '_curriculum_notes', true)],
        'status' => ['new' => $status, 'old' => get_post_meta($curriculum_id, '_curriculum_status', true)],
        'studio_id' => ['new' => $studio_id, 'old' => get_post_meta($curriculum_id, '_studio_id', true)],
    );

    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_curriculum_log',
        array(
            'curriculum_id' => $curriculum_id,
            'user_id' => $current_user->ID,
            'action' => 'update',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to log curriculum changes.']);
    }

    wp_send_json_success(['message' => 'Curriculum updated successfully!', 'post_id' => $curriculum_id, 'permalink' => get_permalink($curriculum_id)]);
}

function courscribe_archive_curriculum_ajax() {
    check_ajax_referer('courscribe_archive_curriculum', 'courscribe_archive_nonce');

    $current_user = wp_get_current_user();
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);
    $curriculum = get_post($curriculum_id);

    if (!$curriculum || !($curriculum->post_author == $current_user->ID || in_array('studio_admin', $current_user->roles) || current_user_can('administrator'))) {
        wp_send_json_error(['message' => 'You do not have permission to archive this curriculum.']);
    }

    // Move to trash (archive)
    $archive_result = wp_trash_post($curriculum_id);
    if (!$archive_result) {
        wp_send_json_error(['message' => 'Failed to archive curriculum.']);
    }

    // Update status meta
    update_post_meta($curriculum_id, '_curriculum_status', 'archived');
    
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_curriculum_log',
        array(
            'curriculum_id' => $curriculum_id,
            'user_id' => $current_user->ID,
            'action' => 'archive',
            'changes' => wp_json_encode(['status' => 'archived']),
            'timestamp' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to log archive action.']);
    }

    wp_send_json_success(['message' => 'Curriculum archived successfully!']);
}

function courscribe_unarchive_curriculum_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_unarchive')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    $current_user = wp_get_current_user();
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);
    $curriculum = get_post($curriculum_id);

    if (!$curriculum || !($curriculum->post_author == $current_user->ID || in_array('studio_admin', $current_user->roles) || current_user_can('administrator'))) {
        wp_send_json_error(['message' => 'You do not have permission to restore this curriculum.']);
    }

    // Restore from trash
    $restore_result = wp_untrash_post($curriculum_id);
    if (!$restore_result) {
        wp_send_json_error(['message' => 'Failed to restore curriculum.']);
    }

    // Update status meta
    update_post_meta($curriculum_id, '_curriculum_status', 'active');
    
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_curriculum_log',
        array(
            'curriculum_id' => $curriculum_id,
            'user_id' => $current_user->ID,
            'action' => 'unarchive',
            'changes' => wp_json_encode(['status' => 'active']),
            'timestamp' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to log restore action.']);
    }

    wp_send_json_success(['message' => 'Curriculum restored successfully!']);
}

function courscribe_delete_curriculum_ajax() {
    check_ajax_referer('courscribe_delete_curriculum', 'nonce');

    $current_user = wp_get_current_user();
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);

    if (!(in_array('studio_admin', $current_user->roles) || current_user_can('administrator'))) {
        wp_send_json_error(['message' => 'You do not have permission to delete this curriculum.']);
    }

    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_curriculum_log',
        array(
            'curriculum_id' => $curriculum_id,
            'user_id' => $current_user->ID,
            'action' => 'delete',
            'changes' => wp_json_encode(['status' => 'deleted']),
            'timestamp' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s')
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to log delete action.']);
    }

    $delete_result = wp_delete_post($curriculum_id, true);
    if ($delete_result) {
        wp_send_json_success(['message' => 'Curriculum deleted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Error deleting curriculum.']);
    }
}

// AJAX handler for creating modules from new modal
add_action('wp_ajax_create_module_ajax', 'courscribe_create_module_ajax');
function courscribe_create_module_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_module')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    // Parse the serialized data
    parse_str($_POST['module_data'] ?? '', $data);
    
    $current_user = wp_get_current_user();
    $course_id = absint($data['course_id'] ?? 0);
    $module_name = sanitize_text_field($data['module_name'] ?? '');
    $module_goal = sanitize_text_field($data['module_goal'] ?? '');
    $objectives = $data['objectives'] ?? [];

    // Get course info
    $course = get_post($course_id);
    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = get_post_meta($course_id, '_studio_id', true);

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb'    => sanitize_text_field($objective['action_verb'] ?? ''),
            'description'    => sanitize_text_field($objective['description'] ?? ''),
        ];
    }

    // Validate
    $errors = [];
    if (empty($module_name)) {
        $errors[] = 'Module name is required.';
    }
    if (empty($module_goal)) {
        $errors[] = 'Module goal is required.';
    }
    if (empty($sanitized_objectives)) {
        $errors[] = 'At least one objective is required.';
    }
    if (!$course_id || !$course || $course->post_type !== 'crscribe_course') {
        $errors[] = 'Invalid course.';
    }

    // Check tier restrictions
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    $module_count = count(get_posts([
        'post_type' => 'crscribe_module',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_course_id',
                'value' => $course_id,
                'compare' => '=',
            ],
        ],
    ]));
    
    if ($tier === 'basics' && $module_count >= 5) {
        $errors[] = 'Your plan (Basics) allows only 5 modules per course. Upgrade to create more.';
    } elseif ($tier === 'plus' && $module_count >= 10) {
        $errors[] = 'Your plan (Plus) allows only 10 modules per course. Upgrade to Pro for unlimited.';
    }

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        ]);
        wp_die();
    }

    $post_data = [
        'post_title' => $module_name,
        'post_type' => 'crscribe_module',
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
        'meta_input' => [
            '_module_goal' => $module_goal,
            '_module_objectives' => maybe_serialize($sanitized_objectives),
            '_course_id' => $course_id,
            '_curriculum_id' => $curriculum_id,
            '_studio_id' => $studio_id,
            '_creator_id' => $current_user->ID,
        ],
    ];

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        wp_send_json_error([
            'message' => 'Error: ' . esc_html($post_id->get_error_message())
        ]);
        wp_die();
    }

    // Log action
    global $wpdb;
    $changes = [
        'title' => ['new' => $module_name],
        'goal' => ['new' => $module_goal],
        'objectives' => ['new' => $sanitized_objectives],
        'course_id' => ['new' => $course_id],
        'curriculum_id' => ['new' => $curriculum_id],
        'studio_id' => ['new' => $studio_id],
    ];

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $post_id,
            'user_id' => $current_user->ID,
            'action' => 'create',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => 'Module created successfully!',
        'module_id' => $post_id,
        'redirect_url' => $_SERVER['REQUEST_URI']
    ]);
    wp_die();
}

// AJAX handler for creating lessons from new modal
add_action('wp_ajax_create_lesson_ajax', 'courscribe_create_lesson_ajax');
function courscribe_create_lesson_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_lesson')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    // Parse the serialized data
    parse_str($_POST['lesson_data'] ?? '', $data);
    
    $current_user = wp_get_current_user();
    $course_id = absint($data['course_id'] ?? 0);
    $module_id = absint($data['module_id'] ?? 0);
    $lesson_name = sanitize_text_field($data['lesson_name'] ?? '');
    $lesson_goal = sanitize_text_field($data['lesson_goal'] ?? '');
    $objectives = $data['objectives'] ?? [];
    $activities = $data['activities'] ?? [];

    // Get course and module info
    $course = get_post($course_id);
    $module = get_post($module_id);
    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = get_post_meta($course_id, '_studio_id', true);

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb'    => sanitize_text_field($objective['action_verb'] ?? ''),
            'description'    => sanitize_text_field($objective['description'] ?? ''),
        ];
    }

    // Sanitize activities
    $sanitized_activities = [];
    foreach ($activities as $activity) {
        $sanitized_activities[] = [
            'type'         => sanitize_text_field($activity['type'] ?? ''),
            'title'        => sanitize_text_field($activity['title'] ?? ''),
            'instructions' => sanitize_textarea_field($activity['instructions'] ?? ''),
        ];
    }

    // Validate
    $errors = [];
    if (empty($lesson_name)) {
        $errors[] = 'Lesson name is required.';
    }
    if (empty($lesson_goal)) {
        $errors[] = 'Lesson goal is required.';
    }
    if (empty($sanitized_objectives)) {
        $errors[] = 'At least one objective is required.';
    }
    if (!$course_id || !$course || $course->post_type !== 'crscribe_course') {
        $errors[] = 'Invalid course.';
    }
    if (!$module_id || !$module || $module->post_type !== 'crscribe_module') {
        $errors[] = 'Invalid module.';
    }

    // Check tier restrictions
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    $lesson_count = count(get_posts([
        'post_type' => 'crscribe_lesson',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_module_id',
                'value' => $module_id,
                'compare' => '=',
            ],
        ],
    ]));
    
    if ($tier === 'basics' && $lesson_count >= 10) {
        $errors[] = 'Your plan (Basics) allows only 10 lessons per module. Upgrade to create more.';
    } elseif ($tier === 'plus' && $lesson_count >= 20) {
        $errors[] = 'Your plan (Plus) allows only 20 lessons per module. Upgrade to Pro for unlimited.';
    }

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        ]);
        wp_die();
    }

    $post_data = [
        'post_title' => $lesson_name,
        'post_type' => 'crscribe_lesson',
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
        'meta_input' => [
            '_lesson_goal' => $lesson_goal,
            '_lesson_objectives' => maybe_serialize($sanitized_objectives),
            '_lesson_activities' => maybe_serialize($sanitized_activities),
            '_course_id' => $course_id,
            '_module_id' => $module_id,
            '_curriculum_id' => $curriculum_id,
            '_studio_id' => $studio_id,
            '_creator_id' => $current_user->ID,
        ],
    ];

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        wp_send_json_error([
            'message' => 'Error: ' . esc_html($post_id->get_error_message())
        ]);
        wp_die();
    }

    // Log action
    global $wpdb;
    $changes = [
        'title' => ['new' => $lesson_name],
        'goal' => ['new' => $lesson_goal],
        'objectives' => ['new' => $sanitized_objectives],
        'activities' => ['new' => $sanitized_activities],
        'course_id' => ['new' => $course_id],
        'module_id' => ['new' => $module_id],
        'curriculum_id' => ['new' => $curriculum_id],
        'studio_id' => ['new' => $studio_id],
    ];

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_lesson_log',
        [
            'lesson_id' => $post_id,
            'user_id' => $current_user->ID,
            'action' => 'create',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => 'Lesson created successfully!',
        'lesson_id' => $post_id,
        'redirect_url' => $_SERVER['REQUEST_URI']
    ]);
    wp_die();
}

// AJAX handler for adding teaching points
add_action('wp_ajax_add_teaching_point', 'courscribe_add_teaching_point');
function courscribe_add_teaching_point() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_teaching_point_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $teaching_point = sanitize_text_field($_POST['teaching_point'] ?? '');

    // Validate inputs
    if (!$lesson_id || empty($teaching_point)) {
        wp_send_json_error(['message' => 'Invalid lesson ID or teaching point.']);
        wp_die();
    }

    // Check if lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    // Get existing teaching points
    $existing_points = get_post_meta($lesson_id, '_teaching_points', true) ?: [];
    if (!is_array($existing_points)) {
        $existing_points = [];
    }

    // Add new point
    $existing_points[] = $teaching_point;

    // Update meta
    $updated = update_post_meta($lesson_id, '_teaching_points', $existing_points);

    if ($updated !== false) {
        // Log action
        global $wpdb;
        $current_user = wp_get_current_user();
        $changes = [
            'action' => 'add_teaching_point',
            'teaching_point' => $teaching_point,
            'total_points' => count($existing_points)
        ];

        $wpdb->insert(
            $wpdb->prefix . 'courscribe_lesson_log',
            [
                'lesson_id' => $lesson_id,
                'user_id' => $current_user->ID,
                'action' => 'add_teaching_point',
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => 'Teaching point added successfully!',
            'teaching_points' => $existing_points,
            'point_index' => count($existing_points) - 1
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to add teaching point.']);
    }
    wp_die();
}

// AJAX handler for updating teaching points
add_action('wp_ajax_update_teaching_point', 'courscribe_update_teaching_point');
function courscribe_update_teaching_point() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_teaching_point_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $point_index = absint($_POST['point_index'] ?? -1);
    $teaching_point = sanitize_text_field($_POST['teaching_point'] ?? '');

    // Validate inputs
    if (!$lesson_id || $point_index < 0 || empty($teaching_point)) {
        wp_send_json_error(['message' => 'Invalid udate teaching point parameters.']);
        wp_die();
    }

    // Check if lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    // Get existing teaching points
    $existing_points = get_post_meta($lesson_id, '_teaching_points', true) ?: [];
    if (!is_array($existing_points) || !isset($existing_points[$point_index])) {
        wp_send_json_error(['message' => 'Teaching point not found.']);
        wp_die();
    }

    $old_point = $existing_points[$point_index];
    $existing_points[$point_index] = $teaching_point;

    // Update meta
    $updated = update_post_meta($lesson_id, '_teaching_points', $existing_points);

    if ($updated !== false) {
        // Log action
        global $wpdb;
        $current_user = wp_get_current_user();
        $changes = [
            'action' => 'update_teaching_point',
            'point_index' => $point_index,
            'old_value' => $old_point,
            'new_value' => $teaching_point
        ];

        $wpdb->insert(
            $wpdb->prefix . 'courscribe_lesson_log',
            [
                'lesson_id' => $lesson_id,
                'user_id' => $current_user->ID,
                'action' => 'update_teaching_point',
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => 'Teaching point updated successfully!',
            'teaching_points' => $existing_points
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update teaching point.']);
    }
    wp_die();
}

// AJAX handler for deleting teaching points
// AJAX handler for updating module fields (name and goal)
add_action('wp_ajax_update_module_field_old', 'courscribe_update_module_field_old');
function courscribe_update_module_field_old() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_module_field_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    $module_id = absint($_POST['module_id'] ?? 0);
    $field_type = sanitize_text_field($_POST['field_type'] ?? '');
    $field_value = sanitize_textarea_field($_POST['field_value'] ?? '');

    // Validate inputs
    if (!$module_id || !$field_type || !$field_value) {
        wp_send_json_error(['message' => 'Invalid old module field update parameters.']);
        wp_die();
    }

    // Check if module exists
    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    $updated = false;
    $old_value = '';

    switch ($field_type) {
        case 'name':
            $old_value = $module->post_title;
            $updated = wp_update_post([
                'ID' => $module_id,
                'post_title' => $field_value
            ]);
            break;
        case 'goal':
            $old_value = get_post_meta($module_id, '_module_goal', true);
            $updated = update_post_meta($module_id, '_module_goal', $field_value);
            break;
        default:
            wp_send_json_error(['message' => 'Invalid field type.']);
            wp_die();
    }

    if ($updated !== false && !is_wp_error($updated)) {
        // Log action
        global $wpdb;
        $current_user = wp_get_current_user();
        $changes = [
            'action' => 'update_field',
            'field_type' => $field_type,
            'old_value' => $old_value,
            'new_value' => $field_value
        ];

        $wpdb->insert(
            $wpdb->prefix . 'courscribe_module_log',
            [
                'module_id' => $module_id,
                'user_id' => $current_user->ID,
                'action' => 'update_field',
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => 'Module ' . $field_type . ' updated successfully!',
            'field_value' => $field_value
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update module ' . $field_type . '.']);
    }
    wp_die();
}

// AJAX handler for updating lesson fields (name and goal)
add_action('wp_ajax_update_lesson_field', 'courscribe_update_lesson_field');
function courscribe_update_lesson_field() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_lesson_field_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $field_type = sanitize_text_field($_POST['field_type'] ?? '');
    $field_value = sanitize_textarea_field($_POST['field_value'] ?? '');

    // Validate inputs
    if (!$lesson_id || !$field_type || !$field_value) {
        wp_send_json_error(['message' => 'Invalid lesson parameters.']);
        wp_die();
    }

    // Check if lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    $updated = false;
    $old_value = '';

    switch ($field_type) {
        case 'name':
            $old_value = $lesson->post_title;
            $updated = wp_update_post([
                'ID' => $lesson_id,
                'post_title' => $field_value
            ]);
            break;
        case 'goal':
            $old_value = get_post_meta($lesson_id, 'lesson-goal', true);
            $updated = update_post_meta($lesson_id, 'lesson-goal', $field_value);
            break;
        default:
            wp_send_json_error(['message' => 'Invalid field type.']);
            wp_die();
    }

    if ($updated !== false && !is_wp_error($updated)) {
        // Log action
        global $wpdb;
        $current_user = wp_get_current_user();
        $changes = [
            'action' => 'update_field',
            'field_type' => $field_type,
            'old_value' => $old_value,
            'new_value' => $field_value
        ];

        $wpdb->insert(
            $wpdb->prefix . 'courscribe_lesson_log',
            [
                'lesson_id' => $lesson_id,
                'user_id' => $current_user->ID,
                'action' => 'update_field',
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => 'Lesson ' . $field_type . ' updated successfully!',
            'field_value' => $field_value
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update lesson ' . $field_type . '.']);
    }
    wp_die();
}

add_action('wp_ajax_delete_teaching_point', 'courscribe_delete_teaching_point');
function courscribe_delete_teaching_point() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_teaching_point_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    $lesson_id = absint($_POST['lesson_id'] ?? 0);
    $point_index = absint($_POST['point_index'] ?? -1);

    // Validate inputs
    if (!$lesson_id || $point_index < 0) {
        wp_send_json_error(['message' => 'Invalid teaching points parameters.']);
        wp_die();
    }

    // Check if lesson exists
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    // Get existing teaching points
    $existing_points = get_post_meta($lesson_id, '_teaching_points', true) ?: [];
    if (!is_array($existing_points) || !isset($existing_points[$point_index])) {
        wp_send_json_error(['message' => 'Teaching point not found.']);
        wp_die();
    }

    $deleted_point = $existing_points[$point_index];
    array_splice($existing_points, $point_index, 1); // Remove the point

    // Update meta
    $updated = update_post_meta($lesson_id, '_teaching_points', $existing_points);

    if ($updated !== false) {
        // Log action
        global $wpdb;
        $current_user = wp_get_current_user();
        $changes = [
            'action' => 'delete_teaching_point',
            'point_index' => $point_index,
            'deleted_value' => $deleted_point,
            'remaining_points' => count($existing_points)
        ];

        $wpdb->insert(
            $wpdb->prefix . 'courscribe_lesson_log',
            [
                'lesson_id' => $lesson_id,
                'user_id' => $current_user->ID,
                'action' => 'delete_teaching_point',
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        wp_send_json_success([
            'message' => 'Teaching point deleted successfully!',
            'teaching_points' => $existing_points
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to delete teaching point.']);
    }
    wp_die();
}

