/**
 * CourScribe Modules Premium Enhanced JavaScript
 * Comprehensive module management with idempotency, premium UI, and enhanced features
 */

(function($) {
    'use strict';

    // Global configuration and state
    let CourScribeModules = {
        config: {},
        state: {
            pendingRequests: {},
            autoSaveTimers: {},
            currentModule: null,
            dragSortInstance: null
        },
        
        // Idempotency utilities
        utils: {
            generateRequestHash: function(data) {
                return btoa(JSON.stringify(data) + Date.now()).replace(/[^a-zA-Z0-9]/g, '');
            },
            
            isDuplicateRequest: function(requestKey) {
                return CourScribeModules.state.pendingRequests.hasOwnProperty(requestKey);
            },
            
            setPendingRequest: function(requestKey, promise) {
                CourScribeModules.state.pendingRequests[requestKey] = promise;
                promise.always(() => {
                    delete CourScribeModules.state.pendingRequests[requestKey];
                });
                return promise;
            },
            
            showToast: function(type, message, options = {}) {
                const toastClass = type === 'success' ? 'cs-toast-success' : 'cs-toast-error';
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                const duration = options.duration || 4000;
                
                const $toast = $(`
                    <div class="cs-toast ${toastClass}">
                        <div class="cs-toast-content">
                            <i class="fas ${icon} cs-toast-icon"></i>
                            <span class="cs-toast-message">${message}</span>
                        </div>
                        <button class="cs-toast-close" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
                
                $('body').append($toast);  
                
                // Animate in
                setTimeout(() => $toast.addClass('show'), 10);
                
                // Auto remove
                setTimeout(() => {
                    $toast.removeClass('show');
                    setTimeout(() => $toast.remove(), 300);
                }, duration);
                
                // Manual close
                $toast.find('.cs-toast-close').on('click', () => {
                    $toast.removeClass('show');
                    setTimeout(() => $toast.remove(), 300);
                });
            },
            
            debounce: function(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }
    };

    /**
     * Initialize all module functionality
     */
    CourScribeModules.init = function(config) {
        this.config = config;
        
        // Initialize all components
        this.initDragAndDrop();
        this.initModuleActions();
        this.initObjectiveManagement();
        this.initTabSystem();
        this.initAutoSave();
        this.initMethodsAndMaterials();
        this.initMediaUpload();
        this.initModalHandlers();
        this.initViewToggle();
        this.initLogViewer();
        this.initAISuggestions();
        // this.initFieldHelpers();
        
        console.log('CourScribe Modules Premium Enhanced initialized');
    };

    /**
     * Initialize drag and drop functionality
     */
    CourScribeModules.initDragAndDrop = function() {
        const moduleContainer = document.getElementById(`cs-modules-container-${this.config.courseId}`);
        if (moduleContainer && window.Sortable) {
            this.state.dragSortInstance = Sortable.create(moduleContainer, {
                handle: '.cs-drag-handle',
                animation: 200,
                ghostClass: 'cs-sortable-ghost',
                chosenClass: 'cs-sortable-chosen',
                dragClass: 'cs-sortable-drag',
                
                onStart: (evt) => {
                    $(evt.item).addClass('cs-dragging');
                    $('.cs-module-item').addClass('cs-sorting-mode');
                },
                
                onEnd: (evt) => {
                    $(evt.item).removeClass('cs-dragging');
                    $('.cs-module-item').removeClass('cs-sorting-mode');
                    
                    // Save new order
                    this.saveModuleOrder();
                }
            });
        }
    };

    /**
     * Save module order with idempotency
     */
    CourScribeModules.saveModuleOrder = function() {
        const orderData = [];
        $(`.cs-modules-container .cs-module-item`).each(function(index) {
            orderData.push({
                id: $(this).data('module-id'),
                order: index + 1
            });
        });

        const requestData = {
            action: 'courscribe_save_module_order',
            course_id: this.config.courseId,
            order_data: orderData,
            nonce: this.config.moduleNonce,
            timestamp: Math.floor(Date.now() / 1000)
        };

        const requestKey = `order_${this.config.courseId}`;
        
        if (this.utils.isDuplicateRequest(requestKey)) {
            return this.state.pendingRequests[requestKey];
        }

        const promise = $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: (response) => {
                if (response.success) {
                    this.utils.showToast('success', 'Module order saved successfully');
                } else {
                    this.utils.showToast('error', 'Failed to save module order');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Error saving module order');
            }
        });

        return this.utils.setPendingRequest(requestKey, promise);
    };

    /**
     * Initialize module action buttons
     */
    CourScribeModules.initModuleActions = function() {
        // Save module button
        $(document).on('click', '.cs-save-module', (e) => {
            const $btn = $(e.currentTarget);
            const moduleId = $btn.data('module-id');
            this.saveModule(moduleId);
        });

        // Archive module button
        $(document).on('click', '.cs-archive-module-btn', (e) => {
            const $btn = $(e.currentTarget);
            const moduleId = $btn.data('module-id');
            const moduleTitle = $btn.data('module-title');
            this.showArchiveModal(moduleId, moduleTitle);
        });

        // Delete module button
        $(document).on('click', '.cs-delete-module-btn', (e) => {
            const $btn = $(e.currentTarget);
            const moduleId = $btn.data('module-id');
            const moduleTitle = $btn.data('module-title');
            this.showDeleteModal(moduleId, moduleTitle);
        });

        // Restore module button
        $(document).on('click', '.cs-restore-module-btn', (e) => {
            const $btn = $(e.currentTarget);
            const moduleId = $btn.data('module-id');
            this.restoreModule(moduleId);
        });
    };

    /**
     * Save module with enhanced data collection
     */
    CourScribeModules.saveModule = function(moduleId) {
        const $module = $(`.cs-module-item[data-module-id="${moduleId}"]`);
        const $saveBtn = $module.find('.cs-save-module');
        const $spinner = $saveBtn.find('.cs-save-spinner');
        
        // Collect all module data
        const moduleData = {
            module_id: moduleId,
            course_id: this.config.courseId,
            module_name: $module.find('[name*="module_name"]').val(),
            module_goal: $module.find('[name*="module_goal"]').val(),
            objectives: this.collectObjectives(moduleId),
            methods: this.collectMethods(moduleId),
            materials: this.collectMaterials(moduleId),
            timestamp: Math.floor(Date.now() / 1000),
            nonce: this.config.moduleNonce
        };

        const requestKey = `save_module_${moduleId}`;
        
        if (this.utils.isDuplicateRequest(requestKey)) {
            return this.state.pendingRequests[requestKey];
        }

        // Show loading state
        $saveBtn.prop('disabled', true);
        $spinner.removeClass('d-none');

        const promise = $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'save_module_changes',
                ...moduleData
            },
            success: (response) => {
                if (response.success) {
                    this.utils.showToast('success', 'Module saved successfully');
                    $module.removeClass('cs-unsaved-changes');
                } else {
                    this.utils.showToast('error', response.data?.message || 'Failed to save module');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Error saving module');
            },
            complete: () => {
                $saveBtn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });

        return this.utils.setPendingRequest(requestKey, promise);
    };

    /**
     * Initialize auto-save functionality
     */
    CourScribeModules.initAutoSave = function() {
        const autoSaveFields = '.cs-module-field, .cs-field-textarea, .cs-objective-description';
        
        $(document).on('input', autoSaveFields, (e) => {
            const $field = $(e.target);
            const moduleId = $field.data('module-id') || $field.closest('.cs-module-item').data('module-id');
            
            if (!moduleId) return;

            // Mark module as having unsaved changes
            $(`.cs-module-item[data-module-id="${moduleId}"]`).addClass('cs-unsaved-changes');
            
            // Clear existing timer
            if (this.state.autoSaveTimers[moduleId]) {
                clearTimeout(this.state.autoSaveTimers[moduleId]);
            }
            
            // Set new auto-save timer
            this.state.autoSaveTimers[moduleId] = setTimeout(() => {
                this.autoSaveField($field, moduleId);
            }, 2000); // Auto-save after 2 seconds of inactivity
        });
    };

    /**
     * Auto-save individual field
     */
    CourScribeModules.autoSaveField = function($field, moduleId) {
        const fieldType = $field.data('field-type') || 'unknown';
        const fieldValue = $field.val();
        
        if (!fieldValue || fieldValue.trim() === '') {
            return; // Don't auto-save empty fields
        }

        const requestData = {
            action: 'update_module_field',
            module_id: moduleId,
            field_type: fieldType,
            field_value: fieldValue,
            timestamp: Math.floor(Date.now() / 1000),
            nonce: this.config.moduleNonce
        };

        const requestKey = `autosave_${moduleId}_${fieldType}`;
        
        if (this.utils.isDuplicateRequest(requestKey)) {
            return this.state.pendingRequests[requestKey];
        }

        const promise = $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: (response) => {
                if (response.success) {
                    $field.addClass('cs-auto-saved');
                    setTimeout(() => $field.removeClass('cs-auto-saved'), 1000);
                }
            },
            error: () => {
                console.log('Auto-save failed for field:', fieldType);
            }
        });

        return this.utils.setPendingRequest(requestKey, promise);
    };

    /**
     * Initialize objective management
     */
    CourScribeModules.initObjectiveManagement = function() {
        // Add objective
        $(document).on('click', '.cs-add-objective', (e) => {
            console.log('Add Objective button clicked');
            const moduleId = $(e.target).data('module-id');
            this.addObjective(moduleId);
        });

        // Remove objective
        $(document).on('click', '.cs-remove-objective', (e) => {
            const objectiveId = $(e.target).data('objective-id');
            this.removeObjective(objectiveId);
        });

        // Update thinking skill dropdown
        $(document).on('change', '.cs-thinking-skill', (e) => {
            const $select = $(e.target);
            const skill = $select.val();
            const objectiveId = $select.data('objective-id');
            this.updateActionVerbs(objectiveId, skill);
        });

        // Auto-save objectives
        $(document).on('change input', '.cs-objective-description, .cs-thinking-skill, .cs-action-verb', 
            this.utils.debounce((e) => {
                const $field = $(e.target);
                const moduleId = $field.data('module-id');
                const objectiveId = $field.data('objective-id');
                this.saveObjective(moduleId, objectiveId);
            }, 1000)
        );
    };

    /**
     * Add new objective
     */
    CourScribeModules.addObjective = function(moduleId) {
        const $objectivesList = $(`#cs-objectives-list-${moduleId}`);
        const objectiveIndex = $objectivesList.find('.cs-objective-item').length;
        const objectiveId = `objective-${moduleId}-${Date.now()}`;

        const objectiveHtml = this.generateObjectiveHTML(moduleId, objectiveId, objectiveIndex + 1);
        $objectivesList.append(objectiveHtml);

        // Focus on the new objective's description field
        $(`[data-objective-id="${objectiveId}"] .cs-objective-description`).focus();

        this.utils.showToast('success', 'New objective added');
    };

    /**
     * Save objective data (FIXED - was missing)
     */
    CourScribeModules.saveObjective = function(moduleId, objectiveId) {
        console.log('💾 Saving objective:', {moduleId, objectiveId});

        const $objectiveItem = $(`.cs-objective-item[data-objective-id="${objectiveId}"]`);
        if (!$objectiveItem.length) {
            console.error('Objective item not found:', objectiveId);
            return;
        }

        const objectiveData = {
            thinking_skill: $objectiveItem.find('.cs-thinking-skill').val() || '',
            action_verb: $objectiveItem.find('.cs-action-verb').val() || '',
            description: $objectiveItem.find('.cs-objective-description').val() || ''
        };

        // Validate - at least description should be present
        if (!objectiveData.description.trim()) {
            console.log('⚠️ Objective description empty, skipping save');
            return;
        }

        console.log('📊 Objective data to save:', objectiveData);

        // Collect all objectives for this module
        const allObjectives = this.collectObjectives(moduleId);

        // Generate request key for idempotency
        const requestKey = `save-objective-${moduleId}-${objectiveId}`;

        // Check for duplicate request
        if (this.utils.isDuplicateRequest(requestKey)) {
            console.log('⏭️ Duplicate request detected, skipping');
            return;
        }

        // Make AJAX request
        const ajaxPromise = $.ajax({
            url: CourscribeAjax?.ajaxurl || ajaxurl,
            type: 'POST',
            data: {
                action: 'courscribe_save_module_objective',
                module_id: moduleId,
                objective_id: objectiveId,
                objective_data: objectiveData,
                all_objectives: allObjectives,
                nonce: CourscribeAjax?.module_generation_nonce || ''
            },
            success: (response) => {
                if (response.success) {
                    console.log('✅ Objective saved successfully');
                } else {
                    console.error('❌ Save failed:', response.data?.message || 'Unknown error');
                }
            },
            error: (xhr, status, error) => {
                console.error('💥 AJAX error saving objective:', error);
            }
        });

        // Track pending request
        this.utils.setPendingRequest(requestKey, ajaxPromise);
    };

    /**
     * Generate HTML for new objective
     */
    CourScribeModules.generateObjectiveHTML = function(moduleId, objectiveId, objectiveNumber) {
        return `
            <div class="cs-objective-item animate-slide-in" data-objective-id="${objectiveId}" data-module-id="${moduleId}">
                <div class="cs-objective-header">
                    <span class="cs-objective-title">Objective ${objectiveNumber}:</span>
                    <div class="cs-objective-actions">
                        <button type="button" class="cs-btn cs-btn-danger cs-remove-objective" data-objective-id="${objectiveId}">
                            <i class="fas fa-trash"></i> 
                        </button>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="cs-field-label">Thinking Skill</label>
                        <select class="cs-field-input cs-thinking-skill" data-objective-id="${objectiveId}" data-module-id="${moduleId}">
                            <option value="">Select thinking skill...</option>
                            <option value="Know">Know</option>
                            <option value="Comprehend">Comprehend</option>
                            <option value="Apply">Apply</option>
                            <option value="Analyze">Analyze</option>
                            <option value="Evaluate">Evaluate</option>
                            <option value="Create">Create</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="cs-field-label">Action Verb</label>
                        <select class="cs-field-input cs-action-verb" data-objective-id="${objectiveId}" data-module-id="${moduleId}">
                            <option value="">First select thinking skill</option>
                        </select>
                    </div>
                </div>

                <div class="cs-field-group">
                    <label class="cs-field-label">By the end of this Module they will: Objective ${objectiveNumber}</label>
                    <div class="d-flex gap-2">
                        <textarea class="cs-field-textarea cs-objective-description" 
                                  data-objective-id="${objectiveId}"
                                  data-module-id="${moduleId}"
                                  placeholder="Enter objective description"
                                  rows="2"></textarea>
                        <button type="button" class="cs-btn-icon cs-ai-suggest-btn" 
                                data-field-id="cs-objective-description-${objectiveId}"
                                data-module-id="${moduleId}"
                                title="Get AI suggestions">
                            <i class="fas fa-magic"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    };

    /**
     * Update action verbs based on thinking skill
     */
    CourScribeModules.updateActionVerbs = function(objectiveId, thinkingSkill) {
        const actionVerbMap = {
            'Know': ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
            'Comprehend': ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
            'Apply': ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
            'Analyze': ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
            'Evaluate': ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
            'Create': ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
        };

        const $actionVerbSelect = $(`.cs-action-verb[data-objective-id="${objectiveId}"]`);
        const verbs = actionVerbMap[thinkingSkill] || [];
        
        $actionVerbSelect.empty();
        if (verbs.length > 0) {
            $actionVerbSelect.append('<option value="">Select action verb...</option>');
            verbs.forEach(verb => {
                $actionVerbSelect.append(`<option value="${verb}">${verb}</option>`);
            });
        } else {
            $actionVerbSelect.append('<option value="">Select thinking skill first</option>');
        }
    };

    /**
     * Collect objectives data from DOM
     */
    CourScribeModules.collectObjectives = function(moduleId) {
        const objectives = [];
        $(`.cs-objective-item[data-module-id="${moduleId}"]`).each(function() {
            const $objective = $(this);
            objectives.push({
                thinking_skill: $objective.find('.cs-thinking-skill').val(),
                action_verb: $objective.find('.cs-action-verb').val(),
                description: $objective.find('.cs-objective-description').val()
            });
        });
        return JSON.stringify(objectives);
    };

    /**
     * Initialize methods and materials management
     */
    CourScribeModules.initMethodsAndMaterials = function() {
        // Add method
        $(document).on('click', '.cs-add-method', (e) => {
            const moduleId = $(e.target).data('module-id');
            this.addMethod(moduleId);
        });

        // Add material
        $(document).on('click', '.cs-add-material', (e) => {
            const moduleId = $(e.target).data('module-id');
            this.addMaterial(moduleId);
        });

        // Remove method/material
        $(document).on('click', '.cs-remove-method, .cs-remove-material', (e) => {
            const $item = $(e.target).closest('.cs-method-item, .cs-material-item');
            $item.addClass('animate-slide-out');
            setTimeout(() => $item.remove(), 300);
        });

        // Auto-save methods and materials
        $(document).on('change input', '.cs-method-type, .cs-method-title, .cs-method-location, .cs-material-type, .cs-material-title, .cs-material-link',
            this.utils.debounce((e) => {
                const $field = $(e.target);
                const moduleId = $field.data('module-id');
                this.saveAdditionalData(moduleId);
            }, 1000)
        );
    };

    /**
     * Collect methods data
     */
    CourScribeModules.collectMethods = function(moduleId) {
        const methods = [];
        $(`.cs-method-item[data-module-id="${moduleId}"]`).each(function() {
            const $method = $(this);
            methods.push({
                method_type: $method.find('.cs-method-type').val(),
                title: $method.find('.cs-method-title').val(),
                location: $method.find('.cs-method-location').val()
            });
        });
        return JSON.stringify(methods);
    };

    /**
     * Collect materials data
     */
    CourScribeModules.collectMaterials = function(moduleId) {
        const materials = [];
        $(`.cs-material-item[data-module-id="${moduleId}"]`).each(function() {
            const $material = $(this);
            materials.push({
                material_type: $material.find('.cs-material-type').val(),
                title: $material.find('.cs-material-title').val(),
                link: $material.find('.cs-material-link').val()
            });
        });
        return JSON.stringify(materials);
    };

    /**
     * Initialize enhanced media upload
     */
    CourScribeModules.initMediaUpload = function() {
        // Dropzone click handlers
        $(document).on('click', '.cs-upload-dropzone', function() {
            const moduleId = $(this).data('module-id');
            $(`#cs-media-upload-${moduleId}, #cs-material-upload-${moduleId}`).click();
        });

        // Drag and drop functionality
        $(document).on({
            dragover: function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('cs-dragover');
            },
            dragleave: function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('cs-dragover');
            },
            drop: (e) => {
                e.preventDefault();
                e.stopPropagation();
                const $dropzone = $(e.currentTarget);
                $dropzone.removeClass('cs-dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                const moduleId = $dropzone.data('module-id');
                const uploadType = $dropzone.hasClass('premium-dropzone') ? 'media' : 'material';
                
                this.handleFileUpload(files, moduleId, uploadType);
            }
        }, '.cs-upload-dropzone');

        // File input change
        $(document).on('change', '[id^="cs-media-upload-"], [id^="cs-material-upload-"]', (e) => {
            const $input = $(e.target);
            const moduleId = $input.data('module-id');
            const uploadType = $input.attr('id').includes('media') ? 'media' : 'material';
            
            this.handleFileUpload(e.target.files, moduleId, uploadType);
        });

        // Delete media
        $(document).on('click', '.cs-media-delete-btn', (e) => {
            const $btn = $(e.target);
            const mediaUrl = $btn.data('media-url');
            const moduleId = $btn.data('module-id');
            
            if (confirm('Are you sure you want to delete this media file?')) {
                this.deleteMedia(moduleId, mediaUrl);
            }
        });
    };

    /**
     * Enhanced file upload with progress and validation
     */
    CourScribeModules.handleFileUpload = function(files, moduleId, uploadType = 'media') {
        if (!files || files.length === 0) return;

        const $dropzone = $(`#cs-${uploadType}-dropzone-${moduleId}`);
        const $progress = $(`#cs-${uploadType}-progress-${moduleId}`);
        
        // Validate files
        const maxSize = 10 * 1024 * 1024; // 10MB
        const validFiles = [];
        
        Array.from(files).forEach(file => {
            if (file.size > maxSize) {
                this.utils.showToast('error', `File "${file.name}" is too large (max 10MB)`);
                return;
            }
            validFiles.push(file);
        });

        if (validFiles.length === 0) return;

        // Prepare upload
        const formData = new FormData();
        formData.append('action', uploadType === 'media' ? 'courscribe_upload_module_media' : 'courscribe_upload_module_material');
        formData.append('module_id', moduleId);
        formData.append('nonce', this.config.moduleNonce);
        
        validFiles.forEach((file, index) => {
            formData.append(`${uploadType}[]`, file);
        });

        // Show upload state
        $dropzone.addClass('cs-uploading');
        $progress.addClass('active');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        $progress.find('.cs-progress-bar').css('width', percentComplete + '%');
                        $progress.find('.cs-progress-text').text(`${percentComplete}%`);
                    }
                }, false);
                return xhr;
            },
            success: (response) => {
                if (response.success) {
                    this.utils.showToast('success', `${validFiles.length} file(s) uploaded successfully`);
                    // Refresh the media grid or materials list
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.utils.showToast('error', response.data?.message || 'Upload failed');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Upload failed');
            },
            complete: () => {
                $dropzone.removeClass('cs-uploading');
                $progress.removeClass('active');
            }
        });
    };

    /**
     * Initialize modal handlers
     */
    CourScribeModules.initModalHandlers = function() {
        // Archive confirmation
        $(document).on('click', '.confirm-archive-btn', (e) => {
            const $btn = $(e.target);
            const moduleId = $btn.closest('.modal').find('#current-module-id').text() || 
                           $('.cs-archive-module-btn.active').data('module-id');
            
            if (moduleId) {
                this.performArchive(moduleId);
            }
        });

        // Delete confirmation
        $(document).on('click', '.confirm-delete-btn', (e) => {
            const $btn = $(e.target);
            const moduleId = $btn.closest('.modal').find('.cs-delete-module-id').val() || 
                           $('.cs-delete-module-btn.active').data('module-id');
            
            if (moduleId) {
                this.performDelete(moduleId);
            }
        });
    };

    /**
     * Show archive confirmation modal
     */
    CourScribeModules.showArchiveModal = function(moduleId, moduleTitle) {
        // Remove any existing modals
        $('#cs-archive-module-modal').remove();
        
        const modalHtml = `
            <div class="modal fade" id="cs-archive-module-modal" tabindex="-1" aria-labelledby="archiveModuleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content cs-modal-content">
                        <div class="modal-header cs-modal-header">
                            <h5 class="modal-title cs-modal-title" id="archiveModuleModalLabel">
                                <i class="fas fa-archive me-2"></i>Archive Module
                            </h5>
                            <button type="button" class="btn-close cs-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body cs-modal-body">
                            <div class="cs-modal-icon-container">
                                <div class="cs-modal-icon cs-warning-icon">
                                    <i class="fas fa-archive"></i>
                                </div>
                            </div>
                            <h6 class="cs-modal-question">Archive this module?</h6>
                            <p class="cs-modal-description">
                                Are you sure you want to archive "<strong>${moduleTitle}</strong>"? 
                                This will move the module to the archived section, but it can be restored later if needed.
                            </p>
                            <div class="cs-modal-note">
                                <i class="fas fa-info-circle"></i>
                                <span>Archived modules can be restored from the archived view.</span>
                            </div>
                            <input type="hidden" id="current-module-id" value="${moduleId}">
                        </div>
                        <div class="modal-footer cs-modal-footer">
                            <button type="button" class="cs-btn cs-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="cs-btn cs-btn-warning confirm-archive-btn">
                                <i class="fas fa-archive me-1"></i>Archive Module
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#cs-archive-module-modal').modal('show');
    };

    /**
     * Show delete confirmation modal
     */
    CourScribeModules.showDeleteModal = function(moduleId, moduleTitle) {
        // Remove any existing modals
        $('#cs-delete-module-modal').remove();
        
        const modalHtml = `
            <div class="modal fade" id="cs-delete-module-modal" tabindex="-1" aria-labelledby="deleteModuleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content cs-modal-content">
                        <div class="modal-header cs-modal-header cs-modal-header-danger">
                            <h5 class="modal-title cs-modal-title" id="deleteModuleModalLabel">
                                <i class="fas fa-trash me-2"></i>Delete Module
                            </h5>
                            <button type="button" class="btn-close cs-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body cs-modal-body">
                            <div class="cs-modal-icon-container">
                                <div class="cs-modal-icon cs-danger-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <h6 class="cs-modal-question">Permanently delete this module?</h6>
                            <p class="cs-modal-description">
                                Are you sure you want to permanently delete "<strong>${moduleTitle}</strong>"? 
                                This action cannot be undone and all module content will be lost forever.
                            </p>
                            <div class="cs-modal-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><strong>Warning:</strong> This action is permanent and cannot be reversed.</span>
                            </div>
                            <input type="hidden" class="cs-delete-module-id" value="${moduleId}">
                        </div>
                        <div class="modal-footer cs-modal-footer">
                            <button type="button" class="cs-btn cs-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="cs-btn cs-btn-danger confirm-delete-btn">
                                <i class="fas fa-trash me-1"></i>Delete Permanently
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#cs-delete-module-modal').modal('show');
    };

    /**
     * Initialize AI suggestions
     */
    CourScribeModules.initAISuggestions = function() {
        $(document).on('click', '.cs-ai-suggest-btn, .ai-suggest-button', (e) => {
            const $btn = $(e.target);
            const fieldId = $btn.data('field-id');
            const moduleId = $btn.data('module-id');
            
            this.showAISuggestions(fieldId, moduleId);
        });
    };

    /**
     * Show AI suggestions modal with context
     */
    CourScribeModules.showAISuggestions = function(fieldId, moduleId) {
        // Get context data
        const $module = $(`.cs-module-item[data-module-id="${moduleId}"]`);
        const moduleTitle = $module.find('[name*="module_name"]').val();
        const moduleGoal = $module.find('[name*="module_goal"]').val();
        
        // Create enhanced AI suggestions modal
        this.createAISuggestionsModal(fieldId, moduleId, moduleTitle, moduleGoal);
    };

    /**
     * Create and show enhanced AI suggestions modal
     */
    CourScribeModules.createAISuggestionsModal = function(fieldId, moduleId, moduleTitle, moduleGoal) {
        // Remove any existing modal
        $('#cs-ai-suggestions-modal').remove();
        
        const modalHtml = `
            <div class="modal fade" id="cs-ai-suggestions-modal" tabindex="-1" aria-labelledby="aiSuggestionsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content cs-modal-content">
                        <div class="modal-header cs-modal-header cs-ai-modal-header">
                            <div class="cs-ai-modal-title-group">
                                <div class="cs-ai-modal-icon">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title cs-modal-title" id="aiSuggestionsModalLabel">AI Content Suggestions</h5>
                                    <p class="cs-ai-modal-subtitle">Get contextual suggestions for your content</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close cs-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body cs-modal-body">
                            <!-- Context Display -->
                            <div class="cs-ai-context-section">
                                <h6 class="cs-ai-section-title">
                                    <i class="fas fa-info-circle me-2"></i>Context
                                </h6>
                                <div class="cs-ai-context-info">
                                    <div class="cs-context-item">
                                        <span class="cs-context-label">Module:</span>
                                        <span class="cs-context-value">${moduleTitle || 'Untitled Module'}</span>
                                    </div>
                                    <div class="cs-context-item">
                                        <span class="cs-context-label">Goal:</span>
                                        <span class="cs-context-value">${moduleGoal || 'No goal specified'}</span>
                                    </div>
                                    <div class="cs-context-item">
                                        <span class="cs-context-label">Field:</span>
                                        <span class="cs-context-value">${this.getFieldTypeLabel(fieldId)}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Custom Instructions -->
                            <div class="cs-ai-instructions-section">
                                <h6 class="cs-ai-section-title">
                                    <i class="fas fa-edit me-2"></i>Additional Instructions (Optional)
                                </h6>
                                <textarea class="cs-field-textarea" id="cs-ai-custom-instructions" 
                                          placeholder="Add specific requirements, tone, or style preferences..."></textarea>
                            </div>

                            <!-- Loading State -->
                            <div class="cs-ai-loading" id="cs-ai-loading" style="display: none;">
                                <div class="cs-ai-loading-content">
                                    <div class="cs-ai-spinner">
                                        <div class="cs-spinner"></div>
                                    </div>
                                    <p class="cs-ai-loading-text">Generating suggestions...</p>
                                </div>
                            </div>

                            <!-- Suggestions Results -->
                            <div class="cs-ai-suggestions-section" id="cs-ai-suggestions-section" style="display: none;">
                                <h6 class="cs-ai-section-title">
                                    <i class="fas fa-lightbulb me-2"></i>AI Suggestions
                                </h6>
                                <div class="cs-ai-suggestions-grid" id="cs-ai-suggestions-grid">
                                    <!-- Suggestions will be populated here -->
                                </div>
                            </div>

                            <!-- Error State -->
                            <div class="cs-ai-error" id="cs-ai-error" style="display: none;">
                                <div class="cs-ai-error-content">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <p class="cs-ai-error-message"></p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer cs-modal-footer">
                            <button type="button" class="cs-btn cs-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="cs-btn cs-btn-primary" id="cs-generate-suggestions" 
                                    data-field-id="${fieldId}" data-module-id="${moduleId}">
                                <i class="fas fa-magic me-1"></i>Generate Suggestions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#cs-ai-suggestions-modal').modal('show');
        
        // Bind events for this modal
        this.bindAISuggestionsEvents();
    };

    /**
     * Get user-friendly field type label
     */
    CourScribeModules.getFieldTypeLabel = function(fieldId) {
        if (fieldId.includes('objective-description')) return 'Learning Objective';
        if (fieldId.includes('method-title')) return 'Teaching Method Title';
        if (fieldId.includes('module-name')) return 'Module Name';
        if (fieldId.includes('module-goal')) return 'Module Goal';
        return 'Content Field';
    };

    /**
     * Bind AI suggestions modal events
     */
    CourScribeModules.bindAISuggestionsEvents = function() {
        // Generate suggestions button
        $('#cs-generate-suggestions').off('click').on('click', (e) => {
            const fieldId = $(e.target).data('field-id');
            const moduleId = $(e.target).data('module-id');
            const customInstructions = $('#cs-ai-custom-instructions').val();
            
            this.generateAISuggestions(fieldId, moduleId, customInstructions);
        });

        // Apply suggestion
        $(document).off('click', '.cs-suggestion-card').on('click', '.cs-suggestion-card', function() {
            $('.cs-suggestion-card').removeClass('selected');
            $(this).addClass('selected');
            
            const suggestion = $(this).find('.cs-suggestion-text').text();
            const fieldId = $('#cs-generate-suggestions').data('field-id');
            
            // Find the target field and set its value
            const $targetField = $(`[data-field-id="${fieldId}"], #${fieldId}`);
            if ($targetField.length) {
                $targetField.val(suggestion);
                CourScribeModules.utils.showToast('success', 'Suggestion applied successfully');
                $('#cs-ai-suggestions-modal').modal('hide');
            }
        });
    };

    /**
     * Generate AI suggestions using context
     */
    CourScribeModules.generateAISuggestions = function(fieldId, moduleId, customInstructions = '') {
        // Show loading state
        $('#cs-ai-loading').show();
        $('#cs-ai-suggestions-section, #cs-ai-error').hide();
        $('#cs-generate-suggestions').prop('disabled', true);

        // Gather context
        const $module = $(`.cs-module-item[data-module-id="${moduleId}"]`);
        const context = {
            moduleTitle: $module.find('[name*="module_name"]').val() || 'Untitled Module',
            moduleGoal: $module.find('[name*="module_goal"]').val() || '',
            fieldType: this.getFieldTypeLabel(fieldId),
            customInstructions: customInstructions
        };

        // Generate prompt based on field type
        let prompt = this.generatePromptForField(fieldId, context);

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'courscribe_get_ai_suggestions',
                module_id: moduleId,
                field_id: fieldId,
                prompt: prompt,
                context: context,
                nonce: this.config.moduleNonce
            },
            success: (response) => {
                if (response.success && response.data.suggestions) {
                    this.displayAISuggestions(response.data.suggestions);
                } else {
                    this.showAIError(response.data?.message || 'Failed to generate suggestions');
                }
            },
            error: (xhr) => {
                this.showAIError('Network error occurred. Please try again.');
                console.error('AI suggestions error:', xhr);
            },
            complete: () => {
                $('#cs-ai-loading').hide();
                $('#cs-generate-suggestions').prop('disabled', false);
            }
        });
    };

    /**
     * Generate prompt based on field type and context
     */
    CourScribeModules.generatePromptForField = function(fieldId, context) {
        const basePrompt = `You are an expert instructional designer. Generate 3-5 high-quality suggestions for the following:

Module: ${context.moduleTitle}
Module Goal: ${context.moduleGoal}
Field Type: ${context.fieldType}`;

        if (fieldId.includes('objective-description')) {
            return `${basePrompt}

Create specific, measurable learning objectives that align with the module goal. Each objective should:
- Use action verbs from Bloom's taxonomy
- Be specific and measurable
- Align with the module's learning outcomes
- Be appropriate for the target audience

Format: Just provide the objective text, no numbering or explanations.`;
        }
        
        if (fieldId.includes('method-title')) {
            return `${basePrompt}

Suggest engaging titles for teaching methods that would effectively deliver this module content. Consider:
- The module's learning objectives
- Modern educational approaches
- Engagement and effectiveness
- Clarity and appeal

Format: Just provide the method title, no explanations.`;
        }
        
        if (fieldId.includes('module-name')) {
            return `${basePrompt}

Suggest clear, descriptive module names that:
- Reflect the module's content and goals
- Are engaging and professional
- Are appropriate for the target audience
- Clearly indicate what learners will achieve

Format: Just provide the module name, no explanations.`;
        }

        if (fieldId.includes('module-goal')) {
            return `${basePrompt}

Create specific, achievable module goals that:
- Are aligned with learning outcomes
- Are measurable and time-bound
- Guide the module's content and activities
- Are appropriate for the target audience

Format: Just provide the goal statement, no explanations.`;
        }

        // Default prompt
        return `${basePrompt}

${context.customInstructions ? 'Additional Requirements: ' + context.customInstructions : ''}

Provide helpful, contextual suggestions for this educational content.`;
    };

    /**
     * Display AI suggestions in the modal
     */
    CourScribeModules.displayAISuggestions = function(suggestions) {
        const $grid = $('#cs-ai-suggestions-grid');
        $grid.empty();
        
        suggestions.forEach((suggestion, index) => {
            const cleanSuggestion = suggestion.replace(/^[\d\s\.\-]+/, '').trim();
            const $card = $(`
                <div class="cs-suggestion-card" data-index="${index}">
                    <div class="cs-suggestion-header">
                        <div class="cs-suggestion-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="cs-suggestion-number">Option ${index + 1}</div>
                    </div>
                    <div class="cs-suggestion-content">
                        <p class="cs-suggestion-text">${cleanSuggestion}</p>
                    </div>
                    <div class="cs-suggestion-actions">
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-primary cs-apply-suggestion">
                            <i class="fas fa-check me-1"></i>Use This
                        </button>
                    </div>
                </div>
            `);
            $grid.append($card);
        });
        
        $('#cs-ai-suggestions-section').show();
        
        // Bind apply suggestion events
        $('.cs-apply-suggestion').on('click', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.cs-suggestion-card');
            $card.click();
        });
    };

    /**
     * Show AI error message
     */
    CourScribeModules.showAIError = function(message) {
        $('#cs-ai-error .cs-ai-error-message').text(message);
        $('#cs-ai-error').show();
        $('#cs-ai-suggestions-section').hide();
    };

    /**
     * Initialize field helpers
     */
    // CourScribeModules.initFieldHelpers = function() {
    //     // Add helpful tooltips and guides
    //     $('.cs-field-input, .cs-field-textarea').each(function() {
    //         const $field = $(this);
    //         const fieldType = $field.data('field-type');
            
    //         // Add character counters for relevant fields
    //         if (fieldType === 'name' || fieldType === 'goal') {
    //             this.addCharacterCounter($field);
    //         }
    //     });
    // };

    /**
     * Add character counter to field
     */
    CourScribeModules.addCharacterCounter = function($field) {
        const maxLength = $field.attr('maxlength');
        if (!maxLength) return;
        
        const $counter = $(`<div class="cs-char-counter"><span class="cs-char-count">0</span>/${maxLength}</div>`);
        $field.after($counter);
        
        $field.on('input', function() {
            const currentLength = $(this).val().length;
            $counter.find('.cs-char-count').text(currentLength);
            
            // Color coding
            if (currentLength > maxLength * 0.9) {
                $counter.addClass('cs-warning');
            } else {
                $counter.removeClass('cs-warning');
            }
        });
    };

    /**
     * Initialize tab system
     */
    CourScribeModules.initTabSystem = function() {
        $(document).on('click', '.cs-tab-btn', function() {
            const $btn = $(this);
            const tabId = $btn.data('tab');
            const moduleId = $btn.data('module-id');
            
            // Update button states
            $(`.cs-tab-btn[data-module-id="${moduleId}"]`).removeClass('active');
            $btn.addClass('active');
            
            // Update content visibility
            $(`[id^="cs-tab-"][id*="${moduleId}"]`).removeClass('active');
            $(`#${tabId}`).addClass('active');
        });
    };

    /**
     * Initialize view toggle
     */
    CourScribeModules.initViewToggle = function() {
        $(document).on('click', '.cs-toggle-btn', function() {
            const $btn = $(this);
            const view = $btn.data('view');
            const courseId = $btn.data('course-id');
            
            // Update button states
            $(`.cs-toggle-btn[data-course-id="${courseId}"]`).removeClass('active');
            $btn.addClass('active');
            
            // Toggle visibility with animations
            if (view === 'active') {
                $(`#cs-modules-archived-${courseId}`).addClass('fade-out');
                setTimeout(() => {
                    $(`#cs-modules-archived-${courseId}`).addClass('d-none');
                    $(`#cs-modules-active-${courseId}`).removeClass('d-none').addClass('fade-in');
                }, 300);
            } else {
                $(`#cs-modules-active-${courseId}`).addClass('fade-out');
                setTimeout(() => {
                    $(`#cs-modules-active-${courseId}`).addClass('d-none');
                    $(`#cs-modules-archived-${courseId}`).removeClass('d-none').addClass('fade-in');
                }, 300);
            }
        });
    };

    /**
     * Initialize log viewer
     */
    CourScribeModules.initLogViewer = function() {
        $(document).on('click', '.cs-view-logs-btn', (e) => {
            const moduleId = $(e.target).data('module-id');
            this.loadModuleLogs(moduleId);
        });

        // Log filters
        $(document).on('change', '#cs-log-filter, #cs-log-sort', () => {
            const moduleId = $('.cs-view-logs-btn.active').data('module-id');
            if (moduleId) {
                this.loadModuleLogs(moduleId);
            }
        });
    };

    /**
     * Load module logs with enhanced formatting
     */
    CourScribeModules.loadModuleLogs = function(moduleId, page = 1) {
        const requestData = {
            action: 'courscribe_get_module_logs',
            module_id: moduleId,
            page: page,
            filter: $('#cs-log-filter').val() || 'all',
            sort: $('#cs-log-sort').val() || 'date-desc',
            nonce: this.config.moduleNonce
        };

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: (response) => {
                if (response.success) {
                    $('#cs-logs-container').html(response.data.logs);
                    $('#cs-logs-pagination').html(response.data.pagination);
                } else {
                    $('#cs-logs-container').html('<div class="cs-empty-state">No logs found</div>');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Error loading logs');
            }
        });
    };

    // Archive module
    CourScribeModules.performArchive = function(moduleId) {
        const requestData = {
            action: 'courscribe_archive_module',
            module_id: moduleId,
            course_id: this.config.courseId,
            nonce: this.config.moduleNonce
        };

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: (response) => {
                if (response.success) {
                    this.utils.showToast('success', 'Module archived successfully');
                    $(`#cs-module-${moduleId}`).addClass('fade-out');
                    setTimeout(() => {
                        $(`#cs-module-${moduleId}`).remove();
                    }, 300);
                    $('.modal').modal('hide');
                } else {
                    this.utils.showToast('error', response.data?.message || 'Failed to archive module');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Error archiving module');
            }
        });
    };

    // Delete module
    CourScribeModules.performDelete = function(moduleId) {
        const requestData = {
            action: 'handle_delete_module',
            module_id: moduleId,
            course_id: this.config.courseId,
            nonce: this.config.moduleNonce
        };

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: (response) => {
                if (response.success) {
                    this.utils.showToast('success', 'Module deleted successfully');
                    $(`#cs-module-${moduleId}`).addClass('fade-out');
                    setTimeout(() => {
                        $(`#cs-module-${moduleId}`).remove();
                    }, 300);
                    $('.modal').modal('hide');
                } else {
                    this.utils.showToast('error', response.data?.message || 'Failed to delete module');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Error deleting module');
            }
        });
    };

    // Restore module
    CourScribeModules.restoreModule = function(moduleId) {
        const requestData = {
            action: 'courscribe_restore_module',
            module_id: moduleId,
            course_id: this.config.courseId,
            nonce: this.config.moduleNonce
        };

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: requestData,
            success: (response) => {
                if (response.success) {
                    this.utils.showToast('success', 'Module restored successfully');
                    // You might want to move the module to the active view
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.utils.showToast('error', response.data?.message || 'Failed to restore module');
                }
            },
            error: () => {
                this.utils.showToast('error', 'Error restoring module');
            }
        });
    };

    // Expose to global scope
    window.CourScribeModulesPremium = CourScribeModules;

})(jQuery);