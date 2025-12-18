/**
 * CourScribe Curriculums Dashboard JavaScript
 * Handles all interactive functionality for the curriculums dashboard
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize curriculums dashboard
    const CurriculumsDashboard = {
        init: function() {
            this.setupEventHandlers();
            this.setupFiltering();
            this.setupBulkOperations();
            this.setupViewSwitching();
            this.setupDropdownMenus();
            this.animateCurriculumCards();
            console.log('CourScribe Curriculums Dashboard initialized');
        },

        /**
         * Setup event handlers for dashboard interactions
         */
        setupEventHandlers: function() {
            // Search functionality
            $('#cs-curriculum-search').on('input', this.handleSearch);
            
            // Filter dropdowns
            $('#cs-filter-studio, #cs-filter-status, #cs-filter-date, #cs-filter-size, #cs-sort-order').on('change', this.applyFilters);
            
            // Reset filters
            $('#cs-reset-filters').on('click', this.resetFilters);
            
            // Bulk operations
            $('#cs-bulk-export').on('click', this.bulkExport);
            $('#cs-bulk-archive').on('click', this.bulkArchive);
            $('#cs-bulk-duplicate').on('click', this.bulkDuplicate);
            $('#cs-bulk-ai-enhance').on('click', this.bulkAIEnhance);
            
            // Individual curriculum actions
            $(document).on('click', '.cs-duplicate-curriculum', this.duplicateCurriculum);
            $(document).on('click', '.cs-ai-enhance', this.aiEnhanceCurriculum);
            $(document).on('click', '.cs-archive-curriculum', this.archiveCurriculum);
            
            // Refresh activity
            $('#cs-refresh-activity').on('click', this.refreshActivity);
            
            // Curriculum card selections
            $(document).on('change', '.cs-curriculum-checkbox', this.updateSelectionCount);
            
            // Select all functionality
            $(document).on('change', '#cs-select-all', this.selectAllCurriculums);
        },

        /**
         * Setup filtering functionality
         */
        setupFiltering: function() {
            this.filters = {
                search: '',
                studio: '',
                status: '',
                date: '',
                size: '',
                sort: 'date_desc'
            };
        },

        /**
         * Handle search input
         */
        handleSearch: function() {
            const query = $(this).val().toLowerCase();
            CurriculumsDashboard.filters.search = query;
            CurriculumsDashboard.applyFilters();
        },

        /**
         * Apply all active filters
         */
        applyFilters: function() {
            // Update filter values
            CurriculumsDashboard.filters.studio = $('#cs-filter-studio').val();
            CurriculumsDashboard.filters.status = $('#cs-filter-status').val();
            CurriculumsDashboard.filters.date = $('#cs-filter-date').val();
            CurriculumsDashboard.filters.size = $('#cs-filter-size').val();
            CurriculumsDashboard.filters.sort = $('#cs-sort-order').val();

            let visibleCount = 0;
            const $cards = $('.cs-curriculum-card');

            $cards.each(function() {
                const $card = $(this);
                const cardData = CurriculumsDashboard.getCardData($card);
                
                let show = true;

                // Apply search filter
                if (CurriculumsDashboard.filters.search) {
                    const searchTerms = [
                        cardData.title.toLowerCase(),
                        cardData.topic.toLowerCase(),
                        cardData.goal.toLowerCase(),
                        cardData.studio.toLowerCase()
                    ].join(' ');
                    
                    if (!searchTerms.includes(CurriculumsDashboard.filters.search)) {
                        show = false;
                    }
                }

                // Apply studio filter
                if (CurriculumsDashboard.filters.studio && cardData.studioId !== CurriculumsDashboard.filters.studio) {
                    show = false;
                }

                // Apply status filter
                if (CurriculumsDashboard.filters.status && cardData.status !== CurriculumsDashboard.filters.status) {
                    show = false;
                }

                // Apply date filter
                if (CurriculumsDashboard.filters.date) {
                    if (!CurriculumsDashboard.matchesDateFilter(cardData.createdDate, CurriculumsDashboard.filters.date)) {
                        show = false;
                    }
                }

                // Apply size filter
                if (CurriculumsDashboard.filters.size) {
                    if (!CurriculumsDashboard.matchesSizeFilter(cardData.courseCount, CurriculumsDashboard.filters.size)) {
                        show = false;
                    }
                }

                if (show) {
                    $card.show().addClass('fade-in-up');
                    visibleCount++;
                } else {
                    $card.hide();
                }
            });

            // Sort visible cards
            CurriculumsDashboard.sortCurriculums();

            // Show/hide empty state
            if (visibleCount === 0) {
                $('#cs-empty-state').show();
                $('#cs-curriculum-grid').hide();
            } else {
                $('#cs-empty-state').hide();
                $('#cs-curriculum-grid').show();
            }

            // Update selection count
            CurriculumsDashboard.updateSelectionCount();
        },

        /**
         * Get card data for filtering
         */
        getCardData: function($card) {
            return {
                title: $card.find('.cs-card-title').text().trim(),
                topic: $card.find('.cs-card-topic').text().trim(),
                goal: $card.find('.cs-card-goal').text().trim(),
                studio: $card.find('.cs-meta-item:first').text().trim(),
                studioId: $card.data('studio-id'),
                status: $card.data('status'),
                courseCount: parseInt($card.data('course-count')) || 0,
                createdDate: $card.find('.cs-meta-item:last').text().trim()
            };
        },

        /**
         * Check if date matches filter
         */
        matchesDateFilter: function(dateStr, filter) {
            const now = new Date();
            const cardDate = new Date(dateStr);
            
            switch (filter) {
                case 'today':
                    return cardDate.toDateString() === now.toDateString();
                case 'week':
                    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                    return cardDate >= weekAgo;
                case 'month':
                    const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                    return cardDate >= monthAgo;
                case 'quarter':
                    const quarterAgo = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000);
                    return cardDate >= quarterAgo;
                case 'year':
                    const yearAgo = new Date(now.getTime() - 365 * 24 * 60 * 60 * 1000);
                    return cardDate >= yearAgo;
                default:
                    return true;
            }
        },

        /**
         * Check if course count matches size filter
         */
        matchesSizeFilter: function(courseCount, filter) {
            switch (filter) {
                case 'small':
                    return courseCount >= 1 && courseCount <= 5;
                case 'medium':
                    return courseCount >= 6 && courseCount <= 15;
                case 'large':
                    return courseCount >= 16;
                default:
                    return true;
            }
        },

        /**
         * Sort curriculum cards
         */
        sortCurriculums: function() {
            const $grid = $('#cs-curriculum-grid');
            const $cards = $grid.find('.cs-curriculum-card:visible').get();

            $cards.sort(function(a, b) {
                const aData = CurriculumsDashboard.getCardData($(a));
                const bData = CurriculumsDashboard.getCardData($(b));

                switch (CurriculumsDashboard.filters.sort) {
                    case 'title_asc':
                        return aData.title.localeCompare(bData.title);
                    case 'title_desc':
                        return bData.title.localeCompare(aData.title);
                    case 'courses_desc':
                        return bData.courseCount - aData.courseCount;
                    case 'courses_asc':
                        return aData.courseCount - bData.courseCount;
                    case 'date_asc':
                        return new Date(aData.createdDate) - new Date(bData.createdDate);
                    case 'date_desc':
                    default:
                        return new Date(bData.createdDate) - new Date(aData.createdDate);
                }
            });

            $.each($cards, function(index, card) {
                $grid.append(card);
            });
        },

        /**
         * Reset all filters
         */
        resetFilters: function() {
            $('#cs-curriculum-search').val('');
            $('#cs-filter-studio, #cs-filter-status, #cs-filter-date, #cs-filter-size').val('');
            $('#cs-sort-order').val('date_desc');
            
            CurriculumsDashboard.filters = {
                search: '',
                studio: '',
                status: '',
                date: '',
                size: '',
                sort: 'date_desc'
            };

            $('.cs-curriculum-card').show().addClass('fade-in-up');
            $('#cs-empty-state').hide();
            $('#cs-curriculum-grid').show();
            
            CurriculumsDashboard.updateSelectionCount();
        },

        /**
         * Setup bulk operations
         */
        setupBulkOperations: function() {
            this.selectedCurriculums = new Set();
        },

        /**
         * Update selection count
         */
        updateSelectionCount: function() {
            const checkedBoxes = $('.cs-curriculum-checkbox:checked:visible');
            const count = checkedBoxes.length;
            
            $('#cs-selected-count').text(count);
            
            // Enable/disable bulk actions
            const $bulkActions = $('.cs-bulk-btn');
            if (count > 0) {
                $bulkActions.prop('disabled', false).removeClass('disabled');
            } else {
                $bulkActions.prop('disabled', true).addClass('disabled');
            }
        },

        /**
         * Select all visible curriculums
         */
        selectAllCurriculums: function() {
            const isChecked = $(this).is(':checked');
            $('.cs-curriculum-checkbox:visible').prop('checked', isChecked);
            CurriculumsDashboard.updateSelectionCount();
        },

        /**
         * Get selected curriculum IDs
         */
        getSelectedCurriculumIds: function() {
            return $('.cs-curriculum-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
        },

        /**
         * Bulk export curriculums
         */
        bulkExport: function() {
            const selectedIds = CurriculumsDashboard.getSelectedCurriculumIds();
            
            if (selectedIds.length === 0) {
                CurriculumsDashboard.showNotification('warning', 'Please select curriculums to export');
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_bulk_export_curriculums',
                    curriculum_ids: selectedIds,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        CurriculumsDashboard.showNotification('success', 'Export completed successfully');
                        
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename || 'curriculums-export.json';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'Export failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Bulk archive curriculums
         */
        bulkArchive: function() {
            const selectedIds = CurriculumsDashboard.getSelectedCurriculumIds();
            
            if (selectedIds.length === 0) {
                CurriculumsDashboard.showNotification('warning', 'Please select curriculums to archive');
                return;
            }

            if (!confirm(`Are you sure you want to archive ${selectedIds.length} curriculum(s)? This action can be undone.`)) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Archiving...').prop('disabled', true);

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_bulk_archive_curriculums',
                    curriculum_ids: selectedIds,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CurriculumsDashboard.showNotification('success', `${selectedIds.length} curriculum(s) archived successfully`);
                        
                        // Remove archived cards from view
                        selectedIds.forEach(id => {
                            $(`.cs-curriculum-card[data-curriculum-id="${id}"]`).fadeOut(300, function() {
                                $(this).remove();
                                CurriculumsDashboard.updateSelectionCount();
                            });
                        });
                        
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'Archive failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Bulk duplicate curriculums
         */
        bulkDuplicate: function() {
            const selectedIds = CurriculumsDashboard.getSelectedCurriculumIds();
            
            if (selectedIds.length === 0) {
                CurriculumsDashboard.showNotification('warning', 'Please select curriculums to duplicate');
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Duplicating...').prop('disabled', true);

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_bulk_duplicate_curriculums',
                    curriculum_ids: selectedIds,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CurriculumsDashboard.showNotification('success', `${selectedIds.length} curriculum(s) duplicated successfully`);
                        
                        // Refresh the page to show duplicated curriculums
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                        
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'Duplication failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Bulk AI enhance curriculums
         */
        bulkAIEnhance: function() {
            const selectedIds = CurriculumsDashboard.getSelectedCurriculumIds();
            
            if (selectedIds.length === 0) {
                CurriculumsDashboard.showNotification('warning', 'Please select curriculums to enhance');
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Enhancing...').prop('disabled', true);

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_bulk_ai_enhance_curriculums',
                    curriculum_ids: selectedIds,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CurriculumsDashboard.showNotification('success', `AI enhancement completed for ${selectedIds.length} curriculum(s)`);
                        
                        // Refresh the page to show enhanced content
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                        
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'AI enhancement failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Duplicate single curriculum
         */
        duplicateCurriculum: function(e) {
            e.preventDefault();
            const curriculumId = $(this).data('id');
            
            if (!confirm('Are you sure you want to duplicate this curriculum?')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Duplicating...');

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_duplicate_curriculum',
                    curriculum_id: curriculumId,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CurriculumsDashboard.showNotification('success', 'Curriculum duplicated successfully');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'Duplication failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                }
            });
        },

        /**
         * AI enhance single curriculum
         */
        aiEnhanceCurriculum: function(e) {
            e.preventDefault();
            const curriculumId = $(this).data('id');
            
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Enhancing...');

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_ai_enhance_curriculum',
                    curriculum_id: curriculumId,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CurriculumsDashboard.showNotification('success', 'AI enhancement completed');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'AI enhancement failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                }
            });
        },

        /**
         * Archive single curriculum
         */
        archiveCurriculum: function(e) {
            e.preventDefault();
            const curriculumId = $(this).data('id');
            
            if (!confirm('Are you sure you want to archive this curriculum? This action can be undone.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Archiving...');

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_archive_curriculum',
                    curriculum_id: curriculumId,
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CurriculumsDashboard.showNotification('success', 'Curriculum archived successfully');
                        
                        // Remove the card from view
                        $(`.cs-curriculum-card[data-curriculum-id="${curriculumId}"]`).fadeOut(300, function() {
                            $(this).remove();
                            CurriculumsDashboard.updateSelectionCount();
                        });
                        
                    } else {
                        CurriculumsDashboard.showNotification('error', response.data?.message || 'Archive failed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                }
            });
        },

        /**
         * Setup view switching (grid, list, table)
         */
        setupViewSwitching: function() {
            $('.cs-view-btn').on('click', function() {
                const view = $(this).data('view');
                
                // Update active button
                $('.cs-view-btn').removeClass('active');
                $(this).addClass('active');
                
                // Switch view (for now just grid is implemented)
                switch (view) {
                    case 'grid':
                        $('#cs-curriculum-grid').removeClass('cs-list-view cs-table-view').addClass('cs-grid-view');
                        break;
                    case 'list':
                        $('#cs-curriculum-grid').removeClass('cs-grid-view cs-table-view').addClass('cs-list-view');
                        break;
                    case 'table':
                        $('#cs-curriculum-grid').removeClass('cs-grid-view cs-list-view').addClass('cs-table-view');
                        break;
                }
            });
        },

        /**
         * Setup dropdown menus
         */
        setupDropdownMenus: function() {
            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.cs-dropdown').length) {
                    $('.cs-dropdown-menu').hide();
                }
            });

            // Toggle dropdown menus
            $(document).on('click', '.cs-dropdown-btn', function(e) {
                e.stopPropagation();
                const $menu = $(this).siblings('.cs-dropdown-menu');
                $('.cs-dropdown-menu').not($menu).hide();
                $menu.toggle();
            });
        },

        /**
         * Refresh activity feed
         */
        refreshActivity: function() {
            const $btn = $(this);
            const $icon = $btn.find('i');
            
            $icon.addClass('fa-spin');

            $.ajax({
                url: CourScribeCurriculums.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_curriculum_activity',
                    nonce: CourScribeCurriculums.nonce
                },
                success: function(response) {
                    if (response.success && response.data.activities) {
                        CurriculumsDashboard.renderActivityFeed(response.data.activities);
                        CurriculumsDashboard.showNotification('success', 'Activity feed refreshed');
                    }
                },
                error: function() {
                    CurriculumsDashboard.showNotification('error', 'Failed to refresh activity');
                },
                complete: function() {
                    setTimeout(() => {
                        $icon.removeClass('fa-spin');
                    }, 1000);
                }
            });
        },

        /**
         * Render activity feed
         */
        renderActivityFeed: function(activities) {
            const $feed = $('#cs-curriculum-activity');
            $feed.empty();

            if (activities.length === 0) {
                $feed.html('<div class="cs-activity-empty">No recent activity found</div>');
                return;
            }

            activities.forEach((activity, index) => {
                const $item = $(`
                    <div class="cs-activity-item fade-in" style="animation-delay: ${index * 0.1}s">
                        <div class="cs-activity-icon">
                            <i class="fas ${CurriculumsDashboard.getActivityIcon(activity.action)}"></i>
                        </div>
                        <div class="cs-activity-content">
                            <div class="cs-activity-title">${activity.title}</div>
                            <div class="cs-activity-meta">
                                <span class="cs-activity-user">${activity.user_name}</span>
                                <span class="cs-activity-time">${CurriculumsDashboard.formatTimeAgo(activity.timestamp)}</span>
                            </div>
                        </div>
                    </div>
                `);
                $feed.append($item);
            });
        },

        /**
         * Get activity icon
         */
        getActivityIcon: function(action) {
            const iconMap = {
                'create': 'fa-plus-circle',
                'update': 'fa-edit',
                'delete': 'fa-trash',
                'archive': 'fa-archive',
                'restore': 'fa-undo',
                'ai_suggestion': 'fa-magic'
            };
            return iconMap[action] || 'fa-circle';
        },

        /**
         * Format time ago
         */
        formatTimeAgo: function(timestamp) {
            const now = new Date();
            const activityTime = new Date(timestamp);
            const diffMs = now - activityTime;
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffHours / 24);

            if (diffDays > 0) {
                return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            } else if (diffHours > 0) {
                return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            } else {
                const diffMinutes = Math.floor(diffMs / (1000 * 60));
                return `${Math.max(1, diffMinutes)} minute${diffMinutes > 1 ? 's' : ''} ago`;
            }
        },

        /**
         * Animate curriculum cards on load
         */
        animateCurriculumCards: function() {
            $('.cs-curriculum-card').each(function(index) {
                $(this).css('animation-delay', `${index * 0.1}s`).addClass('fade-in-up');
            });
        },

        /**
         * Show notification
         */
        showNotification: function(type, message) {
            const $notification = $(`
                <div class="cs-notification cs-notification-${type}">
                    <div class="cs-notification-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="cs-notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);

            // Add notification styles if not already present
            if (!$('#cs-curriculum-notification-styles').length) {
                $('head').append(`
                    <style id="cs-curriculum-notification-styles">
                        .cs-notification {
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            background: white;
                            border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                            padding: 15px 20px;
                            z-index: 10000;
                            border-left: 4px solid;
                            animation: slideInRight 0.3s ease;
                            max-width: 400px;
                        }
                        .cs-notification-success {
                            border-left-color: #28a745;
                        }
                        .cs-notification-warning {
                            border-left-color: #ffc107;
                        }
                        .cs-notification-error {
                            border-left-color: #dc3545;
                        }
                        .cs-notification-content {
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        }
                        .cs-notification-close {
                            background: none;
                            border: none;
                            cursor: pointer;
                            padding: 5px;
                            margin-left: 10px;
                            opacity: 0.6;
                        }
                        .cs-notification-close:hover {
                            opacity: 1;
                        }
                        @keyframes slideInRight {
                            from {
                                transform: translateX(100%);
                                opacity: 0;
                            }
                            to {
                                transform: translateX(0);
                                opacity: 1;
                            }
                        }
                    </style>
                `);
            }

            $('body').append($notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);

            // Manual close
            $notification.find('.cs-notification-close').on('click', () => {
                $notification.fadeOut(() => $notification.remove());
            });
        }
    };

    // Initialize curriculums dashboard
    CurriculumsDashboard.init();

    // Expose for global access
    window.CourScribeCurriculumsDashboard = CurriculumsDashboard;
});