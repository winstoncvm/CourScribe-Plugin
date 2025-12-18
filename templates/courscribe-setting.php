<?php
// courscribe/templates/courscribe-settings.php
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_settings_page() {
    $current_user = wp_get_current_user();
    $site_url = home_url();

    // Handle form submission
    if (isset($_POST['courscribe_save_settings']) && wp_verify_nonce($_POST['courscribe_settings_nonce'], 'courscribe_save_settings')) {
        $tier = sanitize_text_field($_POST['courscribe_tier']);
        $create_studio_page = intval($_POST['courscribe_create_studio_page']);
        $register_page = intval($_POST['courscribe_register_page']);
        $api_key = sanitize_text_field($_POST['courscribe_api_key']);
        $email_notifications = isset($_POST['courscribe_email_notifications']) ? 1 : 0;
        $cache_enabled = isset($_POST['courscribe_cache_enabled']) ? 1 : 0;
        $ai_rate_limit = intval($_POST['courscribe_ai_rate_limit']);
        $max_studios = intval($_POST['courscribe_max_studios']);
        $security_mode = sanitize_text_field($_POST['courscribe_security_mode']);
        $backup_frequency = sanitize_text_field($_POST['courscribe_backup_frequency']);
        $log_retention = intval($_POST['courscribe_log_retention']);

        update_option('courscribe_tier', $tier);
        update_option('courscribe_create_studio_page', $create_studio_page);
        update_option('courscribe_register_page', $register_page);
        update_option('courscribe_api_key', $api_key);
        update_option('courscribe_email_notifications', $email_notifications);
        update_option('courscribe_cache_enabled', $cache_enabled);
        update_option('courscribe_ai_rate_limit', $ai_rate_limit);
        update_option('courscribe_max_studios', $max_studios);
        update_option('courscribe_security_mode', $security_mode);
        update_option('courscribe_backup_frequency', $backup_frequency);
        update_option('courscribe_log_retention', $log_retention);

        $success_message = 'Settings saved successfully!';
    }

    // Get current settings
    $settings = courscribe_get_all_settings();
    $system_info = courscribe_get_system_info();
    
    // Enqueue settings assets
    courscribe_enqueue_settings_assets();
    ?>

    <!-- CourScribe Settings Dashboard -->
    <div class="cs-settings-dashboard">
        <!-- Header Section -->
        <div class="cs-dashboard-header">
            <div class="cs-header-content">
                <div class="cs-brand-section">
                    <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" 
                         alt="CourScribe Logo" class="cs-dashboard-logo">
                    <div class="cs-brand-text">
                        <h1 class="cs-dashboard-title">Plugin Settings</h1>
                        <p class="cs-dashboard-subtitle">Configure CourScribe system preferences</p>
                    </div>
                </div>
                <div class="cs-admin-info">
                    <div class="cs-admin-avatar">
                        <?php echo get_avatar($current_user->ID, 40, '', '', ['class' => 'cs-avatar-img']); ?>
                    </div>
                    <div class="cs-admin-details">
                        <span class="cs-admin-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="cs-admin-role">System Administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)) : ?>
        <div class="cs-notification cs-notification-success cs-notification-floating">
            <div class="cs-notification-content">
                <i class="fas fa-check-circle"></i>
                <span><?php echo esc_html($success_message); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <div class="cs-settings-content">
            <form method="post" class="cs-settings-form" id="cs-settings-form">
                <?php wp_nonce_field('courscribe_save_settings', 'courscribe_settings_nonce'); ?>
                
                <!-- Settings Navigation -->
                <div class="cs-settings-nav">
                    <button type="button" class="cs-nav-tab active" data-tab="general">
                        <i class="fas fa-cog"></i>
                        <span>General</span>
                    </button>
                    <button type="button" class="cs-nav-tab" data-tab="subscription">
                        <i class="fas fa-crown"></i>
                        <span>Subscription</span>
                    </button>
                    <button type="button" class="cs-nav-tab" data-tab="security">
                        <i class="fas fa-shield-alt"></i>
                        <span>Security</span>
                    </button>
                    <button type="button" class="cs-nav-tab" data-tab="performance">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Performance</span>
                    </button>
                    <button type="button" class="cs-nav-tab" data-tab="integrations">
                        <i class="fas fa-plug"></i>
                        <span>Integrations</span>
                    </button>
                    <button type="button" class="cs-nav-tab" data-tab="advanced">
                        <i class="fas fa-tools"></i>
                        <span>Advanced</span>
                    </button>
                </div>

                <!-- General Settings Tab -->
                <div class="cs-tab-content active" id="cs-tab-general">
                    <div class="cs-settings-section">
                        <div class="cs-section-header">
                            <h3 class="cs-section-title">
                                <i class="fas fa-cog"></i>
                                General Settings
                            </h3>
                            <p class="cs-section-description">Configure basic plugin functionality</p>
                        </div>
                        
                        <div class="cs-settings-grid">
                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_create_studio_page">Create Studio Page</label>
                                <div class="cs-setting-control">
                                    <?php
                                    wp_dropdown_pages([
                                        'name' => 'courscribe_create_studio_page',
                                        'id' => 'courscribe_create_studio_page',
                                        'selected' => $settings['create_studio_page'],
                                        'show_option_none' => 'Select a page',
                                        'class' => 'cs-select',
                                    ]);
                                    ?>
                                    <div class="cs-setting-help">Page where users can create new studios</div>
                                </div>
                            </div>

                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_register_page">Registration Page</label>
                                <div class="cs-setting-control">
                                    <?php
                                    wp_dropdown_pages([
                                        'name' => 'courscribe_register_page',
                                        'id' => 'courscribe_register_page',
                                        'selected' => $settings['register_page'],
                                        'show_option_none' => 'Select a page',
                                        'class' => 'cs-select',
                                    ]);
                                    ?>
                                    <div class="cs-setting-help">Page for new user registration</div>
                                </div>
                            </div>

                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_email_notifications">Email Notifications</label>
                                <div class="cs-setting-control">
                                    <div class="cs-toggle">
                                        <input type="checkbox" name="courscribe_email_notifications" id="courscribe_email_notifications" 
                                               value="1" <?php checked($settings['email_notifications'], 1); ?> class="cs-toggle-input">
                                        <label for="courscribe_email_notifications" class="cs-toggle-label">
                                            <span class="cs-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="cs-setting-help">Send email notifications for important events</div>
                                </div>
                            </div>

                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_max_studios">Maximum Studios per User</label>
                                <div class="cs-setting-control">
                                    <input type="number" name="courscribe_max_studios" id="courscribe_max_studios" 
                                           value="<?php echo esc_attr($settings['max_studios']); ?>" 
                                           min="1" max="100" class="cs-input cs-input-number">
                                    <div class="cs-setting-help">Limit the number of studios each user can create (0 = unlimited)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Settings Tab -->
                <div class="cs-tab-content" id="cs-tab-subscription">
                    <div class="cs-settings-section">
                        <div class="cs-section-header">
                            <h3 class="cs-section-title">
                                <i class="fas fa-crown"></i>
                                Subscription Management
                            </h3>
                            <p class="cs-section-description">Configure subscription tiers and limitations</p>
                        </div>
                        
                        <div class="cs-subscription-tiers">
                            <div class="cs-tier-card">
                                <div class="cs-tier-header">
                                    <h4 class="cs-tier-name">Current Tier</h4>
                                    <div class="cs-tier-badge cs-tier-<?php echo esc_attr($settings['tier']); ?>">
                                        <?php echo esc_html(ucfirst($settings['tier'])); ?>
                                    </div>
                                </div>
                                <div class="cs-tier-selector">
                                    <label class="cs-setting-label" for="courscribe_tier">Subscription Tier</label>
                                    <select name="courscribe_tier" id="courscribe_tier" class="cs-select">
                                        <option value="basics" <?php selected($settings['tier'], 'basics'); ?>>Basics</option>
                                        <option value="plus" <?php selected($settings['tier'], 'plus'); ?>>Plus</option>
                                        <option value="pro" <?php selected($settings['tier'], 'pro'); ?>>Pro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="cs-tier-features">
                                <h4>Current Tier Features</h4>
                                <div class="cs-features-grid" id="cs-tier-features">
                                    <!-- Features will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings Tab -->
                <div class="cs-tab-content" id="cs-tab-security">
                    <div class="cs-settings-section">
                        <div class="cs-section-header">
                            <h3 class="cs-section-title">
                                <i class="fas fa-shield-alt"></i>
                                Security Settings
                            </h3>
                            <p class="cs-section-description">Configure security and privacy options</p>
                        </div>
                        
                        <div class="cs-settings-grid">
                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_security_mode">Security Mode</label>
                                <div class="cs-setting-control">
                                    <select name="courscribe_security_mode" id="courscribe_security_mode" class="cs-select">
                                        <option value="standard" <?php selected($settings['security_mode'], 'standard'); ?>>Standard</option>
                                        <option value="enhanced" <?php selected($settings['security_mode'], 'enhanced'); ?>>Enhanced</option>
                                        <option value="strict" <?php selected($settings['security_mode'], 'strict'); ?>>Strict</option>
                                    </select>
                                    <div class="cs-setting-help">Choose security level for user permissions and data access</div>
                                </div>
                            </div>

                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_log_retention">Log Retention (Days)</label>
                                <div class="cs-setting-control">
                                    <input type="number" name="courscribe_log_retention" id="courscribe_log_retention" 
                                           value="<?php echo esc_attr($settings['log_retention']); ?>" 
                                           min="7" max="365" class="cs-input cs-input-number">
                                    <div class="cs-setting-help">How long to keep activity logs before automatic cleanup</div>
                                </div>
                            </div>

                            <div class="cs-setting-item cs-full-width">
                                <label class="cs-setting-label" for="courscribe_api_key">Google Gemini API Key</label>
                                <div class="cs-setting-control">
                                    <div class="cs-api-key-wrapper">
                                        <input type="password" name="courscribe_api_key" id="courscribe_api_key" 
                                               value="<?php echo esc_attr($settings['api_key']); ?>" 
                                               class="cs-input cs-input-password" placeholder="Enter your API key">
                                        <button type="button" class="cs-toggle-password" id="cs-toggle-api-key">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="cs-test-api" id="cs-test-api-key">
                                            <i class="fas fa-vial"></i>
                                            Test
                                        </button>
                                    </div>
                                    <div class="cs-setting-help">API key for AI-powered content generation features</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Settings Tab -->
                <div class="cs-tab-content" id="cs-tab-performance">
                    <div class="cs-settings-section">
                        <div class="cs-section-header">
                            <h3 class="cs-section-title">
                                <i class="fas fa-tachometer-alt"></i>
                                Performance Settings
                            </h3>
                            <p class="cs-section-description">Optimize plugin performance and caching</p>
                        </div>
                        
                        <div class="cs-settings-grid">
                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_cache_enabled">Enable Caching</label>
                                <div class="cs-setting-control">
                                    <div class="cs-toggle">
                                        <input type="checkbox" name="courscribe_cache_enabled" id="courscribe_cache_enabled" 
                                               value="1" <?php checked($settings['cache_enabled'], 1); ?> class="cs-toggle-input">
                                        <label for="courscribe_cache_enabled" class="cs-toggle-label">
                                            <span class="cs-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="cs-setting-help">Enable caching for improved performance</div>
                                </div>
                            </div>

                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_ai_rate_limit">AI Rate Limit (per hour)</label>
                                <div class="cs-setting-control">
                                    <input type="number" name="courscribe_ai_rate_limit" id="courscribe_ai_rate_limit" 
                                           value="<?php echo esc_attr($settings['ai_rate_limit']); ?>" 
                                           min="1" max="1000" class="cs-input cs-input-number">
                                    <div class="cs-setting-help">Maximum AI API calls per user per hour</div>
                                </div>
                            </div>

                            <div class="cs-setting-item">
                                <label class="cs-setting-label" for="courscribe_backup_frequency">Backup Frequency</label>
                                <div class="cs-setting-control">
                                    <select name="courscribe_backup_frequency" id="courscribe_backup_frequency" class="cs-select">
                                        <option value="never" <?php selected($settings['backup_frequency'], 'never'); ?>>Never</option>
                                        <option value="daily" <?php selected($settings['backup_frequency'], 'daily'); ?>>Daily</option>
                                        <option value="weekly" <?php selected($settings['backup_frequency'], 'weekly'); ?>>Weekly</option>
                                        <option value="monthly" <?php selected($settings['backup_frequency'], 'monthly'); ?>>Monthly</option>
                                    </select>
                                    <div class="cs-setting-help">Automatic backup schedule for plugin data</div>
                                </div>
                            </div>

                            <div class="cs-setting-item cs-full-width">
                                <div class="cs-performance-actions">
                                    <button type="button" class="cs-action-btn cs-btn-secondary" id="cs-clear-cache-btn">
                                        <i class="fas fa-broom"></i>
                                        Clear Cache
                                    </button>
                                    <button type="button" class="cs-action-btn cs-btn-info" id="cs-optimize-db-btn">
                                        <i class="fas fa-database"></i>
                                        Optimize Database
                                    </button>
                                    <button type="button" class="cs-action-btn cs-btn-warning" id="cs-run-maintenance-btn">
                                        <i class="fas fa-tools"></i>
                                        Run Maintenance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Integrations Tab -->
                <div class="cs-tab-content" id="cs-tab-integrations">
                    <div class="cs-settings-section">
                        <div class="cs-section-header">
                            <h3 class="cs-section-title">
                                <i class="fas fa-plug"></i>
                                External Integrations
                            </h3>
                            <p class="cs-section-description">Connect with third-party services</p>
                        </div>
                        
                        <div class="cs-integrations-grid">
                            <div class="cs-integration-card">
                                <div class="cs-integration-header">
                                    <div class="cs-integration-icon">
                                        <i class="fab fa-google"></i>
                                    </div>
                                    <div class="cs-integration-info">
                                        <h4>Google Gemini AI</h4>
                                        <p>AI-powered content generation</p>
                                    </div>
                                    <div class="cs-integration-status cs-status-connected">
                                        <i class="fas fa-check-circle"></i>
                                        Connected
                                    </div>
                                </div>
                            </div>

                            <div class="cs-integration-card">
                                <div class="cs-integration-header">
                                    <div class="cs-integration-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="cs-integration-info">
                                        <h4>Email Service</h4>
                                        <p>WordPress built-in email</p>
                                    </div>
                                    <div class="cs-integration-status cs-status-connected">
                                        <i class="fas fa-check-circle"></i>
                                        Active
                                    </div>
                                </div>
                            </div>

                            <div class="cs-integration-card">
                                <div class="cs-integration-header">
                                    <div class="cs-integration-icon">
                                        <i class="fas fa-cloud"></i>
                                    </div>
                                    <div class="cs-integration-info">
                                        <h4>Cloud Storage</h4>
                                        <p>File backup and storage</p>
                                    </div>
                                    <div class="cs-integration-status cs-status-available">
                                        <i class="fas fa-plus-circle"></i>
                                        Available
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings Tab -->
                <div class="cs-tab-content" id="cs-tab-advanced">
                    <div class="cs-settings-section">
                        <div class="cs-section-header">
                            <h3 class="cs-section-title">
                                <i class="fas fa-tools"></i>
                                Advanced Settings
                            </h3>
                            <p class="cs-section-description">Advanced configuration and system information</p>
                        </div>
                        
                        <div class="cs-advanced-grid">
                            <div class="cs-system-info">
                                <h4>System Information</h4>
                                <div class="cs-system-details">
                                    <div class="cs-system-item">
                                        <span class="cs-system-label">Plugin Version:</span>
                                        <span class="cs-system-value"><?php echo esc_html($system_info['plugin_version']); ?></span>
                                    </div>
                                    <div class="cs-system-item">
                                        <span class="cs-system-label">WordPress Version:</span>
                                        <span class="cs-system-value"><?php echo esc_html($system_info['wp_version']); ?></span>
                                    </div>
                                    <div class="cs-system-item">
                                        <span class="cs-system-label">PHP Version:</span>
                                        <span class="cs-system-value"><?php echo esc_html($system_info['php_version']); ?></span>
                                    </div>
                                    <div class="cs-system-item">
                                        <span class="cs-system-label">Database Version:</span>
                                        <span class="cs-system-value"><?php echo esc_html($system_info['db_version']); ?></span>
                                    </div>
                                    <div class="cs-system-item">
                                        <span class="cs-system-label">Memory Limit:</span>
                                        <span class="cs-system-value"><?php echo esc_html($system_info['memory_limit']); ?></span>
                                    </div>
                                    <div class="cs-system-item">
                                        <span class="cs-system-label">Max Upload Size:</span>
                                        <span class="cs-system-value"><?php echo esc_html($system_info['upload_max']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="cs-debug-tools">
                                <h4>Debug Tools</h4>
                                <div class="cs-debug-actions">
                                    <button type="button" class="cs-action-btn cs-btn-secondary" id="cs-export-settings">
                                        <i class="fas fa-download"></i>
                                        Export Settings
                                    </button>
                                    <button type="button" class="cs-action-btn cs-btn-secondary" id="cs-import-settings">
                                        <i class="fas fa-upload"></i>
                                        Import Settings
                                    </button>
                                    <button type="button" class="cs-action-btn cs-btn-warning" id="cs-reset-settings">
                                        <i class="fas fa-undo"></i>
                                        Reset to Defaults
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="cs-settings-footer">
                    <div class="cs-save-section">
                        <button type="submit" name="courscribe_save_settings" class="cs-btn cs-btn-primary cs-btn-large">
                            <i class="fas fa-save"></i>
                            Save All Settings
                        </button>
                        <div class="cs-auto-save-indicator" id="cs-auto-save-indicator">
                            <i class="fas fa-check-circle"></i>
                            Auto-saved
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Get all plugin settings
 */
function courscribe_get_all_settings() {
    return [
        'tier' => get_option('courscribe_tier', 'basics'),
        'create_studio_page' => get_option('courscribe_create_studio_page', 0),
        'register_page' => get_option('courscribe_register_page', 0),
        'api_key' => get_option('courscribe_api_key', ''),
        'email_notifications' => get_option('courscribe_email_notifications', 1),
        'cache_enabled' => get_option('courscribe_cache_enabled', 1),
        'ai_rate_limit' => get_option('courscribe_ai_rate_limit', 50),
        'max_studios' => get_option('courscribe_max_studios', 5),
        'security_mode' => get_option('courscribe_security_mode', 'standard'),
        'backup_frequency' => get_option('courscribe_backup_frequency', 'weekly'),
        'log_retention' => get_option('courscribe_log_retention', 30),
    ];
}

/**
 * Get system information
 */
function courscribe_get_system_info() {
    global $wpdb;
    
    return [
        'plugin_version' => '1.1.9',
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'db_version' => $wpdb->db_version(),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max' => size_format(wp_max_upload_size()),
    ];
}

/**
 * Enqueue settings assets
 */
function courscribe_enqueue_settings_assets() {
    $plugin_url = plugin_dir_url(__FILE__);
    $plugin_url = str_replace('/templates/', '/', $plugin_url);
    
    // Enqueue shared admin dashboard CSS
    wp_enqueue_style(
        'courscribe-admin-dashboard',
        $plugin_url . 'assets/css/admin-dashboard.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . '../assets/css/admin-dashboard.css')
    );
    
    // Enqueue settings-specific CSS
    wp_enqueue_style(
        'courscribe-settings',
        $plugin_url . 'assets/css/settings.css',
        ['courscribe-admin-dashboard'],
        filemtime(plugin_dir_path(__FILE__) . '../assets/css/settings.css')
    );
    
    // Enqueue settings JavaScript
    wp_enqueue_script(
        'courscribe-settings',
        $plugin_url . 'assets/js/settings.js',
        ['jquery', 'courscribe-admin-dashboard'],
        filemtime(plugin_dir_path(__FILE__) . '../assets/js/settings.js'),
        true
    );
    
    // Localize script for AJAX
    wp_localize_script(
        'courscribe-settings',
        'CourScribeSettings',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_settings_nonce'),
            'user_id' => get_current_user_id()
        ]
    );
}
?>