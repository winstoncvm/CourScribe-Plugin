/**
 * Path: courscribe/assets/js/courscribe/lessons/create.js
 */
jQuery(document).ready(function ($) {
    // Set course_id and module_id when modal is opened
    $('.add-lesson').on('click', function () {
        const courseId = $(this).data('course-id');
        const moduleId = $(this).data('module-id');
        $('#lesson-course-id').val(courseId);
        $('#lesson-module-id').val(moduleId);
        $('#addLessonModal').data('module-id', moduleId);
    });

    // Update Action Verbs based on Thinking Skill
    function populateActionVerbs(thinkingSkill, actionVerbField) {
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
                actionVerbField.append(new Option(verb, verb));
            });
        }
    }

    // Initialize first objective's action verbs
    $('#lesson-objectives-container .objective-item').each(function () {
        let thinkingSkillField = $(this).find('.thinking-skill');
        let actionVerbField = $(this).find('.action-verb');
        let currentThinkingSkill = thinkingSkillField.val();
        if (currentThinkingSkill) {
            populateActionVerbs(currentThinkingSkill, actionVerbField);
        }
    });

    // Update action verbs when thinking skill changes
    $(document).on('change', '.thinking-skill', function () {
        let objectiveItem = $(this).closest('.objective-item');
        let thinkingSkill = $(this).val();
        let actionVerbField = objectiveItem.find('.action-verb');
        populateActionVerbs(thinkingSkill, actionVerbField);
    });

    // Add Objective
    let objectiveCounter = $('#lesson-objectives-container .objective-item').length;
    $('#addLessonObjectiveBtn').on('click', function () {
        let uniqueId = 'obj-' + objectiveCounter;
        let newObjective = `
            <div class="objective-item mb-3 p-2" data-objective-id="${uniqueId}">
                <div class="text-dividerr d-flex justify-content-between align-items-center mb-3">
                    <span class="divider-textt">Objective Formation</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 32%;">
                    <button type="button" class="remove-btn btn-sm btn-danger delete-objective">Remove</button>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="thinking-skill-${objectiveCounter}" class="form-label text-light">Thinking Skill</label>
                        <select class="form-control bg-dark text-light thinking-skill w-100" name="objectives[${objectiveCounter}][thinking_skill]" id="thinking-skill-${objectiveCounter}">
                            <option value="Know">Know</option>
                            <option value="Comprehend">Comprehend</option>
                            <option value="Apply">Apply</option>
                            <option value="Analyze">Analyze</option>
                            <option value="Evaluate">Evaluate</option>
                            <option value="Create">Create</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="action-verb-${objectiveCounter}" class="form-label text-light">Action Verb</label>
                        <select class="form-control bg-dark text-light action-verb w-100" name="objectives[${objectiveCounter}][action_verb]" id="action-verb-${objectiveCounter}">
                            <option value="" disabled selected>Select Action Verb</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="objective-description-${objectiveCounter}" class="form-label text-light">Objective Description</label>
                        <input type="text" class="form-control bg-dark text-light objective-description w-100" name="objectives[${objectiveCounter}][description]" id="objective-description-${objectiveCounter}" placeholder="Enter description" />
                    </div>
                </div>
            </div>`;
        $('#lesson-objectives-container').append(newObjective);
        populateActionVerbs('Know', $('#action-verb-' + objectiveCounter));
        objectiveCounter++;
    });

    // Add Activity
    let activityCounter = $('#lesson-activities-container .activity-item').length;
    $('#addLessonActivityBtn').on('click', function () {
        let uniqueId = 'act-' + activityCounter;
        let newActivity = `
            <div class="activity-item mb-3 p-2" data-activity-id="${uniqueId}">
                <div class="text-dividerr d-flex justify-content-between align-items-center mb-3">
                    <span class="divider-textt">Activity</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 32%;">
                    <button type="button" class="remove-btn btn-sm btn-danger delete-activity">Remove</button>
                </div>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="activity-type-${activityCounter}" class="form-label text-light">Activity Type</label>
                        <select class="form-control bg-dark text-light activity-type w-100" name="activities[${activityCounter}][type]" id="activity-type-${activityCounter}">
                            <option value="Quiz">Quiz</option>
                            <option value="Discussion">Discussion</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Presentation">Presentation</option>
                            <option value="Group Work">Group Work</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="activity-title-${activityCounter}" class="form-label text-light">Title</label>
                        <input type="text" class="form-control bg-dark text-light activity-title w-100" name="activities[${activityCounter}][title]" id="activity-title-${activityCounter}" placeholder="Enter activity title" />
                    </div>
                    <div class="col-md-12">
                        <label for="activity-instructions-${activityCounter}" class="form-label text-light">Instructions</label>
                        <textarea class="form-control bg-dark text-light activity-instructions w-100" name="activities[${activityCounter}][instructions]" id="activity-instructions-${activityCounter}" placeholder="Enter instructions" rows="4"></textarea>
                    </div>
                </div>
            </div>`;
        $('#lesson-activities-container').append(newActivity);
        activityCounter++;
    });

    // Delete Objective
    $(document).on('click', '.delete-objective', function () {
        $(this).closest('.objective-item').remove();
    });

    // Delete Activity
    $(document).on('click', '.delete-activity', function () {
        $(this).closest('.activity-item').remove();
    });
});