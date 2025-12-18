/**
 * CourScribe Studios Dashboard JavaScript
 * Handles all interactive functionality for the studios management dashboard
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize studios dashboard
    const StudiosDashboard = {
        init: function() {
            this.setupEventHandlers();
            this.setupViewSwitching();
            this.setupDropdownMenus();
            this.setupManagementActions();
            this.animateStudioCards();
            console.log('CourScribe Studios Dashboard initialized');
        },

        /**
         * Setup event handlers for dashboard interactions
         */
        setupEventHandlers: function() {
            // Site management actions
            $('#cs-clear-cache').on('click', this.clearCache);
            $('#cs-export-all-data').on('click', this.exportAllData);
            $('#cs-system-health').on('click', this.runSystemHealthCheck);
            
            // Studio management actions
            $('#cs-archive-inactive').on('click', this.archiveInactiveStudios);
            $('#cs-bulk-operations').on('click', this.showBulkOperations);
            
            // Individual studio actions
            $(document).on('click', '.cs-view-logs', this.viewStudioLogs);
            $(document).on('click', '.cs-manage-users', this.manageStudioUsers);
            
            // Activity refresh
            $('#cs-refresh-activity').on('click', this.refreshActivity);
            
            // Analytics period change
            $('#cs-analytics-period').on('change', this.updateAnalytics);
        },

        /**
         * Setup view switching (grid, list)
         */
        setupViewSwitching: function() {
            $('.cs-view-btn').on('click', function() {
                const view = $(this).data('view');
                
                // Update active button
                $('.cs-view-btn').removeClass('active');
                $(this).addClass('active');
                
                // Switch view
                switch (view) {
                    case 'grid':
                        $('#cs-studios-grid').removeClass('cs-list-view').addClass('cs-grid-view');
                        break;
                    case 'list':
                        $('#cs-studios-grid').removeClass('cs-grid-view').addClass('cs-list-view');
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
         * Setup management actions
         */
        setupManagementActions: function() {
            // Initialize health status checks
            this.checkSystemHealth();
            
            // Setup auto-refresh for activity
            setInterval(() => {
                this.refreshActivity();
            }, 300000); // 5 minutes
        },

        /**
         * Clear plugin cache
         */
        clearCache: function() {
            if (!confirm('Are you sure you want to clear the CourScribe cache? This will temporarily slow down the plugin while the cache rebuilds.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Clearing...</span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_clear_cache',
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success) {
                        StudiosDashboard.showNotification('success', 'Cache cleared successfully');
                        // Refresh the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        StudiosDashboard.showNotification('error', response.data?.message || 'Failed to clear cache');
                    }
                },
                error: function() {
                    StudiosDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Export all site data
         */
        exportAllData: function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Exporting...</span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_export_all_data',
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        StudiosDashboard.showNotification('success', 'Export completed successfully');
                        
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename || 'courscribe-site-export.json';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        StudiosDashboard.showNotification('error', response.data?.message || 'Export failed');
                    }
                },
                error: function() {
                    StudiosDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Run system health check
         */
        runSystemHealthCheck: function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Checking...</span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_system_health_check',
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success) {
                        StudiosDashboard.showHealthResults(response.data);
                        StudiosDashboard.showNotification('success', 'Health check completed');
                    } else {
                        StudiosDashboard.showNotification('error', response.data?.message || 'Health check failed');
                    }
                },
                error: function() {
                    StudiosDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Archive inactive studios
         */
        archiveInactiveStudios: function() {
            if (!confirm('Are you sure you want to archive all studios with no activity in the last 90 days? This action can be undone.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span>Archiving...</span>');
            $btn.prop('disabled', true);

            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_archive_inactive_studios',
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success) {
                        StudiosDashboard.showNotification('success', `${response.data.archived_count} inactive studios archived`);
                        
                        // Refresh the page to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        StudiosDashboard.showNotification('error', response.data?.message || 'Archive operation failed');
                    }
                },
                error: function() {
                    StudiosDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Show bulk operations modal
         */
        showBulkOperations: function() {
            // Create modal for bulk operations
            const modalHtml = `
                <div class="cs-modal-overlay" id="cs-bulk-modal">
                    <div class="cs-modal">
                        <div class="cs-modal-header">
                            <h3>Bulk Studio Operations</h3>
                            <button type="button" class="cs-modal-close">&times;</button>
                        </div>
                        <div class="cs-modal-content">
                            <div class="cs-bulk-options">
                                <button type="button" class="cs-bulk-option" data-action="export">
                                    <i class="fas fa-download"></i>
                                    <span>Export All Studios</span>
                                </button>
                                <button type="button" class="cs-bulk-option" data-action="backup">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Create Backup</span>
                                </button>
                                <button type="button" class="cs-bulk-option" data-action="optimize">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Optimize Database</span>
                                </button>
                                <button type="button" class="cs-bulk-option" data-action="maintenance">
                                    <i class="fas fa-tools"></i>
                                    <span>Run Maintenance</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#cs-bulk-modal').fadeIn();

            // Handle modal interactions
            $('.cs-modal-close, .cs-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('#cs-bulk-modal').fadeOut(() => $('#cs-bulk-modal').remove());
                }
            });

            $('.cs-bulk-option').on('click', function() {
                const action = $(this).data('action');
                StudiosDashboard.executeBulkAction(action);
                $('#cs-bulk-modal').fadeOut(() => $('#cs-bulk-modal').remove());
            });
        },

        /**
         * Execute bulk action
         */
        executeBulkAction: function(action) {
            const actions = {
                'export': 'Export all studios data',
                'backup': 'Create full site backup',
                'optimize': 'Optimize database tables',
                'maintenance': 'Run maintenance tasks'
            };

            if (!confirm(`Are you sure you want to ${actions[action]}?`)) {
                return;
            }

            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: `courscribe_bulk_${action}`,
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success) {
                        StudiosDashboard.showNotification('success', `${actions[action]} completed successfully`);
                    } else {
                        StudiosDashboard.showNotification('error', response.data?.message || `${actions[action]} failed`);
                    }
                },
                error: function() {
                    StudiosDashboard.showNotification('error', 'Network error occurred');
                }
            });
        },

        /**
         * View studio logs
         */
        viewStudioLogs: function(e) {
            e.preventDefault();
            const studioId = $(this).data('id');
            
            // Create logs modal
            const modalHtml = `
                <div class="cs-modal-overlay" id="cs-logs-modal">
                    <div class="cs-modal cs-modal-large">
                        <div class="cs-modal-header">
                            <h3>Studio Activity Logs</h3>
                            <button type="button" class="cs-modal-close">&times;</button>
                        </div>
                        <div class="cs-modal-content">
                            <div class="cs-logs-container" id="cs-logs-content">
                                <div class="cs-loading">Loading logs...</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#cs-logs-modal').fadeIn();

            // Load logs
            this.loadStudioLogs(studioId);

            // Handle modal close
            $('.cs-modal-close, .cs-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('#cs-logs-modal').fadeOut(() => $('#cs-logs-modal').remove());
                }
            });
        },

        /**
         * Load studio logs
         */
        loadStudioLogs: function(studioId) {
            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_studio_logs',
                    studio_id: studioId,
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '<div class="cs-logs-table"><table><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead><tbody>';
                        
                        response.data.forEach(function(log) {
                            html += `<tr>
                                <td>${log.timestamp}</td>
                                <td>${log.user}</td>
                                <td>${log.action}</td>
                                <td>${log.details}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div>';
                        $('#cs-logs-content').html(html);
                    } else {
                        $('#cs-logs-content').html('<div class="cs-no-logs">No recent activity logs found for this studio.</div>');
                    }
                },
                error: function() {
                    $('#cs-logs-content').html('<div class="cs-error">Failed to load activity logs.</div>');
                }
            });
        },

        /**
         * Manage studio users
         */
        manageStudioUsers: function(e) {
            e.preventDefault();
            const studioId = $(this).data('id');
            
            // Redirect to user management page
            window.open(`${CourScribeStudios.ajaxUrl.replace('admin-ajax.php', '')}edit.php?post_type=crscribe_studio&page=studio_users&studio_id=${studioId}`, '_blank');
        },

        /**
         * Refresh activity feed
         */
        refreshActivity: function() {
            const $btn = $(this);
            const $icon = $btn.find('i');
            
            $icon.addClass('fa-spin');

            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_studio_activity',
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success && response.data.activities) {
                        StudiosDashboard.renderActivityFeed(response.data.activities);
                        StudiosDashboard.showNotification('success', 'Activity feed refreshed');
                    }
                },
                error: function() {
                    StudiosDashboard.showNotification('error', 'Failed to refresh activity');
                },
                complete: function() {
                    setTimeout(() => {
                        $icon.removeClass('fa-spin');
                    }, 1000);
                }
            });
        },

        /**
         * Update analytics
         */
        updateAnalytics: function() {
            const period = $(this).val();
            const $widget = $('.cs-analytics-widget');
            
            $widget.addClass('cs-loading');
            
            $.ajax({
                url: CourScribeStudios.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_studio_analytics',
                    period: period,
                    nonce: CourScribeStudios.nonce
                },
                success: function(response) {
                    if (response.success) {
                        StudiosDashboard.updateAnalyticsDisplay(response.data);
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
                    $item.find('.cs-analytics-chart').css('width', `${data[chart].percentage || 50}%`);
                }
            });
        },

        /**
         * Render activity feed
         */
        renderActivityFeed: function(activities) {
            const $feed = $('#cs-studio-activity');
            $feed.empty();

            if (activities.length === 0) {
                $feed.html('<div class="cs-activity-empty">No recent activity found</div>');
                return;
            }

            activities.forEach((activity, index) => {
                const $item = $(`
                    <div class="cs-activity-item fade-in" style="animation-delay: ${index * 0.1}s">
                        <div class="cs-activity-icon">
                            <i class="fas ${StudiosDashboard.getActivityIcon(activity.action)}"></i>
                        </div>
                        <div class="cs-activity-content">
                            <div class="cs-activity-title">${activity.title}</div>
                            <div class="cs-activity-meta">
                                <span class="cs-activity-user">${activity.user_name}</span>
                                <span class="cs-activity-time">${StudiosDashboard.formatTimeAgo(activity.timestamp)}</span>
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
         * Check system health
         */
        checkSystemHealth: function() {
            // Auto-check system health every 30 minutes
            setInterval(() => {
                $.ajax({
                    url: CourScribeStudios.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'courscribe_auto_health_check',
                        nonce: CourScribeStudios.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.status !== 'healthy') {
                            StudiosDashboard.updateHealthStatus(response.data.status);
                        }
                    }
                });
            }, 1800000); // 30 minutes
        },

        /**
         * Update health status
         */
        updateHealthStatus: function(status) {
            const $statusBadge = $('.cs-health-status');
            $statusBadge.removeClass('cs-status-healthy cs-status-warning cs-status-error');
            $statusBadge.addClass(`cs-status-${status}`);
            $statusBadge.text(status.charAt(0).toUpperCase() + status.slice(1));
        },

        /**
         * Show health results
         */
        showHealthResults: function(data) {
            const modalHtml = `
                <div class="cs-modal-overlay" id="cs-health-modal">
                    <div class="cs-modal">
                        <div class="cs-modal-header">
                            <h3>System Health Check Results</h3>
                            <button type="button" class="cs-modal-close">&times;</button>
                        </div>
                        <div class="cs-modal-content">
                            <div class="cs-health-results">
                                ${data.checks.map(check => `
                                    <div class="cs-health-check cs-check-${check.status}">
                                        <div class="cs-check-icon">
                                            <i class="fas ${check.status === 'pass' ? 'fa-check' : check.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times'}"></i>
                                        </div>
                                        <div class="cs-check-content">
                                            <h4>${check.name}</h4>
                                            <p>${check.message}</p>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#cs-health-modal').fadeIn();

            $('.cs-modal-close, .cs-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('#cs-health-modal').fadeOut(() => $('#cs-health-modal').remove());
                }
            });
        },

        /**
         * Animate studio cards on load
         */
        animateStudioCards: function() {
            $('.cs-studio-card').each(function(index) {
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
            if (!$('#cs-studios-notification-styles').length) {
                $('head').append(`
                    <style id="cs-studios-notification-styles">
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
                        .cs-notification-success { border-left-color: #28a745; }
                        .cs-notification-warning { border-left-color: #ffc107; }
                        .cs-notification-error { border-left-color: #dc3545; }
                        .cs-notification-content { display: flex; align-items: center; gap: 10px; }
                        .cs-notification-close { background: none; border: none; cursor: pointer; padding: 5px; margin-left: 10px; opacity: 0.6; }
                        .cs-notification-close:hover { opacity: 1; }
                        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
                        
                        .cs-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; }
                        .cs-modal { background: white; border-radius: 12px; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
                        .cs-modal-large { max-width: 900px; }
                        .cs-modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
                        .cs-modal-content { padding: 20px; }
                        .cs-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; opacity: 0.6; }
                        .cs-modal-close:hover { opacity: 1; }
                        
                        .cs-bulk-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
                        .cs-bulk-option { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: none; cursor: pointer; transition: all 0.2s ease; }
                        .cs-bulk-option:hover { background: #f8f9fa; border-color: #007cba; }
                        .cs-bulk-option i { font-size: 24px; color: #007cba; }
                        
                        .cs-logs-table { overflow-x: auto; }
                        .cs-logs-table table { width: 100%; border-collapse: collapse; }
                        .cs-logs-table th, .cs-logs-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
                        .cs-logs-table th { background: #f8f9fa; font-weight: 600; }
                        
                        .cs-health-results { display: flex; flex-direction: column; gap: 15px; }
                        .cs-health-check { display: flex; align-items: flex-start; gap: 15px; padding: 15px; border-radius: 8px; }
                        .cs-check-pass { background: rgba(40, 167, 69, 0.1); }
                        .cs-check-warning { background: rgba(255, 193, 7, 0.1); }
                        .cs-check-fail { background: rgba(220, 53, 69, 0.1); }
                        .cs-check-icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; }
                        .cs-check-pass .cs-check-icon { color: #28a745; }
                        .cs-check-warning .cs-check-icon { color: #ffc107; }
                        .cs-check-fail .cs-check-icon { color: #dc3545; }
                        .cs-check-content h4 { margin: 0 0 5px; font-size: 1rem; }
                        .cs-check-content p { margin: 0; color: #666; font-size: 0.9rem; }
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

    // Initialize studios dashboard
    StudiosDashboard.init();

    // Expose for global access
    window.CourScribeStudiosDashboard = StudiosDashboard;
});