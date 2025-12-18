<?php
// Path: courscribe/actions/courscribe-module-actions.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Idempotency helper functions
 */
function courscribe_generate_request_hash_module($data) {
    return hash('sha256', json_encode($data) . get_current_user_id());
}

function courscribe_is_duplicate_request_modules($hash, $expiry_minutes = 5) {
    $transient_key = 'courscribe_req_' . substr($hash, 0, 32);
    $existing = get_transient($transient_key);
    
    if ($existing) {
        return true; // Duplicate request
    }
    
    // Store hash for specified duration
    set_transient($transient_key, time(), $expiry_minutes * 60);
    return false;
}

/**
 * Save new module
 */
function save_new_module() {
   //check_ajax_referer('courscribe_nonce', 'nonce');
//
//    if (!current_user_can('edit_crscribe_modules')) {
//        wp_send_json_error(['message' => 'You are not allowed to add modules.']);
//        wp_die();
//    }

    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $module_name = isset($_POST['module_name']) ? sanitize_text_field($_POST['module_name']) : '';
    $module_goal = isset($_POST['module_goal']) ? sanitize_text_field($_POST['module_goal']) : '';
    $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = get_post_meta($course_id, '_studio_id', true);
    $current_user = wp_get_current_user();

    // Validate
    if (empty($course_id) || empty($module_name) || empty($module_goal) || empty($objectives)) {
        wp_send_json_error(['message' => 'All fields are required.']);
        wp_die();
    }

    if (!get_post($course_id) || get_post($course_id)->post_type !== 'crscribe_course') {
        wp_send_json_error(['message' => 'Invalid course.']);
        wp_die();
    }

    // Check tier restrictions
//    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
//    $module_count = count(get_posts([
//        'post_type' => 'crscribe_module',
//        'post_status' => 'publish',
//        'meta_query' => [
//            [
//                'key' => '_course_id',
//                'value' => $course_id,
//                'compare' => '=',
//            ],
//        ],
//    ]));
//    if ($tier === 'basics' && $module_count >= 5) {
//        wp_send_json_error(['message' => 'Your plan (Basics) allows only 5 modules per course. Upgrade to create more.']);
//        wp_die();
//    } elseif ($tier === 'plus' && $module_count >= 10) {
//        wp_send_json_error(['message' => 'Your plan (Plus) allows only 10 modules per course. Upgrade to Pro for unlimited.']);
//        wp_die();
//    }

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb' => sanitize_text_field($objective['action_verb'] ?? ''),
            'description' => sanitize_text_field($objective['description'] ?? ''),
        ];
    }

    // Create module
    $module_id = wp_insert_post([
        'post_type' => 'crscribe_module',
        'post_title' => $module_name,
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
    ], true);

    if (is_wp_error($module_id)) {
        wp_send_json_error(['message' => 'Failed to create the module: ' . $module_id->get_error_message()]);
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
            'module_id' => $module_id,
            'user_id' => $current_user->ID,
            'action' => 'create',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => 'Module created successfully.',
        'module_id' => $module_id,
    ]);
    wp_die();
}
add_action('wp_ajax_save_new_module', 'save_new_module');

function save_new_ai_module() {
    // Log raw POST data for debugging
    error_log('Courscribe: Raw POST data (save_new_ai_module) - ' . print_r($_POST, true));

    // Check nonce
//    if (!check_ajax_referer('courscribe_nonce', 'nonce', false)) {
//        error_log('Courscribe: Nonce verification failed - Nonce: ' . ($_POST['nonce'] ?? 'missing') . ', User ID: ' . get_current_user_id());
//        wp_send_json_error(['message' => 'Security check failed. Please try again.']);
//        wp_die();
//    }

    // Check permissions
//    if (!current_user_can('edit_crscribe_modules') && !current_user_can('edit_posts')) {
//        error_log('Courscribe: User ' . get_current_user_id() . ' denied permission to add modules');
//        wp_send_json_error(['message' => 'You are not allowed to add modules.']);
//        wp_die();
//    }

    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $curriculum_id = isset($_POST['curriculum_id']) ? absint($_POST['curriculum_id']) : 0;
    $module_name = isset($_POST['module_name']) ? sanitize_text_field($_POST['module_name']) : '';
    $module_goal = isset($_POST['module_goal']) ? sanitize_text_field($_POST['module_goal']) : '';
    $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
    $studio_id = get_post_meta($course_id, '_studio_id', true);
    $current_user = wp_get_current_user();

    // Validate inputs
    $errors = [];
    if (empty($course_id) || !get_post($course_id) || get_post($course_id)->post_type !== 'crscribe_course') {
        $errors[] = 'Invalid course ID.';
    }
    if (empty($curriculum_id) || !get_post($curriculum_id) || get_post($curriculum_id)->post_type !== 'crscribe_curriculum') {
        $errors[] = 'Invalid curriculum ID.';
    }
    if (empty($module_name)) {
        $errors[] = 'Module name is required.';
    }
    if (empty($module_goal)) {
        $errors[] = 'Module goal is required.';
    }
    if (empty($objectives)) {
        $errors[] = 'At least one objective is required.';
    } else {
        foreach ($objectives as $index => $objective) {
            if (empty($objective['thinking_skill']) || empty($objective['action_verb']) || empty($objective['description'])) {
                $errors[] = "Objective " . ($index + 1) . " is incomplete.";
            }
        }
    }

    if (!empty($errors)) {
        error_log('Courscribe: Validation errors - ' . print_r($errors, true));
        wp_send_json_error(['message' => implode(' ', $errors)]);
        wp_die();
    }

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb' => sanitize_text_field($objective['action_verb'] ?? ''),
            'description' => sanitize_text_field($objective['description'] ?? '')
        ];
    }

    // Create module
    $module_id = wp_insert_post([
        'post_type' => 'crscribe_module',
        'post_title' => $module_name,
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
        'meta_input' => [
            '_module_goal' => $module_goal,
            '_module_objectives' => maybe_serialize($sanitized_objectives),
            '_course_id' => $course_id,
            '_curriculum_id' => $curriculum_id,
            '_studio_id' => $studio_id,
            '_creator_id' => $current_user->ID
        ]
    ], true);

    if (is_wp_error($module_id)) {
        error_log('Courscribe: Failed to create module - Error: ' . $module_id->get_error_message());
        wp_send_json_error(['message' => 'Failed to create the module: ' . $module_id->get_error_message()]);
        wp_die();
    }

    // Log action
    global $wpdb;
    $changes = [
        'title' => ['new' => $module_name, 'old' => ''],
        'goal' => ['new' => $module_goal, 'old' => ''],
        'objectives' => ['new' => $sanitized_objectives, 'old' => []],
        'course_id' => ['new' => $course_id, 'old' => 0],
        'curriculum_id' => ['new' => $curriculum_id, 'old' => 0],
        'studio_id' => ['new' => $studio_id, 'old' => 0]
    ];

    $result = $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => $current_user->ID,
            'action' => 'create',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        error_log('Courscribe: Failed to log module creation - Error: ' . $wpdb->last_error);
    } else {
        error_log('Courscribe: Module creation logged - Module ID: ' . $module_id);
    }

    wp_send_json_success([
        'message' => 'Module created successfully.',
        'module_id' => $module_id
    ]);
    wp_die();
}
add_action('wp_ajax_save_ai_new_module', 'save_new_ai_module');

/**
 * Save module changes
 */
function save_module_changes() {
    // Debug: Log request start
    error_log('save_module_changes called');

//    // Check nonce
//    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_nonce')) {
//        error_log('Nonce verification failed');
//        wp_send_json_error(['message' => 'Nonce verification failed.']);
//        wp_die();
//    }
//
//    // Check permissions
//    error_log('User permissions: ' . (current_user_can('edit_crscribe_modules') ? 'Allowed' : 'Denied'));
//    if (!current_user_can('edit_crscribe_modules')) {
//        wp_send_json_error(['message' => 'You are not allowed to edit modules.']);
//        wp_die();
//    }

    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $module_name = isset($_POST['module_name']) ? sanitize_text_field($_POST['module_name']) : '';
    $module_goal = isset($_POST['module_goal']) ? sanitize_text_field($_POST['module_goal']) : '';
    $objectives = isset($_POST['objectives']) ? json_decode(stripslashes($_POST['objectives']), true) : [];

    // Debug input
    error_log('save_module_changes input: ' . print_r([
            'module_id' => $module_id,
            'course_id' => $course_id,
            'module_name' => $module_name,
            'module_goal' => $module_goal,
            'objectives' => $objectives,
            'raw_objectives' => $_POST['objectives'] ?? 'not set',
            'methods' => $_POST['methods'] ?? 'not set',
            'materials' => $_POST['materials'] ?? 'not set'
        ], true));

    // Check JSON decoding
    if (isset($_POST['objectives']) && json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        wp_send_json_error(['message' => 'Invalid objectives format.', 'json_error' => json_last_error_msg()]);
        wp_die();
    }

    // Validate
    if (empty($module_id) || empty($course_id) || empty($module_name) || empty($module_goal) || empty($objectives)) {
        wp_send_json_error(['message' => 'All fields are required.', 'debug' => [
            'module_id' => empty($module_id),
            'course_id' => empty($course_id),
            'module_name' => empty($module_name),
            'module_goal' => empty($module_goal),
            'objectives' => empty($objectives)
        ]]);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    if (!get_post($course_id) || get_post($course_id)->post_type !== 'crscribe_course') {
        wp_send_json_error(['message' => 'Invalid course.']);
        wp_die();
    }

    // Sanitize objectives
    $sanitized_objectives = [];
    foreach ($objectives as $objective) {
        $sanitized_objectives[] = [
            'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
            'action_verb' => sanitize_text_field($objective['action_verb'] ?? ''),
            'description' => sanitize_text_field($objective['description'] ?? '')
        ];
    }

    // Sanitize methods
    $methods = isset($_POST['methods']) ? json_decode(stripslashes($_POST['methods']), true) : [];
    $sanitized_methods = [];
    foreach ($methods as $method) {
        $sanitized_methods[] = [
            'method_type' => sanitize_text_field($method['method_type'] ?? ''),
            'title' => sanitize_text_field($method['title'] ?? ''),
            'location' => sanitize_text_field($method['location'] ?? '')
        ];
    }

    // Sanitize materials
    $materials = isset($_POST['materials']) ? json_decode(stripslashes($_POST['materials']), true) : [];
    $sanitized_materials = [];
    foreach ($materials as $material) {
        $sanitized_materials[] = [
            'material_type' => sanitize_text_field($material['material_type'] ?? ''),
            'title' => sanitize_text_field($material['title'] ?? ''),
            'link' => esc_url_raw($material['link'] ?? '')
        ];
    }

    // Handle media uploads
    $media_urls = maybe_unserialize(get_post_meta($module_id, '_module_media', true)) ?: [];
    if (!empty($_FILES['media']['name'][0])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $files = $_FILES['media'];
        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
                $_FILES = ['upload' => $file];
                $attachment_id = media_handle_upload('upload', 0);
                if (!is_wp_error($attachment_id)) {
                    $media_urls[] = wp_get_attachment_url($attachment_id);
                }
            }
        }
    }

    // Update module
    $post_data = [
        'ID' => $module_id,
        'post_title' => $module_name,
        'meta_input' => [
            '_module_goal' => $module_goal,
            '_module_objectives' => maybe_serialize($sanitized_objectives),
            '_module_methods' => maybe_serialize($sanitized_methods),
            '_module_materials' => maybe_serialize($sanitized_materials),
            '_module_media' => maybe_serialize($media_urls)
        ]
    ];

    $updated = wp_update_post($post_data, true);
    if (is_wp_error($updated)) {
        wp_send_json_error(['message' => 'Failed to update module: ' . $updated->get_error_message()]);
        wp_die();
    }

    // Log changes
    global $wpdb;
    $old_post = get_post($module_id);
    $changes = [
        'title' => ['old' => $old_post->post_title, 'new' => $module_name],
        'goal' => ['old' => get_post_meta($module_id, '_module_goal', true), 'new' => $module_goal],
        'objectives' => ['old' => maybe_unserialize(get_post_meta($module_id, '_module_objectives', true)), 'new' => $sanitized_objectives],
        'methods' => ['old' => maybe_unserialize(get_post_meta($module_id, '_module_methods', true)), 'new' => $sanitized_methods],
        'materials' => ['old' => maybe_unserialize(get_post_meta($module_id, '_module_materials', true)), 'new' => $sanitized_materials],
        'media' => ['old' => maybe_unserialize(get_post_meta($module_id, '_module_media', true)), 'new' => $media_urls]
    ];

    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'update',
            'changes' => wp_json_encode($changes),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Module updated successfully.']);
    wp_die();
}
add_action('wp_ajax_save_module_changes', 'save_module_changes');

/**
 * Delete module
 */
function handle_delete_module() {
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

//    if (!current_user_can('delete_crscribe_modules')) {
//        wp_send_json_error(['message' => 'You are not allowed to delete modules.']);
//        wp_die();
//    }

    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

    if (empty($module_id) || empty($course_id)) {
        wp_send_json_error(['message' => 'Invalid module or course ID.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    $deleted = wp_delete_post($module_id, true);
    if (!$deleted) {
        wp_send_json_error(['message' => 'Failed to delete module.']);
        wp_die();
    }

    // Log action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'delete',
            'changes' => wp_json_encode(['module_name' => $module->post_title]),
            'timestamp' => current_time('mysql'),
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Module deleted successfully.']);
    wp_die();
}
add_action('wp_ajax_handle_delete_module', 'handle_delete_module');

function courscribe_generate_modules() {
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'Professional';
    $audience = isset($_POST['audience']) ? sanitize_text_field($_POST['audience']) : 'Adults';
    $module_count = isset($_POST['module_count']) ? intval($_POST['module_count']) : 1;
    $instructions = isset($_POST['instructions']) ? sanitize_textarea_field($_POST['instructions']) : '';

    if (!$course_id) {
        wp_send_json_error(['message' => 'Invalid course ID']);
        wp_die();
    }

    // Fetch course data for context
    $course_data = get_post_meta($course_id, '_courscribe_course_data', true);
    if (!is_array($course_data)) {
        $course_data = [
            'title' => get_the_title($course_id),
            'goal' => '',
            'level' => '',
            'objectives' => [],
            'modules' => []
        ];
    }

    // Prepare the full course context for the prompt
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

    // Include existing modules
    $context .= "Existing Modules:\n";
    if (!empty($course_data['modules'])) {
        foreach ($course_data['modules'] as $index => $module) {
            $context .= "Module " . ($index + 1) . "\n";
            $context .= "Title: {$module['title']}\n";
            $context .= "Goal: {$module['goal']}\n";
            $context .= "Objectives:\n";
            if (!empty($module['objectives'])) {
                $context .= implode("\n", array_map(function($obj) {
                        return "- {$obj['thinking_skill']} to {$obj['action_verb']} {$obj['description']}";
                    }, $module['objectives'])) . "\n";
            } else {
                $context .= "No objectives set.\n";
            }
            $context .= "\n";
        }
    } else {
        $context .= "No existing modules.\n";
    }

    // Add audience, tone, and additional instructions
    $context .= "Audience: {$audience}\n";
    $context .= "Tone: {$tone}\n";
    $context .= "Additional Instructions: {$instructions}\n";

    // Define the thinking skills and action verbs for the prompt
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
    $thinking_skills_prompt .= "Finally, add a concise Description that completes the objective, ensuring it aligns with the module's goal, course level, and audience.\n";

    // Prepare the prompt for Gemini
    $prompt = "You are an expert in educational content creation. Based on the following course context, generate {$module_count} new module(s) for the course that complement the existing modules and align with the course's goals, level, and objectives. Each module should include:\n";
    $prompt .= "- Title: A concise and relevant module title (do not repeat titles of existing modules).\n";
    $prompt .= "- Goal: A short goal for the module (1-2 sentences) that fits within the course's overall goal.\n";
    $prompt .= "- Objectives: A list of 2-3 objectives, each in the format 'Thinking Skill to Action Verb Description', following the specific structure provided below.\n\n";
    $prompt .= $thinking_skills_prompt . "\n";
    $prompt .= $context . "\n";
    $prompt .= "Return the response in the following format for each module:\n";
    $prompt .= "Module [Number]\nTitle: [Module Title]\nGoal: [Module Goal]\nObjectives:\n- [Objective 1]\n- [Objective 2]\n- [Objective 3]\n\n";
    $prompt .= "Ensure the modules are suitable for the audience and tone specified, and avoid duplicating existing modules.";

    try {
        $api_key = 'AIzaSyBB5ZYwktOFI3R3j_vs8U7CxwKgS3XNgM0'; // Use get_option if needed
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
            error_log('Gemini API Error: ' . $response->get_error_message());
            wp_send_json_error(['message' => 'Failed to generate modules']);
            wp_die();
        }

        $body = json_decode($response['body'], true);
        if (isset($body['error'])) {
            error_log('Gemini API Error: ' . $body['error']['message']);
            wp_send_json_error(['message' => 'Failed to generate modules']);
            wp_die();
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('Gemini API Invalid Response Structure');
            wp_send_json_error(['message' => 'Invalid response from API']);
            wp_die();
        }

        $response_text = $body['candidates'][0]['content']['parts'][0]['text'];

        // Parse the response into an array of modules
        $modules = [];
        $module_blocks = explode("Module ", trim($response_text));
        foreach ($module_blocks as $block) {
            if (empty($block)) continue;

            $lines = explode("\n", $block);
            $module = [
                'title' => '',
                'goal' => '',
                'objectives' => []
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'Title:') === 0) {
                    $module['title'] = trim(substr($line, 6));
                } elseif (strpos($line, 'Goal:') === 0) {
                    $module['goal'] = trim(substr($line, 5));
                } elseif (strpos($line, '- ') === 0 && !empty($module['title'])) {
                    $module['objectives'][] = trim(substr($line, 2));
                }
            }

            if (!empty($module['title'])) {
                $modules[] = $module;
            }
        }

        wp_send_json_success(['modules' => $modules]);
    } catch (Exception $e) {
        error_log('Gemini API Exception: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Error generating modules']);
    }

    wp_die();
}
add_action('wp_ajax_courscribe_generate_modules', 'courscribe_generate_modules');

/**
 * Update individual module field with idempotency
 */
function update_module_field() {
    // Use standard nonce for premium modules
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }
    
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $field_type = isset($_POST['field_type']) ? sanitize_text_field($_POST['field_type']) : '';
    $field_value = isset($_POST['field_value']) ? sanitize_text_field($_POST['field_value']) : '';
    $timestamp = isset($_POST['timestamp']) ? absint($_POST['timestamp']) : time();

    // Idempotency check
    $hash_data = [
        'module_id' => $module_id,
        'field_type' => $field_type,
        'field_value' => $field_value,
        'timestamp' => $timestamp
    ];
    $request_hash = courscribe_generate_request_hash_module($hash_data);
    
    if (courscribe_is_duplicate_request_modules($request_hash)) {
        wp_send_json_success([
            'message' => ucfirst($field_type) . ' updated successfully.',
            'duplicate' => true
        ]);
        wp_die();
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    // Validate inputs
    if (empty($module_id) || empty($field_type)) {
        wp_send_json_error(['message' => 'Invalid request parameters.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Field-specific validation
    switch ($field_type) {
        case 'name':
            if (strlen($field_value) < 3 || strlen($field_value) > 100) {
                wp_send_json_error(['message' => 'Module name must be between 3 and 100 characters.']);
                wp_die();
            }
            // Update post title
            $result = wp_update_post([
                'ID' => $module_id,
                'post_title' => $field_value
            ], true);
            $success_message = 'Module name updated successfully.';
            break;
            
        case 'goal':
            if (strlen($field_value) < 10 || strlen($field_value) > 500) {
                wp_send_json_error(['message' => 'Module goal must be between 10 and 500 characters.']);
                wp_die();
            }
            // Update meta field
            $result = update_post_meta($module_id, '_module_goal', $field_value);
            $success_message = 'Module goal updated successfully.';
            break;
            
        default:
            wp_send_json_error(['message' => 'Invalid field type.']);
            wp_die();
    }

    if (is_wp_error($result) || $result === false) {
        wp_send_json_error(['message' => 'Failed to update field.']);
        wp_die();
    }

    // Log the change
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'update_field',
            'changes' => wp_json_encode([
                'field' => $field_type,
                'old_value' => ($field_type === 'name') ? $module->post_title : get_post_meta($module_id, '_module_goal', true),
                'new_value' => $field_value
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => $success_message]);
    wp_die();
}
add_action('wp_ajax_update_module_field', 'update_module_field');

/**
 * Archive module
 */
function courscribe_archive_module() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }
    
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id) || empty($course_id)) {
        wp_send_json_error(['message' => 'Invalid archive parameters.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Update post status to 'archived' (custom status)
    $result = wp_update_post([
        'ID' => $module_id,
        'post_status' => 'archived'
    ], true);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'Failed to archive module: ' . $result->get_error_message()]);
        wp_die();
    }

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'archive',
            'changes' => wp_json_encode(['module_name' => $module->post_title]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Module archived successfully.']);
    wp_die();
}
add_action('wp_ajax_courscribe_archive_module', 'courscribe_archive_module');

/**
 * Restore archived module
 */
function courscribe_restore_module() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }
    
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id) || empty($course_id)) {
        wp_send_json_error(['message' => 'Invalid restore parameters.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Restore post status to 'publish'
    $result = wp_update_post([
        'ID' => $module_id,
        'post_status' => 'publish'
    ], true);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'Failed to restore module: ' . $result->get_error_message()]);
        wp_die();
    }

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'restore',
            'changes' => wp_json_encode(['module_name' => $module->post_title]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Module restored successfully.']);
    wp_die();
}
add_action('wp_ajax_courscribe_restore_module', 'courscribe_restore_module');

/**
 * Save module additional data (methods, materials, media)
 */
function save_module_additional_data() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }
    
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id)) {
        wp_send_json_error(['message' => 'Invalid module ID.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Process methods
    if (isset($_POST['methods'])) {
        $methods = json_decode(stripslashes($_POST['methods']), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($methods)) {
            $sanitized_methods = [];
            foreach ($methods as $method) {
                $sanitized_methods[] = [
                    'method_type' => sanitize_text_field($method['method_type'] ?? ''),
                    'title' => sanitize_text_field($method['title'] ?? ''),
                    'location' => sanitize_text_field($method['location'] ?? '')
                ];
            }
            update_post_meta($module_id, '_module_methods', maybe_serialize($sanitized_methods));
        }
    }

    // Process materials
    if (isset($_POST['materials'])) {
        $materials = json_decode(stripslashes($_POST['materials']), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($materials)) {
            $sanitized_materials = [];
            foreach ($materials as $material) {
                $sanitized_materials[] = [
                    'material_type' => sanitize_text_field($material['material_type'] ?? ''),
                    'title' => sanitize_text_field($material['title'] ?? ''),
                    'link' => esc_url_raw($material['link'] ?? '')
                ];
            }
            update_post_meta($module_id, '_module_materials', maybe_serialize($sanitized_materials));
        }
    }

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'update_additional_data',
            'changes' => wp_json_encode([
                'methods_count' => isset($sanitized_methods) ? count($sanitized_methods) : 0,
                'materials_count' => isset($sanitized_materials) ? count($sanitized_materials) : 0
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Additional data saved successfully.']);
    wp_die();
}
add_action('wp_ajax_save_module_additional_data', 'save_module_additional_data');

/**
 * Register custom post status for archived modules
 */
function courscribe_register_archived_post_status() {
    register_post_status('archived', array(
        'label' => _x('Archived', 'post status', 'courscribe'),
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => false,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop(
            'Archived <span class="count">(%s)</span>',
            'Archived <span class="count">(%s)</span>',
            'courscribe'
        ),
    ));
}
add_action('init', 'courscribe_register_archived_post_status');

/**
 * Save individual module objective
 */
function save_module_objective() {
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $objective_id = isset($_POST['objective_id']) ? sanitize_text_field($_POST['objective_id']) : '';
    $objective_data = isset($_POST['objective_data']) ? json_decode(stripslashes($_POST['objective_data']), true) : [];
    
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id) || empty($objective_id) || empty($objective_data)) {
        wp_send_json_error(['message' => 'Invalid save objective parameters.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Get existing objectives
    $existing_objectives = maybe_unserialize(get_post_meta($module_id, '_module_objectives', true)) ?: [];
    
    // Update or add objective
    $objective_found = false;
    foreach ($existing_objectives as $index => $objective) {
        if (isset($objective['id']) && $objective['id'] === $objective_id) {
            $existing_objectives[$index] = array_merge($objective, [
                'thinking_skill' => sanitize_text_field($objective_data['thinking_skill'] ?? ''),
                'action_verb' => sanitize_text_field($objective_data['action_verb'] ?? ''),
                'description' => sanitize_text_field($objective_data['description'] ?? '')
            ]);
            $objective_found = true;
            break;
        }
    }
    
    // If not found, add new objective
    if (!$objective_found) {
        $existing_objectives[] = [
            'id' => $objective_id,
            'thinking_skill' => sanitize_text_field($objective_data['thinking_skill'] ?? ''),
            'action_verb' => sanitize_text_field($objective_data['action_verb'] ?? ''),
            'description' => sanitize_text_field($objective_data['description'] ?? '')
        ];
    }

    // Save updated objectives
    update_post_meta($module_id, '_module_objectives', maybe_serialize($existing_objectives));

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'update_objective',
            'changes' => wp_json_encode([
                'objective_id' => $objective_id,
                'data' => $objective_data
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Objective saved successfully.']);
    wp_die();
}
add_action('wp_ajax_save_module_objective', 'save_module_objective');

/**
 * Upload module media
 */
function upload_module_media() {
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id)) {
        wp_send_json_error(['message' => 'Invalid module ID.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Handle file uploads
    if (empty($_FILES['media'])) {
        wp_send_json_error(['message' => 'No files uploaded.']);
        wp_die();
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $uploaded_urls = [];
    $files = $_FILES['media'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/mov', 'video/avi', 'audio/mp3', 'audio/wav', 'audio/ogg', 'application/pdf'];
            if (!in_array($file['type'], $allowed_types)) {
                continue; // Skip invalid files
            }
            
            $_FILES = ['upload' => $file];
            $attachment_id = media_handle_upload('upload', 0);
            
            if (!is_wp_error($attachment_id)) {
                $uploaded_urls[] = wp_get_attachment_url($attachment_id);
            }
        }
    }

    if (empty($uploaded_urls)) {
        wp_send_json_error(['message' => 'No valid files were uploaded.']);
        wp_die();
    }

    // Get existing media and add new ones
    $existing_media = maybe_unserialize(get_post_meta($module_id, '_module_media', true)) ?: [];
    $updated_media = array_merge($existing_media, $uploaded_urls);
    
    // Save updated media
    update_post_meta($module_id, '_module_media', maybe_serialize($updated_media));

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'upload_media',
            'changes' => wp_json_encode([
                'uploaded_files' => count($uploaded_urls),
                'urls' => $uploaded_urls
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => count($uploaded_urls) . ' file(s) uploaded successfully.',
        'urls' => $uploaded_urls
    ]);
    wp_die();
}
add_action('wp_ajax_upload_module_media', 'upload_module_media');

/**
 * Remove module media
 */
function remove_module_media() {
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $media_url = isset($_POST['media_url']) ? esc_url_raw($_POST['media_url']) : '';
    
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    if (!current_user_can('delete_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id) || empty($media_url)) {
        wp_send_json_error(['message' => 'Invalid remove media parameters.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Get existing media
    $existing_media = maybe_unserialize(get_post_meta($module_id, '_module_media', true)) ?: [];
    
    // Remove the specified URL
    $updated_media = array_filter($existing_media, function($url) use ($media_url) {
        return $url !== $media_url;
    });
    
    // Reindex array
    $updated_media = array_values($updated_media);
    
    // Save updated media
    update_post_meta($module_id, '_module_media', maybe_serialize($updated_media));

    // Try to delete the attachment from WordPress
    $attachment_id = attachment_url_to_postid($media_url);
    if ($attachment_id) {
        wp_delete_attachment($attachment_id, true);
    }

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'remove_media',
            'changes' => wp_json_encode([
                'removed_url' => $media_url
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Media removed successfully.']);
    wp_die();
}
add_action('wp_ajax_remove_module_media', 'remove_module_media');

/**
 * Enhanced AJAX handlers with idempotency
 */

/**
 * Upload module material files
 */
function courscribe_upload_module_material() {
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    if (!current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id)) {
        wp_send_json_error(['message' => 'Invalid module ID.']);
        wp_die();
    }

    $module = get_post($module_id);
    if (!$module || $module->post_type !== 'crscribe_module') {
        wp_send_json_error(['message' => 'Invalid module.']);
        wp_die();
    }

    // Handle material file uploads
    if (empty($_FILES['material'])) {
        wp_send_json_error(['message' => 'No files uploaded.']);
        wp_die();
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $uploaded_materials = [];
    $files = $_FILES['material'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            // Validate file type for materials
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/zip'];
            if (!in_array($file['type'], $allowed_types)) {
                continue; // Skip invalid files
            }
            
            $_FILES = ['upload' => $file];
            $attachment_id = media_handle_upload('upload', 0);
            
            if (!is_wp_error($attachment_id)) {
                $uploaded_materials[] = [
                    'title' => pathinfo($file['name'], PATHINFO_FILENAME),
                    'file_url' => wp_get_attachment_url($attachment_id),
                    'material_type' => 'Document'
                ];
            }
        }
    }

    if (empty($uploaded_materials)) {
        wp_send_json_error(['message' => 'No valid files were uploaded.']);
        wp_die();
    }

    // Get existing materials and add new ones
    $existing_materials = maybe_unserialize(get_post_meta($module_id, '_module_materials', true)) ?: [];
    $updated_materials = array_merge($existing_materials, $uploaded_materials);
    
    // Save updated materials
    update_post_meta($module_id, '_module_materials', maybe_serialize($updated_materials));

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => $module_id,
            'user_id' => get_current_user_id(),
            'action' => 'upload_materials',
            'changes' => wp_json_encode([
                'uploaded_files' => count($uploaded_materials),
                'materials' => $uploaded_materials
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => count($uploaded_materials) . ' material(s) uploaded successfully.',
        'materials' => $uploaded_materials
    ]);
    wp_die();
}
add_action('wp_ajax_courscribe_upload_module_material', 'courscribe_upload_module_material');

/**
 * Save module order with idempotency
 */
function courscribe_save_module_order() {
    $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
    $order_data = isset($_POST['order_data']) && is_array($_POST['order_data']) ? $_POST['order_data'] : [];
    $timestamp = isset($_POST['timestamp']) ? absint($_POST['timestamp']) : time();
    
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    // Idempotency check
    $hash_data = [
        'course_id' => $course_id,
        'order_data' => $order_data,
        'timestamp' => $timestamp
    ];
    $request_hash = courscribe_generate_request_hash_module($hash_data);
    
    if (courscribe_is_duplicate_request_modules($request_hash)) {
        wp_send_json_success([
            'message' => 'Module order saved successfully.',
            'duplicate' => true
        ]);
        wp_die();
    }

    if (empty($course_id) || empty($order_data)) {
        wp_send_json_error(['message' => 'Invalid order data.']);
        wp_die();
    }

    // Update module order
    foreach ($order_data as $module_order) {
        $module_id = absint($module_order['id']);
        $order = absint($module_order['order']);
        
        if ($module_id && $order) {
            wp_update_post([
                'ID' => $module_id,
                'menu_order' => $order
            ]);
        }
    }

    // Log the action
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'courscribe_module_log',
        [
            'module_id' => 0, // Course-level action
            'user_id' => get_current_user_id(),
            'action' => 'reorder_modules',
            'changes' => wp_json_encode([
                'course_id' => $course_id,
                'new_order' => $order_data
            ]),
            'timestamp' => current_time('mysql')
        ],
        ['%d', '%d', '%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => 'Module order saved successfully.',
        'hash' => $request_hash
    ]);
    wp_die();
}
add_action('wp_ajax_courscribe_save_module_order', 'courscribe_save_module_order');

/**
 * Get module logs with pagination and filtering
 */
function courscribe_get_module_logs() {
    $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
    $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date-desc';
    
    if (!wp_verify_nonce($_POST['nonce'], 'courscribe_module_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
        wp_die();
    }

    if (!current_user_can('read')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        wp_die();
    }

    if (empty($module_id)) {
        wp_send_json_error(['message' => 'Invalid module ID.']);
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_module_log';
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Build WHERE clause
    $where_conditions = ['module_id = %d'];
    $where_values = [$module_id];

    if ($filter !== 'all') {
        $where_conditions[] = 'action = %s';
        $where_values[] = $filter;
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    // Build ORDER clause
    $order_clause = 'ORDER BY ';
    switch ($sort) {
        case 'date-asc':
            $order_clause .= 'timestamp ASC';
            break;
        case 'action':
            $order_clause .= 'action ASC, timestamp DESC';
            break;
        default:
            $order_clause .= 'timestamp DESC';
    }

    // Get logs
    $query = $wpdb->prepare(
        "SELECT * FROM {$table_name} {$where_clause} {$order_clause} LIMIT %d OFFSET %d",
        array_merge($where_values, [$per_page, $offset])
    );
    
    $logs = $wpdb->get_results($query);

    // Get total count for pagination
    $count_query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} {$where_clause}",
        $where_values
    );
    $total_logs = $wpdb->get_var($count_query);
    $total_pages = ceil($total_logs / $per_page);

    // Format logs HTML
    $logs_html = '';
    if (!empty($logs)) {
        foreach ($logs as $log) {
            $user = get_userdata($log->user_id);
            $user_name = $user ? $user->display_name : 'Unknown User';
            $changes = json_decode($log->changes, true);
            
            $logs_html .= '<div class="cs-log-item">';
            $logs_html .= '<div class="cs-log-header">';
            $logs_html .= '<span class="cs-log-action cs-log-action-' . esc_attr($log->action) . '">' . esc_html(ucfirst($log->action)) . '</span>';
            $logs_html .= '<span class="cs-log-date">' . esc_html(date('M j, Y g:i A', strtotime($log->timestamp))) . '</span>';
            $logs_html .= '</div>';
            $logs_html .= '<div class="cs-log-user">by ' . esc_html($user_name) . '</div>';
            if ($changes) {
                $logs_html .= '<div class="cs-log-changes">' . esc_html(wp_json_encode($changes)) . '</div>';
            }
            $logs_html .= '</div>';
        }
    } else {
        $logs_html = '<div class="cs-empty-state">No logs found for the selected filters.</div>';
    }

    // Pagination HTML
    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html .= '<div class="cs-pagination">';
        
        if ($page > 1) {
            $pagination_html .= '<button class="cs-page-btn" data-page="' . ($page - 1) . '">Previous</button>';
        }
        
        for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
            $active_class = ($i === $page) ? ' active' : '';
            $pagination_html .= '<button class="cs-page-btn' . $active_class . '" data-page="' . $i . '">' . $i . '</button>';
        }
        
        if ($page < $total_pages) {
            $pagination_html .= '<button class="cs-page-btn" data-page="' . ($page + 1) . '">Next</button>';
        }
        
        $pagination_html .= '</div>';
    }

    wp_send_json_success([
        'logs' => $logs_html,
        'pagination' => $pagination_html,
        'total' => $total_logs
    ]);
    wp_die();
}
add_action('wp_ajax_courscribe_get_module_logs', 'courscribe_get_module_logs');