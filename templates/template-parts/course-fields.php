<?php
// courscribe-dashboard/templates/course-fields.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Production-Ready Course Fields template with world-class functionality
 *
 * Features:
 * - Real-time validation and auto-save
 * - Dynamic action verb loading based on thinking skills
 * - Beautiful course log viewer with search, filter, pagination
 * - Archive/delete course functionality with confirmations
 * - Premium UI with smooth animations
 * - Comprehensive error handling and logging
 *
 * @param array $args {
 *     @type int    $course_id         Course post ID
 *     @type string $course_title      Course title
 *     @type int    $curriculum_id     Curriculum post ID
 *     @type object $tooltips          CourScribe_Tooltips instance
 *     @type string $site_url          Site URL for assets
 * }
 */
function courscribe_render_course_fields($args = []) {
    // Default values
    $defaults = [
        'course_id'     => 0,
        'course_title'  => '',
        'curriculum_id' => 0,
        'tooltips'      => null,
        'site_url'      => home_url(),
    ];

    $args = wp_parse_args($args, $defaults);
    $course_id = absint($args['course_id']);
    $course_title = esc_html($args['course_title']);
    $curriculum_id = absint($args['curriculum_id']);
    $tooltips = $args['tooltips'];
    $site_url = esc_url_raw($args['site_url']);

    if (!$course_id || !$curriculum_id || !$tooltips instanceof CourScribe_Tooltips) {
        return; // Exit if required args are missing
    }

    // Fetch course and curriculum meta
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    $level_of_learning = esc_html(get_post_meta($course_id, 'level-of-learning', true));
    $objectives = maybe_unserialize(get_post_meta($course_id, '_course_objectives', true));
    $curriculum_topic = esc_html(get_post_meta($curriculum_id, '_class_topic', true));
    $curriculum_goal = esc_html(get_post_meta($curriculum_id, '_class_goal', true));
    $curriculum_notes = esc_html(get_post_meta($curriculum_id, '_class_notes', true));

    // Get course post object
    $course = get_post($course_id);

    // Determine user roles
    $current_user = wp_get_current_user();
    $is_client = in_array('client', (array) $current_user->roles);
    $is_studio_admin = in_array('studio_admin', (array) $current_user->roles);
    $is_collaborator = in_array('collaborator', (array) $current_user->roles);
    $can_view_feedback = $is_studio_admin || $is_collaborator;

    // Prepare course field outputs
    $course_name_output = '<span>' . esc_html($course_title) . '</span>';
    $course_goal_output = '<span class="selectable-field">' . esc_html($course_goal) . '</span>';

    // Apply filters for annotatable fields
    $atts = [];
    $course_name_output = apply_filters('courscribe_course_fields_output', $course_name_output, $atts, $course, 'post_title');
    $course_goal_output = apply_filters('courscribe_course_fields_output', $course_goal_output, $atts, $course, '_class_goal');

    // Initialize notification system
    $message = '';
    $auto_save_enabled = !$is_client && ($is_studio_admin || $is_collaborator);
    
    // Get course status for archive functionality
    $course_status = get_post_meta($course_id, '_course_status', true) ?: 'active';
    $is_archived = ($course_status === 'archived');
    ?>

    <!-- Course Status Banner -->
    <?php if ($is_archived && !$is_client): ?>
    <div class="alert alert-warning mb-3 archive-banner">
        <i class="fas fa-archive me-2"></i>
        <strong>Archived Course:</strong> This course is archived. <a href="#" class="unarchive-course cs-unarchive-course-btn-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">Restore course</a> to make changes.
    </div>
    <?php endif; ?>
    
    <div class="courscribe-courses-premium" data-course-id="<?php echo esc_attr($course_id); ?>">
        <!-- Auto-save Indicator -->
        <?php if ($auto_save_enabled): ?>
        <div class="auto-save-indicator">
            <i class="fas fa-save"></i>
            <span class="save-status">All changes saved</span>
            <div class="save-spinner d-none">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="post" 
              id="courscribe-course-edit-form-<?php echo esc_attr($course_id); ?>" 
              class="courscribe-course-edit-form premium-course-form" 
              data-auto-save="<?php echo $auto_save_enabled ? 'true' : 'false'; ?>">
            <input type="hidden" name="courscribe_submit_course" value="1">
            <input type="hidden" name="courscribe_course_nonce" value="<?php echo wp_create_nonce('courscribe_course'); ?>">
            <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">
            <input type="hidden" name="curriculum_id" value="<?php echo esc_attr($curriculum_id); ?>">

            <!-- Notification Container -->
            <div class="courscribe-notifications-container mb-3">
                <div class="success-notification d-none">
                    <i class="fas fa-check-circle"></i>
                    <span class="message"></span>
                </div>
                <div class="error-notification d-none">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="message"></span>
                </div>
                <?php if ($message) : ?>
                    <div class="legacy-message mb-3"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>

            <!-- Course Name Section -->
            <div class="premium-field-group mb-4">
                <div class="field-header">
                    <label for="course-name-<?php echo esc_attr($course_id); ?>" class="premium-label">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Course Name
                        <span class="required-indicator">*</span>
                    </label>
                    <?php if (!$is_client && $auto_save_enabled): ?>
                    <div class="field-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary undo-field" data-field="course_name" title="Undo changes">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="premium-input-group">
                    <?php if ($is_client) : ?>
                        <div class="client-readonly-field">
                            <input 
                                class="form-control premium-input client-input"
                                value="<?php echo esc_attr($course_title); ?>" 
                                readonly>
                            <button type="button" 
                                    class="btn premium-feedback-btn"
                                    data-course-id="<?php echo esc_attr($course_id); ?>" 
                                    data-field-name="course_name"
                                    data-field-value="<?php echo esc_attr($course_title); ?>"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#courscribeFieldFeedbackOffcanvas">
                                <i class="fas fa-comment me-2"></i>
                                Give Feedback
                            </button>
                        </div>
                    <?php else : ?>
                        <input 
                            type="text" 
                            id="course-name-<?php echo esc_attr($course_id); ?>" 
                            name="courses[<?php echo esc_attr($course_id); ?>][course_name]" 
                            class="form-control premium-input auto-save-field" 
                            value="<?php echo esc_attr($course_title); ?>"
                            data-field="course_name"
                            data-original="<?php echo esc_attr($course_title); ?>"
                            placeholder="Enter a descriptive course name"
                            maxlength="100"
                            <?php echo $is_archived ? 'disabled' : ''; ?>
                            required />
                        <div class="input-feedback">
                            <div class="character-count">
                                <span class="current"><?php echo strlen($course_title); ?></span>/<span class="max">100</span>
                            </div>
                            <div class="validation-message"></div>
                        </div>
                        <?php if ($can_view_feedback) : ?>
                        <button type="button" 
                                class="btn premium-feedback-indicator"
                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                data-field-name="course_name"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#courscribeFieldFeedbackOffcanvas">
                            <i class="fas fa-comments"></i>
                            <span class="feedback-count">0</span>
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Goal Section -->
            <div class="premium-field-group mb-4">
                <div class="field-header">
                    <label for="course-goal-<?php echo esc_attr($course_id); ?>" class="premium-label">
                        <i class="fas fa-target me-2"></i>
                        Course Goal
                        <span class="required-indicator">*</span>
                    </label>
                    <?php if (!$is_client && $auto_save_enabled): ?>
                    <div class="field-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary undo-field" data-field="course_goal" title="Undo changes">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary ai-suggest-btn" data-field="course_goal" title="AI Suggestions">
                            <i class="fas fa-magic"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="premium-input-group">
                    <?php if ($is_client) : ?>
                        <div class="client-readonly-field">
                            <textarea 
                                class="form-control premium-input client-textarea"
                                rows="3"
                                readonly><?php echo esc_textarea($course_goal); ?></textarea>
                            <button type="button" 
                                    class="btn premium-feedback-btn"
                                    data-course-id="<?php echo esc_attr($course_id); ?>" 
                                    data-field-name="course_goal"
                                    data-field-value="<?php echo esc_attr($course_goal); ?>"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#courscribeFieldFeedbackOffcanvas">
                                <i class="fas fa-comment me-2"></i>
                                Give Feedback
                            </button>
                        </div>
                    <?php else : ?>
                        <textarea 
                            id="course-goal-<?php echo esc_attr($course_id); ?>" 
                            name="courses[<?php echo esc_attr($course_id); ?>][course_goal]" 
                            class="form-control premium-input auto-save-field" 
                            data-field="course_goal"
                            data-original="<?php echo esc_attr($course_goal); ?>"
                            placeholder="Describe what students will achieve by completing this course..."
                            rows="4"
                            maxlength="500"
                            <?php echo $is_archived ? 'disabled' : ''; ?>
                            required><?php echo esc_textarea($course_goal); ?></textarea>
                        <div class="input-feedback">
                            <div class="character-count">
                                <span class="current"><?php echo strlen($course_goal); ?></span>/<span class="max">500</span>
                            </div>
                            <div class="validation-message"></div>
                        </div>
                        <?php if ($can_view_feedback) : ?>
                        <button type="button" 
                                class="btn premium-feedback-indicator"
                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                data-field-name="course_goal"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#courscribeFieldFeedbackOffcanvas">
                            <i class="fas fa-comments"></i>
                            <span class="feedback-count">0</span>
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Level of Learning Section -->
            <div class="premium-field-group mb-4">
                <div class="field-header">
                    <label for="level-of-learning-<?php echo esc_attr($course_id); ?>" class="premium-label">
                        <i class="fas fa-layer-group me-2"></i>
                        Level of Learning
                        <span class="required-indicator">*</span>
                    </label>
                    <?php if (!$is_client && $auto_save_enabled): ?>
                    <div class="field-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary undo-field" data-field="level_of_learning" title="Undo changes">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="premium-input-group">
                    <small class="form-text text-muted mb-2">Select the appropriate difficulty level for your target audience</small>
                    <?php if ($is_client) : ?>
                        <div class="client-readonly-field">
                            <input 
                                class="form-control premium-input client-input"
                                value="<?php echo esc_attr($level_of_learning ?: 'Not specified'); ?>"
                                readonly>
                            <button type="button" 
                                    class="btn premium-feedback-btn"
                                    data-course-id="<?php echo esc_attr($course_id); ?>" 
                                    data-field-name="level_of_learning"
                                    data-field-value="<?php echo esc_attr($level_of_learning); ?>"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#courscribeFieldFeedbackOffcanvas">
                                <i class="fas fa-comment me-2"></i>
                                Give Feedback
                            </button>
                        </div>
                    <?php else : ?>
                        <select id="level-of-learning-<?php echo esc_attr($course_id); ?>" 
                                name="courses[<?php echo esc_attr($course_id); ?>][level_of_learning]" 
                                class="form-control premium-input auto-save-field"
                                data-field="level_of_learning"
                                data-original="<?php echo esc_attr($level_of_learning); ?>"
                                <?php echo $is_archived ? 'disabled' : ''; ?>
                                required>
                            <option value="" disabled <?php echo empty($level_of_learning) ? 'selected' : ''; ?>>Select Level</option>
                            <?php
                            // Ordered by complexity
                            $levels = ['Foundational', 'Introductory', 'Beginner', 'Intermediate', 'Proficient', 'Advanced', 'Expert', 'Mastery'];
                            foreach ($levels as $level) {
                                echo '<option value="' . esc_attr($level) . '"' . selected($level_of_learning, $level, false) . '>' . esc_html($level) . '</option>';
                            }
                            ?>
                        </select>
                        <div class="validation-message"></div>
                        <?php if ($can_view_feedback) : ?>
                        <button type="button" 
                                class="btn premium-feedback-indicator"
                                data-course-id="<?php echo esc_attr($course_id); ?>" 
                                data-field-name="level_of_learning"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#courscribeFieldFeedbackOffcanvas">
                            <i class="fas fa-comments"></i>
                            <span class="feedback-count">0</span>
                        </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Learning Objectives Section -->
            <div class="premium-field-group mb-4">
                <div class="field-header">
                    <h5 class="premium-label mb-0">
                        <i class="fas fa-bullseye me-2"></i>
                        Learning Objectives
                        <span class="required-indicator">*</span>
                    </h5>
                    <div class="objectives-summary">
                        <span class="badge bg-gradient objective-count">
                            <?php echo is_array($objectives) ? count($objectives) : 0; ?> Objectives
                        </span>
                        <?php if (!$is_client && !$is_archived): ?>
                        <button type="button" 
                                class="btn btn-sm premium-btn-outline add-new-objective-btn" 
                                data-course-id="<?php echo esc_attr($course_id); ?>">
                            <i class="fas fa-plus me-2"></i>
                            Add Objective
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="objectives-container" id="objectives-container-<?php echo esc_attr($course_id); ?>">
                    <?php if (!empty($objectives) && is_array($objectives)): ?>
                        <?php foreach ($objectives as $index => $objective): ?>
                            <?php
                            $thinking_skill = isset($objective['thinking_skill']) ? esc_html($objective['thinking_skill']) : '';
                            $action_verb = isset($objective['action_verb']) ? esc_html($objective['action_verb']) : '';
                            $description = isset($objective['description']) ? esc_html($objective['description']) : '';
                            ?>
                            <div class="premium-objective-item course-unique-objective-listing-item" data-index="<?php echo esc_attr($index); ?>">
                                <?php if (!$is_client && !$is_archived): ?>
                                <button type="button" class="objective-remove-btn" title="Remove objective">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                                
                                <div class="objective-header">
                                    <span class="objective-number">Objective <?php echo $index + 1; ?></span>
                                    <?php if ($can_view_feedback): ?>
                                    <button type="button" 
                                            class="btn premium-feedback-indicator"
                                            data-course-id="<?php echo esc_attr($course_id); ?>" 
                                            data-field-name="objective_<?php echo $index; ?>"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#courscribeFieldFeedbackOffcanvas">
                                        <i class="fas fa-comments"></i>
                                        <span class="feedback-count">0</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-light">Thinking Skill</label>
                                        <?php if ($is_client): ?>
                                            <input class="form-control premium-input client-input" 
                                                   value="<?php echo esc_attr($thinking_skill); ?>" 
                                                   readonly>
                                        <?php else: ?>
                                            <select class="form-control premium-input thinking-skill-select auto-save-field" 
                                                    name="courses[<?php echo esc_attr($course_id); ?>][objectives][<?php echo esc_attr($index); ?>][thinking_skill]"
                                                    data-field="objectives"
                                                    data-index="<?php echo esc_attr($index); ?>"
                                                    <?php echo $is_archived ? 'disabled' : ''; ?>
                                                    required>
                                                <option value="" disabled>Select Thinking Skill</option>
                                                <?php
                                                $skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                                foreach ($skills as $skill) {
                                                    echo '<option value="' . esc_attr($skill) . '"' . selected($thinking_skill, $skill, false) . '>' . esc_html($skill) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-light">Action Verb</label>
                                        <?php if ($is_client): ?>
                                            <input class="form-control premium-input client-input" 
                                                   value="<?php echo esc_attr($action_verb); ?>" 
                                                   readonly>
                                        <?php else: ?>
                                            <select class="form-control premium-input action-verb-select auto-save-field" 
                                                    name="courses[<?php echo esc_attr($course_id); ?>][objectives][<?php echo esc_attr($index); ?>][action_verb]"
                                                    data-field="objectives"
                                                    data-index="<?php echo esc_attr($index); ?>"
                                                    data-current="<?php echo esc_attr($action_verb); ?>"
                                                    <?php echo $is_archived ? 'disabled' : ''; ?>
                                                    required>
                                                <option value="" disabled selected>Select Action Verb</option>
                                                <!-- Populated dynamically based on thinking skill -->
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label text-light">Objective Description</label>
                                        <?php if ($is_client): ?>
                                            <textarea class="form-control premium-input client-textarea" 
                                                      rows="2" 
                                                      readonly><?php echo esc_textarea($description); ?></textarea>
                                        <?php else: ?>
                                            <textarea class="form-control premium-input auto-save-field" 
                                                      name="courses[<?php echo esc_attr($course_id); ?>][objectives][<?php echo esc_attr($index); ?>][description]"
                                                      data-field="objectives"
                                                      data-index="<?php echo esc_attr($index); ?>"
                                                      placeholder="Enter a clear description of what students will be able to do..."
                                                      rows="3"
                                                      maxlength="300"
                                                      <?php echo $is_archived ? 'disabled' : ''; ?>
                                                      required><?php echo esc_textarea($description); ?></textarea>
                                            <div class="character-count">
                                                <span class="current"><?php echo strlen($description); ?></span>/<span class="max">300</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-objectives-message">
                            <div class="text-center py-4">
                                <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No objectives added yet</h6>
                                <p class="text-muted small">Add learning objectives to define what students will achieve.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Actions Section -->
            <?php if (!$is_client): ?>
            <div class="premium-field-group mb-4">
                <div class=" cs-course-actions-container-<?php echo esc_attr($course_id); ?>">
                    <div class="cs-primary-actions">
                        <?php if ($auto_save_enabled && !$is_archived): ?>
                        <button type="button" class="btn premium-btn-primary cs-manual-save-btn-<?php echo esc_attr($course_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">
                            <i class="fas fa-save me-2"></i>
                            <span>Save Changes</span>
                            <div class="cs-save-spinner-<?php echo esc_attr($course_id); ?> d-none">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="btn premium-btn-outline cs-view-logs-btn-<?php echo esc_attr($course_id); ?>" 
                                data-course-id="<?php echo esc_attr($course_id); ?>"
                                data-bs-toggle="offcanvas" 
                                data-bs-target="#cs-courseLogsOffcanvas-<?php echo esc_attr($course_id); ?>">
                            <i class="fas fa-history me-2"></i>
                            View Logs
                        </button>
                        <?php if (!$is_archived): ?>
                        <button type="button" 
                                class="btn btn-outline-warning cs-archive-course-btn-<?php echo esc_attr($course_id); ?>" 
                                data-course-id="<?php echo esc_attr($course_id); ?>"
                                data-course-title="<?php echo esc_attr($course->post_title); ?>">
                            <i class="fas fa-archive me-2"></i>
                            Archive Course
                        </button>
                        <?php else: ?>
                        <button type="button" 
                                class="btn btn-outline-success cs-unarchive-course-btn-<?php echo esc_attr($course_id); ?>" 
                                data-course-id="<?php echo esc_attr($course_id); ?>">
                            <i class="fas fa-undo me-2"></i>
                            Restore Course
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="btn btn-outline-danger cs-delete-course-btn-<?php echo esc_attr($course_id); ?>" 
                                data-course-id="<?php echo esc_attr($course_id); ?>"
                                data-course-title="<?php echo esc_attr($course->post_title); ?>">
                            <i class="fas fa-trash me-2"></i>
                            Delete Course
                        </button>
                    </div>
                    
                    <!-- <div class="cs-secondary-actions">
                       
                    </div> -->
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <div class="courscribe-header-with-divider">
                    <span class="courscribe-title-sm">Course Feedback</span>
                    <div class="courscribe-divider"></div>
                    <?php if ($is_client) : ?>
                        <div 
                            class="courscribe-client-review-submit-button"
                            data-course-id="<?php echo esc_attr($course_id); ?>" 
                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                            data-field-name="courses-course-review[<?php echo esc_attr($course_id); ?>]"
                            data-field-id="courses-course-review-<?php echo esc_attr($course_id); ?>"
                            data-post-name="<?php echo esc_attr(get_the_title($course_id)); ?>"
                            data-current-field-value=""
                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                            data-post-type="crscribe_course"
                            data-field-type="post"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                        ><span>Give Course Feedback</span></div>
                    <?php elseif ($can_view_feedback) : ?>
                        <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment feedback-hidden"
                            data-course-id="<?php echo esc_attr($course_id); ?>" 
                            data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" 
                            data-curriculum-title="<?php echo esc_attr(get_the_title($curriculum_id)); ?>" 
                            data-field-name="courses-course-review[<?php echo esc_attr($course_id); ?>]"
                            data-field-id="courses-course-review-<?php echo esc_attr($course_id); ?>"
                            data-post-name="<?php echo esc_attr(get_the_title($course_id)); ?>"
                            data-current-field-value=""
                            data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                            data-user-name="<?php echo esc_attr($current_user->display_name); ?>" 
                            data-post-type="crscribe_course"
                            data-field-type="post"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#courscribeFieldFeedbackOffcanvas"
                            aria-controls="courscribeFieldFeedbackOffcanvasLabel"
                                    >
                            <span class="courscribe-client-review-end-adrnment-tooltip">View Course Feedback</span>
                            <span class="text">5</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            

            <!-- Premium Course Logs Offcanvas -->
            <div class="offcanvas offcanvas-end premium-logs-offcanvas" 
                 tabindex="-1" 
                 id="cs-courseLogsOffcanvas-<?php echo esc_attr($course_id); ?>" 
                 aria-labelledby="cs-courseLogsOffcanvasLabel-<?php echo esc_attr($course_id); ?>"
                 data-course-id="<?php echo esc_attr($course_id); ?>">
                
                <div class="offcanvas-header premium-logs-header">
                    <div class="header-content">
                        <h5 class="offcanvas-title" id="cs-courseLogsOffcanvasLabel-<?php echo esc_attr($course_id); ?>">
                            <i class="fas fa-history me-2"></i>
                            Course Activity Logs
                        </h5>
                        <p class="course-name"><?php echo esc_html($course->post_title); ?></p>
                    </div>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="offcanvas" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="offcanvas-body premium-logs-body">
                    <!-- Search and Filter Controls -->
                    <div class="logs-controls mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="search-input-group">
                                    <input type="text" 
                                           class="form-control premium-input logs-search" 
                                           placeholder="Search logs..." 
                                           id="cs-logs-search-<?php echo esc_attr($course_id); ?>">
                                    <button type="button" class="btn premium-btn-outline search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <select class="form-control premium-input logs-filter" id="cs-logs-filter-<?php echo esc_attr($course_id); ?>">
                                    <option value="">All Actions</option>
                                    <option value="create">Created</option>
                                    <option value="update">Updated</option>
                                    <option value="archive">Archived</option>
                                    <option value="restore">Restored</option>
                                    <option value="delete">Deleted</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logs Content -->
                    <div class="logs-content" id="cs-course-logs-content-<?php echo esc_attr($course_id); ?>">
                        <div class="loading-spinner text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading activity logs...</p>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="logs-pagination mt-3" id="cs-logs-pagination-<?php echo esc_attr($course_id); ?>" style="display: none;">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <!-- Pagination items will be added here -->
                            </ul>
                        </nav>
                        <div class="pagination-info text-center text-muted">
                            <small></small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Confirmation Modals -->
    <div class="modal fade" id="cs-deleteCourseModal-<?php echo esc_attr($course_id); ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header premium-modal-header-danger">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Delete Course?
                    </h5>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="danger-content">
                        <p><strong>Are you sure you want to delete this course?</strong></p>
                        <p class="course-name"></p>
                        <div class="alert alert-danger">
                            <i class="fas fa-warning me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. All course data, modules, lessons, and associated content will be permanently deleted.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger confirm-delete-btn">
                        <i class="fas fa-trash me-2"></i>
                        <span class="btn-text">Delete Course</span>
                        <div class="btn-spinner d-none">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="cs-archiveCourseModal-<?php echo esc_attr($course_id); ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header premium-modal-header-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-archive me-2"></i>
                        Archive Course?
                    </h5>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to archive this course?</strong></p>
                    <p class="course-name"></p>
                    <p class="text-muted">Archived courses are hidden from the main view but can be restored later. No data will be lost.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning confirm-archive-btn">
                        <i class="fas fa-archive me-2"></i>
                        <span class="btn-text">Archive Course</span>
                        <div class="btn-spinner d-none">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="cs-restoreCourseModal-<?php echo esc_attr($course_id); ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content premium-modal">
                <div class="modal-header premium-modal-header-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-undo me-2"></i>
                        Restore Course?
                    </h5>
                    <button type="button" class="premium-close-btn" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to restore this course?</strong></p>
                    <p class="course-name"></p>
                    <!-- <p class="text-muted">Archived courses are hidden from the main view but can be restored later. No data will be lost.</p> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning confirm-restore-btn">
                        <i class="fas fa-undo me-2"></i>
                        <span class="btn-text">Restore Course</span>
                        <div class="btn-spinner d-none">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    

    <script>
    jQuery(document).ready(function($) {
        const courseId = <?php echo json_encode($course_id); ?>;
        const autoSaveEnabled = <?php echo json_encode($auto_save_enabled); ?>;
        const isArchived = <?php echo json_encode($is_archived); ?>;
        
        // Action verbs mapping
        const actionVerbs = {
            'Know': ['Define', 'List', 'Identify', 'Name', 'State', 'Describe', 'Recognize', 'Select', 'Match', 'Recall'],
            'Comprehend': ['Explain', 'Summarize', 'Interpret', 'Classify', 'Compare', 'Contrast', 'Demonstrate', 'Illustrate', 'Paraphrase', 'Translate'],
            'Apply': ['Use', 'Execute', 'Implement', 'Solve', 'Demonstrate', 'Apply', 'Construct', 'Change', 'Prepare', 'Produce'],
            'Analyze': ['Analyze', 'Break down', 'Compare', 'Contrast', 'Differentiate', 'Examine', 'Investigate', 'Categorize', 'Organize', 'Deconstruct'],
            'Evaluate': ['Judge', 'Assess', 'Evaluate', 'Critique', 'Justify', 'Defend', 'Support', 'Validate', 'Rate', 'Prioritize'],
            'Create': ['Create', 'Design', 'Develop', 'Compose', 'Construct', 'Generate', 'Plan', 'Produce', 'Invent', 'Formulate']
        };
        
        let autoSaveTimeout;
        
        // Initialize action verbs for existing objectives
        $('.thinking-skill-select').each(function() {
            const thinkingSkill = $(this).val();
            if (thinkingSkill) {
                populateActionVerbs($(this));
            }
        });
        
        // Handle thinking skill changes
        $(document).on('change', '.thinking-skill-select', function() {
            populateActionVerbs($(this));
            if (autoSaveEnabled && !isArchived) {
                scheduleAutoSave();
            }
        });
        
        // Populate action verbs based on thinking skill
        function populateActionVerbs($thinkingSkillSelect) {
            const thinkingSkill = $thinkingSkillSelect.val();
            const $actionVerbSelect = $thinkingSkillSelect.closest('.course-unique-objective-listing-item').find('.action-verb-select');
            const currentActionVerb = $actionVerbSelect.data('current');
            
            $actionVerbSelect.empty().append('<option value="" disabled>Select Action Verb</option>');
            
            if (actionVerbs[thinkingSkill]) {
                actionVerbs[thinkingSkill].forEach(verb => {
                    const isSelected = verb === currentActionVerb ? ' selected' : '';
                    $actionVerbSelect.append(`<option value="${verb}"${isSelected}>${verb}</option>`);
                });
            }
        }
        
        // Auto-save functionality
        if (autoSaveEnabled && !isArchived) {
            $('.auto-save-field').on('input change', function() {
                const $field = $(this);
                const fieldName = $field.data('field');
                const index = $field.data('index'); // For objectives
                let value;

                // ✅ VALIDATION: Skip if field name is not defined
                if (!fieldName || fieldName === undefined || fieldName === '') {
                    console.warn('⚠️ CourScribe: Skipping autosave - field name is undefined', {
                        element: $field[0],
                        hasDataField: $field.attr('data-field') !== undefined
                    });
                    return;
                }

                // Determine the field value based on type
                if (fieldName === 'objectives') {
                    // Collect all objectives
                    value = collectObjectives();
                } else {
                    value = $field.val();
                }

                // ✅ VALIDATION: Skip if value is undefined
                if (value === undefined) {
                    console.warn('⚠️ CourScribe: Skipping autosave - value is undefined', {
                        fieldName: fieldName,
                        element: $field[0]
                    });
                    return;
                }

                // Skip if value hasn't changed
                const originalValue = $field.data('original');
                if (value === originalValue || (Array.isArray(value) && JSON.stringify(value) === JSON.stringify(originalValue))) {
                    return;
                }

                // Update original value to prevent repeated saves
                $field.data('original', value);

                // Schedule save for this specific field
                scheduleAutoSave(fieldName, value, index);
                updateSaveIndicator('saving');
            });
        }

        function collectObjectives() {
            const objectives = [];
            $('.course-unique-objective-listing-item').each(function(index) {
                const $item = $(this);
                const thinkingSkill = $item.find('.thinking-skill-select').val();
                const actionVerb = $item.find('.action-verb-select').val();
                const description = $item.find('textarea').val();

                if (thinkingSkill && actionVerb && description) {
                    objectives.push({
                        thinking_skill: thinkingSkill,
                        action_verb: actionVerb,
                        description: description
                    });
                }
            });
            return objectives;
        }

        function scheduleAutoSave(field, value, index = null) {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                saveField(field, value, index).then(success => {
                    updateSaveIndicator(success ? 'saved' : 'error');
                    if (success) {
                        showSuccessNotification(`Field ${field} saved successfully!`);
                    } else {
                        showErrorNotification(`Failed to save field ${field}.`);
                    }
                });
            }, 2000); // 2-second debounce
        }

        function saveField(field, value, index = null) {
            // ✅ VALIDATION: Prevent AJAX call if field or value is undefined
            if (!field || field === undefined || field === '') {
                console.error('❌ CourScribe: Cannot save - field name is undefined');
                return Promise.resolve(false);
            }

            if (value === undefined) {
                console.error('❌ CourScribe: Cannot save - value is undefined for field:', field);
                return Promise.resolve(false);
            }

            // ✅ VALIDATION: Ensure course ID is valid
            if (!courseId || courseId === 0) {
                console.error('❌ CourScribe: Cannot save - invalid course ID');
                return Promise.resolve(false);
            }

            console.log('💾 CourScribe: Saving field:', {
                action: 'update_course_ajax',
                course_id: courseId,
                field: field,
                value: field === 'objectives' ? '(objectives array)' : value,
                objectivesCount: field === 'objectives' ? value.length : 'N/A'
            });

            return new Promise((resolve) => {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_course_ajax',
                        course_id: courseId,
                        field: field,
                        value: field === 'objectives' ? JSON.stringify(value) : value,
                        nonce: $('input[name="courscribe_course_nonce"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log(`✅ Field "${field}" saved successfully`);
                            resolve(true);
                        } else {
                            console.error(`❌ Save failed for field "${field}":`, response.data?.message || 'Unknown error');
                            resolve(false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(`💥 AJAX error for field "${field}":`, error);
                        resolve(false);
                    }
                });
            });
        }
        
        function saveFormData() {
            return new Promise((resolve) => {
                const objectives = [];
                $('.course-unique-objective-listing-item').each(function(index) {
                    const $item = $(this);
                    const thinkingSkill = $item.find('.thinking-skill-select').val();
                    const actionVerb = $item.find('.action-verb-select').val();
                    const description = $item.find('textarea').val();
                    
                    if (thinkingSkill && actionVerb && description) {
                        objectives.push({
                            thinking_skill: thinkingSkill,
                            action_verb: actionVerb,
                            description: description
                        });
                    }
                });
                
                const savePromises = [];
                
                // Save course name
                const courseName = $(`#course-name-${courseId}`).val();
                if (courseName) {
                    savePromises.push(saveField('course_name', courseName));
                }
                
                // Save course goal
                const courseGoal = $(`#course-goal-${courseId}`).val();
                if (courseGoal) {
                    savePromises.push(saveField('course_goal', courseGoal));
                }
                
                // Save level of learning
                const levelOfLearning = $(`#level-of-learning-${courseId}`).val();
                if (levelOfLearning) {
                    savePromises.push(saveField('level_of_learning', levelOfLearning));
                }
                
                // Save objectives
                if (objectives.length > 0) {
                    savePromises.push(saveField('objectives', objectives));
                }
                
                Promise.all(savePromises).then(results => {
                    const allSuccessful = results.every(result => result === true);
                    updateSaveIndicator(allSuccessful ? 'saved' : 'error');
                    resolve(allSuccessful);
                }).catch(() => {
                    updateSaveIndicator('error');
                    resolve(false);
                });
            });
        }
        
        
        // Notification functions
        function showSuccessNotification(message) {
            const $container = $('.courscribe-notifications-container');
            const $notification = $container.find('.success-notification');
            $notification.find('.message').text(message);
            $notification.removeClass('d-none').fadeIn();
            
            setTimeout(() => {
                $notification.fadeOut(() => $notification.addClass('d-none'));
            }, 5000);
        }
        
        function showErrorNotification(message) {
            const $container = $('.courscribe-notifications-container');
            const $notification = $container.find('.error-notification');
            $notification.find('.message').text(message);
            $notification.removeClass('d-none').fadeIn();
            
            setTimeout(() => {
                $notification.fadeOut(() => $notification.addClass('d-none'));
            }, 7000);
        }
        
        function updateSaveIndicator(status) {
            const $indicator = $('.auto-save-indicator');
            const $status = $indicator.find('.save-status');
            const $spinner = $indicator.find('.save-spinner');
            
            $indicator.removeClass('saving error');
            $spinner.addClass('d-none');
            
            switch (status) {
                case 'saving':
                    $indicator.addClass('saving');
                    $status.text('Saving...');
                    $spinner.removeClass('d-none');
                    break;
                case 'saved':
                    $status.text('All changes saved');
                    setTimeout(() => {
                        $status.text('All changes saved');
                    }, 2000);
                    break;
                case 'error':
                    $indicator.addClass('error');
                    $status.text('Save failed');
                    break;
            }
        }
        
        // Add new objective
        $('.add-new-objective-btn').on('click', debounce(function() {
            addNewObjective();
            updateObjectiveCount();
        }, 300));
        
        function addNewObjective() {
            const objectiveIndex = $('.course-unique-objective-listing-item').length;
            const objectiveHtml = `
                <div class="premium-objective-item course-unique-objective-listing-item" data-index="${objectiveIndex}">
                    <button type="button" class="objective-remove-btn" title="Remove objective">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="objective-header">
                        <span class="objective-number">Objective ${objectiveIndex + 1}</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-light">Thinking Skill</label>
                            <select class="form-control premium-input thinking-skill-select auto-save-field" 
                                    name="courses[${courseId}][objectives][${objectiveIndex}][thinking_skill]"
                                    data-field="objectives"
                                    data-index="${objectiveIndex}"
                                    required>
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
                            <label class="form-label text-light">Action Verb</label>
                            <select class="form-control premium-input action-verb-select auto-save-field" 
                                    name="courses[${courseId}][objectives][${objectiveIndex}][action_verb]"
                                    data-field="objectives"
                                    data-index="${objectiveIndex}"
                                    required>
                                <option value="" disabled selected>Select Action Verb</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label text-light">Objective Description</label>
                            <textarea class="form-control premium-input auto-save-field" 
                                      name="courses[${courseId}][objectives][${objectiveIndex}][description]"
                                      data-field="objectives"
                                      data-index="${objectiveIndex}"
                                      placeholder="Enter a clear description of what students will be able to do..."
                                      rows="3"
                                      maxlength="300"
                                      required></textarea>
                            <div class="character-count">
                                <span class="current">0</span>/<span class="max">300</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if ($('.no-objectives-message').length) {
                $('.no-objectives-message').remove();
            }
            
            $('.objectives-container').append(objectiveHtml);
            reindexObjectives(); // Reindex after adding
            updateObjectiveCount();
        }
        
        // Remove objective
        $(document).on('click', '.objective-remove-btn', function() {
            const $objective = $(this).closest('.course-unique-objective-listing-item');
            $objective.fadeOut(300, function() {
                $(this).remove();
                updateObjectiveCount();
                reindexObjectives();
                if (autoSaveEnabled && !isArchived) {
                    scheduleAutoSave();
                }
                
                // Show no objectives message if none left
                if ($('.course-unique-objective-listing-item').length === 0) {
                    $('.objectives-container').html(`
                        <div class="no-objectives-message">
                            <div class="text-center py-4">
                                <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No objectives added yet</h6>
                                <p class="text-muted small">Add learning objectives to define what students will achieve.</p>
                            </div>
                        </div>
                    `);
                }
            });
        });
        
        function updateObjectiveCount() {
            const count = $(`.courscribe-courses-premium[data-course-id="${courseId}"] .premium-objective-item`).length;
            $(`.courscribe-courses-premium[data-course-id="${courseId}"] .objective-count`).text(count + (count === 1 ? ' Objective' : ' Objectives'));
        }
        
        function reindexObjectives_ole() {
            $('.course-unique-objective-listing-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.objective-number').text(`Objective ${index + 1}`);
                
                // Update form field names
                $(this).find('select, textarea').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        }
        function reindexObjectives() {
            $(`.courscribe-courses-premium[data-course-id="${courseId}"] .premium-objective-item`).each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.objective-number').text(`Objective ${index + 1}`);
                $(this).find('select, textarea').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/\[objectives\]\[\d+\]/, `[objectives][${index}]`);
                        $(this).attr('name', newName);
                        $(this).attr('data-index', index);
                    }
                });
            });
        }
        
        // Character count tracking
        $(document).on('input', 'textarea.premium-input, input.premium-input', function() {
            const $field = $(this);
            const maxLength = $field.attr('maxlength');
            const currentLength = $field.val().length;
            const $counter = $field.siblings('.input-feedback').find('.character-count .current');
            
            if ($counter.length) {
                $counter.text(currentLength);
                
                // Change color based on usage
                const $charCount = $counter.parent();
                if (currentLength > maxLength * 0.9) {
                    $charCount.css('color', '#dc3545');
                } else if (currentLength > maxLength * 0.7) {
                    $charCount.css('color', '#ffc107');
                } else {
                    $charCount.css('color', 'rgba(255, 255, 255, 0.6)');
                }
            }
        });
        
        // Archive course with unique selectors
        $(`.cs-archive-course-btn-${courseId}`).on('click', function() {
            const courseTitle = $(this).data('course-title');
            const $modal = $(`#cs-archiveCourseModal-${courseId}`);
            $modal.find('.course-name').text(courseTitle);
            $modal.modal('show');
        });

        $(`#cs-archiveCourseModal-${courseId} .confirm-archive-btn`).on('click', function() {
            const $btn = $(this);
            const $btnText = $btn.find('.btn-text');
            const $btnSpinner = $btn.find('.btn-spinner');
            
            $btn.prop('disabled', true);
            $btnText.addClass('d-none');
            $btnSpinner.removeClass('d-none');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'archive_course',
                    course_id: courseId,
                    nonce: $('input[name="courscribe_course_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessNotification('Course archived successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorNotification('Failed to archive course: ' + response.data.message);
                    }
                },
                error: function() {
                    showErrorNotification('Network error occurred.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnSpinner.addClass('d-none');
                }
            });
        });

        // Restore course with unique selectors

        $(`.cs-unarchive-course-btn-${courseId}`).on('click', function() {
            const courseTitle = $(this).data('course-title');
            const $modal = $(`#cs-restoreCourseModal-${courseId}`);
            $modal.find('.course-name').text(courseTitle);
            $modal.modal('show');
        });

        $(`#cs-restoreCourseModal-${courseId} .confirm-restore-btn`).on('click', function() {
            const $btn = $(this);
            const $btnText = $btn.find('.btn-text') || $btn; // Fallback if no .btn-text
            const $btnSpinner = $btn.find('.btn-spinner') || $btn.find('.spinner-border').parent();

            $btn.prop('disabled', true);
            $btnText.addClass('d-none');
            $btnSpinner.removeClass('d-none');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'unarchive_course',
                    course_id: courseId,
                    nonce: $('input[name="courscribe_course_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessNotification('Course restored successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorNotification('Failed to restore course: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function() {
                    showErrorNotification('Network error occurred.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnSpinner.addClass('d-none');
                }
            });
        });
        
        
        
        // Delete course with unique selectors
        $(`.cs-delete-course-btn-${courseId}`).on('click', function() {
            const courseTitle = $(this).data('course-title');
            const $modal = $(`#cs-deleteCourseModal-${courseId}`);
            $modal.find('.course-name').text(courseTitle);
            $modal.modal('show');
        });
        
        $(`#cs-deleteCourseModal-${courseId} .confirm-delete-btn`).on('click', function() {
            const $btn = $(this);
            const $btnText = $btn.find('.btn-text');
            const $btnSpinner = $btn.find('.btn-spinner');
            
            $btn.prop('disabled', true);
            $btnText.addClass('d-none');
            $btnSpinner.removeClass('d-none');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'delete_course',
                    course_id: courseId,
                    nonce: $('input[name="courscribe_course_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessNotification('Course deleted successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorNotification('Failed to delete course: ' + response.data.message);
                    }
                },
                error: function() {
                    showErrorNotification('Network error occurred.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnSpinner.addClass('d-none');
                }
            });
        });
        
        // Manual save button 
        $(`.cs-manual-save-btn-${courseId}`).on('click', function() {
            const $btn = $(this);
            const $btnText = $btn.find('span');
            const $btnSpinner = $btn.find('.cs-save-spinner-' + courseId);

            $btn.prop('disabled', true);
            $btnText.text('Saving...');
            $btnSpinner.removeClass('d-none');

            const savePromises = [];

            // Check each field for changes
            $('.auto-save-field').each(function() {
                const $field = $(this);
                const fieldName = $field.data('field');
                const index = $field.data('index');
                let value = fieldName === 'objectives' ? collectObjectives() : $field.val();
                const originalValue = $field.data('original');

                if (value !== originalValue && !(Array.isArray(value) && JSON.stringify(value) === JSON.stringify(originalValue))) {
                    savePromises.push(saveField(fieldName, value, index).then(success => {
                        if (success) {
                            $field.data('original', value); // Update original value on success
                        }
                        return success;
                    }));
                }
            });

            Promise.all(savePromises).then(results => {
                const allSuccessful = results.length > 0 && results.every(result => result === true);
                if (allSuccessful && results.length > 0) {
                    showSuccessNotification('Course saved successfully!');
                    $btnText.text('Saved!');
                    setTimeout(() => $btnText.text('Save Changes'), 2000);
                } else if (results.length === 0) {
                    showSuccessNotification('No changes to save.');
                    $btnText.text('No Changes');
                    setTimeout(() => $btnText.text('Save Changes'), 2000);
                } else {
                    showErrorNotification('Some fields failed to save.');
                    $btnText.text('Save Failed');
                    setTimeout(() => $btnText.text('Save Changes'), 2000);
                }
            }).finally(() => {
                $btn.prop('disabled', false);
                $btnSpinner.addClass('d-none');
            });
        });
        
        // View logs functionality with unique selectors
        $(`.cs-view-logs-btn-${courseId}`).on('click', function() {
            loadCourseLogs(1); // Load first page
        });
        
        function loadCourseLogs(page = 1, search = '', actionFilter = '') {
            const $logsContent = $(`#cs-course-logs-content-${courseId}`);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_course_logs',
                    course_id: courseId,
                    page: page,
                    per_page: 10,
                    search: search,
                    action_filter: actionFilter,
                    nonce: $('input[name="courscribe_course_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        displayLogs(response.data);
                    } else {
                        $logsContent.html('<div class="alert alert-danger">Failed to load logs: ' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $logsContent.html('<div class="alert alert-danger">Network error occurred.</div>');
                }
            });
        }
        
        function displayLogs(data) {
            const $logsContent = $(`#cs-course-logs-content-${courseId}`);
            const $pagination = $(`#cs-logs-pagination-${courseId}`);
            
            if (data.logs.length === 0) {
                $logsContent.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No activity logs found</h6>
                        <p class="text-muted small">Course activity will appear here.</p>
                    </div>
                `);
                $pagination.hide();
                return;
            }
            
            let logsHtml = '';
            data.logs.forEach(log => {
                logsHtml += `
                    <div class="cs-log-item-${courseId}" data-log-id="${log.id}">
                        <div class="log-header">
                            <div class="d-flex align-items-center gap-2">
                                <span class="log-action ${log.action}">${log.action}</span>
                                <span class="log-meta">${log.formatted_time}</span>
                            </div>
                            <small class="log-meta">by ${log.user_name}</small>
                        </div>
                        <div class="log-changes">${log.formatted_changes}</div>
                        <div class="log-actions">
                            <button type="button" class="cs-restore-btn-${courseId}" data-log-id="${log.id}" data-course-id="${courseId}">
                                <i class="fas fa-undo me-1"></i>Restore
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $logsContent.html(logsHtml);
            
            // Update pagination
            updateLogsPagination(data);
        }
        
        function updateLogsPagination(data) {
            const $pagination = $(`#cs-logs-pagination-${courseId}`);
            
            if (data.pages <= 1) {
                $pagination.hide();
                return;
            }
            
            let paginationHtml = '';
            
            // Previous button
            if (data.current_page > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link cs-logs-page-link-${courseId}" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;
            }
            
            // Page numbers
            for (let i = 1; i <= data.pages; i++) {
                const isActive = i === data.current_page ? ' active' : '';
                paginationHtml += `<li class="page-item${isActive}"><a class="page-link cs-logs-page-link-${courseId}" href="#" data-page="${i}">${i}</a></li>`;
            }
            
            // Next button
            if (data.current_page < data.pages) {
                paginationHtml += `<li class="page-item"><a class="page-link cs-logs-page-link-${courseId}" href="#" data-page="${data.current_page + 1}">Next</a></li>`;
            }
            
            $pagination.find('.pagination').html(paginationHtml);
            $pagination.find('.pagination-info small').text(`Showing ${data.logs.length} of ${data.total} entries`);
            $pagination.show();
        }
        
        // Pagination click handler with unique selectors
        $(document).on('click', `.cs-logs-page-link-${courseId}`, function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            const search = $(`#cs-logs-search-${courseId}`).val();
            const filter = $(`#cs-logs-filter-${courseId}`).val();
            loadCourseLogs(page, search, filter);
        });
        
        // Search and filter handlers with unique selectors
        $(`#cs-logs-search-${courseId}`).on('input', debounce(function() {
            const search = $(this).val();
            const filter = $(`#cs-logs-filter-${courseId}`).val();
            loadCourseLogs(1, search, filter);
        }, 500));
        
        $(`#cs-logs-filter-${courseId}`).on('change', function() {
            const filter = $(this).val();
            const search = $(`#cs-logs-search-${courseId}`).val();
            loadCourseLogs(1, search, filter);
        });
        
        // Restore from log with unique selectors
        $(document).on('click', `.cs-restore-btn-${courseId}`, function() {
            const logId = $(this).data('log-id');
            const $btn = $(this);
            
            if (!confirm('Are you sure you want to restore the course to this previous state?')) {
                return;
            }
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'restore_course_from_log',
                    log_id: logId,
                    nonce: $('input[name="courscribe_course_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessNotification('Course restored successfully!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorNotification('Failed to restore course: ' + response.data.message);
                    }
                },
                error: function() {
                    showErrorNotification('Network error occurred.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-undo me-1"></i>Restore');
                }
            });
        });
        
        // Utility function for debouncing
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Initialize tooltips and other UI enhancements
        try {
            // Use Bootstrap 5 compatible tooltip initialization
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
                // Only initialize tooltips that haven't been initialized yet
                const uninitializedTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]:not([data-bs-original-title])');
                [].slice.call(uninitializedTooltips).map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            } else if (typeof $.fn.tooltip === 'function') {
                // Fallback to jQuery tooltip if available
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        } catch (error) {
            console.warn('CourScribe: Error initializing tooltips in course-fields:', error.message);
        }
    });
    </script>
    
    <?php
}
?>