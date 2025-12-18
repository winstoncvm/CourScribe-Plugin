/**
 * CourScribe Admin Dashboard JavaScript
 * Handles all interactive functionality for the admin dashboard
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize dashboard
    const AdminDashboard = {
        init: function() {
            this.setupEventHandlers();
            this.loadActivityFeed();
            this.setupPerformanceMetrics();
            this.setupRefreshHandlers();
            this.animateStatsCards();
            this.setupQuickAdminMenu();
            this.manageWPAdminMenu();
            console.log('CourScribe Admin Dashboard initialized');
        },

        /**
         * Setup event handlers for dashboard interactions
         */
        setupEventHandlers: function() {
            // Debug toggle
            $('#cs-toggle-debug').on('click', this.toggleDebugInfo);
            
            // Admin action buttons
            $('#cs-clear-cache').on('click', this.clearCache);
            $('#cs-export-data').on('click', this.exportData);
            $('#cs-refresh-activity').on('click', this.refreshActivity);
            
            // Analytics period filter
            $('#cs-analytics-period').on('change', this.updateAnalytics);
            
            // Stat card hover effects
            $('.cs-stat-card').on('mouseenter', this.highlightStatCard);
            $('.cs-stat-card').on('mouseleave', this.unhighlightStatCard);
            
            // Quick admin dropdown
            $('#courscribe-admin-dropdown-toggle-btn').on('click', this.toggleQuickAdminMenu);
            
            // Fallback direct handler
            $(document).on('click', '#courscribe-admin-dropdown-toggle-btn', function(e) {
                console.log('Direct handler triggered');
                e.preventDefault();
                e.stopPropagation();
                $('#courscribe-admin-dropdown-wrapper').toggleClass('active');
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', this.handleOutsideClick);
        },

        /**
         * Toggle debug information visibility
         */
        toggleDebugInfo: function() {
            const $content = $('#cs-debug-content');
            const $chevron = $('#cs-toggle-debug .cs-chevron');
            
            if ($content.is(':visible')) {
                $content.slideUp(300);
                $chevron.removeClass('rotated');
            } else {
                $content.slideDown(300);
                $chevron.addClass('rotated');
            }
        },

        /**
         * Clear system cache
         */
        clearCache: function() {
            if (!confirm('Are you sure you want to clear the CourScribe cache? This action cannot be undone.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Clearing...</span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: CourScribeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_clear_cache',
                    nonce: CourScribeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AdminDashboard.showNotification('success', 'Cache cleared successfully');
                    } else {
                        AdminDashboard.showNotification('error', response.data?.message || 'Failed to clear cache');
                    }
                },
                error: function() {
                    AdminDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Export system data
         */
        exportData: function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Exporting...</span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: CourScribeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_export_data',
                    nonce: CourScribeAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        AdminDashboard.showNotification('success', 'Export completed successfully');
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename || 'courscribe-export.json';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        AdminDashboard.showNotification('error', response.data?.message || 'Export failed');
                    }
                },
                error: function() {
                    AdminDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Load activity feed
         */
        loadActivityFeed: function() {
            $.ajax({
                url: CourScribeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_recent_activity',
                    nonce: CourScribeAdmin.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data.activities) {
                        AdminDashboard.renderActivityFeed(response.data.activities);
                    } else {
                        $('#cs-activity-feed').html('<div class="cs-activity-empty">No recent activity found</div>');
                    }
                },
                error: function() {
                    $('#cs-activity-feed').html('<div class="cs-activity-error">Failed to load activity</div>');
                }
            });
        },

        /**
         * Render activity feed items
         */
        renderActivityFeed: function(activities) {
            const $feed = $('#cs-activity-feed');
            $feed.empty();

            if (activities.length === 0) {
                $feed.html('<div class="cs-activity-empty">No recent activity found</div>');
                return;
            }

            activities.forEach((activity, index) => {
                const $item = $(`
                    <div class="cs-activity-item fade-in" style="animation-delay: ${index * 0.1}s">
                        <div class="cs-activity-icon">
                            <i class="fas ${AdminDashboard.getActivityIcon(activity.action)}"></i>
                        </div>
                        <div class="cs-activity-content">
                            <div class="cs-activity-title">${AdminDashboard.formatActivityTitle(activity)}</div>
                            <div class="cs-activity-meta">
                                ${activity.user_name} • ${AdminDashboard.formatTimeAgo(activity.timestamp)}
                            </div>
                        </div>
                    </div>
                `);
                $feed.append($item);
            });
        },

        /**
         * Get icon for activity type
         */
        getActivityIcon: function(action) {
            const iconMap = {
                'create': 'fa-plus',
                'update': 'fa-edit',
                'delete': 'fa-trash',
                'archive': 'fa-archive',
                'restore': 'fa-undo',
                'ai_suggestion': 'fa-magic'
            };
            return iconMap[action] || 'fa-circle';
        },

        /**
         * Format activity title
         */
        formatActivityTitle: function(activity) {
            const actionMap = {
                'create': 'Created',
                'update': 'Updated',
                'delete': 'Deleted',
                'archive': 'Archived',
                'restore': 'Restored',
                'ai_suggestion': 'Generated AI suggestion for'
            };
            
            const actionText = actionMap[activity.action] || 'Modified';
            const itemType = activity.item_type || 'item';
            const itemTitle = activity.item_title || `${itemType} #${activity.item_id}`;
            
            return `${actionText} ${itemType}: ${itemTitle}`;
        },

        /**
         * Format timestamp as time ago
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
         * Refresh activity feed
         */
        refreshActivity: function() {
            const $btn = $(this);
            $btn.find('i').addClass('fa-spin');
            AdminDashboard.loadActivityFeed();
            
            setTimeout(() => {
                $btn.find('i').removeClass('fa-spin');
            }, 1000);
        },

        /**
         * Setup performance metrics
         */
        setupPerformanceMetrics: function() {
            // Calculate and display page load time
            const loadTime = (performance.now() / 1000).toFixed(2);
            $('#cs-page-load-time').text(`${loadTime}s`);
            
            // Animate the load time bar
            const maxLoadTime = 5; // seconds
            const percentage = Math.min(100, (loadTime / maxLoadTime) * 100);
            $('#cs-load-time-bar').css('width', `${percentage}%`);
            
            // Update metrics every 30 seconds
            setInterval(this.updatePerformanceMetrics, 30000);
        },

        /**
         * Update performance metrics
         */
        updatePerformanceMetrics: function() {
            // This would typically fetch real-time metrics
            // For now, we'll just update the display
            const currentTime = (performance.now() / 1000).toFixed(2);
            $('#cs-page-load-time').text(`${currentTime}s`);
        },

        /**
         * Update analytics based on selected period
         */
        updateAnalytics: function() {
            const period = $(this).val();
            const $widget = $('.cs-analytics-widget');
            
            $widget.addClass('cs-loading');
            
            $.ajax({
                url: CourScribeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_analytics',
                    period: period,
                    nonce: CourScribeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AdminDashboard.updateAnalyticsDisplay(response.data);
                    }
                },
                complete: function() {
                    $widget.removeClass('cs-loading');
                }
            });
        },

        /**
         * Update analytics display
         */
        updateAnalyticsDisplay: function(data) {
            $('.cs-analytics-item').each(function() {
                const $item = $(this);
                const chart = $item.find('.cs-analytics-chart').data('chart');
                
                if (data[chart]) {
                    $item.find('.cs-analytics-number').text(data[chart].total);
                    // Animate chart bars
                    $item.find('.cs-analytics-chart').css('width', `${data[chart].percentage || 50}%`);
                }
            });
        },

        /**
         * Setup refresh handlers
         */
        setupRefreshHandlers: function() {
            // Auto-refresh activity feed every 2 minutes
            setInterval(() => {
                this.loadActivityFeed();
            }, 120000);
        },

        /**
         * Animate stats cards on load
         */
        animateStatsCards: function() {
            $('.cs-stat-card').each(function(index) {
                $(this).css('animation-delay', `${index * 0.1}s`).addClass('fade-in');
            });
        },

        /**
         * Highlight stat card on hover
         */
        highlightStatCard: function() {
            $(this).addClass('pulse');
        },

        /**
         * Remove highlight from stat card
         */
        unhighlightStatCard: function() {
            $(this).removeClass('pulse');
        },

        /**
         * Show notification
         */
        showNotification: function(type, message) {
            const $notification = $(`
                <div class="cs-notification cs-notification-${type}">
                    <div class="cs-notification-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="cs-notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);

            // Add notification styles if not already present
            if (!$('#cs-notification-styles').length) {
                $('head').append(`
                    <style id="cs-notification-styles">
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
                        }
                        .cs-notification-success {
                            border-left-color: #28a745;
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
        /**
         * Setup quick admin dropdown menu
         */
        setupQuickAdminMenu: function() {
            const $wrapper = $('#courscribe-admin-dropdown-wrapper');
            const $toggle = $('#courscribe-admin-dropdown-toggle-btn');
            const $menu = $('#courscribe-admin-dropdown-menu-panel');
            
            // Debug logging
            console.log('Quick Admin Menu Setup:', {
                wrapper: $wrapper.length,
                toggle: $toggle.length,
                menu: $menu.length
            });
            
            // Store reference for outside click handling
            this.$adminDropdown = $wrapper;
        },
        
        /**
         * Toggle quick admin menu dropdown
         */
        toggleQuickAdminMenu: function(e) {
            console.log('Toggle Quick Admin Menu clicked');
            e.preventDefault();
            e.stopPropagation();
            
            const $wrapper = $('#courscribe-admin-dropdown-wrapper');
            console.log('Wrapper found:', $wrapper.length);
            $wrapper.toggleClass('active');
            console.log('Active class toggled, has active:', $wrapper.hasClass('active'));
        },
        
        /**
         * Handle outside click to close dropdown
         */
        handleOutsideClick: function(e) {
            const $wrapper = $('#courscribe-admin-dropdown-wrapper');
            if ($wrapper.hasClass('active') && !$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                $wrapper.removeClass('active');
            }
        },
        
        /**
         * Manage WordPress admin menu collapse/expand
         */
        manageWPAdminMenu: function() {
            // Store original admin menu state
            this.originalMenuState = this.isWPAdminMenuCollapsed();
            
            // Collapse WordPress admin menu when on CourScribe pages
            this.collapseWPAdminMenu();
            
            // Set up beforeunload to restore menu state
            $(window).on('beforeunload', () => {
                this.restoreWPAdminMenuState();
            });
            
            // Handle navigation away from CourScribe pages
            $(document).on('click', 'a[href]:not([href*="courscribe"]):not([href^="#"]):not([target="_blank"])', () => {
                this.restoreWPAdminMenuState();
            });
        },
        
        /**
         * Check if WordPress admin menu is collapsed
         */
        isWPAdminMenuCollapsed: function() {
            return $('body').hasClass('folded');
        },
        
        /**
         * Collapse WordPress admin menu
         */
        collapseWPAdminMenu: function() {
            if (!this.isWPAdminMenuCollapsed()) {
                // Trigger the collapse by clicking the collapse button
                $('#collapse-menu').click();
                // Or add the class directly if the button doesn't exist
                if (!$('body').hasClass('folded')) {
                    $('body').addClass('folded');
                }
            }
        },
        
        /**
         * Restore WordPress admin menu to original state
         */
        restoreWPAdminMenuState: function() {
            const isCurrentlyCollapsed = this.isWPAdminMenuCollapsed();
            
            // If original state was expanded and current is collapsed, expand it
            if (!this.originalMenuState && isCurrentlyCollapsed) {
                $('#collapse-menu').click();
                // Or remove the class directly if the button doesn't exist
                $('body').removeClass('folded');
            }
            // If original state was collapsed and current is expanded, collapse it
            else if (this.originalMenuState && !isCurrentlyCollapsed) {
                $('#collapse-menu').click();
                // Or add the class directly if the button doesn't exist
                $('body').addClass('folded');
            }
        }
    };

    // Initialize dashboard
    AdminDashboard.init();

    // Expose for global access
    window.CourScribeAdminDashboard = AdminDashboard;
});

/**
 * AJAX action handlers for dashboard functionality
 */

// Handle cache clearing
jQuery(document).ready(function($) {
    if (typeof CourScribeAdmin !== 'undefined') {
        // Add action handlers to WordPress AJAX
        $(document).on('wp_ajax_courscribe_clear_cache', function() {
            // This would be handled server-side
        });
    }
});