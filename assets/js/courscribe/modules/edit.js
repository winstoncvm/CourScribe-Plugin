/**
 * Path: courscribe/assets/js/courscribe/modules/edit.js
 */
jQuery(document).ready(function ($) {
    let actionVerbs = {
        'Know': ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
        'Comprehend': ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
        'Apply': ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
        'Analyze': ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
        'Evaluate': ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
        'Create': ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
    };

    function populateActionVerbs(thinkingSkill, actionVerbField, currentActionVerb = null) {
        actionVerbField.empty();
        actionVerbField.append(new Option("Select Action Verb", "", true, false));
        if (actionVerbs[thinkingSkill]) {
            actionVerbs[thinkingSkill].forEach(function (verb) {
                let option = new Option(verb, verb, false, verb === currentActionVerb);
                actionVerbField.append(option);
            });
        }
    }

    // Populate action verbs for existing objectives
    $('.objective-item-modules-list').each(function () {
        let objectiveItem = $(this);
        let objectiveId = objectiveItem.data('objective-id');
        let thinkingSkillField = objectiveItem.find('.thinking-skill-objective-modules');
        let actionVerbField = objectiveItem.find('.action-verb-objective-modules');
        let currentThinkingSkill = thinkingSkillField.val();
        let currentActionVerb = actionVerbField.data('current-action-verb');
        if (currentThinkingSkill) {
            populateActionVerbs(currentThinkingSkill, actionVerbField, currentActionVerb);
        }
    });

    // Update action verbs when thinking skill changes
    $(document).on('change', '.thinking-skill-objective-modules', function () {
        let objectiveId = $(this).data('objective-id');
        let thinkingSkill = $(this).val();
        let actionVerbField = $('#module-current-action-verb-' + objectiveId);
        populateActionVerbs(thinkingSkill, actionVerbField);
    });

    // Debug AJAX URL and nonce
    // console.log('AJAX URL:', courscribeAjax.ajaxurl);
    // console.log('Nonce:', courscribeAjax.nonce);

    // Save module changes
    $(document).on('click', '.save-module', function () {
        let moduleId = $(this).data('module-id');
        let courseId = $(this).data('course-id');
        let moduleName = $('#module-name-' + moduleId).val();
        let moduleGoal = $('#module-goal-' + moduleId).val();
        let formData = new FormData();

        // Validate required fields
        if (!moduleId || !courseId) {
            alertbox.render({
                alertIcon: 'error',
                title: 'Cannot save module',
                message: 'Module ID or Course ID is missing.',
                btnTitle: 'Ok',
                themeColor: '#000000',
                btnColor: '#665442',
                border: true
            });
            return;
        }
        if (!moduleName || !moduleGoal) {
            alertbox.render({
                alertIcon: 'error',
                title: 'Cannot save module',
                message: 'Module name and goal are required.',
                btnTitle: 'Ok',
                themeColor: '#000000',
                btnColor: '#665442',
                border: true
            });
            return;
        }
        // Function to collect objectives
        function collectObjectives(moduleId) {
            let objectives = [];
            let objectiveItems = $('#module-objectives-list-' + moduleId + ' .objective-item-modules-list');
            console.log('Objective items found:', objectiveItems);
            objectiveItems.each(function () {
                let objectiveId = $(this).data('objective-id');
                console.log('Processing objective ID:', objectiveId);
                let thinkingSkill = $('#module-thinking-skill-' + objectiveId).val() || '';
                let actionVerb = $('#module-current-action-verb-' + objectiveId).val() || '';
                // Target the description input within the speech-input-wrapper
                let description = $(this).find('.speech-input-wrapper .objective-description').val() || '';
                console.log('Objective data:', { thinkingSkill, actionVerb, description });
                if (thinkingSkill || actionVerb || description) {
                    objectives.push({
                        thinking_skill: thinkingSkill,
                        action_verb: actionVerb,
                        description: description
                    });
                }
            });
            return objectives;
        }

        // Collect Objectives
        let objectives = collectObjectives(moduleId);
        console.log('Collected objectives:', objectives);

        // Validate objectives
        if (objectives.length === 0) {
            alertbox.render({
                alertIcon: 'error',
                title: 'Cannot save module',
                message: 'At least one objective is required.',
                btnTitle: 'Ok',
                themeColor: '#000000',
                btnColor: '#665442',
                border: true
            });
            return;
        }

        // Collect Methods
        let methods = [];
        let methodItems = $('#module-methods-list-' + moduleId + ' .methods-container');
        console.log('Method items found:', methodItems.length);
        methodItems.each(function () {
            let index = $(this).data('method-index');
            let methodType = $(this).find('.method-type').val() || '';
            let title = $(this).find('.method-title').val().trim() || '';
            let location = $(this).find('.method-location').val().trim() || '';
            console.log('Method data:', { methodType, title, location });
            if (methodType || title || location) {
                methods.push({
                    method_type: methodType,
                    title: title,
                    location: location
                });
            }
        });

        // Collect Materials
        let materials = [];
        let materialItems = $('#module-materials-list-' + moduleId + ' .material-item');
        console.log('Material items found:', materialItems.length);
        materialItems.each(function () {
            let index = $(this).data('material-index');
            let materialType = $(this).find('.material-type').val() || '';
            let title = $(this).find('.material-title').val().trim() || '';
            let link = $(this).find('.material-link').val().trim() || '';
            console.log('Material data:', { materialType, title, link });
            if (materialType || title || link) {
                materials.push({
                    material_type: materialType,
                    title: title,
                    link: link
                });
            }
        });

        // Collect Media
        let mediaInput = $('#media-' + moduleId)[0];
        if (mediaInput && mediaInput.files.length > 0) {
            for (let i = 0; i < mediaInput.files.length; i++) {
                formData.append('media[]', mediaInput.files[i]);
            }
        }

        // Log data
        console.log('Saving module with data:', {
            moduleId,
            courseId,
            moduleName,
            moduleGoal,
            objectives,
            methods,
            materials,
            nonce: courscribeAjax.nonce
        });

        formData.append('action', 'save_module_changes');
        formData.append('module_id', moduleId);
        formData.append('course_id', courseId);
        formData.append('module_name', moduleName);
        formData.append('module_goal', moduleGoal);
        formData.append('objectives', JSON.stringify(objectives));
        formData.append('methods', JSON.stringify(methods));
        formData.append('materials', JSON.stringify(materials));
        formData.append('nonce', courscribeAjax.nonce);

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log('Server response:', response);
                if (response.success) {
                    alertbox.render({
                        alertIcon: 'success',
                        title: 'Module updated!',
                        message: 'Module saved successfully!',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                    location.reload();
                } else {
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Module not updated!',
                        message: 'Failed to save changes: ' + (response.data?.message || 'Unknown error'),
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                }
            },
            error: function (xhr) {
                console.error('AJAX error:', xhr.responseText);
                alertbox.render({
                    alertIcon: 'error',
                    title: 'Module not updated!',
                    message: 'An error occurred: ' + (xhr.responseJSON?.data?.message || 'Please try again'),
                    btnTitle: 'Ok',
                    themeColor: '#2a2a2b',
                    btnColor: '#F8923E',
                    border: true
                });
            }
        });
    });

    // Remove objective
    $(document).on('click', '.delete-objective', function () {
        if (confirm('Are you sure you want to remove this objective?')) {
            $(this).closest('.objective-item-modules-list').remove();
        }
    });

    // Add new objective
    $(document).on('click', '#addModuleListObjectiveBtn', function () {
        let moduleId = $(this).data('module-id');
        let objectivesContainer = $('#module-objectives-list-' + moduleId);
        let uniqueId = 'newobj-' + Math.random().toString(36).substr(2, 9);

        let newObjective = `
            <li class="objective-item-modules-list mb-3" data-objective-id="${uniqueId}">
                <div class="text-dividerr-modules-list">
                    <span class="divider-textt">Objective Formation</span>
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 56%;">
                    <button type="button" class="remove-btn btn-sm delete-objective">Cancel</button>
                </div>
                <div class="objective-row mb-2">
                    <label for="thinking-skill-${uniqueId}">Select the Thinking Skill</label>
                    <select id="module-thinking-skill-${uniqueId}" data-objective-id="${uniqueId}" class="form-control bg-dark text-light thinking-skill-objective-modules" style="min-width: 80px; max-width: 140px; padding-inline: 0.5rem;">
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
                    <img src="${window.location.origin}/wp-content/uploads/2024/12/Rectangle-1501.png" alt="divider" style="width: 58%; margin-right: 30px;">
                </div>
                <div class="objective-row mb-2">
                    <label for="action-verb-${uniqueId}">Action Verb</label>
                    <select id="module-current-action-verb-${uniqueId}" data-objective-id="${uniqueId}" class="form-control bg-dark text-light action-verb-objective-modules" style="min-width: 80px; max-width: 140px; padding-inline: 0.5rem;">
                        <option value="" disabled selected>Select Action Verb</option>
                    </select>
                </div>
                <div class="objective-row mb-2">
                    <label for="objective-description-${uniqueId}">Objective Description</label>
                    <input type="text" id="module-objective-description-${uniqueId}" class="form-control bg-dark text-light objective-description" placeholder="Enter Object/noun with accuracy..." style="flex: 1;" />
                </div>
            </li>
        `;

        objectivesContainer.append(newObjective);
        populateActionVerbs('Know', $('#module-current-action-verb-' + uniqueId));
    });

    // Delete module
    $(document).on('click', '.delete-module', function () {
        if (confirm('Are you sure you want to delete this module?')) {
            let moduleId = $(this).data('module-id');
            let courseId = $(this).data('course-id');
            $(this).closest('.module-item').remove();
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'handle_delete_module',
                    module_id: moduleId,
                    course_id: courseId,
                    nonce: courscribeAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alertbox.render({
                            alertIcon: 'success',
                            title: 'Module deleted!',
                            message: 'Module deleted successfully',
                            btnTitle: 'Ok',
                            themeColor: '#000000',
                            btnColor: '#665442',
                            border: true
                        });
                    } else {
                        alertbox.render({
                            alertIcon: 'error',
                            title: 'Module not deleted!',
                            message: 'Failed to delete module: ' + (response.data?.message || 'Unknown error'),
                            btnTitle: 'Ok',
                            themeColor: '#000000',
                            btnColor: '#665442',
                            border: true
                        });
                    }
                },
                error: function (xhr) {
                    console.error('Delete module error:', xhr.responseText);
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Module not deleted!',
                        message: 'An error occurred: ' + (xhr.responseJSON?.data?.message || 'Please try again'),
                        btnTitle: 'Ok',
                        themeColor: '#2a2a2b',
                        btnColor: '#F8923E',
                        border: true
                    });
                }
            });
        }
    });

    // Add Method
    $(document).on('click', '.add-method', function () {
        let moduleId = $(this).data('module-id');
        let methodsContainer = $('#module-methods-list-' + moduleId);
        let newIndex = methodsContainer.find('.methods-container').length;
        let newMethod = `
            <li class="moodule-conteiner-body methods-container mb-2" data-method-index="${newIndex}">
                <div class="module-body">
                    <h5>Method ${newIndex + 1}</h5>
                    <div class="method-group mb-4">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label for="method-type-${newIndex}" class="form-label">Method Type</label>
                                <select class="form-control bg-dark text-light method-type" data-method-index="${newIndex}">
                                    <option value="Live">Live</option>
                                    <option value="Webinar">Webinar</option>
                                    <option value="Online">Online</option>
                                    <option value="Self-Paced">Self-Paced</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="method-title-${newIndex}" class="form-label">Title</label>
                                <input type="text" class="form-control bg-dark text-light method-title" data-method-index="${newIndex}" placeholder="Enter method title" />
                            </div>
                            <div class="col-md-12">
                                <label for="method-location-${newIndex}" class="form-label">Location/Link</label>
                                <input type="text" class="form-control bg-dark text-light method-location" data-method-index="${newIndex}" placeholder="Enter location or link" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="delete-aside delete-method">
                    <button class="module-delete-button delete-method" type="button" data-method-index="${newIndex}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#E4B26F">
                            <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                        </svg>
                    </button>
                </div>
            </li>`;
        methodsContainer.append(newMethod);
    });

    // Remove Method
    $(document).on('click', '.delete-method', function () {
        $(this).closest('.methods-container').remove();
    });

    // Add Material
    $(document).on('click', '.add-material', function () {
        let moduleId = $(this).data('module-id');
        let materialsContainer = $('#module-materials-list-' + moduleId);
        let newIndex = materialsContainer.find('.material-item').length;
        let newMaterial = `
            <li class="moodule-conteiner-body material-item mb-2" data-material-index="${newIndex}">
                <div class="module-body">
                    <h5>Material ${newIndex + 1}</h5>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Material Type</label>
                            <select class="form-control bg-dark text-light material-type" data-material-index="${newIndex}">
                                <option value="Document">Document</option>
                                <option value="Video">Video</option>
                                <option value="Audio">Audio</option>
                                <option value="Link">Link</option>
                                <option value="Physical">Physical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control bg-dark text-light material-title" data-material-index="${newIndex}" placeholder="Enter material title" />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Link</label>
                            <input type="url" class="form-control bg-dark text-light material-link" data-material-index="${newIndex}" placeholder="Enter material link" />
                        </div>
                    </div>
                </div>
                <div class="delete-aside delete-material">
                    <button class="module-delete-button delete-material" type="button" data-material-index="${newIndex}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#E4B26F">
                            <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                        </svg>
                    </button>
                </div>
            </li>`;
        materialsContainer.append(newMaterial);
    });

    // Remove Material
    $(document).on('click', '.delete-material', function () {
        $(this).closest('.material-item').remove();
    });

    // Handle File Upload Previews
    function handleFileUpload(files, previewContainer) {
        Array.from(files).forEach(file => {
            let reader = new FileReader();
            reader.onload = function (e) {
                let filePreview = $('<div class="col-md-3 media-item"></div>');
                if (file.type.startsWith('image/')) {
                    filePreview.append(`<img src="${e.target.result}" class="media-preview img-fluid" alt="Media Image" />`);
                } else if (file.type.startsWith('video/')) {
                    filePreview.append(`<video controls class="media-preview"><source src="${e.target.result}" type="${file.type}">Your browser does not support the video tag.</video>`);
                } else if (file.type === 'application/pdf') {
                    filePreview.append(`<embed src="${e.target.result}" type="application/pdf" class="media-preview" />`);
                } else {
                    filePreview.append(`<div class="file-icon"><i class="fas fa-file"></i> ${file.name}</div>`);
                }
                filePreview.append('<button type="button" class="btn btn-sm btn-danger delete-media">Remove</button>');
                previewContainer.append(filePreview);
            };
            reader.readAsDataURL(file);
        });
    }

    // Media Upload Handler
    $(document).on('change', '[id^="media-"]', function (e) {
        let moduleId = this.id.replace('media-', '');
        let files = e.target.files;
        let previewContainer = $('#media-preview-grid-' + moduleId + ' .row');
        handleFileUpload(files, previewContainer);
    });

    // Remove Media
    $(document).on('click', '.delete-media', function () {
        $(this).closest('.media-item').remove();
    });
});