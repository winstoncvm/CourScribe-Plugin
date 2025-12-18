jQuery(document).ready(function ($) {
    let previousSuggestions = [];

    // Utility function to sanitize IDs
    function sanitizeId(id) {
        return id.replace(/[^a-zA-Z0-9-_]/g, '_');
    }

    // Clear modal content when it opens and initialize loader
    $('#inputAiSuggestionsModal').on('show.bs.modal', function () {
        $('#suggestionsContainer').empty();
        $('#ai-suggestions-results').empty();
        $('#chat_bot').val('');
        $('#courscribe-loader').addClass('d-none');
    });

    $(document).on('click', '.ai-suggest-button', function (e) {
        e.preventDefault();
        let fieldId = $(this).data('field-id');
        fieldId = sanitizeId(fieldId);
        const parts = fieldId.split('-');
        const fieldType = parts[0];
        const elementId = parts[parts.length - 1];
        let title = '';
        let prompt = '';

        const buttonData = $(this).data();

        try {
            if (fieldId.includes('course-goal')) {
                const courseId = buttonData.courseId;
                const courseName = buttonData.courseName;
                const curriculumId = buttonData.curriculumId;
                const curriculumTitle = buttonData.curriculumTitle;
                const curriculumTopic = buttonData.curriculumTopic;
                const curriculumGoal = buttonData.curriculumGoal;
                const curriculumNotes = buttonData.curriculumNotes;
                title = `${courseName} Course Goal`;
                prompt = `As an instructional designer, please suggest 3 well-formed course goals based on this information:

                    Curriculum Title: ${curriculumTitle}
                    Curriculum Topic: ${curriculumTopic}
                    Curriculum Goal: ${curriculumGoal}
                    Curriculum Notes: ${curriculumNotes}

                    Course Name: ${courseName}

                    The goals should be short, specific, measurable, and aligned with the curriculum objectives. Format each suggestion with a number (1-5) followed by the goal, no explanations, just the short goal only`;
            } else if (fieldId.includes('module-goal')) {
                const moduleId = buttonData.moduleId;
                const moduleName = buttonData.moduleName;
                const courseName = buttonData.courseName;
                const courseGoal = buttonData.courseGoal;
                title = `${moduleName} Module Goal`;
                prompt = `As an instructional designer, please suggest 3 well-formed module goals based on this information:

                    Course Name: ${courseName}
                    Course Goal: ${courseGoal}

                    Module Name: ${moduleName}

                    The goals should be specific, measurable, and aligned with the course goal and module objectives. Format each suggestion with a number (1-5) followed by the goal, no explanations, just the short goal only`;
            } else if (fieldId.includes('lesson-goal')) {
                const lessonId = buttonData.lessonId;
                const lessonName = buttonData.lessonName;
                const moduleName = buttonData.moduleName;
                const moduleGoal = buttonData.moduleGoal;
                const courseName = buttonData.courseName;
                const courseGoal = buttonData.courseGoal;
                title = `${lessonName} Lesson Goal`;
                prompt = `As an instructional designer, please suggest 3 well-formed lesson goals based on this information:

                    Course Name: ${courseName}
                    Course Goal: ${courseGoal}

                    Module Name: ${moduleName}
                    Module Goal: ${moduleGoal}

                    Lesson Name: ${lessonName}

                    The goals should be specific, measurable, and aligned with the module goal and lesson objectives. Format each suggestion with a number (1-5) followed by the goal, no explanations, just the short goal only`;
            } else if (fieldId.includes('teaching-point')) {
                const lessonId = buttonData.lessonId;
                const lessonName = buttonData.lessonName;
                const moduleName = buttonData.moduleName;
                const moduleGoal = buttonData.moduleGoal;
                const courseName = buttonData.courseName;
                const courseGoal = buttonData.courseGoal;
                title = `${lessonName} Lesson Teaching Points`;
                prompt = `As an instructional designer, please suggest 3 well-formed lesson teaching points based on this information:

                    Course Name: ${courseName}
                    Course Goal: ${courseGoal}

                    Module Name: ${moduleName}
                    Module Goal: ${moduleGoal}

                    Lesson Name: ${lessonName}

                    The goals should be specific, measurable, and aligned with the module goal and lesson objectives. Format each suggestion with a number (1-5) followed by the goal, no explanations, just the short goal only`;
            } else if (fieldId.includes('objective-description')) {
                const parts = fieldId.split('-');
                const parentType = parts[0];
                const parentId = parts[2];
                const index = parts[3];
                let hierarchy = '';

                if (buttonData.courseId) {
                    title = `${buttonData.courseName} - Objective ${parseInt(index) + 1}`;
                    hierarchy = `Course: ${buttonData.courseName}\nCourse Goal: ${buttonData.courseGoal}`;
                } else if (buttonData.moduleId) {
                    title = `${buttonData.moduleName} - Objective ${parseInt(index) + 1}`;
                    hierarchy = `Course: ${buttonData.courseName}\nModule: ${buttonData.moduleName}\nModule Goal: ${buttonData.moduleGoal}`;
                } else if (buttonData.lessonId) {
                    title = `${buttonData.lessonName} - Objective ${parseInt(index) + 1}`;
                    hierarchy = `Course: ${buttonData.courseName}\nModule: ${buttonData.moduleName}\nLesson: ${buttonData.lessonName}\nLesson Goal: ${buttonData.lessonGoal}`;
                }

                prompt = `As an instructional designer, please suggest 3 well-formed learning objectives based on this information:

                    ${hierarchy}

                    Thinking Skill: ${buttonData.thinkingSkill}
                    Action Verb: ${buttonData.actionVerb}

                    The objectives should be specific, measurable, and aligned with the parent's goals.
                    Format each suggestion with a number (1-5) followed by the objective.
                    Each objective should begin with the action verb and incorporate the thinking skill.

                    Example format:
                    1. [Action Verb] [content] to demonstrate [Thinking Skill]
                    2. [Action Verb] [content] that shows [Thinking Skill]`;
            } else if (fieldId.includes('module-activity')) {
                title = `${buttonData.moduleName} - Activity Suggestion`;
                prompt = `As an instructional designer, please suggest 3 learning activities for a module with the following details:

                    Module Name: ${buttonData.moduleName}
                    Module Goal: ${buttonData.moduleGoal}
                    Course Name: ${buttonData.courseName}
                    Course Goal: ${buttonData.courseGoal}

                    The activities should be engaging and contribute to achieving the module's learning objectives. Format each suggestion with a number (1-5) followed by the activity, focusing on active learning strategies.`;
            } else {
                title = 'AI Suggestions';
                prompt = 'Provide 5 general suggestions for an unspecified educational field.';
            }

            $('#modal-title-target').text(title);
            $('#inputAiSuggestionsModal').data('field-id', fieldId);
            $('#inputAiSuggestionsModal').data('original-prompt', prompt);
            $('#inputAiSuggestionsModal').modal('show');
            getSuggestions(prompt);
        } catch (error) {
            console.error('Error processing AI suggest button click:', error);
            $('#ai-suggestions-results').html(
                `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: Invalid field ID or data. Please try again.</div>`
            );
        }
    });

    $(document).on('click', '#courscribe-ai-suggest-send', function (e) {
        e.preventDefault();
        const fieldId = $('#inputAiSuggestionsModal').data('field-id');
        const originalPrompt = $('#inputAiSuggestionsModal').data('original-prompt');
        const additionalInstructions = $('#chat_bot').val().trim();
        let newPrompt = originalPrompt;

        if (additionalInstructions) {
            newPrompt += `\n\nAdditional Instructions: ${additionalInstructions}`;
        }

        getSuggestions(newPrompt);
    });

    function getSuggestions(prompt) {
        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_ai_suggestions',
                prompt: prompt
            },
            beforeSend: function () {
                $('#courscribe-loader').removeClass('d-none');
                $('#suggestionsContainer').empty();
                $('#ai-suggestions-results').empty();
            },
            success: function (response) {
                if (response.success) {
                    previousSuggestions = response.data.suggestions;
                    displaySuggestions(previousSuggestions);
                } else {
                    $('#ai-suggestions-results').html(
                        `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">${response.data.message || 'Failed to get suggestions'}</div>`
                    );
                }
            },
            error: function (xhr) {
                $('#ai-suggestions-results').html(
                    `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: ${xhr.responseJSON?.data?.message || 'Request failed'}</div>`
                );
            },
            complete: function () {
                $('#courscribe-loader').addClass('d-none');
            }
        });
    }

    function displaySuggestions(suggestions) {
        const $results = $('#ai-suggestions-results');
        $results.empty();
        const fieldId = $('#inputAiSuggestionsModal').data('field-id');

        if (suggestions.length === 0) {
            $results.append(`
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                    No suggestions were generated. Please try again with more specific context.
                </div>
            `);
            return;
        }

        // Build the radio select container
        let radioHtml = `
            <div class="w-100 p-i-2">
            <div class="courscribe-ai-select-radio-container" style="--total-radio: ${suggestions.length}">
        `;
        suggestions.forEach((suggestion, index) => {
            const cleanSuggestion = suggestion.replace(/^[\d\s\.\-]+/, '').trim();
            const isChecked = index === 0 ? 'checked' : '';
            radioHtml += `
                <input ${isChecked} id="radio-${index}" name="radio" type="radio" value="${index}" />
                <label for="radio-${index}">${cleanSuggestion}</label>
            `;
        });
        radioHtml += `
            <div class="courscribe-ai-select-glider-container">
                <div class="glider"></div>
            </div>
        </div>
        <button data-bs-dismiss="modal" class="continue-application insert-ai-suggestion mt-2" style="margin-right: 40px;">
            <div>
                <div class="pencil"></div>
                <div class="folder">
                    <div class="top">
                        <svg viewBox="0 0 24 27">
                            <path d="M1,0 L23,0 C23.5522847,-1.01453063e-16 24,0.44771525 24,1 L24,8.17157288 C24,8.70200585 23.7892863,9.21071368 23.4142136,9.58578644 L20.5857864,12.4142136 C20.2107137,12.7892863 20,13.2979941 20,13.8284271 L20,26 C20,26.5522847 19.5522847,27 19,27 L1,27 C0.44771525,27 6.76353751e-17,26.5522847 0,26 L0,1 C-6.76353751e-17,0.44771525 0.44771525,1.01453063e-16 1,0 Z"></path>
                        </svg>
                    </div>
                    <div class="paper"></div>
                </div>
            </div>
            Insert Selected
        </button>
        </div>
        `;

        $results.append(radioHtml);

        // Handle "Insert Selected" button click
        $('.insert-ai-suggestion').on('click', function () {
            const selectedIndex = $('input[name="radio"]:checked').val();
            if (selectedIndex !== undefined) {
                const suggestion = suggestions[selectedIndex];
                const sanitizedFieldId = sanitizeId(fieldId);
                try {
                    const $input = $(`#${sanitizedFieldId}`);
                    if ($input.length) {
                        $input.val(suggestion.replace(/^[\d\s\.\-]+/, '').trim());
                        $('#inputAiSuggestionsModal').modal('hide');
                    } else {
                        console.error(`Input field with ID ${sanitizedFieldId} not found`);
                        $('#ai-suggestions-results').html(
                            `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: Input field not found.</div>`
                        );
                    }
                } catch (error) {
                    console.error('Error setting suggestion value:', error);
                    $('#ai-suggestions-results').html(
                        `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: Failed to apply suggestion.</div>`
                    );
                }
            } else {
                $('#ai-suggestions-results').html(
                    `<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">Please select a suggestion.</div>`
                );
            }
        });
    }
});