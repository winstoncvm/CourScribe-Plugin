jQuery(document).ready(function ($) {
    $('#courscribe-generate-lessons-form').on('submit', function (e) {
        e.preventDefault();

        const moduleId = $('#offcanvasLessons').data('module-id');
        const courseId = $('#offcanvasLessons').data('course-id');
        const tone = $('#lesson-tone').val();
        const audience = $('#lesson-audience').val();
        const lessonCount = $('#lesson-count').val();
        const instructions = $('#lesson-instructions').val();

        console.log(moduleId, courseId, tone, audience, lessonCount, instructions)

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'courscribe_generate_lessons',
                course_id: courseId,
                module_id: moduleId,
                tone: tone,
                audience: audience,
                lesson_count: lessonCount,
                instructions: instructions
            },
            beforeSend: function () {
                $('#courscribe-generate-lessons').prop('disabled', true).text('Generating...');
            },
            success: function (response) {
                $('#courscribe-generate-lessons').prop('disabled', false).text('Generate');
                if (response.success) {
                    displayGeneratedLessons(response.data.lessons);
                    $('#courscribe-generated-lessons').show();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                $('#courscribe-generate-lessons').prop('disabled', false).text('Generate');
                alert('An error occurred while generating lessons: ' + error);
            }
        });
    });

    function displayGeneratedLessons(lessons) {
        const $lessonsList = $('#courscribe-lessons-list');
        $lessonsList.empty();

        lessons.forEach((lesson, index) => {
            const lessonHtml = `
                <div class="courscribe-ai-suggestions-card-container mb-3" data-lesson-index="${index}">
                    <div class="courscribe-ai-suggestions-title-card d-flex justify-content-between align-items-center">
                        <p>${lesson.title}</p>
                        <button class="courscribe-delete-lesson btn btn-sm btn-danger" data-lesson-index="${index}">
                            Delete
                        </button>
                    </div>
                    <div class="courscribe-ai-suggestions-card-content">
                        <p class="title">Goal:</p>
                        <p class="description">${lesson.goal}</p>
                        <div class="card-separate">
                            <div class="separate"></div>
                            <p>Objectives</p>
                            <div class="separate"></div>
                        </div>
                        <div class="card-list-features">
                            ${lesson.objectives.map(obj => `
                                <div class="option">
                                    <svg viewBox="0 0 24 24" height="14" width="14" xmlns="http://www.w3.org/2000/svg">
                                        <g stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" fill="none">
                                            <rect rx="4" y="3" x="3" height="18" width="18"></rect>
                                            <path d="m9 12l2.25 2L15 10"></path>
                                        </g>
                                    </svg>
                                    <p>${obj}</p>
                                </div>
                            `).join('')}
                        </div>
                        <div class="form-check mt-2">
                        Select This Lesson
                            <div class="checkbox-wrapper">
                            <input class="courscribe-lesson-checkbox" type="checkbox" id="lesson-${index}" data-lesson-index="${index}">
                            <label for="lesson-${index}">
                                <div class="tick_mark"></div>
                            </label>
                            </div>
                           
                        </div>
                    </div>
                </div>`;
            $lessonsList.append(lessonHtml);
        });
    }

    $('#courscribe-select-all-lessons').on('click', function () {
        const $checkboxes = $('.courscribe-lesson-checkbox');
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        $checkboxes.prop('checked', !allChecked);
    });

    $(document).on('click', '.courscribe-delete-lesson', function () {
        const index = $(this).data('lesson-index');
        $(`[data-lesson-index="${index}"]`).remove();
        if ($('#courscribe-lessons-list').children().length === 0) {
            $('#courscribe-generated-lessons').hide();
        }
    });

    $('#courscribe-add-selected-lessons').on('click', function () {
        const selectedLessons = [];
        $('.courscribe-lesson-checkbox:checked').each(function () {
            const index = $(this).data('lesson-index');
            const $lessonCard = $(`[data-lesson-index="${index}"]`);
            const objectives = $lessonCard.find('.card-list-features .option p').map(function () {
                const objectiveText = $(this).text();
                const parts = objectiveText.split(' to ');
                const thinkingSkill = parts[0].charAt(0).toUpperCase() + parts[0].slice(1).toLowerCase();
                const actionVerbAndDescription = parts[1].split(' ', 2);
                const actionVerb = actionVerbAndDescription[0];
                const description = objectiveText.split(' to ')[1].replace(actionVerb + ' ', '');
                return {
                    thinking_skill: thinkingSkill,
                    action_verb: actionVerb,
                    description: description
                };
            }).get();

            selectedLessons.push({
                lesson_name: $lessonCard.find('.courscribe-ai-suggestions-title-card p').text(),
                lesson_goal: $lessonCard.find('.description').text(),
                objectives: objectives
            });
        });

        if (selectedLessons.length === 0) {
            alert('Please select at least one lesson to add.');
            return;
        }

        const moduleId = $('#offcanvasLessons').data('module-id');
        let savedCount = 0;
        const totalToSave = selectedLessons.length;

        selectedLessons.forEach((lesson) => {
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_new_lesson',
                    module_id: moduleId,
                    lesson_name: lesson.lesson_name,
                    lesson_goal: lesson.lesson_goal,
                    objectives: lesson.objectives,
                    course_id: $('#offcanvasLessons').data('course-id')

                },
                success: function (response) {
                    savedCount++;
                    if (response.success) {
                        console.log(`Lesson ${lesson.lesson_name} saved successfully.`);
                    } else {
                        alert(`Error saving lesson ${lesson.lesson_name}: ${response.data}`);
                    }
                    if (savedCount === totalToSave) {
                        alert('All selected lessons have been added successfully!');
                        $('#offcanvasLessons').offcanvas('hide');
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    savedCount++;
                    alert(`An error occurred while saving lesson ${lesson.lesson_name}: ${error}`);
                    if (savedCount === totalToSave) {
                        $('#offcanvasLessons').offcanvas('hide');
                        location.reload();
                    }
                }
            });
        });
    });

    $('#courscribe-regenerate-lessons').on('click', function () {
        $('#courscribe-generate-lessons-form').submit();
    });
    $('#offcanvasLessons').on('show.bs.offcanvas', function (e) {
        const button = $(e.relatedTarget); // The button that triggered the offcanvas
        const courseId = button.data('course-id');
        const moduleId = button.data('module-id');
    
        // Set them on the offcanvas
        $(this).attr('data-course-id', courseId).attr('data-module-id', moduleId);
    });
});