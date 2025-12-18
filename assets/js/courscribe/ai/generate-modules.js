// assets/js/courscribe/ai/generate-modules.js
jQuery(document).ready(function ($) {
    $('#courscribe-generate-modules').on('click', function (e) {
        e.preventDefault();
        const courseId = $('#generateModulesOffcanvas').data('course-id'); 
        const tone = $('#module-tone').val();
        const audience = $('#module-audience').val();
        const moduleCount = $('#module-count').val();
        const instructions = $('#module-instructions').val();

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'courscribe_generate_modules',
                course_id: courseId,
                tone: tone,
                audience: audience,
                module_count: moduleCount,
                instructions: instructions
            },
            beforeSend: function () {
                $('#courscribe-generate-modules').prop('disabled', true).text('Generating...');
            },
            success: function (response) {
                $('#courscribe-generate-modules').prop('disabled', false).text('Generate');
                if (response.success) {
                    displayGeneratedModules(response.data.modules);
                    $('#courscribe-generated-modules').show();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                $('#courscribe-generate-modules').prop('disabled', false).text('Generate');
                alert('An error occurred while generating modules: ' + error);
            }
        });
    });

    function displayGeneratedModules(modules) {
        const $modulesList = $('#courscribe-modules-list');
        $modulesList.empty();

        modules.forEach((module, index) => {
            const moduleHtml = `
                <div class="courscribe-ai-suggestions-card-container mb-3" data-module-index="${index}">
                    <div class="courscribe-ai-suggestions-title-card d-flex justify-content-between align-items-center">
                        <p>${module.title}</p>
                        <button class="courscribe-close-button btn-close courscribe-delete-module" data-module-index="${index}" aria-label="Close">
                            <span class="X"></span>
                            <span class="Y"></span>
                            <div class="courscribe-close-close">Close</div>
                        </button>
                    </div>
                    <div class="courscribe-ai-suggestions-card-content">
                        <p class="title">Goal:</p>
                        <p class="description">${module.goal}</p>
                        <div class="card-separate">
                            <div class="separate"></div>
                            <p>Objectives</p>
                            <div class="separate"></div>
                        </div>
                        <div class="card-list-features">
                            ${module.objectives.map(obj => `
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
                            <input type="checkbox" class="form-check-input courscribe-module-checkbox" id="module-${index}" data-module-index="${index}">
                            <label class="form-check-label" for="module-${index}">Select this module</label>
                        </div>
                    </div>
                </div>`;
            $modulesList.append(moduleHtml);
        });
    }

    $('#courscribe-select-all-modules').on('click', function () {
        const $checkboxes = $('.courscribe-module-checkbox');
        const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        $checkboxes.prop('checked', !allChecked);
    });

    $(document).on('click', '.courscribe-delete-module', function () {
        const index = $(this).data('module-index');
        $(`[data-module-index="${index}"]`).remove();
        if ($('#courscribe-modules-list').children().length === 0) {
            $('#courscribe-generated-modules').hide();
        }
    });

    $('#courscribe-add-selected-modules').on('click', function () {
        const selectedModules = [];
        $('.courscribe-module-checkbox:checked').each(function () {
            const index = $(this).data('module-index');
            const $moduleCard = $(`[data-module-index="${index}"]`);
            const objectives = $moduleCard.find('.card-list-features .option p').map(function () {
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

            selectedModules.push({
                module_name: $moduleCard.find('.courscribe-ai-suggestions-title-card p').text(),
                module_goal: $moduleCard.find('.description').text(),
                objectives: objectives
            });
        });

        if (selectedModules.length === 0) {
            alert('Please select at least one module to add.');
            return;
        }
        const courseId = $('#generateModulesOffcanvas').data('course-id');
        const curriculumId = $('#generateModulesOffcanvas').data('curriculum-id');
        let savedCount = 0;
        const totalToSave = selectedModules.length;
        selectedModules.forEach((module) => {
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'save_ai_new_module',
                    course_id: courseId,
                    module_name: module.module_name,
                    module_goal: module.module_goal,
                    objectives: module.objectives,
                    curriculum_id: curriculumId,
                    nonce: courscribeAjax.nonce
                },
                success: function (response) {
                    savedCount++;
                    if (response.success) {
                        console.log(`Module ${module.module_name} saved successfully.`);
                    } else {
                        alert(`Error saving module ${module.module_name}: ${response.data}`);
                    }
                    if (savedCount === totalToSave) {
                        alert('All selected modules have been added successfully!');
                        $('#offcanvasRight').offcanvas('hide');
                        location.reload(); // Refresh to show new modules
                    }
                },
                error: function (xhr, status, error) {
                    savedCount++;
                    console.log('Error details:', {
                        status: xhr.status,
                        responseText: xhr.responseText,
                        error: error
                    });

                    alert(`An error occurred while saving module ${module.module_name}: ${error}`);
                    if (savedCount === totalToSave) {
                        $('#offcanvasRight').offcanvas('hide');
                         location.reload();
                    }
                }
            });
        });
    });

    $('#courscribe-regenerate-modules').on('click', function () {
        $('#courscribe-generate-modules-form').submit();
    });
    $('#generateModulesOffcanvas').on('show.bs.offcanvas', function (event) {
        var button = $(event.relatedTarget);
        var courseId = button.data('course-id');
        var curriculumId = button.data('curriculum-id');
        // Set them on the offcanvas
        $(this).attr('data-course-id', courseId).attr('data-curriculum-id', curriculumId);
        
    });
});