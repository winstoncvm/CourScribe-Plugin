<?php
/**
 * CourScribe Modules Dashboard
 * Modern, comprehensive module management interface
 */

if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_modules_dashboard() {
    $current_user = wp_get_current_user();
    $site_url = home_url();
    $tier = get_option('courscribe_tier', 'basics');
    
    // Get all courses for module organization
    $courses_query = new WP_Query([
        'post_type' => 'crscribe_course',
        'post_status' => ['publish', 'draft'],
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => '_curriculum_id',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    // Get all modules for quick stats
    $modules_query = new WP_Query([
        'post_type' => 'crscribe_module',
        'post_status' => ['publish', 'draft'],
        'numberposts' => -1,
        'fields' => 'ids'
    ]);
    
    $total_modules = $modules_query->found_posts;
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
                    <i class="fas fa-puzzle-piece"></i>
                    Modules Dashboard
                </h1>
                <p class="ccb-dashboard-subtitle">
                    Welcome back, <strong><?php echo esc_html($current_user->display_name); ?></strong>! 
                    Organize your learning modules and manage content structure.
                </p>
            </div>
            <div class="ccb-header-actions">
                <button class="ccb-btn ccb-btn-secondary" id="ccbRefreshStats">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_module'); ?>" 
                   class="ccb-btn ccb-btn-primary">
                    <i class="fas fa-plus"></i>
                    New Module
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="ccb-stats-section">
        <div class="ccb-stats-grid" id="ccbStatsGrid">
            <div class="ccb-stat-card ccb-stat-primary">
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
                        <span>+15% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-secondary">
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
                        <span>+22% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-success">
                <div class="ccb-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statCompletedModules">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Completed Modules</div>
                    <div class="ccb-stat-trend" id="completedTrend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-warning">
                <div class="ccb-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statAvgDuration">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Avg Duration</div>
                    <div class="ccb-stat-period">Minutes per module</div>
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
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_module'); ?>" 
                   class="ccb-action-item ccb-action-primary">
                    <div class="ccb-action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Create Module</h4>
                        <p>Build a new learning module</p>
                    </div>
                </a>
                
                <button class="ccb-action-item ccb-action-secondary" onclick="window.ccbModulesDash.bulkOperations()">
                    <div class="ccb-action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Bulk Operations</h4>
                        <p>Manage multiple modules</p>
                    </div>
                </button>
                
                <button class="ccb-action-item ccb-action-tertiary" onclick="window.ccbModulesDash.generateWithAI()">
                    <div class="ccb-action-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>AI Generate</h4>
                        <p>Create modules with AI</p>
                    </div>
                </button>
                
                <a href="<?php echo admin_url('edit.php?post_type=crscribe_module'); ?>" 
                   class="ccb-action-item ccb-action-info">
                    <div class="ccb-action-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Browse All</h4>
                        <p>View all modules</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Module Progress Chart -->
        <div class="ccb-chart-section">
            <div class="ccb-chart-header">
                <h3 class="ccb-section-title">
                    <i class="fas fa-chart-line"></i>
                    Module Progress Overview
                </h3>
                <div class="ccb-chart-controls">
                    <select class="ccb-chart-type" id="ccbChartType">
                        <option value="completion">Completion Rate</option>
                        <option value="creation" selected>Creation Activity</option>
                        <option value="engagement">Engagement</option>
                    </select>
                    <select class="ccb-chart-period" id="ccbChartPeriod">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            <div class="ccb-chart-container">
                <canvas id="ccbModulesChart"></canvas>
                <div class="ccb-chart-loading" id="ccbChartLoading">
                    <div class="ccb-loading-spinner"></div>
                    <p>Loading module data...</p>
                </div>
            </div>
        </div>

        <!-- Courses & Modules -->
        <div class="ccb-content-section">
            <div class="ccb-content-header">
                <h3 class="ccb-section-title">
                    <i class="fas fa-book-open"></i>
                    Courses & Modules
                </h3>
                <div class="ccb-content-filters">
                    <select class="ccb-filter-select" id="ccbStatusFilter">
                        <option value="">All Status</option>
                        <option value="publish">Published</option>
                        <option value="draft">Draft</option>
                        <option value="private">Private</option>
                    </select>
                    <select class="ccb-filter-select" id="ccbCourseFilter">
                        <option value="">All Courses</option>
                        <?php
                        if ($courses_query->have_posts()) {
                            while ($courses_query->have_posts()) {
                                $courses_query->the_post();
                                echo '<option value="' . get_the_ID() . '">' . esc_html(get_the_title()) . '</option>';
                            }
                            wp_reset_postdata();
                        }
                        ?>
                    </select>
                    <div class="ccb-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search modules..." id="ccbSearchInput">
                    </div>
                </div>
            </div>

            <div class="ccb-courses-container" id="ccbCoursesContainer">
                <?php if ($courses_query->have_posts()): ?>
                    <?php $courses_query->rewind_posts(); ?>
                    <?php while ($courses_query->have_posts()): $courses_query->the_post(); ?>
                        <?php
                        $course_id = get_the_ID();
                        $course_title = get_the_title();
                        $course_status = get_post_status();
                        $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
                        $curriculum = $curriculum_id ? get_post($curriculum_id) : null;
                        
                        // Get modules for this course
                        $course_modules = new WP_Query([
                            'post_type' => 'crscribe_module',
                            'post_status' => ['publish', 'draft'],
                            'numberposts' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_course_id',
                                    'value' => $course_id,
                                    'compare' => '='
                                ]
                            ],
                            'orderby' => 'menu_order',
                            'order' => 'ASC'
                        ]);
                        
                        $modules_count = $course_modules->found_posts;
                        ?>
                        
                        <div class="ccb-course-card" data-course-id="<?php echo esc_attr($course_id); ?>" data-status="<?php echo esc_attr($course_status); ?>">
                            <div class="ccb-course-header">
                                <div class="ccb-course-meta">
                                    <div class="ccb-course-status ccb-status-<?php echo esc_attr($course_status); ?>">
                                        <?php echo esc_html(ucfirst($course_status)); ?>
                                    </div>
                                    <div class="ccb-course-curriculum">
                                        <i class="fas fa-layer-group"></i>
                                        <?php echo $curriculum ? esc_html($curriculum->post_title) : 'No Curriculum'; ?>
                                    </div>
                                    <div class="ccb-modules-count">
                                        <i class="fas fa-puzzle-piece"></i>
                                        <?php echo esc_html($modules_count); ?> Module<?php echo $modules_count !== 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                                <div class="ccb-course-actions">
                                    <button class="ccb-action-btn ccb-action-add" 
                                            data-course-id="<?php echo esc_attr($course_id); ?>" 
                                            title="Add Module">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="ccb-action-btn ccb-action-edit" 
                                            data-course-id="<?php echo esc_attr($course_id); ?>" 
                                            title="Edit Course">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="ccb-action-btn ccb-action-more" 
                                            data-course-id="<?php echo esc_attr($course_id); ?>" 
                                            title="More Options">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="ccb-course-content">
                                <h4 class="ccb-course-title">
                                    <a href="<?php echo get_permalink($course_id); ?>">
                                        <?php echo esc_html($course_title); ?>
                                    </a>
                                </h4>
                                <div class="ccb-course-summary">
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
                            
                            <div class="ccb-course-modules" style="display: none;">
                                <?php if ($course_modules->have_posts()): ?>
                                    <div class="ccb-modules-list" data-course-id="<?php echo esc_attr($course_id); ?>">
                                        <?php while ($course_modules->have_posts()): $course_modules->the_post(); ?>
                                            <?php
                                            $module_id = get_the_ID();
                                            $module_status = get_post_status();
                                            $module_content = get_the_content();
                                            
                                            // Count lessons in this module
                                            $lessons_count = get_posts([
                                                'post_type' => 'crscribe_lesson',
                                                'post_status' => 'any',
                                                'meta_key' => '_module_id',
                                                'meta_value' => $module_id,
                                                'fields' => 'ids'
                                            ]);
                                            
                                            // Get module objectives
                                            $objectives = get_post_meta($module_id, '_module_objectives', true);
                                            $objectives_count = is_array($objectives) ? count($objectives) : 0;
                                            ?>
                                            
                                            <div class="ccb-module-item" data-module-id="<?php echo esc_attr($module_id); ?>" data-status="<?php echo esc_attr($module_status); ?>">
                                                <div class="ccb-module-header">
                                                    <div class="ccb-module-drag">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </div>
                                                    <div class="ccb-module-info">
                                                        <h5 class="ccb-module-title">
                                                            <a href="<?php echo get_edit_post_link($module_id); ?>">
                                                                <?php echo esc_html(get_the_title()); ?>
                                                            </a>
                                                        </h5>
                                                        <div class="ccb-module-meta">
                                                            <span class="ccb-module-status ccb-status-<?php echo esc_attr($module_status); ?>">
                                                                <?php echo esc_html(ucfirst($module_status)); ?>
                                                            </span>
                                                            <span class="ccb-module-lessons">
                                                                <?php echo count($lessons_count); ?> lessons
                                                            </span>
                                                            <span class="ccb-module-objectives">
                                                                <?php echo $objectives_count; ?> objectives
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ccb-module-progress">
                                                        <div class="ccb-progress-circle" data-progress="<?php echo rand(20, 95); ?>">
                                                            <span><?php echo rand(20, 95); ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="ccb-module-actions">
                                                        <button class="ccb-module-action" 
                                                                data-action="edit" 
                                                                data-module-id="<?php echo esc_attr($module_id); ?>" 
                                                                title="Edit Module">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="ccb-module-action" 
                                                                data-action="lessons" 
                                                                data-module-id="<?php echo esc_attr($module_id); ?>" 
                                                                title="Manage Lessons">
                                                            <i class="fas fa-play-circle"></i>
                                                        </button>
                                                        <button class="ccb-module-action" 
                                                                data-action="duplicate" 
                                                                data-module-id="<?php echo esc_attr($module_id); ?>" 
                                                                title="Duplicate Module">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                        <button class="ccb-module-action ccb-module-delete" 
                                                                data-action="delete" 
                                                                data-module-id="<?php echo esc_attr($module_id); ?>" 
                                                                title="Delete Module">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($module_content)): ?>
                                                <div class="ccb-module-preview">
                                                    <p><?php echo esc_html(wp_trim_words($module_content, 20)); ?></p>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="ccb-module-footer">
                                                    <div class="ccb-module-stats">
                                                        <span>Updated <?php echo esc_html(get_the_modified_date('M j')); ?></span>
                                                    </div>
                                                    <div class="ccb-module-quick-actions">
                                                        <button class="ccb-quick-btn" onclick="window.ccbModulesDash.addLesson(<?php echo esc_attr($module_id); ?>)">
                                                            <i class="fas fa-plus"></i>
                                                            Add Lesson
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="ccb-empty-modules">
                                        <i class="fas fa-plus-circle"></i>
                                        <p>No modules in this course yet.</p>
                                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbModulesDash.addModule(<?php echo esc_attr($course_id); ?>)">
                                            Add First Module
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php wp_reset_postdata(); ?>
                            </div>
                            
                            <div class="ccb-course-footer">
                                <button class="ccb-toggle-modules" data-course-id="<?php echo esc_attr($course_id); ?>">
                                    <span class="ccb-toggle-text">View Modules</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="ccb-course-footer-actions">
                                    <button class="ccb-btn ccb-btn-sm ccb-btn-secondary" onclick="window.ccbModulesDash.addModule(<?php echo esc_attr($course_id); ?>)">
                                        <i class="fas fa-plus"></i>
                                        Add Module
                                    </button>
                                    <a href="<?php echo get_edit_post_link($course_id); ?>" 
                                       class="ccb-btn ccb-btn-sm ccb-btn-outline">
                                        <i class="fas fa-edit"></i>
                                        Edit Course
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="ccb-empty-state">
                        <div class="ccb-empty-icon">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <h3>No Courses Found</h3>
                        <p>Create courses first to organize your modules.</p>
                        <div class="ccb-empty-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=crscribe_course'); ?>" 
                               class="ccb-btn ccb-btn-primary">
                                <i class="fas fa-plus"></i>
                                Create Course
                            </a>
                            <a href="<?php echo admin_url('post-new.php?post_type=crscribe_module'); ?>" 
                               class="ccb-btn ccb-btn-secondary">
                                <i class="fas fa-puzzle-piece"></i>
                                Create Module
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
        
        <!-- Module Templates -->
        <div class="ccb-templates-section">
            <h3 class="ccb-section-title">
                <i class="fas fa-th-large"></i>
                Module Templates
            </h3>
            <div class="ccb-templates-grid">
                <div class="ccb-template-card" data-template="introduction">
                    <div class="ccb-template-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="ccb-template-content">
                        <h4>Introduction Module</h4>
                        <p>Perfect for course introductions and overviews</p>
                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbModulesDash.useTemplate('introduction')">
                            Use Template
                        </button>
                    </div>
                </div>
                
                <div class="ccb-template-card" data-template="practical">
                    <div class="ccb-template-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="ccb-template-content">
                        <h4>Practical Module</h4>
                        <p>Hands-on activities and exercises</p>
                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbModulesDash.useTemplate('practical')">
                            Use Template
                        </button>
                    </div>
                </div>
                
                <div class="ccb-template-card" data-template="assessment">
                    <div class="ccb-template-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="ccb-template-content">
                        <h4>Assessment Module</h4>
                        <p>Quizzes, tests, and evaluations</p>
                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbModulesDash.useTemplate('assessment')">
                            Use Template
                        </button>
                    </div>
                </div>
                
                <div class="ccb-template-card" data-template="project">
                    <div class="ccb-template-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="ccb-template-content">
                        <h4>Project Module</h4>
                        <p>Capstone projects and portfolios</p>
                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbModulesDash.useTemplate('project')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Inherit all base styles from courses dashboard */
<?php echo file_get_contents(dirname(__FILE__) . '/courscribe-courses-dashboard.php'); ?>

/* Additional Module-specific styles */
.ccb-modules-list {
    display: grid;
    gap: var(--ccb-spacing-md);
    padding: var(--ccb-spacing-md) 0;
}

.ccb-module-item {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    transition: var(--ccb-transition);
    cursor: grab;
}

.ccb-module-item:hover {
    border-color: var(--ccb-primary);
    transform: translateY(-1px);
    box-shadow: var(--ccb-shadow);
}

.ccb-module-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.ccb-module-header {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    margin-bottom: var(--ccb-spacing-sm);
}

.ccb-module-drag {
    color: var(--ccb-text-muted);
    cursor: grab;
    padding: var(--ccb-spacing-xs);
}

.ccb-module-drag:hover {
    color: var(--ccb-primary);
}

.ccb-module-info {
    flex: 1;
}

.ccb-module-title {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    font-size: 16px;
    font-weight: 600;
}

.ccb-module-title a {
    color: var(--ccb-text-primary);
    text-decoration: none;
}

.ccb-module-title a:hover {
    color: var(--ccb-primary);
}

.ccb-module-meta {
    display: flex;
    gap: var(--ccb-spacing-md);
    font-size: 12px;
    flex-wrap: wrap;
}

.ccb-module-status {
    padding: 2px var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 500;
    text-transform: uppercase;
}

.ccb-module-progress {
    display: flex;
    align-items: center;
}

.ccb-progress-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: conic-gradient(var(--ccb-primary) calc(var(--progress, 0) * 1%), var(--ccb-bg-card) 0);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.ccb-progress-circle::before {
    content: '';
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--ccb-bg-elevated);
    position: absolute;
}

.ccb-progress-circle span {
    position: relative;
    z-index: 1;
    font-size: 10px;
    font-weight: 600;
    color: var(--ccb-text-primary);
}

.ccb-module-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

.ccb-module-action {
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

.ccb-module-action:hover {
    background: var(--ccb-bg-card);
    color: var(--ccb-text-primary);
}

.ccb-module-action.ccb-module-delete:hover {
    background: var(--ccb-danger);
    color: white;
}

.ccb-module-preview {
    margin: var(--ccb-spacing-sm) 0;
    padding: var(--ccb-spacing-sm);
    background: var(--ccb-bg-card);
    border-radius: var(--ccb-border-radius);
    border-left: 3px solid var(--ccb-primary);
}

.ccb-module-preview p {
    margin: 0;
    color: var(--ccb-text-secondary);
    font-size: 14px;
    line-height: 1.4;
}

.ccb-module-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--ccb-spacing-sm);
    padding-top: var(--ccb-spacing-sm);
    border-top: 1px solid var(--ccb-border-color);
}

.ccb-module-stats {
    font-size: 12px;
    color: var(--ccb-text-muted);
}

.ccb-quick-btn {
    background: none;
    border: 1px solid var(--ccb-primary);
    color: var(--ccb-primary);
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
    transition: var(--ccb-transition);
}

.ccb-quick-btn:hover {
    background: var(--ccb-primary);
    color: white;
}

.ccb-course-footer-actions {
    display: flex;
    gap: var(--ccb-spacing-sm);
}

.ccb-btn-outline {
    background: transparent;
    border: 1px solid var(--ccb-border-color);
    color: var(--ccb-text-primary);
}

.ccb-btn-outline:hover {
    border-color: var(--ccb-primary);
    color: var(--ccb-primary);
}

/* Template Section */
.ccb-templates-section {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-lg);
}

.ccb-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--ccb-spacing-md);
}

.ccb-template-card {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-lg);
    text-align: center;
    transition: var(--ccb-transition);
}

.ccb-template-card:hover {
    border-color: var(--ccb-primary);
    transform: translateY(-2px);
    box-shadow: var(--ccb-shadow-lg);
}

.ccb-template-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--ccb-primary) 0%, var(--ccb-primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--ccb-spacing-md);
    color: white;
    font-size: 24px;
}

.ccb-template-content h4 {
    margin: 0 0 var(--ccb-spacing-sm) 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--ccb-text-primary);
}

.ccb-template-content p {
    margin: 0 0 var(--ccb-spacing-md) 0;
    color: var(--ccb-text-secondary);
    font-size: 14px;
}

/* Progress circles with CSS custom properties */
.ccb-progress-circle[data-progress] {
    --progress: attr(data-progress);
}

/* Enhanced responsive design for modules */
@media (max-width: 768px) {
    .ccb-module-header {
        flex-wrap: wrap;
        gap: var(--ccb-spacing-sm);
    }
    
    .ccb-module-meta {
        flex-direction: column;
        gap: var(--ccb-spacing-xs);
    }
    
    .ccb-module-footer {
        flex-direction: column;
        gap: var(--ccb-spacing-sm);
        align-items: stretch;
    }
    
    .ccb-course-footer-actions {
        flex-direction: column;
    }
    
    .ccb-templates-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// CourScribe Modules Dashboard JavaScript
window.ccbModulesDash = {
    initialized: false,
    
    init() {
        if (this.initialized) return;
        this.initialized = true;
        
        console.log('CourScribe Modules Dashboard initialized');
        
        this.bindEvents();
        this.loadStats();
        this.loadChart();
        this.initializeDragAndDrop();
    },
    
    bindEvents() {
        // Refresh stats
        jQuery('#ccbRefreshStats').on('click', (e) => {
            e.preventDefault();
            this.loadStats();
        });
        
        // Toggle course modules
        jQuery(document).on('click', '.ccb-toggle-modules', (e) => {
            e.preventDefault();
            const $button = jQuery(e.currentTarget);
            const courseId = $button.data('course-id');
            const $card = $button.closest('.ccb-course-card');
            const $modules = $card.find('.ccb-course-modules');
            
            if ($modules.is(':visible')) {
                $modules.slideUp(300);
                $button.removeClass('expanded').find('.ccb-toggle-text').text('View Modules');
            } else {
                $modules.slideDown(300);
                $button.addClass('expanded').find('.ccb-toggle-text').text('Hide Modules');
            }
        });
        
        // Module actions
        jQuery(document).on('click', '.ccb-module-action', (e) => {
            e.preventDefault();
            const $btn = jQuery(e.currentTarget);
            const action = $btn.data('action');
            const moduleId = $btn.data('module-id');
            
            this.handleModuleAction(action, moduleId);
        });
        
        // Search functionality
        jQuery('#ccbSearchInput').on('input', (e) => {
            this.filterModules(e.target.value);
        });
        
        // Filters
        jQuery('#ccbStatusFilter, #ccbCourseFilter').on('change', (e) => {
            this.applyFilters();
        });
        
        // Chart controls
        jQuery('#ccbChartType, #ccbChartPeriod').on('change', (e) => {
            this.loadChart();
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
                    action: 'courscribe_get_modules_stats',
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                jQuery('#statTotalModules').text(response.data.total_modules || 0);
                jQuery('#statTotalLessons').text(response.data.total_lessons || 0);
                jQuery('#statCompletedModules').text(response.data.completed_modules || 0);
                jQuery('#statAvgDuration').text((response.data.avg_duration || 0) + 'min');
            } else {
                $statsCards.text('Error');
                console.error('Stats loading failed:', response.data?.message);
            }
        } catch (error) {
            $statsCards.text('Error');
            console.error('Stats AJAX error:', error);
        }
    },
    
    async loadChart() {
        const $loading = jQuery('#ccbChartLoading');
        const canvas = document.getElementById('ccbModulesChart');
        const chartType = jQuery('#ccbChartType').val();
        const period = jQuery('#ccbChartPeriod').val();
        
        if (!canvas) return;
        
        $loading.show();
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_get_modules_chart',
                    chart_type: chartType,
                    period: period,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success && window.Chart) {
                const ctx = canvas.getContext('2d');
                
                // Destroy existing chart
                if (window.ccbModulesChart) {
                    window.ccbModulesChart.destroy();
                }
                
                const config = {
                    type: chartType === 'completion' ? 'doughnut' : 'line',
                    data: response.data,
                    options: this.getChartOptions(chartType)
                };
                
                window.ccbModulesChart = new Chart(ctx, config);
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
    
    getChartOptions(type) {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false
        };
        
        if (type === 'completion') {
            return {
                ...baseOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#B0B0B0'
                        }
                    }
                }
            };
        }
        
        return {
            ...baseOptions,
            scales: {
                x: {
                    border: { color: '#404040' },
                    grid: { color: '#404040' },
                    ticks: { color: '#B0B0B0' }
                },
                y: {
                    beginAtZero: true,
                    border: { color: '#404040' },
                    grid: { color: '#404040' },
                    ticks: { color: '#B0B0B0' }
                }
            }
        };
    },
    
    initializeDragAndDrop() {
        if (!window.Sortable) {
            console.warn('Sortable.js not loaded - drag and drop disabled');
            return;
        }
        
        // Initialize sortable for each modules list
        jQuery('.ccb-modules-list').each((index, element) => {
            if (element.sortableInstance) {
                element.sortableInstance.destroy();
            }
            
            element.sortableInstance = Sortable.create(element, {
                handle: '.ccb-module-drag',
                animation: 300,
                ghostClass: 'ccb-module-ghost',
                chosenClass: 'ccb-module-chosen',
                dragClass: 'ccb-module-drag-active',
                
                onStart: (evt) => {
                    evt.item.classList.add('dragging');
                },
                
                onEnd: (evt) => {
                    evt.item.classList.remove('dragging');
                    if (evt.oldIndex !== evt.newIndex) {
                        this.updateModuleOrder(element.dataset.courseId, evt);
                    }
                }
            });
        });
    },
    
    async updateModuleOrder(courseId, evt) {
        const moduleIds = Array.from(evt.to.children).map(item => item.dataset.moduleId);
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_update_module_order',
                    course_id: courseId,
                    module_order: moduleIds,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                this.showNotification('Module order updated successfully!', 'success');
            } else {
                this.showNotification('Failed to update module order', 'error');
            }
        } catch (error) {
            console.error('Update order error:', error);
            this.showNotification('Error updating module order', 'error');
        }
    },
    
    handleModuleAction(action, moduleId) {
        switch (action) {
            case 'edit':
                window.location.href = `/wp-admin/post.php?post=${moduleId}&action=edit`;
                break;
            case 'lessons':
                this.manageLessons(moduleId);
                break;
            case 'duplicate':
                this.duplicateModule(moduleId);
                break;
            case 'delete':
                this.deleteModule(moduleId);
                break;
        }
    },
    
    manageLessons(moduleId) {
        // TODO: Open lessons management modal
        alert(`Managing lessons for module ${moduleId}`);
    },
    
    async duplicateModule(moduleId) {
        if (!confirm('Are you sure you want to duplicate this module?')) return;
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_duplicate_module',
                    module_id: moduleId,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                this.showNotification('Module duplicated successfully!', 'success');
                location.reload();
            } else {
                this.showNotification('Failed to duplicate module', 'error');
            }
        } catch (error) {
            console.error('Duplicate error:', error);
            this.showNotification('Error duplicating module', 'error');
        }
    },
    
    async deleteModule(moduleId) {
        if (!confirm('Are you sure you want to delete this module? This action cannot be undone.')) return;
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_delete_module',
                    module_id: moduleId,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                jQuery(`[data-module-id="${moduleId}"]`).fadeOut(300, function() {
                    jQuery(this).remove();
                });
                this.showNotification('Module deleted successfully!', 'success');
            } else {
                this.showNotification('Failed to delete module', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Error deleting module', 'error');
        }
    },
    
    filterModules(searchTerm) {
        const $modules = jQuery('.ccb-module-item');
        const term = searchTerm.toLowerCase();
        
        $modules.each(function() {
            const $module = jQuery(this);
            const title = $module.find('.ccb-module-title').text().toLowerCase();
            
            if (title.includes(term)) {
                $module.show();
            } else {
                $module.hide();
            }
        });
    },
    
    applyFilters() {
        const status = jQuery('#ccbStatusFilter').val();
        const courseId = jQuery('#ccbCourseFilter').val();
        
        jQuery('.ccb-course-card').each(function() {
            const $card = jQuery(this);
            let showCard = true;
            
            // Filter by course
            if (courseId && $card.data('course-id') != courseId) {
                showCard = false;
            }
            
            // Filter modules by status
            if (status) {
                $card.find('.ccb-module-item').each(function() {
                    const $module = jQuery(this);
                    if ($module.data('status') === status) {
                        $module.show();
                    } else {
                        $module.hide();
                    }
                });
            } else {
                $card.find('.ccb-module-item').show();
            }
            
            if (showCard) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    },
    
    showNotification(message, type) {
        const $notification = jQuery(`
            <div class="ccb-notification ccb-notification-${type}">
                <span>${message}</span>
                <button class="ccb-notification-close">&times;</button>
            </div>
        `);
        
        jQuery('body').append($notification);
        
        setTimeout(() => {
            $notification.fadeOut(300, () => $notification.remove());
        }, 5000);
        
        $notification.find('.ccb-notification-close').on('click', () => {
            $notification.fadeOut(300, () => $notification.remove());
        });
    },
    
    addModule(courseId) {
        const url = `<?php echo admin_url('post-new.php?post_type=crscribe_module'); ?>&course_id=${courseId}`;
        window.location.href = url;
    },
    
    addLesson(moduleId) {
        const url = `<?php echo admin_url('post-new.php?post_type=crscribe_lesson'); ?>&module_id=${moduleId}`;
        window.location.href = url;
    },
    
    useTemplate(templateType) {
        // TODO: Implement template usage
        alert(`Using ${templateType} template`);
    },
    
    bulkOperations() {
        // TODO: Implement bulk operations modal
        alert('Bulk operations feature coming soon!');
    },
    
    generateWithAI() {
        // TODO: Implement AI generation modal
        alert('AI Generation feature coming soon!');
    }
};

// Initialize when DOM is ready
jQuery(document).ready(() => {
    window.ccbModulesDash.init();
});
</script>

<?php
}
?>