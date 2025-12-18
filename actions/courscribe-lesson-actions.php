<?php
// Path: courscribe/inc/courscribe-lesson-actions.php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_save_new_lesson', 'handle_save_new_lesson');
function handle_save_new_lesson() {
//    if (!current_user_can('edit_posts')) {
//        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to add lessons');
//        wp_send_json_error(['message' => 'You are not allowed to add lessons.']);
//        wp_die();
//    }

    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data - ' . print_r($_POST, true));

    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $lesson_name = isset($_POST['lesson_name']) ? sanitize_text_field($_POST['lesson_name']) : '';
    $lesson_goal = isset($_POST['lesson_goal']) ? sanitize_text_field($_POST['lesson_goal']) : '';
    $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
    $activities = isset($_POST['activities']) && is_array($_POST['activities']) ? $_POST['activities'] : [];

    // Log processed input
    error_log('Courscribe: handle_save_new_lesson input - ' . print_r([
            'lesson_id' => $lesson_id,
            'module_id' => $module_id,
            'course_id' => $course_id,
            'lesson_name' => $lesson_name,
            'lesson_goal' => $lesson_goal,
            'objectives' => $objectives,
            'activities' => $activities
        ], true));

    // Validate required fields
    if (empty($module_id) || empty($course_id) || empty($lesson_name) || empty($lesson_goal)) {
        error_log('Courscribe: Missing required fields - Module ID: ' . $module_id . ', Course ID: ' . $course_id . ', Lesson Name: ' . $lesson_name . ', Lesson Goal: ' . $lesson_goal);
        wp_send_json_error(['message' => 'All fields are required.', 'debug' => [
            'module_id' => empty($module_id),
            'course_id' => empty($course_id),
            'lesson_name' => empty($lesson_name),
            'lesson_goal' => empty($lesson_goal)
        ]]);
        wp_die();
    }

    // Validate module and course
    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        error_log('Courscribe: Invalid module - Module ID: ' . $module_id);
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'crscribe_course') {
        error_log('Courscribe: Invalid course - Course ID: ' . $course_id);
        wp_send_json_error(['message' => 'Invalid course.']);
        wp_die();
    }

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'id' => uniqid('obj_', true),
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb' => sanitize_text_field($objective['action_verb'] ?? ''),
            'description' => sanitize_text_field($objective['description'] ?? '')
        ];
    }

    // Sanitize activities
    $sanitized_activities = [];
    foreach ($activities as $activity) {
        $sanitized_activities[] = [
            'id' => uniqid('act_', true),
            'type' => sanitize_text_field($activity['type'] ?? ''),
            'title' => sanitize_text_field($activity['title'] ?? ''),
            'instructions' => sanitize_textarea_field($activity['instructions'] ?? '')
        ];
    }

    // Prepare post data
    $current_user = wp_get_current_user();
    $post_data = [
        'ID' => $lesson_id,
        'post_title' => $lesson_name,
        'post_type' => 'crscribe_lesson',
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
        'meta_input' => [
            'lesson-goal' => $lesson_goal,
            '_lesson_objectives' => $sanitized_objectives,
            '_lesson_activities' => $sanitized_activities,
            '_course_id' => $course_id,
            '_module_id' => $module_id,
            '_creator_id' => $current_user->ID
        ]
    ];

    // Insert or update lesson
    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        error_log('Courscribe: Failed to save lesson - Error: ' . $post_id->get_error_message());
        wp_send_json_error(['message' => 'Failed to save lesson: ' . $post_id->get_error_message()]);
        wp_die();
    }

    // Update module's lessons meta
    $lessons = get_post_meta($module_id, 'lessons', true);
    $lessons = is_array($lessons) ? $lessons : [];
    if (!in_array($post_id, $lessons)) {
        $lessons[] = $post_id;
        update_post_meta($module_id, 'lessons', array_unique($lessons));
    }

    // Log the action
    global $wpdb;
    $changes = [
        'title' => ['new' => $lesson_name],
        'goal' => ['new' => $lesson_goal],
        'objectives' => ['new' => $sanitized_objectives],
        'activities' => ['new' => $sanitized_activities],
        'course_id' => ['new' => $course_id],
        'module_id' => ['new' => $module_id]
    ];

    if ($lesson_id) {
        $old_post = get_post($lesson_id);
        $changes['title']['old'] = $old_post ? $old_post->post_title : '';
        $changes['goal']['old'] = get_post_meta($lesson_id, 'lesson-goal', true) ?: '';
        $changes['objectives']['old'] = get_post_meta($lesson_id, '_lesson_objectives', true) ?: [];
        $changes['activities']['old'] = get_post_meta($lesson_id, '_lesson_activities', true) ?: [];
        $changes['course_id']['old'] = get_post_meta($lesson_id, '_course_id', true) ?: 0;
        $changes['module_id']['old'] = get_post_meta($lesson_id, '_module_id', true) ?: 0;
    }

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_lesson_log',
        [
            'lesson_id' => $post_id,
            'user_id' => $current_user->ID,
            'action' => $lesson_id ? 'update' : 'create',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($wpdb->last_error) {
        error_log('Courscribe: Failed to log lesson action - Error: ' . $wpdb->last_error);
    } else {
        error_log('Courscribe: Lesson action logged - Lesson ID: ' . $post_id . ', Action: ' . ($lesson_id ? 'update' : 'create'));
    }

    wp_send_json_success([
        'message' => 'Lesson saved successfully.',
        'lesson_id' => $post_id
    ]);
    wp_die();
}

add_action('wp_ajax_save_lesson_changes', 'handle_save_lesson_changes');
function handle_save_lesson_changes() {
//    if (!current_user_can('edit_posts')) {
//        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to edit lessons');
//        wp_send_json_error(['message' => 'You are not allowed to edit lessons.']);
//        wp_die();
//    }

    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (save_lesson_changes) - ' . print_r($_POST, true));

    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $lesson_name = isset($_POST['lesson_name']) ? sanitize_text_field($_POST['lesson_name']) : '';
    $lesson_goal = isset($_POST['lesson_goal']) ? sanitize_text_field($_POST['lesson_goal']) : '';
    $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
    $activities = isset($_POST['activities']) && is_array($_POST['activities']) ? $_POST['activities'] : [];

    // Log processed input
    error_log('Courscribe: handle_save_lesson_changes input - ' . print_r([
            'lesson_id' => $lesson_id,
            'module_id' => $module_id,
            'course_id' => $course_id,
            'lesson_name' => $lesson_name,
            'lesson_goal' => $lesson_goal,
            'objectives' => $objectives,
            'activities' => $activities
        ], true));

    // Validate required fields
    if (empty($lesson_id) || empty($module_id) || empty($course_id) || empty($lesson_name) || empty($lesson_goal)) {
        error_log('Courscribe: Missing required fields - Lesson ID: ' . $lesson_id . ', Module ID: ' . $module_id . ', Course ID: ' . $course_id . ', Lesson Name: ' . $lesson_name . ', Lesson Goal: ' . $lesson_goal);
        wp_send_json_error(['message' => 'All fields are required.', 'debug' => [
            'lesson_id' => empty($lesson_id),
            'module_id' => empty($module_id),
            'course_id' => empty($course_id),
            'lesson_name' => empty($lesson_name),
            'lesson_goal' => empty($lesson_goal)
        ]]);
        wp_die();
    }

    // Validate lesson, module, and course
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        error_log('Courscribe: Invalid lesson - Lesson ID: ' . $lesson_id);
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        error_log('Courscribe: Invalid module - Module ID: ' . $module_id);
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'crscribe_course') {
        error_log('Courscribe: Invalid course - Course ID: ' . $course_id);
        wp_send_json_error(['message' => 'Invalid course.']);
        wp_die();
    }

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $obj) {
        $id = !empty($obj['id']) ? sanitize_text_field($obj['id']) : uniqid('obj_', true);
        $sanitized_objectives[] = [
            'id' => $id,
            'thinking_skill' => sanitize_text_field($obj['thinking_skill'] ?? ''),
            'action_verb' => sanitize_text_field($obj['action_verb'] ?? ''),
            'description' => sanitize_text_field($obj['description'] ?? '')
        ];
    }

    // Sanitize activities
    $sanitized_activities = [];
    foreach ($activities as $act) {
        $id = !empty($act['id']) ? sanitize_text_field($act['id']) : uniqid('act_', true);
        $sanitized_activities[] = [
            'id' => $id,
            'type' => sanitize_text_field($act['type'] ?? ''),
            'title' => sanitize_text_field($act['title'] ?? ''),
            'instructions' => sanitize_textarea_field($act['instructions'] ?? '')
        ];
    }

    // Prepare post data
    $current_user = wp_get_current_user();
    $post_data = [
        'ID' => $lesson_id,
        'post_title' => $lesson_name,
        'post_type' => 'crscribe_lesson',
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
        'meta_input' => [
            'lesson-goal' => $lesson_goal,
            '_lesson_objectives' => $sanitized_objectives,
            '_lesson_activities' => $sanitized_activities,
            '_course_id' => $course_id,
            '_module_id' => $module_id,
            '_creator_id' => $current_user->ID
        ]
    ];

    // Update lesson
    $updated = wp_update_post($post_data, true);
    if (is_wp_error($updated)) {
        error_log('Courscribe: Failed to update lesson - Error: ' . $updated->get_error_message());
        wp_send_json_error(['message' => 'Failed to update lesson: ' . $updated->get_error_message()]);
        wp_die();
    }

    // Log the action
    global $wpdb;
    $old_post = get_post($lesson_id);
    $changes = [
        'title' => ['old' => $old_post ? $old_post->post_title : '', 'new' => $lesson_name],
        'goal' => ['old' => get_post_meta($lesson_id, 'lesson-goal', true) ?: '', 'new' => $lesson_goal],
        'objectives' => ['old' => get_post_meta($lesson_id, '_lesson_objectives', true) ?: [], 'new' => $sanitized_objectives],
        'activities' => ['old' => get_post_meta($lesson_id, '_lesson_activities', true) ?: [], 'new' => $sanitized_activities],
        'course_id' => ['old' => get_post_meta($lesson_id, '_course_id', true) ?: 0, 'new' => $course_id],
        'module_id' => ['old' => get_post_meta($lesson_id, '_module_id', true) ?: 0, 'new' => $module_id]
    ];

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_lesson_log',
        [
            'lesson_id' => $lesson_id,
            'user_id' => $current_user->ID,
            'action' => 'update',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($wpdb->last_error) {
        error_log('Courscribe: Failed to log lesson update - Error: ' . $wpdb->last_error);
    } else {
        error_log('Courscribe: Lesson update logged - Lesson ID: ' . $lesson_id);
    }

    wp_send_json_success(['message' => 'Lesson, objectives, and activities saved.', 'lesson_id' => $lesson_id]);
    wp_die();
}

add_action('wp_ajax_delete_lesson', 'handle_delete_lesson');
function handle_delete_lesson() {
//    if (!current_user_can('edit_posts')) {
//        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to delete lessons');
//        wp_send_json_error(['message' => 'You are not allowed to delete this lesson.']);
//        wp_die();
//    }

    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (delete_lesson) - ' . print_r($_POST, true));

    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;

    if (empty($lesson_id)) {
        error_log('Courscribe: Invalid lesson ID - Lesson ID: ' . $lesson_id);
        wp_send_json_error(['message' => 'Invalid lesson ID.']);
        wp_die();
    }

    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        error_log('Courscribe: Invalid lesson - Lesson ID: ' . $lesson_id);
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    $module_id = get_post_meta($lesson_id, '_module_id', true);
    if ($module_id) {
        $module = get_post($module_id);
        if (!$module || $module->post_type !== 'crscribe_module') {
            error_log('Courscribe: Invalid module for lesson - Module ID: ' . $module_id);
            wp_send_json_error(['message' => 'Invalid module associated with lesson.']);
            wp_die();
        }

        $lessons = get_post_meta($module_id, 'lessons', true);
        if (is_array($lessons)) {
            $lessons = array_filter($lessons, function($l) use ($lesson_id) {
                return $l != $lesson_id;
            });
            update_post_meta($module_id, 'lessons', array_unique($lessons));
        }
    }

    // Log the action before deletion
    global $wpdb;
    $changes = [
        'title' => ['old' => $lesson->post_title, 'new' => ''],
        'goal' => ['old' => get_post_meta($lesson_id, 'lesson-goal', true) ?: '', 'new' => ''],
        'objectives' => ['old' => get_post_meta($lesson_id, '_lesson_objectives', true) ?: [], 'new' => []],
        'activities' => ['old' => get_post_meta($lesson_id, '_lesson_activities', true) ?: [], 'new' => []],
        'course_id' => ['old' => get_post_meta($lesson_id, '_course_id', true) ?: 0, 'new' => 0],
        'module_id' => ['old' => $module_id ?: 0, 'new' => 0]
    ];

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_lesson_log',
        [
            'lesson_id' => $lesson_id,
            'user_id' => get_current_user_id(),
            'action' => 'delete',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($wpdb->last_error) {
        error_log('Courscribe: Failed to log lesson deletion - Error: ' . $wpdb->last_error);
    } else {
        error_log('Courscribe: Lesson deletion logged - Lesson ID: ' . $lesson_id);
    }

    $deleted = wp_delete_post($lesson_id, true);
    if ($deleted) {
        wp_send_json_success(['message' => 'Lesson deleted successfully.', 'lesson_id' => $lesson_id]);
    } else {
        error_log('Courscribe: Failed to delete lesson - Lesson ID: ' . $lesson_id);
        wp_send_json_error(['message' => 'Failed to delete the lesson.']);
    }

    wp_die();
}

add_action('wp_ajax_save_lesson_teaching_points', 'save_lesson_teaching_points_changes');
function save_lesson_teaching_points_changes() {
//    if (!current_user_can('edit_posts')) {
//        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to edit teaching points');
//        wp_send_json_error(['message' => 'You are not allowed to edit teaching points.']);
//        wp_die();
//    }

    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (save_lesson_teaching_points) - ' . print_r($_POST, true));

    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $teaching_points = isset($_POST['teaching_points']) && is_array($_POST['teaching_points']) ? $_POST['teaching_points'] : [];

    // Log processed input
    error_log('Courscribe: save_lesson_teaching_points_changes input - ' . print_r([
            'lesson_id' => $lesson_id,
            'module_id' => $module_id,
            'teaching_points' => $teaching_points
        ], true));

    // Validate required fields
    if (empty($lesson_id) || empty($module_id)) {
        error_log('Courscribe: Missing required fields - Lesson ID: ' . $lesson_id . ', Module ID: ' . $module_id);
        wp_send_json_error(['message' => 'Lesson ID and Module ID are required.', 'debug' => [
            'lesson_id' => empty($lesson_id),
            'module_id' => empty($module_id)
        ]]);
        wp_die();
    }

    // Validate lesson and module
    $lesson = get_post($lesson_id);
    if (!$lesson || $lesson->post_type !== 'crscribe_lesson') {
        error_log('Courscribe: Invalid lesson - Lesson ID: ' . $lesson_id);
        wp_send_json_error(['message' => 'Invalid lesson.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        error_log('Courscribe: Invalid module - Module ID: ' . $module_id);
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Sanitize teaching points
    $sanitized_teaching_points = array_map('sanitize_text_field', $teaching_points);

    // Log the action
    global $wpdb;
    $changes = [
        'teaching_points' => [
            'old' => get_post_meta($lesson_id, '_teaching_points', true) ?: [],
            'new' => $sanitized_teaching_points
        ]
    ];

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_lesson_log',
        [
            'lesson_id' => $lesson_id,
            'user_id' => get_current_user_id(),
            'action' => 'update_teaching_points',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($wpdb->last_error) {
        error_log('Courscribe: Failed to log teaching points update - Error: ' . $wpdb->last_error);
    } else {
        error_log('Courscribe: Teaching points update logged - Lesson ID: ' . $lesson_id);
    }

    // Update teaching points
    update_post_meta($lesson_id, '_teaching_points', $sanitized_teaching_points);

    wp_send_json_success(['message' => 'Lesson teaching points updated successfully.', 'lesson_id' => $lesson_id]);
    wp_die();
}

function courscribe_generate_lessons() {
    // Basic security checks
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in']);
    }
    
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'Professional';
    $audience = isset($_POST['audience']) ? sanitize_text_field($_POST['audience']) : 'Adults';
    $lesson_count = isset($_POST['lesson_count']) ? intval($_POST['lesson_count']) : 1;
    $instructions = isset($_POST['instructions']) ? sanitize_textarea_field($_POST['instructions']) : '';

    if (!$course_id || !$module_id) {
        error_log('Courscribe: Invalid course or module ID - Course ID: ' . $course_id . ', Module ID: ' . $module_id);
        wp_send_json_error(['message' => 'Invalid course or module ID']);
        wp_die();
    }

    // Fetch course data
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    $level_of_learning = esc_html(get_post_meta($course_id, 'level-of-learning', true));
    $course_objectives = maybe_unserialize(get_post_meta($course_id, '_course_objectives', true));
    $course_data = [
        'title' => get_the_title($course_id),
        'goal' => $course_goal ?: '',
        'level' => $level_of_learning ?: '',
        'objectives' => is_array($course_objectives) ? $course_objectives : []
    ];

    // Fetch module data
    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        error_log('Courscribe: Module not found - Module ID: ' . $module_id);
        wp_send_json_error(['message' => 'Module not found']);
        wp_die();
    }

    $module_data = [
        'id' => $module->ID,
        'title' => $module->post_title,
        'goal' => esc_html(get_post_meta($module->ID, '_module_goal', true)) ?: '',
        'objectives' => maybe_unserialize(get_post_meta($module->ID, '_module_objectives', true)) ?: [],
        'methods' => maybe_unserialize(get_post_meta($module->ID, '_module_methods', true)) ?: [],
        'materials' => maybe_unserialize(get_post_meta($module->ID, '_module_materials', true)) ?: []
    ];

    // Log module data for debugging
    error_log('Courscribe: Module data fetched - ' . print_r($module_data, true));

    // Prepare the full context for the prompt
    $context = "Course Title: {$course_data['title']}\n";
    $context .= "Course Goal: {$course_data['goal']}\n";
    $context .= "Course Level: {$course_data['level']}\n";
    $context .= "Course Objectives:\n";
    if (!empty($course_data['objectives'])) {
        $context .= implode("\n", array_map(function($obj) {
                return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
            }, $course_data['objectives'])) . "\n";
    } else {
        $context .= "No objectives set.\n";
    }

    $context .= "Module Title: {$module_data['title']}\n";
    $context .= "Module Goal: {$module_data['goal']}\n";
    $context .= "Module Objectives:\n";
    if (!empty($module_data['objectives'])) {
        $context .= implode("\n", array_map(function($obj) {
                return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
            }, $module_data['objectives'])) . "\n";
    } else {
        $context .= "No objectives set.\n";
    }

    $context .= "Module Methods:\n";
    if (!empty($module_data['methods'])) {
        $context .= implode("\n", array_map(function($method) {
                return "- {$method['method_type']}: {$method['title']} ({$method['location']})";
            }, $module_data['methods'])) . "\n";
    } else {
        $context .= "No methods set.\n";
    }

    $context .= "Module Materials:\n";
    if (!empty($module_data['materials'])) {
        $context .= implode("\n", array_map(function($material) {
                return "- {$material['material_type']}: {$material['title']} ({$material['link']})";
            }, $module_data['materials'])) . "\n";
    } else {
        $context .= "No materials set.\n";
    }

    // Include existing lessons in the module
    $context .= "Existing Lessons:\n";
    $lesson_query = new WP_Query([
        'post_type'      => 'crscribe_lesson',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_module_id',
                'value'   => $module_id,
                'compare' => '='
            ]
        ],
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'cache_results'  => true
    ]);

    if ($lesson_query->have_posts()) {
        foreach ($lesson_query->posts as $index => $lesson_id) {
            $context .= "Lesson " . ($index + 1) . "\n";
            $context .= "Title: " . esc_html(get_the_title($lesson_id)) . "\n";
            $context .= "Goal: " . esc_html(get_post_meta($lesson_id, 'lesson-goal', true)) . "\n";
            $context .= "Objectives:\n";
            $lesson_objectives = maybe_unserialize(get_post_meta($lesson_id, '_lesson_objectives', true));
            if (!empty($lesson_objectives) && is_array($lesson_objectives)) {
                $context .= implode("\n", array_map(function($obj) {
                        return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
                    }, $lesson_objectives)) . "\n";
            } else {
                $context .= "No objectives set.\n";
            }
            $context .= "\n";
        }
        wp_reset_postdata();
    } else {
        $context .= "No existing lessons.\n";
    }

    // Log lesson query results
    error_log('Courscribe: Lessons fetched for module ' . $module_id . ' - Count: ' . $lesson_query->post_count);

    // Add audience, tone, and additional instructions
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

    $thinking_skills_prompt = "To create objectives, use the following structure: 'Thinking Skill to Action Verb Description'. Select a Thinking Skill from the following: " . implode(", ", array_keys($thinking_skills_action_verbs)) . ".\n";
    $thinking_skills_prompt .= "Then, select an appropriate Action Verb based on the chosen Thinking Skill:\n";
    foreach ($thinking_skills_action_verbs as $skill => $verbs) {
        $thinking_skills_prompt .= "- For '$skill', use one of: " . implode(", ", $verbs) . "\n";
    }
    $thinking_skills_prompt .= "Finally, add a concise Description that completes the objective, ensuring it aligns with the lesson's goal, module's goal, course level, and audience.\n";

    // Prepare the prompt for Gemini
    $prompt = "You are an expert in educational content creation. Based on the following course and module context, generate {$lesson_count} new lesson(s) for the specified module that complement the existing lessons and align with the module's goals, course level, and objectives. Each lesson should include:\n";
    $prompt .= "- Title: A concise and relevant lesson title (do not repeat titles of existing lessons).\n";
    $prompt .= "- Goal: A short goal for the lesson (1-2 sentences) that fits within the module's overall goal.\n";
    $prompt .= "- Objectives: A list of 2-3 objectives, each in the format 'Thinking Skill to Action Verb Description', following the specific structure provided below.\n";
    $prompt .= "Do not generate methods or materials for the lessons, as these will be added separately.\n\n";
    $prompt .= $thinking_skills_prompt . "\n";
    $prompt .= $context . "\n";
    $prompt .= "Return the response in the following format for each lesson:\n";
    $prompt .= "Lesson [Number]\nTitle: [Lesson Title]\nGoal: [Lesson Goal]\nObjectives:\n- [Objective 1]\n- [Objective 2]\n- [Objective 3]\n\n";
    $prompt .= "Ensure the lessons are suitable for the audience and tone specified, and avoid duplicating existing lessons.";

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
            error_log('Courscribe: Gemini API Error: ' . $response->get_error_message());
            wp_send_json_error(['message' => 'Failed to generate lessons']);
            wp_die();
        }

        $body = json_decode($response['body'], true);
        if (isset($body['error'])) {
            error_log('Courscribe: Gemini API Error: ' . $body['error']['message']);
            wp_send_json_error(['message' => 'Failed to generate lessons']);
            wp_die();
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('Courscribe: Gemini API Invalid Response Structure');
            wp_send_json_error(['message' => 'Invalid response from API']);
            wp_die();
        }

        $response_text = $body['candidates'][0]['content']['parts'][0]['text'];

        // Parse the response into an array of lessons
        $lessons = [];
        $lesson_blocks = explode("Lesson ", trim($response_text));
        foreach ($lesson_blocks as $block) {
            if (empty($block)) continue;

            $lines = explode("\n", $block);
            $lesson = [
                'title' => '',
                'goal' => '',
                'objectives' => []
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'Title:') === 0) {
                    $lesson['title'] = trim(substr($line, 6));
                } elseif (strpos($line, 'Goal:') === 0) {
                    $lesson['goal'] = trim(substr($line, 5));
                } elseif (strpos($line, '- ') === 0 && !empty($lesson['title'])) {
                    $lesson['objectives'][] = trim(substr($line, 2));
                }
            }

            if (!empty($lesson['title'])) {
                $lessons[] = $lesson;
            }
        }

        // Log successful generation
        error_log('Courscribe: Lessons generated successfully - Count: ' . count($lessons));
        wp_send_json_success(['lessons' => $lessons]);
    } catch (Exception $e) {
        error_log('Courscribe: Gemini API Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error generating lessons']);
    }

    wp_die();
}
add_action('wp_ajax_courscribe_generate_lessons', 'courscribe_generate_lessons');
?>