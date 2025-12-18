/**
 * CourScribe Settings Page JavaScript
 * Handles all interactive functionality for the settings interface
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize settings dashboard
    const SettingsDashboard = {
        init: function() {
            this.setupEventHandlers();
            this.setupTabNavigation();
            this.setupAutoSave();
            this.setupFormValidation();
            this.loadTierFeatures();
            this.animateElements();
            console.log('CourScribe Settings Dashboard initialized');
        },

        /**
         * Setup event handlers for dashboard interactions
         */
        setupEventHandlers: function() {
            // Tab navigation
            $('.cs-nav-tab').on('click', this.switchTab);
            
            // Performance actions
            $('#cs-clear-cache-btn').on('click', this.clearCache);
            $('#cs-optimize-db-btn').on('click', this.optimizeDatabase);
            $('#cs-run-maintenance-btn').on('click', this.runMaintenance);
            
            // Debug tools
            $('#cs-export-settings').on('click', this.exportSettings);
            $('#cs-import-settings').on('click', this.importSettings);
            $('#cs-reset-settings').on('click', this.resetSettings);
            
            // API key functionality
            $('#cs-toggle-api-key').on('click', this.toggleApiKeyVisibility);
            $('#cs-test-api-key').on('click', this.testApiKey);
            
            // Tier change handler
            $('#courscribe_tier').on('change', this.updateTierFeatures);
            
            // Form change detection
            $('.cs-settings-form input, .cs-settings-form select').on('change', this.markUnsaved);
            
            // Auto-hide success notification
            setTimeout(() => {
                $('.cs-notification-floating').fadeOut();
            }, 5000);
        },

        /**
         * Setup tab navigation
         */
        setupTabNavigation: function() {
            // Handle browser back/forward
            window.addEventListener('popstate', (e) => {
                if (e.state && e.state.tab) {
                    this.activateTab(e.state.tab);
                }
            });
            
            // Check for hash in URL
            const hash = window.location.hash.slice(1);
            if (hash && $('#cs-tab-' + hash).length) {
                this.activateTab(hash);
            }
        },

        /**
         * Switch between tabs
         */
        switchTab: function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            SettingsDashboard.activateTab(tabId);
            
            // Update URL without page reload
            const newUrl = window.location.pathname + window.location.search + '#' + tabId;
            history.pushState({tab: tabId}, '', newUrl);
        },

        /**
         * Activate specific tab
         */
        activateTab: function(tabId) {
            console.log('Activating tab:', tabId);
            
            // Remove active class from all tabs and content
            $('.cs-nav-tab').removeClass('active');
            $('.cs-tab-content').removeClass('active').hide();
            
            // Add active class to selected tab and content
            $(`.cs-nav-tab[data-tab="${tabId}"]`).addClass('active');
            $(`#cs-tab-${tabId}`).addClass('active').fadeIn(300);
            
            // Ensure the tab content is visible and animate
            $(`#cs-tab-${tabId}`).css('display', 'block');
            
            // Trigger any necessary updates for the active tab
            if (tabId === 'general') {
                this.enhanceGeneralSettingsContrast();
            }
        },

        /**
         * Setup auto-save functionality
         */
        setupAutoSave: function() {
            let saveTimeout;
            
            $('.cs-settings-form input, .cs-settings-form select').on('input change', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    SettingsDashboard.autoSave();
                }, 2000);
            });
        },

        /**
         * Auto-save form data
         */
        autoSave: function() {
            const formData = new FormData($('.cs-settings-form')[0]);
            formData.append('action', 'courscribe_auto_save_settings');
            formData.append('nonce', CourScribeSettings.nonce);
            
            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showAutoSaveIndicator();
                    }
                }
            });
        },

        /**
         * Show auto-save indicator
         */
        showAutoSaveIndicator: function() {
            const $indicator = $('#cs-auto-save-indicator');
            $indicator.addClass('visible');
            setTimeout(() => {
                $indicator.removeClass('visible');
            }, 3000);
        },

        /**
         * Mark form as having unsaved changes
         */
        markUnsaved: function() {
            $(window).on('beforeunload', function() {
                return 'You have unsaved changes. Are you sure you want to leave?';
            });
        },

        /**
         * Setup form validation
         */
        setupFormValidation: function() {
            $('.cs-settings-form').on('submit', function(e) {
                if (!SettingsDashboard.validateForm()) {
                    e.preventDefault();
                    return false;
                }
                
                // Remove unsaved changes warning
                $(window).off('beforeunload');
            });
        },

        /**
         * Validate form before submission
         */
        validateForm: function() {
            let isValid = true;
            const errors = [];
            
            // Validate API key format
            const apiKey = $('#courscribe_api_key').val();
            if (apiKey && !apiKey.match(/^[A-Za-z0-9\-_]{10,}$/)) {
                errors.push('API key format appears to be invalid');
                isValid = false;
            }
            
            // Validate numeric fields
            const numericFields = ['courscribe_ai_rate_limit', 'courscribe_max_studios', 'courscribe_log_retention'];
            numericFields.forEach(field => {
                const value = $(`#${field}`).val();
                if (value && (isNaN(value) || parseInt(value) < 0)) {
                    errors.push(`${field.replace('courscribe_', '').replace('_', ' ')} must be a positive number`);
                    isValid = false;
                }
            });
            
            if (!isValid) {
                this.showNotification('error', 'Please fix the following errors:<br>' + errors.join('<br>'));
            }
            
            return isValid;
        },

        /**
         * Load tier features
         */
        loadTierFeatures: function() {
            const tier = $('#courscribe_tier').val();
            this.updateTierFeatures(tier);
        },

        /**
         * Update tier features display
         */
        updateTierFeatures: function(tier) {
            if (typeof tier === 'object') {
                tier = $(this).val();
            }
            
            const features = {
                basics: [
                    {icon: 'fa-check', text: '1 Studio', enabled: true},
                    {icon: 'fa-check', text: '1 Curriculum per studio', enabled: true},
                    {icon: 'fa-check', text: 'Basic AI assistance', enabled: true},
                    {icon: 'fa-times', text: 'Advanced analytics', enabled: false},
                    {icon: 'fa-times', text: 'Priority support', enabled: false},
                    {icon: 'fa-times', text: 'White-label options', enabled: false}
                ],
                plus: [
                    {icon: 'fa-check', text: '3 Studios', enabled: true},
                    {icon: 'fa-check', text: '2 Curriculums per studio', enabled: true},
                    {icon: 'fa-check', text: 'Enhanced AI assistance', enabled: true},
                    {icon: 'fa-check', text: 'Advanced analytics', enabled: true},
                    {icon: 'fa-times', text: 'Priority support', enabled: false},
                    {icon: 'fa-times', text: 'White-label options', enabled: false}
                ],
                pro: [
                    {icon: 'fa-check', text: 'Unlimited Studios', enabled: true},
                    {icon: 'fa-check', text: 'Unlimited Curriculums', enabled: true},
                    {icon: 'fa-check', text: 'Premium AI assistance', enabled: true},
                    {icon: 'fa-check', text: 'Advanced analytics', enabled: true},
                    {icon: 'fa-check', text: 'Priority support', enabled: true},
                    {icon: 'fa-check', text: 'White-label options', enabled: true}
                ]
            };
            
            const tierFeatures = features[tier] || features.basics;
            const $container = $('#cs-tier-features');
            
            $container.empty();
            tierFeatures.forEach(feature => {
                const $feature = $(`
                    <div class="cs-feature-item">
                        <div class="cs-feature-icon">
                            <i class="fas ${feature.icon} ${feature.enabled ? 'cs-icon-enabled' : 'cs-icon-disabled'}"></i>
                        </div>
                        <span class="cs-feature-text">${feature.text}</span>
                    </div>
                `);
                $container.append($feature);
            });
        },

        /**
         * Clear plugin cache
         */
        clearCache: function() {
            if (!confirm('Are you sure you want to clear the plugin cache? This may temporarily slow down the plugin.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Clearing...').prop('disabled', true);

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_clear_cache',
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showNotification('success', 'Cache cleared successfully');
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'Failed to clear cache');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Optimize database
         */
        optimizeDatabase: function() {
            if (!confirm('Are you sure you want to optimize the database? This process may take a few minutes.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Optimizing...').prop('disabled', true);

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_optimize_database',
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showNotification('success', 'Database optimized successfully');
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'Database optimization failed');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Run maintenance tasks
         */
        runMaintenance: function() {
            if (!confirm('Are you sure you want to run maintenance tasks? This will clean up temporary files and optimize performance.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Running...').prop('disabled', true);

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_run_maintenance',
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showNotification('success', 'Maintenance completed successfully');
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'Maintenance failed');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Toggle API key visibility
         */
        toggleApiKeyVisibility: function() {
            const $input = $('#courscribe_api_key');
            const $icon = $(this).find('i');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        },

        /**
         * Test API key connection
         */
        testApiKey: function() {
            const apiKey = $('#courscribe_api_key').val();
            
            if (!apiKey) {
                SettingsDashboard.showNotification('warning', 'Please enter an API key to test');
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_test_api_key',
                    api_key: apiKey,
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showNotification('success', 'API key is valid and working');
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'API key test failed');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Export settings
         */
        exportSettings: function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_export_settings',
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        SettingsDashboard.showNotification('success', 'Settings exported successfully');
                        
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename || 'courscribe-settings.json';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'Export failed');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Import settings
         */
        importSettings: function() {
            // Create file input
            const $fileInput = $('<input type="file" accept=".json" style="display: none;">');
            $('body').append($fileInput);
            
            $fileInput.on('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const settings = JSON.parse(e.target.result);
                        SettingsDashboard.processImportedSettings(settings);
                    } catch (error) {
                        SettingsDashboard.showNotification('error', 'Invalid settings file format');
                    }
                };
                reader.readAsText(file);
                
                // Clean up
                $fileInput.remove();
            });
            
            $fileInput.click();
        },

        /**
         * Process imported settings
         */
        processImportedSettings: function(settings) {
            if (!confirm('Are you sure you want to import these settings? This will overwrite your current configuration.')) {
                return;
            }

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_import_settings',
                    settings: JSON.stringify(settings),
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showNotification('success', 'Settings imported successfully');
                        // Reload page to show imported settings
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'Import failed');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                }
            });
        },

        /**
         * Reset settings to defaults
         */
        resetSettings: function() {
            if (!confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
                return;
            }

            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...').prop('disabled', true);

            $.ajax({
                url: CourScribeSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_reset_settings',
                    nonce: CourScribeSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SettingsDashboard.showNotification('success', 'Settings reset to defaults');
                        // Reload page to show reset settings
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        SettingsDashboard.showNotification('error', response.data?.message || 'Reset failed');
                    }
                },
                error: function() {
                    SettingsDashboard.showNotification('error', 'Network error occurred');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        },

        /**
         * Animate elements on page load
         */
        animateElements: function() {
            $('.cs-settings-section').each(function(index) {
                $(this).css('animation-delay', `${index * 0.1}s`).addClass('fade-in');
            });
        },

        /**
         * Enhance General Settings contrast
         */
        enhanceGeneralSettingsContrast: function() {
            // Add enhanced contrast to General Settings elements
            $('#cs-tab-general .cs-setting-item').each(function() {
                const $item = $(this);
                if (!$item.hasClass('enhanced-contrast')) {
                    $item.addClass('enhanced-contrast');
                    
                    // Animate the enhancement
                    $item.css({
                        'transform': 'scale(0.98)',
                        'opacity': '0.8'
                    }).animate({
                        'transform': 'scale(1)',
                        'opacity': '1'
                    }, 300);
                }
            });
        },

        /**
         * Show notification
         */
        showNotification: function(type, message) {
            // Remove existing notifications
            $('.cs-notification-toast').remove();
            
            const $notification = $(`
                <div class="cs-notification-toast cs-notification-${type}">
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
            if (!$('#cs-settings-notification-styles').length) {
                $('head').append(`
                    <style id="cs-settings-notification-styles">
                        .cs-notification-toast {
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            background: white;
                            border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                            padding: 15px 20px;
                            z-index: 10001;
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

    // Initialize settings dashboard
    SettingsDashboard.init();

    // Expose for global access
    window.CourScribeSettingsDashboard = SettingsDashboard;
});