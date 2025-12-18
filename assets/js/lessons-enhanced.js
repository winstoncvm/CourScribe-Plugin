/**
 * CourScribe Enhanced Lessons JavaScript
 * Handles all interactive functionality for the enhanced lessons interface
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize enhanced lessons
    const EnhancedLessons = {
        currentLessonId: null,
        autoSaveTimeout: null,
        activeTab: 'overview',

        init: function() {
            this.setupEventHandlers();
            this.setupTabNavigation();
            this.setupAutoSave();
            this.initializeLessons();
            console.log('CourScribe Enhanced Lessons initialized');
        },

        /**
         * Setup event handlers for lesson interactions
         */
        setupEventHandlers: function() {
            // Tab navigation
            $(document).on('click', '.cs-lesson-tab', this.switchTab);
            
            // Lesson field updates
            $(document).on('input change', '.cs-lesson-title-input', this.handleFieldChange);
            $(document).on('input change', '.cs-lesson-goal-textarea', this.handleFieldChange);
            
            // Teaching points
            $(document).on('click', '.cs-add-teaching-point-btn', this.showAddTeachingPointForm);
            $(document).on('click', '.cs-save-teaching-point-btn', this.saveTeachingPoint);
            $(document).on('click', '.cs-cancel-teaching-point-btn', this.hideAddTeachingPointForm);
            $(document).on('click', '.cs-remove-teaching-point-btn', this.removeTeachingPoint);
            
            // Objectives
            $(document).on('click', '.cs-add-objective-btn', this.showAddObjectiveModal);
            $(document).on('click', '.cs-remove-objective-btn', this.removeObjective);
            $(document).on('input change', '.cs-objective-description-input', this.handleObjectiveChange);
            
            // Activities
            $(document).on('click', '.cs-add-activity-btn', this.showAddActivityModal);
            $(document).on('click', '.cs-remove-activity-btn', this.removeActivity);
            $(document).on('input change', '.cs-activity-title-input, .cs-activity-description-textarea', this.handleActivityChange);
            
            // Lesson actions
            $(document).on('click', '.cs-archive-lesson-btn', this.archiveLesson);
            $(document).on('click', '.cs-restore-lesson-btn', this.restoreLesson);
            $(document).on('click', '.cs-delete-lesson-btn', this.deleteLesson);
            $(document).on('click', '.cs-move-lesson-btn', this.moveLesson);
            $(document).on('click', '.cs-view-logs-btn', this.viewLogs);
            
            // Modal handlers
            $(document).on('click', '.cs-modal-close, .cs-modal-overlay', this.closeModal);
            $(document).on('click', '.cs-modal-content', function(e) { e.stopPropagation(); });
            $(document).on('click', '.cs-btn-secondary', this.closeModal);
            
            // Form submissions
            $(document).on('submit', '#cs-add-objective-form', this.submitObjectiveForm);
            $(document).on('submit', '#cs-add-activity-form', this.submitActivityForm);
            
            // Character counters
            $(document).on('input', '.cs-form-textarea, .cs-lesson-goal-textarea', this.updateCharacterCounter);
        },

        /**
         * Setup tab navigation
         */
        setupTabNavigation: function() {
            // Initialize first tab as active
            $('.cs-lesson-tab').first().addClass('active');
            $('.cs-lesson-tab-content').first().addClass('active');
        },

        /**
         * Switch between lesson tabs
         */
        switchTab: function(e) {
            e.preventDefault();
            const $tab = $(this);
            const tabId = $tab.data('tab');
            
            // Update tab states
            $('.cs-lesson-tab').removeClass('active');
            $('.cs-lesson-tab-content').removeClass('active');
            
            $tab.addClass('active');
            $(`#cs-lesson-tab-${tabId}`).addClass('active');
            
            EnhancedLessons.activeTab = tabId;
        },

        /**
         * Initialize all lessons on the page
         */
        initializeLessons: function() {
            $('.cs-enhanced-lesson-container').each(function() {
                const lessonId = $(this).data('lesson-id');
                if (lessonId) {
                    EnhancedLessons.currentLessonId = lessonId;
                    EnhancedLessons.updateCharacterCounters(this);
                }
            });
        },

        /**
         * Setup auto-save functionality
         */
        setupAutoSave: function() {
            $(document).on('input change', '.cs-autosave-field', function() {
                const lessonId = $(this).closest('.cs-enhanced-lesson-container').data('lesson-id');
                if (lessonId) {
                    EnhancedLessons.currentLessonId = lessonId;
                    clearTimeout(EnhancedLessons.autoSaveTimeout);
                    EnhancedLessons.autoSaveTimeout = setTimeout(() => {
                        EnhancedLessons.autoSaveField(this);
                    }, 2000);
                }
            });
        },

        /**
         * Handle field changes with validation
         */
        handleFieldChange: function() {
            const $field = $(this);
            const lessonId = $field.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId) return;
            
            EnhancedLessons.currentLessonId = lessonId;
            EnhancedLessons.validateField($field);
            
            // Trigger auto-save
            $field.addClass('cs-autosave-field').trigger('change');
        },

        /**
         * Auto-save lesson field
         */
        autoSaveField: function(field) {
            const $field = $(field);
            const lessonId = EnhancedLessons.currentLessonId;
            const fieldName = $field.data('field') || $field.attr('name');
            const fieldValue = $field.val();
            
            if (!lessonId || !fieldName) return;
            
            EnhancedLessons.showAutoSaveIndicator('saving');
            
            const data = {
                action: 'courscribe_autosave_lesson_field',
                lesson_id: lessonId,
                field_name: fieldName,
                field_value: fieldValue,
                timestamp: Math.floor(Date.now() / 1000),
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showAutoSaveIndicator('saved');
                    } else {
                        EnhancedLessons.showAutoSaveIndicator('error');
                        console.error('Auto-save failed:', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showAutoSaveIndicator('error');
                    console.error('Auto-save network error');
                }
            });
        },

        /**
         * Handle objective field changes
         */
        handleObjectiveChange: function() {
            const $field = $(this);
            const objectiveIndex = $field.data('objective-index');
            const lessonId = $field.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId || objectiveIndex === undefined) return;
            
            // Trigger auto-save for objective
            clearTimeout(EnhancedLessons.autoSaveTimeout);
            EnhancedLessons.autoSaveTimeout = setTimeout(() => {
                EnhancedLessons.autoSaveObjective(lessonId, objectiveIndex, $field);
            }, 2000);
        },

        /**
         * Auto-save objective changes
         */
        autoSaveObjective: function(lessonId, objectiveIndex, $field) {
            const fieldValue = $field.val();
            
            EnhancedLessons.showAutoSaveIndicator('saving');
            
            const data = {
                action: 'courscribe_autosave_lesson_field',
                lesson_id: lessonId,
                field_name: 'objective_description',
                field_value: fieldValue,
                objective_index: objectiveIndex,
                timestamp: Math.floor(Date.now() / 1000),
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showAutoSaveIndicator('saved');
                    } else {
                        EnhancedLessons.showAutoSaveIndicator('error');
                    }
                },
                error: function() {
                    EnhancedLessons.showAutoSaveIndicator('error');
                }
            });
        },

        /**
         * Handle activity field changes
         */
        handleActivityChange: function() {
            const $field = $(this);
            const activityIndex = $field.data('activity-index');
            const lessonId = $field.closest('.cs-enhanced-lesson-container').data('lesson-id');
            const fieldType = $field.hasClass('cs-activity-title-input') ? 'activity_title' : 'activity_description';
            
            if (!lessonId || activityIndex === undefined) return;
            
            // Trigger auto-save for activity
            clearTimeout(EnhancedLessons.autoSaveTimeout);
            EnhancedLessons.autoSaveTimeout = setTimeout(() => {
                EnhancedLessons.autoSaveActivity(lessonId, activityIndex, fieldType, $field.val());
            }, 2000);
        },

        /**
         * Auto-save activity changes
         */
        autoSaveActivity: function(lessonId, activityIndex, fieldType, fieldValue) {
            EnhancedLessons.showAutoSaveIndicator('saving');
            
            const data = {
                action: 'courscribe_autosave_lesson_field',
                lesson_id: lessonId,
                field_name: fieldType,
                field_value: fieldValue,
                activity_index: activityIndex,
                timestamp: Math.floor(Date.now() / 1000),
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showAutoSaveIndicator('saved');
                    } else {
                        EnhancedLessons.showAutoSaveIndicator('error');
                    }
                },
                error: function() {
                    EnhancedLessons.showAutoSaveIndicator('error');
                }
            });
        },

        /**
         * Show add teaching point form
         */
        showAddTeachingPointForm: function() {
            const $container = $(this).closest('.cs-teaching-points-section');
            const $form = $container.find('.cs-add-teaching-point-form');
            $form.show().find('.cs-teaching-point-input').focus();
        },

        /**
         * Hide add teaching point form
         */
        hideAddTeachingPointForm: function() {
            const $form = $(this).closest('.cs-add-teaching-point-form');
            $form.hide().find('.cs-teaching-point-input').val('');
        },

        /**
         * Save teaching point
         */
        saveTeachingPoint: function() {
            const $btn = $(this);
            const $form = $btn.closest('.cs-add-teaching-point-form');
            const $input = $form.find('.cs-teaching-point-input');
            const pointText = $input.val().trim();
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!pointText || !lessonId) {
                EnhancedLessons.showNotification('error', 'Teaching point text is required');
                return;
            }
            
            if (pointText.length > 300) {
                EnhancedLessons.showNotification('error', 'Teaching point too long (max 300 characters)');
                return;
            }
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            const data = {
                action: 'courscribe_add_teaching_point',
                lesson_id: lessonId,
                point_text: pointText,
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to show new teaching point
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
                }
            });
        },

        /**
         * Remove teaching point
         */
        removeTeachingPoint: function() {
            if (!confirm('Are you sure you want to remove this teaching point?')) {
                return;
            }
            
            const $btn = $(this);
            const pointIndex = $btn.data('point-index');
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId || pointIndex === undefined) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            const data = {
                action: 'courscribe_remove_teaching_point',
                lesson_id: lessonId,
                point_index: pointIndex,
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to update teaching points
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            });
        },

        /**
         * Show add objective modal
         */
        showAddObjectiveModal: function() {
            const lessonId = $(this).closest('.cs-enhanced-lesson-container').data('lesson-id');
            if (!lessonId) return;
            
            const modalHtml = `
                <div class="cs-modal-overlay">
                    <div class="cs-modal-content">
                        <div class="cs-modal-header">
                            <h3 class="cs-modal-title">
                                <i class="fas fa-target"></i>
                                Add Learning Objective
                            </h3>
                            <button class="cs-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form id="cs-add-objective-form">
                            <input type="hidden" name="lesson_id" value="${lessonId}">
                            <div class="cs-form-group">
                                <label class="cs-form-label">Thinking Skill (Bloom's Taxonomy)</label>
                                <select name="thinking_skill" class="cs-form-select" required>
                                    <option value="remember">Remember</option>
                                    <option value="understand" selected>Understand</option>
                                    <option value="apply">Apply</option>
                                    <option value="analyze">Analyze</option>
                                    <option value="evaluate">Evaluate</option>
                                    <option value="create">Create</option>
                                </select>
                                <div class="cs-form-help">Select the cognitive level for this objective</div>
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-form-label">Action Verb</label>
                                <input type="text" name="action_verb" class="cs-form-input" value="explain" required>
                                <div class="cs-form-help">What action should students be able to perform?</div>
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-form-label">Objective Description</label>
                                <textarea name="description" class="cs-form-textarea" rows="4" required 
                                    placeholder="Students will be able to..."></textarea>
                                <div class="cs-character-counter">0 / 500</div>
                            </div>
                            <div class="cs-modal-actions">
                                <button type="button" class="cs-btn-secondary">Cancel</button>
                                <button type="submit" class="cs-btn-primary">
                                    <i class="fas fa-plus"></i> Add Objective
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#cs-add-objective-form').find('[name="description"]').focus();
        },

        /**
         * Show add activity modal
         */
        showAddActivityModal: function() {
            const lessonId = $(this).closest('.cs-enhanced-lesson-container').data('lesson-id');
            if (!lessonId) return;
            
            const modalHtml = `
                <div class="cs-modal-overlay">
                    <div class="cs-modal-content">
                        <div class="cs-modal-header">
                            <h3 class="cs-modal-title">
                                <i class="fas fa-tasks"></i>
                                Add Learning Activity
                            </h3>
                            <button class="cs-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form id="cs-add-activity-form">
                            <input type="hidden" name="lesson_id" value="${lessonId}">
                            <div class="cs-form-group">
                                <label class="cs-form-label">Activity Title</label>
                                <input type="text" name="title" class="cs-form-input" required 
                                    placeholder="Enter activity name">
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-form-label">Activity Type</label>
                                <select name="type" class="cs-form-select">
                                    <option value="individual">Individual</option>
                                    <option value="group">Group</option>
                                    <option value="discussion">Discussion</option>
                                    <option value="hands-on">Hands-on</option>
                                    <option value="presentation">Presentation</option>
                                </select>
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-form-label">Duration (minutes)</label>
                                <input type="number" name="duration" class="cs-form-input" value="15" min="1" max="180">
                            </div>
                            <div class="cs-form-group">
                                <label class="cs-form-label">Activity Description</label>
                                <textarea name="description" class="cs-form-textarea" rows="4" required 
                                    placeholder="Describe what students will do..."></textarea>
                                <div class="cs-character-counter">0 / 1000</div>
                            </div>
                            <div class="cs-modal-actions">
                                <button type="button" class="cs-btn-secondary">Cancel</button>
                                <button type="submit" class="cs-btn-primary">
                                    <i class="fas fa-plus"></i> Add Activity
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#cs-add-activity-form').find('[name="title"]').focus();
        },

        /**
         * Submit objective form
         */
        submitObjectiveForm: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('[type="submit"]');
            
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
            
            const formData = $form.serialize();
            const data = formData + '&action=courscribe_add_objective&nonce=' + CourScribeConfig.objectiveNonce;
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        EnhancedLessons.closeModal();
                        location.reload(); // Refresh to show new objective
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Objective');
                }
            });
        },

        /**
         * Submit activity form
         */
        submitActivityForm: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('[type="submit"]');
            
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
            
            const formData = $form.serialize();
            const data = formData + '&action=courscribe_add_activity&nonce=' + CourScribeConfig.activityNonce;
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        EnhancedLessons.closeModal();
                        location.reload(); // Refresh to show new activity
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Activity');
                }
            });
        },

        /**
         * Remove objective
         */
        removeObjective: function() {
            if (!confirm('Are you sure you want to remove this objective?')) {
                return;
            }
            
            const $btn = $(this);
            const objectiveIndex = $btn.data('objective-index');
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId || objectiveIndex === undefined) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            const data = {
                action: 'courscribe_remove_objective',
                lesson_id: lessonId,
                objective_index: objectiveIndex,
                nonce: CourScribeConfig.objectiveNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to update objectives
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            });
        },

        /**
         * Remove activity
         */
        removeActivity: function() {
            if (!confirm('Are you sure you want to remove this activity?')) {
                return;
            }
            
            const $btn = $(this);
            const activityIndex = $btn.data('activity-index');
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId || activityIndex === undefined) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            const data = {
                action: 'courscribe_remove_activity',
                lesson_id: lessonId,
                activity_index: activityIndex,
                nonce: CourScribeConfig.activityNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to update activities
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                }
            });
        },

        /**
         * Archive lesson
         */
        archiveLesson: function() {
            if (!confirm('Are you sure you want to archive this lesson? It can be restored later.')) {
                return;
            }
            
            const $btn = $(this);
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Archiving...');
            
            const data = {
                action: 'courscribe_archive_lesson',
                lesson_id: lessonId,
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to update lesson status
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-archive"></i> Archive');
                }
            });
        },

        /**
         * Restore lesson
         */
        restoreLesson: function() {
            const $btn = $(this);
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Restoring...');
            
            const data = {
                action: 'courscribe_restore_lesson',
                lesson_id: lessonId,
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to update lesson status
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> Restore');
                }
            });
        },

        /**
         * Delete lesson
         */
        deleteLesson: function() {
            const confirmation = prompt('This will permanently delete the lesson. Type "DELETE" to confirm:');
            if (confirmation !== 'DELETE') {
                return;
            }
            
            const $btn = $(this);
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
            
            const data = {
                action: 'courscribe_delete_lesson',
                lesson_id: lessonId,
                confirm: 'DELETE',
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        // Remove lesson from DOM or redirect
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
                }
            });
        },

        /**
         * Move lesson up/down
         */
        moveLesson: function() {
            const $btn = $(this);
            const direction = $btn.data('direction');
            const lessonId = $btn.closest('.cs-enhanced-lesson-container').data('lesson-id');
            
            if (!lessonId || !direction) return;
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            const data = {
                action: 'courscribe_move_lesson',
                lesson_id: lessonId,
                direction: direction,
                nonce: CourScribeConfig.lessonNonce
            };
            
            $.ajax({
                url: CourScribeConfig.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        EnhancedLessons.showNotification('success', response.data.message);
                        location.reload(); // Refresh to update lesson order
                    } else {
                        EnhancedLessons.showNotification('error', response.data.message);
                    }
                },
                error: function() {
                    EnhancedLessons.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    const iconClass = direction === 'up' ? 'fa-arrow-up' : 'fa-arrow-down';
                    $btn.prop('disabled', false).html(`<i class="fas ${iconClass}"></i>`);
                }
            });
        },

        /**
         * View lesson logs
         */
        viewLogs: function() {
            const lessonId = $(this).closest('.cs-enhanced-lesson-container').data('lesson-id');
            if (!lessonId) return;
            
            // For now, just show a simple alert. In the future, this could open a modal with logs
            EnhancedLessons.showNotification('info', 'Activity logs feature coming soon');
        },

        /**
         * Close modal
         */
        closeModal: function(e) {
            if (e) {
                e.stopPropagation();
            }
            $('.cs-modal-overlay').remove();
        },

        /**
         * Update character counter
         */
        updateCharacterCounter: function() {
            const $field = $(this);
            const maxLength = $field.attr('maxlength') || 500;
            const currentLength = $field.val().length;
            const $counter = $field.siblings('.cs-character-counter').first();
            
            if ($counter.length) {
                $counter.text(`${currentLength} / ${maxLength}`);
                
                // Update counter color based on usage
                $counter.removeClass('warning danger');
                if (currentLength > maxLength * 0.8) {
                    $counter.addClass(currentLength > maxLength * 0.95 ? 'danger' : 'warning');
                }
            }
        },

        /**
         * Update all character counters in a container
         */
        updateCharacterCounters: function(container) {
            $(container).find('.cs-form-textarea, .cs-lesson-goal-textarea').each(function() {
                EnhancedLessons.updateCharacterCounter.call(this);
            });
        },

        /**
         * Validate field input
         */
        validateField: function($field) {
            const value = $field.val();
            const fieldName = $field.data('field') || $field.attr('name');
            let isValid = true;
            let message = '';
            
            // Field-specific validation
            switch (fieldName) {
                case 'lesson_name':
                    if (value.length > 100) {
                        isValid = false;
                        message = 'Lesson name too long (max 100 characters)';
                    }
                    break;
                case 'lesson_goal':
                    if (value.length > 500) {
                        isValid = false;
                        message = 'Lesson goal too long (max 500 characters)';
                    }
                    break;
            }
            
            // Update field styling
            $field.removeClass('cs-field-error cs-field-valid');
            if (!isValid) {
                $field.addClass('cs-field-error');
                EnhancedLessons.showNotification('error', message);
            } else if (value.trim()) {
                $field.addClass('cs-field-valid');
            }
            
            return isValid;
        },

        /**
         * Show auto-save indicator
         */
        showAutoSaveIndicator: function(status) {
            const $indicator = $('#cs-autosave-indicator');
            
            if ($indicator.length === 0) {
                $('body').append(`
                    <div id="cs-autosave-indicator" class="cs-autosave-indicator">
                        <i class="fas fa-save"></i>
                        <span class="cs-autosave-text">Saved</span>
                    </div>
                `);
            }
            
            const $indicatorElement = $('#cs-autosave-indicator');
            const $icon = $indicatorElement.find('i');
            const $text = $indicatorElement.find('.cs-autosave-text');
            
            $indicatorElement.removeClass('saving saved error').addClass(status);
            
            switch (status) {
                case 'saving':
                    $icon.removeClass().addClass('fas fa-spinner fa-spin');
                    $text.text('Saving...');
                    break;
                case 'saved':
                    $icon.removeClass().addClass('fas fa-check');
                    $text.text('Saved');
                    break;
                case 'error':
                    $icon.removeClass().addClass('fas fa-exclamation-triangle');
                    $text.text('Error');
                    break;
            }
            
            $indicatorElement.addClass('visible');
            
            // Auto-hide after 3 seconds
            if (status === 'saved' || status === 'error') {
                setTimeout(() => {
                    $indicatorElement.removeClass('visible');
                }, 3000);
            }
        },

        /**
         * Show notification
         */
        showNotification: function(type, message) {
            // Remove existing notifications
            $('.cs-notification').remove();
            
            const $notification = $(`
                <div class="cs-notification ${type}">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
        }
    };

    // Initialize enhanced lessons
    EnhancedLessons.init();

    // Expose for global access
    window.CourScribeEnhancedLessons = EnhancedLessons;
});