/**
 * CourScribe Single Curriculum Functionality
 * Handles all JavaScript interactions for the single curriculum page
 */

jQuery(document).ready(function($) {
    'use strict';

    // Constants
    const AJAX_URL = courscribe_single_curriculum_vars.ajax_url;
    const NONCE = courscribe_single_curriculum_vars.nonce;
    const CURRENT_USER_ID = courscribe_single_curriculum_vars.current_user_id;
    const IS_CLIENT = courscribe_single_curriculum_vars.is_client === '1';

    // Utility Functions
    function showMessage(message, type = 'success') {
        // Create toast notification
        const toast = $(`
            <div class="courscribe-toast courscribe-toast-${type}">
                <div class="courscribe-toast-content">
                    <i class="fa fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="courscribe-toast-close">&times;</button>
            </div>
        `);

        // Add to page
        if (!$('.courscribe-toast-container').length) {
            $('body').append('<div class="courscribe-toast-container"></div>');
        }
        $('.courscribe-toast-container').append(toast);

        // Auto-remove after 5s
        setTimeout(() => toast.remove(), 5000);

        // Manual close
        toast.find('.courscribe-toast-close').on('click', () => toast.remove());
    }

    function debounce(func, wait) {
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

    // Course Management
    class CourseManager {
        constructor() {
            this.initEventHandlers();
        }

        initEventHandlers() {
            // Course name editing
            $(document).on('blur', '.the-input-field', this.handleCourseNameChange.bind(this));
            $(document).on('keypress', '.the-input-field', this.handleCourseNameKeypress.bind(this));
            
            // Course deletion
            $(document).on('click', '.delete-course', this.handleCourseDelete.bind(this));
            
            // Course reordering
            this.initDragAndDrop();
        }

        handleCourseNameChange(e) {
            const $input = $(e.target);
            const courseId = this.extractCourseId($input);
            const newTitle = $input.val().trim();
            
            if (!newTitle) {
                showMessage('Course title cannot be empty', 'error');
                return;
            }

            this.updateCourseTitle(courseId, newTitle);
        }

        handleCourseNameKeypress(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $(e.target).blur();
            }
        }

        handleCourseDelete(e) {
            e.preventDefault();
            const $button = $(e.target);
            const courseId = $button.data('course-id');
            const courseName = $button.closest('.courscribe-xy-acc-item').find('.course-title-span').text();

            if (confirm(`Are you sure you want to delete the course "${courseName}"? This action cannot be undone.`)) {
                this.deleteCourse(courseId);
            }
        }

        updateCourseTitle(courseId, newTitle) {
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_update_course_title',
                    nonce: NONCE,
                    course_id: courseId,
                    title: newTitle
                },
                success: (response) => {
                    if (response.success) {
                        // Update the visible title span
                        $(`#course-header-${courseId} .course-title-span`).text(newTitle);
                        showMessage('Course title updated successfully');
                    } else {
                        showMessage(response.data.message || 'Failed to update course title', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error updating course title:', error);
                    showMessage('Network error: Please try again', 'error');
                }
            });
        }

        deleteCourse(courseId) {
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_delete_course',
                    nonce: NONCE,
                    course_id: courseId
                },
                success: (response) => {
                    if (response.success) {
                        // Remove course from DOM
                        $(`.courscribe-xy-acc-item[data-course-id="${courseId}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        showMessage('Course deleted successfully');
                    } else {
                        showMessage(response.data.message || 'Failed to delete course', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error deleting course:', error);
                    showMessage('Network error: Please try again', 'error');
                }
            });
        }

        extractCourseId($element) {
            // Extract course ID from element ID or closest course container
            const id = $element.attr('id');
            if (id) {
                const match = id.match(/course-name-(\d+)/);
                if (match) return match[1];
            }
            
            // Fallback: get from closest course container
            const $courseContainer = $element.closest('.courscribe-xy-acc-item');
            return $courseContainer.data('course-id');
        }

        initDragAndDrop() {
            if (IS_CLIENT) return; // Clients can't reorder

            const coursesContainer = document.getElementById('coursesAccordion');
            if (coursesContainer) {
                Sortable.create(coursesContainer, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    onEnd: (evt) => {
                        this.updateCourseOrder();
                    }
                });
            }
        }

        updateCourseOrder() {
            const courseIds = [];
            $('#coursesAccordion .courscribe-xy-acc-item').each(function() {
                courseIds.push($(this).data('course-id'));
            });

            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_update_course_order',
                    nonce: NONCE,
                    course_ids: courseIds
                },
                success: (response) => {
                    if (response.success) {
                        showMessage('Course order updated');
                    } else {
                        showMessage('Failed to update course order', 'error');
                        // Refresh page to restore original order
                        location.reload();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error updating course order:', error);
                    showMessage('Network error: Please try again', 'error');
                    location.reload();
                }
            });
        }
    }

    // Module Management
    class ModuleManager {
        constructor() {
            this.initEventHandlers();
        }

        initEventHandlers() {
            // Add new module
            $(document).on('click', '.add-module-btn', this.handleAddModule.bind(this));
            
            // Delete module
            $(document).on('click', '.delete-module', this.handleDeleteModule.bind(this));
            
            // Update module title
            $(document).on('blur', '.module-input-field', this.handleModuleUpdate.bind(this));
        }

        handleAddModule(e) {
            e.preventDefault();
            const $button = $(e.target);
            const courseId = $button.data('course-id');
            
            // Get module title from modal or prompt
            const moduleTitle = prompt('Enter module title:');
            if (!moduleTitle) return;

            this.createModule(courseId, moduleTitle);
        }

        handleDeleteModule(e) {
            e.preventDefault();
            const $button = $(e.target);
            const moduleId = $button.data('module-id');
            
            if (confirm('Are you sure you want to delete this module? This action cannot be undone.')) {
                this.deleteModule(moduleId);
            }
        }

        handleModuleUpdate(e) {
            const $input = $(e.target);
            const moduleId = this.extractModuleId($input);
            const newTitle = $input.val().trim();
            
            if (!newTitle) {
                showMessage('Module title cannot be empty', 'error');
                return;
            }

            this.updateModule(moduleId, newTitle);
        }

        createModule(courseId, title) {
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_create_module',
                    nonce: NONCE,
                    course_id: courseId,
                    title: title
                },
                success: (response) => {
                    if (response.success) {
                        showMessage('Module created successfully');
                        // Could refresh the course section or add module HTML dynamically
                        location.reload(); // Simple approach for now
                    } else {
                        showMessage(response.data.message || 'Failed to create module', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error creating module:', error);
                    showMessage('Network error: Please try again', 'error');
                }
            });
        }

        updateModule(moduleId, title) {
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_update_module',
                    nonce: NONCE,
                    module_id: moduleId,
                    title: title
                },
                success: (response) => {
                    if (response.success) {
                        showMessage('Module updated successfully');
                    } else {
                        showMessage(response.data.message || 'Failed to update module', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error updating module:', error);
                    showMessage('Network error: Please try again', 'error');
                }
            });
        }

        deleteModule(moduleId) {
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_delete_module',
                    nonce: NONCE,
                    module_id: moduleId
                },
                success: (response) => {
                    if (response.success) {
                        // Remove module from DOM
                        $(`.module-item[data-module-id="${moduleId}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        showMessage('Module deleted successfully');
                    } else {
                        showMessage(response.data.message || 'Failed to delete module', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error deleting module:', error);
                    showMessage('Network error: Please try again', 'error');
                }
            });
        }

        extractModuleId($element) {
            // Similar to course ID extraction
            const id = $element.attr('id');
            if (id) {
                const match = id.match(/module-(\d+)/);
                if (match) return match[1];
            }
            
            const $moduleContainer = $element.closest('.module-item');
            return $moduleContainer.data('module-id');
        }
    }

    // Slide Deck Management
    class SlideDeckManager {
        constructor() {
            this.initEventHandlers();
        }

        initEventHandlers() {
            // Generate slide deck
            $(document).on('click', '.courscribe-generate-slide-deck', this.handleGenerateSlides.bind(this));
            
            // Preview slide deck
            $(document).on('click', '.courscribe-preview-slide-deck', this.handlePreviewSlides.bind(this));
            
            // Download slide deck
            $(document).on('change', '[id^="courscribe-download-deck-"]', this.handleDownloadSlides.bind(this));
        }

        handleGenerateSlides(e) {
            e.preventDefault();
            const $button = $(e.target);
            const courseId = $button.data('course-id');
            
            // Show loading state
            const originalText = $button.text();
            $button.text('Generating...').prop('disabled', true);

            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: 'courscribe_generate_slide_deck',
                    nonce: NONCE,
                    course_id: courseId
                },
                success: (response) => {
                    if (response.success) {
                        showMessage('Slide deck generated successfully');
                        // Update UI with new slide deck
                        this.updateSlideDeckSelect(courseId, response.data.slide_deck);
                    } else {
                        showMessage(response.data.message || 'Failed to generate slide deck', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error generating slide deck:', error);
                    showMessage('Network error: Please try again', 'error');
                },
                complete: () => {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        }

        handlePreviewSlides(e) {
            e.preventDefault();
            const $button = $(e.target);
            const courseId = $button.data('course-id');
            
            // Get latest slide deck URL
            const $select = $(`#courscribe-download-deck-${courseId}`);
            const selectedOption = $select.find(':selected');
            const revealUrl = selectedOption.data('reveal-url');
            
            if (revealUrl) {
                $('#courscribe-preview-iframe').attr('src', revealUrl);
                $('#courscribePreviewOffcanvas').offcanvas('show');
            } else {
                showMessage('No slide deck available for preview', 'error');
            }
        }

        handleDownloadSlides(e) {
            const $select = $(e.target);
            const selectedUrl = $select.val();
            
            if (selectedUrl) {
                // Create temporary link and trigger download
                const link = document.createElement('a');
                link.href = selectedUrl;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        updateSlideDeckSelect(courseId, slideDeck) {
            const $select = $(`#courscribe-download-deck-${courseId}`);
            if ($select.length && slideDeck.ppt_url && slideDeck.date) {
                const date = new Date(slideDeck.date).toLocaleString();
                const $option = $(`<option value="${slideDeck.ppt_url}" data-reveal-url="${slideDeck.reveal_url || ''}">${date}</option>`);
                $select.prepend($option);
            }
        }
    }

    // Auto-save functionality
    class AutoSave {
        constructor() {
            this.saveQueue = new Map();
            this.initAutoSave();
        }

        initAutoSave() {
            // Auto-save course titles after 2 seconds of inactivity
            $(document).on('input', '.the-input-field', debounce((e) => {
                const $input = $(e.target);
                const courseId = this.extractCourseId($input);
                const newTitle = $input.val().trim();
                
                if (newTitle && courseId) {
                    this.queueSave('course_title', courseId, { title: newTitle });
                }
            }, 2000));
        }

        queueSave(type, id, data) {
            this.saveQueue.set(`${type}_${id}`, { type, id, data });
            this.processSaveQueue();
        }

        processSaveQueue() {
            if (this.saveQueue.size === 0) return;

            const item = this.saveQueue.entries().next().value;
            const key = item[0];
            const { type, id, data } = item[1];

            this.saveQueue.delete(key);

            // Show subtle save indicator
            this.showSaveIndicator(type, id, 'saving');

            const action = `courscribe_autosave_${type}`;
            
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: {
                    action: action,
                    nonce: NONCE,
                    id: id,
                    ...data
                },
                success: (response) => {
                    if (response.success) {
                        this.showSaveIndicator(type, id, 'saved');
                    } else {
                        this.showSaveIndicator(type, id, 'error');
                    }
                },
                error: () => {
                    this.showSaveIndicator(type, id, 'error');
                },
                complete: () => {
                    // Process next item in queue
                    setTimeout(() => this.processSaveQueue(), 500);
                }
            });
        }

        showSaveIndicator(type, id, status) {
            const $element = this.getElementForType(type, id);
            if (!$element.length) return;

            // Remove existing indicators
            $element.find('.save-indicator').remove();
            
            const icons = {
                saving: 'fa-spinner fa-spin',
                saved: 'fa-check',
                error: 'fa-exclamation-triangle'
            };

            const colors = {
                saving: '#007cba',
                saved: '#46b450',
                error: '#dc3232'
            };

            const $indicator = $(`<i class="fa ${icons[status]} save-indicator" style="color: ${colors[status]}; margin-left: 5px;"></i>`);
            $element.append($indicator);

            // Remove after 2 seconds (except for errors)
            if (status !== 'error') {
                setTimeout(() => $indicator.fadeOut(300, () => $indicator.remove()), 2000);
            }
        }

        getElementForType(type, id) {
            switch (type) {
                case 'course_title':
                    return $(`#course-header-${id}`);
                default:
                    return $();
            }
        }

        extractCourseId($element) {
            const id = $element.attr('id');
            if (id) {
                const match = id.match(/course-name-(\d+)/);
                if (match) return match[1];
            }
            
            const $courseContainer = $element.closest('.courscribe-xy-acc-item');
            return $courseContainer.data('course-id');
        }
    }

    // Keyboard Shortcuts
    class KeyboardShortcuts {
        constructor() {
            this.initShortcuts();
        }

        initShortcuts() {
            $(document).on('keydown', (e) => {
                // Ctrl/Cmd + S to save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.triggerSave();
                }

                // Escape to close modals/offcanvas
                if (e.key === 'Escape') {
                    $('.offcanvas.show').offcanvas('hide');
                    $('.modal.show').modal('hide');
                }
            });
        }

        triggerSave() {
            // Trigger save for any form currently being edited
            const $activeForm = $('form:has(:focus)');
            if ($activeForm.length) {
                $activeForm.find('.courscribe-save-curriculum').click();
            } else {
                showMessage('Nothing to save', 'info');
            }
        }
    }

    // Initialize all managers
    if (!IS_CLIENT) {
        // Only initialize editing functionality for non-clients
        window.courseManager = new CourseManager();
        window.moduleManager = new ModuleManager();
        window.slideDeckManager = new SlideDeckManager();
        window.autoSave = new AutoSave();
        window.keyboardShortcuts = new KeyboardShortcuts();
    }

    // Add CSS for toast notifications
    if (!$('#courscribe-toast-styles').length) {
        $('head').append(`
            <style id="courscribe-toast-styles">
                .courscribe-toast-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                }
                .courscribe-toast {
                    background: #fff;
                    border-left: 4px solid #46b450;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    border-radius: 4px;
                    padding: 12px 16px;
                    margin-bottom: 10px;
                    min-width: 300px;
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    animation: slideInRight 0.3s ease;
                }
                .courscribe-toast-error {
                    border-left-color: #dc3232;
                }
                .courscribe-toast-content {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .courscribe-toast-content i {
                    color: #46b450;
                    font-size: 16px;
                }
                .courscribe-toast-error .courscribe-toast-content i {
                    color: #dc3232;
                }
                .courscribe-toast-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #666;
                    padding: 0;
                    margin-left: 10px;
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                .sortable-ghost {
                    opacity: 0.4;
                }
                .save-indicator {
                    font-size: 12px;
                }
            </style>
        `);
    }

    // Global error handler for AJAX requests
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 403) {
            showMessage('Permission denied. Please refresh the page and try again.', 'error');
        } else if (xhr.status === 0) {
            showMessage('Network connection lost. Please check your internet connection.', 'error');
        }
    });

    console.log('CourScribe Single Curriculum JavaScript initialized');
});