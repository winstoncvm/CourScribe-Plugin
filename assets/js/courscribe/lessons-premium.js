/**
 * CourScribe Premium Lessons JavaScript
 * Handles advanced lessons functionality with real-time validation,
 * auto-save, archive/restore, and activity logging
 */

(function($) {
    'use strict';

    class CourScribeLessonsPremium {
        constructor() {
            this.settings = {
                autoSaveDelay: 1000,
                validationDelay: 300,
                maxRetries: 3,
                notificationDuration: 5000
            };
            
            this.timers = new Map();
            this.validation = new Map();
            this.saving = new Map();
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initializeValidation();
            this.initializeAutoSave();
            this.initializeNotifications();
            this.updateCharacterCounters();
        }

        bindEvents() {
            // Form field events
            $(document).on('input', '.cs-auto-save-field', this.handleFieldInput.bind(this));
            $(document).on('blur', '.cs-auto-save-field', this.handleFieldBlur.bind(this));
            $(document).on('focus', '.cs-auto-save-field', this.handleFieldFocus.bind(this));

            // Character counter events
            $(document).on('input', '.cs-premium-input', this.updateCharacterCounter.bind(this));

            // ✅ FIX: Add Lesson Button Handler
            $(document).on('click', '.cs-add-lesson-btn', this.handleAddLessonClick.bind(this));

            // Lesson actions
            $(document).on('click', '.cs-save-lesson-btn', this.handleSaveLesson.bind(this));
            $(document).on('click', '.cs-archive-lesson-btn', this.handleArchiveLesson.bind(this));
            $(document).on('click', '.cs-unarchive-lesson', this.handleUnarchiveLesson.bind(this));
            $(document).on('click', '.cs-delete-lesson-btn', this.handleDeleteLesson.bind(this));
            
            // Sort controls
            $(document).on('click', '.cs-sort-up', this.handleSortUp.bind(this));
            $(document).on('click', '.cs-sort-down', this.handleSortDown.bind(this));
            
            // Objectives and activities
            $(document).on('click', '.cs-add-objective-btn', this.handleAddObjective.bind(this));
            $(document).on('click', '.cs-add-activity-btn', this.handleAddActivity.bind(this));
            $(document).on('click', '.cs-remove-objective', this.handleRemoveObjective.bind(this));
            $(document).on('click', '.cs-remove-activity', this.handleRemoveActivity.bind(this));
            
            // Teaching points
            $(document).on('click', '.cs-add-teaching-point-btn', this.handleAddTeachingPoint.bind(this));
            $(document).on('click', '.cs-remove-teaching-point', this.handleRemoveTeachingPoint.bind(this));
            $(document).on('input', '.cs-teaching-point-input', this.handleTeachingPointChange.bind(this));
            
            // Notification close buttons
            $(document).on('click', '.cs-notifications-container .btn-close', this.handleCloseNotification.bind(this));
            
            // Activity logs
            $(document).on('show.bs.offcanvas', '.cs-logs-offcanvas', this.handleShowLogs.bind(this));
            $(document).on('click', '.cs-restore-from-log', this.handleRestoreFromLog.bind(this));
        }

        initializeValidation() {
            // Set up field validation rules
            this.validationRules = {
                lesson_name: {
                    required: true,
                    minLength: 3,
                    maxLength: 100,
                    pattern: /^[a-zA-Z0-9\s\-_.,!?()]+$/,
                    message: 'Lesson name must be 3-100 characters and contain only letters, numbers, and basic punctuation.'
                },
                lesson_goal: {
                    required: true,
                    minLength: 10,
                    maxLength: 500,
                    message: 'Lesson goal must be 10-500 characters long.'
                },
                teaching_point: {
                    required: true,
                    minLength: 5,
                    maxLength: 500,
                    message: 'Teaching point must be 5-500 characters long.'
                }
            };
        }

        initializeAutoSave() {
            // Set up auto-save for forms with auto-save enabled
            $('.cs-lesson-form[data-auto-save="true"]').each((index, form) => {
                const $form = $(form);
                const lessonId = $form.data('lesson-id');
                
                // Initialize original values
                $form.find('.cs-auto-save-field').each((i, field) => {
                    const $field = $(field);
                    $field.data('original-value', $field.val());
                });
            });
        }

        initializeNotifications() {
            // Auto-hide success notifications
            setTimeout(() => {
                $('.success-notification').fadeOut(300, function() {
                    $(this).remove();
                });
            }, this.settings.notificationDuration);
        }

        updateCharacterCounters() {
            $('.cs-premium-input').each((index, input) => {
                this.updateCharacterCounter({ target: input });
            });
        }

        handleFieldInput(event) {
            const $field = $(event.target);
            const fieldName = $field.data('field-name');
            const lessonId = $field.data('lesson-id');
            
            if (!fieldName || !lessonId) return;
            
            // Clear existing timer
            const timerKey = `${lessonId}-${fieldName}`;
            if (this.timers.has(timerKey)) {
                clearTimeout(this.timers.get(timerKey));
            }
            
            // Validate field
            this.validateField($field);
            
            // Set auto-save timer
            if ($field.closest('.cs-lesson-form').data('auto-save') === 'true') {
                const timer = setTimeout(() => {
                    this.autoSaveField($field);
                }, this.settings.autoSaveDelay);
                
                this.timers.set(timerKey, timer);
            }
        }

        handleFieldBlur(event) {
            const $field = $(event.target);
            this.validateField($field);
        }

        handleFieldFocus(event) {
            const $field = $(event.target);
            const currentValue = $field.val();
            $field.data('focus-value', currentValue);
        }

        validateField($field) {
            const fieldName = $field.data('field-name') || $field.attr('name');
            const value = $field.val();
            const rules = this.validationRules[fieldName];
            
            if (!rules) return true;
            
            const validation = this.performValidation(value, rules);
            const $feedback = $field.closest('.cs-premium-input-group').find('.cs-validation-message');
            
            if (validation.isValid) {
                $field.removeClass('is-invalid').addClass('is-valid');
                $feedback.text('').removeClass('text-danger').addClass('text-success');
            } else {
                $field.removeClass('is-valid').addClass('is-invalid');
                $feedback.text(validation.message).removeClass('text-success').addClass('text-danger');
            }
            
            return validation.isValid;
        }

        performValidation(value, rules) {
            if (rules.required && (!value || value.trim().length === 0)) {
                return { isValid: false, message: 'This field is required.' };
            }
            
            if (rules.minLength && value.length < rules.minLength) {
                return { isValid: false, message: `Minimum ${rules.minLength} characters required.` };
            }
            
            if (rules.maxLength && value.length > rules.maxLength) {
                return { isValid: false, message: `Maximum ${rules.maxLength} characters allowed.` };
            }
            
            if (rules.pattern && !rules.pattern.test(value)) {
                return { isValid: false, message: rules.message || 'Invalid format.' };
            }
            
            return { isValid: true, message: '' };
        }

        updateCharacterCounter(event) {
            const $field = $(event.target);
            const maxLength = parseInt($field.attr('maxlength'));
            const currentLength = $field.val().length;
            const $counter = $field.closest('.cs-premium-input-group, .cs-add-point-form').find('.cs-character-count, .cs-char-counter');
            
            if ($counter.length && maxLength) {
                $counter.find('.current').text(currentLength);
                $counter.find('.max').text(maxLength);
                
                // Color coding based on usage
                $counter.removeClass('text-warning text-danger');
                if (currentLength > maxLength * 0.8) {
                    $counter.addClass(currentLength > maxLength * 0.95 ? 'text-danger' : 'text-warning');
                }
            }
        }

        autoSaveField($field) {
            const lessonId = $field.data('lesson-id');
            const fieldName = $field.data('field-name');
            const fieldValue = $field.val();
            const originalValue = $field.data('original-value') || '';
            
            // Skip if value hasn't changed
            if (fieldValue === originalValue) {
                return;
            }
            
            // Validate before saving
            if (!this.validateField($field)) {
                this.updateSaveStatus(lessonId, 'validation-error');
                return;
            }
            
            // Check if already saving this field
            const saveKey = `${lessonId}-${fieldName}`;
            if (this.saving.has(saveKey)) {
                return;
            }
            
            this.saving.set(saveKey, true);
            this.updateSaveStatus(lessonId, 'saving');
            
            // Perform AJAX save
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_autosave_lesson_field',
                    lesson_id: lessonId,
                    field_name: fieldName,
                    field_value: fieldValue,
                    nonce: courscribeAjax.lesson_nonce
                },
                success: (response) => {
                    if (response.success) {
                        $field.data('original-value', fieldValue);
                        this.updateSaveStatus(lessonId, 'saved');
                        
                        // Log the change
                        this.logFieldChange(lessonId, fieldName, originalValue, fieldValue);
                    } else {
                        this.updateSaveStatus(lessonId, 'error');
                        this.showNotification('error', response.data?.message || 'Auto-save failed');
                    }
                },
                error: () => {
                    this.updateSaveStatus(lessonId, 'error');
                    this.showNotification('error', 'Network error during auto-save');
                },
                complete: () => {
                    this.saving.delete(saveKey);
                }
            });
        }

        updateSaveStatus(lessonId, status) {
            const $indicator = $(`#cs-autosave-indicator-${lessonId}`);
            if (!$indicator.length) return;
            
            const $icon = $indicator.find('.save-icon');
            const $text = $indicator.find('.save-status');
            const $spinner = $indicator.find('.save-spinner');
            const $timestamp = $indicator.find('.timestamp');
            
            // Hide spinner and show icon by default
            $spinner.addClass('d-none');
            $icon.removeClass('d-none');
            
            switch (status) {
                case 'saving':
                    $icon.addClass('d-none');
                    $spinner.removeClass('d-none');
                    $text.text('Saving changes...');
                    break;
                    
                case 'saved':
                    $icon.removeClass('fa-exclamation-triangle fa-times text-danger')
                         .addClass('fa-save text-success');
                    $text.text('All changes saved');
                    $timestamp.text(new Date().toLocaleTimeString());
                    break;
                    
                case 'error':
                    $icon.removeClass('fa-save text-success')
                         .addClass('fa-exclamation-triangle text-danger');
                    $text.text('Save failed');
                    break;
                    
                case 'validation-error':
                    $icon.removeClass('fa-save text-success')
                         .addClass('fa-times text-warning');
                    $text.text('Fix validation errors');
                    break;
            }
        }

        showNotification(type, message) {
            const $container = $('.cs-notifications-container').first();
            if (!$container.length) return;
            
            const iconClass = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-triangle',
                'warning': 'fa-exclamation-circle',
                'info': 'fa-info-circle'
            }[type] || 'fa-info-circle';
            
            const $notification = $(`
                <div class="${type}-notification">
                    <i class="fas ${iconClass}"></i>
                    <span class="message">${message}</span>
                    <button type="button" class="btn-close" aria-label="Close"></button>
                </div>
            `);
            
            $container.append($notification);
            
            // Auto-hide success and info notifications
            if (type === 'success' || type === 'info') {
                setTimeout(() => {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, this.settings.notificationDuration);
            }
        }

        handleCloseNotification(event) {
            $(event.target).closest('.success-notification, .error-notification, .warning-notification').fadeOut(300, function() {
                $(this).remove();
            });
        }

        /**
         * ✅ FIX: Handle Add Lesson Button Click
         * Opens the Add Lesson Modal and sets the module ID
         */
        handleAddLessonClick(event) {
            event.preventDefault();
            event.stopPropagation();

            const $btn = $(event.currentTarget);
            const moduleId = $btn.data('module-id');
            const courseId = $btn.data('course-id') || $('#lesson-course-id').val();

            console.log('📝 CourScribe: Opening Add Lesson Modal', { moduleId, courseId });

            // ✅ VALIDATION: Ensure module ID is valid
            if (!moduleId || moduleId === 0) {
                console.error('❌ CourScribe: Invalid module ID for add lesson');
                this.showNotification('error', 'Invalid module ID. Please try again.');
                return false;
            }

            // Set the module ID and course ID in the modal form
            $('#lesson-module-id').val(moduleId);
            if (courseId) {
                $('#lesson-course-id').val(courseId);
            }

            // Clear the form for new lesson
            const $form = $('#courscribe-lesson-form');
            $form.find('input[name="lesson_id"]').val(0);
            $form.find('input[name="lesson_name"]').val('');
            $form.find('textarea[name="lesson_goal"]').val('');

            // Show the modal
            const $modal = $('#addLessonModal');
            if ($modal.length) {
                $modal.modal('show');
            } else {
                console.error('❌ CourScribe: Add Lesson Modal not found');
                this.showNotification('error', 'Add Lesson Modal not available');
            }

            return false;
        }

        handleSaveLesson(event) {
            event.preventDefault();

            const $btn = $(event.target);
            const lessonId = $btn.data('lesson-id');
            const $form = $btn.closest('.cs-lesson-form');
            
            // Validate all fields
            let isValid = true;
            $form.find('.cs-auto-save-field').each((index, field) => {
                if (!this.validateField($(field))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                this.showNotification('error', 'Please fix validation errors before saving');
                return;
            }
            
            // Show saving state
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            
            // Collect form data
            const formData = new FormData($form[0]);
            formData.append('action', 'courscribe_save_lesson_premium');
            formData.append('nonce', courscribeAjax.lesson_nonce);
            
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('success', response.data.message || 'Lesson saved successfully');
                        this.updateSaveStatus(lessonId, 'saved');
                        
                        // Update original values
                        $form.find('.cs-auto-save-field').each((index, field) => {
                            const $field = $(field);
                            $field.data('original-value', $field.val());
                        });
                    } else {
                        this.showNotification('error', response.data?.message || 'Failed to save lesson');
                    }
                },
                error: () => {
                    this.showNotification('error', 'Network error while saving lesson');
                },
                complete: () => {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        }

        handleArchiveLesson(event) {
            event.preventDefault();
            
            const $btn = $(event.target);
            const lessonId = $btn.data('lesson-id');
            
            if (!confirm('Are you sure you want to archive this lesson? It will be hidden but can be restored later.')) {
                return;
            }
            
            this.toggleLessonArchiveStatus(lessonId, 'archive');
        }

        handleUnarchiveLesson(event) {
            event.preventDefault();
            
            const $btn = $(event.target);
            const lessonId = $btn.data('lesson-id');
            
            this.toggleLessonArchiveStatus(lessonId, 'unarchive');
        }

        toggleLessonArchiveStatus(lessonId, action) {
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_toggle_lesson_archive',
                    lesson_id: lessonId,
                    archive_action: action,
                    nonce: courscribeAjax.lesson_nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('success', response.data.message);
                        
                        // Refresh the lesson container
                        this.refreshLessonContainer(lessonId);
                    } else {
                        this.showNotification('error', response.data?.message || 'Operation failed');
                    }
                },
                error: () => {
                    this.showNotification('error', 'Network error during operation');
                }
            });
        }

        handleDeleteLesson(event) {
            event.preventDefault();
            
            const $btn = $(event.target);
            const lessonId = $btn.data('lesson-id');
            const $lesson = $btn.closest('.cs-lesson-premium');
            const lessonTitle = $lesson.find('[data-field-name="lesson_name"]').val() || 'this lesson';
            
            if (!confirm(`Are you sure you want to permanently delete "${lessonTitle}"? This action cannot be undone.`)) {
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_delete_lesson',
                    lesson_id: lessonId,
                    nonce: courscribeAjax.lesson_nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('success', response.data.message);
                        
                        // Remove lesson with animation
                        $lesson.fadeOut(500, function() {
                            $(this).remove();
                            
                            // Check if lessons container is empty
                            const $container = $lesson.parent();
                            if ($container.find('.cs-lesson-premium').length === 0) {
                                $container.html(`
                                    <div class="cs-empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <h5>No lessons yet</h5>
                                        <p class="text-muted">Start by adding your first lesson to this module.</p>
                                    </div>
                                `);
                            }
                        });
                    } else {
                        this.showNotification('error', response.data?.message || 'Failed to delete lesson');
                        $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                },
                error: () => {
                    this.showNotification('error', 'Network error while deleting lesson');
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            });
        }

        handleAddTeachingPoint(event) {
            event.preventDefault();
            
            const $btn = $(event.target);
            const lessonId = $btn.data('lesson-id');
            const $input = $(`#cs-new-teaching-point-${lessonId}`);
            const teachingPoint = $input.val().trim();
            
            if (!teachingPoint) {
                this.showNotification('error', 'Please enter a teaching point.');
                $input.focus();
                return;
            }
            
            // Validate teaching point
            const validation = this.performValidation(teachingPoint, this.validationRules.teaching_point);
            if (!validation.isValid) {
                this.showNotification('error', validation.message);
                return;
            }
            
            // Show loading state
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
            
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_add_teaching_point',
                    lesson_id: lessonId,
                    teaching_point: teachingPoint,
                    nonce: courscribeAjax.lesson_nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Clear input
                        $input.val('');
                        this.updateCharacterCounter({ target: $input[0] });
                        
                        // Add new teaching point to container
                        this.addTeachingPointToContainer(lessonId, response.data.teaching_point, response.data.point_index);
                        
                        this.showNotification('success', response.data.message);
                    } else {
                        this.showNotification('error', response.data?.message || 'Failed to add teaching point');
                    }
                },
                error: () => {
                    this.showNotification('error', 'Network error while adding teaching point');
                },
                complete: () => {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        }

        addTeachingPointToContainer(lessonId, teachingPoint, pointIndex) {
            const $container = $(`#cs-teaching-points-container-${lessonId}`);
            const pointNumber = $container.find('.cs-teaching-point-item').length + 1;
            
            // Remove empty message if exists
            $container.find('.cs-empty-state').remove();
            
            const pointHtml = `
                <div class="cs-teaching-point-item mb-2" data-point-index="${pointIndex}">
                    <div class="cs-teaching-point-content">
                        <div class="cs-point-number">
                            <span>${pointNumber}</span>
                        </div>
                        <div class="cs-point-text">
                            <input type="text" 
                                   class="form-control cs-premium-input cs-teaching-point-input cs-auto-save-field" 
                                   value="${teachingPoint}" 
                                   data-field-name="teaching_point"
                                   data-lesson-id="${lessonId}"
                                   data-point-index="${pointIndex}"
                                   data-original-value="${teachingPoint}"
                                   placeholder="Enter teaching point..." />
                        </div>
                        <div class="cs-point-actions">
                            <button type="button" 
                                    class="cs-btn cs-btn-sm cs-btn-danger cs-remove-teaching-point" 
                                    data-point-index="${pointIndex}" 
                                    data-lesson-id="${lessonId}"
                                    title="Remove teaching point">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $container.append(pointHtml);
        }

        handleShowLogs(event) {
            const $offcanvas = $(event.target);
            const moduleId = $offcanvas.find('.cs-logs-container').data('module-id');
            
            if (!moduleId) return;
            
            this.loadActivityLogs(moduleId);
        }

        loadActivityLogs(moduleId) {
            const $container = $(`.cs-logs-container[data-module-id="${moduleId}"]`);
            
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_lesson_activity_logs',
                    module_id: moduleId,
                    nonce: courscribeAjax.lesson_nonce
                },
                success: (response) => {
                    if (response.success) {
                        $container.html(response.data.html);
                    } else {
                        $container.html('<div class="alert alert-danger">Failed to load activity logs.</div>');
                    }
                },
                error: () => {
                    $container.html('<div class="alert alert-danger">Network error while loading logs.</div>');
                }
            });
        }

        logFieldChange(lessonId, fieldName, oldValue, newValue) {
            // Log field changes for audit trail (fire and forget)
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_log_lesson_field_change',
                    lesson_id: lessonId,
                    field_name: fieldName,
                    old_value: oldValue,
                    new_value: newValue,
                    nonce: courscribeAjax.lesson_nonce
                }
            });
        }

        refreshLessonContainer(lessonId) {
            // This would refresh the specific lesson container after archive/unarchive
            // Implementation depends on specific requirements
            location.reload(); // Simple approach for now
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on a lessons page
        if ($('.cs-lessons-premium').length > 0) {
            window.courscribleLessonsPremium = new CourScribeLessonsPremium();
        }
    });

    // Export for external access
    window.CourScribeLessonsPremium = CourScribeLessonsPremium;

})(jQuery);