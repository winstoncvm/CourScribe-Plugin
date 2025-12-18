<?php
// Path: courscribe-dashboard/templates/course-fields.php
$site_url = home_url();
$current_user = wp_get_current_user();

$curriculum_id = isset($curriculum_id) ? absint($curriculum_id) : 0; // Assume passed from shortcode or context
$message = ''; // For success/error messages

// Legacy form handling for fallback - now we use AJAX
if (isset($_POST['courscribe_submit_course_create']) && isset($_POST['courscribe_course_nonce'])) {
    if (!wp_verify_nonce($_POST['courscribe_course_nonce'], 'courscribe_course')) {
        $message = '<div class="courscribe-error"><p>Security check failed. Please try again.</p></div>';
    } else {
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
        $title = sanitize_text_field($_POST['course_name'] ?? '');
        $goal = sanitize_text_field($_POST['course_goal'] ?? '');
        $level_of_learning = sanitize_text_field($_POST['level_of_learning'] ?? '');
        $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
        $curriculum_id = absint($_POST['curriculum_id'] ?? 0);
        $studio_id = get_post_meta($curriculum_id, '_studio_id', true);

        // Sanitize objectives
        $sanitized_objectives = [];
        foreach ($objectives as $objective) {
            $sanitized_objectives[] = [
                'thinking_skill' => sanitize_text_field($objective['thinking_skill'] ?? ''),
                'action_verb'    => sanitize_text_field($objective['action_verb'] ?? ''),
                'description'    => sanitize_text_field($objective['description'] ?? ''),
            ];
        }
        error_log($title);
        error_log($goal);
        error_log($level_of_learning);
        error_log($studio_id);
        error_log($curriculum_id);

        // Validate
        $errors = [];
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
            $errors[] = 'At least one objective is required.';
        }
        if (!$curriculum_id || get_post($curriculum_id)->post_type !== 'crscribe_curriculum') {
            $errors[] = 'Invalid curriculum.';
        }
//        if (!$studio_id || !current_user_can('read_crscribe_studio', $studio_id) || !current_user_can('edit_crscribe_courses')) {
//            $errors[] = 'Permission denied or invalid studio.';
//        }

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
        if ($tier === 'basics' && !$course_id && $course_count >= 10) {
            $errors[] = 'Your plan (Basics) allows only 1 course per curriculum. Upgrade to create more.';
        } elseif ($tier === 'plus' && !$course_id && $course_count >= 20) {
            $errors[] = 'Your plan (Plus) allows only 2 courses per curriculum. Upgrade to Pro for unlimited.';
        }

        if (empty($errors)) {
            error_log('Creating new course.');
            $post_data = [
                'ID' => $course_id,
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
                ],
            ];

            $post_id = wp_insert_post($post_data, true);
            if (!is_wp_error($post_id)) {
                // Post created successfully - for AJAX requests this will be handled differently
                if (wp_doing_ajax()) {
                    wp_send_json_success([
                        'message' => 'Course created successfully!',
                        'course_id' => $post_id,
                        'redirect_url' => $_SERVER['REQUEST_URI']
                    ]);
                } else {
                    // Fallback for non-AJAX requests
                    wp_safe_redirect($_SERVER['REQUEST_URI']);
                    exit;
                }
            }
//            error_log('post id:.' . $post_id);
            if (!is_wp_error($post_id)) {
                foreach ($post_data['meta_input'] as $meta_key => $meta_value) {
                    $logged_value = is_array($meta_value) ? print_r($meta_value, true) : $meta_value;
                    error_log("Meta for {$meta_key}: " . $logged_value);
                }
            } else {
                error_log('Post creation error: ' . $post_id->get_error_message());
            }
            if (!is_wp_error($post_id)) {
                global $wpdb;
                $changes = [
                    'title' => ['new' => $title],
                    'goal' => ['new' => $goal],
                    'level_of_learning' => ['new' => $level_of_learning],
                    'objectives' => ['new' => $sanitized_objectives],
                    'curriculum_id' => ['new' => $curriculum_id],
                    'studio_id' => ['new' => $studio_id],
                ];

                if ($course_id) {
                    $old_post = get_post($course_id);
                    $changes['title']['old'] = $old_post ? $old_post->post_title : '';
                    $changes['goal']['old'] = get_post_meta($course_id, '_class_goal', true);
                    $changes['level_of_learning']['old'] = get_post_meta($course_id, 'level-of-learning', true);
                    $changes['objectives']['old'] = maybe_unserialize(get_post_meta($course_id, '_course_objectives', true));
                    $changes['curriculum_id']['old'] = get_post_meta($course_id, '_curriculum_id', true);
                    $changes['studio_id']['old'] = get_post_meta($course_id, '_studio_id', true);
                }

                $result = $wpdb->insert(
                    $wpdb->prefix . 'courscribe_course_log',
                    [
                        'course_id' => $post_id,
                        'user_id' => $current_user->ID,
                        'action' => $course_id ? 'update' : 'create',
                        'changes' => wp_json_encode($changes),
                        'timestamp' => current_time('mysql'),
                    ],
                    ['%d', '%d', '%s', '%s', '%s']
                );

                if ($result === false) {
                    $message = '<div class="courscribe-error"><p>Error: Failed to log course changes.</p></div>';
                } else {
                    $message = '<div class="courscribe-success"><p>Course ' . ($course_id ? 'updated' : 'created') . ' successfully! <a href="' . esc_url(get_permalink($post_id)) . '">View</a></p></div>';
                }
            } else {
                if (wp_doing_ajax()) {
                    wp_send_json_error([
                        'message' => 'Error: ' . esc_html($post_id->get_error_message())
                    ]);
                } else {
                    $message = '<div class="courscribe-error"><p>Error: ' . esc_html($post_id->get_error_message()) . '</p></div>';
                }
            }
        } else {
            if (wp_doing_ajax()) {
                wp_send_json_error([
                    'message' => 'Please correct the following errors:',
                    'errors' => $errors
                ]);
            } else {
                $message = '<div class="courscribe-error"><p>Please correct the following errors:</p><ul>';
                foreach ($errors as $error) {
                    $message .= '<li>' . esc_html($error) . '</li>';
                }
                $message .= '</ul></div>';
            }
        }
    }
}
?>
<!-- Font Awesome CDN -->
<link
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  rel="stylesheet"
/>

<!-- Add Course Modal Start -->
<div class="modal fade create-new-course-modal" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content courscribe-premium-modal">
            <!-- Premium Header with Gradient -->
            <div class="modal-header courscribe-premium-header">
                <div class="premium-header-content">
                    <div class="premium-icon">
                    <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/courscribe/courscribe-logo-v1-orange@2x.png" alt="Logo" style="max-width: 40px; display: block;" />
                    
                    </div>
                    <div class="premium-title-section">
                        <h4 class="premium-modal-title" id="addCourseModalLabel">
                            <span class="gradient-text">Create New Course</span>
                        </h4>
                        <p class="premium-subtitle">Build engaging educational content for your curriculum</p>
                    </div>
                </div>
                <button type="button" class="premium-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if ($message) : ?>
                    <div class="mb-3"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="post" id="courscribe-course-form">
                    <input type="hidden" name="courscribe_submit_course_create" value="1">
                    <input type="hidden" name="courscribe_course_nonce" value="<?php echo wp_create_nonce('courscribe_course'); ?>">
                    <input type="hidden" name="curriculum_id" value="<?php echo esc_attr($curriculum_id); ?>">
                    <input type="hidden" name="course_id" value="0">

                    <!-- Name (Title) Field -->
                    <div class="mb-3">
                        <label for="course-name" class="form-label text-light">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Course Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               id="course-name" 
                               name="course_name" 
                               class="form-control premium-input" 
                               placeholder="Enter a descriptive course name"
                               value="<?php echo isset($_POST['course_name']) ? esc_attr($_POST['course_name']) : ''; ?>" 
                               required
                               maxlength="100" />
                        <div class="invalid-feedback"></div>
                        <small class="form-text text-muted">Choose a clear, engaging name for your course</small>
                    </div>

                    <!-- Goal Field -->
                    <div class="mb-3">
                        <label for="course-goal" class="form-label text-light">
                            <i class="fas fa-target me-2"></i>
                            Course Goal <span class="text-danger">*</span>
                        </label>
                        <textarea id="course-goal" 
                                  name="course_goal" 
                                  class="form-control premium-input" 
                                  rows="3"
                                  placeholder="Describe what students will achieve by completing this course"
                                  required
                                  maxlength="500"><?php echo isset($_POST['course_goal']) ? esc_textarea($_POST['course_goal']) : ''; ?></textarea>
                        <div class="invalid-feedback"></div>
                        <small class="form-text text-muted">Define the main learning outcome for this course</small>
                    </div>

                    <!-- Level of Learning Field -->
                    <div class="mb-3">
                        <label for="level-of-learning" class="form-label text-light">
                            <i class="fas fa-layer-group me-2"></i>
                            Level of Learning <span class="text-danger">*</span>
                        </label>
                        <select id="level-of-learning" name="level_of_learning" class="form-control premium-input" required>
                            <option value="" disabled <?php echo !isset($_POST['level_of_learning']) ? 'selected' : ''; ?>>Select Level</option>
                            <option value="Foundational" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Foundational' ? 'selected' : ''; ?>>Foundational</option>
                            <option value="Introductory" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Introductory' ? 'selected' : ''; ?>>Introductory</option>
                            <option value="Beginner" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Proficient" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Proficient' ? 'selected' : ''; ?>>Proficient</option>
                            <option value="Advanced" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                            <option value="Expert" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Expert' ? 'selected' : ''; ?>>Expert</option>
                            <option value="Mastery" <?php echo isset($_POST['level_of_learning']) && $_POST['level_of_learning'] === 'Mastery' ? 'selected' : ''; ?>>Mastery</option>
                        </select>
                        <div class="invalid-feedback"></div>
                        <small class="form-text text-muted">Select the appropriate difficulty level for your target audience</small>
                    </div>

                    <!-- Add Objectives Section -->
                    <div class="mb-4">
                        <div class="objectives-header d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-light mb-0">
                                <i class="fas fa-bullseye me-2"></i>
                                Learning Objectives
                            </h5>
                            <span class="badge bg-primary objective-count">1 Objective</span>
                        </div>
                        <div id="new-course-objectives-container" class="course-list-objectives-container">
                            <!-- Dynamic objectives will be added here -->
                        </div>
                        <div class="objectives-actions mt-3">
                            <button type="button" id="addNewObjectiveBtn" class="btn premium-btn-outline">
                                <i class="fas fa-plus me-2"></i>
                                Add Another Objective
                            </button>
                            <small class="text-muted ms-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Add multiple objectives to enhance your course goals
                            </small>
                        </div>
                    </div>

                    <!-- Premium Modal Footer -->
                    <div class="modal-footer courscribe-premium-footer">
                        <div class="footer-actions">
                            <button type="button" class="btn premium-btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                <span>Cancel</span>
                            </button>
                            <button type="submit" id="finalizeCourseBtn" class="btn premium-btn-primary" disabled>
                                <i class="fas fa-save me-2"></i>
                                <span class="btn-text">Create Course</span>
                                <div class="btn-loader d-none">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span>Creating...</span>
                                </div>
                            </button>
                        </div>
                        <div class="footer-info">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your course will be saved securely
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Add Course Modal End -->

<!-- Success Popup -->
<div class="courscribe-success-popup" id="courseSuccessPopup" style="display: none;">
    <div class="success-popup-content">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Course Created Successfully!</h3>
        <p>Your course has been added to the curriculum and is ready for content development.</p>
        <button type="button" class="btn premium-btn-primary" id="closeSuccessPopup">
            Continue
        </button>
    </div>
</div>

<style>
    /* Premium Modal Styles */
    .courscribe-premium-modal {
        background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%)
        border: 1px solid rgba(228, 178, 111, 0.2);
        border-radius: 16px;
        /* box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); */
        /* overflow: hidden; */
        width: 100%;
    }

    .courscribe-premium-header {
        background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%);
        border-bottom: none;
        padding: 24px;
        position: relative;
        overflow: hidden;
    }

    .courscribe-premium-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .premium-header-content {
        display: flex;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .premium-icon {
        background: rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 12px;
        margin-right: 16px;
    }

    .premium-icon i {
        font-size: 24px;
        color: #fff;
    }

    .premium-title-section h4 {
        margin: 0;
        color: #fff;
        font-weight: 600;
        font-size: 24px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .premium-subtitle {
        margin: 4px 0 0 0;
        color: rgba(255,255,255,0.9);
        font-size: 14px;
    }

    .premium-close-btn {
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 8px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .premium-close-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.05);
    }

    .modal-body {
        background: #1a1a1b;
        padding: 32px;
        color: #fff;
    }

    .premium-input {
        background: rgba(255,255,255,0.05);
        border: 2px solid rgba(228, 178, 111, 0.2);
        border-radius: 8px;
        color: #fff;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }

    .premium-input:focus {
        background: rgba(255,255,255,0.08);
        border-color: #E4B26F;
        box-shadow: 0 0 0 0.2rem rgba(228, 178, 111, 0.25);
        color: #fff;
    }

    .premium-input::placeholder {
        color: rgba(255,255,255,0.5);
    }

    .courscribe-premium-footer {
        background: rgba(255,255,255,0.03);
        border-top: 1px solid rgba(228, 178, 111, 0.1);
        padding: 24px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .footer-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .premium-btn-secondary {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        padding: 10px 24px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    .premium-btn-secondary:hover {
        background: rgba(255,255,255,0.2);
        color: #fff;
    }

    .premium-btn-primary {
        background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
        border: none;
        color: #fff;
        padding: 12px 32px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .premium-btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(248, 146, 62, 0.3);
    }

    .premium-btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .premium-btn-outline {
        background: transparent;
        border: 2px solid #E4B26F;
        color: #E4B26F;
        padding: 10px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }

    .premium-btn-outline:hover {
        background: #E4B26F;
        color: #1a1a1b;
    }

    .objectives-header {
        background: rgba(228, 178, 111, 0.1);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
    }

    .objective-item {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(228, 178, 111, 0.2);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
        position: relative;
    }

    .objective-item:hover {
        border-color: rgba(228, 178, 111, 0.4);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .objective-remove-btn {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: #dc3545;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        transition: all 0.3s ease;
    }

    .objective-remove-btn:hover {
        background: #dc3545;
        color: #fff;
    }

    .objective-count {
        background: linear-gradient(135deg, #E4B26F 0%, #F8923E 100%);
        border: none;
        font-size: 12px;
        padding: 6px 12px;
    }

    /* Success Popup Styles */
    .courscribe-success-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .courscribe-success-popup.show {
        opacity: 1;
    }

    .success-popup-content {
        background: linear-gradient(135deg, #1a1a1b 0%, #2a2a2b 100%);
        border: 2px solid #E4B26F;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        color: #fff;
        max-width: 400px;
        transform: scale(0.8);
        transition: transform 0.3s ease;
    }

    .courscribe-success-popup.show .success-popup-content {
        transform: scale(1);
    }

    .success-icon {
        background: linear-gradient(135deg, #28a745, #20c997);
        border-radius: 50%;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        animation: successPulse 2s infinite;
    }

    .success-icon i {
        font-size: 36px;
        color: #fff;
    }

    @keyframes successPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .footer-info {
        display: flex;
        align-items: center;
        margin-top: 8px;
    }

    @media (max-width: 576px) {
        .courscribe-premium-footer {
            flex-direction: column;
            gap: 16px;
        }
        
        .footer-actions {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
jQuery(document).ready(function($) {
    let objectiveCount = $('.course-new-objective-item').length; // Initialize count based on existing elements
    
    // Action verbs for each thinking skill
    const actionVerbs = {
        'Know': ['Define', 'List', 'Identify', 'Name', 'State', 'Describe', 'Recognize', 'Select', 'Match', 'Recall'],
        'Comprehend': ['Explain', 'Summarize', 'Interpret', 'Classify', 'Compare', 'Contrast', 'Demonstrate', 'Illustrate', 'Paraphrase', 'Translate'],
        'Apply': ['Use', 'Execute', 'Implement', 'Solve', 'Demonstrate', 'Apply', 'Construct', 'Change', 'Prepare', 'Produce'],
        'Analyze': ['Analyze', 'Break down', 'Compare', 'Contrast', 'Differentiate', 'Examine', 'Investigate', 'Categorize', 'Organize', 'Deconstruct'],
        'Evaluate': ['Judge', 'Assess', 'Evaluate', 'Critique', 'Justify', 'Defend', 'Support', 'Validate', 'Rate', 'Prioritize'],
        'Create': ['Create', 'Design', 'Develop', 'Compose', 'Construct', 'Generate', 'Plan', 'Produce', 'Invent', 'Formulate']
    };
    
    // Initialize with first objective
    addObjective();
    
    // Add new objective button
    $('#addNewObjectiveBtn').on('click', function() {
        addObjective();
        updateObjectiveCount();
    });
    
    // Function to add objective
    function addObjective() {
        const objectiveHtml = `
            <div class="objective-item course-new-objective-item" data-index="${objectiveCount}">
                <button type="button" class="objective-remove-btn" onclick="removeObjective(this)" ${objectiveCount === 0 ? 'style="display: none;"' : ''}>
                    <i class="fas fa-times me-1"></i>Remove
                </button>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Thinking Skill <span class="text-danger">*</span></label>
                        <select class="form-control premium-input thinking-skill" name="objectives[${objectiveCount}][thinking_skill]" required>
                            <option value="" disabled selected>Select Thinking Skill</option>
                            <option value="Know">Know</option>
                            <option value="Comprehend">Comprehend</option>
                            <option value="Apply">Apply</option>
                            <option value="Analyze">Analyze</option>
                            <option value="Evaluate">Evaluate</option>
                            <option value="Create">Create</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Action Verb <span class="text-danger">*</span></label>
                        <select class="form-control premium-input action-verb" name="objectives[${objectiveCount}][action_verb]" required disabled>
                            <option value="" disabled selected>Select Action Verb</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-light">Objective Description <span class="text-danger">*</span></label>
                        <textarea class="form-control premium-input" 
                                  name="objectives[${objectiveCount}][description]" 
                                  placeholder="Enter a clear description of what students will be able to do"
                                  rows="3" 
                                  maxlength="300" 
                                  required></textarea>
                        <small class="form-text text-muted">Be specific about the expected learning outcome</small>
                    </div>
                </div>
            </div>
        `;
        
        $('#new-course-objectives-container').append(objectiveHtml);
        objectiveCount++;
    }
    
    // Handle thinking skill change to populate action verbs
    $(document).on('change', '.thinking-skill', function() {
        const thinkingSkill = $(this).val();
        const actionVerbSelect = $(this).closest('.course-new-objective-item').find('.action-verb');
        
        actionVerbSelect.empty().prop('disabled', false);
        actionVerbSelect.append('<option value="" disabled selected>Select Action Verb</option>');
        
        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(verb => {
                actionVerbSelect.append(`<option value="${verb}">${verb}</option>`);
            });
        }
    });
    
    // Remove objective function
    window.removeObjective = function(button) {
        const objectiveItem = $(button).closest('.course-new-objective-item');
        objectiveItem.fadeOut(300, function() {
            $(this).remove();
            updateObjectiveCount();
            reindexObjectives();
        });
    };
    
    // Update objective count display
    function updateObjectiveCount() {
        const count = $('.course-new-objective-item').length;
        $('.objective-count').text(count + (count === 1 ? ' Objective' : ' Objectives'));
        
        // Show/hide remove buttons
        if (count <= 1) {
            $('.objective-remove-btn').hide();
        } else {
            $('.objective-remove-btn').show();
        }
    }
    
    // Reindex objectives after removal
    function reindexObjectives() {
        $('.course-new-objective-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    // Form validation and submission
    $('#courscribe-course-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return false;
        }
        
        const $submitBtn = $('#finalizeCourseBtn');
        const $btnText = $submitBtn.find('.btn-text');
        const $btnLoader = $submitBtn.find('.btn-loader');
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $btnLoader.removeClass('d-none');
        
        // Submit via AJAX
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'create_course_ajax',
                nonce: $('input[name="courscribe_course_nonce"]').val(),
                course_data: $(this).serialize()
            },
            success: function(response) {
                if (response.success) {
                    // Show success popup
                    showSuccessPopup();
                } else {
                    showError(response.data.message || 'An error occurred');
                }
            },
            error: function() {
                showError('Network error. Please try again.');
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $btnLoader.addClass('d-none');
            }
        });
    });
    
    function validateForm() {
        let isValid = true;
        const requiredFields = ['course_name', 'course_goal', 'level_of_learning'];

        console.log('🔍 Starting form validation...');

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('').hide();

        // Validate basic fields
        requiredFields.forEach(field => {
            const $field = $(`[name="${field}"]`);
            const value = $field.val();
            console.log(`Checking ${field}:`, value);

            if (!value || !String(value).trim()) {
                console.warn(`❌ ${field} is invalid`);
                $field.addClass('is-invalid');
                $field.siblings('.invalid-feedback').text('This field is required.').show();
                isValid = false;
            }
        });

        // Validate objectives
        const objectives = $('.course-new-objective-item');
        console.log(`🧠 Found ${objectives.length} objective(s).`);

        if (objectives.length === 0) {
            console.error('❌ No objectives found.');
            showError('At least one objective is required.');
            return false;
        }

        objectives.each(function(index) {
            const $item = $(this);
            const $thinkingSkill = $item.find('.thinking-skill');
            const $actionVerb = $item.find('.action-verb');
            const $description = $item.find('textarea');

            const thinkingSkillVal = $thinkingSkill.val();
            const actionVerbVal = $actionVerb.val();
            const descriptionVal = $description.val();

            console.group(`🔍 Validating Objective ${index + 1}`);
            console.log('Thinking Skill:', thinkingSkillVal);
            console.log('Action Verb:', actionVerbVal);
            console.log('Description:', descriptionVal);

            if (!thinkingSkillVal) {
                console.warn(`❌ Objective ${index + 1} missing thinking skill`);
                $thinkingSkill.addClass('is-invalid');
                $thinkingSkill.siblings('.invalid-feedback').text('Please select a thinking skill.').show();
                isValid = false;
            }
            if (!actionVerbVal) {
                console.warn(`❌ Objective ${index + 1} missing action verb`);
                $actionVerb.addClass('is-invalid');
                $actionVerb.siblings('.invalid-feedback').text('Please select an action verb.').show();
                isValid = false;
            }
            if (!descriptionVal || !String(descriptionVal).trim()) {
                console.warn(`❌ Objective ${index + 1} missing description`);
                $description.addClass('is-invalid');
                $description.siblings('.invalid-feedback').text('Please enter a description.').show();
                isValid = false;
            }

            console.groupEnd();
        });

        if (!isValid) {
            console.error('🚫 Validation failed.');
            showError('Please fill in all required fields.');
        } else {
            console.log('✅ Validation passed.');
        }

        return isValid;
    }
    
    // Show success popup
    function showSuccessPopup() {
        $('#courseSuccessPopup').fadeIn(300).addClass('show');
        $('#addCourseModal').modal('hide');
    }
    
    // Close success popup and reload
    $('#closeSuccessPopup').on('click', function() {
        $('#courseSuccessPopup').fadeOut(300, function() {
            window.location.reload();
        });
    });
    
    // Show error message
    function showError(message) {
        // You can customize this to show errors in a better way
        alert(message);
    }
    
    // Enable/disable submit button based on form validity
    $('#courscribe-course-form input, #courscribe-course-form select, #courscribe-course-form textarea').on('input change', function() {
        const hasContent = $('#course-name').val().trim() && 
                          $('#course-goal').val().trim() && 
                          $('#level-of-learning').val() &&
                          objectiveCount > 0;
        
        $('#finalizeCourseBtn').prop('disabled', !hasContent);
    });
    
    // Initialize objective count
    updateObjectiveCount();
});
</script>