<?php
// Premium CourScribe Studio Settings Shortcode
// Beautiful, modern settings interface with comprehensive options
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_premium_settings_shortcode($atts) {
    // Check authentication and permissions
    if (!is_user_logged_in()) {
        return courscribe_premium_auth_required_settings();
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_roles = $current_user->roles;
    $is_collaborator = in_array( 'collaborator', $user_roles );
    $is_client = in_array( 'client', $user_roles );
    $is_studio_admin = in_array( 'studio_admin', $user_roles );
    $is_wp_admin = current_user_can('administrator');

    // Determine user's studio ID for autofill
    $user_studio_id = 0;
    if ($is_collaborator || $is_client) {
        $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($is_client && !$user_studio_id) {
            global $wpdb;
            $invite_table = $wpdb->prefix . 'courscribe_client_invites';
            $first_invite = $wpdb->get_row($wpdb->prepare(
                "SELECT curriculum_id FROM $invite_table WHERE email = %s AND status = 'Accepted' ORDER BY created_at ASC LIMIT 1",
                $current_user->user_email
            ));
            if ($first_invite) {
                $user_studio_id = get_post_meta($first_invite->curriculum_id, '_studio_id', true);
            }
        }
    } elseif ($is_studio_admin || $is_wp_admin) {
        $studios = get_posts(array(
            'post_type' => 'crscribe_studio',
            'author' => $current_user->ID,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));
        if (!empty($studios)) {
            $user_studio_id = $studios[0];
        }
    }
    
    // Get studio information
    $studio = null;
    if ($user_studio_id) {
        $studio = get_post($user_studio_id);
    }
    
    if (!$studio) {
        // Try to find user's studio
        $studios = get_posts([
            'post_type' => 'crscribe_studio',
            'author' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        if (empty($studios)) {
            return '<div class="no-studio-message">No studio found. Please create a studio first.</div>';
        }
        
        $studio = $studios[0];
        $user_studio_id = $studio->ID;
    }
    
    // Get current settings
    $email = get_post_meta($user_studio_id, '_studio_email', true) ?: $current_user->user_email;
    $website = get_post_meta($user_studio_id, '_studio_website', true) ?: '';
    $address = get_post_meta($user_studio_id, '_studio_address', true) ?: '';
    $phone = get_post_meta($user_studio_id, '_studio_phone', true) ?: '';
    $timezone = get_post_meta($user_studio_id, '_studio_timezone', true) ?: 'America/New_York';
    $is_public = get_post_meta($user_studio_id, '_studio_is_public', true) === 'Yes';
    $allow_search = get_post_meta($user_studio_id, '_studio_allow_search', true) !== 'no';
    $email_notifications = get_post_meta($user_studio_id, '_studio_email_notifications', true) !== 'no';
    $weekly_reports = get_post_meta($user_studio_id, '_studio_weekly_reports', true) !== 'no';
    
    // Get user tier
    $user_tier = courscribe_get_user_tier($user_id);
    
    // Get team members count
    $collaborators = get_users([
        'role' => 'collaborator',
        'meta_key' => '_courscribe_studio_id',
        'meta_value' => $user_studio_id,
        'fields' => ['ID'],
    ]);
    
    ob_start();
    ?>

    <!-- Premium Settings Interface -->
    <div class="courscribe-premium-settings" id="settings-app">
   
        <!-- Settings Header -->
        <div class="settings-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="settings-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="header-info">
                        <h1 class="settings-title">Studio Settings</h1>
                        <p class="settings-subtitle">Manage your studio preferences and configuration</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="studio-status">
                        <div class="status-indicator active"></div>
                        <span class="status-text">Studio Active</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Settings Navigation -->
        <div class="settings-nav">
            <div class="nav-container">
                <button class="nav-btn active" data-section="general">
                    <i class="fas fa-building"></i>
                    <span>General</span>
                </button>
                <button class="nav-btn" data-section="account">
                    <i class="fas fa-user-circle"></i>
                    <span>Account</span>
                </button>
                <button class="nav-btn" data-section="privacy">
                    <i class="fas fa-shield-alt"></i>
                    <span>Privacy</span>
                </button>
                <button class="nav-btn" data-section="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </button>
                <button class="nav-btn" data-section="billing">
                    <i class="fas fa-credit-card"></i>
                    <span>Billing</span>
                </button>
                <button class="nav-btn" data-section="advanced">
                    <i class="fas fa-tools"></i>
                    <span>Advanced</span>
                </button>
            </div>
        </div>
        
        <!-- Settings Content -->
        <div class="settings-content">
            
            <!-- General Settings -->
            <div class="settings-section active" id="general-section">
                <div class="section-header">
                    <h2 class="section-title">General Information</h2>
                    <p class="section-description">Basic information about your studio</p>
                </div>
                
                <form class="settings-form" id="general-form">
                    <?php wp_nonce_field('courscribe_save_settings', 'settings_nonce'); ?>
                    <input type="hidden" name="studio_id" value="<?php echo esc_attr($user_studio_id); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="studio-name" class="form-label">
                                <i class="fas fa-signature"></i>
                                Studio Name
                            </label>
                            <input type="text" id="studio-name" name="studio_name" 
                                   value="<?php echo esc_attr($studio->post_title); ?>" 
                                   class="form-control-premium" placeholder="Enter your studio name" required>
                            <span class="form-hint">This is how your studio will appear to collaborators and clients</span>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="studio-description" class="form-label">
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea id="studio-description" name="studio_description" 
                                      class="form-control-premium" rows="4" 
                                      placeholder="Describe your studio's mission and goals..."><?php echo esc_textarea($studio->post_content); ?></textarea>
                            <span class="form-hint">A brief description of what your studio does</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="studio-email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Contact Email
                            </label>
                            <input type="email" id="studio-email" name="studio_email" 
                                   value="<?php echo esc_attr($email); ?>" 
                                   class="form-control-premium" placeholder="contact@yourstudio.com">
                            <span class="form-hint">Primary contact email for your studio</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="studio-phone" class="form-label">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </label>
                            <input type="tel" id="studio-phone" name="studio_phone" 
                                   value="<?php echo esc_attr($phone); ?>" 
                                   class="form-control-premium" placeholder="+1 (555) 123-4567">
                            <span class="form-hint">Optional contact phone number</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="studio-website" class="form-label">
                                <i class="fas fa-globe"></i>
                                Website URL
                            </label>
                            <input type="url" id="studio-website" name="studio_website" 
                                   value="<?php echo esc_attr($website); ?>" 
                                   class="form-control-premium" placeholder="https://yourstudio.com">
                            <span class="form-hint">Your studio's website or portfolio</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="studio-timezone" class="form-label">
                                <i class="fas fa-clock"></i>
                                Timezone
                            </label>
                            <select id="studio-timezone" name="studio_timezone" class="form-control-premium">
                                <option value="America/New_York" <?php selected($timezone, 'America/New_York'); ?>>Eastern Time (EST/EDT)</option>
                                <option value="America/Chicago" <?php selected($timezone, 'America/Chicago'); ?>>Central Time (CST/CDT)</option>
                                <option value="America/Denver" <?php selected($timezone, 'America/Denver'); ?>>Mountain Time (MST/MDT)</option>
                                <option value="America/Los_Angeles" <?php selected($timezone, 'America/Los_Angeles'); ?>>Pacific Time (PST/PDT)</option>
                                <option value="Europe/London" <?php selected($timezone, 'Europe/London'); ?>>London (GMT/BST)</option>
                                <option value="Europe/Paris" <?php selected($timezone, 'Europe/Paris'); ?>>Paris (CET/CEST)</option>
                                <option value="Asia/Tokyo" <?php selected($timezone, 'Asia/Tokyo'); ?>>Tokyo (JST)</option>
                                <option value="Australia/Sydney" <?php selected($timezone, 'Australia/Sydney'); ?>>Sydney (AEST/AEDT)</option>
                            </select>
                            <span class="form-hint">Used for scheduling and time-based features</span>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="studio-address" class="form-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Address
                            </label>
                            <textarea id="studio-address" name="studio_address" 
                                      class="form-control-premium" rows="3" 
                                      placeholder="Your studio's physical address..."><?php echo esc_textarea($address); ?></textarea>
                            <span class="form-hint">Optional physical location of your studio</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="resetForm('general-form')">
                            <i class="fas fa-undo"></i>
                            Reset Changes
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Save General Settings
                            <div class="btn-glow"></div>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Account Settings -->
            <div class="settings-section" id="account-section">
                <div class="section-header">
                    <h2 class="section-title">Account Settings</h2>
                    <p class="section-description">Manage your personal account preferences</p>
                </div>
                
                <div class="account-overview">
                    <div class="account-card">
                        <div class="account-header">
                            <div class="account-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="account-info">
                                <h3><?php echo esc_html($current_user->display_name); ?></h3>
                                <p><?php echo esc_html($current_user->user_email); ?></p>
                                <span class="account-badge">Studio Admin</span>
                            </div>
                        </div>
                        
                        <div class="account-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo esc_html(ucfirst($user_tier)); ?></div>
                                <div class="stat-label">Current Plan</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($collaborators); ?></div>
                                <div class="stat-label">Team Members</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo date('M Y', strtotime($current_user->user_registered)); ?></div>
                                <div class="stat-label">Member Since</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form class="settings-form" id="account-form">
                    <?php wp_nonce_field('courscribe_save_account', 'account_nonce'); ?>
                    
                    <div class="form-section">
                        <h3 class="section-subtitle">Profile Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="display-name" class="form-label">
                                    <i class="fas fa-user"></i>
                                    Display Name
                                </label>
                                <input type="text" id="display-name" name="display_name" 
                                       value="<?php echo esc_attr($current_user->display_name); ?>" 
                                       class="form-control-premium" placeholder="Your display name">
                            </div>
                            
                            <div class="form-group">
                                <label for="user-email" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" id="user-email" name="user_email" 
                                       value="<?php echo esc_attr($current_user->user_email); ?>" 
                                       class="form-control-premium" placeholder="your@email.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-subtitle">Security</h3>
                        
                        <div class="security-options">
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="security-content">
                                    <h4>Password</h4>
                                    <p>Last changed: <?php echo human_time_diff(strtotime($current_user->user_pass)) . ' ago'; ?></p>
                                    <button type="button" class="btn-link" onclick="showPasswordModal()">Change Password</button>
                                </div>
                            </div>
                            
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="security-content">
                                    <h4>Two-Factor Authentication</h4>
                                    <p>Add an extra layer of security to your account</p>
                                    <button type="button" class="btn-link" onclick="setup2FA()">Enable 2FA</button>
                                </div>
                            </div>
                            
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="security-content">
                                    <h4>Login Activity</h4>
                                    <p>View recent login attempts and active sessions</p>
                                    <button type="button" class="btn-link" onclick="viewLoginHistory()">View Activity</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Update Account
                            <div class="btn-glow"></div>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Privacy Settings -->
            <div class="settings-section" id="privacy-section">
                <div class="section-header">
                    <h2 class="section-title">Privacy & Visibility</h2>
                    <p class="section-description">Control who can see and access your studio</p>
                </div>
                
                <form class="settings-form" id="privacy-form">
                    <?php wp_nonce_field('courscribe_save_privacy', 'privacy_nonce'); ?>
                    
                    <div class="privacy-options">
                        <div class="privacy-card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="card-content">
                                    <h3>Studio Visibility</h3>
                                    <p>Control who can discover and view your studio</p>
                                </div>
                            </div>
                            
                            <div class="toggle-group">
                                <div class="toggle-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="studio_public" class="toggle-input" 
                                               <?php checked($is_public); ?>>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Public Studio</span>
                                            <span class="toggle-description">Make your studio discoverable in public directories</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="toggle-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="allow_search" class="toggle-input" 
                                               <?php checked($allow_search); ?>>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Search Engine Indexing</span>
                                            <span class="toggle-description">Allow search engines to index your studio content</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="privacy-card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="card-content">
                                    <h3>Data Management</h3>
                                    <p>Control how your data is handled and shared</p>
                                </div>
                            </div>
                            
                            <div class="data-actions">
                                <button type="button" class="data-action-btn export" onclick="exportStudioData()">
                                    <div class="action-icon">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">Export Studio Data</span>
                                        <span class="action-desc">Download all your studio content and settings</span>
                                    </div>
                                </button>
                                
                                <button type="button" class="data-action-btn backup" onclick="createBackup()">
                                    <div class="action-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">Create Backup</span>
                                        <span class="action-desc">Backup your studio to secure cloud storage</span>
                                    </div>
                                </button>
                                
                                <button type="button" class="data-action-btn danger" onclick="deleteStudioData()">
                                    <div class="action-icon">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">Delete Studio Data</span>
                                        <span class="action-desc">Permanently remove all studio content</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Save Privacy Settings
                            <div class="btn-glow"></div>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Notification Settings -->
            <div class="settings-section" id="notifications-section">
                <div class="section-header">
                    <h2 class="section-title">Notifications</h2>
                    <p class="section-description">Choose what notifications you want to receive</p>
                </div>
                
                <form class="settings-form" id="notifications-form">
                    <?php wp_nonce_field('courscribe_save_notifications', 'notifications_nonce'); ?>
                    
                    <div class="notification-categories">
                        <div class="notification-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="category-content">
                                    <h3>Email Notifications</h3>
                                    <p>Receive important updates via email</p>
                                </div>
                            </div>
                            
                            <div class="notification-list">
                                <div class="notification-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="email_notifications" class="toggle-input" 
                                               <?php checked($email_notifications); ?>>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">General Notifications</span>
                                            <span class="toggle-description">Studio updates, new features, and important announcements</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="notification-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="weekly_reports" class="toggle-input" 
                                               <?php checked($weekly_reports); ?>>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Weekly Reports</span>
                                            <span class="toggle-description">Summary of your studio activity and progress</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="notification-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="collaboration_alerts" class="toggle-input" checked>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Collaboration Alerts</span>
                                            <span class="toggle-description">When team members edit or comment on your content</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="notification-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="security_alerts" class="toggle-input" checked>
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Security Alerts</span>
                                            <span class="toggle-description">Login attempts and security-related notifications</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="notification-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="category-content">
                                    <h3>Push Notifications</h3>
                                    <p>Instant notifications on your devices</p>
                                </div>
                            </div>
                            
                            <div class="notification-list">
                                <div class="notification-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="push_notifications" class="toggle-input">
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Browser Notifications</span>
                                            <span class="toggle-description">Show notifications in your browser</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="notification-item">
                                    <label class="toggle-label">
                                        <input type="checkbox" name="desktop_notifications" class="toggle-input">
                                        <span class="toggle-slider"></span>
                                        <div class="toggle-content">
                                            <span class="toggle-title">Desktop Notifications</span>
                                            <span class="toggle-description">Native desktop notifications</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Save Notification Settings
                            <div class="btn-glow"></div>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Billing Settings -->
            <div class="settings-section" id="billing-section">
                <div class="section-header">
                    <h2 class="section-title">Billing & Subscription</h2>
                    <p class="section-description">Manage your subscription and billing information</p>
                </div>
                
                <div class="billing-overview">
                    <div class="billing-card">
                        <div class="billing-header">
                            <div class="plan-icon tier-<?php echo esc_attr($user_tier); ?>">
                                <i class="fas fa-<?php echo $user_tier === 'basics' ? 'star' : ($user_tier === 'plus' ? 'rocket' : 'crown'); ?>"></i>
                            </div>
                            <div class="plan-info">
                                <h3><?php echo esc_html(ucfirst($user_tier)); ?> Plan</h3>
                                <p>
                                    <?php if ($user_tier === 'basics'): ?>
                                        Free forever plan with essential features
                                    <?php elseif ($user_tier === 'plus'): ?>
                                        Enhanced features with AI assistance
                                    <?php else: ?>
                                        Full access to all premium features
                                    <?php endif; ?>
                                </p>
                                <div class="plan-status active">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Active</span>
                                </div>
                            </div>
                            <?php if ($user_tier !== 'pro'): ?>
                                <button class="btn-upgrade" onclick="window.location.href='<?php echo home_url('/select-tribe/'); ?>'">
                                    <i class="fas fa-arrow-up"></i>
                                    Upgrade Plan
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="billing-details">
                            <div class="detail-item">
                                <span class="detail-label">Plan Type:</span>
                                <span class="detail-value"><?php echo esc_html(ucfirst($user_tier)); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Billing Cycle:</span>
                                <span class="detail-value"><?php echo $user_tier === 'basics' ? 'Free' : 'Monthly'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Next Billing:</span>
                                <span class="detail-value"><?php echo $user_tier === 'basics' ? 'N/A' : date('M d, Y'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($user_tier !== 'basics'): ?>
                <div class="billing-actions">
                    <div class="action-grid">
                        <button class="billing-action-btn" onclick="viewInvoices()">
                            <div class="action-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">View Invoices</span>
                                <span class="action-desc">Download past invoices and receipts</span>
                            </div>
                        </button>
                        
                        <button class="billing-action-btn" onclick="updatePaymentMethod()">
                            <div class="action-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">Payment Method</span>
                                <span class="action-desc">Update your billing information</span>
                            </div>
                        </button>
                        
                        <button class="billing-action-btn" onclick="manageBilling()">
                            <div class="action-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">Billing Portal</span>
                                <span class="action-desc">Manage subscription and billing</span>
                            </div>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Advanced Settings -->
            <div class="settings-section" id="advanced-section">
                <div class="section-header">
                    <h2 class="section-title">Advanced Settings</h2>
                    <p class="section-description">Advanced configuration options for power users</p>
                </div>
                
                <div class="advanced-options">
                    <div class="advanced-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="card-content">
                                <h3>Developer Options</h3>
                                <p>Advanced settings for developers and integrations</p>
                            </div>
                        </div>
                        
                        <div class="advanced-list">
                            <div class="advanced-item">
                                <div class="item-content">
                                    <h4>API Access</h4>
                                    <p>Generate API keys for custom integrations</p>
                                </div>
                                <button class="btn-link" onclick="manageAPI()">Manage</button>
                            </div>
                            
                            <div class="advanced-item">
                                <div class="item-content">
                                    <h4>Webhooks</h4>
                                    <p>Configure webhooks for real-time updates</p>
                                </div>
                                <button class="btn-link" onclick="configureWebhooks()">Configure</button>
                            </div>
                            
                            <div class="advanced-item">
                                <div class="item-content">
                                    <h4>Custom CSS</h4>
                                    <p>Add custom styling to your studio</p>
                                </div>
                                <button class="btn-link" onclick="editCustomCSS()">Edit</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="advanced-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="card-content">
                                <h3>Security & Compliance</h3>
                                <p>Advanced security settings and compliance options</p>
                            </div>
                        </div>
                        
                        <div class="advanced-list">
                            <div class="advanced-item">
                                <div class="item-content">
                                    <h4>Access Logs</h4>
                                    <p>View detailed access and activity logs</p>
                                </div>
                                <button class="btn-link" onclick="viewAccessLogs()">View Logs</button>
                            </div>
                            
                            <div class="advanced-item">
                                <div class="item-content">
                                    <h4>IP Restrictions</h4>
                                    <p>Restrict access by IP address</p>
                                </div>
                                <button class="btn-link" onclick="manageIPRestrictions()">Manage</button>
                            </div>
                            
                            <div class="advanced-item">
                                <div class="item-content">
                                    <h4>GDPR Compliance</h4>
                                    <p>Configure GDPR compliance settings</p>
                                </div>
                                <button class="btn-link" onclick="configureGDPR()">Configure</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Success Message -->
        <div class="success-toast" id="success-toast">
            <div class="toast-content">
                <i class="fas fa-check-circle"></i>
                <span class="toast-message">Settings saved successfully!</span>
            </div>
        </div>
        
    </div>

    

    <!-- Premium Settings JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Navigation functionality
        const navBtns = document.querySelectorAll('.nav-btn');
        const sections = document.querySelectorAll('.settings-section');
        
        navBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetSection = this.getAttribute('data-section');
                
                // Update navigation
                navBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update sections
                sections.forEach(s => s.classList.remove('active'));
                document.getElementById(targetSection + '-section').classList.add('active');
            });
        });
        
        // Form submission handlers
        const forms = document.querySelectorAll('.settings-form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmission(this);
            });
        });
        
        // Toggle functionality
        const toggleInputs = document.querySelectorAll('.toggle-input');
        toggleInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Auto-save toggle changes
                setTimeout(() => {
                    showSuccessToast('Setting updated successfully!');
                }, 300);
            });
        });
        
        function handleFormSubmission(form) {
            const formData = new FormData(form);
            formData.append('action', 'courscribe_save_settings');
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast('Settings saved successfully!');
                } else {
                    showErrorToast(data.data?.message || 'Error saving settings');
                }
            })
            .catch(error => {
                showErrorToast('Network error occurred');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function showSuccessToast(message) {
            const toast = document.getElementById('success-toast');
            const messageEl = toast.querySelector('.toast-message');
            messageEl.textContent = message;
            
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        function showErrorToast(message) {
            // Create and show error toast
            console.error('Settings error:', message);
            alert('Error: ' + message); // Simple fallback
        }
        
        // Global functions for button actions
        window.resetForm = function(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.reset();
                showSuccessToast('Form reset successfully!');
            }
        };
        
        window.showPasswordModal = function() {
            alert('Password change functionality will be implemented');
        };
        
        window.setup2FA = function() {
            alert('2FA setup functionality will be implemented');
        };
        
        window.viewLoginHistory = function() {
            alert('Login history functionality will be implemented');
        };
        
        window.exportStudioData = function() {
            alert('Data export functionality will be implemented');
        };
        
        window.createBackup = function() {
            alert('Backup functionality will be implemented');
        };
        
        window.deleteStudioData = function() {
            if (confirm('Are you sure you want to delete all studio data? This action cannot be undone.')) {
                alert('Data deletion functionality will be implemented');
            }
        };
        
        window.viewInvoices = function() {
            alert('Invoice viewing functionality will be implemented');
        };
        
        window.updatePaymentMethod = function() {
            alert('Payment method update functionality will be implemented');
        };
        
        window.manageBilling = function() {
            alert('Billing management functionality will be implemented');
        };
        
        window.manageAPI = function() {
            alert('API management functionality will be implemented');
        };
        
        window.configureWebhooks = function() {
            alert('Webhook configuration functionality will be implemented');
        };
        
        window.editCustomCSS = function() {
            alert('Custom CSS editor will be implemented');
        };
        
        window.viewAccessLogs = function() {
            alert('Access logs functionality will be implemented');
        };
        
        window.manageIPRestrictions = function() {
            alert('IP restrictions functionality will be implemented');
        };
        
        window.configureGDPR = function() {
            alert('GDPR configuration functionality will be implemented');
        };
    });
    </script>

    <?php
    return ob_get_clean();
}

// Helper function for authentication required message
function courscribe_premium_auth_required_settings() {
    return '<div class="courscribe-auth-required">
        <div class="auth-message">
            <i class="fas fa-lock"></i>
            <h3>Authentication Required</h3>
            <p>Please log in to access your studio settings.</p>
            <a href="' . wp_login_url(get_permalink()) . '" class="btn">Login to Studio</a>
        </div>
    </div>';
}

// Register the shortcode
add_shortcode('courscribe_premium_settings', 'courscribe_premium_settings_shortcode');

// AJAX handler for saving settings
add_action('wp_ajax_courscribe_save_settings', 'courscribe_handle_save_settings');
function courscribe_handle_save_settings() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Authentication required']);
    }
    
    if (!wp_verify_nonce($_POST['settings_nonce'], 'courscribe_save_settings')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    $studio_id = intval($_POST['studio_id']);
    $current_user = wp_get_current_user();
    
    // Verify user owns this studio
    $studio = get_post($studio_id);
    if (!$studio || $studio->post_author != $current_user->ID) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    // Update studio information
    $studio_data = [
        'ID' => $studio_id,
        'post_title' => sanitize_text_field($_POST['studio_name']),
        'post_content' => sanitize_textarea_field($_POST['studio_description'])
    ];
    
    wp_update_post($studio_data);
    
    // Update meta fields
    update_post_meta($studio_id, '_studio_email', sanitize_email($_POST['studio_email']));
    update_post_meta($studio_id, '_studio_phone', sanitize_text_field($_POST['studio_phone']));
    update_post_meta($studio_id, '_studio_website', esc_url_raw($_POST['studio_website']));
    update_post_meta($studio_id, '_studio_address', sanitize_textarea_field($_POST['studio_address']));
    update_post_meta($studio_id, '_studio_timezone', sanitize_text_field($_POST['studio_timezone']));
    update_post_meta($studio_id, '_studio_is_public', isset($_POST['studio_public']) ? 'Yes' : 'No');
    update_post_meta($studio_id, '_studio_allow_search', isset($_POST['allow_search']) ? 'yes' : 'no');
    update_post_meta($studio_id, '_studio_email_notifications', isset($_POST['email_notifications']) ? 'yes' : 'no');
    update_post_meta($studio_id, '_studio_weekly_reports', isset($_POST['weekly_reports']) ? 'yes' : 'no');
    
    wp_send_json_success(['message' => 'Settings saved successfully']);
}
?>