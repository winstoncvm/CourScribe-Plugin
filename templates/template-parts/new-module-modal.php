<?php
// Path: templates/template-parts/new-module-modal.php
if (!defined('ABSPATH')) {
    exit;
}

$site_url = home_url();
$current_user = wp_get_current_user();
$course_id = isset($course_id) ? absint($course_id) : 0; // Assume passed from shortcode or context
$message = ''; // For success/error messages

// Legacy form handling for fallback - now we use AJAX
if (isset($_POST['courscribe_submit_module_create']) && isset($_POST['courscribe_module_nonce'])) {
    if (!wp_verify_nonce($_POST['courscribe_module_nonce'], 'courscribe_module')) {
        $message = '<div class="courscribe-error"><p>Security check failed. Please try again.</p></div>';
    } else {
        $module_id = isset($_POST['module_id']) ? absint($_POST['module_id']) : 0;
        $title = sanitize_text_field($_POST['module_name'] ?? '');
        $goal = sanitize_text_field($_POST['module_goal'] ?? '');
        $objectives = isset($_POST['objectives']) && is_array($_POST['objectives']) ? $_POST['objectives'] : [];
        $course_id = absint($_POST['course_id'] ?? 0);
        
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
        if (empty($title)) {
            $errors[] = 'Module name is required.';
        }
        if (empty($goal)) {
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
        
        if ($tier === 'basics' && !$module_id && $module_count >= 5) {
            $errors[] = 'Your plan (Basics) allows only 5 modules per course. Upgrade to create more.';
        } elseif ($tier === 'plus' && !$module_id && $module_count >= 10) {
            $errors[] = 'Your plan (Plus) allows only 10 modules per course. Upgrade to Pro for unlimited.';
        }

        if (empty($errors)) {
            $post_data = [
                'ID' => $module_id,
                'post_title' => $title,
                'post_type' => 'crscribe_module',
                'post_status' => 'publish',
                'post_author' => $current_user->ID,
                'meta_input' => [
                    '_module_goal' => $goal,
                    '_module_objectives' => maybe_serialize($sanitized_objectives),
                    '_course_id' => $course_id,
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
                        'message' => 'Module created successfully!',
                        'module_id' => $post_id,
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

<!-- Add Module Modal Start -->
<div class="modal fade create-new-module-modal" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content courscribe-premium-modal">
            <!-- Premium Header with Gradient -->
            <div class="modal-header courscribe-premium-header">
                <div class="premium-header-content">
                    <div class="premium-icon">
                        <i class="fas fa-puzzle-piece"></i>
                    </div>
                    <div class="premium-title-section">
                        <h4 class="premium-modal-title" id="addModuleModalLabel">
                            <span class="gradient-text">Create New Module</span>
                        </h4>
                        <p class="premium-subtitle">Add a learning module to structure your course content</p>
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
                
                <form method="post" id="courscribe-module-form">
                    <input type="hidden" name="courscribe_submit_module_create" value="1">
                    <input type="hidden" name="courscribe_module_nonce" value="<?php echo wp_create_nonce('courscribe_module'); ?>">
                    <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">
                    <input type="hidden" name="module_id" value="0">

                    <!-- Module Name Field -->
                    <div class="mb-3">
                        <label for="module-name" class="form-label text-light">
                            <i class="fas fa-puzzle-piece me-2"></i>
                            Module Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               id="module-name" 
                               name="module_name" 
                               class="form-control premium-input" 
                               placeholder="Enter a descriptive module name"
                               value="<?php echo isset($_POST['module_name']) ? esc_attr($_POST['module_name']) : ''; ?>" 
                               required
                               maxlength="100" />
                        <div class="invalid-feedback"></div>
                        <div class="char-counter">
                            <span class="current">0</span>/<span class="max">100</span>
                        </div>
                        <small class="form-text text-muted">Choose a clear, engaging name for your module</small>
                    </div>

                    <!-- Module Goal Field -->
                    <div class="mb-3">
                        <label for="module-goal" class="form-label text-light">
                            <i class="fas fa-target me-2"></i>
                            Module Goal <span class="text-danger">*</span>
                        </label>
                        <textarea id="module-goal" 
                                  name="module_goal" 
                                  class="form-control premium-input" 
                                  rows="3"
                                  placeholder="Describe what students will achieve by completing this module"
                                  required
                                  maxlength="500"><?php echo isset($_POST['module_goal']) ? esc_textarea($_POST['module_goal']) : ''; ?></textarea>
                        <div class="invalid-feedback"></div>
                        <div class="char-counter">
                            <span class="current">0</span>/<span class="max">500</span>
                        </div>
                        <small class="form-text text-muted">Define the main learning outcome for this module</small>
                    </div>

                    <!-- Add Objectives Section -->
                    <div class="mb-4">
                        <div class="objectives-header d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-light mb-0">
                                <i class="fas fa-bullseye me-2"></i>
                                Learning Objectives
                            </h5>
                            <span class="badge item-count">1 Objective</span>
                        </div>
                        <div id="new-module-objectives-container" class="module-list-objectives-container">
                            <!-- Dynamic objectives will be added here -->
                        </div>
                        <div class="objectives-actions mt-3">
                            <button type="button" id="addNewModuleObjectiveBtn" class="btn premium-btn-outline">
                                <i class="fas fa-plus me-2"></i>
                                Add Another Objective
                            </button>
                            <small class="text-muted ms-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Add multiple objectives to enhance your module goals
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
                            <button type="submit" id="finalizeModuleBtn" class="btn premium-btn-primary" disabled>
                                <i class="fas fa-save me-2"></i>
                                <span class="btn-text">Create Module</span>
                                <div class="btn-loader d-none">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    <span>Creating...</span>
                                </div>
                            </button>
                        </div>
                        <div class="footer-info">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your module will be saved securely
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Add Module Modal End -->

<!-- Success Popup -->
<div class="courscribe-success-popup" id="moduleSuccessPopup" style="display: none;">
    <div class="success-popup-content">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Module Created Successfully!</h3>
        <p>Your module has been added to the course and is ready for lesson development.</p>
        <button type="button" class="btn premium-btn-primary" id="closeModuleSuccessPopup">
            Continue
        </button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let objectiveCount = $('.module-new-objective-item').length;
    
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
    addModuleObjective();
    
    // Add new objective button
    $('#addNewModuleObjectiveBtn').on('click', function() {
        addModuleObjective();
        updateModuleObjectiveCount();
    });
    
    // Function to add objective
    function addModuleObjective() {
        const objectiveHtml = `
            <div class="objective-item module-new-objective-item" data-index="${objectiveCount}">
                <button type="button" class="objective-remove-btn" onclick="removeModuleObjective(this)" ${objectiveCount === 0 ? 'style="display: none;"' : ''}>
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
                                  rows="3" 
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
        
        $('#new-module-objectives-container').append(objectiveHtml);
        objectiveCount++;
        
        // Initialize character counter for new objective
        initializeCharCounter();
    }
    
    // Handle thinking skill change to populate action verbs
    $(document).on('change', '.thinking-skill', function() {
        const thinkingSkill = $(this).val();
        const actionVerbSelect = $(this).closest('.module-new-objective-item').find('.action-verb');
        
        actionVerbSelect.empty().prop('disabled', false);
        actionVerbSelect.append('<option value="" disabled selected>Select Action Verb</option>');
        
        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(verb => {
                actionVerbSelect.append(`<option value="${verb}">${verb}</option>`);
            });
        }
    });
    
    // Remove objective function
    window.removeModuleObjective = function(button) {
        const objectiveItem = $(button).closest('.module-new-objective-item');
        objectiveItem.fadeOut(300, function() {
            $(this).remove();
            updateModuleObjectiveCount();
            reindexModuleObjectives();
            checkFormValidity();
        });
    };
    
    // Update objective count display
    function updateModuleObjectiveCount() {
        const count = $('.module-new-objective-item').length;
        $('.item-count').text(count + (count === 1 ? ' Objective' : ' Objectives'));
        
        // Show/hide remove buttons
        if (count <= 1) {
            $('.objective-remove-btn').hide();
        } else {
            $('.objective-remove-btn').show();
        }
    }
    
    // Reindex objectives after removal
    function reindexModuleObjectives() {
        $('.module-new-objective-item').each(function(index) {
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
    $('#courscribe-module-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateModuleForm()) {
            return false;
        }
        
        const $submitBtn = $('#finalizeModuleBtn');
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
                action: 'create_module_ajax',
                nonce: $('input[name="courscribe_module_nonce"]').val(),
                module_data: $(this).serialize()
            },
            success: function(response) {
                if (response.success) {
                    showModuleSuccessPopup();
                } else {
                    showModuleError(response.data.message || 'An error occurred');
                }
            },
            error: function() {
                showModuleError('Network error. Please try again.');
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $btnLoader.addClass('d-none');
            }
        });
    });
    
    function validateModuleForm() {
        let isValid = true;
        const requiredFields = ['module_name', 'module_goal'];

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
        const objectives = $('.module-new-objective-item');
        if (objectives.length === 0) {
            showModuleError('At least one objective is required.');
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
            showModuleError('Please fill in all required fields.');
        }

        return isValid;
    }
    
    // Show success popup
    function showModuleSuccessPopup() {
        $('#moduleSuccessPopup').fadeIn(300).addClass('show');
        $('#addModuleModal').modal('hide');
    }
    
    // Close success popup and reload
    $('#closeModuleSuccessPopup').on('click', function() {
        $('#moduleSuccessPopup').fadeOut(300, function() {
            window.location.reload();
        });
    });
    
    // Show error message
    function showModuleError(message) {
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
        const hasModuleName = $('#module-name').val().trim();
        const hasModuleGoal = $('#module-goal').val().trim();
        const hasObjectives = $('.module-new-objective-item').length > 0;
        
        $('#finalizeModuleBtn').prop('disabled', !(hasModuleName && hasModuleGoal && hasObjectives));
    }
    
    $('#courscribe-module-form input, #courscribe-module-form select, #courscribe-module-form textarea').on('input change', checkFormValidity);
    
    // Initialize character counters and form validation
    initializeCharCounter();
    updateModuleObjectiveCount();
    checkFormValidity();
});
</script>