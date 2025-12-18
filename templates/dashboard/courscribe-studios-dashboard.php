<?php
// courscribe/templates/dashboard/courscribe-studios-dashboard.php
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_studios_dashboard() {
    $current_user = wp_get_current_user();
    $site_url = home_url();
    $tier = get_option('courscribe_tier', 'basics');
    
    // Get comprehensive studio data for site admin
    $studio_stats = courscribe_get_studio_management_stats();
    $studios = courscribe_get_all_studios_with_details();
    $recent_activity = courscribe_get_studio_activity();
    
    // Enqueue studios dashboard assets
    courscribe_enqueue_studios_dashboard_assets();
    ?>

    <!-- CourScribe Studios Dashboard -->
    <div class="cs-studios-dashboard">
        <!-- Header Section -->
        <div class="cs-dashboard-header">
            <div class="cs-header-content">
                <div class="cs-brand-section">
                    <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" 
                         alt="CourScribe Logo" class="cs-dashboard-logo">
                    <div class="cs-brand-text">
                        <h1 class="cs-dashboard-title">Studios Management</h1>
                        <p class="cs-dashboard-subtitle">Comprehensive studio oversight & administration</p>
                    </div>
                </div>
                <div class="cs-admin-info">
                    <div class="cs-admin-avatar">
                        <?php echo get_avatar($current_user->ID, 40, '', '', ['class' => 'cs-avatar-img']); ?>
                    </div>
                    <div class="cs-admin-details">
                        <span class="cs-admin-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="cs-admin-role">Site Administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Studios Overview Stats -->
        <div class="cs-studios-overview">
            <div class="cs-overview-stats">
                <div class="cs-stat-card cs-stat-total">
                    <div class="cs-stat-icon cs-icon-studios">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($studio_stats['total_studios']); ?></div>
                        <div class="cs-stat-label">Total Studios</div>
                        <div class="cs-stat-trend">
                            <span class="cs-trend-indicator positive">
                                <i class="fas fa-arrow-up"></i>
                                <?php echo esc_html($studio_stats['studio_growth_rate']); ?>%
                            </span>
                            <span class="cs-trend-period">vs last month</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-active">
                    <div class="cs-stat-icon cs-icon-active">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($studio_stats['active_studios']); ?></div>
                        <div class="cs-stat-label">Active Studios</div>
                        <div class="cs-stat-meta">
                            <span><?php echo esc_html(round(($studio_stats['active_studios'] / max($studio_stats['total_studios'], 1)) * 100)); ?>% of total studios</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-users">
                    <div class="cs-stat-icon cs-icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($studio_stats['total_users']); ?></div>
                        <div class="cs-stat-label">Total Users</div>
                        <div class="cs-stat-meta">
                            <span><?php echo esc_html($studio_stats['active_users']); ?> active this week</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-content">
                    <div class="cs-stat-icon cs-icon-content">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($studio_stats['total_curriculums']); ?></div>
                        <div class="cs-stat-label">Total Curriculums</div>
                        <div class="cs-stat-meta">
                            <span><?php echo esc_html($studio_stats['avg_curriculums_per_studio']); ?> avg per studio</span>
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
                    <!-- Site Management -->
                    <div class="cs-dashboard-widget cs-site-management">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-cogs"></i>
                                Site Management
                            </h3>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-admin-actions">
                                <div class="cs-action-group">
                                    <h4 class="cs-action-group-title">Plugin Administration</h4>
                                    <div class="cs-action-buttons">
                                        <button type="button" class="cs-action-btn cs-btn-primary" id="cs-clear-cache">
                                            <i class="fas fa-broom"></i>
                                            <span>Clear Plugin Cache</span>
                                        </button>
                                        <button type="button" class="cs-action-btn cs-btn-secondary" id="cs-export-all-data">
                                            <i class="fas fa-download"></i>
                                            <span>Export All Data</span>
                                        </button>
                                        <button type="button" class="cs-action-btn cs-btn-info" id="cs-system-health">
                                            <i class="fas fa-heartbeat"></i>
                                            <span>System Health Check</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="cs-action-group">
                                    <h4 class="cs-action-group-title">Studio Management</h4>
                                    <div class="cs-action-buttons">
                                        <a href="<?php echo admin_url('post-new.php?post_type=crscribe_studio'); ?>" class="cs-action-btn cs-btn-gradient">
                                            <i class="fas fa-plus"></i>
                                            <span>Create New Studio</span>
                                        </a>
                                        <button type="button" class="cs-action-btn cs-btn-warning" id="cs-archive-inactive">
                                            <i class="fas fa-archive"></i>
                                            <span>Archive Inactive Studios</span>
                                        </button>
                                        <button type="button" class="cs-action-btn cs-btn-secondary" id="cs-bulk-operations">
                                            <i class="fas fa-tasks"></i>
                                            <span>Bulk Operations</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="cs-dashboard-widget cs-system-health">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-shield-alt"></i>
                                System Health
                            </h3>
                            <span class="cs-health-status cs-status-healthy">Healthy</span>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-health-metrics">
                                <div class="cs-health-metric">
                                    <span class="cs-metric-label">Database Performance</span>
                                    <div class="cs-metric-status">
                                        <i class="fas fa-check cs-icon-enabled"></i>
                                        <span>Optimal</span>
                                    </div>
                                </div>
                                <div class="cs-health-metric">
                                    <span class="cs-metric-label">Plugin Version</span>
                                    <div class="cs-metric-status">
                                        <i class="fas fa-check cs-icon-enabled"></i>
                                        <span>v1.1.9</span>
                                    </div>
                                </div>
                                <div class="cs-health-metric">
                                    <span class="cs-metric-label">Security Status</span>
                                    <div class="cs-metric-status">
                                        <i class="fas fa-check cs-icon-enabled"></i>
                                        <span>Secure</span>
                                    </div>
                                </div>
                                <div class="cs-health-metric">
                                    <span class="cs-metric-label">Cache Status</span>
                                    <div class="cs-metric-status">
                                        <i class="fas fa-check cs-icon-enabled"></i>
                                        <span>Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Widget -->
                    <div class="cs-dashboard-widget cs-analytics-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-chart-pie"></i>
                                Usage Analytics
                            </h3>
                            <div class="cs-time-filter">
                                <select id="cs-analytics-period" class="cs-select">
                                    <option value="7">Last 7 days</option>
                                    <option value="30" selected>Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                </select>
                            </div>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-analytics-grid">
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($studio_stats['curriculum_creation_rate']); ?></div>
                                    <div class="cs-analytics-label">New Curriculums</div>
                                    <div class="cs-analytics-chart" data-chart="curriculum_creation" style="width: 75%;"></div>
                                </div>
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($studio_stats['user_engagement_rate']); ?>%</div>
                                    <div class="cs-analytics-label">User Engagement</div>
                                    <div class="cs-analytics-chart" data-chart="user_engagement" style="width: <?php echo esc_attr($studio_stats['user_engagement_rate']); ?>%;"></div>
                                </div>
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($studio_stats['ai_usage_count']); ?></div>
                                    <div class="cs-analytics-label">AI Generations</div>
                                    <div class="cs-analytics-chart" data-chart="ai_usage" style="width: 60%;"></div>
                                </div>
                                <div class="cs-analytics-item">
                                    <div class="cs-analytics-number"><?php echo esc_html($studio_stats['storage_usage']); ?>%</div>
                                    <div class="cs-analytics-label">Storage Used</div>
                                    <div class="cs-analytics-chart" data-chart="storage" style="width: <?php echo esc_attr($studio_stats['storage_usage']); ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="cs-dashboard-col cs-col-right">
                    <!-- Studios Grid -->
                    <div class="cs-dashboard-widget cs-studios-grid-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-building"></i>
                                Studios Overview
                            </h3>
                            <div class="cs-view-controls">
                                <button type="button" class="cs-view-btn active" data-view="grid">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button type="button" class="cs-view-btn" data-view="list">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-studios-grid" id="cs-studios-grid">
                                <?php foreach ($studios as $studio) : ?>
                                <div class="cs-studio-card" data-studio-id="<?php echo esc_attr($studio['id']); ?>">
                                    <div class="cs-card-header">
                                        <div class="cs-studio-status">
                                            <span class="cs-status-badge cs-status-<?php echo esc_attr($studio['status']); ?>">
                                                <?php echo esc_html(ucfirst($studio['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="cs-card-actions">
                                            <div class="cs-dropdown">
                                                <button type="button" class="cs-dropdown-btn">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="cs-dropdown-menu">
                                                    <a href="<?php echo esc_url(get_edit_post_link($studio['id'])); ?>" class="cs-dropdown-item">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                    <a href="<?php echo esc_url(get_permalink($studio['id'])); ?>" class="cs-dropdown-item">
                                                        <i class="fas fa-external-link-alt"></i> Visit Studio
                                                    </a>
                                                    <button type="button" class="cs-dropdown-item cs-view-logs" data-id="<?php echo esc_attr($studio['id']); ?>">
                                                        <i class="fas fa-history"></i> Activity Logs
                                                    </button>
                                                    <div class="cs-dropdown-divider"></div>
                                                    <button type="button" class="cs-dropdown-item cs-manage-users" data-id="<?php echo esc_attr($studio['id']); ?>">
                                                        <i class="fas fa-users-cog"></i> Manage Users
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="cs-card-content">
                                        <div class="cs-card-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <h4 class="cs-card-title"><?php echo esc_html($studio['title']); ?></h4>
                                        <p class="cs-card-description"><?php echo esc_html(wp_trim_words($studio['description'], 12)); ?></p>
                                        
                                        <div class="cs-studio-owner">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo esc_html($studio['owner_name']); ?></span>
                                        </div>
                                    </div>

                                    <div class="cs-card-stats">
                                        <div class="cs-stat-item">
                                            <span class="cs-stat-icon"><i class="fas fa-graduation-cap"></i></span>
                                            <span class="cs-stat-text"><?php echo esc_html($studio['curriculum_count']); ?> Curriculums</span>
                                        </div>
                                        <div class="cs-stat-item">
                                            <span class="cs-stat-icon"><i class="fas fa-users"></i></span>
                                            <span class="cs-stat-text"><?php echo esc_html($studio['user_count']); ?> Users</span>
                                        </div>
                                        <div class="cs-stat-item">
                                            <span class="cs-stat-icon"><i class="fas fa-chart-line"></i></span>
                                            <span class="cs-stat-text"><?php echo esc_html($studio['activity_score']); ?> Activity</span>
                                        </div>
                                    </div>

                                    <div class="cs-card-footer">
                                        <div class="cs-card-meta">
                                            <span class="cs-meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo esc_html($studio['created_date']); ?>
                                            </span>
                                            <span class="cs-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <?php echo esc_html($studio['last_activity']); ?>
                                            </span>
                                        </div>
                                        <div class="cs-studio-health">
                                            <div class="cs-health-indicator cs-health-<?php echo esc_attr($studio['health_status']); ?>">
                                                <i class="fas fa-circle"></i>
                                                <span><?php echo esc_html(ucfirst($studio['health_status'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (empty($studios)) : ?>
                            <div class="cs-empty-state">
                                <div class="cs-empty-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h4 class="cs-empty-title">No studios found</h4>
                                <p class="cs-empty-description">Create the first studio to get started with CourScribe.</p>
                                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_studio'); ?>" class="cs-btn cs-btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create First Studio
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Activity Feed -->
                    <div class="cs-dashboard-widget cs-activity-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-history"></i>
                                Recent Studio Activity
                            </h3>
                            <button type="button" class="cs-refresh-btn" id="cs-refresh-activity">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-activity-feed" id="cs-studio-activity">
                                <?php foreach ($recent_activity as $activity) : ?>
                                <div class="cs-activity-item">
                                    <div class="cs-activity-icon">
                                        <i class="fas <?php echo esc_attr(courscribe_get_activity_icon_dash($activity['action'])); ?>"></i>
                                    </div>
                                    <div class="cs-activity-content">
                                        <div class="cs-activity-title"><?php echo esc_html($activity['title']); ?></div>
                                        <div class="cs-activity-meta">
                                            <span class="cs-activity-user"><?php echo esc_html($activity['user_name']); ?></span>
                                            <span class="cs-activity-time"><?php echo esc_html(courscribe_time_ago($activity['timestamp'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get comprehensive studio management statistics for site admin
 */
function courscribe_get_studio_management_stats() {
    global $wpdb;
    
    $stats = [];
    
    // Total studios
    $total_studios = wp_count_posts('crscribe_studio');
    $stats['total_studios'] = $total_studios->publish ?? 0;
    
    // Studio growth rate calculation
    $last_month_studios = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_studio' AND post_date >= %s",
        date('Y-m-d', strtotime('-1 month'))
    ));
    $prev_month_studios = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_studio' AND post_date BETWEEN %s AND %s",
        date('Y-m-d', strtotime('-2 months')),
        date('Y-m-d', strtotime('-1 month'))
    ));
    $stats['studio_growth_rate'] = $prev_month_studios > 0 ? round((($last_month_studios - $prev_month_studios) / $prev_month_studios) * 100) : 0;
    
    // Active studios (with activity in last 30 days)
    $active_studios = $wpdb->get_var("
        SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->prefix}courscribe_course_log cl ON p.ID = cl.studio_id
        WHERE p.post_type = 'crscribe_studio' 
        AND p.post_status = 'publish'
        AND cl.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['active_studios'] = $active_studios ?? 0;
    
    // Total users (studio owners + collaborators)
    $studio_owners = $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} 
        WHERE post_type = 'crscribe_studio' AND post_status = 'publish'
    ");
    $collaborators = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->usermeta} 
        WHERE meta_key = '_courscribe_studio_id' AND meta_value != ''
    ");
    $stats['total_users'] = ($studio_owners ?? 0) + ($collaborators ?? 0);
    
    // Active users this week
    $stats['active_users'] = $wpdb->get_var("
        SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_course_log 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ") ?? 0;
    
    // Total curriculums
    $total_curriculums = wp_count_posts('crscribe_curriculum');
    $stats['total_curriculums'] = $total_curriculums->publish ?? 0;
    
    // Average curriculums per studio
    $stats['avg_curriculums_per_studio'] = $stats['total_studios'] > 0 ? 
        round($stats['total_curriculums'] / $stats['total_studios'], 1) : 0;
    
    // Curriculum creation rate (this month)
    $stats['curriculum_creation_rate'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_curriculum' AND post_date >= %s",
        date('Y-m-01')
    )) ?? 0;
    
    // User engagement rate (percentage of users active this week)
    $stats['user_engagement_rate'] = $stats['total_users'] > 0 ? 
        round(($stats['active_users'] / $stats['total_users']) * 100) : 0;
    
    // AI usage count (this month)
    $stats['ai_usage_count'] = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_course_log 
        WHERE action LIKE '%ai%' AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ") ?? 0;
    
    // Storage usage (simplified - would need real calculation)
    $stats['storage_usage'] = rand(15, 45); // Placeholder
    
    return $stats;
}

/**
 * Get all studios with comprehensive details for site admin
 */
function courscribe_get_all_studios_with_details() {
    $studios = get_posts([
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    
    $studios_data = [];
    
    foreach ($studios as $studio) {
        $curriculum_count = count(get_posts([
            'post_type' => 'crscribe_curriculum',
            'post_status' => 'publish',
            'meta_key' => '_studio_id',
            'meta_value' => $studio->ID,
            'numberposts' => -1,
        ]));
        
        $user_count = count(get_users([
            'meta_key' => '_courscribe_studio_id',
            'meta_value' => $studio->ID,
            'fields' => 'ID',
        ])) + 1; // +1 for studio owner
        
        $owner = get_user_by('ID', $studio->post_author);
        
        // Calculate activity score (simplified)
        global $wpdb;
        $activity_score = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_course_log WHERE studio_id = %d AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $studio->ID
        )) ?? 0;
        
        // Get last activity
        $last_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(timestamp) FROM {$wpdb->prefix}courscribe_course_log WHERE studio_id = %d",
            $studio->ID
        ));
        
        // Determine health status
        $health_status = 'good';
        if ($activity_score == 0) {
            $health_status = 'inactive';
        } elseif ($activity_score < 5) {
            $health_status = 'warning';
        }
        
        $studios_data[] = [
            'id' => $studio->ID,
            'title' => $studio->post_title,
            'description' => $studio->post_content,
            'status' => $studio->post_status,
            'owner_name' => $owner ? $owner->display_name : 'Unknown',
            'curriculum_count' => $curriculum_count,
            'user_count' => $user_count,
            'activity_score' => $activity_score,
            'created_date' => date('M j, Y', strtotime($studio->post_date)),
            'last_activity' => $last_activity ? courscribe_time_ago($last_activity) : 'No activity',
            'health_status' => $health_status
        ];
    }
    
    return $studios_data;
}

/**
 * Get recent studio activity for site admin
 */
function courscribe_get_studio_activity() {
    global $wpdb;
    
    $activities = $wpdb->get_results("
        SELECT cl.*, u.display_name as user_name, p.post_title as studio_name
        FROM {$wpdb->prefix}courscribe_course_log cl
        LEFT JOIN {$wpdb->users} u ON cl.user_id = u.ID
        LEFT JOIN {$wpdb->posts} p ON cl.studio_id = p.ID
        WHERE cl.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY cl.timestamp DESC
        LIMIT 15
    ", ARRAY_A);
    
    $activity_data = [];
    foreach ($activities as $activity) {
        $activity_data[] = [
            'title' => courscribe_format_studio_activity_title($activity),
            'action' => $activity['action'],
            'user_name' => $activity['user_name'] ?: 'Unknown User',
            'timestamp' => $activity['timestamp']
        ];
    }
    
    return $activity_data;
}

/**
 * Format studio activity title
 */
function courscribe_format_studio_activity_title($activity) {
    $actions = [
        'create' => 'Created',
        'update' => 'Updated',
        'delete' => 'Deleted',
        'archive' => 'Archived',
        'restore' => 'Restored'
    ];
    
    $action_text = $actions[$activity['action']] ?? 'Modified';
    $studio_name = $activity['studio_name'] ?? 'Unknown Studio';
    
    return "{$action_text} content in {$studio_name}";
}

/**
 * Enqueue studios dashboard assets
 */
function courscribe_enqueue_studios_dashboard_assets() {
    $plugin_url = plugin_dir_url(__FILE__);
    $plugin_url = str_replace('/templates/dashboard/', '/', $plugin_url);
    
    // Enqueue shared admin dashboard CSS
    wp_enqueue_style(
        'courscribe-admin-dashboard',
        $plugin_url . 'assets/css/admin-dashboard.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/css/admin-dashboard.css')
    );
    
    // Enqueue studios-specific CSS (reuse curriculums dashboard styles with some modifications)
    wp_enqueue_style(
        'courscribe-studios-dashboard',
        $plugin_url . 'assets/css/curriculums-dashboard.css',
        ['courscribe-admin-dashboard'],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/css/curriculums-dashboard.css')
    );
    
    // Enqueue studios dashboard JavaScript
    wp_enqueue_script(
        'courscribe-studios-dashboard',
        $plugin_url . 'assets/js/studios-dashboard.js',
        ['jquery', 'courscribe-admin-dashboard'],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/js/studios-dashboard.js'),
        true
    );
    
    // Localize script for AJAX
    wp_localize_script(
        'courscribe-studios-dashboard',
        'CourScribeStudios',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_studios_nonce'),
            'user_id' => get_current_user_id()
        ]
    );
}
?>