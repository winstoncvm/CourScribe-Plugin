<?php
// Path: courscribe/actions/courscribe-course-actions.php
if (!defined('ABSPATH')) {
    exit;
}

// Add Course to Curriculum  
add_action('wp_ajax_add_course_to_curriculum', 'add_course_to_curriculum');

// Create Course AJAX Handler (Production Ready)
add_action('wp_ajax_create_course_ajax', 'create_course_ajax_handler');
add_action('wp_ajax_nopriv_create_course_ajax', 'create_course_ajax_handler');

// Update Course AJAX Handler (Production Ready)
add_action('wp_ajax_update_course_ajax', 'update_course_ajax_handler');
add_action('wp_ajax_nopriv_update_course_ajax', 'update_course_ajax_handler');

// Archive Course AJAX Handler
add_action('wp_ajax_archive_course', 'archive_course_ajax_handler');

// Delete Course AJAX Handler
add_action('wp_ajax_delete_course', 'delete_course_ajax_handler');

// Course Logs AJAX Handler
add_action('wp_ajax_get_course_logs', 'get_course_logs_ajax_handler');

// Restore Course from Log AJAX Handler
add_action('wp_ajax_restore_course_from_log', 'restore_course_from_log_ajax_handler');
function add_course_to_curriculum() {
    check_ajax_referer('courscribe_course_nonce', 'security');

    $current_user = wp_get_current_user();
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $course_title = isset($_POST['course_title']) ? sanitize_text_field($_POST['course_title']) : '';
    $course_content = isset($_POST['course_content']) ? wp_kses_post($_POST['course_content']) : '';

    // Validate inputs
    if (!$curriculum_id || !$course_title) {
        wp_send_json_error(['message' => 'Curriculum ID and course title are required.']);
        return;
    }

    // Verify curriculum exists and user has access
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Invalid curriculum.']);
        return;
    }

    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    if (!current_user_can('read_crscribe_studio', $studio_id) || !current_user_can('edit_crscribe_courses')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    // Check tier restrictions
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    $course_count = count(get_posts([
        'post_type' => 'crscribe_course',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_curriculum_id',
                'value' => $curriculum_id,
                'compare' => '=',
            ],
        ],
    ]));

    if ($tier === 'basics' && $course_count >= 1) {
        wp_send_json_error(['message' => 'Your plan (Basics) allows only 1 course per curriculum. Upgrade to create more.']);
        return;
    } elseif ($tier === 'plus' && $course_count >= 2) {
        wp_send_json_error(['message' => 'Your plan (Plus) allows only 2 courses per curriculum. Upgrade to Pro for unlimited.']);
        return;
    }

    // Create course
    $new_course = [
        'post_title'   => $course_title,
        'post_content' => $course_content,
        'post_status'  => 'publish',
        'post_type'    => 'crscribe_course',
        'post_author'  => $current_user->ID,
        'meta_input'   => [
            '_curriculum_id' => $curriculum_id,
            '_studio_id'     => $studio_id,
            '_creator_id'    => $current_user->ID,
        ],
    ];

    $course_id = wp_insert_post($new_course, true);

    if (is_wp_error($course_id)) {
        wp_send_json_error(['message' => 'Failed to create course: ' . $course_id->get_error_message()]);
        return;
    }

    // Log action
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_course_log',
        [
            'course_id' => $course_id,
            'user_id'   => $current_user->ID,
            'action'    => 'create',
            'changes'   => wp_json_encode([
                'title'     => ['new' => $course_title],
                'content'   => ['new' => $course_content],
                'curriculum_id' => ['new' => $curriculum_id],
                'studio_id' => ['new' => $studio_id],
            ]),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Course created but failed to log action.']);
        return;
    }

    wp_send_json_success(['course_id' => $course_id, 'message' => 'Course added successfully.']);
}

// Update Objective Title (Removed since objectives are not a post type)
add_action('acf/save_post', 'update_objective_title', 20);
function update_objective_title($post_id) {
    // Deprecated: Objectives are now stored as meta, not a post type
    wp_send_json_error(['message' => 'Objective post type is deprecated.']);
}

// Update Course Order
add_action('wp_ajax_update_course_order', 'update_course_order_callback');
function update_course_order_callback() {
    check_ajax_referer('custom_nonce', 'security');
    $current_user = wp_get_current_user();
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $new_order = isset($_POST['order']) && is_array($_POST['order']) ? array_map('absint', $_POST['order']) : [];

    // Validate inputs
    if (!$curriculum_id || empty($new_order)) {
        wp_send_json_error(['message' => 'Missing curriculum ID or order data.']);
        return;
    }

    // Verify curriculum and permissions
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Invalid curriculum.']);
        return;
    }

    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    if (!current_user_can('read_crscribe_studio', $studio_id) || !current_user_can('edit_crscribe_courses')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    // Get current courses
    $current_courses = get_posts([
        'post_type' => 'crscribe_course',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_curriculum_id',
                'value' => $curriculum_id,
                'compare' => '=',
            ],
        ],
        'fields' => 'ids',
    ]);

    // Validate new order
    $valid_order = true;
    foreach ($new_order as $course_id) {
        if (!in_array($course_id, $current_courses)) {
            $valid_order = false;
            break;
        }
    }

    if (!$valid_order) {
        wp_send_json_error(['message' => 'Invalid course ID in new order.']);
        return;
    }

    // Update menu_order for each course
    foreach ($new_order as $index => $course_id) {
        wp_update_post([
            'ID' => $course_id,
            'menu_order' => $index,
        ]);
    }

    // Log action
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_course_log',
        [
            'course_id' => 0, // No single course ID for order update
            'user_id'   => $current_user->ID,
            'action'    => 'reorder',
            'changes'   => wp_json_encode([
                'curriculum_id' => $curriculum_id,
                'new_order'     => $new_order,
            ]),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Order updated but failed to log action.']);
        return;
    }

    wp_send_json_success(['message' => 'Course order updated successfully.']);
}




// Save New Course
add_action('wp_ajax_save_new_course', 'save_new_course');
function save_new_course() {
    //    if (!current_user_can('edit_crscribe_courses')) {
    //        wp_send_json_error(['message' => 'Permission denied.']);
    //        return;
    //    }

    $current_user = wp_get_current_user();
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $course_name = isset($_POST['course_name']) ? sanitize_text_field($_POST['course_name']) : '';
    $course_goal = isset($_POST['course_goal']) ? sanitize_text_field($_POST['course_goal']) : '';
    $level_of_learning = isset($_POST['level_of_learning']) ? sanitize_text_field($_POST['level_of_learning']) : '';
    $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];

    // Validate inputs
    if (!$curriculum_id || !$course_name || !$course_goal || !$level_of_learning) {
        wp_send_json_error(['message' => 'All fields are required.']);
        return;
    }

    // Verify curriculum and studio
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Invalid curriculum.']);
        return;
    }

    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    if (!current_user_can('read_crscribe_studio', $studio_id)) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

        // Check tier restrictions
    //    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    //    $course_count = count(get_posts([
    //        'post_type' => 'crscribe_course',
    //        'post_status' => 'publish',
    //        'meta_query' => [
    //            [
    //                'key' => '_curriculum_id',
    //                'value' => $curriculum_id,
    //                'compare' => '=',
    //            ],
    //        ],
    //    ]));
    //
    //    if ($tier === 'basics' && $course_count >= 1) {
    //        wp_send_json_error(['message' => 'Your plan (Basics) allows only 1 course per curriculum. Upgrade to create more.']);
    //        return;
    //    } elseif ($tier === 'plus' && $course_count >= 2) {
    //        wp_send_json_error(['message' => 'Your plan (Plus) allows only 2 courses per curriculum. Upgrade to Pro for unlimited.']);
    //        return;
    //    }

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb'    => sanitize_text_field($objective['action_verb'] ?? ''),
            'description'    => sanitize_text_field($objective['description'] ?? ''),
        ];
    }

    // Create course
    $course_id = wp_insert_post([
        'post_title'   => $course_name,
        'post_type'    => 'crscribe_course',
        'post_status'  => 'publish',
        'post_author'  => $current_user->ID,
        'meta_input'   => [
            '_class_goal'       => $course_goal,
            'level-of-learning' => $level_of_learning,
            '_course_objectives' => maybe_serialize($sanitized_objectives),
            '_curriculum_id'    => $curriculum_id,
            '_studio_id'        => $studio_id,
            '_creator_id'       => $current_user->ID,
        ],
    ], true);

    if (is_wp_error($course_id)) {
        wp_send_json_error(['message' => 'Failed to create course: ' . $course_id->get_error_message()]);
        return;
    }

    // Log action
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_course_log',
        [
            'course_id' => $course_id,
            'user_id'   => $current_user->ID,
            'action'    => 'create',
            'changes'   => wp_json_encode([
                'title'           => ['new' => $course_name],
                'goal'            => ['new' => $course_goal],
                'level_of_learning' => ['new' => $level_of_learning],
                'objectives'      => ['new' => $sanitized_objectives],
                'curriculum_id'   => ['new' => $curriculum_id],
                'studio_id'       => ['new' => $studio_id],
            ]),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Course created but failed to log action.']);
        return;
    }

    wp_send_json_success([
        'message'   => 'Course saved and associated successfully.',
        'course_id' => $course_id,
    ]);
}

add_action('wp_ajax_update_course', 'courscribe_handle_course_update');

function courscribe_handle_course_update() {
    if (!isset($_POST['courscribe_course_nonce']) || !wp_verify_nonce($_POST['courscribe_course_nonce'], 'courscribe_course')) {
        error_log('Courscribe: Course update failed - Invalid nonce');
        wp_send_json_error(['message' => '<div class="courscribe-error"><p>Security check failed. Please try again.</p></div>']);
        wp_die();
    }

    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (course update) - ' . print_r($_POST, true));

    $course_id = absint($_POST['course_id'] ?? 0);
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);
    $courses = isset($_POST['courses'][$course_id]) ? $_POST['courses'][$course_id] : [];
    $title = sanitize_text_field($courses['course_name'] ?? '');
    $goal = sanitize_text_field($courses['course_goal'] ?? '');
    $level_of_learning = sanitize_text_field($courses['level_of_learning'] ?? '');
    $objectives = isset($courses['objectives']) && is_array($courses['objectives']) ? $courses['objectives'] : [];
    $current_user = wp_get_current_user();

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $thinking_skill = sanitize_text_field($objective['thinking_skill'] ?? '');
        $action_verb = sanitize_text_field($objective['action_verb'] ?? '');
        $description = sanitize_text_field($objective['description'] ?? '');
        if (!empty($thinking_skill) && !empty($action_verb) && !empty($description)) {
            $sanitized_objectives[] = [
                'thinking_skill' => $thinking_skill,
                'action_verb' => $action_verb,
                'description' => $description
            ];
        }
    }

    // Validate input data
    $errors = [];
    if (empty($course_id) || !get_post($course_id) || get_post($course_id)->post_type !== 'crscribe_course') {
        $errors[] = 'Invalid course ID.';
    }
    if (empty($title)) {
        $errors[] = 'Course name is required.';
    }
    if (empty($goal)) {
        $errors[] = 'Course goal is required.';
    }
    if (empty($level_of_learning)) {
        $errors[] = 'Level of learning is required.';
    }
    if (empty($sanitized_objectives)) {
        $errors[] = 'At least one complete objective is required.';
    }
    if (empty($curriculum_id) || !get_post($curriculum_id) || get_post($curriculum_id)->post_type !== 'crscribe_curriculum') {
        $errors[] = 'Invalid curriculum.';
    }

    // Permission checks
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    if (!defined('COURSCRIBE_MVP_BYPASS_RESTRICTIONS') || !COURSCRIBE_MVP_BYPASS_RESTRICTIONS) {
        $creator_id = get_post_meta($studio_id, '_creator_id', true);
        $collaborators = get_post_meta($studio_id, '_collaborators', true) ?: [];
        $is_studio_member = ($studio_id && ($current_user->ID == $creator_id || in_array($current_user->ID, $collaborators)));
        if (!$studio_id || !$is_studio_member || !current_user_can('edit_crscribe_courses')) {
            $errors[] = 'Permission denied or invalid studio.';
        }
    }

    if (empty($errors)) {
        // Prepare post data
        $post_data = [
            'ID' => $course_id,
            'post_title' => $title,
            'post_type' => 'crscribe_course',
            'post_status' => 'publish',
            'post_author' => $current_user->ID,
            'meta_input' => [
                '_class_goal' => $goal,
                'level-of-learning' => $level_of_learning,
                'course_objectives' => maybe_serialize($sanitized_objectives),
                '_curriculum_id' => $curriculum_id,
                '_studio_id' => $studio_id,
                '_creator_id' => $current_user->ID
            ]
        ];

        // Update course
        $post_id = wp_update_post($post_data, true);
        if (!is_wp_error($post_id)) {
            // Log the action
            global $wpdb;
            $old_post = get_post($course_id);
            $changes = [
                'title' => ['old' => $old_post ? $old_post->post_title : '', 'new' => $title],
                'goal' => ['old' => get_post_meta($course_id, '_class_goal', true) ?: '', 'new' => $goal],
                'level_of_learning' => ['old' => get_post_meta($course_id, 'level-of-learning', true) ?: '', 'new' => $level_of_learning],
                'objectives' => ['old' => maybe_unserialize(get_post_meta($course_id, '_course_objectives', true)) ?: [], 'new' => $sanitized_objectives],
                'curriculum_id' => ['old' => get_post_meta($course_id, '_curriculum_id', true) ?: 0, 'new' => $curriculum_id],
                'studio_id' => ['old' => get_post_meta($course_id, '_studio_id', true) ?: 0, 'new' => $studio_id]
            ];

            $result = $wpdb->insert(
                $wpdb->prefix . 'courscribe_course_log',
                [
                    'course_id' => $post_id,
                    'user_id' => $current_user->ID,
                    'action' => 'update',
                    'changes' => wp_json_encode($changes),
                    'timestamp' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            if ($result === false) {
                error_log('Courscribe: Failed to log course update - Error: ' . $wpdb->last_error);
                wp_send_json_error(['message' => '<div class="courscribe-error"><p>Error: Failed to log course changes.</p></div>']);
            } else {
                error_log('Courscribe: Course update logged - Course ID: ' . $post_id);
                wp_send_json_success(['message' => '<div class="courscribe-success"><p>Course updated successfully!</p></div>']);
            }
        } else {
            error_log('Courscribe: Failed to update course - Error: ' . $post_id->get_error_message());
            wp_send_json_error(['message' => '<div class="courscribe-error"><p>Error: ' . esc_html($post_id->get_error_message()) . '</p></div>']);
        }
    } else {
        error_log('Courscribe: Course update validation errors - ' . print_r($errors, true));
        $message = '<div class="courscribe-error"><p>Please correct the following errors:</p><ul>';
        foreach ($errors as $error) {
            $message .= '<li>' . esc_html($error) . '</li>';
        }
        $message .= '</ul></div>';
        wp_send_json_error(['message' => $message]);
    }

    wp_die();
}

// Delete Objective
add_action('wp_ajax_delete_objective', 'delete_objective');
function delete_objective() {
    if (!current_user_can('edit_crscribe_courses')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    $current_user = wp_get_current_user();
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $objective_index = isset($_POST['objective_index']) ? absint($_POST['objective_index']) : -1;

    // Validate inputs
    if (!$course_id || $objective_index < 0) {
        wp_send_json_error(['message' => 'Invalid course ID or objective index.']);
        return;
    }

    // Verify course
    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'crscribe_course') {
        wp_send_json_error(['message' => 'Invalid course.']);
        return;
    }

    $studio_id = get_post_meta($course_id, '_studio_id', true);
    if (!current_user_can('read_crscribe_studio', $studio_id)) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    // Get objectives
    $objectives = maybe_unserialize(get_post_meta($course_id, '_course_objectives', true));
    if (!is_array($objectives) || !isset($objectives[$objective_index])) {
        wp_send_json_error(['message' => 'Objective not found.']);
        return;
    }

    // Remove objective
    $old_objective = $objectives[$objective_index];
    unset($objectives[$objective_index]);
    $objectives = array_values($objectives); // Reindex array
    update_post_meta($course_id, '_course_objectives', maybe_serialize($objectives));

    // Log action
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_course_log',
        [
            'course_id' => $course_id,
            'user_id'   => $current_user->ID,
            'action'    => 'delete_objective',
            'changes'   => wp_json_encode([
                'objective' => ['old' => $old_objective],
                'index'     => $objective_index,
            ]),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Objective deleted but failed to log action.']);
        return;
    }

    wp_send_json_success(['message' => 'Objective deleted successfully.']);
}

// Delete Course
add_action('wp_ajax_delete_course', 'handle_delete_course');
function handle_delete_course() {
    if (!current_user_can('delete_crscribe_courses')) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    $current_user = wp_get_current_user();
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;

    // Validate inputs
    if (!$course_id || !$curriculum_id) {
        wp_send_json_error(['message' => 'Invalid course or curriculum ID.']);
        return;
    }

    // Verify course and curriculum
    $course = get_post($course_id);
    $curriculum = get_post($curriculum_id);
    if (!$course || $course->post_type !== 'crscribe_course' || !$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Invalid course or curriculum.']);
        return;
    }

    $studio_id = get_post_meta($course_id, '_studio_id', true);
    if (!current_user_can('read_crscribe_studio', $studio_id)) {
        wp_send_json_error(['message' => 'Permission denied.']);
        return;
    }

    // Delete course
    $deleted = wp_delete_post($course_id, true);

    if (!$deleted) {
        wp_send_json_error(['message' => 'Failed to delete course.']);
        return;
    }

    // Log action
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_course_log',
        [
            'course_id' => $course_id,
            'user_id'   => $current_user->ID,
            'action'    => 'delete',
            'changes'   => wp_json_encode([
                'title'         => ['old' => $course->post_title],
                'curriculum_id' => ['old' => $curriculum_id],
                'studio_id'     => ['old' => $studio_id],
            ]),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Course deleted but failed to log action.']);
        return;
    }

    wp_send_json_success(['message' => 'Course deleted successfully.']);
}

add_action('wp_ajax_courscribe_get_course_logs', 'courscribe_get_course_logs');
function courscribe_get_course_logs() {
    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (get_course_logs) - ' . print_r($_POST, true));

    // Check nonce
    //    if (!check_ajax_referer('courscribe_nonce', 'security', false)) {
    //        error_log('Courscribe: Nonce verification failed - Security: ' . ($_POST['security'] ?? 'missing') . ', User ID: ' . get_current_user_id());
    //        wp_send_json_error(['message' => 'Security check failed. Please try again.']);
    //        wp_die();
    //    }

        // Check user permissions
    //    if (!current_user_can('edit_crscribe_courses') && !current_user_can('edit_posts')) {
    //        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to view course logs');
    //        wp_send_json_error(['message' => 'You are not allowed to view course logs.']);
    //        wp_die();
    //    }

    $course_id = absint($_POST['course_id'] ?? 0);
    if (!$course_id || !get_post($course_id) || get_post($course_id)->post_type !== 'crscribe_course') {
        error_log('Courscribe: Invalid course ID - Course ID: ' . $course_id);
        wp_send_json_error(['message' => 'Invalid course ID.']);
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_course_log';
    $logs = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE course_id = %d ORDER BY timestamp DESC", $course_id),
        ARRAY_A
    );

    if ($logs === false) {
        error_log('Courscribe: Error fetching logs - SQL Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Error fetching logs.']);
        wp_die();
    }

    error_log('Courscribe: Course logs fetched - Course ID: ' . $course_id . ', Log count: ' . count($logs));
    wp_send_json_success(['logs' => $logs]);
    wp_die();
}

function courscribe_generate_courses() {
    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (generate_courses) - ' . print_r($_POST, true));

    // Check nonce
    //    if (!check_ajax_referer('courscribe_nonce', 'nonce', false)) {
    //        error_log('Courscribe: Nonce verification failed - Nonce: ' . ($_POST['nonce'] ?? 'missing') . ', User ID: ' . get_current_user_id());
    //        wp_send_json_error(['message' => 'Security check failed. Please try again.']);
    //        wp_die();
    //    }
    //
    //    // Check permissions
    //    if (!current_user_can('edit_crscribe_courses') && !current_user_can('edit_posts')) {
    //        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to generate courses');
    //        wp_send_json_error(['message' => 'You are not allowed to generate courses.']);
    //        wp_die();
    //    }

    $curriculum_id = isset($_POST['curriculum_id']) ? intval($_POST['curriculum_id']) : 0;
    $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'Professional';
    $audience = isset($_POST['audience']) ? sanitize_text_field($_POST['audience']) : 'Adults';
    $course_count = isset($_POST['course_count']) ? intval($_POST['course_count']) : 1;
    $instructions = isset($_POST['instructions']) ? sanitize_textarea_field($_POST['instructions']) : '';

    // Validate inputs
    if (!$curriculum_id || !get_post($curriculum_id) || get_post($curriculum_id)->post_type !== 'crscribe_curriculum') {
        error_log('Courscribe: Invalid curriculum ID - Curriculum ID: ' . $curriculum_id);
        wp_send_json_error(['message' => 'Invalid curriculum ID']);
        wp_die();
    }
    if ($course_count < 1 || $course_count > 5) {
        error_log('Courscribe: Invalid course count - Count: ' . $course_count);
        wp_send_json_error(['message' => 'Course count must be between 1 and 5']);
        wp_die();
    }

    // Fetch curriculum data
    $curriculum_title = get_the_title($curriculum_id);
    $curriculum_goal = get_post_meta($curriculum_id, '_curriculum_goal', true) ?: 'No goal set';

    // Fetch existing courses
    $existing_courses = get_posts([
        'post_type' => 'crscribe_course',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query' => [
            [
                'key' => '_curriculum_id',
                'value' => $curriculum_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ],
    ]);

    // Build context for existing courses
    $existing_courses_context = '';
    if (!empty($existing_courses)) {
        $existing_courses_context = "Existing Courses:\n";
        foreach ($existing_courses as $index => $course) {
            $course_title = $course->post_title;
            $course_goal = get_post_meta($course->ID, '_course_goal', true) ?: 'No goal set';
            $existing_courses_context .= "Course " . ($index + 1) . "\nTitle: {$course_title}\nGoal: {$course_goal}\n\n";

            // Fetch modules for this course
            $modules = get_posts([
                'post_type' => 'crscribe_module',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => [
                    [
                        'key' => '_course_id',
                        'value' => $course->ID,
                        'compare' => '=',
                        'type' => 'NUMERIC',
                    ],
                ],
            ]);

            if (!empty($modules)) {
                $existing_courses_context .= "Modules for Course '{$course_title}':\n";
                foreach ($modules as $module_index => $module) {
                    $module_title = $module->post_title;
                    $module_goal = get_post_meta($module->ID, '_module_goal', true) ?: 'No goal set';
                    $existing_courses_context .= "Module " . ($module_index + 1) . "\nTitle: {$module_title}\nGoal: {$module_goal}\n";

                    // Fetch lessons for this module
                    $lessons = get_posts([
                        'post_type' => 'crscribe_lesson',
                        'post_status' => 'publish',
                        'numberposts' => -1,
                        'meta_query' => [
                            [
                                'key' => '_module_id',
                                'value' => $module->ID,
                                'compare' => '=',
                                'type' => 'NUMERIC',
                            ],
                        ],
                    ]);

                    if (!empty($lessons)) {
                        $existing_courses_context .= "Lessons:\n";
                        foreach ($lessons as $lesson_index => $lesson) {
                            $lesson_title = $lesson->post_title;
                            $lesson_goal = get_post_meta($lesson->ID, 'lesson-goal', true) ?: 'No goal set';
                            $existing_courses_context .= "- {$lesson_title}: {$lesson_goal}\n";
                        }
                    } else {
                        $existing_courses_context .= "No lessons.\n";
                    }
                    $existing_courses_context .= "\n";
                }
            } else {
                $existing_courses_context .= "No modules for Course '{$course_title}'.\n\n";
            }
        }
    } else {
        $existing_courses_context = "No existing courses.\n";
    }

    // Prepare the full context
    $context = "Curriculum Title: {$curriculum_title}\n";
    $context .= "Curriculum Goal: {$curriculum_goal}\n";
    $context .= $existing_courses_context;
    $context .= "Audience: {$audience}\n";
    $context .= "Tone: {$tone}\n";
    $context .= "Additional Instructions: {$instructions}\n";

    // Define thinking skills and action verbs
    $thinking_skills_action_verbs = [
        'Know' => ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
        'Comprehend' => ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
        'Apply' => ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
        'Analyze' => ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
        'Evaluate' => ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
        'Create' => ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
    ];

    $thinking_skills_prompt = "To create objectives, use the following structure: 'Thinking Skill to Action Verb Description'. Select a Thinking Skill from: " . implode(", ", array_keys($thinking_skills_action_verbs)) . ".\n";
    $thinking_skills_prompt .= "Then, select an appropriate Action Verb based on the chosen Thinking Skill:\n";
    foreach ($thinking_skills_action_verbs as $skill => $verbs) {
        $thinking_skills_prompt .= "- For '$skill', use one of: " . implode(", ", $verbs) . "\n";
    }
    $thinking_skills_prompt .= "Finally, add a concise Description that aligns with the course's goal, curriculum's goal, and audience.\n";

    // Prepare the prompt for Gemini
    $prompt = "You are an expert in educational content creation. Based on the following curriculum context, generate {$course_count} new course(s) for the curriculum that complement the existing courses and align with the curriculum's goal. Each course should include:\n";
    $prompt .= "- Title: A concise, relevant course title (do not repeat existing course titles).\n";
    $prompt .= "- Goal: A short goal (1-2 sentences) that fits within the curriculum's overall goal.\n";
    $prompt .= "- Level: Select one of Beginner, Intermediate, Advanced.\n";
    $prompt .= "- Objectives: A list of 2-3 objectives in the format 'Thinking Skill to Action Verb Description', following the structure below.\n\n";
    $prompt .= $thinking_skills_prompt . "\n";
    $prompt .= $context . "\n";
    $prompt .= "Return the response in the following format for each course:\n";
    $prompt .= "Course [Number]\nTitle: [Course Title]\nGoal: [Course Goal]\nLevel: [Level]\nObjectives:\n- [Objective 1]\n- [Objective 2]\n- [Objective 3]\n\n";
    $prompt .= "Ensure the courses are suitable for the audience and tone specified, and avoid duplicating existing courses.";

    // Send to Gemini API
    try {
        $api_key = 'AIzaSyBB5ZYwktOFI3R3j_vs8U7CxwKgS3XNgM0';
        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('Courscribe: Gemini API Error - ' . $response->get_error_message());
            wp_send_json_error(['message' => 'Failed to generate courses']);
            wp_die();
        }

        $body = json_decode($response['body'], true);
        if (isset($body['error'])) {
            error_log('Courscribe: Gemini API Error - ' . $body['error']['message']);
            wp_send_json_error(['message' => 'Failed to generate courses']);
            wp_die();
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('Courscribe: Gemini API Invalid Response Structure');
            wp_send_json_error(['message' => 'Invalid response from API']);
            wp_die();
        }

        $response_text = $body['candidates'][0]['content']['parts'][0]['text'];

        // Parse the response into an array of courses
        $courses = [];
        $course_blocks = explode("Course ", trim($response_text));
        foreach ($course_blocks as $block) {
            if (empty($block)) continue;

            $lines = explode("\n", $block);
            $course = [
                'title' => '',
                'goal' => '',
                'level' => '',
                'objectives' => []
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'Title:') === 0) {
                    $course['title'] = trim(substr($line, 6));
                } elseif (strpos($line, 'Goal:') === 0) {
                    $course['goal'] = trim(substr($line, 5));
                } elseif (strpos($line, 'Level:') === 0) {
                    $course['level'] = trim(substr($line, 6));
                } elseif (strpos($line, '- ') === 0 && !empty($course['title'])) {
                    $course['objectives'][] = trim(substr($line, 2));
                }
            }

            if (!empty($course['title'])) {
                $courses[] = $course;
            }
        }

        error_log('Courscribe: Courses generated successfully - Count: ' . count($courses));
        wp_send_json_success(['courses' => $courses]);
    } catch (Exception $e) {
        error_log('Courscribe: Gemini API Exception - ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to generate courses']);
        }
    wp_die();
}

add_action('wp_ajax_courscribe_generate_courses', 'courscribe_generate_courses');
/**
 * AJAX handler to check RichTextEditor content
 */
function courscribe_check_richtexteditor_content()
{
    //check_ajax_referer('courscribe_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    if (!$course_id || get_post_type($course_id) !== 'crscribe_course') {
        wp_send_json_error(['message' => 'Invalid course ID']);
        wp_die();
    }

    $content = get_post_meta($course_id, '_courscribe_richtexteditor_content', true);
    wp_send_json_success([
        'exists' => !empty($content),
        'content' => $content ?: ''
    ]);
    wp_die();
}

add_action('wp_ajax_courscribe_check_richtexteditor', 'courscribe_check_richtexteditor_content');

/**
 * AJAX handler to fetch course data
 */
function courscribe_get_the_course_data()
{
   // check_ajax_referer('courscribe_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    if (!$course_id || get_post_type($course_id) !== 'crscribe_course') {
        error_log('CourScribe: Invalid course ID ' . $course_id . ' in courscribe_get_the_course_data');
        wp_send_json_error(['message' => 'Invalid course ID']);
        wp_die();
    }

    $current_user = wp_get_current_user();
    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = $curriculum_id ? get_post_meta($curriculum_id, '_studio_id', true) : 0;

    // Permission check (bypassed for MVP)
    if (!defined('COURSCRIBE_MVP_BYPASS_RESTRICTIONS') || !COURSCRIBE_MVP_BYPASS_RESTRICTIONS) {
        $creator_id = $studio_id ? get_post_meta($studio_id, '_creator_id', true) : 0;
        $collaborators = $studio_id ? get_post_meta($studio_id, '_collaborators', true) ?: [] : [];
        $is_studio_member = ($studio_id && ($current_user->ID == $creator_id || in_array($current_user->ID, $collaborators)));
        if (!$is_studio_member || !current_user_can('edit_crscribe_courses')) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'courscribe_course_log',
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'permission_denied',
                    'changes' => wp_json_encode(['message' => 'User ' . $current_user->user_login . ' denied access to course ' . $course_id]),
                    'timestamp' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
            error_log('CourScribe: Permission denied for user ' . $current_user->ID . ' on course ' . $course_id);
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }
    }

    $course = get_post($course_id);
    $data = [
        'title' => $course->post_title ?: 'Untitled Course',
        'goal' => get_post_meta($course_id, '_class_goal', true) ?: 'Not set',
        'level' => get_post_meta($course_id, 'level-of-learning', true) ?: 'Not set',
        'objectives' => maybe_unserialize(get_post_meta($course_id, '_course_objectives', true)) ?: [],
        'modules' => []
    ];

    // Fetch modules
    $module_args = [
        'post_type' => 'crscribe_module',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_course_id',
                'value' => $course_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1
    ];
    $module_query = new WP_Query($module_args);
    while ($module_query->have_posts()) {
        $module_query->the_post();
        $module_id = get_the_ID();
        $module_data = [
            'id' => $module_id,
            'title' => get_the_title() ?: 'Untitled Module',
            'goal' => get_post_meta($module_id, '_module_goal', true) ?: 'Not set',
            'objectives' => maybe_unserialize(get_post_meta($module_id, '_module_objectives', true)) ?: [],
            'methods' => maybe_unserialize(get_post_meta($module_id, '_module_methods', true)) ?: [],
            'materials' => maybe_unserialize(get_post_meta($module_id, '_module_materials', true)) ?: [],
            'activities' => maybe_unserialize(get_post_meta($module_id, '_module_activities', true)) ?: [],
            'lessons' => []
        ];

        // Fetch lessons
        $lesson_args = [
            'post_type' => 'crscribe_lesson',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_module_id',
                    'value' => $module_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ];
        $lesson_query = new WP_Query($lesson_args);
        while ($lesson_query->have_posts()) {
            $lesson_query->the_post();
            $lesson_id = get_the_ID();
            $module_data['lessons'][] = [
                'id' => $lesson_id,
                'title' => get_the_title() ?: 'Untitled Lesson',
                'goal' => get_post_meta($lesson_id, '_lesson_goal', true) ?: 'Not set',
                'objectives' => maybe_unserialize(get_post_meta($lesson_id, '_lesson_objectives', true)) ?: [],
                'teaching_points' => maybe_unserialize(get_post_meta($lesson_id, '_teaching_points', true)) ?: []
            ];
        }
        wp_reset_postdata();
        $data['modules'][] = $module_data;
    }
    wp_reset_postdata();

    wp_send_json_success($data);
    wp_die();
}

add_action('wp_ajax_courscribe_get_the_course_data', 'courscribe_get_the_course_data');

/**
 * AJAX handler to save RichTextEditor content
 */
function courscribe_save_richtexteditor_content()
{
    check_ajax_referer('courscribe_nonce', 'nonce');

    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

    if (!$course_id || get_post_type($course_id) !== 'crscribe_course' || empty($content)) {
        error_log('CourScribe: Invalid course ID or content in courscribe_save_richtexteditor_content');
        wp_send_json_error(['message' => 'Invalid course ID or content']);
        wp_die();
    }

    $current_user = wp_get_current_user();
    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = $curriculum_id ? get_post_meta($curriculum_id, '_studio_id', true) : 0;

    // Permission check (bypassed for MVP)
    if (!defined('COURSCRIBE_MVP_BYPASS_RESTRICTIONS') || !COURSCRIBE_MVP_BYPASS_RESTRICTIONS) {
        $creator_id = $studio_id ? get_post_meta($studio_id, '_creator_id', true) : 0;
        $collaborators = $studio_id ? get_post_meta($studio_id, '_collaborators', true) ?: [] : [];
        $is_studio_member = ($studio_id && ($current_user->ID == $creator_id || in_array($current_user->ID, $collaborators)));
        if (!$is_studio_member || !current_user_can('edit_crscribe_courses')) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'courscribe_course_log',
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'permission_denied',
                    'changes' => wp_json_encode(['message' => 'User ' . $current_user->user_login . ' denied access to save content for course ' . $course_id]),
                    'timestamp' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
            error_log('CourScribe: Permission denied for user ' . $current_user->ID . ' on course ' . $course_id);
            wp_send_json_error(['message' => 'Permission denied']);
            wp_die();
        }
    }

    $old_content = get_post_meta($course_id, '_courscribe_richtexteditor_content', true);
    $updated = update_post_meta($course_id, '_courscribe_richtexteditor_content', $content);

    if ($updated) {
        global $wpdb;
        $changes = [
            'richtexteditor_content' => [
                'new' => $content,
                'old' => $old_content ?: 'Not set'
            ]
        ];

        $result = $wpdb->insert(
            $wpdb->prefix . 'courscribe_course_log',
            [
                'course_id' => $course_id,
                'user_id' => $current_user->ID,
                'action' => 'update_richtexteditor',
                'changes' => wp_json_encode($changes),
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            error_log('CourScribe: Failed to log richtexteditor update for course ' . $course_id);
            wp_send_json_error(['message' => 'Content saved, but failed to log changes']);
        } else {
            wp_send_json_success(['message' => 'Content saved successfully']);
        }
    } else {
        wp_send_json_error(['message' => 'No changes detected or error saving content']);
    }
    wp_die();
}

add_action('wp_ajax_courscribe_save_richtexteditor', 'courscribe_save_richtexteditor_content');

// Use statements for PHPPresentation
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Shape\Line;

function create_revealjs_preview($course_data, $course_id) {
    // Helper function to polish text with Gemini (already defined)
    if (!function_exists('polish_text_with_gemini')) {
        function polish_text_with_gemini($text, $context, $stage, $name, $goal, $course_data) {
            // Simulated response since we can't call the Gemini API directly
            return [
                'slideText' => $text,
                'slideNotes' => "Presenter notes for {$stage}: {$name}. Encourage engagement."
            ];
        }
    }

    // Start building the Reveal.js HTML content
    $html = <<<EOD
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

        <title>Course Preview: {$course_data['title']}</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/dist/reset.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/dist/reveal.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/dist/theme/black.css">

        <!-- Theme used for syntax highlighted code -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/plugin/highlight/monokai.css">

        <!-- Custom CSS to match CourScribe PowerPoint styling -->
        <style>
            .reveal .slides {
                font-family: Arial, sans-serif;
            }

            /* Title Slide Styling */
            .reveal .title-slide {
                background-color: #FFF2CC !important;
                text-align: center;
                padding-top: 50px;
            }
            .reveal .title-slide h1 {
                font-size: 48px;
                font-weight: bold;
                color: #231F20;
                margin-bottom: 20px;
            }
            .reveal .title-slide h2 {
                font-size: 35px;
                color: #231F20;
            }

            /* Content Slide Styling */
            .reveal .content-slide {
                background-color: #FFFFFF !important;
                position: relative;
                text-align: left;
            }
            .reveal .content-slide h3 {
                font-size: 33px;
                font-weight: bold;
                color: #231F20;
                position: absolute;
                left: 50px;
                top: 30px;
            }
            .reveal .content-slide .divider {
                position: absolute;
                left: 50px;
                top: 90px;
                width: 860px;
                height: 4px;
                background-color: #000000;
            }
            .reveal .content-slide .content {
                font-size: 20px;
                color: #231F20;
                position: absolute;
                left: 50px;
                top: 120px;
                white-space: pre-wrap;
            }
            .reveal .content-slide .top-right-info {
                position: absolute;
                right: 20px;
                top: 10px;
                font-size: 14px;
                color: #231F20;
            }

            /* Ensure Reveal.js notes match PowerPoint notes styling */
            .reveal .speaker-notes {
                font-size: 12px;
                color: #231F20;
            }
        </style>
    </head>
    <body>
        <div class="reveal">
            <div class="slides">
EOD;

    // Title Slide
    $html .= <<<EOD
                <!-- Title Slide -->
                <section class="title-slide">
                    <h1>{$course_data['title']}</h1>
                    <h2>Course Preview</h2>
                </section>
EOD;

    // Welcome Slide
    $welcome_text = "Welcome to {$course_data['title']}!\nGet ready to explore this course.\nExpect engaging content and activities.\nTake notes and participate actively.";
    $polished = polish_text_with_gemini(
        $welcome_text,
        "educational slide for a course welcome",
        "course",
        $course_data['title'],
        $course_data['goal'],
        $course_data
    );
    $html .= <<<EOD
                <!-- Welcome Slide -->
                <section class="content-slide" data-notes="{$polished['slideNotes']}">
                    <div class="top-right-info">{$course_data['title']}</div>
                    <h3>Welcome</h3>
                    <div class="divider"></div>
                    <div class="content">
{$polished['slideText']}
                    </div>
                </section>
EOD;

    // Purpose Slide
    $purpose_text = "Goal: {$course_data['goal']}\nLevel: {$course_data['level']}";
    $purpose_text .= $course_data['objectives'] ? "\nObjectives:\n" . implode("\n", array_map(function($obj) {
            return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
        }, $course_data['objectives'])) : '';
    $polished = polish_text_with_gemini(
        $purpose_text,
        "educational slide for a course purpose",
        "course",
        $course_data['title'],
        $course_data['goal'],
        $course_data
    );
    $html .= <<<EOD
                <!-- Purpose Slide -->
                <section class="content-slide" data-notes="{$polished['slideNotes']}">
                    <div class="top-right-info">{$course_data['title']}</div>
                    <h3>Course Purpose</h3>
                    <div class="divider"></div>
                    <div class="content">
{$polished['slideText']}
                    </div>
                </section>
EOD;

    // Module and Lesson Slides
    if (!empty($course_data['modules'])) {
        foreach ($course_data['modules'] as $module_index => $module) {
            $module_title = $module['title'];
            // Module Overview Slide
            $module_text = "Goal: {$module['goal']}\n";
            $module_text .= !empty($module['lessons']) ? "Lessons:\n" . implode("\n", array_map(function($lesson) {
                    return "- {$lesson['title']}";
                }, $module['lessons'])) : "No lessons yet.";
            $module_text .= $module['objectives'] ? "\n\nObjectives:\n" . implode("\n", array_map(function($obj) {
                    return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
                }, $module['objectives'])) : '';
            $polished = polish_text_with_gemini(
                $module_text,
                "educational slide for a module overview",
                "module",
                $module_title,
                $module['goal'],
                $course_data
            );
            $module_number = $module_index + 1;
            $html .= <<<EOD
                <!-- Module {$module_number} Overview Slide -->
                <section class="content-slide" data-notes="{$polished['slideNotes']}">
                    <div class="top-right-info">{$course_data['title']} | Module {$module_number}</div>
                    <h3>Module {$module_number}: {$module_title}</h3>
                    <div class="divider"></div>
                    <div class="content">
{$polished['slideText']}
                    </div>
                </section>
EOD;

            // Lesson Slides
            if (!empty($module['lessons'])) {
                foreach ($module['lessons'] as $lesson_index => $lesson) {
                    $lesson_title = $lesson['title'];
                    $lesson_text = "Goal: {$lesson['goal']}\n";
                    $lesson_text .= $lesson['objectives'] ? "Objectives:\n" . implode("\n", array_map(function($obj) {
                            return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
                        }, $lesson['objectives'])) : "No objectives set.";
                    $polished = polish_text_with_gemini(
                        $lesson_text,
                        "educational slide for a lesson introduction",
                        "lesson",
                        $lesson_title,
                        $lesson['goal'],
                        $course_data
                    );
                    $lesson_number = $lesson_index + 1;
                    $html .= <<<EOD
                <!-- Module {$module_number} Lesson {$lesson_number} Slide -->
                <section class="content-slide" data-notes="{$polished['slideNotes']}">
                    <div class="top-right-info">{$course_data['title']} | Module {$module_number} | Lesson {$lesson_number}</div>
                    <h3>Lesson {$lesson_number}: {$lesson_title}</h3>
                    <div class="divider"></div>
                    <div class="content">
{$polished['slideText']}
                    </div>
                </section>
EOD;
                }
            }
        }
    }

    // Close the HTML
    $html .= <<<EOD
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/dist/reveal.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/plugin/notes/notes.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/plugin/markdown/markdown.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/reveal.js@4.5.0/plugin/highlight/highlight.js"></script>
        <script>
            Reveal.initialize({
                hash: true,
                width: 960,
                height: 720,
                plugins: [ RevealMarkdown, RevealHighlight, RevealNotes ]
            });
        </script>
    </body>
</html>
EOD;

    return $html;
}

// Register AJAX handler
add_action('wp_ajax_generate_test_slide', 'courscribe_generate_test_slide');
function courscribe_generate_test_slide() {
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    if (!$course_id) {
        wp_send_json_error(['message' => 'Invalid course ID']);
        wp_die();
    }

    // Check the current number of slide decks
    $slide_decks = get_post_meta($course_id, '_courscribe_slide_decks', true);
    if (!is_array($slide_decks)) {
        $slide_decks = [];
    }

    $max_decks = 4; // Updated to limit to 4 slide decks
    $current_count = count($slide_decks);

    // If the limit is reached, remove the oldest entry
    if ($current_count >= $max_decks) {
        $oldest_deck = array_shift($slide_decks);
        // Delete the oldest files from the server
        if (!empty($oldest_deck['ppt_url'])) {
            $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $oldest_deck['ppt_url']);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        if (!empty($oldest_deck['reveal_url'])) {
            $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $oldest_deck['reveal_url']);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    try {
        $presentation = new PhpPresentation();
        $presentation->getDocumentProperties()
            ->setCreator('CourScribe')
            ->setTitle('Course Slide Deck - ' . get_the_title($course_id));

        // Fetch course data
        $course_data = get_course_data($course_id);

        // Course Slides
        create_course_slides($presentation, $course_data);


        // Module and Lesson Slides
        foreach ($course_data['modules'] as $module) {
            create_module_slides($presentation, $course_data['title'], $module);
            foreach ($module['lessons'] as $lesson) {
                create_lesson_slides($presentation, $course_data['title'], $module['title'], $lesson);
            }
        }

        // Save the file
        $upload_dir = wp_upload_dir();
        $courscribe_dir = $upload_dir['basedir'] . '/courscribe-slides';
        if (!file_exists($courscribe_dir)) {
            mkdir($courscribe_dir, 0755, true);
        }

        if (!is_writable($upload_dir['basedir']) || !is_writable($courscribe_dir)) {
            error_log('CourScribe: Uploads directory not writable at ' . $upload_dir['basedir']);
            wp_send_json_error(['message' => 'Cannot write to uploads directory']);
            wp_die();
        }

        // Add a timestamp to the filename to avoid overwriting
        $timestamp = current_time('YmdHis');
        $course_name = get_the_title($course_id);
        $sanitized_course_name = sanitize_title_with_dashes($course_name);

        $ppt_file_path = $upload_dir['path'] . '/course-slide-deck-' . $sanitized_course_name . '-' . $timestamp . '.pptx';
        $ppt_file_url = $upload_dir['url'] . '/course-slide-deck-' . $sanitized_course_name . '-' . $timestamp . '.pptx';

        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($ppt_file_path);

        // Generate Reveal.js preview
        $reveal_html = create_revealjs_preview($course_data, $course_id);
        $reveal_filename = 'course-' . $sanitized_course_name . '-' . $timestamp . '-preview.html';
        $reveal_filepath = $courscribe_dir . '/' . $reveal_filename;
        $reveal_file_url = $upload_dir['baseurl'] . '/courscribe-slides/' . $reveal_filename;
        file_put_contents($reveal_filepath, $reveal_html);

        if (file_exists($ppt_file_path) && file_exists($reveal_filepath)) {
            // Add the new slide deck to the array
            $slide_decks[] = [
                'ppt_url' => $ppt_file_url,
                'reveal_url' => $reveal_file_url,
                'date' => current_time('Y-m-d H:i:s')
            ];

            // Update the post meta
            update_post_meta($course_id, '_courscribe_slide_decks', $slide_decks);

            wp_send_json_success([
                'ppt_url' => $ppt_file_url,
                'reveal_url' => $reveal_file_url,
                'remaining_generations' => max(0, $max_decks - count($slide_decks))
            ]);
        } else {
            error_log('CourScribe: Failed to save file at ' . $ppt_file_path);
            wp_send_json_error(['message' => 'Failed to save the slide deck']);
        }
    } catch (Exception $e) {
        error_log('CourScribe: Error generating slide deck - ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error generating slide deck: ' . $e->getMessage()]);
    }

    wp_die();
}

// Fetch course, module, and lesson data
function get_course_data($course_id) {
    $course = get_post($course_id);
    $data = [
        'title' => $course->post_title,
        'goal' => get_post_meta($course_id, '_class_goal', true) ?: 'Not set',
        'level' => get_post_meta($course_id, '_level-of-learning', true) ?: 'Not set',
        'objectives' => maybe_unserialize(get_post_meta($course_id, '_course_objectives', true)) ?: [],
        'modules' => []
    ];

    // Fetch modules
    $module_ids = get_post_meta($course_id, 'modules', true) ?: [];
    foreach ($module_ids as $module_id) {
        $module = get_post($module_id);
        $module_data = [
            'id' => $module_id,
            'title' => $module->post_title,
            'goal' => get_post_meta($module_id, 'module-goal', true) ?: 'Not set',
            'objectives' => maybe_unserialize(get_post_meta($module_id, '_module_objectives', true)) ?: [],
            'methods' => maybe_unserialize(get_post_meta($module_id, '_module_methods', true)) ?: [],
            'materials' => maybe_unserialize(get_post_meta($module_id, '_module_materials', true)) ?: [],
            'activities' => maybe_unserialize(get_post_meta($module_id, '_module_activities', true)) ?: [],
            'lessons' => []
        ];

        // Fetch lessons
        $lesson_ids = get_post_meta($module_id, 'lessons', true) ?: [];
        foreach ($lesson_ids as $lesson_id) {
            $lesson = get_post($lesson_id);
            $module_data['lessons'][] = [
                'id' => $lesson_id,
                'title' => $lesson->post_title,
                'goal' => get_post_meta($lesson_id, 'lesson-goal', true) ?: 'Not set',
                'objectives' => maybe_unserialize(get_post_meta($lesson_id, '_lesson_objectives', true)) ?: [],
                'teaching_points' => maybe_unserialize(get_post_meta($lesson_id, '_teaching_points', true)) ?: []
            ];
        }
        $data['modules'][] = $module_data;
    }
    return $data;
}

function polish_text_with_gemini($text, $context = 'educational slide', $stage = '', $name = '', $goal = '', $course_data = []) {
    try {
        $api_key = get_option('courscribe_gemini_api_key', ''); // Securely retrieve API key
        if (empty($api_key)) {
            error_log('CourScribe: Gemini API key not configured');
            return ['slideText' => $text, 'slideNotes' => ''];
        }
        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        // Enhanced prompt for better content polishing
        $prompt = "You are an expert instructional designer specializing in creating engaging educational slides. Below is the context for a {$context} in a course:\n\n";
        $prompt .= "Stage: {$stage}\nName: {$name}\nGoal: {$goal}\n";
        $prompt .= "Course Overview:\n- Title: " . ($course_data['title'] ?? 'Not set') . "\n";
        $prompt .= "- Goal: " . ($course_data['goal'] ?? 'Not set') . "\n";
        $prompt .= "- Level: " . ($course_data['level'] ?? 'Not set') . "\n";
        $prompt .= "- Modules: " . (isset($course_data['modules']) ? implode(", ", array_column($course_data['modules'], 'title')) : 'None') . "\n\n";
        $prompt .= "Rewrite the following text to be engaging, concise, and professional, suitable for a single PowerPoint slide. Ensure an educational tone, clear language, and alignment with the course context. ";
        $prompt .= "The slide text should be brief (4-6 bullet points or 3-5 sentences, max 100 words), visually appealing for learners, and avoid jargon unless appropriate for the course level. ";
        $prompt .= "The presenter notes should provide actionable guidance (2-4 sentences, max 150 words) to help the instructor deliver the slide effectively, including tips for engagement or emphasis.\n\n";
        $prompt .= "Text to rewrite:\n{$text}\n\n";
        $prompt .= "Return the response in this exact format:\nslideText: [Your concise slide text]\nslideNotes: [Your presenter notes]";

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('Gemini API Error: ' . $response->get_error_message());
            return ['slideText' => $text, 'slideNotes' => ''];
        }

        $body = json_decode($response['body'], true);
        if (isset($body['error'])) {
            error_log('Gemini API Error: ' . $body['error']['message']);
            return ['slideText' => $text, 'slideNotes' => ''];
        }

        $response_text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (empty($response_text)) {
            error_log('Gemini API: Empty response');
            return ['slideText' => $text, 'slideNotes' => ''];
        }

        // Parse response
        $slide_text = $text;
        $slide_notes = '';
        if (preg_match('/slideText: (.*?)\nslideNotes: (.*)/s', $response_text, $matches)) {
            $slide_text = trim($matches[1]);
            $slide_notes = trim($matches[2]);
        }

        return [
            'slideText' => $slide_text,
            'slideNotes' => $slide_notes
        ];
    } catch (Exception $e) {
        error_log('Gemini API Exception: ' . $e->getMessage());
        return ['slideText' => $text, 'slideNotes' => ''];
    }
}

// Helper function for top-right info
function add_top_right_info($slide, $course_title, $module_title = '', $lesson_title = '') {
    $info_text = $course_title;
    if ($module_title) {
        $info_text .= " | " . $module_title;
    }
    if ($lesson_title) {
        $info_text .= " | " . $lesson_title;
    }
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(300)
        ->setOffsetX(650)
        ->setOffsetY(10);
    $shape->getActiveParagraph()->getAlignment()->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_RIGHT);
    $textRun = $shape->createTextRun($info_text);
    $textRun->getFont()->setSize(14)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
}

// Course Slides
function create_course_slides($presentation, $course_data) {
    // Title Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getActiveParagraph()->getAlignment()
        ->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpPresentation\Style\Alignment::VERTICAL_CENTER);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFF2CC');
    $shape = $slide->createRichTextShape()
        ->setHeight(100)
        ->setWidth(600)
        ->setOffsetX(180)
        ->setOffsetY(150);
    $shape->getActiveParagraph()->getAlignment()->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER);
    $textRun = $shape->createTextRun($course_data['title']);
    $textRun->getFont()->setSize(48)->setBold(true)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(600)
        ->setOffsetX(180)
        ->setOffsetY(300);
    $shape->getActiveParagraph()->getAlignment()->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER);
    $textRun = $shape->createTextRun('Course Overview');
    $textRun->getFont()->setSize(35)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));

    // Welcome Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_data['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Welcome to ' . $course_data['title']);
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $text = "Welcome to {$course_data['title']}!\nThis course introduces key concepts to spark curiosity.\nExpect interactive content and practical activities.\nPrepare by taking notes and engaging actively.";
    $polished = polish_text_with_gemini($text, "course welcome slide", "course", $course_data['title'], $course_data['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Purpose Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_data['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Course Purpose');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $text = "Goal: {$course_data['goal']}\nLevel: {$course_data['level']}";
    $polished = polish_text_with_gemini($text, "course purpose slide", "course", $course_data['title'], $course_data['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Objectives Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_data['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Course Objectives');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $objectives_text = $course_data['objectives'] ? implode("\n", array_map(function($obj) {
        return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
    }, $course_data['objectives'])) : 'No objectives set.';
    $polished = polish_text_with_gemini($objectives_text, "course objectives slide", "course", $course_data['title'], $course_data['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Overview Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_data['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Course Overview');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $modules_text = $course_data['modules'] ? "Modules covered:\n" . implode("\n", array_map(function($mod) {
            return "- " . $mod['title'];
        }, $course_data['modules'])) : 'No modules set.';
    $polished = polish_text_with_gemini($modules_text, "course overview slide", "course", $course_data['title'], $course_data['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }
}

// Module Slides
function create_module_slides($presentation, $course_title, $module) {
    $course_data = get_course_data(get_the_ID());

    // Title Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getActiveParagraph()->getAlignment()
        ->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpPresentation\Style\Alignment::VERTICAL_CENTER);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFF2CC');
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(180)
        ->setOffsetY(200);
    $shape->getActiveParagraph()->getAlignment()->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER);
    $textRun = $shape->createTextRun('Module: ' . $module['title']);
    $textRun->getFont()->setSize(48)->setBold(true)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));

    // Welcome Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Welcome to ' . $module['title']);
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $text = "Dive into {$module['title']}!\nExplore core concepts with engaging content.\nParticipate in activities to reinforce learning.\nTake notes to stay on track.";
    $polished = polish_text_with_gemini($text, "module welcome slide", "module", $module['title'], $module['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Purpose Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Module Purpose');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(200)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $text = "Goal: {$module['goal']}";
    $polished = polish_text_with_gemini($text, "module purpose slide", "module", $module['title'], $module['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Objectives Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Module Objectives');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $objectives_text = $module['objectives'] ? implode("\n", array_map(function($obj) {
        return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
    }, $module['objectives'])) : 'No objectives set.';
    $polished = polish_text_with_gemini($objectives_text, "module objectives slide", "module", $module['title'], $module['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Overview Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Module Overview');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $lessons_text = $module['lessons'] ? "Lessons covered:\n" . implode("\n", array_map(function($les) {
            return "- " . $les['title'];
        }, $module['lessons'])) : 'No lessons set.';
    $polished = polish_text_with_gemini($lessons_text, "module overview slide", "module", $module['title'], $module['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Materials Slides
    foreach ($module['materials'] as $index => $material) {
        $slide = $presentation->createSlide();
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(720)
            ->setWidth(960);
        $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFFFFF');
        add_top_right_info($slide, $course_title, $module['title']);
        $shape = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        $textRun = $shape->createTextRun('Module Material ' . ($index + 1));
        $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
        $shape = $slide->createRichTextShape()
            ->setHeight(300)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(120);
        $text = "Thinking Skill: " . ($material['thinking_skill'] ?: 'Not set') . "\nActivity: " . ($material['learner_activities'] ?: 'Not set') . "\nLink: " . ($material['add_link'] ?: 'None');
        $polished = polish_text_with_gemini($text, "module materials slide", "module", $module['title'], $module['goal'], $course_data);
        $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        if ($polished['slideNotes']) {
            $slide->getNotes()->createRichTextShape()
                ->setHeight(100)
                ->setWidth(600)
                ->setOffsetX(50)
                ->setOffsetY(50)
                ->createTextRun($polished['slideNotes'])
                ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        }
    }

    // Methods Slides
    foreach ($module['methods'] as $index => $method) {
        $slide = $presentation->createSlide();
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(720)
            ->setWidth(960);
        $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFFFFF');
        add_top_right_info($slide, $course_title, $module['title']);
        $shape = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        $textRun = $shape->createTextRun('Module Method ' . ($index + 1));
        $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
        $shape = $slide->createRichTextShape()
            ->setHeight(300)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(120);
        $text = "Thinking Skill: " . ($method['thinking_skill'] ?: 'Not set') . "\nAction: " . ($method['action_verb'] ?: 'Not set') . "\nStrategy: " . ($method['teaching_strategy'] ?: 'Not set') . "\nLink: " . ($method['add_link'] ?: 'None');
        $polished = polish_text_with_gemini($text, "module methods slide", "module", $module['title'], $module['goal'], $course_data);
        $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        if ($polished['slideNotes']) {
            $slide->getNotes()->createRichTextShape()
                ->setHeight(100)
                ->setWidth(600)
                ->setOffsetX(50)
                ->setOffsetY(50)
                ->createTextRun($polished['slideNotes'])
                ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        }
    }

    // Activities Slides
    foreach ($module['activities'] as $index => $activity) {
        $slide = $presentation->createSlide();
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(720)
            ->setWidth(960);
        $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFFFFF');
        add_top_right_info($slide, $course_title, $module['title']);
        $shape = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(30);
        $textRun = $shape->createTextRun('Module Activity ' . ($index + 1));
        $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
        $shape = $slide->createRichTextShape()
            ->setHeight(200)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(120);
        $text = $activity ?: 'No activity set';
        $polished = polish_text_with_gemini($text, "module activities slide", "module", $module['title'], $module['goal'], $course_data);
        $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        if ($polished['slideNotes']) {
            $slide->getNotes()->createRichTextShape()
                ->setHeight(100)
                ->setWidth(600)
                ->setOffsetX(50)
                ->setOffsetY(50)
                ->createTextRun($polished['slideNotes'])
                ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
        }
    }
}

// Lesson Slides
function create_lesson_slides($presentation, $course_title, $module_title, $lesson) {
    $course_data = get_course_data(get_the_ID());

    // Title Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getActiveParagraph()->getAlignment()
        ->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpPresentation\Style\Alignment::VERTICAL_CENTER);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFF2CC');
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(180)
        ->setOffsetY(200);
    $shape->getActiveParagraph()->getAlignment()->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER);
    $textRun = $shape->createTextRun('Lesson: ' . $lesson['title']);
    $textRun->getFont()->setSize(48)->setBold(true)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));

    // Welcome Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module_title, $lesson['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Welcome to ' . $lesson['title']);
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $text = "Start {$lesson['title']} now!\nEngage with focused content.\nApply concepts through activities.\nNote key points for review.";
    $polished = polish_text_with_gemini($text, "lesson welcome slide", "lesson", $lesson['title'], $lesson['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Purpose Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module_title, $lesson['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Lesson Purpose');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(200)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $text = "Goal: {$lesson['goal']}";
    $polished = polish_text_with_gemini($text, "lesson purpose slide", "lesson", $lesson['title'], $lesson['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Objectives Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module_title, $lesson['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Lesson Objectives');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $objectives_text = $lesson['objectives'] ? implode("\n", array_map(function($obj) {
        return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
    }, $lesson['objectives'])) : 'No objectives set.';
    $polished = polish_text_with_gemini($objectives_text, "lesson objectives slide", "lesson", $lesson['title'], $lesson['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Overview Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module_title, $lesson['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Lesson Overview');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $points_text = $lesson['teaching_points'] ? "Key points:\n" . implode("\n", array_map(function($point) {
            return "- " . $point;
        }, $lesson['teaching_points'])) : 'No teaching points set.';
    $polished = polish_text_with_gemini($points_text, "lesson overview slide", "lesson", $lesson['title'], $lesson['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }

    // Summary Slide
    $slide = $presentation->createSlide();
    $backgroundShape = $slide->createRichTextShape()
        ->setHeight(720)
        ->setWidth(960);
    $backgroundShape->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFFFFF');
    add_top_right_info($slide, $course_title, $module_title, $lesson['title']);
    $shape = $slide->createRichTextShape()
        ->setHeight(50)
        ->setWidth(900)
        ->setOffsetX(50)
        ->setOffsetY(30);
    $textRun = $shape->createTextRun('Lesson Summary');
    $textRun->getFont()->setBold(true)->setSize(33)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    $line = $slide->createLineShape(50, 90, 910, 90)->getBorder()->setLineWidth(4);
    $shape = $slide->createRichTextShape()
        ->setHeight(400)
        ->setWidth(600)
        ->setOffsetX(50)
        ->setOffsetY(120);
    $summary_text = "Review {$lesson['title']}:\n- Goal: {$lesson['goal']}\n- Next: Prepare for upcoming lessons.";
    $summary_text .= $lesson['objectives'] ? "\nAchievements:\n" . implode("\n", array_map(function($obj, $i) {
            $past_tense = str_replace(['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'], ['Knew', 'Comprehended', 'Applied', 'Analyzed', 'Evaluated', 'Created'], $obj['action_verb']);
            return "- {$past_tense} {$obj['description']}";
        }, $lesson['objectives'], array_keys($lesson['objectives']))) : '';
    $polished = polish_text_with_gemini($summary_text, "lesson summary slide", "lesson", $lesson['title'], $lesson['goal'], $course_data);
    $shape->createTextRun($polished['slideText'])->getFont()->setSize(20)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    if ($polished['slideNotes']) {
        $slide->getNotes()->createRichTextShape()
            ->setHeight(100)
            ->setWidth(600)
            ->setOffsetX(50)
            ->setOffsetY(50)
            ->createTextRun($polished['slideNotes'])
            ->getFont()->setSize(12)->setColor(new \PhpOffice\PhpPresentation\Style\Color('231F20'));
    }
}

add_action('wp_ajax_get_ai_suggestions', 'handle_get_ai_suggestions');
function handle_get_ai_suggestions() {

    // Check user capabilities
    //    if (!current_user_can('edit_posts')) {
    //        wp_send_json_error(['message' => 'Unauthorized']);
    //    }

    $prompt = sanitize_text_field($_POST['prompt']);
    try {
        $api_key = get_option('AIzaSyBB5ZYwktOFI3R3j_vs8U7CxwKgS3XNgM0');
        $model = 'gemini-2.0-flash'; // Updated model name
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=AIzaSyBB5ZYwktOFI3R3j_vs8U7CxwKgS3XNgM0";

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ]
            ]),
            'timeout' => 30 // Increased timeout
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode($response['body'], true);

        // Log the complete response for debugging
        error_log('Gemini API Raw Response: ' . print_r($body, true));

        // Check for API errors
        if (isset($body['error'])) {
            throw new Exception($body['error']['message'] ?? 'API returned an error');
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid response structure from Gemini API');

        }



        $suggestions_text = $body['candidates'][0]['content']['parts'][0]['text'];
        $suggestions = parse_gemini_response($suggestions_text);

        wp_send_json_success([
            'suggestions' => $suggestions
        ]);

    } catch (Exception $e) {
        error_log('Gemini API Exception: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'AI Service Error: ' . $e->getMessage()
        ]);
    }
}
function parse_gemini_response($response) {
    // First try to split by numbered items (1., 2., etc.)
    $lines = preg_split('/\n\d+\./', $response);

    // If that didn't work (only one item), try splitting by double newlines
    if (count($lines) <= 1) {
        $lines = preg_split('/\n\n+/', $response);
    }

    // Clean up each line
    $lines = array_map(function($line) {
        // Remove any remaining numbers or bullets at start
        $line = preg_replace('/^[\d\s\.\-\*]+/', '', $line);
        // Remove markdown formatting if present
        $line = str_replace(['**', '*'], '', $line);
        // Trim whitespace
        $line = trim($line);
        return $line;
    }, $lines);

    // Filter out empty lines and lines that are too short
    $lines = array_filter($lines, function($line) {
        return !empty($line) && strlen($line) > 10;
    });

    // Take first 5 suggestions
    return array_slice($lines, 1, 6);
}

// Production-Ready AJAX Course Creation Handler
function create_course_ajax_handler() {
    try {
        // Security checks
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }
        
        // Parse course data from serialized form
        $course_data_string = $_POST['course_data'] ?? '';
        parse_str($course_data_string, $course_data);
        
        // Get current user and validate permissions
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        if (!in_array('studio_admin', $user_roles) && !in_array('collaborator', $user_roles) && !current_user_can('administrator')) {
            wp_send_json_error(['message' => 'Insufficient permissions to create courses.']);
        }
        
        // Validate and sanitize input data
        $curriculum_id = absint($course_data['curriculum_id'] ?? 0);
        $title = sanitize_text_field($course_data['course_name'] ?? '');
        $goal = sanitize_textarea_field($course_data['course_goal'] ?? '');
        $level_of_learning = sanitize_text_field($course_data['level_of_learning'] ?? '');
        $objectives = $course_data['objectives'] ?? [];
        
        // Comprehensive validation
        $validation_errors = [];
        
        if (empty($title)) {
            $validation_errors[] = 'Course name is required.';
        } elseif (strlen($title) > 100) {
            $validation_errors[] = 'Course name must be 100 characters or less.';
        }
        
        if (empty($goal)) {
            $validation_errors[] = 'Course goal is required.';
        } elseif (strlen($goal) > 500) {
            $validation_errors[] = 'Course goal must be 500 characters or less.';
        }
        
        if (empty($level_of_learning)) {
            $validation_errors[] = 'Level of learning is required.';
        }
        
        $valid_levels = ['Foundational', 'Introductory', 'Beginner', 'Intermediate', 'Proficient', 'Advanced', 'Expert', 'Mastery'];
        if (!in_array($level_of_learning, $valid_levels)) {
            $validation_errors[] = 'Invalid level of learning selected.';
        }
        
        if (!$curriculum_id || !get_post($curriculum_id) || get_post($curriculum_id)->post_type !== 'crscribe_curriculum') {
            $validation_errors[] = 'Invalid curriculum specified.';
        }
        
        // Validate objectives
        if (empty($objectives) || !is_array($objectives)) {
            $validation_errors[] = 'At least one learning objective is required.';
        } else {
            $sanitized_objectives = [];
            $valid_thinking_skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
            
            foreach ($objectives as $index => $objective) {
                if (!is_array($objective)) continue;
                
                $thinking_skill = sanitize_text_field($objective['thinking_skill'] ?? '');
                $action_verb = sanitize_text_field($objective['action_verb'] ?? '');
                $description = sanitize_textarea_field($objective['description'] ?? '');
                
                if (empty($thinking_skill) || empty($action_verb) || empty($description)) {
                    $validation_errors[] = "Objective #" . ($index + 1) . " is incomplete. All fields are required.";
                    continue;
                }
                
                if (!in_array($thinking_skill, $valid_thinking_skills)) {
                    $validation_errors[] = "Objective #" . ($index + 1) . " has an invalid thinking skill.";
                    continue;
                }
                
                if (strlen($description) > 300) {
                    $validation_errors[] = "Objective #" . ($index + 1) . " description must be 300 characters or less.";
                    continue;
                }
                
                $sanitized_objectives[] = [
                    'thinking_skill' => $thinking_skill,
                    'action_verb' => $action_verb,
                    'description' => $description,
                ];
            }
            
            if (empty($sanitized_objectives)) {
                $validation_errors[] = 'At least one valid learning objective is required.';
            }
        }
        
        if (!empty($validation_errors)) {
            wp_send_json_error([
                'message' => 'Please correct the following errors:',
                'errors' => $validation_errors
            ]);
        }
        
        // Get studio information and check permissions
        $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
        if (!$studio_id) {
            wp_send_json_error(['message' => 'Curriculum is not associated with a valid studio.']);
        }
        
        // Check tier restrictions
        $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
        $existing_course_count = count(get_posts([
            'post_type' => 'crscribe_course',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_curriculum_id',
                    'value' => $curriculum_id,
                    'compare' => '=',
                ],
            ],
        ]));
        
        // Tier-based course limits
        $tier_limits = [
            'basics' => 7,
            'plus' => 16,
            'pro' => PHP_INT_MAX
        ];
        
        if ($existing_course_count >= ($tier_limits[$tier] ?? 1)) {
            $tier_names = [
                'basics' => 'Basics',
                'plus' => 'Plus',
                'pro' => 'Pro'
            ];
            
            wp_send_json_error([
                'message' => "Your {$tier_names[$tier]} plan allows only {$tier_limits[$tier]} course(s) per curriculum. Upgrade to create more courses."
            ]);
        }
        
        // Create course post
        $post_data = [
            'post_title' => $title,
            'post_type' => 'crscribe_course',
            'post_status' => 'publish',
            'post_author' => $current_user->ID,
            'meta_input' => [
                '_class_goal' => $goal,
                'level-of-learning' => $level_of_learning,
                '_course_objectives' => maybe_serialize($sanitized_objectives),
                '_curriculum_id' => $curriculum_id,
                '_studio_id' => $studio_id,
                '_creator_id' => $current_user->ID,
                '_created_date' => current_time('mysql'),
            ],
        ];
        
        $course_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($course_id)) {
            error_log('CourScribe Course Creation Error: ' . $course_id->get_error_message());
            wp_send_json_error([
                'message' => 'Failed to create course. Please try again.'
            ]);
        }
        
        // Log the course creation for audit trail
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';
        
        // Check if log table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table)) == $log_table;
        
        if ($table_exists) {
            $changes = [
                'title' => ['new' => $title],
                'goal' => ['new' => $goal],
                'level_of_learning' => ['new' => $level_of_learning],
                'objectives' => ['new' => $sanitized_objectives],
                'curriculum_id' => ['new' => $curriculum_id],
                'studio_id' => ['new' => $studio_id],
            ];
            
            $log_result = $wpdb->insert(
                $log_table,
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'create',
                    'changes' => wp_json_encode($changes),
                    'timestamp' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
            
            if ($log_result === false) {
                error_log('CourScribe: Failed to log course creation for course ID: ' . $course_id);
            }
        }
        
        // Success response
        wp_send_json_success([
            'message' => 'Course created successfully!',
            'course_id' => $course_id,
            'course_title' => $title,
            'redirect_url' => $_SERVER['HTTP_REFERER'] ?? home_url(),
        ]);
        
    } catch (Exception $e) {
        error_log('CourScribe Course Creation Exception: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'An unexpected error occurred. Please try again.'
        ]);
    }
}

// Production-Ready Course Update AJAX Handler
function update_course_ajax_handler() {
    try {
        // Security checks
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }
        
        $course_id = absint($_POST['course_id'] ?? 0);
        $field = sanitize_text_field($_POST['field'] ?? '');
        $value = $_POST['value'] ?? '';
        
        if (!$course_id || !$field) {
            wp_send_json_error(['message' => 'Invalid course ID or field.']);
        }
        
        // Get course and validate permissions
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'crscribe_course') {
            wp_send_json_error(['message' => 'Course not found.']);
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        if (!in_array('studio_admin', $user_roles) && !in_array('collaborator', $user_roles) && !current_user_can('administrator')) {
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }
        
        // Check if course is archived
        $course_status = get_post_meta($course_id, '_course_status', true);
        if ($course_status === 'archived') {
            wp_send_json_error(['message' => 'Cannot edit archived course.']);
        }
        
        $old_value = '';
        $success = false;
        
        switch ($field) {
            case 'course_name':
                $value = sanitize_text_field($value);
                if (strlen($value) > 100) {
                    wp_send_json_error(['message' => 'Course name must be 100 characters or less.']);
                }
                $old_value = $course->post_title;
                $success = wp_update_post([
                    'ID' => $course_id,
                    'post_title' => $value
                ]);
                break;
                
            case 'course_goal':
                $value = sanitize_textarea_field($value);
                if (strlen($value) > 500) {
                    wp_send_json_error(['message' => 'Course goal must be 500 characters or less.']);
                }
                $old_value = get_post_meta($course_id, '_class_goal', true);
                $success = update_post_meta($course_id, '_class_goal', $value);
                break;
                
            case 'level_of_learning':
                $valid_levels = ['Foundational', 'Introductory', 'Beginner', 'Intermediate', 'Proficient', 'Advanced', 'Expert', 'Mastery'];
                if (!in_array($value, $valid_levels)) {
                    wp_send_json_error(['message' => 'Invalid level of learning.']);
                }
                $old_value = get_post_meta($course_id, 'level-of-learning', true);
                $success = update_post_meta($course_id, 'level-of-learning', $value);
                break;
                
            // case 'objectives':
            //     if (!is_array($value)) {
            //         wp_send_json_error(['message' => 'Invalid objectives format.']);
            //     }
                
            //     // Validate and sanitize objectives
            //     $sanitized_objectives = [];
            //     $valid_thinking_skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                
            //     foreach ($value as $objective) {
            //         if (!is_array($objective)) continue;
                    
            //         $thinking_skill = sanitize_text_field($objective['thinking_skill'] ?? '');
            //         $action_verb = sanitize_text_field($objective['action_verb'] ?? '');
            //         $description = sanitize_textarea_field($objective['description'] ?? '');
                    
            //         if (empty($thinking_skill) || empty($action_verb) || empty($description)) {
            //             continue; // Skip incomplete objectives
            //         }
                    
            //         if (!in_array($thinking_skill, $valid_thinking_skills)) {
            //             continue; // Skip invalid thinking skills
            //         }
                    
            //         if (strlen($description) > 300) {
            //             wp_send_json_error(['message' => 'Objective description must be 300 characters or less.']);
            //         }
                    
            //         $sanitized_objectives[] = [
            //             'thinking_skill' => $thinking_skill,
            //             'action_verb' => $action_verb,
            //             'description' => $description,
            //         ];
            //     }
                
            //     if (empty($sanitized_objectives)) {
            //         wp_send_json_error(['message' => 'At least one valid objective is required.']);
            //     }
                
            //     $old_value = maybe_unserialize(get_post_meta($course_id, '_course_objectives', true));
            //     $success = update_post_meta($course_id, '_course_objectives', maybe_serialize($sanitized_objectives));
            //     $value = $sanitized_objectives; // Use sanitized value for logging
            //     break;
            case 'objectives':
                // ✅ FIX: Decode JSON if it's a string (frontend sends JSON.stringify)
                if (is_string($value)) {
                    $value = json_decode($value, true);
                }

                if (!is_array($value)) {
                    wp_send_json_error(['message' => 'Invalid objectives format. Expected array or JSON string.']);
                }

                $sanitized_objectives = [];
                $valid_thinking_skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
            
                foreach ($value as $objective) {
                    if (!is_array($objective)) continue;
            
                    $thinking_skill = sanitize_text_field($objective['thinking_skill'] ?? '');
                    $action_verb = sanitize_text_field($objective['action_verb'] ?? '');
                    $description = sanitize_textarea_field($objective['description'] ?? '');
            
                    if (empty($thinking_skill) || empty($action_verb) || empty($description)) {
                        continue;
                    }
            
                    if (!in_array($thinking_skill, $valid_thinking_skills)) {
                        continue;
                    }
            
                    if (strlen($description) > 300) {
                        wp_send_json_error(['message' => 'Objective description must be 300 characters or less.']);
                    }
            
                    $sanitized_objectives[] = [
                        'thinking_skill' => $thinking_skill,
                        'action_verb' => $action_verb,
                        'description' => $description,
                    ];
                }
            
                if (empty($sanitized_objectives)) {
                    wp_send_json_error(['message' => 'At least one valid objective is required.']);
                }
            
                $sanitized_objectives = array_values($sanitized_objectives); // Explicitly reindex
                $old_value = maybe_unserialize(get_post_meta($course_id, '_course_objectives', true));
                $success = update_post_meta($course_id, '_course_objectives', maybe_serialize($sanitized_objectives));
                $value = $sanitized_objectives;
                break;
                
            default:
                wp_send_json_error(['message' => 'Invalid field specified.']);
        }
        
        if (!$success) {
            wp_send_json_error(['message' => 'Failed to update course.']);
        }
        
        // Log the change
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table)) == $log_table) {
            $changes = [
                $field => [
                    'old' => $old_value,
                    'new' => $value
                ]
            ];
            
            $wpdb->insert(
                $log_table,
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'update',
                    'changes' => wp_json_encode($changes),
                    'timestamp' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }
        
        wp_send_json_success([
            'message' => 'Course updated successfully.',
            'field' => $field,
            'value' => $value
        ]);
        
    } catch (Exception $e) {
        error_log('CourScribe Course Update Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An unexpected error occurred.']);
    }
}

// Archive Course AJAX Handler
function archive_course_ajax_handler() {
    try {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }
        
        $course_id = absint($_POST['course_id'] ?? 0);
        if (!$course_id) {
            wp_send_json_error(['message' => 'Invalid course ID.']);
        }
        
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'crscribe_course') {
            wp_send_json_error(['message' => 'Course not found.']);
        }
        
        // Check permissions
        $current_user = wp_get_current_user();
        if (!in_array('studio_admin', $current_user->roles) && !current_user_can('administrator')) {
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }
        
        // Archive the course
        $old_status = get_post_meta($course_id, '_course_status', true) ?: 'active';
        update_post_meta($course_id, '_course_status', 'archived');
        update_post_meta($course_id, '_archived_at', current_time('mysql'));
        update_post_meta($course_id, '_archived_by', $current_user->ID);
        
        // Log the action
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table)) == $log_table) {
            $changes = [
                'status' => [
                    'old' => $old_status,
                    'new' => 'archived'
                ]
            ];
            
            $wpdb->insert(
                $log_table,
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'archive',
                    'changes' => wp_json_encode($changes),
                    'timestamp' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }
        
        wp_send_json_success(['message' => 'Course archived successfully.']);
        
    } catch (Exception $e) {
        error_log('CourScribe Archive Course Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to archive course.']);
    }
}

// Delete Course AJAX Handler
function delete_course_ajax_handler() {
    try {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }
        
        $course_id = absint($_POST['course_id'] ?? 0);
        if (!$course_id) {
            wp_send_json_error(['message' => 'Invalid course ID.']);
        }
        
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'crscribe_course') {
            wp_send_json_error(['message' => 'Course not found.']);
        }
        
        // Check permissions
        $current_user = wp_get_current_user();
        if (!in_array('studio_admin', $current_user->roles) && !current_user_can('administrator')) {
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }
        
        // Log before deletion
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table)) == $log_table) {
            $changes = [
                'deleted_course' => [
                    'title' => $course->post_title,
                    'goal' => get_post_meta($course_id, '_class_goal', true),
                    'level' => get_post_meta($course_id, 'level-of-learning', true),
                    'objectives' => get_post_meta($course_id, '_course_objectives', true)
                ]
            ];
            
            $wpdb->insert(
                $log_table,
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'delete',
                    'changes' => wp_json_encode($changes),
                    'timestamp' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }
        
        // Delete the course
        $deleted = wp_delete_post($course_id, true); // Force delete
        
        if (!$deleted) {
            wp_send_json_error(['message' => 'Failed to delete course.']);
        }
        
        wp_send_json_success(['message' => 'Course deleted successfully.']);
        
    } catch (Exception $e) {
        error_log('CourScribe Delete Course Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to delete course.']);
    }
}

// Get Course Logs AJAX Handler
function get_course_logs_ajax_handler() {
    try {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }
        
        $course_id = absint($_POST['course_id'] ?? 0);
        $page = max(1, absint($_POST['page'] ?? 1));
        $per_page = max(5, min(50, absint($_POST['per_page'] ?? 20)));
        $search = sanitize_text_field($_POST['search'] ?? '');
        $action_filter = sanitize_text_field($_POST['action_filter'] ?? '');
        
        if (!$course_id) {
            wp_send_json_error(['message' => 'Invalid course ID.']);
        }
        
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table)) != $log_table) {
            wp_send_json_success([
                'logs' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => 1
            ]);
        }
        
        // Build query
        $where_conditions = ['course_id = %d'];
        $query_params = [$course_id];
        
        if ($search) {
            $where_conditions[] = '(changes LIKE %s OR action LIKE %s)';
            $query_params[] = '%' . $wpdb->esc_like($search) . '%';
            $query_params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if ($action_filter && in_array($action_filter, ['create', 'update', 'archive', 'delete'])) {
            $where_conditions[] = 'action = %s';
            $query_params[] = $action_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$log_table} WHERE {$where_clause}";
        $total = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        
        // Get logs
        $offset = ($page - 1) * $per_page;
        $logs_query = "SELECT cl.*, u.display_name, u.user_email 
                      FROM {$log_table} cl 
                      LEFT JOIN {$wpdb->users} u ON cl.user_id = u.ID 
                      WHERE {$where_clause} 
                      ORDER BY cl.timestamp DESC 
                      LIMIT %d OFFSET %d";
        
        $query_params[] = $per_page;
        $query_params[] = $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare($logs_query, $query_params));
        
        // Format logs for display
        $formatted_logs = [];
        foreach ($logs as $log) {
            $changes = json_decode($log->changes, true) ?: [];
            
            $formatted_logs[] = [
                'id' => $log->id,
                'action' => $log->action,
                'user_name' => $log->display_name ?: 'Unknown User',
                'user_email' => $log->user_email ?: '',
                'timestamp' => $log->timestamp,
                'formatted_time' => human_time_diff(strtotime($log->timestamp), current_time('timestamp')) . ' ago',
                'changes' => $changes,
                'formatted_changes' => format_course_log_changes($changes, $log->action)
            ];
        }
        
        wp_send_json_success([
            'logs' => $formatted_logs,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ]);
        
    } catch (Exception $e) {
        error_log('CourScribe Get Logs Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to retrieve logs.']);
    }
}

// Format course log changes for display
function format_course_log_changes($changes, $action) {
    if (empty($changes) || !is_array($changes)) {
        return 'No changes recorded.';
    }
    
    $formatted = [];
    
    foreach ($changes as $field => $change) {
        switch ($field) {
            case 'title':
            case 'course_name':
                $formatted[] = "Name: '{$change['old']}' → '{$change['new']}'";
                break;
            case 'goal':
            case 'course_goal':
                $old = wp_trim_words($change['old'], 10);
                $new = wp_trim_words($change['new'], 10);
                $formatted[] = "Goal: '{$old}' → '{$new}'";
                break;
            case 'level_of_learning':
                $formatted[] = "Level: '{$change['old']}' → '{$change['new']}'";
                break;
            case 'objectives':
                $old_count = is_array($change['old']) ? count($change['old']) : 0;
                $new_count = is_array($change['new']) ? count($change['new']) : 0;
                $formatted[] = "Objectives: {$old_count} → {$new_count} objectives";
                break;
            case 'status':
                $formatted[] = "Status: '{$change['old']}' → '{$change['new']}'";
                break;
        }
    }
    
    return implode(', ', $formatted) ?: 'Changes made.';
}

// Restore Course from Log AJAX Handler
function restore_course_from_log_ajax_handler() {
    try {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }
        
        $log_id = absint($_POST['log_id'] ?? 0);
        if (!$log_id) {
            wp_send_json_error(['message' => 'Invalid log ID.']);
        }
        
        // Check permissions
        $current_user = wp_get_current_user();
        if (!in_array('studio_admin', $current_user->roles) && !current_user_can('administrator')) {
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }
        
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';
        
        // Get the log entry
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$log_table} WHERE id = %d",
            $log_id
        ));
        
        if (!$log) {
            wp_send_json_error(['message' => 'Log entry not found.']);
        }
        
        $changes = json_decode($log->changes, true);
        if (empty($changes)) {
            wp_send_json_error(['message' => 'No changes to restore.']);
        }
        
        $course_id = $log->course_id;
        $course = get_post($course_id);
        
        if (!$course || $course->post_type !== 'crscribe_course') {
            wp_send_json_error(['message' => 'Course not found.']);
        }
        
        // Restore the old values
        $restored_fields = [];
        foreach ($changes as $field => $change) {
            if (!isset($change['old'])) continue;
            
            switch ($field) {
                case 'title':
                case 'course_name':
                    wp_update_post([
                        'ID' => $course_id,
                        'post_title' => $change['old']
                    ]);
                    $restored_fields[] = 'Course Name';
                    break;
                case 'goal':
                case 'course_goal':
                    update_post_meta($course_id, '_class_goal', $change['old']);
                    $restored_fields[] = 'Course Goal';
                    break;
                case 'level_of_learning':
                    update_post_meta($course_id, 'level-of-learning', $change['old']);
                    $restored_fields[] = 'Level of Learning';
                    break;
                case 'objectives':
                    update_post_meta($course_id, '_course_objectives', maybe_serialize($change['old']));
                    $restored_fields[] = 'Objectives';
                    break;
                case 'status':
                    update_post_meta($course_id, '_course_status', $change['old']);
                    $restored_fields[] = 'Status';
                    break;
            }
        }
        
        // Log the restoration
        $restore_changes = [
            'restored_from_log' => $log_id,
            'restored_fields' => $restored_fields,
            'original_action' => $log->action
        ];
        
        $wpdb->insert(
            $log_table,
            [
                'course_id' => $course_id,
                'user_id' => $current_user->ID,
                'action' => 'restore',
                'changes' => wp_json_encode($restore_changes),
                'timestamp' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
        
        wp_send_json_success([
            'message' => 'Course restored successfully.',
            'restored_fields' => $restored_fields
        ]);
        
    } catch (Exception $e) {
        error_log('CourScribe Restore Course Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to restore course.']);
    }
}

function unarchive_course_ajax_handler() {
    try {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.']);
        }

        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_course')) {
            wp_send_json_error(['message' => 'Security verification failed.']);
        }

        $course_id = absint($_POST['course_id'] ?? 0);
        if (!$course_id) {
            wp_send_json_error(['message' => 'Invalid course ID.']);
        }

        $course = get_post($course_id);
        if (!$course || $course->post_type =! 'crscribe_course') {
            wp_send_json_error(['message' => 'Course not found.']);
        }

        // Check permissions
        $current_user = wp_get_current_user();
        if (!in_array('studio_admin', $current_user->roles) && !current_user_can('administrator')) {
            wp_send_json_error(['message' => 'Insufficient permissions.']);
        }

        // Unarchive the course
        $old_status = get_post_meta($course_id, '_course_status', true) ?: 'active';
        update_post_meta($course_id, '_course_status', 'active');
        update_post_meta($course_id, '_unarchived_at', current_time('mysql'));
        update_post_meta($course_id, '_unarchived_by', $current_user->ID);

        // Log the action
        global $wpdb;
        $log_table = $wpdb->prefix . 'courscribe_course_log';

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $log_table)) == $log_table) {
            $changes = [
                'status' => [
                    'old' => $old_status,
                    'new' => 'active'
                ]
            ];

            $wpdb->insert(
                $log_table,
                [
                    'course_id' => $course_id,
                    'user_id' => $current_user->ID,
                    'action' => 'restore',
                    'changes' => wp_json_encode($changes),
                    'timestamp' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }

        wp_send_json_success(['message' => 'Course restored successfully.']);
    } catch (Exception $e) {
        error_log('CourScribe Unarchive Course Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to restore course.']);
    }
}
add_action('wp_ajax_unarchive_course', 'unarchive_course_ajax_handler');

// Enhanced AI suggestions for modules with proper security
add_action('wp_ajax_courscribe_get_ai_suggestions', 'handle_courscribe_get_ai_suggestions');
function handle_courscribe_get_ai_suggestions() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed']);
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Unauthorized access']);
        return;
    }

    $module_id = sanitize_text_field($_POST['module_id']);
    $field_id = sanitize_text_field($_POST['field_id']);
    $prompt = sanitize_text_field($_POST['prompt']);
    
    // Validate required fields
    if (empty($prompt)) {
        wp_send_json_error(['message' => 'Prompt is required']);
        return;
    }

    try {
        // Get API key from settings (secure approach)
        $api_key = get_option('courscribe_gemini_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'AI service not configured. Please check your settings.']);
            return;
        }

        // Use the latest Gemini model
        $model = 'gemini-2.0-flash-exp';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        
        // Prepare the request
        $request_body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'CourScribe/1.0'
            ],
            'body' => json_encode($request_body),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            throw new Exception('Network error: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log for debugging (remove in production)
        error_log('Gemini API Response Code: ' . $response_code);
        
        if ($response_code !== 200) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'API request failed';
            throw new Exception('API Error (' . $response_code . '): ' . $error_message);
        }

        // Check for API errors
        if (isset($body['error'])) {
            throw new Exception($body['error']['message'] ?? 'API returned an error');
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid response structure from AI service');
        }

        $suggestions_text = $body['candidates'][0]['content']['parts'][0]['text'];
        $suggestions = courscribe_parse_ai_suggestions($suggestions_text);
        
        // Log activity for modules
        if (!empty($module_id)) {
            courscribe_log_module_activity_actions($module_id, 'ai_suggestion', [
                'field_id' => $field_id,
                'suggestions_count' => count($suggestions)
            ]);
        }

        wp_send_json_success([
            'suggestions' => $suggestions,
            'original_text' => $suggestions_text
        ]);

    } catch (Exception $e) {
        error_log('Gemini AI Exception: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'AI service error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Parse AI suggestions from response text
 */
function courscribe_parse_ai_suggestions($response_text) {
    // Clean up the response
    $response_text = trim($response_text);
    
    // Try different parsing strategies
    
    // Strategy 1: Split by numbered items (1., 2., 3., etc.)
    $numbered_pattern = '/(?:^|\n)\s*\d+\.\s+(.+?)(?=\n\s*\d+\.|$)/s';
    if (preg_match_all($numbered_pattern, $response_text, $matches)) {
        $suggestions = array_map('trim', $matches[1]);
        if (count($suggestions) > 1) {
            return array_filter($suggestions, function($suggestion) {
                return !empty($suggestion) && strlen($suggestion) > 5;
            });
        }
    }
    
    // Strategy 2: Split by bullet points (-, *, •)
    $bullet_pattern = '/(?:^|\n)\s*[-\*•]\s+(.+?)(?=\n\s*[-\*•]|$)/s';
    if (preg_match_all($bullet_pattern, $response_text, $matches)) {
        $suggestions = array_map('trim', $matches[1]);
        if (count($suggestions) > 1) {
            return array_filter($suggestions, function($suggestion) {
                return !empty($suggestion) && strlen($suggestion) > 5;
            });
        }
    }
    
    // Strategy 3: Split by double newlines
    $suggestions = preg_split('/\n\s*\n/', $response_text);
    $suggestions = array_map('trim', $suggestions);
    $suggestions = array_filter($suggestions, function($suggestion) {
        return !empty($suggestion) && strlen($suggestion) > 5;
    });
    
    if (count($suggestions) > 1) {
        return array_values($suggestions);
    }
    
    // Strategy 4: Split by single newlines if content is short
    if (strlen($response_text) < 500) {
        $suggestions = preg_split('/\n/', $response_text);
        $suggestions = array_map('trim', $suggestions);
        $suggestions = array_filter($suggestions, function($suggestion) {
            return !empty($suggestion) && strlen($suggestion) > 5;
        });
        
        if (count($suggestions) > 1) {
            return array_values($suggestions);
        }
    }
    
    // Fallback: Return the entire response as a single suggestion
    return [$response_text];
}

/**
 * Log module activity for AI suggestions
 */
function courscribe_log_module_activity_actions($module_id, $action, $details = []) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'courscribe_module_log';
    
    // Check if table exists before inserting
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name) {
        $wpdb->insert(
            $table_name,
            [
                'module_id' => intval($module_id),
                'user_id' => get_current_user_id(),
                'action' => sanitize_text_field($action),
                'changes' => wp_json_encode($details),
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }
}

?>