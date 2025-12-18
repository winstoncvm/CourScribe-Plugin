/**
 * Path: courscribe/assets/js/courscribe/courses/create.js
 */
jQuery(document).ready(function ($) {
    let objectiveIndex = 1; // Start at 1 since 0 is already in the form

    // Initialize SortableJS for objectives
    // $('.course-list-objectives-container').each(function () {
    //     new Sortable(this, {
    //         animation: 150,
    //         handle: '.objective-item',
    //         onEnd: function (evt) {
    //             // Reindex objectives after sorting
    //             $('.objective-item').each(function (index) {
    //                 $(this).find('.thinking-skill').attr('name', `objectives[${index}][thinking_skill]`);
    //                 $(this).find('.action-verb').attr('name', `objectives[${index}][action_verb]`);
    //                 $(this).find('.objective-description').attr('name', `objectives[${index}][description]`);
    //             });
    //         }
    //     });
    // });

    // Populate action verbs based on thinking skill
    $(document).on('change', '.thinking-skill', function () {
        let thinkingSkill = $(this).val();
        let actionVerbField = $(this).closest('.objective-item').find('.action-verb');

        let actionVerbs = {
            'Know': ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
            'Comprehend': ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
            'Apply': ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
            'Analyze': ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
            'Evaluate': ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
            'Create': ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
        };

        actionVerbField.empty().append('<option value="" disabled selected>Select Action Verb</option>');

        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(function (verb) {
                actionVerbField.append(new Option(verb, verb));
            });
        }
    });

    // Trigger change for initial objective
    $('.thinking-skill').trigger('change');

    // Add new objective
    $('#saveObjectiveBtn').on('click', function () {
        let newObjective = `
            <div class="text-dividerr d-flex justify-content-between align-items-center mb-3">
                <span class="divider-textt">Objective Formation</span>
                <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 32%;">
                <button type="button" class="remove-btn btn-sm delete-objective">Remove</button>
            </div>
            <div class="objective-item mb-3 p-2">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="thinking-skill-${objectiveIndex}" class="form-label text-light">Thinking Skill</label>
                        <select class="form-control bg-dark text-light thinking-skill w-100" name="objectives[${objectiveIndex}][thinking_skill]" id="thinking-skill-${objectiveIndex}">
                            <option value="Know">Know</option>
                            <option value="Comprehend">Comprehend</option>
                            <option value="Apply">Apply</option>
                            <option value="Analyze">Analyze</option>
                            <option value="Evaluate">Evaluate</option>
                            <option value="Create">Create</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="action-verb-${objectiveIndex}" class="form-label text-light">Action Verb</label>
                        <select class="form-control bg-dark text-light action-verb w-100" name="objectives[${objectiveIndex}][action_verb]" id="action-verb-${objectiveIndex}">
                            <option value="" disabled selected>Select Action Verb</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="objective-description-${objectiveIndex}" class="form-label text-light">Objective Description</label>
                        <input type="text" class="form-control bg-dark text-light objective-description w-100" name="objectives[${objectiveIndex}][description]" id="objective-description-${objectiveIndex}" placeholder="Enter description" />
                    </div>
                </div>
            </div>
        `;
        $('#new-course-objectives-container').append(newObjective);
        $(`#thinking-skill-${objectiveIndex}`).trigger('change');
        objectiveIndex++;
    });

    // Delete objective
    $('#new-course-objectives-container').on('click', '.delete-objective', function () {
        if ($('#new-course-objectives-container .objective-item').length > 1) {
            if (confirm('Are you sure you want to delete this objective?')) {
                $(this).closest('.objective-item').prev('.text-dividerr').remove();
                $(this).closest('.objective-item').remove();
                // Reindex remaining objectives
                $('.objective-item').each(function (index) {
                    $(this).find('.thinking-skill').attr('name', `objectives[${index}][thinking_skill]`).attr('id', `thinking-skill-${index}`);
                    $(this).find('.action-verb').attr('name', `objectives[${index}][action_verb]`).attr('id', `action-verb-${index}`);
                    $(this).find('.objective-description').attr('name', `objectives[${index}][description]`).attr('id', `objective-description-${index}`);
                });
                objectiveIndex = $('.objective-item').length;
            }
        } else {
            alert('At least one objective is required.');
        }
    });

    // Form submission (optional: add client-side validation)
    $('#courscribe-course-form').on('submit', function (e) {
        let errors = [];
        if (!$('#course-name').val().trim()) {
            errors.push('Course name is required.');
        }
        if (!$('#course-goal').val().trim()) {
            errors.push('Course goal is required.');
        }
        if (!$('#level-of-learning').val()) {
            errors.push('Level of learning is required.');
        }
        let hasValidObjective = false;
        $('.objective-item').each(function () {
            let thinkingSkill = $(this).find('.thinking-skill').val();
            let actionVerb = $(this).find('.action-verb').val();
            let description = $(this).find('.objective-description').val().trim();
            if (thinkingSkill && actionVerb && description) {
                hasValidObjective = true;
            }
        });
        if (!hasValidObjective) {
            errors.push('At least one complete objective is required.');
        }

        if (errors.length > 0) {
            e.preventDefault();
            let errorHtml = '<div class="courscribe-error"><p>Please correct the following errors:</p><ul>';
            errors.forEach(function (error) {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul></div>';
            $('.modal-body').prepend(errorHtml);
            setTimeout(function () {
                $('.courscribe-error').remove();
            }, 5000);
        }
    });
});