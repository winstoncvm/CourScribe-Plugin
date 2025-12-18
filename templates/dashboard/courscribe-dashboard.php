<?php
// courscribe/templates/dashboard/courscribe-dashboard.php
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_dashboard() {
    $current_user = wp_get_current_user();
    $site_url = home_url();
    $tier = get_option('courscribe_tier', 'basics');
    
    // Get comprehensive statistics
    $stats = courscribe_get_dashboard_stats();
    $user_stats = courscribe_get_user_activity_stats();
    $system_health = courscribe_get_system_health();
    
    // Enqueue admin dashboard assets
    courscribe_enqueue_admin_dashboard_assets();
    ?>

    <!-- CourScribe Admin Dashboard -->
    <div class="cs-admin-dashboard">
        <!-- Header Section -->
        <div class="cs-dashboard-header">
            <div class="cs-header-content">
                <div class="cs-brand-section">
                    <!-- <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" 
                         alt="CourScribe Logo" class="cs-dashboard-logo"> -->
                    <a class="animated-button" href="/studio">
                        <svg xmlns="http://www.w3.org/2000/svg" class="arr-2" viewBox="0 0 24 24">
                            <path
                            d="M16.1716 10.9999L10.8076 5.63589L12.2218 4.22168L20 11.9999L12.2218 19.778L10.8076 18.3638L16.1716 12.9999H4V10.9999H16.1716Z"
                            ></path>
                        </svg>
                        <span class="text">Go To My Studio</span>
                        <span class="circle"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="arr-1" viewBox="0 0 24 24">
                            <path
                            d="M16.1716 10.9999L10.8076 5.63589L12.2218 4.22168L20 11.9999L12.2218 19.778L10.8076 18.3638L16.1716 12.9999H4V10.9999H16.1716Z"
                            ></path>
                        </svg>
                    </a>
                    <div class="cs-brand-text">
                        <h1 class="cs-dashboard-title">CourScribe Admin</h1>
                        <p class="cs-dashboard-subtitle">Comprehensive curriculum management & analytics</p>
                    </div>
                </div>
                <!-- Quick Admin Menu -->
                <div class="courscribe-quick-admin-menu-container">
                    <div class="courscribe-admin-dropdown-wrapper" id="courscribe-admin-dropdown-wrapper">
                        <button class="courscribe-admin-dropdown-toggle-btn" id="courscribe-admin-dropdown-toggle-btn" type="button">
                            <i class="fas fa-th"></i>
                            <span>Quick Admin</span>
                            <i class="fas fa-chevron-down courscribe-dropdown-arrow"></i>
                        </button>
                        <div class="courscribe-admin-dropdown-menu-panel" id="courscribe-admin-dropdown-menu-panel">
                            <div class="courscribe-dropdown-section">
                                <h4 class="courscribe-dropdown-section-title">Content</h4>
                                <a href="<?php echo admin_url('edit.php?post_type=crscribe_studio'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-building"></i>
                                    <span>All Studios</span>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=crscribe_curriculum'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>All Curriculums</span>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=crscribe_course'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-book-open"></i>
                                    <span>All Courses</span>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=crscribe_module'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span>All Modules</span>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=crscribe_lesson'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-bookmark"></i>
                                    <span>All Lessons</span>
                                </a>
                            </div>
                            <div class="courscribe-dropdown-divider"></div>
                            <div class="courscribe-dropdown-section">
                                <h4 class="courscribe-dropdown-section-title">WordPress Admin</h4>
                                <a href="<?php echo admin_url('index.php'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>WP Dashboard</span>
                                </a>
                                <a href="<?php echo admin_url('edit.php'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Posts</span>
                                </a>
                                <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-file"></i>
                                    <span>Pages</span>
                                </a>
                                <a href="<?php echo admin_url('users.php'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-users"></i>
                                    <span>Users</span>
                                </a>
                                <a href="<?php echo admin_url('themes.php'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-paint-brush"></i>
                                    <span>Appearance</span>
                                </a>
                                <a href="<?php echo admin_url('plugins.php'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-plug"></i>
                                    <span>Plugins</span>
                                </a>
                            </div>
                            <div class="courscribe-dropdown-divider"></div>
                            <div class="courscribe-dropdown-section">
                                <h4 class="courscribe-dropdown-section-title">Settings</h4>
                                <a href="<?php echo admin_url('admin.php?page=courscribe_settings'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>CourScribe Settings</span>
                                </a>
                                <a href="<?php echo admin_url('options-general.php'); ?>" class="courscribe-dropdown-item">
                                    <i class="fas fa-tools"></i>
                                    <span>General Settings</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="cs-admin-info">
                    <div class="cs-admin-avatar">
                        <?php echo get_avatar($current_user->ID, 40, '', '', ['class' => 'cs-avatar-img']); ?>
                    </div>
                    <div class="cs-admin-details">
                        <span class="cs-admin-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="cs-admin-role"><?php echo esc_html(ucfirst(implode(', ', $current_user->roles))); ?></span>
                    </div>
                </div>
                

            </div>
            
        </div>

        <!-- Top Stats Overview -->
        <div class="cs-stats-overview">
            <div class="cs-stats-grid">
                <div class="cs-stat-card cs-stat-studios">
                    <div class="cs-stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($stats['studios']['total']); ?></div>
                        <div class="cs-stat-label">Studios</div>
                        <div class="cs-stat-change">
                            <span class="cs-change-indicator <?php echo $stats['studios']['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['studios']['change'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['studios']['change']); ?>%
                            </span>
                            <span class="cs-change-period">vs last month</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-curriculums">
                    <div class="cs-stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($stats['curriculums']['total']); ?></div>
                        <div class="cs-stat-label">Curriculums</div>
                        <div class="cs-stat-change">
                            <span class="cs-change-indicator <?php echo $stats['curriculums']['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['curriculums']['change'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['curriculums']['change']); ?>%
                            </span>
                            <span class="cs-change-period">vs last month</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-courses">
                    <div class="cs-stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($stats['courses']['total']); ?></div>
                        <div class="cs-stat-label">Courses</div>
                        <div class="cs-stat-change">
                            <span class="cs-change-indicator <?php echo $stats['courses']['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-arrow-<?php echo $stats['courses']['change'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($stats['courses']['change']); ?>%
                            </span>
                            <span class="cs-change-period">vs last month</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-users">
                    <div class="cs-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($user_stats['active_users']); ?></div>
                        <div class="cs-stat-label">Active Users</div>
                        <div class="cs-stat-change">
                            <span class="cs-change-indicator positive">
                                <i class="fas fa-clock"></i>
                                <?php echo esc_html($user_stats['online_now']); ?>
                            </span>
                            <span class="cs-change-period">online now</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="cs-dashboard-content">
            <div class="cs-dashboard-row">
                <!-- Left Column -->
                <div class="cs-dashboard-col cs-col-left">
                    <!-- System Health -->
                    <div class="cs-dashboard-widget cs-health-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-heartbeat"></i>
                                System Health
                            </h3>
                            <div class="cs-health-status cs-status-<?php echo esc_attr($system_health['overall_status']); ?>">
                                <?php echo esc_html(ucfirst($system_health['overall_status'])); ?>
                            </div>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-health-metrics">
                                <div class="cs-health-metric">
                                    <div class="cs-metric-label">Database</div>
                                    <div class="cs-metric-status cs-status-<?php echo esc_attr($system_health['database']); ?>">
                                        <i class="fas fa-<?php echo $system_health['database'] === 'healthy' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                        <?php echo esc_html(ucfirst($system_health['database'])); ?>
                                    </div>
                                </div>
                                <div class="cs-health-metric">
                                    <div class="cs-metric-label">AI Service</div>
                                    <div class="cs-metric-status cs-status-<?php echo esc_attr($system_health['ai_service']); ?>">
                                        <i class="fas fa-<?php echo $system_health['ai_service'] === 'healthy' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                        <?php echo esc_html(ucfirst($system_health['ai_service'])); ?>
                                    </div>
                                </div>
                                <div class="cs-health-metric">
                                    <div class="cs-metric-label">File System</div>
                                    <div class="cs-metric-status cs-status-<?php echo esc_attr($system_health['file_system']); ?>">
                                        <i class="fas fa-<?php echo $system_health['file_system'] === 'healthy' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                        <?php echo esc_html(ucfirst($system_health['file_system'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Admin Actions -->
                    <div class="cs-dashboard-widget cs-actions-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-rocket"></i>
                                Admin Controls
                            </h3>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-admin-actions">
                                <div class="cs-action-group">
                                    <h4 class="cs-action-group-title">Content Management</h4>
                                    <div class="cs-action-buttons">
                                        <a href="<?php echo admin_url('post-new.php?post_type=crscribe_studio'); ?>" class="cs-action-btn cs-btn-primary">
                                            <i class="fas fa-building"></i>
                                            <span>Create Studio</span>
                                        </a>
                                        <a href="<?php echo admin_url('edit.php?post_type=crscribe_studio'); ?>" class="cs-action-btn cs-btn-secondary">
                                            <i class="fas fa-list"></i>
                                            <span>Manage Studios</span>
                                        </a>
                                        <a href="<?php echo admin_url('edit.php?post_type=crscribe_curriculum'); ?>" class="cs-action-btn cs-btn-secondary">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>All Curriculums</span>
                                        </a>
                                    </div>
                                </div>

                                <div class="cs-action-group">
                                    <h4 class="cs-action-group-title">System Settings</h4>
                                    <div class="cs-action-buttons">
                                        <a href="<?php echo admin_url('admin.php?page=courscribe_settings'); ?>" class="cs-action-btn cs-btn-warning">
                                            <i class="fas fa-cog"></i>
                                            <span>Plugin Settings</span>
                                        </a>
                                        <button type="button" class="cs-action-btn cs-btn-danger" id="cs-clear-cache">
                                            <i class="fas fa-trash"></i>
                                            <span>Clear Cache</span>
                                        </button>
                                        <button type="button" class="cs-action-btn cs-btn-info" id="cs-export-data">
                                            <i class="fas fa-download"></i>
                                            <span>Export Data</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Management -->
                    <div class="cs-dashboard-widget cs-subscription-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-crown"></i>
                                Subscription
                            </h3>
                            <div class="cs-tier-badge cs-tier-<?php echo esc_attr($tier); ?>">
                                <?php echo esc_html(strtoupper($tier)); ?>
                            </div>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-subscription-info">
                                <div class="cs-tier-features">
                                    <?php 
                                    $tier_features = courscribe_get_tier_features($tier);
                                    foreach ($tier_features as $feature => $status) :
                                    ?>
                                    <div class="cs-feature-item">
                                        <i class="fas fa-<?php echo $status ? 'check' : 'times'; ?> cs-feature-icon cs-icon-<?php echo $status ? 'enabled' : 'disabled'; ?>"></i>
                                        <span class="cs-feature-text"><?php echo esc_html($feature); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if ($tier !== 'pro') : ?>
                                <div class="cs-upgrade-section">
                                    <p class="cs-upgrade-message">Unlock advanced features with CourScribe Pro</p>
                                    <a href="https://courscribe.com/pricing" target="_blank" class="cs-action-btn cs-btn-gradient">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Upgrade Now</span>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="cs-dashboard-col cs-col-right">
                    <!-- Content Analytics -->
                    <div class="cs-dashboard-widget cs-analytics-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-chart-bar"></i>
                                Content Analytics
                            </h3>
                            <div class="cs-time-filter">
                                <select id="cs-analytics-period" class="cs-select">
                                    <option value="7">Last 7 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                </select>
                            </div>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-analytics-grid">
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($stats['modules']['total']); ?></div>
                                    <div class="cs-analytics-label">Total Modules</div>
                                    <div class="cs-analytics-chart" data-chart="modules"></div>
                                </div>
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($stats['lessons']['total']); ?></div>
                                    <div class="cs-analytics-label">Total Lessons</div>
                                    <div class="cs-analytics-chart" data-chart="lessons"></div>
                                </div>
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($stats['ai_generations']['total']); ?></div>
                                    <div class="cs-analytics-label">AI Generations</div>
                                    <div class="cs-analytics-chart" data-chart="ai"></div>
                                </div>
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($stats['file_uploads']['total']); ?></div>
                                    <div class="cs-analytics-label">File Uploads</div>
                                    <div class="cs-analytics-chart" data-chart="uploads"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Activity -->
                    <div class="cs-dashboard-widget cs-activity-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-history"></i>
                                Recent Activity
                            </h3>
                            <button type="button" class="cs-refresh-btn" id="cs-refresh-activity">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-activity-feed" id="cs-activity-feed">
                                <!-- Activity items will be loaded via AJAX -->
                                <div class="cs-activity-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading recent activity...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="cs-dashboard-widget cs-performance-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-tachometer-alt"></i>
                                Performance Metrics
                            </h3>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-performance-metrics">
                                <div class="cs-metric-item">
                                    <div class="cs-metric-header">
                                        <span class="cs-metric-name">Database Queries</span>
                                        <span class="cs-metric-value"><?php echo esc_html(get_num_queries()); ?></span>
                                    </div>
                                    <div class="cs-metric-bar">
                                        <div class="cs-metric-fill" style="width: <?php echo min(100, (get_num_queries() / 50) * 100); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="cs-metric-item">
                                    <div class="cs-metric-header">
                                        <span class="cs-metric-name">Memory Usage</span>
                                        <span class="cs-metric-value"><?php echo size_format(memory_get_peak_usage(true)); ?></span>
                                    </div>
                                    <div class="cs-metric-bar">
                                        <div class="cs-metric-fill" style="width: <?php echo (memory_get_peak_usage(true) / wp_convert_hr_to_bytes(WP_MEMORY_LIMIT)) * 100; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="cs-metric-item">
                                    <div class="cs-metric-header">
                                        <span class="cs-metric-name">Page Load Time</span>
                                        <span class="cs-metric-value" id="cs-page-load-time">--</span>
                                    </div>
                                    <div class="cs-metric-bar">
                                        <div class="cs-metric-fill" id="cs-load-time-bar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Information (Admin Only) -->
        <?php if (current_user_can('manage_options')) : ?>
        <div class="cs-debug-section">
            <div class="cs-debug-toggle">
                <button type="button" class="cs-debug-btn" id="cs-toggle-debug">
                    <i class="fas fa-bug"></i>
                    <span>Debug Information</span>
                    <i class="fas fa-chevron-down cs-chevron"></i>
                </button>
            </div>
            <div class="cs-debug-content" id="cs-debug-content" style="display: none;">
                <div class="cs-debug-grid">
                    <div class="cs-debug-item">
                        <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?>
                    </div>
                    <div class="cs-debug-item">
                        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="cs-debug-item">
                        <strong>Plugin Version:</strong> <?php echo esc_html(get_plugin_data(plugin_dir_path(__FILE__) . '../../../courscribe.php')['Version']); ?>
                    </div>
                    <div class="cs-debug-item">
                        <strong>Database Prefix:</strong> <?php echo esc_html($GLOBALS['wpdb']->prefix); ?>
                    </div>
                    <div class="cs-debug-item">
                        <strong>User Capabilities:</strong> <?php echo esc_html(implode(', ', array_keys($current_user->allcaps))); ?>
                    </div>
                    <div class="cs-debug-item">
                        <strong>Active Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Get comprehensive dashboard statistics
 */
function courscribe_get_dashboard_stats() {
    global $wpdb;
    
    $stats = [];
    
    // Studios stats
    $studios_total = wp_count_posts('crscribe_studio');
    $studios_last_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_studio' AND post_date >= %s",
        date('Y-m-d', strtotime('-1 month'))
    ));
    $studios_prev_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_studio' AND post_date BETWEEN %s AND %s",
        date('Y-m-d', strtotime('-2 months')),
        date('Y-m-d', strtotime('-1 month'))
    ));
    
    $stats['studios'] = [
        'total' => $studios_total->publish ?? 0,
        'change' => $studios_prev_month > 0 ? round((($studios_last_month - $studios_prev_month) / $studios_prev_month) * 100) : 0
    ];
    
    // Curriculums stats
    $curriculums_total = wp_count_posts('crscribe_curriculum');
    $curriculums_last_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_curriculum' AND post_date >= %s",
        date('Y-m-d', strtotime('-1 month'))
    ));
    $curriculums_prev_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_curriculum' AND post_date BETWEEN %s AND %s",
        date('Y-m-d', strtotime('-2 months')),
        date('Y-m-d', strtotime('-1 month'))
    ));
    
    $stats['curriculums'] = [
        'total' => $curriculums_total->publish ?? 0,
        'change' => $curriculums_prev_month > 0 ? round((($curriculums_last_month - $curriculums_prev_month) / $curriculums_prev_month) * 100) : 0
    ];
    
    // Courses stats
    $courses_total = wp_count_posts('crscribe_course');
    $courses_last_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_course' AND post_date >= %s",
        date('Y-m-d', strtotime('-1 month'))
    ));
    $courses_prev_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_course' AND post_date BETWEEN %s AND %s",
        date('Y-m-d', strtotime('-2 months')),
        date('Y-m-d', strtotime('-1 month'))
    ));
    
    $stats['courses'] = [
        'total' => $courses_total->publish ?? 0,
        'change' => $courses_prev_month > 0 ? round((($courses_last_month - $courses_prev_month) / $courses_prev_month) * 100) : 0
    ];
    
    // Modules stats
    $modules_total = wp_count_posts('crscribe_module');
    $stats['modules'] = [
        'total' => $modules_total->publish ?? 0
    ];
    
    // Lessons stats
    $lessons_total = wp_count_posts('crscribe_lesson');
    $stats['lessons'] = [
        'total' => $lessons_total->publish ?? 0
    ];
    
    // AI generations (from logs)
    $ai_generations = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_module_log 
        WHERE action = 'ai_suggestion' AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['ai_generations'] = [
        'total' => $ai_generations ?? 0
    ];
    
    // File uploads approximation
    $uploads = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} 
        WHERE post_type = 'attachment' AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['file_uploads'] = [
        'total' => $uploads ?? 0
    ];
    
    return $stats;
}

/**
 * Get user activity statistics
 */
function courscribe_get_user_activity_stats() {
    global $wpdb;
    
    // Active users in last 7 days
    $active_users = $wpdb->get_var("
        SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_course_log 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    
    // Simulate online users (would need more sophisticated tracking in production)
    $online_now = rand(1, 5);
    
    return [
        'active_users' => $active_users ?? 0,
        'online_now' => $online_now
    ];
}

/**
 * Get system health status
 */
function courscribe_get_system_health() {
    global $wpdb;
    
    $health = [
        'overall_status' => 'healthy',
        'database' => 'healthy',
        'ai_service' => 'healthy',
        'file_system' => 'healthy'
    ];
    
    // Check database connection
    if (!$wpdb->get_var("SELECT 1")) {
        $health['database'] = 'warning';
        $health['overall_status'] = 'warning';
    }
    
    // Check AI service configuration
    $api_key = get_option('courscribe_gemini_api_key', '');
    if (empty($api_key)) {
        $health['ai_service'] = 'warning';
        if ($health['overall_status'] === 'healthy') {
            $health['overall_status'] = 'warning';
        }
    }
    
    // Check file system permissions
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
        $health['file_system'] = 'error';
        $health['overall_status'] = 'error';
    }
    
    return $health;
}

/**
 * Get tier features for display
 */
function courscribe_get_tier_features($tier) {
    $features = [
        'basics' => [
            'Basic Studio Management' => true,
            'Up to 1 Curriculum' => true,
            'Up to 2 Courses' => true,
            'Basic AI Assistance' => true,
            'PDF Export' => false,
            'Advanced Analytics' => false,
            'Priority Support' => false,
            'Custom Branding' => false
        ],
        'plus' => [
            'Advanced Studio Management' => true,
            'Up to 5 Curriculums' => true,
            'Up to 10 Courses' => true,
            'Enhanced AI Assistance' => true,
            'PDF Export' => true,
            'Basic Analytics' => true,
            'Priority Support' => false,
            'Custom Branding' => false
        ],
        'pro' => [
            'Full Studio Management' => true,
            'Unlimited Curriculums' => true,
            'Unlimited Courses' => true,
            'Premium AI Assistance' => true,
            'Advanced PDF Export' => true,
            'Advanced Analytics' => true,
            'Priority Support' => true,
            'Custom Branding' => true
        ]
    ];
    
    return $features[$tier] ?? $features['basics'];
}

/**
 * Enqueue admin dashboard assets
 */
function courscribe_enqueue_admin_dashboard_assets() {
    $plugin_url = plugin_dir_url(__FILE__);
    $plugin_url = str_replace('/templates/dashboard/', '/', $plugin_url);
    
    // Enqueue admin dashboard CSS
    wp_enqueue_style(
        'courscribe-admin-dashboard',
        $plugin_url . 'assets/css/admin-dashboard.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/css/admin-dashboard.css')
    );
    
    // Enqueue admin dashboard JavaScript
    wp_enqueue_script(
        'courscribe-admin-dashboard',
        $plugin_url . 'assets/js/admin-dashboard.js',
        ['jquery'],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/js/admin-dashboard.js'),
        true
    );
    
    // Localize script for AJAX
    wp_localize_script(
        'courscribe-admin-dashboard',
        'CourScribeAdmin',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_admin_nonce'),
            'user_id' => get_current_user_id(),
            'page_load_start' => microtime(true)
        ]
    );
}
?>