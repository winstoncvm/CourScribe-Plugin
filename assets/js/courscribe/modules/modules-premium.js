/**
 * CourScribe Modules Premium JavaScript
 * Enhanced module management functionality with drag & drop, search, filtering, and premium media upload
 */

(function($) {
    'use strict';

    // Global variables
    let courseId, curriculumId, ajaxUrl, moduleNonce;
    let sortableInstance = null;
    let searchTimeout = null;

    /**
     * Initialize modules premium functionality
     */
    function initModulesPremium(config) {
        courseId = config.courseId;
        curriculumId = config.curriculumId;
        ajaxUrl = config.ajaxUrl;
        moduleNonce = config.moduleNonce;

        // Initialize all functionality
        initDragDropSorting();
        initSearchAndFilter();
        initExpandableContainer();
        initPremiumFileUpload();
        initMediaManagement();
        initModuleLogs();
        initViewToggle();
        initArchiveRestore();
    }

    /**
     * Initialize Drag & Drop Sorting
     */
    function initDragDropSorting() {
        const moduleContainer = document.getElementById(`cs-modules-container-${courseId}`);
        if (moduleContainer && window.Sortable) {
            sortableInstance = Sortable.create(moduleContainer, {
                handle: '.cs-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onStart: function(evt) {
                    $('.cs-module-item').addClass('dragging');
                },
                onEnd: function(evt) {
                    $('.cs-module-item').removeClass('dragging');
                    
                    // Save new order
                    const newOrder = [];
                    $(moduleContainer).find('.cs-module-item').each(function(index) {
                        newOrder.push({
                            id: $(this).data('module-id'),
                            order: index + 1
                        });
                    });
                    
                    saveModuleOrder(newOrder);
                }
            });
        }
    }

    /**
     * Save module order to database
     */
    function saveModuleOrder(orderData) {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'courscribe_save_module_order',
                course_id: courseId,
                order_data: orderData,
                nonce: moduleNonce
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Module order saved successfully');
                } else {
                    showToast('error', 'Failed to save module order');
                }
            },
            error: function() {
                showToast('error', 'Error saving module order');
            }
        });
    }
  
    /**
     * Initialize Search and Filter functionality
     */
    function initSearchAndFilter() {
        // Module search functionality
        $(document).on('input', `#cs-module-search-${courseId}`, function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                filterModules();
            }, 300);
        });

        // Module filter functionality
        $(document).on('change', `#cs-module-filter-${courseId}, #cs-module-sort-${courseId}`, function() {
            filterModules();
        });

        // Reset filters
        $(document).on('click', `#cs-reset-filters-${courseId}`, function() {
            $(`#cs-module-search-${courseId}`).val('');
            $(`#cs-module-filter-${courseId}`).val('all');
            $(`#cs-module-sort-${courseId}`).val('default');
            filterModules();
        });
    }

    /**
     * Filter and sort modules function
     */
    function filterModules() {
        const moduleContainer = document.getElementById(`cs-modules-container-${courseId}`);
        if (!moduleContainer) return;

        const searchTerm = $(`#cs-module-search-${courseId}`).val().toLowerCase();
        const filterValue = $(`#cs-module-filter-${courseId}`).val();
        const sortValue = $(`#cs-module-sort-${courseId}`).val();
        
        let modules = $(moduleContainer).find('.cs-module-item').toArray();
        
        // Filter modules
        modules = modules.filter(module => {
            const $module = $(module);
            const title = $module.find('input[name*="module_title"]').val() || '';
            const hasLessons = $module.find('.cs-lessons-count').length > 0;
            
            // Search filter
            if (searchTerm && !title.toLowerCase().includes(searchTerm)) {
                return false;
            }
            
            // Category filter
            switch(filterValue) {
                case 'with-lessons':
                    return hasLessons;
                case 'without-lessons':
                    return !hasLessons;
                case 'recent':
                    // Implementation depends on your date tracking
                    return true;
                default:
                    return true;
            }
        });
        
        // Sort modules
        modules.sort((a, b) => {
            const $a = $(a), $b = $(b);
            const titleA = $a.find('input[name*="module_title"]').val() || '';
            const titleB = $b.find('input[name*="module_title"]').val() || '';
            
            switch(sortValue) {
                case 'title-asc':
                    return titleA.localeCompare(titleB);
                case 'title-desc':
                    return titleB.localeCompare(titleA);
                case 'date-asc':
                    return new Date($a.data('created')) - new Date($b.data('created'));
                case 'date-desc':
                    return new Date($b.data('created')) - new Date($a.data('created'));
                default:
                    return 0;
            }
        });
        
        // Hide all modules first
        $(moduleContainer).find('.cs-module-item').hide();
        
        // Show filtered and sorted modules
        modules.forEach(module => {
            $(module).show();
            $(moduleContainer).append(module);
        });
    }

    /**
     * Initialize expandable container functionality
     */
    function initExpandableContainer() {
        $(document).on('click', `#cs-expand-toggle-${courseId}`, function() {
            const $container = $(`#cs-modules-container-${courseId}`);
            const $toggle = $(this);
            const $icon = $toggle.find('i');
            const $text = $toggle.find('.expand-text');
            
            if ($container.hasClass('expanded')) {
                $container.removeClass('expanded');
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $text.text($text.text().replace('Show Less', 'Show All'));
            } else {
                $container.addClass('expanded');
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $text.text($text.text().replace('Show All', 'Show Less'));
            }
        });
    }

    /**
     * Initialize premium file upload functionality
     */
    function initPremiumFileUpload() {
        // Click handler for upload areas
        $(document).on('click', '.cs-premium-upload-area', function() {
            const moduleId = $(this).data('module-id');
            $(`#cs-media-upload-${moduleId}`).click();
        });

        // Drag and drop functionality for upload areas
        $('.cs-premium-upload-area').on({
            dragover: function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            },
            dragleave: function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            },
            drop: function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                const moduleId = $(this).data('module-id');
                handleFileUpload(files, moduleId);
            }
        });

        // File input change handler
        $(document).on('change', '[id^="cs-media-upload-"]', function() {
            const moduleId = $(this).data('module-id');
            const files = this.files;
            handleFileUpload(files, moduleId);
        });
    }

    /**
     * Handle file upload with progress and validation
     */
    function handleFileUpload(files, moduleId) {
        if (files.length === 0) return;
        
        const $uploadArea = $(`#cs-upload-area-${moduleId}`);
        const $progress = $(`#cs-upload-progress-${moduleId}`);
        
        const formData = new FormData();
        formData.append('action', 'courscribe_upload_module_media');
        formData.append('module_id', moduleId);
        formData.append('nonce', moduleNonce);
        
        // Validate files
        let totalSize = 0;
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            totalSize += file.size;
            
            if (file.size > maxSize) {
                showToast('error', `File ${file.name} is too large (max 10MB)`);
                return;
            }
            
            formData.append('media[]', file);
        }
        
        // Show upload progress
        $progress.css('transform', 'translateX(0%)');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        $progress.css('transform', `translateX(${percentComplete - 100}%)`);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $progress.css('transform', 'translateX(-100%)');
                
                if (response.success) {
                    showToast('success', 'Files uploaded successfully');
                    // Refresh the media grid
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast('error', response.data.message || 'Upload failed');
                }
            },
            error: function() {
                $progress.css('transform', 'translateX(-100%)');
                showToast('error', 'Upload failed');
            }
        });
    }

    /**
     * Initialize media management functionality
     */
    function initMediaManagement() {
        // Enhanced media deletion
        $(document).on('click', '.cs-media-delete-btn', function() {
            const mediaUrl = $(this).data('media-url');
            const moduleId = $(this).data('module-id');
            const $mediaCard = $(this).closest('.cs-media-card');
            
            if (confirm('Are you sure you want to delete this media file?')) {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'courscribe_remove_module_media',
                        module_id: moduleId,
                        media_url: mediaUrl,
                        nonce: moduleNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $mediaCard.fadeOut(300, function() {
                                $(this).remove();
                            });
                            showToast('success', 'Media file deleted successfully');
                        } else {
                            showToast('error', response.data.message || 'Failed to delete media');
                        }
                    },
                    error: function() {
                        showToast('error', 'Error deleting media file');
                    }
                });
            }
        });
    }

    /**
     * Initialize module logs functionality
     */
    function initModuleLogs() {
        // Module logs functionality
        $(document).on('click', '.cs-view-logs-btn', function() {
            const moduleId = $(this).data('module-id');
            $(this).addClass('active');
            loadModuleLogs(moduleId);
        });

        // Log filter and sort handlers
        $('#cs-log-filter, #cs-log-sort').on('change', function() {
            const moduleId = $('.cs-view-logs-btn.active').data('module-id');
            if (moduleId) {
                loadModuleLogs(moduleId);
            }
        });

        // Pagination handler
        $(document).on('click', '.cs-page-btn', function() {
            const page = $(this).data('page');
            const moduleId = $('.cs-view-logs-btn.active').data('module-id');
            if (moduleId && page) {
                loadModuleLogs(moduleId, page);
            }
        });
    }

    /**
     * Load module logs with filtering and pagination
     */
    function loadModuleLogs(moduleId, page = 1) {
        const filterValue = $('#cs-log-filter').val() || 'all';
        const sortValue = $('#cs-log-sort').val() || 'date-desc';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'courscribe_get_module_logs',
                module_id: moduleId,
                page: page,
                filter: filterValue,
                sort: sortValue,
                nonce: moduleNonce
            },
            success: function(response) {
                if (response.success) {
                    $('#cs-logs-container').html(response.data.logs);
                    $('#cs-logs-pagination').html(response.data.pagination);
                } else {
                    $('#cs-logs-container').html('<div class="text-center py-4">No logs found</div>');
                }
            },
            error: function() {
                showToast('error', 'Error loading logs');
            }
        });
    }

    /**
     * Initialize view toggle functionality
     */
    function initViewToggle() {
        // Toggle between active and archived modules
        $(document).on('click', '.cs-toggle-btn', function() {
            const view = $(this).data('view');
            const courseId = $(this).data('course-id');
            
            // Update button states
            $(`.cs-toggle-btn[data-course-id="${courseId}"]`).removeClass('active');
            $(this).addClass('active');
            
            // Toggle visibility
            if (view === 'active') {
                $(`#cs-modules-active-${courseId}`).removeClass('d-none');
                $(`#cs-modules-archived-${courseId}`).addClass('d-none');
            } else {
                $(`#cs-modules-active-${courseId}`).addClass('d-none');
                $(`#cs-modules-archived-${courseId}`).removeClass('d-none');
            }
        });
    }

    /**
     * Initialize archive and restore functionality
     */
    function initArchiveRestore() {
        // Archive module
        $(document).on('click', '.cs-btn-archive-new', function() {
            const moduleId = $(this).data('module-id');
            const moduleTitle = $(this).data('module-title');
            
            // Show confirmation modal (if using Bootstrap modals)
            const modalId = $(this).data('bs-target');
            if (modalId) {
                $(modalId).find('.course-name').text(moduleTitle);
                $(modalId).find('#current-module-id').text(moduleId);
            }
        });

        // Restore module
        $(document).on('click', '.cs-btn-restore', function() {
            const moduleId = $(this).data('module-id');
            const moduleTitle = $(this).data('module-title');
            
            if (confirm(`Are you sure you want to restore "${moduleTitle}"?`)) {
                archiveRestoreModule(moduleId, 'restore');
            }
        });

        // Confirm archive button
        $(document).on('click', '.confirm-archive-btn', function() {
            const moduleId = $('#current-module-id').text();
            if (moduleId) {
                archiveRestoreModule(moduleId, 'archive');
                $(this).closest('.modal').modal('hide');
            }
        });
    }

    /**
     * Archive or restore a module
     */
    function archiveRestoreModule(moduleId, action) {
        const actionName = action === 'archive' ? 'courscribe_archive_module' : 'courscribe_restore_module';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: actionName,
                module_id: moduleId,
                course_id: courseId,
                nonce: moduleNonce
            },
            success: function(response) {
                if (response.success) {
                    const message = action === 'archive' ? 'Module archived successfully' : 'Module restored successfully';
                    showToast('success', message);
                    
                    // Remove from current view
                    $(`#cs-module-${moduleId}`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Reload page after short delay to reflect changes
                    setTimeout(() => location.reload(), 1500);
                } else {
                    const message = action === 'archive' ? 'Failed to archive module' : 'Failed to restore module';
                    showToast('error', response.data.message || message);
                }
            },
            error: function() {
                const message = action === 'archive' ? 'Error archiving module' : 'Error restoring module';
                showToast('error', message);
            }
        });
    }

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        const toastClass = type === 'success' ? 'success' : 'error';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const $toast = $(`
            <div class="cs-toast ${toastClass}">
                <i class="fas ${icon} me-2"></i>
                ${message}
            </div>
        `);
        
        $('body').append($toast);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            $toast.fadeOut(300, () => $toast.remove());
        }, 4000);
    }

    /**
     * Tab functionality for module details
     */
    function initTabSystem() {
        $(document).on('click', '.cs-tab-btn', function() {
            const tabId = $(this).data('tab');
            const moduleId = $(this).data('module-id');
            
            // Update button states
            $(`.cs-tab-btn[data-module-id="${moduleId}"]`).removeClass('active');
            $(this).addClass('active');
            
            // Update content visibility
            $(`[id^="cs-tab-"][id*="${moduleId}"]`).removeClass('active');
            $(`#${tabId}`).addClass('active');
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initTabSystem();
    });

    // Expose public API
    window.CourScribeModulesPremium = {
        init: initModulesPremium,
        showToast: showToast,
        filterModules: filterModules,
        loadModuleLogs: loadModuleLogs
    };

})(jQuery);