/**
 * Path: courscribe/assets/js/courscribe/courses/edit.js
 */
jQuery(document).ready(function ($) {
    // Populate action verbs
    function populateActionVerbs(thinkingSkill, actionVerbField, currentActionVerb) {
        let actionVerbs = {
            'Know': ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
            'Comprehend': ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
            'Apply': ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
            'Analyze': ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
            'Evaluate': ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
            'Create': ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
        };

        actionVerbField.empty().append('<option value="" disabled>Select Action Verb</option>');

        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(function (verb) {
                let option = new Option(verb, verb);
                if (verb === currentActionVerb) {
                    $(option).attr('selected', 'selected');
                }
                actionVerbField.append(option);
            });
        }
    }

    // Initialize action verb dropdowns
    $('.action-verb-objective').each(function () {
        let thinkingSkill = $(this).closest('.objective-item-courses-list').find('.thinking-skill-objective').val();
        let actionVerbField = $(this);
        let currentActionVerb = actionVerbField.data('current-action-verb') || '';
        populateActionVerbs(thinkingSkill, actionVerbField, currentActionVerb);
    });

    // Handle thinking skill change
    $(document).on('change', '.thinking-skill-objective', function () {
        let thinkingSkill = $(this).val();
        let actionVerbField = $(this).closest('.objective-item-courses-list').find('.action-verb-objective');
        populateActionVerbs(thinkingSkill, actionVerbField, '');
    });

    $('.courscribe-course-edit-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        let form = $(this);
        let courseId = form.find('input[name="course_id"]').val();
        let curriculumId = form.find('input[name="curriculum_id"]').val();
        let nonce = form.find('input[name="courscribe_course_nonce"]').val();
        let courseName = $(`#course-name-${courseId}`).val().trim();
        let courseGoal = $(`#course-goal-${courseId}`).val().trim();
        let levelOfLearning = $(`#level-of-learning-${courseId}`).val().trim();
        let objectives = [];

        // Collect objectives
        $(`#objectives-list-${courseId} .objective-item-courses-list`).each(function() {
            const thinkingSkill = $(this).find('.thinking-skill-objective').val();
            const actionVerb = $(this).find('.action-verb-objective').val();
            // Find description within speech-input-wrapper
            const descriptionInput = $(this).find('.speech-input-wrapper .objective-description');
            const description = descriptionInput.length ? descriptionInput.val().trim() : '';

            if (thinkingSkill && actionVerb && description) {
                objectives.push({
                    thinking_skill: thinkingSkill,
                    action_verb: actionVerb,
                    description: description
                });
            }
        });

        // Validate inputs
        let errors = [];
        if (!courseId) errors.push('Course ID is required.');
        if (!curriculumId) errors.push('Curriculum ID is required.');
        if (!nonce) errors.push('Security nonce is missing.');
        if (!courseName) errors.push('Course name is required.');
        if (!courseGoal) errors.push('Course goal is required.');
        if (!levelOfLearning) errors.push('Level of learning is required.');
        if (objectives.length === 0) errors.push('At least one complete objective is required.');

        if (errors.length > 0) {
            let errorHtml = '<div class="courscribe-error"><p>Please correct the following errors:</p><ul>';
            errors.forEach(function(error) {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul></div>';
            form.find('.courscribe-error-container').html(errorHtml);
            return;
        }

        // Clear error container
        form.find('.courscribe-error-container').html('');

        // Prepare AJAX data
        let formData = {
            action: 'update_course',
            course_id: courseId,
            curriculum_id: curriculumId,
            courscribe_course_nonce: nonce,
            courscribe_submit_course: 1,
            courses: {}
        };
        formData.courses[courseId] = {
            course_name: courseName,
            course_goal: courseGoal,
            level_of_learning: levelOfLearning,
            objectives: objectives
        };

        // Send AJAX request
        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: formData,
            security: courscribeAjax.nonce,
            success: function(response) {
                form.find('.courscribe-error-container').html(response.data.message);
                if (response.success) {
                    // Optionally reset form or update UI
                    // form[0].reset();
                    alertbox.render({
                        alertIcon: 'success',
                        title: 'Course updated!',
                        message: 'Course saved successfully!',
                        btnTitle: 'Ok',
                        themeColor: '#665442',
                        btnColor: '#665442',
                        border: true
                    });
                }
            },
            error: function(xhr) {
                form.find('.courscribe-error-container').html(
                    '<div class="courscribe-error"><p>Error: ' + (xhr.responseText || 'Unknown error occurred') + '</p></div>'
                );
            }
        });
    });
    // Delete objective (AJAX)
    $('.courscribe-courses').on('click', '.delete-objective', function () {
        if (confirm('Are you sure you want to delete this objective?')) {
            let $objectiveItem = $(this).closest('.objective-item-courses-list');
            let courseId = $objectiveItem.data('course-id');
            let objectiveIndex = $objectiveItem.index();

            if ($(`#objectives-list-${courseId} .objective-item-courses-list`).length <= 1) {
                let errorHtml = '<div class="courscribe-error"><p>Please correct the following errors:</p><ul><li>At least one objective is required.</li></ul></div>';
                $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(errorHtml);
                return;
            }

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_objective',
                    course_id: courseId,
                    objective_index: objectiveIndex,
                    security: courscribeAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $objectiveItem.remove();
                        $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                            '<div class="courscribe-success"><p>Objective deleted successfully.</p></div>'
                        );
                    } else {
                        $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                            '<div class="courscribe-error"><p>' + (response.data.message || 'Failed to delete objective.') + '</p></div>'
                        );
                    }
                },
                error: function (xhr) {
                    $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                        '<div class="courscribe-error"><p>An error occurred: ' + (xhr.responseJSON?.data?.message || 'Please try again') + '</p></div>'
                    );
                }
            });
        }
    });

    // Delete course (AJAX)
    $('.as-course').on('click', '.delete-course', function () {
        if (confirm('Are you sure you want to delete this course?')) {
            let courseId = $(this).data('course-id');
            let curriculumId = $('#open-course-modal').data('curriculum-id');
            let $accordionItem = $(this).closest('.accordion-item');

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_course',
                    course_id: courseId,
                    curriculum_id: curriculumId,
                    security: courscribeAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $accordionItem.remove();
                        $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                            '<div class="courscribe-success"><p>Course deleted successfully.</p></div>'
                        );
                    } else {
                        $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                            '<div class="courscribe-error"><p>' + (response.data.message || 'Error occurred while deleting course.') + '</p></div>'
                        );
                    }
                },
                error: function (xhr) {
                    $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                        '<div class="courscribe-error"><p>An error occurred: ' + (xhr.responseJSON?.data?.message || 'Please try again') + '</p></div>'
                    );
                }
            });
        }
    });

    // Add new objective
    $('.courscribe-courses').on('click', '.add-new-objective-btn', function () {
        const courseId = $(this).data('course-id');
        const objectivesList = $(`#objectives-list-${courseId}`);

        if (!courseId || !objectivesList.length) {
            $(`#courscribe-course-edit-form-${courseId} .courscribe-error-container`).html(
                '<div class="courscribe-error"><p>Invalid course ID or objectives list not found.</p></div>'
            );
            return;
        }

        const objectiveIndex = objectivesList.find('.objective-item-courses-list').length;
        const objectiveNumber = objectiveIndex + 1;
        let newObjective = `
            <li class="objective-item-courses-list objective-item-course-${courseId} mb-3" data-course-id="${courseId}">
                <div class="text-dividerr-courses-list">
                    <span class="divider-textt">Objective ${objectiveNumber}</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 62%;">
                    <button type="button" class="remove-btn btn-sm delete-objective">Remove</button>
                </div>
                <div class="objective-row mb-2">
                    <label for="thinking-skill-${courseId}-${objectiveIndex}">Select the Thinking Skill</label>
                    <select class="form-control bg-dark text-light thinking-skill-objective" name="courses[${courseId}][objectives][${objectiveIndex}][thinking_skill]" id="thinking-skill-${courseId}-${objectiveIndex}" style="min-width: 180px; max-width: 240px; padding-inline: 0.5rem;">
                        <option value="Know">Know</option>
                        <option value="Comprehend">Comprehend</option>
                        <option value="Apply">Apply</option>
                        <option value="Analyze">Analyze</option>
                        <option value="Evaluate">Evaluate</option>
                        <option value="Create">Create</option>
                    </select>
                </div>
                <div class="text-dividerr">
                    <span class="divider-textt">Forms the Objectives</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 82%;">
                </div>
                <div class="objective-row mb-2">
                    <label for="action-verb-${courseId}-${objectiveIndex}">By the end of this Course they will: Objective ${objectiveNumber}</label>
                    <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                        <select class="form-control bg-dark text-light action-verb-objective" name="courses[${courseId}][objectives][${objectiveIndex}][action_verb]" id="action-verb-${courseId}-${objectiveIndex}" style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                            <option value="" disabled selected>Select Action Verb</option>
                        </select>
                        <input id="course-objective-description-${courseId}-${objectiveIndex}" style="flex:1" class="form-control bg-dark text-light objective-description" name="courses[${courseId}][objectives][${objectiveIndex}][description]" value="" />
                       
                        <button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                            data-field-id="course-objective-description-${courseId}-${objectiveNumber}"
                            data-bs-toggle="modal"
                            data-bs-target="#inputAiSuggestionsModal"
                            data-course-id="${courseId}"
                            data-course-name=""
                            data-course-goal=""
                            data-thinking-skill=""
                            data-action-verb="">
                            <i class="fa fa-magic"></i>
                        </button>
                    </div>
                </div>
            </li>
        `;

        objectivesList.append(newObjective);

        const thinkingSkillField = objectivesList.find(`#thinking-skill-${courseId}-${objectiveIndex}`);
        const actionVerbField = objectivesList.find(`#action-verb-${courseId}-${objectiveIndex}`);
        const defaultThinkingSkill = thinkingSkillField.val();
        populateActionVerbs(defaultThinkingSkill, actionVerbField, '');
    });

    // View logs (AJAX)
    $('.courscribe-courses').on('click', '.view-logs-btn', function () {
        const courseId = $(this).data('course-id');
        const logsContent = $(`#course-logs-content-${courseId}`);

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'courscribe_get_course_logs',
                course_id: courseId,
                security: courscribeAjax.nonce
            },
            success: function (response) {
                if (response.success && response.data.logs) {
                    let logsHtml = '<table class="table table-dark"><thead><tr><th>User</th><th>Action</th><th>Changes</th><th>Timestamp</th></tr></thead><tbody>';
                    response.data.logs.forEach(function (log) {
                        logsHtml += `<tr>
                            <td>${log.user_id}</td>
                            <td>${log.action}</td>
                            <td><pre>${JSON.stringify(JSON.parse(log.changes), null, 2)}</pre></td>
                            <td>${log.timestamp}</td>
                        </tr>`;
                    });
                    logsHtml += '</tbody></table>';
                    logsContent.html(logsHtml);
                } else {
                    logsContent.html('<p>No logs found for this course.</p>');
                }
            },
            error: function (xhr) {
                logsContent.html('<p>Error loading logs: ' + (xhr.responseJSON?.data?.message || 'Please try again') + '</p>');
            }
        });
    });
});