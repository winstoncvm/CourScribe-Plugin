<?php
/**
 * Sidebar Navigation Component
 * Displays curriculum structure and navigation options
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current curriculum data
$curriculum_id = isset($curriculum_id) ? $curriculum_id : (isset($_GET['curriculum_id']) ? absint($_GET['curriculum_id']) : 0);
$curriculum = get_post($curriculum_id);
$curriculum_goal = get_post_meta($curriculum_id, '_curriculum_goal', true) ?: 'No goal set';
?>

<div class="ccb-sidebar-header">
    <h2 class="ccb-logo">CourScribe</h2>
    <p class="ccb-logo-subtitle">Curriculum development with studio management</p>
</div>

<div class="ccb-curriculum-nav">
    <div class="ccb-nav-section">
        <div class="ccb-nav-section-title">Current Curriculum</div>
        <div class="ccb-nav-item active">
            <i class="fas fa-book"></i>
            <span><?php echo esc_html($curriculum ? $curriculum->post_title : 'Untitled Curriculum'); ?></span>
        </div>
    </div>

    <div class="ccb-nav-section">
        <div class="ccb-nav-section-title">Structure</div>
        <div class="ccb-nav-item" data-section="overview">
            <i class="fas fa-graduation-cap"></i>
            <span>Course Overview</span>
        </div>
        <div class="ccb-nav-item" data-section="courses">
            <i class="fas fa-cube"></i>
            <span>Courses (<?php echo count($courses ?? []); ?>)</span>
        </div>
        <div class="ccb-nav-item" data-section="modules">
            <i class="fas fa-puzzle-piece"></i>
            <span>Modules</span>
        </div>
        <div class="ccb-nav-item" data-section="lessons">
            <i class="fas fa-play-circle"></i>
            <span>Lessons</span>
        </div>
        <div class="ccb-nav-item" data-section="objectives">
            <i class="fas fa-bullseye"></i>
            <span>Learning Objectives</span>
        </div>
    </div>

    <div class="ccb-nav-section">
        <div class="ccb-nav-section-title">Tools</div>
        <div class="ccb-nav-item" id="ccbSidebarAIBtn">
            <i class="fas fa-magic"></i>
            <span>AI Assistant</span>
            <span class="ccb-nav-badge ccb-nav-badge-premium">PRO</span>
        </div>
        <div class="ccb-nav-item" id="ccbSidebarTemplatesBtn">
            <i class="fas fa-th-large"></i>
            <span>Templates</span>
        </div>
        <div class="ccb-nav-item" data-section="feedback">
            <i class="fas fa-comments"></i>
            <span>Feedback</span>
            <span class="ccb-nav-badge">3</span>
        </div>
        <div class="ccb-nav-item" id="ccbSidebarExportBtn">
            <i class="fas fa-file-export"></i>
            <span>Export PDF</span>
        </div>
    </div>

    <div class="ccb-nav-section">
        <div class="ccb-nav-section-title">Settings</div>
        <div class="ccb-nav-item" data-section="preferences">
            <i class="fas fa-cog"></i>
            <span>Preferences</span>
        </div>
        <div class="ccb-nav-item" data-section="collaboration">
            <i class="fas fa-users"></i>
            <span>Collaboration</span>
        </div>
        <div class="ccb-nav-item" data-section="history">
            <i class="fas fa-history"></i>
            <span>Version History</span>
        </div>
    </div>

    <!-- Progress Section -->
    <div class="ccb-nav-section">
        <div class="ccb-nav-section-title">Progress</div>
        <div class="ccb-progress-item">
            <div class="ccb-progress-label">
                <span>Completion</span>
                <span class="ccb-progress-value">45%</span>
            </div>
            <div class="ccb-progress-bar">
                <div class="ccb-progress-fill" style="width: 45%;"></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="ccb-nav-section">
        <div class="ccb-nav-section-title">Quick Stats</div>
        <div class="ccb-stats-grid">
            <div class="ccb-stat-item">
                <div class="ccb-stat-value"><?php echo count($courses ?? []); ?></div>
                <div class="ccb-stat-label">Courses</div>
            </div>
            <div class="ccb-stat-item">
                <div class="ccb-stat-value">0</div>
                <div class="ccb-stat-label">Modules</div>
            </div>
            <div class="ccb-stat-item">
                <div class="ccb-stat-value">0</div>
                <div class="ccb-stat-label">Lessons</div>
            </div>
            <div class="ccb-stat-item">
                <div class="ccb-stat-value">0</div>
                <div class="ccb-stat-label">Points</div>
            </div>
        </div>
    </div>
</div>

<style>
.ccb-curriculum-nav {
    padding: var(--ccb-spacing-lg);
}

.ccb-nav-section {
    margin-bottom: var(--ccb-spacing-lg);
}

.ccb-nav-section-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--ccb-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--ccb-spacing-sm);
}

.ccb-nav-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    margin-bottom: 2px;
    border-radius: var(--ccb-border-radius);
    cursor: pointer;
    transition: all var(--ccb-transition);
    color: var(--ccb-text-secondary);
    position: relative;
}

.ccb-nav-item:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-text-primary);
}

.ccb-nav-item.active {
    background: var(--ccb-gradient-secondary);
    color: white;
    box-shadow: 0 2px 8px rgba(244, 146, 62, 0.3);
}

.ccb-nav-item i {
    margin-right: 12px;
    width: 16px;
    text-align: center;
}

.ccb-nav-item span:first-of-type {
    flex: 1;
}

.ccb-nav-badge {
    background: var(--ccb-primary-gold);
    color: var(--ccb-bg-primary);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    margin-left: auto;
}

.ccb-nav-badge-premium {
    background: var(--ccb-gradient-primary);
    color: white;
}

.ccb-progress-item {
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-progress-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--ccb-text-secondary);
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-progress-value {
    font-weight: 600;
    color: var(--ccb-primary-gold);
}

.ccb-progress-bar {
    height: 4px;
    background: var(--ccb-border-color);
    border-radius: 2px;
    overflow: hidden;
}

.ccb-progress-fill {
    height: 100%;
    background: var(--ccb-gradient-secondary);
    transition: width var(--ccb-transition);
}

.ccb-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--ccb-spacing-sm);
}

.ccb-stat-item {
    text-align: center;
    padding: var(--ccb-spacing-sm);
    background: var(--ccb-bg-elevated);
    border-radius: var(--ccb-border-radius);
}

.ccb-stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--ccb-primary-gold);
    line-height: 1;
}

.ccb-stat-label {
    font-size: 10px;
    color: var(--ccb-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: var(--ccb-spacing-xs);
}
</style>