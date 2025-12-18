<?php
/**
 * CourScribe Lessons Dashboard
 * Modern, comprehensive lesson management interface
 */

if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_lessons_dashboard() {
    $current_user = wp_get_current_user();
    $site_url = home_url();
    $tier = get_option('courscribe_tier', 'basics');
    
    // Get all modules for lesson organization
    $modules_query = new WP_Query([
        'post_type' => 'crscribe_module',
        'post_status' => ['publish', 'draft'],
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => '_course_id',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    // Get all lessons for quick stats
    $lessons_query = new WP_Query([
        'post_type' => 'crscribe_lesson',
        'post_status' => ['publish', 'draft'],
        'numberposts' => -1,
        'fields' => 'ids'
    ]);
    
    $total_lessons = $lessons_query->found_posts;
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
                    <i class="fas fa-play-circle"></i>
                    Lessons Dashboard
                </h1>
                <p class="ccb-dashboard-subtitle">
                    Welcome back, <strong><?php echo esc_html($current_user->display_name); ?></strong>! 
                    Create engaging lessons and manage teaching content.
                </p>
            </div>
            <div class="ccb-header-actions">
                <button class="ccb-btn ccb-btn-secondary" id="ccbRefreshStats">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_lesson'); ?>" 
                   class="ccb-btn ccb-btn-primary">
                    <i class="fas fa-plus"></i>
                    New Lesson
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="ccb-stats-section">
        <div class="ccb-stats-grid" id="ccbStatsGrid">
            <div class="ccb-stat-card ccb-stat-primary">
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
                        <span>+18% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-secondary">
                <div class="ccb-stat-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statTeachingPoints">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Teaching Points</div>
                    <div class="ccb-stat-trend" id="pointsTrend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+25% this month</span>
                    </div>
                </div>
            </div>

            <div class="ccb-stat-card ccb-stat-success">
                <div class="ccb-stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="ccb-stat-content">
                    <div class="ccb-stat-number" id="statCompletedLessons">
                        <span class="ccb-loading-dots">...</span>
                    </div>
                    <div class="ccb-stat-label">Completed Lessons</div>
                    <div class="ccb-stat-trend" id="completedTrend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12% this month</span>
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
                    <div class="ccb-stat-period">Minutes per lesson</div>
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
                <a href="<?php echo admin_url('post-new.php?post_type=crscribe_lesson'); ?>" 
                   class="ccb-action-item ccb-action-primary">
                    <div class="ccb-action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Create Lesson</h4>
                        <p>Build an engaging lesson</p>
                    </div>
                </a>
                
                <button class="ccb-action-item ccb-action-secondary" onclick="window.ccbLessonsDash.lessonBuilder()">
                    <div class="ccb-action-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Lesson Builder</h4>
                        <p>Enhanced creation tool</p>
                    </div>
                </button>
                
                <button class="ccb-action-item ccb-action-tertiary" onclick="window.ccbLessonsDash.generateWithAI()">
                    <div class="ccb-action-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>AI Generate</h4>
                        <p>Create lessons with AI</p>
                    </div>
                </button>
                
                <button class="ccb-action-item ccb-action-info" onclick="window.ccbLessonsDash.lessonTemplates()">
                    <div class="ccb-action-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="ccb-action-content">
                        <h4>Templates</h4>
                        <p>Use lesson templates</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Lesson Performance Chart -->
        <div class="ccb-chart-section">
            <div class="ccb-chart-header">
                <h3 class="ccb-section-title">
                    <i class="fas fa-chart-bar"></i>
                    Lesson Performance Overview
                </h3>
                <div class="ccb-chart-controls">
                    <select class="ccb-chart-metric" id="ccbChartMetric">
                        <option value="engagement" selected>Engagement Rate</option>
                        <option value="completion">Completion Rate</option>
                        <option value="creation">Creation Activity</option>
                        <option value="objectives">Objectives Count</option>
                    </select>
                    <select class="ccb-chart-period" id="ccbChartPeriod">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            <div class="ccb-chart-container">
                <canvas id="ccbLessonsChart"></canvas>
                <div class="ccb-chart-loading" id="ccbChartLoading">
                    <div class="ccb-loading-spinner"></div>
                    <p>Loading lesson performance data...</p>
                </div>
            </div>
        </div>

        <!-- Modules & Lessons -->
        <div class="ccb-content-section">
            <div class="ccb-content-header">
                <h3 class="ccb-section-title">
                    <i class="fas fa-layer-group"></i>
                    Modules & Lessons
                </h3>
                <div class="ccb-content-filters">
                    <select class="ccb-filter-select" id="ccbStatusFilter">
                        <option value="">All Status</option>
                        <option value="publish">Published</option>
                        <option value="draft">Draft</option>
                        <option value="private">Private</option>
                    </select>
                    <select class="ccb-filter-select" id="ccbModuleFilter">
                        <option value="">All Modules</option>
                        <?php
                        if ($modules_query->have_posts()) {
                            while ($modules_query->have_posts()) {
                                $modules_query->the_post();
                                echo '<option value="' . get_the_ID() . '">' . esc_html(get_the_title()) . '</option>';
                            }
                            wp_reset_postdata();
                        }
                        ?>
                    </select>
                    <div class="ccb-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search lessons..." id="ccbSearchInput">
                    </div>
                </div>
            </div>

            <div class="ccb-modules-container" id="ccbModulesContainer">
                <?php if ($modules_query->have_posts()): ?>
                    <?php $modules_query->rewind_posts(); ?>
                    <?php while ($modules_query->have_posts()): $modules_query->the_post(); ?>
                        <?php
                        $module_id = get_the_ID();
                        $module_title = get_the_title();
                        $module_status = get_post_status();
                        $course_id = get_post_meta($module_id, '_course_id', true);
                        $course = $course_id ? get_post($course_id) : null;
                        
                        // Get lessons for this module
                        $module_lessons = new WP_Query([
                            'post_type' => 'crscribe_lesson',
                            'post_status' => ['publish', 'draft'],
                            'numberposts' => -1,
                            'meta_query' => [
                                [
                                    'key' => '_module_id',
                                    'value' => $module_id,
                                    'compare' => '='
                                ]
                            ],
                            'orderby' => 'menu_order',
                            'order' => 'ASC'
                        ]);
                        
                        $lessons_count = $module_lessons->found_posts;
                        ?>
                        
                        <div class="ccb-module-card" data-module-id="<?php echo esc_attr($module_id); ?>" data-status="<?php echo esc_attr($module_status); ?>">
                            <div class="ccb-module-header">
                                <div class="ccb-module-meta">
                                    <div class="ccb-module-status ccb-status-<?php echo esc_attr($module_status); ?>">
                                        <?php echo esc_html(ucfirst($module_status)); ?>
                                    </div>
                                    <div class="ccb-module-course">
                                        <i class="fas fa-book"></i>
                                        <?php echo $course ? esc_html($course->post_title) : 'No Course'; ?>
                                    </div>
                                    <div class="ccb-lessons-count">
                                        <i class="fas fa-play-circle"></i>
                                        <?php echo esc_html($lessons_count); ?> Lesson<?php echo $lessons_count !== 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                                <div class="ccb-module-actions">
                                    <button class="ccb-action-btn ccb-action-add" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>" 
                                            title="Add Lesson"
                                            onclick="window.ccbLessonsDash.addLesson(<?php echo esc_attr($module_id); ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="ccb-action-btn ccb-action-edit" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>" 
                                            title="Edit Module"
                                            onclick="window.location.href='<?php echo get_edit_post_link($module_id); ?>'">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="ccb-action-btn ccb-action-more" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>" 
                                            title="More Options">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="ccb-module-content">
                                <h4 class="ccb-module-title">
                                    <a href="<?php echo get_edit_post_link($module_id); ?>">
                                        <?php echo esc_html($module_title); ?>
                                    </a>
                                </h4>
                                <div class="ccb-module-summary">
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
                            
                            <div class="ccb-module-lessons" style="display: none;">
                                <?php if ($module_lessons->have_posts()): ?>
                                    <div class="ccb-lessons-list" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php while ($module_lessons->have_posts()): $module_lessons->the_post(); ?>
                                            <?php
                                            $lesson_id = get_the_ID();
                                            $lesson_status = get_post_status();
                                            $lesson_content = get_the_content();
                                            
                                            // Get lesson objectives and teaching points
                                            $objectives = get_post_meta($lesson_id, '_lesson_objectives', true);
                                            $teaching_points = get_post_meta($lesson_id, '_teaching_points', true);
                                            $objectives_count = is_array($objectives) ? count($objectives) : 0;
                                            $teaching_points_count = is_array($teaching_points) ? count($teaching_points) : 0;
                                            
                                            // Get lesson duration
                                            $duration = get_post_meta($lesson_id, '_lesson_duration', true) ?: '30';
                                            ?>
                                            
                                            <div class="ccb-lesson-item" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-status="<?php echo esc_attr($lesson_status); ?>">
                                                <div class="ccb-lesson-header">
                                                    <div class="ccb-lesson-drag">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </div>
                                                    <div class="ccb-lesson-icon">
                                                        <i class="fas fa-play"></i>
                                                    </div>
                                                    <div class="ccb-lesson-info">
                                                        <h5 class="ccb-lesson-title">
                                                            <a href="<?php echo get_edit_post_link($lesson_id); ?>">
                                                                <?php echo esc_html(get_the_title()); ?>
                                                            </a>
                                                        </h5>
                                                        <div class="ccb-lesson-meta">
                                                            <span class="ccb-lesson-status ccb-status-<?php echo esc_attr($lesson_status); ?>">
                                                                <?php echo esc_html(ucfirst($lesson_status)); ?>
                                                            </span>
                                                            <span class="ccb-lesson-duration">
                                                                <i class="fas fa-clock"></i>
                                                                <?php echo esc_html($duration); ?> min
                                                            </span>
                                                            <span class="ccb-lesson-objectives">
                                                                <i class="fas fa-bullseye"></i>
                                                                <?php echo $objectives_count; ?> objectives
                                                            </span>
                                                            <span class="ccb-lesson-points">
                                                                <i class="fas fa-lightbulb"></i>
                                                                <?php echo $teaching_points_count; ?> points
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ccb-lesson-completion">
                                                        <div class="ccb-completion-badge" data-completion="<?php echo rand(40, 100); ?>">
                                                            <span><?php echo rand(40, 100); ?>%</span>
                                                        </div>
                                                    </div>
                                                    <div class="ccb-lesson-actions">
                                                        <button class="ccb-lesson-action" 
                                                                data-action="edit" 
                                                                data-lesson-id="<?php echo esc_attr($lesson_id); ?>" 
                                                                title="Edit Lesson"
                                                                onclick="window.location.href='<?php echo get_edit_post_link($lesson_id); ?>'">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="ccb-lesson-action" 
                                                                data-action="preview" 
                                                                data-lesson-id="<?php echo esc_attr($lesson_id); ?>" 
                                                                title="Preview Lesson"
                                                                onclick="window.open('<?php echo get_permalink($lesson_id); ?>', '_blank')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="ccb-lesson-action" 
                                                                data-action="duplicate" 
                                                                data-lesson-id="<?php echo esc_attr($lesson_id); ?>" 
                                                                title="Duplicate Lesson">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                        <button class="ccb-lesson-action ccb-lesson-delete" 
                                                                data-action="delete" 
                                                                data-lesson-id="<?php echo esc_attr($lesson_id); ?>" 
                                                                title="Delete Lesson">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($lesson_content)): ?>
                                                <div class="ccb-lesson-preview">
                                                    <p><?php echo esc_html(wp_trim_words($lesson_content, 25)); ?></p>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($objectives_count > 0 || $teaching_points_count > 0): ?>
                                                <div class="ccb-lesson-highlights">
                                                    <?php if ($objectives_count > 0): ?>
                                                    <div class="ccb-lesson-objectives-preview">
                                                        <strong>Objectives:</strong>
                                                        <span><?php echo esc_html(implode(', ', array_slice(array_column($objectives, 'description'), 0, 2))); ?><?php echo count($objectives) > 2 ? '...' : ''; ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($teaching_points_count > 0): ?>
                                                    <div class="ccb-lesson-points-preview">
                                                        <strong>Key Points:</strong>
                                                        <span><?php echo esc_html(implode(', ', array_slice(array_column($teaching_points, 'title'), 0, 2))); ?><?php echo count($teaching_points) > 2 ? '...' : ''; ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="ccb-lesson-footer">
                                                    <div class="ccb-lesson-stats">
                                                        <span>Updated <?php echo esc_html(get_the_modified_date('M j')); ?></span>
                                                    </div>
                                                    <div class="ccb-lesson-quick-actions">
                                                        <button class="ccb-quick-btn" onclick="window.ccbLessonsDash.addObjective(<?php echo esc_attr($lesson_id); ?>)">
                                                            <i class="fas fa-plus"></i>
                                                            Add Objective
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="ccb-empty-lessons">
                                        <i class="fas fa-plus-circle"></i>
                                        <p>No lessons in this module yet.</p>
                                        <button class="ccb-btn ccb-btn-sm ccb-btn-primary" onclick="window.ccbLessonsDash.addLesson(<?php echo esc_attr($module_id); ?>)">
                                            Add First Lesson
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php wp_reset_postdata(); ?>
                            </div>
                            
                            <div class="ccb-module-footer">
                                <button class="ccb-toggle-lessons" data-module-id="<?php echo esc_attr($module_id); ?>">
                                    <span class="ccb-toggle-text">View Lessons</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="ccb-module-footer-actions">
                                    <button class="ccb-btn ccb-btn-sm ccb-btn-secondary" onclick="window.ccbLessonsDash.addLesson(<?php echo esc_attr($module_id); ?>)">
                                        <i class="fas fa-plus"></i>
                                        Add Lesson
                                    </button>
                                    <a href="<?php echo get_edit_post_link($module_id); ?>" 
                                       class="ccb-btn ccb-btn-sm ccb-btn-outline">
                                        <i class="fas fa-edit"></i>
                                        Edit Module
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="ccb-empty-state">
                        <div class="ccb-empty-icon">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <h3>No Modules Found</h3>
                        <p>Create modules first to organize your lessons.</p>
                        <div class="ccb-empty-actions">
                            <a href="<?php echo admin_url('post-new.php?post_type=crscribe_module'); ?>" 
                               class="ccb-btn ccb-btn-primary">
                                <i class="fas fa-plus"></i>
                                Create Module
                            </a>
                            <a href="<?php echo admin_url('post-new.php?post_type=crscribe_lesson'); ?>" 
                               class="ccb-btn ccb-btn-secondary">
                                <i class="fas fa-play-circle"></i>
                                Create Lesson
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
        
        <!-- Lesson Builder Tools -->
        <div class="ccb-tools-section">
            <h3 class="ccb-section-title">
                <i class="fas fa-tools"></i>
                Lesson Builder Tools
            </h3>
            <div class="ccb-tools-grid">
                <div class="ccb-tool-card" onclick="window.ccbLessonsDash.objectiveBuilder()">
                    <div class="ccb-tool-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="ccb-tool-content">
                        <h4>Objective Builder</h4>
                        <p>Create learning objectives using Bloom's Taxonomy</p>
                    </div>
                </div>
                
                <div class="ccb-tool-card" onclick="window.ccbLessonsDash.assessmentBuilder()">
                    <div class="ccb-tool-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="ccb-tool-content">
                        <h4>Assessment Builder</h4>
                        <p>Design quizzes and evaluations</p>
                    </div>
                </div>
                
                <div class="ccb-tool-card" onclick="window.ccbLessonsDash.mediaLibrary()">
                    <div class="ccb-tool-icon">
                        <i class="fas fa-photo-video"></i>
                    </div>
                    <div class="ccb-tool-content">
                        <h4>Media Library</h4>
                        <p>Manage lesson images and videos</p>
                    </div>
                </div>
                
                <div class="ccb-tool-card" onclick="window.ccbLessonsDash.interactiveElements()">
                    <div class="ccb-tool-icon">
                        <i class="fas fa-hand-pointer"></i>
                    </div>
                    <div class="ccb-tool-content">
                        <h4>Interactive Elements</h4>
                        <p>Add engaging interactive components</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Inherit all base styles from courses dashboard */
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

/* Additional Lesson-specific styles */
.ccb-lessons-list {
    display: grid;
    gap: var(--ccb-spacing-md);
    padding: var(--ccb-spacing-md) 0;
}

.ccb-lesson-item {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    transition: var(--ccb-transition);
    position: relative;
}

.ccb-lesson-item:hover {
    border-color: var(--ccb-primary);
    transform: translateY(-1px);
    box-shadow: var(--ccb-shadow);
}

.ccb-lesson-item.dragging {
    opacity: 0.5;
    transform: rotate(3deg);
}

.ccb-lesson-header {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    margin-bottom: var(--ccb-spacing-sm);
}

.ccb-lesson-drag {
    color: var(--ccb-text-muted);
    cursor: grab;
    padding: var(--ccb-spacing-xs);
}

.ccb-lesson-drag:hover {
    color: var(--ccb-primary);
}

.ccb-lesson-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--ccb-primary) 0%, var(--ccb-primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.ccb-lesson-info {
    flex: 1;
}

.ccb-lesson-title {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    font-size: 16px;
    font-weight: 600;
}

.ccb-lesson-title a {
    color: var(--ccb-text-primary);
    text-decoration: none;
}

.ccb-lesson-title a:hover {
    color: var(--ccb-primary);
}

.ccb-lesson-meta {
    display: flex;
    gap: var(--ccb-spacing-md);
    font-size: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.ccb-lesson-meta > span {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
}

.ccb-lesson-status {
    padding: 2px var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 500;
    text-transform: uppercase;
}

.ccb-completion-badge {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: conic-gradient(var(--ccb-success) calc(var(--completion, 0) * 1%), var(--ccb-bg-card) 0);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-left: var(--ccb-spacing-md);
}

.ccb-completion-badge::before {
    content: '';
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--ccb-bg-elevated);
    position: absolute;
}

.ccb-completion-badge span {
    position: relative;
    z-index: 1;
    font-size: 10px;
    font-weight: 600;
    color: var(--ccb-text-primary);
}

.ccb-lesson-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

.ccb-lesson-action {
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

.ccb-lesson-action:hover {
    background: var(--ccb-bg-card);
    color: var(--ccb-text-primary);
}

.ccb-lesson-action.ccb-lesson-delete:hover {
    background: var(--ccb-danger);
    color: white;
}

.ccb-lesson-preview {
    margin: var(--ccb-spacing-sm) 0;
    padding: var(--ccb-spacing-sm);
    background: var(--ccb-bg-card);
    border-radius: var(--ccb-border-radius);
    border-left: 3px solid var(--ccb-success);
}

.ccb-lesson-preview p {
    margin: 0;
    color: var(--ccb-text-secondary);
    font-size: 14px;
    line-height: 1.4;
}

.ccb-lesson-highlights {
    background: var(--ccb-bg-card);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-sm);
    margin: var(--ccb-spacing-sm) 0;
    font-size: 12px;
}

.ccb-lesson-objectives-preview,
.ccb-lesson-points-preview {
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-lesson-objectives-preview:last-child,
.ccb-lesson-points-preview:last-child {
    margin-bottom: 0;
}

.ccb-lesson-highlights strong {
    color: var(--ccb-primary);
    font-weight: 600;
}

.ccb-lesson-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--ccb-spacing-sm);
    padding-top: var(--ccb-spacing-sm);
    border-top: 1px solid var(--ccb-border-color);
}

.ccb-lesson-stats {
    font-size: 12px;
    color: var(--ccb-text-muted);
}

.ccb-quick-btn {
    background: none;
    border: 1px solid var(--ccb-success);
    color: var(--ccb-success);
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
    background: var(--ccb-success);
    color: white;
}

/* Tools Section */
.ccb-tools-section {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-lg);
}

.ccb-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--ccb-spacing-md);
}

.ccb-tool-card {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    transition: var(--ccb-transition);
    cursor: pointer;
}

.ccb-tool-card:hover {
    border-color: var(--ccb-primary);
    transform: translateY(-2px);
    box-shadow: var(--ccb-shadow-lg);
}

.ccb-tool-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--ccb-info) 0%, #138496 100%);
    border-radius: var(--ccb-border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.ccb-tool-content h4 {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--ccb-text-primary);
}

.ccb-tool-content p {
    margin: 0;
    color: var(--ccb-text-secondary);
    font-size: 14px;
}

/* Inherit and apply common dashboard styles */
.ccb-dashboard-header,
.ccb-header-content,
.ccb-header-logo,
.ccb-header-info,
.ccb-header-actions,
.ccb-dashboard-title,
.ccb-dashboard-subtitle,
.ccb-btn,
.ccb-btn-primary,
.ccb-btn-secondary,
.ccb-btn-sm,
.ccb-btn-outline,
.ccb-stats-section,
.ccb-stats-grid,
.ccb-stat-card,
.ccb-stat-icon,
.ccb-stat-content,
.ccb-stat-number,
.ccb-stat-label,
.ccb-stat-trend,
.ccb-stat-period,
.ccb-main-content,
.ccb-section-title,
.ccb-quick-actions,
.ccb-actions-grid,
.ccb-action-item,
.ccb-action-icon,
.ccb-action-content,
.ccb-chart-section,
.ccb-chart-header,
.ccb-chart-controls,
.ccb-chart-container,
.ccb-chart-loading,
.ccb-loading-spinner,
.ccb-content-section,
.ccb-content-header,
.ccb-content-filters,
.ccb-filter-select,
.ccb-search-box,
.ccb-empty-state,
.ccb-empty-icon,
.ccb-empty-actions,
.ccb-module-card,
.ccb-module-header,
.ccb-module-meta,
.ccb-module-actions,
.ccb-module-content,
.ccb-module-title,
.ccb-module-summary,
.ccb-module-footer,
.ccb-toggle-lessons,
.ccb-module-footer-actions,
.ccb-action-btn,
.ccb-loading-dots {
    /* Use same styles as courses dashboard with lesson-specific overrides */
}

/* Status styling */
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

/* Enhanced responsive design for lessons */
@media (max-width: 768px) {
    .ccb-lesson-header {
        flex-wrap: wrap;
        gap: var(--ccb-spacing-sm);
    }
    
    .ccb-lesson-meta {
        flex-direction: column;
        gap: var(--ccb-spacing-xs);
        align-items: flex-start;
    }
    
    .ccb-lesson-footer {
        flex-direction: column;
        gap: var(--ccb-spacing-sm);
        align-items: stretch;
    }
    
    .ccb-module-footer-actions {
        flex-direction: column;
    }
    
    .ccb-tools-grid {
        grid-template-columns: 1fr;
    }
    
    .ccb-tool-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// CourScribe Lessons Dashboard JavaScript
window.ccbLessonsDash = {
    initialized: false,
    
    init() {
        if (this.initialized) return;
        this.initialized = true;
        
        console.log('CourScribe Lessons Dashboard initialized');
        
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
        
        // Toggle module lessons
        jQuery(document).on('click', '.ccb-toggle-lessons', (e) => {
            e.preventDefault();
            const $button = jQuery(e.currentTarget);
            const moduleId = $button.data('module-id');
            const $card = $button.closest('.ccb-module-card');
            const $lessons = $card.find('.ccb-module-lessons');
            
            if ($lessons.is(':visible')) {
                $lessons.slideUp(300);
                $button.removeClass('expanded').find('.ccb-toggle-text').text('View Lessons');
            } else {
                $lessons.slideDown(300);
                $button.addClass('expanded').find('.ccb-toggle-text').text('Hide Lessons');
            }
        });
        
        // Lesson actions
        jQuery(document).on('click', '.ccb-lesson-action', (e) => {
            e.preventDefault();
            const $btn = jQuery(e.currentTarget);
            const action = $btn.data('action');
            const lessonId = $btn.data('lesson-id');
            
            this.handleLessonAction(action, lessonId);
        });
        
        // Search functionality
        jQuery('#ccbSearchInput').on('input', (e) => {
            this.filterLessons(e.target.value);
        });
        
        // Filters
        jQuery('#ccbStatusFilter, #ccbModuleFilter').on('change', (e) => {
            this.applyFilters();
        });
        
        // Chart controls
        jQuery('#ccbChartMetric, #ccbChartPeriod').on('change', (e) => {
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
                    action: 'courscribe_get_lessons_stats',
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                jQuery('#statTotalLessons').text(response.data.total_lessons || 0);
                jQuery('#statTeachingPoints').text(response.data.teaching_points || 0);
                jQuery('#statCompletedLessons').text(response.data.completed_lessons || 0);
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
        const canvas = document.getElementById('ccbLessonsChart');
        const metric = jQuery('#ccbChartMetric').val();
        const period = jQuery('#ccbChartPeriod').val();
        
        if (!canvas) return;
        
        $loading.show();
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_get_lessons_chart',
                    metric: metric,
                    period: period,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success && window.Chart) {
                const ctx = canvas.getContext('2d');
                
                // Destroy existing chart
                if (window.ccbLessonsChart) {
                    window.ccbLessonsChart.destroy();
                }
                
                const config = {
                    type: metric === 'completion' ? 'doughnut' : 'bar',
                    data: response.data,
                    options: this.getChartOptions(metric)
                };
                
                window.ccbLessonsChart = new Chart(ctx, config);
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
    
    getChartOptions(metric) {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false
        };
        
        if (metric === 'completion') {
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
        
        // Initialize sortable for each lessons list
        jQuery('.ccb-lessons-list').each((index, element) => {
            if (element.sortableInstance) {
                element.sortableInstance.destroy();
            }
            
            element.sortableInstance = Sortable.create(element, {
                handle: '.ccb-lesson-drag',
                animation: 300,
                ghostClass: 'ccb-lesson-ghost',
                chosenClass: 'ccb-lesson-chosen',
                dragClass: 'ccb-lesson-drag-active',
                
                onStart: (evt) => {
                    evt.item.classList.add('dragging');
                },
                
                onEnd: (evt) => {
                    evt.item.classList.remove('dragging');
                    if (evt.oldIndex !== evt.newIndex) {
                        this.updateLessonOrder(element.dataset.moduleId, evt);
                    }
                }
            });
        });
    },
    
    async updateLessonOrder(moduleId, evt) {
        const lessonIds = Array.from(evt.to.children).map(item => item.dataset.lessonId);
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_update_lesson_order',
                    module_id: moduleId,
                    lesson_order: lessonIds,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                this.showNotification('Lesson order updated successfully!', 'success');
            } else {
                this.showNotification('Failed to update lesson order', 'error');
            }
        } catch (error) {
            console.error('Update order error:', error);
            this.showNotification('Error updating lesson order', 'error');
        }
    },
    
    handleLessonAction(action, lessonId) {
        switch (action) {
            case 'edit':
                window.location.href = `/wp-admin/post.php?post=${lessonId}&action=edit`;
                break;
            case 'preview':
                window.open(`/?p=${lessonId}`, '_blank');
                break;
            case 'duplicate':
                this.duplicateLesson(lessonId);
                break;
            case 'delete':
                this.deleteLesson(lessonId);
                break;
        }
    },
    
    async duplicateLesson(lessonId) {
        if (!confirm('Are you sure you want to duplicate this lesson?')) return;
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_duplicate_lesson',
                    lesson_id: lessonId,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                this.showNotification('Lesson duplicated successfully!', 'success');
                location.reload();
            } else {
                this.showNotification('Failed to duplicate lesson', 'error');
            }
        } catch (error) {
            console.error('Duplicate error:', error);
            this.showNotification('Error duplicating lesson', 'error');
        }
    },
    
    async deleteLesson(lessonId) {
        if (!confirm('Are you sure you want to delete this lesson? This action cannot be undone.')) return;
        
        try {
            const response = await jQuery.ajax({
                url: courscribeAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'courscribe_delete_lesson',
                    lesson_id: lessonId,
                    nonce: courscribeAjax.nonce
                }
            });
            
            if (response.success) {
                jQuery(`[data-lesson-id="${lessonId}"]`).fadeOut(300, function() {
                    jQuery(this).remove();
                });
                this.showNotification('Lesson deleted successfully!', 'success');
            } else {
                this.showNotification('Failed to delete lesson', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Error deleting lesson', 'error');
        }
    },
    
    filterLessons(searchTerm) {
        const $lessons = jQuery('.ccb-lesson-item');
        const term = searchTerm.toLowerCase();
        
        $lessons.each(function() {
            const $lesson = jQuery(this);
            const title = $lesson.find('.ccb-lesson-title').text().toLowerCase();
            const content = $lesson.find('.ccb-lesson-preview').text().toLowerCase();
            
            if (title.includes(term) || content.includes(term)) {
                $lesson.show();
            } else {
                $lesson.hide();
            }
        });
    },
    
    applyFilters() {
        const status = jQuery('#ccbStatusFilter').val();
        const moduleId = jQuery('#ccbModuleFilter').val();
        
        jQuery('.ccb-module-card').each(function() {
            const $card = jQuery(this);
            let showCard = true;
            
            // Filter by module
            if (moduleId && $card.data('module-id') != moduleId) {
                showCard = false;
            }
            
            // Filter lessons by status
            if (status) {
                $card.find('.ccb-lesson-item').each(function() {
                    const $lesson = jQuery(this);
                    if ($lesson.data('status') === status) {
                        $lesson.show();
                    } else {
                        $lesson.hide();
                    }
                });
            } else {
                $card.find('.ccb-lesson-item').show();
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
    
    addLesson(moduleId) {
        const url = `<?php echo admin_url('post-new.php?post_type=crscribe_lesson'); ?>&module_id=${moduleId}`;
        window.location.href = url;
    },
    
    addObjective(lessonId) {
        // TODO: Open objectives modal for specific lesson
        alert(`Adding objective to lesson ${lessonId}`);
    },
    
    lessonBuilder() {
        // TODO: Open lesson builder interface
        alert('Enhanced Lesson Builder coming soon!');
    },
    
    objectiveBuilder() {
        // TODO: Open objective builder with Bloom's taxonomy
        alert('Objective Builder with Bloom\'s Taxonomy coming soon!');
    },
    
    assessmentBuilder() {
        // TODO: Open assessment builder
        alert('Assessment Builder coming soon!');
    },
    
    mediaLibrary() {
        // TODO: Open media library interface
        alert('Media Library coming soon!');
    },
    
    interactiveElements() {
        // TODO: Open interactive elements panel
        alert('Interactive Elements coming soon!');
    },
    
    lessonTemplates() {
        // TODO: Open lesson templates modal
        alert('Lesson Templates coming soon!');
    },
    
    generateWithAI() {
        // TODO: Implement AI generation modal
        alert('AI Generation feature coming soon!');
    }
};

// Initialize when DOM is ready
jQuery(document).ready(() => {
    window.ccbLessonsDash.init();
});
</script>

<?php
}
?>