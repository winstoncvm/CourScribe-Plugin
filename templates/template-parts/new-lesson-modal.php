<?php
// Path: templates/template-parts/new-lesson-modal.php
if (!defined('ABSPATH')) {
    exit;
}

$site_url = home_url();
$current_user = wp_get_current_user();
$course_id = isset($course_id) ? absint($course_id) : (isset($_GET['course_id']) ? absint($_GET['course_id']) : 0);
$module_id = isset($module_id) ? absint($module_id) : 0;
$message = '';

// Legacy form handling for fallback - now we use AJAX
if (isset($_POST['courscribe_submit_lesson_create']) && isset($_POST['courscribe_lesson_nonce'])) {
    if (!wp_verify_nonce($_POST['courscribe_lesson_nonce'], 'courscribe_lesson')) {
        $message = '<div class="courscribe-error"><p>Security check failed. Please try again.</p></div>';
    } else {
        $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
        $lesson_name = sanitize_text_field($_POST['lesson_name'] ?? '');
        $lesson_goal = sanitize_text_field($_POST['lesson_goal'] ?? '');
        $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
        $activities = isset($_POST['activities']) && is_array($_POST['activities']) ? $_POST['activities'] : [];
        $module_id = absint($_POST['module_id'] ?? 0);
        $course_id = absint($_POST['course_id'] ?? 0);

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
        
        if ($tier === 'basics' && !$lesson_id && $lesson_count >= 10) {
            $errors[] = 'Your plan (Basics) allows only 10 lessons per module. Upgrade to create more.';
        } elseif ($tier === 'plus' && !$lesson_id && $lesson_count >= 20) {
            $errors[] = 'Your plan (Plus) allows only 20 lessons per module. Upgrade to Pro for unlimited.';
        }

        if (empty($errors)) {
            $post_data = [
                'ID' => $lesson_id,
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
            if (!is_wp_error($post_id)) {
                // Handle AJAX response
                if (wp_doing_ajax()) {
                    wp_send_json_success([
                        'message' => 'Lesson created successfully!',
                        'lesson_id' => $post_id,
                        'redirect_url' => $_SERVER['REQUEST_URI']
                    ]);
                } else {
                    wp_safe_redirect($_SERVER['REQUEST_URI']);
                    exit;
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

<!-- Link to the premium modal CSS -->
<link rel="stylesheet" href="<?php echo esc_url(plugin_dir_url(__DIR__) . '../assets/css/components/premium-modals.css'); ?>">

<!-- Font Awesome CDN -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

<!-- Add Lesson Modal Start -->
<div class="modal fade create-new-lesson-modal" id="addLessonModal" tabindex="-1" aria-labelledby="addLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content courscribe-premium-modal">
            <!-- Premium Header with Gradient -->
            <div class="modal-header courscribe-premium-header">
                <div class="premium-header-content">
                    <div class="premium-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="premium-title-section">
                        <h4 class="premium-modal-title" id="addLessonModalLabel">
                            <span class="gradient-text">Create New Lesson</span>
                        </h4>
                        <p class="premium-subtitle">Design an engaging lesson with objectives and activities</p>
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
                
                <form method="post" id="courscribe-lesson-form">
                    <input type="hidden" name="courscribe_submit_lesson_create" value="1">
                    <input type="hidden" name="courscribe_lesson_nonce" value="<?php echo wp_create_nonce('courscribe_lesson'); ?>">
                    <input type="hidden" name="course_id" id="lesson-course-id" value="<?php echo esc_attr($course_id); ?>">
                    <input type="hidden" name="module_id" id="lesson-module-id" value="<?php echo esc_attr($module_id); ?>">
                    <input type="hidden" name="lesson_id" value="0">

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Lesson Name Field -->
                            <div class="mb-3">
                                <label for="lesson-name" class="form-label text-light">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>
                                    Lesson Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       id="lesson-name" 
                                       name="lesson_name" 
                                       class="form-control premium-input" 
                                       placeholder="Enter a descriptive lesson name"
                                       value="<?php echo isset($_POST['lesson_name']) ? esc_attr($_POST['lesson_name']) : ''; ?>" 
                                       required
                                       maxlength="100" />
                                <div class="invalid-feedback"></div>
                                <div class="char-counter">
                                    <span class="current">0</span>/<span class="max">100</span>
                                </div>
                                <small class="form-text text-muted">Choose a clear, engaging name for your lesson</small>
                            </div>

                            <!-- Lesson Goal Field -->
                            <div class="mb-3">
                                <label for="lesson-goal" class="form-label text-light">
                                    <i class="fas fa-bullseye me-2"></i>
                                    Lesson Goal <span class="text-danger">*</span>
                                </label>
                                <textarea id="lesson-goal" 
                                          name="lesson_goal" 
                                          class="form-control premium-input" 
                                          rows="3"
                                          placeholder="Describe what students will achieve in this lesson"
                                          required
                                          maxlength="500"><?php echo isset($_POST['lesson_goal']) ? esc_textarea($_POST['lesson_goal']) : ''; ?></textarea>
                                <div class="invalid-feedback"></div>
                                <div class="char-counter">
                                    <span class="current">0</span>/<span class="max">500</span>
                                </div>
                                <small class="form-text text-muted">Define the main learning outcome for this lesson</small>
                            </div>

                            <!-- Activities Section -->
                            <div class="mb-4">
                                <div class="objectives-header d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-light mb-0">
                                        <i class="fas fa-tasks me-2"></i>
                                        Learning Activities
                                    </h5>
                                    <span class="badge item-count activity-count">0 Activities</span>
                                </div>
                                <div id="new-lesson-activities-container" class="lesson-list-activities-container">
                                    <!-- Dynamic activities will be added here -->
                                </div>
                                <div class="objectives-actions mt-3">
                                    <button type="button" id="addNewLessonActivityBtn" class="btn premium-btn-outline">
                                        <i class="fas fa-plus me-2"></i>
                                        Add Activity
                                    </button>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Add engaging activities to support learning
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Objectives Section -->
                            <div class="mb-4">
                                <div class="objectives-header d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-light mb-0">
                                        <i class="fas fa-bullseye me-2"></i>
                                        Learning Objectives
                                    </h5>
                                    <span class="badge item-count objective-count">1 Objective</span>
                                </div>
                                <div id="new-lesson-objectives-container" class="lesson-list-objectives-container">
                                    <!-- Dynamic objectives will be added here -->
                                </div>
                                <div class="objectives-actions mt-3">
                                    <button type="button" id="addNewLessonObjectiveBtn" class="btn premium-btn-outline">
                                        <i class="fas fa-plus me-2"></i>
                                        Add Another Objective
                                    </button>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Add multiple objectives to enhance your lesson
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Premium Modal Footer -->
                    <div class="modal-footer courscribe-premium-footer">
                        <div class="footer-actions">
                            <button type="button" class="btn premium-btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>
                                <span>Cancel</span>
                            </button>
                            <button type="submit" id="finalizeLessonBtn" class="btn premium-btn-primary" disabled>
                                <i class="fas fa-save me-2"></i>
                                <span class="btn-text">Create Lesson</span>
                                <div class="btn-loader d-none">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span>Creating...</span>
                                </div>
                            </button>
                        </div>
                        <div class="footer-info">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your lesson will be saved securely
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Add Lesson Modal End -->

<!-- Success Popup -->
<div class="courscribe-success-popup" id="lessonSuccessPopup" style="display: none;">
    <div class="success-popup-content">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Lesson Created Successfully!</h3>
        <p>Your lesson has been added to the module and is ready for teaching point development.</p>
        <button type="button" class="btn premium-btn-primary" id="closeLessonSuccessPopup">
            Continue
        </button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let objectiveCount = $('.lesson-new-objective-item').length;
    let activityCount = $('.lesson-new-activity-item').length;
    
    // Action verbs for each thinking skill
    const actionVerbs = {
        'Know': ['Define', 'List', 'Identify', 'Name', 'State', 'Describe', 'Recognize', 'Select', 'Match', 'Recall'],
        'Comprehend': ['Explain', 'Summarize', 'Interpret', 'Classify', 'Compare', 'Contrast', 'Demonstrate', 'Illustrate', 'Paraphrase', 'Translate'],
        'Apply': ['Use', 'Execute', 'Implement', 'Solve', 'Demonstrate', 'Apply', 'Construct', 'Change', 'Prepare', 'Produce'],
        'Analyze': ['Analyze', 'Break down', 'Compare', 'Contrast', 'Differentiate', 'Examine', 'Investigate', 'Categorize', 'Organize', 'Deconstruct'],
        'Evaluate': ['Judge', 'Assess', 'Evaluate', 'Critique', 'Justify', 'Defend', 'Support', 'Validate', 'Rate', 'Prioritize'],
        'Create': ['Create', 'Design', 'Develop', 'Compose', 'Construct', 'Generate', 'Plan', 'Produce', 'Invent', 'Formulate']
    };
    
    // Activity types
    const activityTypes = [
        'Discussion', 'Quiz', 'Assignment', 'Presentation', 'Group Work', 
        'Case Study', 'Role Play', 'Simulation', 'Research', 'Project', 
        'Workshop', 'Lecture', 'Demo', 'Lab Work', 'Field Trip', 'Other'
    ];
    
    // Initialize with first objective
    addLessonObjective();
    
    // Add new objective button
    $('#addNewLessonObjectiveBtn').on('click', function() {
        addLessonObjective();
        updateLessonObjectiveCount();
    });
    
    // Add new activity button
    $('#addNewLessonActivityBtn').on('click', function() {
        addLessonActivity();
        updateLessonActivityCount();
    });
    
    // Function to add objective
    function addLessonObjective() {
        const objectiveHtml = `
            <div class="objective-item lesson-new-objective-item" data-index="${objectiveCount}">
                <button type="button" class="objective-remove-btn" onclick="removeLessonObjective(this)" ${objectiveCount === 0 ? 'style="display: none;"' : ''}>
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
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Action Verb <span class="text-danger">*</span></label>
                        <select class="form-control premium-input action-verb" name="objectives[${objectiveCount}][action_verb]" required>
                            <option value="" disabled selected>Select Action Verb</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-light">Objective Description <span class="text-danger">*</span></label>
                        <textarea class="form-control premium-input objective-description" 
                                  name="objectives[${objectiveCount}][description]" 
                                  placeholder="Enter a clear description of what students will be able to do"
                                  rows="2" 
                                  maxlength="300" 
                                  required></textarea>
                        <div class="invalid-feedback"></div>
                        <div class="char-counter">
                            <span class="current">0</span>/<span class="max">300</span>
                        </div>
                        <small class="form-text text-muted">Be specific about the expected learning outcome</small>
                    </div>
                </div>
            </div>
        `;
        
        $('#new-lesson-objectives-container').append(objectiveHtml);
        objectiveCount++;
        
        initializeCharCounter();
    }
    
    // Function to add activity
    function addLessonActivity() {
        const activityOptions = activityTypes.map(type => 
            `<option value="${type}">${type}</option>`
        ).join('');
        
        const activityHtml = `
            <div class="objective-item lesson-new-activity-item" data-index="${activityCount}">
                <button type="button" class="objective-remove-btn" onclick="removeLessonActivity(this)" ${activityCount === 0 ? 'style="display: none;"' : ''}>
                    <i class="fas fa-times me-1"></i>Remove
                </button>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Activity Type</label>
                        <select class="form-control premium-input activity-type" name="activities[${activityCount}][type]">
                            <option value="" disabled selected>Select Activity Type</option>
                            ${activityOptions}
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Activity Title</label>
                        <input type="text" 
                               class="form-control premium-input activity-title" 
                               name="activities[${activityCount}][title]" 
                               placeholder="Enter activity title"
                               maxlength="100" />
                        <div class="invalid-feedback"></div>
                        <div class="char-counter">
                            <span class="current">0</span>/<span class="max">100</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-light">Activity Instructions</label>
                        <textarea class="form-control premium-input activity-instructions" 
                                  name="activities[${activityCount}][instructions]" 
                                  placeholder="Enter detailed instructions for this activity"
                                  rows="3" 
                                  maxlength="1000"></textarea>
                        <div class="invalid-feedback"></div>
                        <div class="char-counter">
                            <span class="current">0</span>/<span class="max">1000</span>
                        </div>
                        <small class="form-text text-muted">Provide clear instructions for students and instructors</small>
                    </div>
                </div>
            </div>
        `;
        
        $('#new-lesson-activities-container').append(activityHtml);
        activityCount++;
        
        initializeCharCounter();
    }
    
    // Handle thinking skill change to populate action verbs
    $(document).on('change', '.thinking-skill', function() {
        const thinkingSkill = $(this).val();
        const actionVerbSelect = $(this).closest('.lesson-new-objective-item').find('.action-verb');
        
        actionVerbSelect.empty().prop('disabled', false);
        actionVerbSelect.append('<option value="" disabled selected>Select Action Verb</option>');
        
        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(verb => {
                actionVerbSelect.append(`<option value="${verb}">${verb}</option>`);
            });
        }
    });
    
    // Remove objective function
    window.removeLessonObjective = function(button) {
        const objectiveItem = $(button).closest('.lesson-new-objective-item');
        objectiveItem.fadeOut(300, function() {
            $(this).remove();
            updateLessonObjectiveCount();
            reindexLessonObjectives();
            checkFormValidity();
        });
    };
    
    // Remove activity function
    window.removeLessonActivity = function(button) {
        const activityItem = $(button).closest('.lesson-new-activity-item');
        activityItem.fadeOut(300, function() {
            $(this).remove();
            updateLessonActivityCount();
            reindexLessonActivities();
            checkFormValidity();
        });
    };
    
    // Update objective count display
    function updateLessonObjectiveCount() {
        const count = $('.lesson-new-objective-item').length;
        $('.objective-count').text(count + (count === 1 ? ' Objective' : ' Objectives'));
        
        // Show/hide remove buttons for objectives
        if (count <= 1) {
            $('.lesson-new-objective-item .objective-remove-btn').hide();
        } else {
            $('.lesson-new-objective-item .objective-remove-btn').show();
        }
    }
    
    // Update activity count display
    function updateLessonActivityCount() {
        const count = $('.lesson-new-activity-item').length;
        $('.activity-count').text(count + (count === 1 ? ' Activity' : ' Activities'));
        
        // Show/hide remove buttons for activities
        if (count <= 1) {
            $('.lesson-new-activity-item .objective-remove-btn').hide();
        } else {
            $('.lesson-new-activity-item .objective-remove-btn').show();
        }
    }
    
    // Reindex objectives after removal
    function reindexLessonObjectives() {
        $('.lesson-new-objective-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name && name.includes('objectives')) {
                    const newName = name.replace(/objectives\[\d+\]/, `objectives[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    // Reindex activities after removal
    function reindexLessonActivities() {
        $('.lesson-new-activity-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('select, input, textarea').each(function() {
                const name = $(this).attr('name');
                if (name && name.includes('activities')) {
                    const newName = name.replace(/activities\[\d+\]/, `activities[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }
    
    // Character counter functionality
    function initializeCharCounter() {
        $('[maxlength]').each(function() {
            const $field = $(this);
            const $counter = $field.siblings('.char-counter');
            if ($counter.length) {
                updateCharCount($field, $counter);
            }
        });
    }
    
    $(document).on('input', '[maxlength]', function() {
        const $counter = $(this).siblings('.char-counter');
        if ($counter.length) {
            updateCharCount($(this), $counter);
        }
    });
    
    function updateCharCount($field, $counter) {
        const current = $field.val().length;
        const max = parseInt($field.attr('maxlength'));
        const $current = $counter.find('.current');
        
        $current.text(current);
        
        // Update counter styling based on usage
        $counter.removeClass('warning danger');
        if (current > max * 0.8) {
            $counter.addClass(current > max * 0.95 ? 'danger' : 'warning');
        }
    }
    
    // Form validation and submission
    $('#courscribe-lesson-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateLessonForm()) {
            return false;
        }
        
        const $submitBtn = $('#finalizeLessonBtn');
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
                action: 'create_lesson_ajax',
                nonce: $('input[name="courscribe_lesson_nonce"]').val(),
                lesson_data: $(this).serialize()
            },
            success: function(response) {
                if (response.success) {
                    showLessonSuccessPopup();
                } else {
                    showLessonError(response.data.message || 'An error occurred');
                }
            },
            error: function() {
                showLessonError('Network error. Please try again.');
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $btnLoader.addClass('d-none');
            }
        });
    });
    
    function validateLessonForm() {
        let isValid = true;
        const requiredFields = ['lesson_name', 'lesson_goal'];

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('').hide();

        // Validate basic fields
        requiredFields.forEach(field => {
            const $field = $(`[name="${field}"]`);
            const value = $field.val();

            if (!value || !String(value).trim()) {
                $field.addClass('is-invalid');
                $field.siblings('.invalid-feedback').text('This field is required.').show();
                isValid = false;
            }
        });

        // Validate objectives
        const objectives = $('.lesson-new-objective-item');
        if (objectives.length === 0) {
            showLessonError('At least one objective is required.');
            return false;
        }

        objectives.each(function() {
            const $item = $(this);
            const $thinkingSkill = $item.find('.thinking-skill');
            const $actionVerb = $item.find('.action-verb');
            const $description = $item.find('.objective-description');

            if (!$thinkingSkill.val()) {
                $thinkingSkill.addClass('is-invalid');
                $thinkingSkill.siblings('.invalid-feedback').text('Please select a thinking skill.').show();
                isValid = false;
            }
            if (!$actionVerb.val()) {
                $actionVerb.addClass('is-invalid');
                $actionVerb.siblings('.invalid-feedback').text('Please select an action verb.').show();
                isValid = false;
            }
            if (!$description.val() || !String($description.val()).trim()) {
                $description.addClass('is-invalid');
                $description.siblings('.invalid-feedback').text('Please enter a description.').show();
                isValid = false;
            }
        });

        if (!isValid) {
            showLessonError('Please fill in all required fields.');
        }

        return isValid;
    }
    
    // Show success popup
    function showLessonSuccessPopup() {
        $('#lessonSuccessPopup').fadeIn(300).addClass('show');
        $('#addLessonModal').modal('hide');
    }
    
    // Close success popup and reload
    $('#closeLessonSuccessPopup').on('click', function() {
        $('#lessonSuccessPopup').fadeOut(300, function() {
            window.location.reload();
        });
    });
    
    // Show error message
    function showLessonError(message) {
        // Create or update error alert
        let $errorAlert = $('.modal-body').find('.alert-danger');
        if ($errorAlert.length === 0) {
            $errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert"></div>');
            $('.modal-body').prepend($errorAlert);
        }
        
        $errorAlert.html(`
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `);
        
        // Scroll to top of modal
        $('.modal-body').scrollTop(0);
    }
    
    // Enable/disable submit button based on form validity
    function checkFormValidity() {
        const hasLessonName = $('#lesson-name').val().trim();
        const hasLessonGoal = $('#lesson-goal').val().trim();
        const hasObjectives = $('.lesson-new-objective-item').length > 0;
        
        $('#finalizeLessonBtn').prop('disabled', !(hasLessonName && hasLessonGoal && hasObjectives));
    }
    
    $('#courscribe-lesson-form input, #courscribe-lesson-form select, #courscribe-lesson-form textarea').on('input change', checkFormValidity);
    
    // Initialize character counters and form validation
    initializeCharCounter();
    updateLessonObjectiveCount();
    updateLessonActivityCount();
    checkFormValidity();
});
</script>