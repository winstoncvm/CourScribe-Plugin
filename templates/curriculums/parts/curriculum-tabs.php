<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Curriculum Tabs Component
 * Handles the tab navigation and content sections
 */
function courscribe_render_curriculum_tabs($curriculum_id, $is_client) {
    ?>
    <div class="pcss3t pcss3t-effect-scale pcss3t-theme-1">
        <input type="radio" name="pcss3t" checked id="tab1" class="tab-content-1">
        <label for="tab1"><i class="icon-home"></i>Courses</label>

        <input type="radio" name="pcss3t" id="tab2" class="tab-content-2">
        <label for="tab2"><i class="icon-picture"></i>Activity</label>

        <input type="radio" name="pcss3t" id="tab3" class="tab-content-3">
        <label for="tab3"><i class="icon-headphones"></i>Feedback</label>

        <div class="scrollable-tabs">
            <?php courscribe_render_courses_tab($curriculum_id, $is_client); ?>
            <?php courscribe_render_activity_tab($curriculum_id); ?>
            <?php courscribe_render_feedback_tab($curriculum_id); ?>
        </div>
    </div>
    <?php
}

/**
 * Render Courses Tab Content
 */
function courscribe_render_courses_tab($curriculum_id, $is_client) {
    ?>
    <ul>
        <li class="tab-content tab-content-1 typography">
            <div class="course-stage-wrapper mb-4">
                <div class="courscribe-container-row gap-3" 
                     data-tour-step="2" 
                     data-tour-title="Course Management" 
                     data-tour-content="This is where you manage all courses within your curriculum. You can add, edit, and organize your educational content.">
                    
                    <?php if (!$is_client) : ?>
                        <div class="courscribe-row-add-course">
                            <?php courscribe_render_add_course_section($curriculum_id); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="courscribe-divider-row px-10">
                        <?php courscribe_render_course_accordion($curriculum_id, wp_get_current_user(), CourScribe_Tooltips::get_instance(), $is_client); ?>
                    </div>
                </div>
            </div>
        </li>
    </ul>
    <?php
}

/**
 * Render Activity Tab Content
 */
function courscribe_render_activity_tab($curriculum_id) {
    ?>
    <ul>
        <li class="tab-content tab-content-2 typography">
            <div class="activity-content">
                <h4 class="text-light mb-4">Activity Log</h4>
                <div id="activity-log-container">
                    <?php courscribe_render_activity_log($curriculum_id); ?>
                </div>
            </div>
        </li>
    </ul>
    <?php
}

/**
 * Render Feedback Tab Content
 */
function courscribe_render_feedback_tab($curriculum_id) {
    ?>
    <ul>
        <li class="tab-content tab-content-3 typography">
            <div class="feedback-content">
                <h4 class="text-light mb-4">Feedback Summary</h4>
                <div id="feedback-summary-container">
                    <?php courscribe_render_feedback_summary($curriculum_id); ?>
                </div>
            </div>
        </li>
    </ul>
    <?php
}

/**
 * Render Add Course Section
 */
function courscribe_render_add_course_section($curriculum_id) {
    $tooltips = CourScribe_Tooltips::get_instance();
    ?>
    <div class="add-course-section">
        <div class="courscribe-row-add-courses" 
             data-tour-step="3" 
             data-tour-title="Add New Course" 
             data-tour-content="Click here to add a new course to your curriculum. Courses contain modules and lessons.">
            
            <?php
            $add_course_button = '
                <button type="button" class="btn btn-primary add-course-btn" 
                        data-curriculum-id="' . esc_attr($curriculum_id) . '">
                    <i class="fa fa-plus"></i> Add Course
                </button>';
            
            echo $tooltips->wrap_button_with_tooltip($add_course_button, [
                'title' => 'Add Course',
                'description' => 'Add a new course to this curriculum. Courses help organize your content into major subject areas.',
                'required_package' => 'CourScribe Basics'
            ]);
            ?>
        </div>
        
        <!-- AI Course Generation Section -->
        <?php if (courscribe_can_use_ai_features()) : ?>
            <div class="ai-course-generation mt-3">
                <div class="d-flex align-items-center">
                    <span class="text-muted me-2">or</span>
                    <?php
                    $ai_button = '
                        <button type="button" class="btn btn-outline-warning ai-generate-course-btn" 
                                data-curriculum-id="' . esc_attr($curriculum_id) . '">
                            <i class="fa fa-magic"></i> Generate with AI
                        </button>';
                    
                    echo $tooltips->wrap_button_with_tooltip($ai_button, [
                        'title' => 'AI Course Generation',
                        'description' => 'Let AI generate course content based on your curriculum goals and topic.',
                        'required_package' => 'CourScribe Plus'
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Activity Log
 */
function courscribe_render_activity_log($curriculum_id) {
    global $wpdb;
    
    $activities = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}courscribe_activity_log 
         WHERE curriculum_id = %d 
         ORDER BY created_at DESC 
         LIMIT 20",
        $curriculum_id
    ));
    
    if (!empty($activities)) {
        echo '<div class="activity-list">';
        foreach ($activities as $activity) {
            ?>
            <div class="activity-item border-bottom border-secondary py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="activity-content">
                        <h6 class="text-light mb-1"><?php echo esc_html($activity->action); ?></h6>
                        <p class="text-muted mb-1 small"><?php echo esc_html($activity->description); ?></p>
                        <small class="text-muted">
                            by <?php echo esc_html($activity->user_name); ?> 
                            on <?php echo esc_html(date('M j, Y \a\t g:i A', strtotime($activity->created_at))); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p class="text-muted text-center py-4">No activity recorded yet.</p>';
    }
}

/**
 * Render Feedback Summary
 */
function courscribe_render_feedback_summary($curriculum_id) {
    global $wpdb;
    
    $feedback_counts = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) as total_feedback,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_feedback,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_feedback,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_feedback
         FROM {$wpdb->prefix}courscribe_annotations 
         WHERE post_id = %d AND post_type = 'crscribe_curriculum'",
        $curriculum_id
    ));
    
    if ($feedback_counts && $feedback_counts->total_feedback > 0) {
        ?>
        <div class="feedback-stats row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h3><?php echo esc_html($feedback_counts->total_feedback); ?></h3>
                    <p class="mb-0">Total Feedback</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-dark p-3 rounded">
                    <h3><?php echo esc_html($feedback_counts->open_feedback); ?></h3>
                    <p class="mb-0">Open</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h3><?php echo esc_html($feedback_counts->in_progress_feedback); ?></h3>
                    <p class="mb-0">In Progress</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h3><?php echo esc_html($feedback_counts->resolved_feedback); ?></h3>
                    <p class="mb-0">Resolved</p>
                </div>
            </div>
        </div>
        
        <div class="recent-feedback">
            <h5 class="text-light mb-3">Recent Feedback</h5>
            <?php courscribe_render_recent_feedback($curriculum_id); ?>
        </div>
        <?php
    } else {
        echo '<p class="text-muted text-center py-4">No feedback received yet.</p>';
    }
}

/**
 * Render Recent Feedback Items
 */
function courscribe_render_recent_feedback($curriculum_id) {
    global $wpdb;
    
    $recent_feedback = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}courscribe_annotations 
         WHERE post_id = %d AND post_type = 'crscribe_curriculum'
         ORDER BY created_at DESC 
         LIMIT 10",
        $curriculum_id
    ));
    
    if (!empty($recent_feedback)) {
        echo '<div class="feedback-list">';
        foreach ($recent_feedback as $feedback) {
            $status_class = '';
            switch ($feedback->status) {
                case 'open':
                    $status_class = 'bg-warning text-dark';
                    break;
                case 'in_progress':
                    $status_class = 'bg-info text-white';
                    break;
                case 'resolved':
                    $status_class = 'bg-success text-white';
                    break;
            }
            ?>
            <div class="feedback-item border-bottom border-secondary py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="feedback-content flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge <?php echo esc_attr($status_class); ?> me-2">
                                <?php echo esc_html(ucfirst($feedback->status)); ?>
                            </span>
                            <h6 class="text-light mb-0"><?php echo esc_html($feedback->field_name); ?></h6>
                        </div>
                        <p class="text-muted mb-1 small"><?php echo esc_html($feedback->feedback_text); ?></p>
                        <small class="text-muted">
                            by <?php echo esc_html($feedback->user_name); ?> 
                            on <?php echo esc_html(date('M j, Y \a\t g:i A', strtotime($feedback->created_at))); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
}

/**
 * Check if user can use AI features
 */
function courscribe_can_use_ai_features() {
    // This would check user's subscription tier
    // For now, return true for admins and collaborators
    return current_user_can('administrator') || in_array('collaborator', wp_get_current_user()->roles);
}