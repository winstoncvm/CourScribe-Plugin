/**
 * Path: courscribe/assets/js/courscribe/lessons/edit.js
 */
jQuery(document).ready(function ($) {
    function populateActionVerbs(thinkingSkill, actionVerbField, currentActionVerb = null) {
        let actionVerbs = {
            'Know': ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
            'Comprehend': ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
            'Apply': ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
            'Analyze': ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
            'Evaluate': ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
            'Create': ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
        };

        actionVerbField.empty();
        actionVerbField.append(new Option("Select Action Verb", "", true, false));

        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(function (verb) {
                let option = new Option(verb, verb, false, verb === currentActionVerb);
                actionVerbField.append(option);
            });
        }
    }

    // Initialize objectives
    $('.lesson-list-objective-container').each(function () {
        let objectiveItem = $(this);
        let objectiveId = objectiveItem.data('objective-id');
        let thinkingSkillField = objectiveItem.find('.thinking-skill-objective-lessons');
        let actionVerbField = objectiveItem.find('.action-verb-objective-lessons');
        let currentThinkingSkill = thinkingSkillField.val();
        let currentActionVerb = actionVerbField.data('current-action-verb');

        if (currentThinkingSkill) {
            populateActionVerbs(currentThinkingSkill, actionVerbField, currentActionVerb);
        }
    });

    $(document).on('change', '.thinking-skill-objective-lessons', function () {
        let objectiveId = $(this).data('objective-id');
        let thinkingSkill = $(this).val();
        let actionVerbField = $('#lesson-current-action-verb-' + objectiveId);
        populateActionVerbs(thinkingSkill, actionVerbField);
    });

    // Add Objective
    $(document).on('click', '#addLessonListObjectiveBtn', function () {
        let lessonId = $(this).data('lesson-id');
        let objectivesContainer = $('#lesson-objectives-list-' + lessonId);
        let uniqueId = 'newobj-' + Math.random().toString(36).substr(2, 9);

        let newObjective = `
            <li class="lesson-list-objective-container mb-3" data-objective-id="${uniqueId}">
                <div class="text-dividerr-lessons-list">
                    <span class="divider-textt">Objective Formation</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 62%;">
                    <button type="button" class="remove-btn btn-sm delete-objective">Cancel</button>
                </div>
                <div class="mb-2">
                    <label for="lesson-thinking-skill-${uniqueId}">Select the Thinking Skill</label>
                    <select id="lesson-thinking-skill-${uniqueId}" data-objective-id="${uniqueId}" class="form-control bg-dark text-light thinking-skill-objective-lessons" style="min-width:80px; max-width:140px; padding-inline:0.5rem;">
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
                    <label for="lesson-current-action-verb-${uniqueId}">By the end of this Lesson they will:</label>
                    <div class="d-flex w-100 my-mr-1 mb-2 gap2">
                        <select id="lesson-current-action-verb-${uniqueId}" data-objective-id="${uniqueId}" class="form-control bg-dark text-light action-verb-objective-lessons" style="min-width:80px; max-width:140px; padding-inline:0.5rem;">
                            <option value="" disabled selected>Select Action Verb</option>
                        </select>
                        <input type="text" id="lesson-objective-description-${uniqueId}" class="form-control bg-dark text-light objective-description" placeholder="Enter Object/noun with accuracy..." style="flex:1; margin-inline:1rem;"/>
                    </div>
                </div>
            </li>`;

        objectivesContainer.append(newObjective);
        populateActionVerbs('Know', $('#lesson-current-action-verb-' + uniqueId));
    });

    // Add Activity
    $(document).on('click', '#addLessonListActivityBtn', function () {
        let lessonId = $(this).data('lesson-id');
        let activitiesContainer = $('#lesson-activities-list-' + lessonId);
        let uniqueId = 'newact-' + Math.random().toString(36).substr(2, 9);

        let newActivity = `
            <li class="lesson-list-activity-container mb-3" data-activity-id="${uniqueId}">
                <div class="text-dividerr-lessons-list">
                    <span class="divider-textt">Activity</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 62%;">
                    <button type="button" class="remove-btn btn-sm delete-activity">Cancel</button>
                </div>
                <div class="mb-2">
                    <label for="lesson-activity-type-${uniqueId}">Activity Type</label>
                    <select id="lesson-activity-type-${uniqueId}" data-activity-id="${uniqueId}" class="form-control bg-dark text-light activity-type" style="min-width:120px; max-width:180px; padding-inline:0.5rem;">
                        <option value="Quiz">Quiz</option>
                        <option value="Discussion">Discussion</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Presentation">Presentation</option>
                        <option value="Group Work">Group Work</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="lesson-activity-title-${uniqueId}">Title</label>
                    <input type="text" id="lesson-activity-title-${uniqueId}" class="form-control bg-dark text-light activity-title" placeholder="Enter activity title" style="flex:1;"/>
                </div>
                <div class="mb-2">
                    <label for="lesson-activity-instructions-${uniqueId}">Instructions</label>
                    <textarea id="lesson-activity-instructions-${uniqueId}" class="form-control bg-dark text-light activity-instructions" placeholder="Enter instructions" rows="4" style="flex:1;"></textarea>
                </div>
            </li>`;

        activitiesContainer.append(newActivity);
    });

    // Delete Objective
    $(document).on('click', '.delete-objective', function () {
        if (confirm('Are you sure you want to remove this objective?')) {
            $(this).closest('.lesson-list-objective-container').remove();
        }
    });

    // Delete Activity
    $(document).on('click', '.delete-activity', function () {
        if (confirm('Are you sure you want to remove this activity?')) {
            $(this).closest('.lesson-list-activity-container').remove();
        }
    });

    // Save Lesson
    $(document).on('click', '.save-lesson', function () {
        let lessonId = $(this).data('lesson-id');
        let moduleId = $(this).data('module-id');
        let courseId = $(this).data('course-id');
        let lessonName = $('#lesson-name-' + lessonId).val();
        let lessonGoal = $('#lesson-goal-' + lessonId).val();
        let activities = [];

        function collectObjectives(lessonId) {
            let objectives = [];
            // Updated selector to match the HTML structure
            let objectiveItems = $('#lesson-objectives-list-' + lessonId + ' .lesson-list-objective-container');

            objectiveItems.each(function () {
                let objectiveId = $(this).data('objective-id');
                // Updated selectors to match the HTML IDs
                let thinkingSkill = $('#lesson-thinking-skill-' + objectiveId).val() || '';
                let actionVerb = $('#lesson-current-action-verb-' + objectiveId).val() || '';
                // Direct selector for the objective description input
                let description = $('#lesson-objective-description-' + lessonId + '-' + objectiveId).val() || '';

                if (thinkingSkill || actionVerb || description) {
                    objectives.push({
                        id: objectiveId,
                        thinking_skill: thinkingSkill,
                        action_verb: actionVerb,
                        description: description
                    });
                }
            });
            return objectives;
        }

        // Collect Objectives
        let objectives = collectObjectives(lessonId);
        console.log('Collected objectives:', objectives);

        // Collect Activities
        $('#lesson-activities-list-' + lessonId + ' .lesson-list-activity-container').each(function () {
            let activityItem = $(this);
            let activityId = activityItem.data('activity-id');
            let type = $('#lesson-activity-type-' + activityId).val();
            let title = $('#lesson-activity-title-' + activityId).val();
            let instructions = $('#lesson-activity-instructions-' + activityId).val();

            if (type && title && instructions) {
                activities.push({
                    id: activityId,
                    type: type,
                    title: title,
                    instructions: instructions
                });
            }
        });

        if (!lessonName || !lessonGoal) {
            alertbox.render({
                alertIcon: 'error',
                title: 'Cannot save lesson',
                message: 'Lesson name and goal are required.',
                btnTitle: 'Ok',
                themeColor: '#000000',
                btnColor: '#665442',
                border: true
            });
            return;
        }

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_lesson_changes',
                lesson_id: lessonId,
                module_id: moduleId,
                course_id: courseId,
                lesson_name: lessonName,
                lesson_goal: lessonGoal,
                objectives: objectives,
                activities: activities,
                nonce: courscribeAjax.nonce
            },
            success: function (response) {
                console.log('Save lesson response:', response);
                if (response.success) {
                    alertbox.render({
                        alertIcon: 'success',
                        title: 'Lesson saved!',
                        message: 'Changes saved successfully.',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                    location.reload();
                } else {
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Lesson not saved!',
                        message: response.data || 'Failed to save changes.',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('Save lesson error:', xhr.responseText);
                alertbox.render({
                    alertIcon: 'error',
                    title: 'Lesson not saved!',
                    message: 'An error occurred: ' + (xhr.responseJSON?.data || 'Please try again'),
                    btnTitle: 'Ok',
                    themeColor: '#000000',
                    btnColor: '#665442',
                    border: true
                });
            }
        });
    });

    // Delete Lesson
    $(document).on('click', '.delete-lesson', function () {
        if (!confirm('Are you sure you want to delete this lesson?')) return;

        let lessonId = $(this).data('lesson-id');

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_lesson',
                lesson_id: lessonId,
                nonce: courscribeAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alertbox.render({
                        alertIcon: 'success',
                        title: 'Lesson deleted!',
                        message: response.data.message,
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                    location.reload();
                } else {
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Deletion failed!',
                        message: response.data || 'Failed to delete lesson.',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                }
            },
            error: function () {
                alertbox.render({
                    alertIcon: 'error',
                    title: 'Deletion failed!',
                    message: 'An error occurred while deleting the lesson.',
                    btnTitle: 'Ok',
                    themeColor: '#000000',
                    btnColor: '#665442',
                    border: true
                });
            }
        });
    });
});