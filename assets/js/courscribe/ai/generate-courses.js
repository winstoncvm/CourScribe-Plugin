// assets/js/courscribe/ai/generate-courses.js
jQuery(document).ready(function ($) {
    $('#courscribe-generate-courses').on('click', function (e) {
        e.preventDefault();
        const tone = $('#course-tone').val();
        const audience = $('#course-audience').val();
        const courseCount = $('#course-count').val();
        const instructions = $('#course-instructions').val();
        const curriculumId = $('#generateCoursesOffcanvas').data('curriculum-id');

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'courscribe_generate_courses',
                curriculum_id: curriculumId,
                tone: tone,
                audience: audience,
                course_count: courseCount,
                instructions: instructions
            },
            beforeSend: function () {
                $('#courscribe-generate-courses').prop('disabled', true).text('Generating...');
            },
            success: function (response) {
                $('#courscribe-generate-courses').prop('disabled', false).text('Generate');
                if (response.success) {
                    displayGeneratedCourses(response.data.courses);
                    $('#courscribe-generated-courses').show();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                $('#courscribe-generate-courses').prop('disabled', false).text('Generate');
                alert('An error occurred while generating courses: ' + error);
            }
        });
    });

    function displayGeneratedCourses(courses) {
        const $coursesList = $('#courscribe-courses-list');
        $coursesList.empty();

        courses.forEach((course, index) => {
            const courseHtml = `
                <div class="courscribe-ai-suggestions-card-container mb-3" data-course-index="${index}">
                    <div class="courscribe-ai-suggestions-title-card d-flex justify-content-between align-items-center">
                        <p>${course.title}</p>
                        <button class="courscribe-close-button btn-close courscribe-delete-course" data-course-index="${index}" aria-label="Close">
                            <span class="X"></span>
                            <span class="Y"></span>
                            <div class="courscribe-close-close">Close</div>
                        </button>
                    </div>
                    <div class="courscribe-ai-suggestions-card-content">
                        <p class="title">Goal:</p>
                        <p class="description">${course.goal}</p>
                        <div class="card-separate">
                            <div class="separate"></div>
                            <p>Objectives</p>
                            <div class="separate"></div>
                        </div>
                        <div class="card-list-features">
                            ${course.objectives.map(obj => `
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
                            <input type="checkbox" class="form-check-input courscribe-course-checkbox" id="course-${index}" data-course-index="${index}">
                            <label class="form-check-label" for="course-${index}">Select this course</label>
                        </div>
                    </div>
                </div>`;
            $coursesList.append(courseHtml);
        });
    }

    $('#courscribe-select-all-courses').on('click', function () {
        const $checkboxes = $('.courscribe-course-checkbox');
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        $checkboxes.prop('checked', !allChecked);
    });

    $(document).on('click', '.courscribe-delete-course', function () {
        const index = $(this).data('course-index');
        $(`[data-course-index="${index}"]`).remove();
        if ($('#courscribe-courses-list').children().length === 0) {
            $('#courscribe-generated-courses').hide();
        }
    });

    $('#courscribe-add-selected-courses').on('click', function () {
        const selectedCourses = [];
        $('.courscribe-course-checkbox:checked').each(function () {
            const index = $(this).data('course-index');
            const $courseCard = $(`[data-course-index="${index}"]`);
            const objectives = $courseCard.find('.card-list-features .option p').map(function () {
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

            selectedCourses.push({
                course_name: $courseCard.find('.courscribe-ai-suggestions-title-card p').text(),
                course_goal: $courseCard.find('.description').text(),
                objectives: objectives
            });
        });

        if (selectedCourses.length === 0) {
            alert('Please select at least one course to add.');
            return;
        }

        const curriculumId = $('#generateCoursesOffcanvas').data('curriculum-id');
        let savedCount = 0;
        const totalToSave = selectedCourses.length;


        selectedCourses.forEach((course) => {


            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_new_course',
                    course_name: course.course_name,
                    course_goal: course.course_goal,
                    level_of_learning: 'Beginner',
                    objectives: course.objectives,
                    curriculum_id: curriculumId,
                    nonce: courscribeAjax.nonce
                },
                success: function (response) {
                    savedCount++;
                    if (response.success) {
                        console.log(`course ${course.course_name} saved successfully.`);
                    } else {
                        alert(`Error saving course ${course.course_name}: ${JSON.stringify(response.data) || 'Unknown error'}`);
                        console.log(response.data);
                    }
                    if (savedCount === totalToSave) {
                        alert('All selected courses have been added successfully!');
                        $('#offcanvasRight').offcanvas('hide');
                        location.reload(); // Refresh to show new courses
                    }
                },
                error: function (xhr, status, error) {
                    savedCount++;
                    console.log('Error details:', {
                        status: xhr.status,
                        responseText: xhr.responseText,
                        error: error
                    });

                    alert(`An error occurred while saving course ${course.course_name}: ${error}`);
                    if (savedCount === totalToSave) {
                        $('#offcanvasRight').offcanvas('hide');
                        location.reload();
                    }
                }
            });
        });
    });

    $('#courscribe-regenerate-courses').on('click', function () {
        $('#courscribe-generate-courses-form').submit();
    });
    $('#generateCoursesOffcanvas').on('show.bs.offcanvas', function (event) {
        var button = $(event.relatedTarget);
        var curriculumId = button.data('curriculum-id');
        // Set them on the offcanvas
        $(this).attr('data-curriculum-id', curriculumId);
    });
});