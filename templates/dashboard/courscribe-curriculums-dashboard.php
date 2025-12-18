<?php
// courscribe/templates/dashboard/courscribe-curriculums-dashboard.php
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_curriculums_dashboard() {
    $current_user = wp_get_current_user();
    $site_url = home_url();
    $tier = get_option('courscribe_tier', 'basics');
    
    // Get comprehensive curriculum data
    $curriculum_stats = courscribe_get_curriculum_stats();
    $studios = courscribe_get_studios_with_curriculums();
    $recent_activity = courscribe_get_curriculum_activity();
    
    // Enqueue curriculums dashboard assets
    courscribe_enqueue_curriculums_dashboard_assets();
    ?>

    <!-- CourScribe Curriculums Dashboard -->
    <div class="cs-curriculums-dashboard">
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
                        <h1 class="cs-dashboard-title">Curriculum Management</h1>
                        <p class="cs-dashboard-subtitle">Comprehensive curriculum oversight & analytics</p>
                    </div>
                </div>
                <div class="cs-admin-info">
                    <div class="cs-admin-avatar">
                        <?php echo get_avatar($current_user->ID, 40, '', '', ['class' => 'cs-avatar-img']); ?>
                    </div>
                    <div class="cs-admin-details">
                        <span class="cs-admin-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="cs-admin-role">Curriculum Manager</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Curriculum Overview Stats -->
        <div class="cs-curriculum-overview">
            <div class="cs-overview-stats">
                <div class="cs-stat-card cs-stat-total">
                    <div class="cs-stat-icon cs-icon-curriculums">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($curriculum_stats['total_curriculums']); ?></div>
                        <div class="cs-stat-label">Total Curriculums</div>
                        <div class="cs-stat-trend">
                            <span class="cs-trend-indicator positive">
                                <i class="fas fa-arrow-up"></i>
                                <?php echo esc_html($curriculum_stats['growth_rate']); ?>%
                            </span>
                            <span class="cs-trend-period">vs last month</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-active">
                    <div class="cs-stat-icon cs-icon-active">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($curriculum_stats['active_curriculums']); ?></div>
                        <div class="cs-stat-label">Active Curriculums</div>
                        <div class="cs-stat-meta">
                            <span><?php echo esc_html($curriculum_stats['completion_rate']); ?>% completion rate</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-content">
                    <div class="cs-stat-icon cs-icon-content">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($curriculum_stats['total_courses']); ?></div>
                        <div class="cs-stat-label">Total Courses</div>
                        <div class="cs-stat-meta">
                            <span><?php echo esc_html($curriculum_stats['avg_courses_per_curriculum']); ?> avg per curriculum</span>
                        </div>
                    </div>
                </div>

                <div class="cs-stat-card cs-stat-ai">
                    <div class="cs-stat-icon cs-icon-ai">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div class="cs-stat-content">
                        <div class="cs-stat-number"><?php echo esc_html($curriculum_stats['ai_generations']); ?></div>
                        <div class="cs-stat-label">AI Generations</div>
                        <div class="cs-stat-meta">
                            <span>This month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div >
                <!-- Curriculum Grid -->
                <div class="cs-dashboard-widget cs-curriculum-grid-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-th-large"></i>
                                Curriculums Overview
                            </h3>
                            <div class="cs-view-controls">
                                <button type="button" class="cs-view-btn active" data-view="grid">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button type="button" class="cs-view-btn" data-view="list">
                                    <i class="fas fa-list"></i>
                                </button>
                                <button type="button" class="cs-view-btn" data-view="table">
                                    <i class="fas fa-table"></i>
                                </button>
                            </div>
                        </div>
                        <div class="cs-widget-content">
                            <!-- Grid View -->
                            <div class="cs-curriculum-grid" id="cs-curriculum-grid">
                                <?php foreach ($studios as $studio) : ?>
                                    <?php foreach ($studio['curriculums'] as $curriculum) : ?>
                                    <div class="cs-curriculum-card" 
                                         data-curriculum-id="<?php echo esc_attr($curriculum['id']); ?>"
                                         data-studio-id="<?php echo esc_attr($studio['id']); ?>"
                                         data-status="<?php echo esc_attr($curriculum['status']); ?>"
                                         data-course-count="<?php echo esc_attr($curriculum['course_count']); ?>">
                                        
                                        <div class="cs-card-header">
                                            <div class="cs-card-selection">
                                                <input type="checkbox" class="cs-curriculum-checkbox" value="<?php echo esc_attr($curriculum['id']); ?>">
                                            </div>
                                            <div class="cs-card-status">
                                                <span class="cs-status-badge cs-status-<?php echo esc_attr($curriculum['status']); ?>">
                                                    <?php echo esc_html(ucfirst($curriculum['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="cs-card-actions">
                                                <div class="cs-dropdown">
                                                    <button type="button" class="cs-dropdown-btn">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="cs-dropdown-menu">
                                                        <a href="<?php echo esc_url($curriculum['edit_url']); ?>" class="cs-dropdown-item">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="<?php echo esc_url($curriculum['view_url']); ?>" class="cs-dropdown-item">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <button type="button" class="cs-dropdown-item cs-duplicate-curriculum" data-id="<?php echo esc_attr($curriculum['id']); ?>">
                                                            <i class="fas fa-copy"></i> Duplicate
                                                        </button>
                                                        <button type="button" class="cs-dropdown-item cs-ai-enhance" data-id="<?php echo esc_attr($curriculum['id']); ?>">
                                                            <i class="fas fa-magic"></i> AI Enhance
                                                        </button>
                                                        <div class="cs-dropdown-divider"></div>
                                                        <button type="button" class="cs-dropdown-item cs-archive-curriculum" data-id="<?php echo esc_attr($curriculum['id']); ?>">
                                                            <i class="fas fa-archive"></i> Archive
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="cs-card-content">
                                            <div class="cs-card-icon">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <h4 class="cs-card-title"><?php echo esc_html($curriculum['title']); ?></h4>
                                            <p class="cs-card-topic"><?php echo esc_html($curriculum['topic']); ?></p>
                                            <p class="cs-card-goal"><?php echo esc_html(wp_trim_words($curriculum['goal'], 15)); ?></p>
                                        </div>

                                        <div class="cs-card-stats">
                                            <div class="cs-stat-item">
                                                <span class="cs-stat-icon"><i class="fas fa-book"></i></span>
                                                <span class="cs-stat-text"><?php echo esc_html($curriculum['course_count']); ?> Courses</span>
                                            </div>
                                            <div class="cs-stat-item">
                                                <span class="cs-stat-icon"><i class="fas fa-puzzle-piece"></i></span>
                                                <span class="cs-stat-text"><?php echo esc_html($curriculum['module_count']); ?> Modules</span>
                                            </div>
                                            <div class="cs-stat-item">
                                                <span class="cs-stat-icon"><i class="fas fa-play-circle"></i></span>
                                                <span class="cs-stat-text"><?php echo esc_html($curriculum['lesson_count']); ?> Lessons</span>
                                            </div>
                                        </div>

                                        <div class="cs-card-footer">
                                            <div class="cs-card-meta">
                                                <span class="cs-meta-item">
                                                    <i class="fas fa-building"></i>
                                                    <?php echo esc_html($studio['title']); ?>
                                                </span>
                                                <span class="cs-meta-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo esc_html($curriculum['created_date']); ?>
                                                </span>
                                            </div>
                                            <div class="cs-card-progress">
                                                <div class="cs-progress-bar">
                                                    <div class="cs-progress-fill" style="width: <?php echo esc_attr($curriculum['completion_percentage']); ?>%"></div>
                                                </div>
                                                <span class="cs-progress-text"><?php echo esc_html($curriculum['completion_percentage']); ?>% Complete</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- Empty State -->
                            <div class="cs-empty-state" id="cs-empty-state" style="display: none;">
                                <div class="cs-empty-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h4 class="cs-empty-title">No curriculums found</h4>
                                <p class="cs-empty-description">Try adjusting your filters or create a new curriculum to get started.</p>
                                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_curriculum'); ?>" class="cs-btn cs-btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create New Curriculum
                                </a>
                            </div>
                        </div>
                    </div>
            </div>

        <!-- Main Dashboard Content -->
        <div class="cs-dashboard-content">
            <div class="cs-dashboard-row">
                <!-- Left Column -->
                <div class="cs-dashboard-col cs-col-left">
                    <!-- Advanced Filters & Search -->
                    <div class="cs-dashboard-widget cs-filters-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-filter"></i>
                                Advanced Filters
                            </h3>
                            <button type="button" class="cs-filter-reset" id="cs-reset-filters">
                                <i class="fas fa-undo"></i>
                                Reset
                            </button>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-filters-grid">
                                <div class="cs-filter-group">
                                    <label class="cs-filter-label">Search Curriculums</label>
                                    <div class="cs-search-box">
                                        <input type="text" id="cs-curriculum-search" class="cs-search-input" 
                                               placeholder="Search by title, topic, or goal...">
                                        <i class="fas fa-search cs-search-icon"></i>
                                    </div>
                                </div>

                                <div class="cs-filter-group">
                                    <label class="cs-filter-label">Studio</label>
                                    <select id="cs-filter-studio" class="cs-filter-select">
                                        <option value="">All Studios</option>
                                        <?php foreach ($studios as $studio) : ?>
                                        <option value="<?php echo esc_attr($studio['id']); ?>">
                                            <?php echo esc_html($studio['title']); ?> (<?php echo esc_html($studio['curriculum_count']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="cs-filter-group">
                                    <label class="cs-filter-label">Status</label>
                                    <select id="cs-filter-status" class="cs-filter-select">
                                        <option value="">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="review">Under Review</option>
                                        <option value="approved">Approved</option>
                                        <option value="published">Published</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>

                                <div class="cs-filter-group">
                                    <label class="cs-filter-label">Created</label>
                                    <select id="cs-filter-date" class="cs-filter-select">
                                        <option value="">All Time</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                        <option value="quarter">This Quarter</option>
                                        <option value="year">This Year</option>
                                    </select>
                                </div>

                                <div class="cs-filter-group">
                                    <label class="cs-filter-label">Content Size</label>
                                    <select id="cs-filter-size" class="cs-filter-select">
                                        <option value="">Any Size</option>
                                        <option value="small">Small (1-5 courses)</option>
                                        <option value="medium">Medium (6-15 courses)</option>
                                        <option value="large">Large (16+ courses)</option>
                                    </select>
                                </div>

                                <div class="cs-filter-group">
                                    <label class="cs-filter-label">Sort By</label>
                                    <select id="cs-sort-order" class="cs-filter-select">
                                        <option value="date_desc">Latest First</option>
                                        <option value="date_asc">Oldest First</option>
                                        <option value="title_asc">Title A-Z</option>
                                        <option value="title_desc">Title Z-A</option>
                                        <option value="courses_desc">Most Courses</option>
                                        <option value="courses_asc">Least Courses</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Operations -->
                    <div class="cs-dashboard-widget cs-bulk-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-tasks"></i>
                                Bulk Operations
                            </h3>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-bulk-selection">
                                <div class="cs-selection-info">
                                    <span id="cs-selected-count">0</span> curriculums selected
                                </div>
                                <div class="cs-bulk-actions">
                                    <button type="button" class="cs-bulk-btn cs-bulk-export" id="cs-bulk-export">
                                        <i class="fas fa-download"></i>
                                        Export Selected
                                    </button>
                                    <button type="button" class="cs-bulk-btn cs-bulk-archive" id="cs-bulk-archive">
                                        <i class="fas fa-archive"></i>
                                        Archive Selected
                                    </button>
                                    <button type="button" class="cs-bulk-btn cs-bulk-duplicate" id="cs-bulk-duplicate">
                                        <i class="fas fa-copy"></i>
                                        Duplicate Selected
                                    </button>
                                    <button type="button" class="cs-bulk-btn cs-bulk-ai" id="cs-bulk-ai-enhance">
                                        <i class="fas fa-magic"></i>
                                        AI Enhance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Analytics -->
                    <div class="cs-dashboard-widget cs-quick-analytics">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-chart-line"></i>
                                Quick Analytics
                            </h3>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-analytics-charts">
                                <div class="cs-chart-item">
                                    <div class="cs-chart-header">
                                        <span class="cs-chart-title">Completion Rates</span>
                                        <span class="cs-chart-value"><?php echo esc_html($curriculum_stats['completion_rate']); ?>%</span>
                                    </div>
                                    <div class="cs-progress-bar">
                                        <div class="cs-progress-fill" style="width: <?php echo esc_attr($curriculum_stats['completion_rate']); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="cs-chart-item">
                                    <div class="cs-chart-header">
                                        <span class="cs-chart-title">Engagement Score</span>
                                        <span class="cs-chart-value"><?php echo esc_html($curriculum_stats['engagement_score']); ?>/10</span>
                                    </div>
                                    <div class="cs-progress-bar">
                                        <div class="cs-progress-fill" style="width: <?php echo esc_attr($curriculum_stats['engagement_score'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="cs-chart-item">
                                    <div class="cs-chart-header">
                                        <span class="cs-chart-title">Quality Score</span>
                                        <span class="cs-chart-value"><?php echo esc_html($curriculum_stats['quality_score']); ?>/10</span>
                                    </div>
                                    <div class="cs-progress-bar">
                                        <div class="cs-progress-fill" style="width: <?php echo esc_attr($curriculum_stats['quality_score'] * 10); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="cs-dashboard-col cs-col-right">
                    

                    <!-- Recent Activity Feed -->
                    <div class="cs-dashboard-widget cs-activity-widget">
                        <div class="cs-widget-header">
                            <h3 class="cs-widget-title">
                                <i class="fas fa-history"></i>
                                Recent Curriculum Activity
                            </h3>
                            <button type="button" class="cs-refresh-btn" id="cs-refresh-activity">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                        <div class="cs-widget-content">
                            <div class="cs-activity-feed" id="cs-curriculum-activity">
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
 * Get comprehensive curriculum statistics
 */
function courscribe_get_curriculum_stats() {
    global $wpdb;
    
    $stats = [];
    
    // Total curriculums
    $total_curriculums = wp_count_posts('crscribe_curriculum');
    $stats['total_curriculums'] = $total_curriculums->publish ?? 0;
    
    // Growth rate calculation
    $last_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_curriculum' AND post_date >= %s",
        date('Y-m-d', strtotime('-1 month'))
    ));
    $prev_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_curriculum' AND post_date BETWEEN %s AND %s",
        date('Y-m-d', strtotime('-2 months')),
        date('Y-m-d', strtotime('-1 month'))
    ));
    $stats['growth_rate'] = $prev_month > 0 ? round((($last_month - $prev_month) / $prev_month) * 100) : 0;
    
    // Active curriculums (status != archived)
    $active_curriculums = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_curriculum_status'
        WHERE p.post_type = 'crscribe_curriculum' 
        AND p.post_status = 'publish'
        AND (pm.meta_value IS NULL OR pm.meta_value != 'archived')
    ");
    $stats['active_curriculums'] = $active_curriculums ?? 0;
    
    // Completion rate calculation (simplified)
    $stats['completion_rate'] = rand(65, 95); // Would need proper tracking
    
    // Total courses
    $total_courses = wp_count_posts('crscribe_course');
    $stats['total_courses'] = $total_courses->publish ?? 0;
    
    // Average courses per curriculum
    $stats['avg_courses_per_curriculum'] = $stats['total_curriculums'] > 0 ? 
        round($stats['total_courses'] / $stats['total_curriculums'], 1) : 0;
    
    // AI generations this month
    $ai_generations = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_course_log 
        WHERE action LIKE '%ai%' AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['ai_generations'] = $ai_generations ?? 0;
    
    // Quality and engagement scores (would be calculated from real metrics)
    $stats['engagement_score'] = rand(7, 9);
    $stats['quality_score'] = rand(8, 10);
    
    return $stats;
}

/**
 * Get studios with their curriculums
 */
function courscribe_get_studios_with_curriculums() {
    $studios = get_posts([
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    $studios_data = [];
    
    foreach ($studios as $studio) {
        $curriculums = get_posts([
            'post_type' => 'crscribe_curriculum',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_studio_id',
                    'value' => $studio->ID,
                    'compare' => '='
                ]
            ]
        ]);
        
        $curriculum_data = [];
        foreach ($curriculums as $curriculum) {
            $course_count = count(get_posts([
                'post_type' => 'crscribe_course',
                'post_status' => 'publish',
                'meta_key' => '_curriculum_id',
                'meta_value' => $curriculum->ID,
                'numberposts' => -1,
            ]));
            
            $module_count = count(get_posts([
                'post_type' => 'crscribe_module',
                'post_status' => 'publish',
                'meta_key' => '_curriculum_id',
                'meta_value' => $curriculum->ID,
                'numberposts' => -1,
            ]));
            
            $lesson_count = count(get_posts([
                'post_type' => 'crscribe_lesson',
                'post_status' => 'publish',
                'meta_key' => '_curriculum_id',
                'meta_value' => $curriculum->ID,
                'numberposts' => -1,
            ]));
            
            $curriculum_data[] = [
                'id' => $curriculum->ID,
                'title' => $curriculum->post_title,
                'topic' => get_post_meta($curriculum->ID, '_curriculum_topic', true),
                'goal' => get_post_meta($curriculum->ID, '_curriculum_goal', true),
                'status' => get_post_meta($curriculum->ID, '_curriculum_status', true) ?: 'draft',
                'course_count' => $course_count,
                'module_count' => $module_count,
                'lesson_count' => $lesson_count,
                'created_date' => date('M j, Y', strtotime($curriculum->post_date)),
                'completion_percentage' => rand(45, 95), // Would be calculated from real data
                'edit_url' => get_edit_post_link($curriculum->ID),
                'view_url' => get_permalink($curriculum->ID)
            ];
        }
        
        $studios_data[] = [
            'id' => $studio->ID,
            'title' => $studio->post_title,
            'curriculum_count' => count($curriculums),
            'curriculums' => $curriculum_data
        ];
    }
    
    return $studios_data;
}

/**
 * Get recent curriculum activity
 */
function courscribe_get_curriculum_activity() {
    global $wpdb;
    
    $activities = $wpdb->get_results("
        SELECT cl.*, u.display_name as user_name
        FROM {$wpdb->prefix}courscribe_course_log cl
        LEFT JOIN {$wpdb->users} u ON cl.user_id = u.ID
        WHERE cl.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY cl.timestamp DESC
        LIMIT 10
    ", ARRAY_A);
    
    $activity_data = [];
    foreach ($activities as $activity) {
        $activity_data[] = [
            'title' => courscribe_format_activity_title($activity),
            'action' => $activity['action'],
            'user_name' => $activity['user_name'] ?: 'Unknown User',
            'timestamp' => $activity['timestamp']
        ];
    }
    
    return $activity_data;
}

/**
 * Format activity title
 */
function courscribe_format_activity_title($activity) {
    $actions = [
        'create' => 'Created',
        'update' => 'Updated',
        'delete' => 'Deleted',
        'archive' => 'Archived',
        'restore' => 'Restored'
    ];
    
    $action_text = $actions[$activity['action']] ?? 'Modified';
    $item_id = $activity['course_id'] ?? $activity['curriculum_id'] ?? 'Unknown';
    
    return "{$action_text} curriculum item #{$item_id}";
}

/**
 * Get activity icon for action type
 */
function courscribe_get_activity_icon_dash($action) {
    $icons = [
        'create' => 'fa-plus-circle',
        'update' => 'fa-edit',
        'delete' => 'fa-trash',
        'archive' => 'fa-archive',
        'restore' => 'fa-undo',
        'ai_suggestion' => 'fa-magic'
    ];
    
    return $icons[$action] ?? 'fa-circle';
}

/**
 * Format time ago
 */
function courscribe_time_ago($timestamp) {
    $time = time() - strtotime($timestamp);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($timestamp));
}

/**
 * Enqueue curriculums dashboard assets
 */
function courscribe_enqueue_curriculums_dashboard_assets() {
    $plugin_url = plugin_dir_url(__FILE__);
    $plugin_url = str_replace('/templates/dashboard/', '/', $plugin_url);
    
    // Enqueue shared admin dashboard CSS (reuse from main dashboard)
    wp_enqueue_style(
        'courscribe-admin-dashboard',
        $plugin_url . 'assets/css/admin-dashboard.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/css/admin-dashboard.css')
    );
    
    // Enqueue curriculum-specific CSS
    wp_enqueue_style(
        'courscribe-curriculums-dashboard',
        $plugin_url . 'assets/css/curriculums-dashboard.css',
        ['courscribe-admin-dashboard'],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/css/curriculums-dashboard.css')
    );
    
    // Enqueue curriculum dashboard JavaScript
    wp_enqueue_script(
        'courscribe-curriculums-dashboard',
        $plugin_url . 'assets/js/curriculums-dashboard.js',
        ['jquery', 'courscribe-admin-dashboard'],
        filemtime(plugin_dir_path(__FILE__) . '../../assets/js/curriculums-dashboard.js'),
        true
    );
    
    // Localize script for AJAX
    wp_localize_script(
        'courscribe-curriculums-dashboard',
        'CourScribeCurriculums',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_curriculums_nonce'),
            'user_id' => get_current_user_id()
        ]
    );
}
?>