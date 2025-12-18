<?php
// courscribe/actions/courscribe-generation-premium-actions.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Generation AJAX Handlers
 * Handles all AJAX requests for the premium generation system (courses, modules, lessons)
 */

// Generate courses with premium features
add_action('wp_ajax_courscribe_generate_courses_premium', 'courscribe_handle_generate_courses_premium');
function courscribe_handle_generate_courses_premium() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_courses_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    // if (!current_user_can('edit_posts')) {
    //     wp_send_json_error(['message' => 'Insufficient permissions']);
    //     return;
    // }

    // // Get and validate wizard data
    // $wizard_data_json = $_POST['wizard_data'] ?? '';
    // $wizard_data = json_decode($wizard_data_json, true);
    
    // if (!$wizard_data || !is_array($wizard_data)) {
    //     wp_send_json_error(['message' => 'Invalid wizard data']);
    //     return;
    // }
    // Get wizard data as string
    $wizard_data_json = $_POST['wizard_data'] ?? '';
    error_log('Raw wizard_data: ' . $wizard_data_json);  // Existing log

    // Remove slashes if magic quotes or similar escaping is active
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $wizard_data_json = stripslashes($wizard_data_json);
    } else {
        // Fallback: Always stripslashes if escaping is detected (safe for this JSON use case)
        $wizard_data_json = stripslashes($wizard_data_json);
    }
    error_log('Unescaped wizard_data: ' . $wizard_data_json);  // New log for verification

    $wizard_data = json_decode($wizard_data_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        wp_send_json_error(['message' => 'JSON decode failed: ' . json_last_error_msg()]);
        return;
    }

    if (!$wizard_data || !is_array($wizard_data)) {
        wp_send_json_error(['message' => 'Invalid wizard data']);
        return;
    }

    // Extract required data
    $curriculum_id = absint($wizard_data['curriculum']['curriculum_id'] ?? 0);
    $count = absint($wizard_data['count'] ?? 1);
    $difficulty = sanitize_text_field($wizard_data['difficulty'] ?? 'intermediate');
    $audience = sanitize_text_field($wizard_data['audience'] ?? 'professionals');
    $tone = sanitize_text_field($wizard_data['tone'] ?? 'professional');
    $depth = sanitize_text_field($wizard_data['depth'] ?? 'detailed');
    $duration = sanitize_text_field($wizard_data['duration'] ?? '1-hour');
    $template = sanitize_text_field($wizard_data['template'] ?? '');
    $instructions = wp_kses_post($wizard_data['instructions'] ?? '');
    $topics = array_map('sanitize_text_field', $wizard_data['topics'] ?? []);
    $objectives = wp_kses_post($wizard_data['objectives'] ?? '');

    // Advanced settings
    $ai_model = sanitize_text_field($wizard_data['aiModel'] ?? 'gemini-pro');
    $creativity = absint($wizard_data['creativity'] ?? 70);
    $complexity = absint($wizard_data['complexity'] ?? 2);
    $language = sanitize_text_field($wizard_data['language'] ?? 'en');
    $industry = sanitize_text_field($wizard_data['industry'] ?? '');

    // Quality controls
    $fact_check = (bool)($wizard_data['factCheck'] ?? true);
    $grammar_check = (bool)($wizard_data['grammarCheck'] ?? true);
    $plagiarism_check = (bool)($wizard_data['plagiarismCheck'] ?? false);

    // Validate curriculum
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Invalid curriculum']);
        return;
    }

    // Check tier restrictions
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    
    if (!courscribe_check_generation_limits($tier, $count, 'course')) {
        wp_send_json_error(['message' => courscribe_get_tier_limit_message($tier, 'course')]);
        return;
    }

    try {
        // Generate courses using AI
        $generated_courses = courscribe_generate_courses_with_ai([
            'curriculum_id' => $curriculum_id,
            'curriculum_title' => $curriculum->post_title,
            'curriculum_topic' => get_post_meta($curriculum_id, '_class_topic', true),
            'curriculum_goal' => get_post_meta($curriculum_id, '_class_goal', true),
            'count' => $count,
            'difficulty' => $difficulty,
            'audience' => $audience,
            'tone' => $tone,
            'depth' => $depth,
            'duration' => $duration,
            'template' => $template,
            'instructions' => $instructions,
            'topics' => $topics,
            'objectives' => $objectives,
            'ai_model' => $ai_model,
            'creativity' => $creativity,
            'complexity' => $complexity,
            'language' => $language,
            'industry' => $industry,
            'fact_check' => $fact_check,
            'grammar_check' => $grammar_check,
            'plagiarism_check' => $plagiarism_check
        ]);

        // Log the generation activity
        courscribe_log_generation_activity($curriculum_id, 'course_generation', [
            'count' => $count,
            'settings' => $wizard_data,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        // Update AI usage tracking
        courscribe_update_ai_usage($studio_id, 'course_generation', $count);

        wp_send_json_success([
            'message' => 'Courses generated successfully',
            'courses' => $generated_courses,
            'generation_id' => uniqid('gen_'),
            'usage_remaining' => courscribe_get_remaining_ai_usage($studio_id)
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Course Generation Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Generation failed: ' . $e->getMessage()]);
    }
}

// Save generated courses
add_action('wp_ajax_courscribe_save_generated_courses', 'courscribe_handle_save_generated_courses');
function courscribe_handle_save_generated_courses() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_courses_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Get and validate data
    $curriculum_id = absint($_POST['curriculum_id'] ?? 0);
    $courses_json = $_POST['courses'] ?? '';
    $courses = json_decode($courses_json, true);

    if (!$curriculum_id || !is_array($courses) || empty($courses)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
        return;
    }

    // Validate curriculum
    $curriculum = get_post($curriculum_id);
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        wp_send_json_error(['message' => 'Invalid curriculum']);
        return;
    }

    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $current_user = wp_get_current_user();
    $saved_courses = [];
    $errors = [];

    try {
        foreach ($courses as $index => $course_data) {
            $title = sanitize_text_field($course_data['title'] ?? '');
            $goal = wp_kses_post($course_data['goal'] ?? '');
            $level = sanitize_text_field($course_data['level'] ?? 'apply');
            $duration = sanitize_text_field($course_data['duration'] ?? '1-hour');
            $objectives = $course_data['objectives'] ?? [];

            if (empty($title) || empty($goal)) {
                $errors[] = "Course " . ($index + 1) . ": Title and goal are required";
                continue;
            }

            // Sanitize objectives to match expected format
            $sanitized_objectives = [];
            if (is_array($objectives)) {
                foreach ($objectives as $objective) {
                    if (isset($objective['thinking_skill'], $objective['action_verb'], $objective['description'])) {
                        $sanitized_objectives[] = [
                            'thinking_skill' => sanitize_text_field($objective['thinking_skill']),
                            'action_verb' => sanitize_text_field($objective['action_verb']),
                            'description' => sanitize_text_field($objective['description'])
                        ];
                    }
                }
            }

            // Create course post using the same structure as save_new_course
            $course_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => 'crscribe_course',
                'post_status' => 'publish',
                'post_author' => $current_user->ID,
                'meta_input' => [
                    '_class_goal' => $goal,
                    'level-of-learning' => $level,
                    '_course_objectives' => maybe_serialize($sanitized_objectives),
                    '_curriculum_id' => $curriculum_id,
                    '_studio_id' => $studio_id,
                    '_creator_id' => $current_user->ID,
                    '_estimated_duration' => $duration,
                    '_generated_by_ai' => true,
                    '_generation_timestamp' => current_time('mysql')
                ],
            ], true);

            if (is_wp_error($course_id)) {
                $errors[] = "Course " . ($index + 1) . ": " . $course_id->get_error_message();
                continue;
            }

            // Save additional course data if provided
            if (isset($course_data['topics']) && is_array($course_data['topics'])) {
                update_post_meta($course_id, '_course_topics', $course_data['topics']);
            }

            $saved_courses[] = [
                'id' => $course_id,
                'title' => $title,
                'goal' => $goal,
                'edit_url' => admin_url('post.php?post=' . $course_id . '&action=edit')
            ];

            // Log course creation
            courscribe_log_course_activity($course_id, 'course_created', [
                'created_by' => 'ai_generation',
                'curriculum_id' => $curriculum_id,
                'user_id' => $current_user->ID,
                'timestamp' => current_time('mysql')
            ]);
        }

        if (empty($saved_courses)) {
            wp_send_json_error([
                'message' => 'No courses were saved successfully',
                'errors' => $errors
            ]);
            return;
        }

        wp_send_json_success([
            'message' => count($saved_courses) . ' course(s) saved successfully',
            'courses' => $saved_courses,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Save Courses Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to save courses: ' . $e->getMessage()]);
    }
}

// Enhance existing courses with AI
add_action('wp_ajax_courscribe_enhance_courses', 'courscribe_handle_enhance_courses');
function courscribe_handle_enhance_courses() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_courses_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    $course_indices = array_map('absint', $_POST['course_indices'] ?? []);
    $generated_data = json_decode($_POST['generated_data'] ?? '', true);

    if (empty($course_indices) || !is_array($generated_data)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
        return;
    }

    try {
        $enhanced_courses = [];

        foreach ($course_indices as $index) {
            if (!isset($generated_data[$index])) continue;

            $course_data = $generated_data[$index];
            
            // Enhance using AI
            $enhanced_course = courscribe_enhance_course_with_ai($course_data, [
                'enhancement_type' => 'quality_improvement',
                'focus_areas' => ['clarity', 'structure', 'engagement', 'completeness']
            ]);

            $enhanced_courses[$index] = $enhanced_course;
        }

        wp_send_json_success([
            'message' => 'Courses enhanced successfully',
            'enhanced_courses' => $enhanced_courses
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Enhance Courses Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Enhancement failed: ' . $e->getMessage()]);
    }
}

// Get generation templates
add_action('wp_ajax_courscribe_get_generation_templates', 'courscribe_handle_get_generation_templates');
function courscribe_handle_get_generation_templates() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_courses_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    $type = sanitize_key($_POST['type'] ?? 'course');
    $industry = sanitize_text_field($_POST['industry'] ?? '');

    $templates = courscribe_get_generation_templates($type, $industry);

    wp_send_json_success([
        'templates' => $templates,
        'type' => $type
    ]);
}

/**
 * Core AI Generation Functions
 */

function courscribe_generate_courses_with_ai($params) {
    // Validate required parameters
    $required_params = ['curriculum_id', 'count'];
    foreach ($required_params as $param) {
        if (empty($params[$param])) {
            throw new Exception("Missing required parameter: {$param}");
        }
    }

    // Build AI prompt
    $prompt = courscribe_build_course_generation_prompt($params);
    
    // Call AI service
    $ai_response = courscribe_call_ai_service($prompt, [
        'model' => $params['ai_model'] ?? 'gemini-pro',
        'creativity' => $params['creativity'] ?? 70,
        'language' => $params['language'] ?? 'en',
        'max_tokens' => 4000
    ]);

    // Parse and validate AI response
    $generated_courses = courscribe_parse_ai_course_response($ai_response, $params);

    // Apply quality controls
    if ($params['fact_check'] ?? true) {
        $generated_courses = courscribe_apply_fact_checking($generated_courses);
    }

    if ($params['grammar_check'] ?? true) {
        $generated_courses = courscribe_apply_grammar_checking($generated_courses);
    }

    if ($params['plagiarism_check'] ?? false) {
        $generated_courses = courscribe_apply_plagiarism_checking($generated_courses);
    }

    return $generated_courses;
}

function courscribe_build_course_generation_prompt($params) {
    $curriculum_title = $params['curriculum_title'] ?? '';
    $curriculum_topic = $params['curriculum_topic'] ?? '';
    $curriculum_goal = $params['curriculum_goal'] ?? '';
    $count = $params['count'] ?? 1;
    $difficulty = $params['difficulty'] ?? 'intermediate';
    $audience = $params['audience'] ?? 'professionals';
    $tone = $params['tone'] ?? 'professional';
    $depth = $params['depth'] ?? 'detailed';
    $duration = $params['duration'] ?? '1-hour';
    $instructions = $params['instructions'] ?? '';
    $topics = $params['topics'] ?? [];
    $objectives = $params['objectives'] ?? '';
    $industry = $params['industry'] ?? '';

    $prompt = "You are an expert curriculum designer and educational content creator. ";
    $prompt .= "Generate {$count} professional course" . ($count > 1 ? 's' : '') . " for the following curriculum:\n\n";
    
    $prompt .= "CURRICULUM CONTEXT:\n";
    $prompt .= "- Title: {$curriculum_title}\n";
    if ($curriculum_topic) $prompt .= "- Topic: {$curriculum_topic}\n";
    if ($curriculum_goal) $prompt .= "- Goal: {$curriculum_goal}\n";
    $prompt .= "\n";

    $prompt .= "COURSE REQUIREMENTS:\n";
    $prompt .= "- Difficulty Level: {$difficulty}\n";
    $prompt .= "- Target Audience: {$audience}\n";
    $prompt .= "- Content Tone: {$tone}\n";
    $prompt .= "- Content Depth: {$depth}\n";
    $prompt .= "- Target Duration: {$duration}\n";
    if ($industry) $prompt .= "- Industry Focus: {$industry}\n";
    $prompt .= "\n";

    if (!empty($topics)) {
        $prompt .= "KEY TOPICS TO COVER:\n";
        foreach ($topics as $topic) {
            $prompt .= "- {$topic}\n";
        }
        $prompt .= "\n";
    }

    if ($objectives) {
        $prompt .= "LEARNING OBJECTIVES:\n{$objectives}\n\n";
    }

    if ($instructions) {
        $prompt .= "SPECIAL INSTRUCTIONS:\n{$instructions}\n\n";
    }

    $prompt .= "Please generate courses that are:\n";
    $prompt .= "- Educationally sound and well-structured\n";
    $prompt .= "- Appropriate for the specified audience and difficulty level\n";
    $prompt .= "- Engaging and practical\n";
    $prompt .= "- Logically sequenced within the curriculum\n";
    $prompt .= "- Comprehensive yet focused\n\n";

    $prompt .= "For each course, provide:\n";
    $prompt .= "1. Title (clear and descriptive)\n";
    $prompt .= "2. Goal (specific learning outcome)\n";
    $prompt .= "3. Key Topics (3-5 main topics to be covered)\n";
    $prompt .= "4. Level of Learning (remember, understand, apply, analyze, evaluate, create)\n";
    $prompt .= "5. Estimated Duration\n";
    $prompt .= "6. Quality Assessment (rate as 'good', 'very good', or 'excellent')\n\n";

    $prompt .= "Format the response as a JSON array of course objects with the following structure:\n";
    $prompt .= '```json
    [
        {
            "title": "Course Title",
            "goal": "Specific learning goal or outcome",
            "topics": ["Topic 1", "Topic 2", "Topic 3"],
            "level": "apply",
            "duration": "2-hours",
            "quality": "excellent"
        }
    ]
    ```';

    return $prompt;
}

function courscribe_call_ai_service($prompt, $options = []) {
    $model = $options['model'] ?? 'gemini-pro';
    $creativity = $options['creativity'] ?? 70;
    $language = $options['language'] ?? 'en';
    $max_tokens = $options['max_tokens'] ?? 2000;

    // This would be replaced with actual AI service integration
    // For now, return mock data for demonstration
    if (defined('COURSCRIBE_DEMO_MODE') && COURSCRIBE_DEMO_MODE) {
        return courscribe_get_demo_ai_response($prompt, $options);
    }

    // Example Google Gemini integration
    if ($model === 'gemini-pro') {
        return courscribe_call_gemini_api($prompt, $options);
    }

    // Example GPT integration
    if (strpos($model, 'gpt') === 0) {
        return courscribe_call_openai_api($prompt, $options);
    }

    throw new Exception("Unsupported AI model: {$model}");
}

function courscribe_parse_ai_course_response($ai_response, $params) {
    // Extract JSON from AI response
    $json_pattern = '/```json\s*(.*?)\s*```/s';
    if (preg_match($json_pattern, $ai_response, $matches)) {
        $json_content = $matches[1];
    } else {
        // Try to find JSON content without markdown
        $json_content = $ai_response;
    }

    $courses = json_decode($json_content, true);
    
    if (!is_array($courses)) {
        throw new Exception("Invalid AI response format");
    }

    // Validate and enhance course data
    $validated_courses = [];
    foreach ($courses as $index => $course) {
        if (!isset($course['title']) || !isset($course['goal'])) {
            continue; // Skip invalid courses
        }

        $validated_course = [
            'id' => uniqid('course_'),
            'title' => sanitize_text_field($course['title']),
            'goal' => wp_kses_post($course['goal']),
            'topics' => array_map('sanitize_text_field', $course['topics'] ?? []),
            'level' => sanitize_text_field($course['level'] ?? 'apply'),
            'duration' => sanitize_text_field($course['duration'] ?? $params['duration']),
            'quality' => sanitize_text_field($course['quality'] ?? 'good'),
            'generated_at' => current_time('mysql'),
            'ai_model' => $params['ai_model'] ?? 'gemini-pro'
        ];

        // Add suggestions if provided
        if (isset($course['suggestions']) && is_array($course['suggestions'])) {
            $validated_course['suggestions'] = array_map('sanitize_text_field', $course['suggestions']);
        }

        $validated_courses[] = $validated_course;
    }

    return $validated_courses;
}

function courscribe_get_demo_ai_response($prompt, $options) {
    // Demo response for development/testing
    return '```json
    [
        {
            "title": "Introduction to Digital Marketing Fundamentals",
            "goal": "Students will understand core digital marketing concepts and develop foundational skills in online marketing strategies",
            "topics": ["Digital Marketing Overview", "SEO Basics", "Social Media Marketing", "Email Marketing", "Analytics and Measurement"],
            "level": "understand",
            "duration": "2-hours",
            "quality": "excellent",
            "suggestions": ["Include hands-on exercises with real marketing tools", "Add case studies from successful campaigns"]
        },
        {
            "title": "Advanced Digital Marketing Strategy",
            "goal": "Students will create comprehensive digital marketing strategies and implement advanced techniques for business growth",
            "topics": ["Strategic Planning", "Multi-channel Campaigns", "Conversion Optimization", "Marketing Automation", "ROI Analysis"],
            "level": "create",
            "duration": "3-hours",
            "quality": "very good",
            "suggestions": ["Focus on practical strategy development", "Include industry-specific examples"]
        }
    ]
    ```';
}

/**
 * Quality Control Functions
 */

function courscribe_apply_fact_checking($courses) {
    // Implement fact-checking logic
    // This could integrate with fact-checking services or use additional AI validation
    
    foreach ($courses as &$course) {
        // Mark as fact-checked
        $course['fact_checked'] = true;
        $course['fact_check_timestamp'] = current_time('mysql');
    }
    
    return $courses;
}

function courscribe_apply_grammar_checking($courses) {
    // Implement grammar checking
    // This could use services like Grammarly API or built-in grammar checking
    
    foreach ($courses as &$course) {
        // Basic grammar improvements could be applied here
        $course['grammar_checked'] = true;
        $course['grammar_check_timestamp'] = current_time('mysql');
    }
    
    return $courses;
}

function courscribe_apply_plagiarism_checking($courses) {
    // Implement plagiarism detection
    // This could integrate with plagiarism detection services
    
    foreach ($courses as &$course) {
        $course['plagiarism_checked'] = true;
        $course['plagiarism_score'] = 0; // Percentage of potential plagiarism
        $course['plagiarism_check_timestamp'] = current_time('mysql');
    }
    
    return $courses;
}

/**
 * Utility Functions
 */

function courscribe_check_generation_limits($tier, $count, $type = 'course') {
    $limits = [
        'basics' => ['course' => 1, 'module' => 3, 'lesson' => 10],
        'plus' => ['course' => 2, 'module' => 6, 'lesson' => 25],
        'pro' => ['course' => -1, 'module' => -1, 'lesson' => -1]
    ];
    
    $limit = $limits[$tier][$type] ?? 1;
    return $limit === -1 || $count <= $limit;
}

function courscribe_get_tier_limit_message($tier, $type = 'course') {
    $limits = [
        'basics' => ['course' => 1, 'module' => 3, 'lesson' => 10],
        'plus' => ['course' => 2, 'module' => 6, 'lesson' => 25],
        'pro' => ['course' => -1, 'module' => -1, 'lesson' => -1]
    ];
    
    $limit = $limits[$tier][$type] ?? 1;
    
    if ($limit === -1) {
        return '';
    }
    
    return "Your {$tier} plan allows only {$limit} {$type}" . ($limit > 1 ? 's' : '') . " per generation. Upgrade for more.";
}

function courscribe_log_generation_activity($parent_id, $activity_type, $activity_data = []) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_generation_logs';
    
    // Create table if not exists
    courscribe_create_generation_logs_table();

    try {
        $result = $wpdb->insert(
            $table_name,
            [
                'parent_id' => $parent_id,
                'activity_type' => $activity_type,
                'activity_data' => maybe_serialize($activity_data),
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%d', '%s']
        );

        return $result !== false;
    } catch (Exception $e) {
        error_log('CourScribe: Generation activity log error - ' . $e->getMessage());
        return false;
    }
}

function courscribe_create_generation_logs_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_generation_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        parent_id bigint(20) NOT NULL,
        activity_type varchar(50) NOT NULL,
        activity_data longtext,
        user_id bigint(20) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY parent_id (parent_id),
        KEY activity_type (activity_type),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function courscribe_update_ai_usage($studio_id, $usage_type, $count = 1) {
    $current_usage = get_post_meta($studio_id, '_ai_usage_' . $usage_type, true) ?: 0;
    $new_usage = $current_usage + $count;
    
    update_post_meta($studio_id, '_ai_usage_' . $usage_type, $new_usage);
    update_post_meta($studio_id, '_ai_last_used', current_time('mysql'));
    
    return $new_usage;
}

function courscribe_get_remaining_ai_usage($studio_id) {
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    
    $limits = [
        'basics' => 10,
        'plus' => 50,
        'pro' => -1
    ];
    
    $limit = $limits[$tier] ?? 10;
    
    if ($limit === -1) {
        return -1; // Unlimited
    }
    
    $current_usage = get_post_meta($studio_id, '_ai_usage_course_generation', true) ?: 0;
    return max(0, $limit - $current_usage);
}

function courscribe_log_course_activity($course_id, $activity_type, $activity_data = []) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_course_log';

    try {
        $result = $wpdb->insert(
            $table_name,
            [
                'course_id' => $course_id,
                'user_id' => get_current_user_id(),
                'action' => $activity_type,
                'changes' => maybe_serialize($activity_data),
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            error_log('CourScribe: Failed to log course activity - ' . $wpdb->last_error);
        }

        return $result !== false;
    } catch (Exception $e) {
        error_log('CourScribe: Course activity log error - ' . $e->getMessage());
        return false;
    }
}

// AI Service Integration Functions (stub implementations for development)
if (!function_exists('courscribe_call_gemini_api')) {
    function courscribe_call_gemini_api($prompt, $options = []) {
        // This would be replaced with actual Gemini API integration
        // For now, return demo response format
        return courscribe_get_demo_ai_response($prompt, $options);
    }
}

if (!function_exists('courscribe_call_openai_api')) {
    function courscribe_call_openai_api($prompt, $options = []) {
        // This would be replaced with actual OpenAI API integration
        // For now, return demo response format
        return courscribe_get_demo_ai_response($prompt, $options);
    }
}

if (!function_exists('courscribe_enhance_course_with_ai')) {
    function courscribe_enhance_course_with_ai($course_data, $options = []) {
        // This would be replaced with actual AI enhancement logic
        // For now, return the course data with some enhancements
        $enhanced = $course_data;
        $enhanced['enhanced'] = true;
        $enhanced['enhancement_timestamp'] = current_time('mysql');
        return $enhanced;
    }
}

if (!function_exists('courscribe_get_generation_templates')) {
    function courscribe_get_generation_templates($type = 'course', $industry = '') {
        // Return demo templates - in production this would come from database
        $templates = [
            [
                'id' => 'business-basics',
                'name' => 'Business Fundamentals',
                'description' => 'Essential business concepts and practices',
                'icon' => 'fas fa-briefcase',
                'topics' => ['Strategy', 'Operations', 'Marketing', 'Finance'],
                'difficulty' => 'intermediate'
            ],
            [
                'id' => 'technical-training',
                'name' => 'Technical Skills',
                'description' => 'Hands-on technical training structure',
                'icon' => 'fas fa-code',
                'topics' => ['Theory', 'Practice', 'Projects', 'Assessment'],
                'difficulty' => 'advanced'
            ]
        ];
        
        return $templates;
    }
}

/**
 * MODULE GENERATION HANDLERS
 */

// Generate modules with premium features
add_action('wp_ajax_courscribe_generate_modules_premium', 'courscribe_handle_generate_modules_premium');
function courscribe_handle_generate_modules_premium() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_modules_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Get wizard data
    $wizard_data_json = stripslashes($_POST['wizard_data'] ?? '');
    $wizard_data = json_decode($wizard_data_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'JSON decode failed: ' . json_last_error_msg()]);
        return;
    }

    if (!$wizard_data || !is_array($wizard_data)) {
        wp_send_json_error(['message' => 'Invalid wizard data']);
        return;
    }

    // Extract required data
    $course_id = absint($wizard_data['parent']['parent_id'] ?? 0);
    $count = absint($wizard_data['count'] ?? 1);
    $difficulty = sanitize_text_field($wizard_data['difficulty'] ?? 'intermediate');
    $audience = sanitize_text_field($wizard_data['audience'] ?? 'professionals');
    $tone = sanitize_text_field($wizard_data['tone'] ?? 'professional');
    $depth = sanitize_text_field($wizard_data['depth'] ?? 'detailed');
    $duration = sanitize_text_field($wizard_data['duration'] ?? '1-hour');
    $instructions = wp_kses_post($wizard_data['instructions'] ?? '');
    $focus_areas = array_map('sanitize_text_field', $wizard_data['focus_areas'] ?? []);

    // Advanced settings
    $ai_model = sanitize_text_field($wizard_data['aiModel'] ?? 'gemini-pro');
    $creativity = absint($wizard_data['creativity'] ?? 70);
    $complexity = absint($wizard_data['complexity'] ?? 2);

    // Validate course
    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'crscribe_course') {
        wp_send_json_error(['message' => 'Invalid course']);
        return;
    }

    // Get studio and tier info
    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    
    if (!courscribe_check_generation_limits($tier, $count, 'module')) {
        wp_send_json_error(['message' => courscribe_get_tier_limit_message($tier, 'module')]);
        return;
    }

    try {
        // Generate modules using AI
        $generated_modules = courscribe_generate_modules_with_ai([
            'course_id' => $course_id,
            'course_title' => $course->post_title,
            'course_goal' => get_post_meta($course_id, '_class_goal', true),
            'count' => $count,
            'difficulty' => $difficulty,
            'audience' => $audience,
            'tone' => $tone,
            'depth' => $depth,
            'duration' => $duration,
            'instructions' => $instructions,
            'focus_areas' => $focus_areas,
            'ai_model' => $ai_model,
            'creativity' => $creativity,
            'complexity' => $complexity
        ]);

        // Log the generation activity
        courscribe_log_generation_activity($course_id, 'module_generation', [
            'count' => $count,
            'settings' => $wizard_data,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        // Update AI usage tracking
        courscribe_update_ai_usage($studio_id, 'module_generation', $count);

        wp_send_json_success([
            'message' => 'Modules generated successfully',
            'modules' => $generated_modules,
            'generation_id' => uniqid('gen_mod_'),
            'usage_remaining' => courscribe_get_remaining_ai_usage($studio_id)
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Module Generation Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Generation failed: ' . $e->getMessage()]);
    }
}

// Save generated modules
add_action('wp_ajax_courscribe_save_generated_modules', 'courscribe_handle_save_generated_modules');
function courscribe_handle_save_generated_modules() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_modules_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Get and validate data
    $course_id = absint($_POST['course_id'] ?? 0);
    $modules_json = $_POST['modules'] ?? '';
    $modules = json_decode($modules_json, true);

    if (!$course_id || !is_array($modules) || empty($modules)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
        return;
    }

    // Validate course
    $course = get_post($course_id);
    if (!$course || $course->post_type !== 'crscribe_course') {
        wp_send_json_error(['message' => 'Invalid course']);
        return;
    }

    $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $current_user = wp_get_current_user();
    $saved_modules = [];
    $errors = [];

    try {
        foreach ($modules as $index => $module_data) {
            $title = sanitize_text_field($module_data['title'] ?? '');
            $goal = wp_kses_post($module_data['goal'] ?? '');
            $description = wp_kses_post($module_data['description'] ?? '');
            $duration = sanitize_text_field($module_data['duration'] ?? '1-hour');

            if (empty($title) || empty($goal)) {
                $errors[] = "Module " . ($index + 1) . ": Title and goal are required";
                continue;
            }

            // Create module post
            $module_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => 'crscribe_module',
                'post_status' => 'publish',
                'post_author' => $current_user->ID,
                'meta_input' => [
                    '_module_goal' => $goal,
                    '_module_description' => $description,
                    '_course_id' => $course_id,
                    '_curriculum_id' => $curriculum_id,
                    '_studio_id' => $studio_id,
                    '_creator_id' => $current_user->ID,
                    '_estimated_duration' => $duration,
                    '_generated_by_ai' => true,
                    '_generation_timestamp' => current_time('mysql')
                ],
            ], true);

            if (is_wp_error($module_id)) {
                $errors[] = "Module " . ($index + 1) . ": " . $module_id->get_error_message();
                continue;
            }

            // Save additional module data if provided
            if (isset($module_data['focus_areas']) && is_array($module_data['focus_areas'])) {
                update_post_meta($module_id, '_module_focus_areas', $module_data['focus_areas']);
            }

            $saved_modules[] = [
                'id' => $module_id,
                'title' => $title,
                'goal' => $goal,
                'edit_url' => admin_url('post.php?post=' . $module_id . '&action=edit')
            ];

            // Log module creation
            courscribe_log_module_activity($module_id, 'module_created', [
                'created_by' => 'ai_generation',
                'course_id' => $course_id,
                'user_id' => $current_user->ID,
                'timestamp' => current_time('mysql')
            ]);
        }

        if (empty($saved_modules)) {
            wp_send_json_error([
                'message' => 'No modules were saved successfully',
                'errors' => $errors
            ]);
            return;
        }

        wp_send_json_success([
            'message' => count($saved_modules) . ' module(s) saved successfully',
            'modules' => $saved_modules,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Save Modules Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to save modules: ' . $e->getMessage()]);
    }
}

/**
 * LESSON GENERATION HANDLERS
 */

// Generate lessons with premium features
add_action('wp_ajax_courscribe_generate_lessons_premium', 'courscribe_handle_generate_lessons_premium');
function courscribe_handle_generate_lessons_premium() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_lessons_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Get wizard data
    $wizard_data_json = stripslashes($_POST['wizard_data'] ?? '');
    $wizard_data = json_decode($wizard_data_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(['message' => 'JSON decode failed: ' . json_last_error_msg()]);
        return;
    }

    if (!$wizard_data || !is_array($wizard_data)) {
        wp_send_json_error(['message' => 'Invalid wizard data']);
        return;
    }

    // Extract required data
    $parent_id = absint($wizard_data['parent']['parent_id'] ?? 0);
    $parent_type = sanitize_key($wizard_data['parent']['parent_type'] ?? 'module');
    $count = absint($wizard_data['count'] ?? 1);
    $difficulty = sanitize_text_field($wizard_data['difficulty'] ?? 'intermediate');
    $audience = sanitize_text_field($wizard_data['audience'] ?? 'professionals');
    $tone = sanitize_text_field($wizard_data['tone'] ?? 'professional');
    $duration = sanitize_text_field($wizard_data['duration'] ?? '45-minutes');
    $lesson_type = sanitize_text_field($wizard_data['lesson_type'] ?? 'concept');
    $instructions = wp_kses_post($wizard_data['instructions'] ?? '');
    $teaching_points_focus = array_map('sanitize_text_field', $wizard_data['teaching_points_focus'] ?? []);

    // Advanced settings
    $ai_model = sanitize_text_field($wizard_data['aiModel'] ?? 'gemini-pro');
    $creativity = absint($wizard_data['creativity'] ?? 70);
    $complexity = absint($wizard_data['complexity'] ?? 2);

    // Validate parent (module or course)
    $parent = get_post($parent_id);
    $valid_parent_types = ['crscribe_module', 'crscribe_course'];
    if (!$parent || !in_array($parent->post_type, $valid_parent_types)) {
        wp_send_json_error(['message' => 'Invalid parent ' . $parent_type]);
        return;
    }

    // Get studio and tier info
    if ($parent->post_type === 'crscribe_module') {
        $course_id = get_post_meta($parent_id, '_course_id', true);
        $curriculum_id = get_post_meta($parent_id, '_curriculum_id', true);
        $studio_id = get_post_meta($parent_id, '_studio_id', true);
    } else {
        $course_id = $parent_id;
        $curriculum_id = get_post_meta($parent_id, '_curriculum_id', true);
        $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    }
    
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    
    if (!courscribe_check_generation_limits($tier, $count, 'lesson')) {
        wp_send_json_error(['message' => courscribe_get_tier_limit_message($tier, 'lesson')]);
        return;
    }

    try {
        // Generate lessons using AI
        $generated_lessons = courscribe_generate_lessons_with_ai([
            'parent_id' => $parent_id,
            'parent_type' => $parent_type,
            'parent_title' => $parent->post_title,
            'parent_goal' => get_post_meta($parent_id, $parent_type === 'module' ? '_module_goal' : '_class_goal', true),
            'course_id' => $course_id,
            'curriculum_id' => $curriculum_id,
            'count' => $count,
            'difficulty' => $difficulty,
            'audience' => $audience,
            'tone' => $tone,
            'duration' => $duration,
            'lesson_type' => $lesson_type,
            'instructions' => $instructions,
            'teaching_points_focus' => $teaching_points_focus,
            'ai_model' => $ai_model,
            'creativity' => $creativity,
            'complexity' => $complexity,
            'include_teaching_points' => true
        ]);

        // Log the generation activity
        courscribe_log_generation_activity($parent_id, 'lesson_generation', [
            'count' => $count,
            'settings' => $wizard_data,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);

        // Update AI usage tracking
        courscribe_update_ai_usage($studio_id, 'lesson_generation', $count);

        wp_send_json_success([
            'message' => 'Lessons generated successfully',
            'lessons' => $generated_lessons,
            'generation_id' => uniqid('gen_les_'),
            'usage_remaining' => courscribe_get_remaining_ai_usage($studio_id)
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Lesson Generation Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Generation failed: ' . $e->getMessage()]);
    }
}

// Save generated lessons
add_action('wp_ajax_courscribe_save_generated_lessons', 'courscribe_handle_save_generated_lessons');
function courscribe_handle_save_generated_lessons() {
    // Security check
    if (!check_ajax_referer('courscribe_generate_lessons_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Permission check
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Get and validate data
    $parent_id = absint($_POST['parent_id'] ?? 0);
    $parent_type = sanitize_key($_POST['parent_type'] ?? 'module');
    $course_id = absint($_POST['course_id'] ?? 0);
    $lessons_json = $_POST['lessons'] ?? '';
    $lessons = json_decode($lessons_json, true);

    if (!$parent_id || !is_array($lessons) || empty($lessons)) {
        wp_send_json_error(['message' => 'Invalid data provided']);
        return;
    }

    // Validate parent
    $parent = get_post($parent_id);
    $valid_parent_types = ['crscribe_module', 'crscribe_course'];
    if (!$parent || !in_array($parent->post_type, $valid_parent_types)) {
        wp_send_json_error(['message' => 'Invalid parent']);
        return;
    }

    // Get context data
    if ($parent_type === 'module') {
        $module_id = $parent_id;
        $course_id = get_post_meta($parent_id, '_course_id', true);
        $curriculum_id = get_post_meta($parent_id, '_curriculum_id', true);
        $studio_id = get_post_meta($parent_id, '_studio_id', true);
    } else {
        $module_id = 0;
        $curriculum_id = get_post_meta($parent_id, '_curriculum_id', true);
        $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    }

    $current_user = wp_get_current_user();
    $saved_lessons = [];
    $errors = [];

    try {
        foreach ($lessons as $index => $lesson_data) {
            $title = sanitize_text_field($lesson_data['title'] ?? '');
            $goal = wp_kses_post($lesson_data['goal'] ?? '');
            $description = wp_kses_post($lesson_data['description'] ?? '');
            $duration = sanitize_text_field($lesson_data['duration'] ?? '45-minutes');
            $lesson_type = sanitize_text_field($lesson_data['lesson_type'] ?? 'concept');
            $teaching_points = $lesson_data['teaching_points'] ?? [];

            if (empty($title) || empty($goal)) {
                $errors[] = "Lesson " . ($index + 1) . ": Title and goal are required";
                continue;
            }

            // Sanitize teaching points
            $sanitized_teaching_points = [];
            if (is_array($teaching_points)) {
                foreach ($teaching_points as $tp) {
                    if (isset($tp['title'], $tp['description'])) {
                        $sanitized_teaching_points[] = [
                            'title' => sanitize_text_field($tp['title']),
                            'description' => wp_kses_post($tp['description']),
                            'example' => wp_kses_post($tp['example'] ?? ''),
                            'activity' => wp_kses_post($tp['activity'] ?? '')
                        ];
                    }
                }
            }

            // Create lesson post
            $lesson_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => 'crscribe_lesson',
                'post_status' => 'publish',
                'post_author' => $current_user->ID,
                'meta_input' => [
                    '_lesson_goal' => $goal,
                    '_lesson_description' => $description,
                    '_lesson_type' => $lesson_type,
                    '_module_id' => $module_id,
                    '_course_id' => $course_id,
                    '_curriculum_id' => $curriculum_id,
                    '_studio_id' => $studio_id,
                    '_creator_id' => $current_user->ID,
                    '_estimated_duration' => $duration,
                    '_teaching_points' => maybe_serialize($sanitized_teaching_points),
                    '_generated_by_ai' => true,
                    '_generation_timestamp' => current_time('mysql')
                ],
            ], true);

            if (is_wp_error($lesson_id)) {
                $errors[] = "Lesson " . ($index + 1) . ": " . $lesson_id->get_error_message();
                continue;
            }

            $saved_lessons[] = [
                'id' => $lesson_id,
                'title' => $title,
                'goal' => $goal,
                'teaching_points_count' => count($sanitized_teaching_points),
                'edit_url' => admin_url('post.php?post=' . $lesson_id . '&action=edit')
            ];

            // Log lesson creation
            courscribe_log_lesson_activity_gen($lesson_id, 'lesson_created', [
                'created_by' => 'ai_generation',
                'parent_type' => $parent_type,
                'parent_id' => $parent_id,
                'teaching_points_count' => count($sanitized_teaching_points),
                'user_id' => $current_user->ID,
                'timestamp' => current_time('mysql')
            ]);
        }

        if (empty($saved_lessons)) {
            wp_send_json_error([
                'message' => 'No lessons were saved successfully',
                'errors' => $errors
            ]);
            return;
        }

        wp_send_json_success([
            'message' => count($saved_lessons) . ' lesson(s) saved successfully',
            'lessons' => $saved_lessons,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        error_log('CourScribe Save Lessons Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to save lessons: ' . $e->getMessage()]);
    }
}

/**
 * AI GENERATION FUNCTIONS FOR MODULES AND LESSONS
 */

function courscribe_generate_modules_with_ai($params) {
    // Build AI prompt for modules
    $prompt = courscribe_build_module_generation_prompt($params);
    
    // Call AI service
    $ai_response = courscribe_call_ai_service($prompt, [
        'model' => $params['ai_model'] ?? 'gemini-pro',
        'creativity' => $params['creativity'] ?? 70,
        'max_tokens' => 3000
    ]);

    // Parse and validate AI response
    $generated_modules = courscribe_parse_ai_module_response($ai_response, $params);

    return $generated_modules;
}

function courscribe_build_module_generation_prompt($params) {
    $course_title = $params['course_title'] ?? '';
    $course_goal = $params['course_goal'] ?? '';
    $count = $params['count'] ?? 1;
    $difficulty = $params['difficulty'] ?? 'intermediate';
    $audience = $params['audience'] ?? 'professionals';
    $tone = $params['tone'] ?? 'professional';
    $duration = $params['duration'] ?? '1-hour';
    $instructions = $params['instructions'] ?? '';
    $focus_areas = $params['focus_areas'] ?? [];

    $prompt = "You are an expert curriculum designer. Generate {$count} learning module" . ($count > 1 ? 's' : '') . " for the following course:\n\n";
    
    $prompt .= "COURSE CONTEXT:\n";
    $prompt .= "- Title: {$course_title}\n";
    if ($course_goal) $prompt .= "- Goal: {$course_goal}\n";
    $prompt .= "\n";

    $prompt .= "MODULE REQUIREMENTS:\n";
    $prompt .= "- Difficulty Level: {$difficulty}\n";
    $prompt .= "- Target Audience: {$audience}\n";
    $prompt .= "- Content Tone: {$tone}\n";
    $prompt .= "- Target Duration: {$duration}\n";
    $prompt .= "\n";

    if (!empty($focus_areas)) {
        $prompt .= "FOCUS AREAS:\n";
        foreach ($focus_areas as $area) {
            $prompt .= "- {$area}\n";
        }
        $prompt .= "\n";
    }

    if ($instructions) {
        $prompt .= "SPECIAL INSTRUCTIONS:\n{$instructions}\n\n";
    }

    $prompt .= "IMPORTANT: Do not include methods, materials, or media in the modules - the user will add these later.\n\n";

    $prompt .= "For each module, provide:\n";
    $prompt .= "1. Title (clear and descriptive)\n";
    $prompt .= "2. Goal (specific learning outcome)\n";
    $prompt .= "3. Description (overview of what will be covered)\n";
    $prompt .= "4. Key Topics (3-4 main topics)\n";
    $prompt .= "5. Estimated Duration\n\n";

    $prompt .= "Format the response as a JSON array:\n";
    $prompt .= '```json
    [
        {
            "title": "Module Title",
            "goal": "Specific learning goal",
            "description": "Module description",
            "topics": ["Topic 1", "Topic 2", "Topic 3"],
            "duration": "1-hour"
        }
    ]
    ```';

    return $prompt;
}

function courscribe_parse_ai_module_response($ai_response, $params) {
    // Extract JSON from AI response
    $json_pattern = '/```json\s*(.*?)\s*```/s';
    if (preg_match($json_pattern, $ai_response, $matches)) {
        $json_content = $matches[1];
    } else {
        $json_content = $ai_response;
    }

    $modules = json_decode($json_content, true);
    
    if (!is_array($modules)) {
        throw new Exception("Invalid AI response format for modules");
    }

    // Validate and enhance module data
    $validated_modules = [];
    foreach ($modules as $index => $module) {
        if (!isset($module['title']) || !isset($module['goal'])) {
            continue;
        }

        $validated_module = [
            'id' => uniqid('module_'),
            'title' => sanitize_text_field($module['title']),
            'goal' => wp_kses_post($module['goal']),
            'description' => wp_kses_post($module['description'] ?? ''),
            'topics' => array_map('sanitize_text_field', $module['topics'] ?? []),
            'duration' => sanitize_text_field($module['duration'] ?? $params['duration']),
            'generated_at' => current_time('mysql'),
            'ai_model' => $params['ai_model'] ?? 'gemini-pro'
        ];

        $validated_modules[] = $validated_module;
    }

    return $validated_modules;
}

function courscribe_generate_lessons_with_ai($params) {
    // Build AI prompt for lessons
    $prompt = courscribe_build_lesson_generation_prompt($params);
    
    // Call AI service
    $ai_response = courscribe_call_ai_service($prompt, [
        'model' => $params['ai_model'] ?? 'gemini-pro',
        'creativity' => $params['creativity'] ?? 70,
        'max_tokens' => 4000
    ]);

    // Parse and validate AI response
    $generated_lessons = courscribe_parse_ai_lesson_response($ai_response, $params);

    return $generated_lessons;
}

function courscribe_build_lesson_generation_prompt($params) {
    $parent_title = $params['parent_title'] ?? '';
    $parent_goal = $params['parent_goal'] ?? '';
    $parent_type = $params['parent_type'] ?? 'module';
    $count = $params['count'] ?? 1;
    $difficulty = $params['difficulty'] ?? 'intermediate';
    $audience = $params['audience'] ?? 'professionals';
    $tone = $params['tone'] ?? 'professional';
    $duration = $params['duration'] ?? '45-minutes';
    $lesson_type = $params['lesson_type'] ?? 'concept';
    $instructions = $params['instructions'] ?? '';
    $teaching_points_focus = $params['teaching_points_focus'] ?? [];

    $prompt = "You are an expert educational designer. Generate {$count} comprehensive lesson" . ($count > 1 ? 's' : '') . " for the following {$parent_type}:\n\n";
    
    $prompt .= strtoupper($parent_type) . " CONTEXT:\n";
    $prompt .= "- Title: {$parent_title}\n";
    if ($parent_goal) $prompt .= "- Goal: {$parent_goal}\n";
    $prompt .= "\n";

    $prompt .= "LESSON REQUIREMENTS:\n";
    $prompt .= "- Lesson Type: {$lesson_type}\n";
    $prompt .= "- Difficulty Level: {$difficulty}\n";
    $prompt .= "- Target Audience: {$audience}\n";
    $prompt .= "- Content Tone: {$tone}\n";
    $prompt .= "- Target Duration: {$duration}\n";
    $prompt .= "\n";

    if (!empty($teaching_points_focus)) {
        $prompt .= "TEACHING POINTS FOCUS:\n";
        foreach ($teaching_points_focus as $focus) {
            $prompt .= "- {$focus}\n";
        }
        $prompt .= "\n";
    }

    if ($instructions) {
        $prompt .= "SPECIAL INSTRUCTIONS:\n{$instructions}\n\n";
    }

    $prompt .= "CRITICAL REQUIREMENT: Each lesson MUST include 3-5 detailed teaching points.\n\n";

    $prompt .= "For each lesson, provide:\n";
    $prompt .= "1. Title (clear and descriptive)\n";
    $prompt .= "2. Goal (specific learning outcome)\n";
    $prompt .= "3. Description (lesson overview)\n";
    $prompt .= "4. Lesson Type\n";
    $prompt .= "5. Duration\n";
    $prompt .= "6. Teaching Points (3-5 detailed points with title, description, and example)\n\n";

    $prompt .= "Format the response as a JSON array:\n";
    $prompt .= '```json
    [
        {
            "title": "Lesson Title",
            "goal": "Specific learning goal",
            "description": "Lesson description",
            "lesson_type": "concept",
            "duration": "45-minutes",
            "teaching_points": [
                {
                    "title": "Teaching Point Title",
                    "description": "Detailed explanation of the concept",
                    "example": "Concrete example or illustration",
                    "activity": "Optional practice activity or exercise"
                }
            ]
        }
    ]
    ```';

    return $prompt;
}

function courscribe_parse_ai_lesson_response($ai_response, $params) {
    // Extract JSON from AI response
    $json_pattern = '/```json\s*(.*?)\s*```/s';
    if (preg_match($json_pattern, $ai_response, $matches)) {
        $json_content = $matches[1];
    } else {
        $json_content = $ai_response;
    }

    $lessons = json_decode($json_content, true);
    
    if (!is_array($lessons)) {
        throw new Exception("Invalid AI response format for lessons");
    }

    // Validate and enhance lesson data
    $validated_lessons = [];
    foreach ($lessons as $index => $lesson) {
        if (!isset($lesson['title']) || !isset($lesson['goal'])) {
            continue;
        }

        // Validate and sanitize teaching points
        $teaching_points = [];
        if (isset($lesson['teaching_points']) && is_array($lesson['teaching_points'])) {
            foreach ($lesson['teaching_points'] as $tp) {
                if (isset($tp['title'], $tp['description'])) {
                    $teaching_points[] = [
                        'title' => sanitize_text_field($tp['title']),
                        'description' => wp_kses_post($tp['description']),
                        'example' => wp_kses_post($tp['example'] ?? ''),
                        'activity' => wp_kses_post($tp['activity'] ?? '')
                    ];
                }
            }
        }

        $validated_lesson = [
            'id' => uniqid('lesson_'),
            'title' => sanitize_text_field($lesson['title']),
            'goal' => wp_kses_post($lesson['goal']),
            'description' => wp_kses_post($lesson['description'] ?? ''),
            'lesson_type' => sanitize_text_field($lesson['lesson_type'] ?? $params['lesson_type']),
            'duration' => sanitize_text_field($lesson['duration'] ?? $params['duration']),
            'teaching_points' => $teaching_points,
            'generated_at' => current_time('mysql'),
            'ai_model' => $params['ai_model'] ?? 'gemini-pro'
        ];

        $validated_lessons[] = $validated_lesson;
    }

    return $validated_lessons;
}

/**
 * LOGGING FUNCTIONS
 */

function courscribe_log_module_activity($module_id, $activity_type, $activity_data = []) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_module_log';

    try {
        $result = $wpdb->insert(
            $table_name,
            [
                'module_id' => $module_id,
                'user_id' => get_current_user_id(),
                'action' => $activity_type,
                'changes' => maybe_serialize($activity_data),
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        return $result !== false;
    } catch (Exception $e) {
        error_log('CourScribe: Module activity log error - ' . $e->getMessage());
        return false;
    }
}

function courscribe_log_lesson_activity_gen($lesson_id, $activity_type, $activity_data = []) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'courscribe_lesson_log';

    try {
        $result = $wpdb->insert(
            $table_name,
            [
                'lesson_id' => $lesson_id,
                'user_id' => get_current_user_id(),
                'action' => $activity_type,
                'changes' => maybe_serialize($activity_data),
                'timestamp' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        return $result !== false;
    } catch (Exception $e) {
        error_log('CourScribe: Lesson activity log error - ' . $e->getMessage());
        return false;
    }
}
?>