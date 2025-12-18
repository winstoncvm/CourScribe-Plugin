<?php
/**
 * CourScribe Courses Dashboard
 * Modern, comprehensive course management interface
 */

if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_courses_dashboard() {
    $current_user = wp_get_current_user();
    $site_url = home_url();
    $plugin_url = plugin_dir_url(__FILE__);
    $tier = get_option('courscribe_tier', 'basics');
    
    // Get all curriculums for the current user
    $curriculums_query = new WP_Query([
        'post_type' => 'crscribe_curriculum',
        'post_status' => ['publish', 'draft'],
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => '_studio_id',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    // Get all courses for quick stats
    $courses_query = new WP_Query([
        'post_type' => 'crscribe_course',
        'post_status' => ['publish', 'draft'],
        'numberposts' => -1,
        'fields' => 'ids'
    ]);
    
    $total_courses = $courses_query->found_posts;
    wp_reset_postdata();
?>

<div class="ccb-dashboard-wrapper">
    <!-- Header Section -->
    <div class="ccb-dashboard-header">
        <div class="ccb-header-content">
            <div class="ccb-header-logo">
                <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" 
                     alt="CourScribe" 
                     class="ccb-logo">
            </div>
            <div class="ccb-header-info">
                <h1 class="ccb-dashboard-title">
                    <i class="fas fa-graduation-cap"></i>
                    Courses Dashboard
                </h1>
                <p class="ccb-dashboard-subtitle">
                    Welcome back, <strong><?php echo esc_html($current_user->display_name); ?></strong>! 
                    Manage your educational content and track performance.
                </p>
            </div>
            <div class="ccb-header-actions">
                <button class="ccb-btn ccb-btn-secondary" id="ccbRefreshStats">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_course'); ?>" 
                   class="ccb-btn ccb-btn-primary">
                    <i class="fas fa-plus"></i>
                    New Course
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="ccb-stats-section">
        <div class="ccb-stats-grid" id="ccbStatsGrid">
            <div class="ccb-stat-card ccb-stat-primary">
                <div class="ccb-stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statTotalCourses">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Total Courses</div>
                    <div class="ccb-stat-trend" id="coursesTrend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-secondary">
                <div class="ccb-stat-icon">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statTotalModules">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Total Modules</div>
                    <div class="ccb-stat-trend" id="modulesTrend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-success">
                <div class="ccb-stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statTotalLessons">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Total Lessons</div>
                    <div class="ccb-stat-trend" id="lessonsTrend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+15% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-warning">
                <div class="ccb-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statActiveUsers">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Active Users</div>
                    <div class="ccb-stat-period">Last 7 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ccb-main-content">
        
        <!-- Quick Actions Panel -->
        <div class="ccb-quick-actions">
            <h3 class="ccb-section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h3>
            <div class="ccb-actions-grid">
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_course'); ?>" 
                   class="ccb-action-item ccb-action-primary">
                    <div class="ccb-action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Create Course</h4>
                        <p>Start building a new course</p>
                    </div>
                </a>
                
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_curriculum'); ?>" 
                   class="ccb-action-item ccb-action-secondary">
                    <div class="ccb-action-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>New Curriculum</h4>
                        <p>Design a learning pathway</p>
                    </div>
                </a>
                
                <button class="ccb-action-item ccb-action-tertiary" onclick="window.ccbDashboard.generateWithAI()">
                    <div class="ccb-action-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>AI Generate</h4>
                        <p>Create with AI assistance</p>
                    </div>
                </button>
                
                <a href="<?php echo admin_url('edit.php?post_type=crscribe_course'); ?>" 
                   class="ccb-action-item ccb-action-info">
                    <div class="ccb-action-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Browse All</h4>
                        <p>View all courses</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="ccb-chart-section">
            <div class="ccb-chart-header">
                <h3 class="ccb-section-title">
                    <i class="fas fa-chart-line"></i>
                    Activity Overview
                </h3>
                <div class="ccb-chart-controls">
                    <select class="ccb-chart-period" id="ccbChartPeriod">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            <div class="ccb-chart-container">
                <canvas id="ccbActivityChart"></canvas>
                <div class="ccb-chart-loading" id="ccbChartLoading">
                    <div class="ccb-loading-spinner"></div>
                    <p>Loading activity data...</p>
                </div>
            </div>
        </div>

        <!-- Curriculums & Courses -->
        <div class="ccb-content-section">
            <div class="ccb-content-header">
                <h3 class="ccb-section-title">
                    <i class="fas fa-layer-group"></i>
                    Curriculums & Courses
                </h3>
                <div class="ccb-content-filters">
                    <select class="ccb-filter-select" id="ccbStatusFilter">
                        <option value="">All Status</option>
                        <option value="publish">Published</option>
                        <option value="draft">Draft</option>
                        <option value="private">Private</option>
                    </select>
                    <div class="ccb-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search curriculums..." id="ccbSearchInput">
                    </div>
                </div>
            </div>

            <div class="ccb-curriculums-container" id="ccbCurriculumsContainer">
                <?php if ($curriculums_query->have_posts()): ?>
                    <?php while ($curriculums_query->have_posts()): $curriculums_query->the_post(); ?>
                        <?php
                        $curriculum_id = get_the_ID();
                        $curriculum_title = get_the_title();
                        $curriculum_status = get_post_status();
                        $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
                        $studio = $studio_id ? get_post($studio_id) : null;
                        
                        // Get courses for this curriculum
                        $curriculum_courses = new WP_Query([
                            'post_type' => 'crscribe_course',
                            'post_status' => ['publish', 'draft'],
                            'numberposts' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_curriculum_id',
                                    'value' => $curriculum_id,
                                    'compare' => '='
                                ]
                            ],
                            'orderby' => 'menu_order',
                            'order' => 'ASC'
                        ]);
                        
                        $courses_count = $curriculum_courses->found_posts;
                        ?>
                        
                        <div class="ccb-curriculum-card" data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" data-status="<?php echo esc_attr($curriculum_status); ?>">
                            <div class="ccb-curriculum-header">
                                <div class="ccb-curriculum-meta">
                                    <div class="ccb-curriculum-status ccb-status-<?php echo esc_attr($curriculum_status); ?>">
                                        <?php echo esc_html(ucfirst($curriculum_status)); ?>
                                    </div>
                                    <div class="ccb-curriculum-studio">
                                        <i class="fas fa-building"></i>
                                        <?php echo $studio ? esc_html($studio->post_title) : 'No Studio'; ?>
                                    </div>
                                </div>
                                <div class="ccb-curriculum-actions">
                                    <button class="ccb-action-btn ccb-action-edit" data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" title="Edit Curriculum">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="ccb-action-btn ccb-action-view" data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="ccb-action-btn ccb-action-more" data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>" title="More Options">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="ccb-curriculum-content">
                                <h4 class="ccb-curriculum-title">
                                    <a href="<?php echo get_permalink($curriculum_id); ?>">
                                        <?php echo esc_html($curriculum_title); ?>
                                    </a>
                                </h4>
                                <div class="ccb-curriculum-summary">
                                    <div class="ccb-summary-stat">
                                        <i class="fas fa-book"></i>
                                        <span><?php echo esc_html($courses_count); ?> Course<?php echo $courses_count !== 1 ? 's' : ''; ?></span>
                                    </div>
                                    <div class="ccb-summary-stat">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo esc_html(get_the_date('M j, Y')); ?></span>
                                    </div>
                                    <div class="ccb-summary-stat">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo esc_html(get_the_author_meta('display_name')); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ccb-curriculum-courses" style="display: none;">
                                <?php if ($curriculum_courses->have_posts()): ?>
                                    <div class="ccb-courses-list">
                                        <?php while ($curriculum_courses->have_posts()): $curriculum_courses->the_post(); ?>
                                            <?php
                                            $course_id = get_the_ID();
                                            $course_status = get_post_status();
                                            
                                            // Count modules and lessons
                                            $modules_count = get_posts([
                                                'post_type' => 'crscribe_module',
                                                'post_status' => 'any',
                                                'meta_key' => '_course_id',
                                                'meta_value' => $course_id,
                                                'fields' => 'ids'
                                            ]);
                                            
                                            $lessons_count = get_posts([
                                                'post_type' => 'crscribe_lesson',
                                                'post_status' => 'any',
                                                'meta_key' => '_course_id',
                                                'meta_value' => $course_id,
                                                'fields' => 'ids'
                                            ]);
                                            ?>
                                            
                                            <div class="ccb-course-item" data-course-id="<?php echo esc_attr($course_id); ?>">
                                                <div class="ccb-course-icon">
                                                    <i class="fas fa-play-circle"></i>
                                                </div>
                                                <div class="ccb-course-info">
                                                    <h5 class="ccb-course-title">
                                                        <a href="<?php echo get_permalink($course_id); ?>">
                                                            <?php echo esc_html(get_the_title()); ?>
                                                        </a>
                                                    </h5>
                                                    <div class="ccb-course-meta">
                                                        <span class="ccb-course-status ccb-status-<?php echo esc_attr($course_status); ?>">
                                                            <?php echo esc_html(ucfirst($course_status)); ?>
                                                        </span>
                                                        <span class="ccb-course-modules">
                                                            <?php echo count($modules_count); ?> modules
                                                        </span>
                                                        <span class="ccb-course-lessons">
                                                            <?php echo count($lessons_count); ?> lessons
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ccb-course-actions">
                                                    <a href="<?php echo get_edit_post_link($course_id); ?>" 
                                                       class="ccb-course-action" 
                                                       title="Edit Course">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo get_permalink($course_id); ?>" 
                                                       class="ccb-course-action" 
                                                       title="View Course">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="ccb-empty-courses">
                                        <i class="fas fa-plus-circle"></i>
                                        <p>No courses in this curriculum yet.</p>
                                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbDashboard.addCourse(<?php echo esc_attr($curriculum_id); ?>)">
                                            Add First Course
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php wp_reset_postdata(); ?>
                            </div>
                            
                            <div class="ccb-curriculum-footer">
                                <button class="ccb-toggle-courses" data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
                                    <span class="ccb-toggle-text">View Courses</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <a href="<?php echo admin_url('post.php?post=' . $curriculum_id . '&action=edit'); ?>" 
                                   class="ccb-btn ccb-btn-sm ccb-btn-secondary">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="ccb-empty-state">
                        <div class="ccb-empty-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>No Curriculums Found</h3>
                        <p>Get started by creating your first curriculum and courses.</p>
                        <div class="ccb-empty-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=crscribe_curriculum'); ?>" 
                               class="ccb-btn ccb-btn-primary">
                                <i class="fas fa-plus"></i>
                                Create Curriculum
                            </a>
                            <a href="<?php echo admin_url('post-new.php?post_type=crscribe_course'); ?>" 
                               class="ccb-btn ccb-btn-secondary">
                                <i class="fas fa-book"></i>
                                Create Course
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="ccb-activity-section">
            <h3 class="ccb-section-title">
                <i class="fas fa-clock"></i>
                Recent Activity
            </h3>
            <div class="ccb-activity-feed" id="ccbActivityFeed">
                <div class="ccb-activity-loading">
                    <div class="ccb-loading-spinner"></div>
                    <p>Loading recent activity...</p>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* CourScribe Dashboard Styles */
:root {
    --ccb-primary: #E4B26F;
    --ccb-primary-light: #F0C788;
    --ccb-primary-dark: #D4A05C;
    --ccb-secondary: #665442;
    --ccb-success: #28a745;
    --ccb-warning: #ffc107;
    --ccb-danger: #dc3545;
    --ccb-info: #17a2b8;
    --ccb-dark: #231F20;
    --ccb-light: #f8f9fa;
    --ccb-bg-primary: #231F20;
    --ccb-bg-secondary: #2a2a2b;
    --ccb-bg-elevated: #353535;
    --ccb-bg-card: #2f2f2f;
    --ccb-text-primary: #FFFFFF;
    --ccb-text-secondary: #E0E0E0;
    --ccb-text-muted: #B0B0B0;
    --ccb-border-color: #404040;
    --ccb-shadow: 0 2px 4px rgba(0,0,0,0.1);
    --ccb-shadow-lg: 0 4px 12px rgba(0,0,0,0.15);
    --ccb-border-radius: 8px;
    --ccb-border-radius-sm: 4px;
    --ccb-border-radius-lg: 12px;
    --ccb-spacing-xs: 4px;
    --ccb-spacing-sm: 8px;
    --ccb-spacing-md: 16px;
    --ccb-spacing-lg: 24px;
    --ccb-spacing-xl: 32px;
    --ccb-spacing-2xl: 48px;
    --ccb-transition: all 0.3s ease;
}

.ccb-dashboard-wrapper {
    background: var(--ccb-bg-primary);
    min-height: 100vh;
    color: var(--ccb-text-primary);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.ccb-dashboard-header {
    background: var(--ccb-bg-secondary);
    border-bottom: 1px solid var(--ccb-border-color);
    padding: var(--ccb-spacing-lg) var(--ccb-spacing-xl);
    margin-bottom: var(--ccb-spacing-xl);
}

.ccb-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1400px;
    margin: 0 auto;
    gap: var(--ccb-spacing-lg);
}

.ccb-header-logo .ccb-logo {
    height: 60px;
    width: auto;
}

.ccb-header-info {
    flex: 1;
}

.ccb-dashboard-title {
    margin: 0 0 var(--ccb-spacing-sm) 0;
    font-size: 28px;
    font-weight: 600;
    color: var(--ccb-text-primary);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
}

.ccb-dashboard-title i {
    color: var(--ccb-primary);
    font-size: 24px;
}

.ccb-dashboard-subtitle {
    margin: 0;
    color: var(--ccb-text-secondary);
    font-size: 16px;
}

.ccb-header-actions {
    display: flex;
    gap: var(--ccb-spacing-md);
}

/* Buttons */
.ccb-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    border: none;
    border-radius: var(--ccb-border-radius);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--ccb-transition);
    white-space: nowrap;
}

.ccb-btn-sm {
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    font-size: 12px;
}

.ccb-btn-primary {
    background: linear-gradient(135deg, var(--ccb-primary) 0%, var(--ccb-primary-dark) 100%);
    color: white;
}

.ccb-btn-primary:hover {
    background: linear-gradient(135deg, var(--ccb-primary-dark) 0%, var(--ccb-primary) 100%);
    transform: translateY(-1px);
    box-shadow: var(--ccb-shadow-lg);
}

.ccb-btn-secondary {
    background: var(--ccb-bg-elevated);
    color: var(--ccb-text-primary);
    border: 1px solid var(--ccb-border-color);
}

.ccb-btn-secondary:hover {
    background: var(--ccb-border-color);
    color: var(--ccb-text-primary);
}

/* Stats Section */
.ccb-stats-section {
    padding: 0 var(--ccb-spacing-xl);
    margin-bottom: var(--ccb-spacing-2xl);
}

.ccb-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--ccb-spacing-lg);
    max-width: 1400px;
    margin: 0 auto;
}

.ccb-stat-card {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    transition: var(--ccb-transition);
    position: relative;
    overflow: hidden;
}

.ccb-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--ccb-primary);
}

.ccb-stat-card.ccb-stat-secondary::before {
    background: var(--ccb-secondary);
}

.ccb-stat-card.ccb-stat-success::before {
    background: var(--ccb-success);
}

.ccb-stat-card.ccb-stat-warning::before {
    background: var(--ccb-warning);
}

.ccb-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--ccb-shadow-lg);
    border-color: var(--ccb-primary);
}

.ccb-stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--ccb-primary) 0%, var(--ccb-primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.ccb-stat-secondary .ccb-stat-icon {
    background: linear-gradient(135deg, var(--ccb-secondary) 0%, #554433 100%);
}

.ccb-stat-success .ccb-stat-icon {
    background: linear-gradient(135deg, var(--ccb-success) 0%, #218838 100%);
}

.ccb-stat-warning .ccb-stat-icon {
    background: linear-gradient(135deg, var(--ccb-warning) 0%, #e0a800 100%);
}

.ccb-stat-content {
    flex: 1;
}

.ccb-stat-number {
    font-size: 32px;
    font-weight: 700;
    color: var(--ccb-text-primary);
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-stat-label {
    font-size: 14px;
    color: var(--ccb-text-secondary);
    font-weight: 500;
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-stat-trend {
    font-size: 12px;
    color: var(--ccb-success);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
}

.ccb-stat-period {
    font-size: 12px;
    color: var(--ccb-text-muted);
}

/* Loading Animation */
.ccb-loading-dots::after {
    content: '...';
    animation: ccbDots 2s infinite;
}

@keyframes ccbDots {
    0%, 20% { content: '.'; }
    40% { content: '..'; }
    60%, 100% { content: '...'; }
}

/* Main Content */
.ccb-main-content {
    padding: 0 var(--ccb-spacing-xl);
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    gap: var(--ccb-spacing-2xl);
}

.ccb-section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--ccb-text-primary);
    margin: 0 0 var(--ccb-spacing-lg) 0;
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
}

.ccb-section-title i {
    color: var(--ccb-primary);
}

/* Quick Actions */
.ccb-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--ccb-spacing-md);
}

.ccb-action-item {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    text-decoration: none;
    color: var(--ccb-text-primary);
    transition: var(--ccb-transition);
    cursor: pointer;
}

.ccb-action-item:hover {
    border-color: var(--ccb-primary);
    transform: translateY(-2px);
    box-shadow: var(--ccb-shadow-lg);
    color: var(--ccb-text-primary);
}

.ccb-action-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--ccb-primary) 0%, var(--ccb-primary-dark) 100%);
    border-radius: var(--ccb-border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.ccb-action-secondary .ccb-action-icon {
    background: linear-gradient(135deg, var(--ccb-secondary) 0%, #554433 100%);
}

.ccb-action-tertiary .ccb-action-icon {
    background: linear-gradient(135deg, var(--ccb-info) 0%, #138496 100%);
}

.ccb-action-info .ccb-action-icon {
    background: linear-gradient(135deg, var(--ccb-success) 0%, #218838 100%);
}

.ccb-action-content h4 {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    font-size: 16px;
    font-weight: 600;
}

.ccb-action-content p {
    margin: 0;
    font-size: 14px;
    color: var(--ccb-text-secondary);
}

/* Chart Section */
.ccb-chart-section {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-lg);
}

.ccb-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--ccb-spacing-lg);
}

.ccb-chart-controls select {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    color: var(--ccb-text-primary);
    font-size: 14px;
}

.ccb-chart-container {
    position: relative;
    height: 300px;
}

.ccb-chart-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--ccb-bg-card);
    color: var(--ccb-text-secondary);
}

.ccb-loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--ccb-border-color);
    border-top: 3px solid var(--ccb-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: var(--ccb-spacing-md);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Content Section */
.ccb-content-section {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-lg);
}

.ccb-content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--ccb-spacing-lg);
    flex-wrap: wrap;
    gap: var(--ccb-spacing-md);
}

.ccb-content-filters {
    display: flex;
    gap: var(--ccb-spacing-md);
    align-items: center;
}

.ccb-filter-select {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    color: var(--ccb-text-primary);
    font-size: 14px;
}

.ccb-search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.ccb-search-box i {
    position: absolute;
    left: var(--ccb-spacing-md);
    color: var(--ccb-text-muted);
    font-size: 14px;
}

.ccb-search-box input {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md) var(--ccb-spacing-sm) 40px;
    color: var(--ccb-text-primary);
    font-size: 14px;
    width: 250px;
}

.ccb-search-box input:focus {
    outline: none;
    border-color: var(--ccb-primary);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

/* Curriculum Cards */
.ccb-curriculums-container {
    display: grid;
    gap: var(--ccb-spacing-lg);
}

.ccb-curriculum-card {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    overflow: hidden;
    transition: var(--ccb-transition);
}

.ccb-curriculum-card:hover {
    border-color: var(--ccb-primary);
    transform: translateY(-1px);
    box-shadow: var(--ccb-shadow-lg);
}

.ccb-curriculum-header {
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
    border-bottom: 1px solid var(--ccb-border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ccb-curriculum-meta {
    display: flex;
    gap: var(--ccb-spacing-md);
    align-items: center;
}

.ccb-curriculum-status {
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.ccb-status-publish {
    background: rgba(40, 167, 69, 0.2);
    color: var(--ccb-success);
}

.ccb-status-draft {
    background: rgba(255, 193, 7, 0.2);
    color: var(--ccb-warning);
}

.ccb-status-private {
    background: rgba(220, 53, 69, 0.2);
    color: var(--ccb-danger);
}

.ccb-curriculum-studio {
    font-size: 14px;
    color: var(--ccb-text-secondary);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
}

.ccb-curriculum-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

.ccb-action-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: var(--ccb-text-muted);
    border-radius: var(--ccb-border-radius-sm);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--ccb-transition);
}

.ccb-action-btn:hover {
    background: var(--ccb-bg-elevated);
    color: var(--ccb-text-primary);
}

.ccb-curriculum-content {
    padding: var(--ccb-spacing-md);
}

.ccb-curriculum-title {
    margin: 0 0 var(--ccb-spacing-sm) 0;
    font-size: 18px;
    font-weight: 600;
}

.ccb-curriculum-title a {
    color: var(--ccb-text-primary);
    text-decoration: none;
}

.ccb-curriculum-title a:hover {
    color: var(--ccb-primary);
}

.ccb-curriculum-summary {
    display: flex;
    gap: var(--ccb-spacing-md);
    flex-wrap: wrap;
}

.ccb-summary-stat {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
    font-size: 14px;
    color: var(--ccb-text-secondary);
}

.ccb-summary-stat i {
    color: var(--ccb-primary);
    width: 16px;
}

/* Courses List */
.ccb-curriculum-courses {
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
    border-top: 1px solid var(--ccb-border-color);
}

.ccb-courses-list {
    display: grid;
    gap: var(--ccb-spacing-sm);
}

.ccb-course-item {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    transition: var(--ccb-transition);
}

.ccb-course-item:hover {
    border-color: var(--ccb-primary);
    background: var(--ccb-bg-card);
}

.ccb-course-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--ccb-primary) 0%, var(--ccb-primary-dark) 100%);
    border-radius: var(--ccb-border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.ccb-course-info {
    flex: 1;
}

.ccb-course-title {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    font-size: 16px;
    font-weight: 600;
}

.ccb-course-title a {
    color: var(--ccb-text-primary);
    text-decoration: none;
}

.ccb-course-title a:hover {
    color: var(--ccb-primary);
}

.ccb-course-meta {
    display: flex;
    gap: var(--ccb-spacing-md);
    font-size: 12px;
}

.ccb-course-status {
    padding: 2px var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 500;
    text-transform: uppercase;
}

.ccb-course-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

.ccb-course-action {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: var(--ccb-text-muted);
    border-radius: var(--ccb-border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--ccb-transition);
    text-decoration: none;
}

.ccb-course-action:hover {
    background: var(--ccb-bg-elevated);
    color: var(--ccb-primary);
}

.ccb-curriculum-footer {
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
    border-top: 1px solid var(--ccb-border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ccb-toggle-courses {
    background: none;
    border: none;
    color: var(--ccb-primary);
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
    transition: var(--ccb-transition);
}

.ccb-toggle-courses:hover {
    color: var(--ccb-primary-light);
}

.ccb-toggle-courses i {
    transition: var(--ccb-transition);
}

.ccb-toggle-courses.expanded i {
    transform: rotate(180deg);
}

/* Empty States */
.ccb-empty-state,
.ccb-empty-courses {
    text-align: center;
    padding: var(--ccb-spacing-2xl);
    color: var(--ccb-text-muted);
}

.ccb-empty-icon {
    font-size: 64px;
    color: var(--ccb-text-muted);
    margin-bottom: var(--ccb-spacing-lg);
}

.ccb-empty-state h3 {
    margin: 0 0 var(--ccb-spacing-md) 0;
    color: var(--ccb-text-primary);
    font-size: 24px;
}

.ccb-empty-state p {
    margin: 0 0 var(--ccb-spacing-lg) 0;
    font-size: 16px;
}

.ccb-empty-actions {
    display: flex;
    gap: var(--ccb-spacing-md);
    justify-content: center;
    flex-wrap: wrap;
}

.ccb-empty-courses {
    padding: var(--ccb-spacing-lg);
}

.ccb-empty-courses i {
    font-size: 32px;
    color: var(--ccb-text-muted);
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-empty-courses p {
    margin: 0 0 var(--ccb-spacing-md) 0;
    font-size: 14px;
}

/* Activity Section */
.ccb-activity-section {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-lg);
}

.ccb-activity-feed {
    min-height: 200px;
}

.ccb-activity-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--ccb-spacing-2xl);
    color: var(--ccb-text-secondary);
}

/* Responsive Design */
@media (max-width: 768px) {
    .ccb-dashboard-header {
        padding: var(--ccb-spacing-md);
    }
    
    .ccb-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--ccb-spacing-md);
    }
    
    .ccb-header-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .ccb-stats-section,
    .ccb-main-content {
        padding: 0 var(--ccb-spacing-md);
    }
    
    .ccb-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .ccb-content-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .ccb-content-filters {
        width: 100%;
        flex-direction: column;
    }
    
    .ccb-search-box input {
        width: 100%;
    }
    
    .ccb-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .ccb-curriculum-summary {
        flex-direction: column;
        gap: var(--ccb-spacing-sm);
    }
    
    .ccb-curriculum-footer {
        flex-direction: column;
        gap: var(--ccb-spacing-md);
        align-items: stretch;
    }
    
    .ccb-empty-actions {
        flex-direction: column;
    }
}
</style>

<script>
// CourScribe Dashboard JavaScript
window.ccbDashboard = {
    initialized: false,
    
    init() {
        if (this.initialized) return;
        this.initialized = true;
        
        console.log('CourScribe Dashboard initialized');
        
        this.bindEvents();
        this.loadStats();
        this.loadActivityChart();
        this.loadRecentActivity();
    },
    
    bindEvents() {
        // Refresh stats
        jQuery('#ccbRefreshStats').on('click', (e) => {
            e.preventDefault();
            this.loadStats();
        });
        
        // Toggle curriculum courses
        jQuery(document).on('click', '.ccb-toggle-courses', (e) => {
            e.preventDefault();
            const $button = jQuery(e.currentTarget);
            const curriculumId = $button.data('curriculum-id');
            const $card = $button.closest('.ccb-curriculum-card');
            const $courses = $card.find('.ccb-curriculum-courses');
            
            if ($courses.is(':visible')) {
                $courses.slideUp(300);
                $button.removeClass('expanded').find('.ccb-toggle-text').text('View Courses');
            } else {
                $courses.slideDown(300);
                $button.addClass('expanded').find('.ccb-toggle-text').text('Hide Courses');
            }
        });
        
        // Search functionality
        jQuery('#ccbSearchInput').on('input', (e) => {
            this.filterCurriculums(e.target.value);
        });
        
        // Status filter
        jQuery('#ccbStatusFilter').on('change', (e) => {
            this.filterByStatus(e.target.value);
        });
        
        // Chart period change
        jQuery('#ccbChartPeriod').on('change', (e) => {
            this.loadActivityChart(e.target.value);
        });
    },
    
    async loadStats() {
        const $statsCards = jQuery('.ccb-stat-number');
        $statsCards.html('<span class="ccb-loading-dots">...</span>');
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_get_courses_stats',
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                jQuery('#statTotalCourses').text(response.data.total_courses || 0);
                jQuery('#statTotalModules').text(response.data.total_modules || 0);
                jQuery('#statTotalLessons').text(response.data.total_lessons || 0);
                jQuery('#statActiveUsers').text(response.data.active_users || 0);
            } else {
                $statsCards.text('Error');
                console.error('Stats loading failed:', response.data?.message);
            }
        } catch (error) {
            $statsCards.text('Error');
            console.error('Stats AJAX error:', error);
        }
    },
    
    async loadActivityChart(period = 30) {
        const $loading = jQuery('#ccbChartLoading');
        const canvas = document.getElementById('ccbActivityChart');
        
        if (!canvas) return;
        
        $loading.show();
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_get_activity_chart',
                    period: period,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success && window.Chart) {
                const ctx = canvas.getContext('2d');
                
                // Destroy existing chart if it exists
                if (window.ccbChart) {
                    window.ccbChart.destroy();
                }
                
                window.ccbChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels || [],
                        datasets: [{
                            label: 'Course Activities',
                            data: response.data.values || [],
                            borderColor: '#E4B26F',
                            backgroundColor: 'rgba(228, 178, 111, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#E4B26F',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                border: {
                                    color: '#404040'
                                },
                                grid: {
                                    color: '#404040'
                                },
                                ticks: {
                                    color: '#B0B0B0'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                border: {
                                    color: '#404040'
                                },
                                grid: {
                                    color: '#404040'
                                },
                                ticks: {
                                    color: '#B0B0B0'
                                }
                            }
                        }
                    }
                });
                
                $loading.hide();
            } else {
                console.error('Chart data loading failed');
                $loading.html('<p>Error loading chart data</p>');
            }
        } catch (error) {
            console.error('Chart AJAX error:', error);
            $loading.html('<p>Error loading chart data</p>');
        }
    },
    
    async loadRecentActivity() {
        const $feed = jQuery('#ccbActivityFeed');
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_get_recent_activity',
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                if (response.data.length > 0) {
                    let html = '<div class="ccb-activity-list">';
                    response.data.forEach(activity => {
                        html += `
                            <div class="ccb-activity-item">
                                <div class="ccb-activity-icon">
                                    <i class="fas fa-${this.getActivityIcon(activity.action)}"></i>
                                </div>
                                <div class="ccb-activity-content">
                                    <div class="ccb-activity-text">${activity.description}</div>
                                    <div class="ccb-activity-time">${activity.time_ago}</div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $feed.html(html);
                } else {
                    $feed.html('<div class="ccb-empty-activity"><i class="fas fa-clock"></i><p>No recent activity</p></div>');
                }
            } else {
                $feed.html('<div class="ccb-error-activity"><i class="fas fa-exclamation-triangle"></i><p>Error loading activity</p></div>');
            }
        } catch (error) {
            console.error('Activity AJAX error:', error);
            $feed.html('<div class="ccb-error-activity"><i class="fas fa-exclamation-triangle"></i><p>Error loading activity</p></div>');
        }
    },
    
    getActivityIcon(action) {
        const icons = {
            'create': 'plus',
            'update': 'edit',
            'delete': 'trash',
            'publish': 'eye',
            'draft': 'file-alt'
        };
        return icons[action] || 'circle';
    },
    
    filterCurriculums(searchTerm) {
        const $cards = jQuery('.ccb-curriculum-card');
        const term = searchTerm.toLowerCase();
        
        $cards.each(function() {
            const $card = jQuery(this);
            const title = $card.find('.ccb-curriculum-title').text().toLowerCase();
            const studio = $card.find('.ccb-curriculum-studio').text().toLowerCase();
            
            if (title.includes(term) || studio.includes(term)) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    },
    
    filterByStatus(status) {
        const $cards = jQuery('.ccb-curriculum-card');
        
        if (!status) {
            $cards.show();
            return;
        }
        
        $cards.each(function() {
            const $card = jQuery(this);
            const cardStatus = $card.data('status');
            
            if (cardStatus === status) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    },
    
    generateWithAI() {
        // TODO: Implement AI generation modal
        alert('AI Generation feature coming soon!');
    },
    
    addCourse(curriculumId) {
        const url = `<?php echo admin_url('post-new.php?post_type=crscribe_course'); ?>&curriculum_id=${curriculumId}`;
        window.location.href = url;
    }
};

// Initialize when DOM is ready
jQuery(document).ready(() => {
    window.ccbDashboard.init();
});
</script>

<?php
}
?>