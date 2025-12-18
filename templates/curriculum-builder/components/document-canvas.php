<?php
/**
 * Document Canvas Component
 * Main editing interface with document-like layout
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current curriculum data
$curriculum_id = isset($curriculum_id) ? $curriculum_id : (isset($_GET['curriculum_id']) ? absint($_GET['curriculum_id']) : 0);
$curriculum = get_post($curriculum_id);
$curriculum_title = $curriculum ? $curriculum->post_title : 'Untitled Curriculum';
$curriculum_goal = get_post_meta($curriculum_id, '_curriculum_goal', true) ?: 'No goal set';
$curriculum_topic = get_post_meta($curriculum_id, '_curriculum_topic', true) ?: 'General';
?>

<div class="ccb-document-page ccb-fade-in">
    
    <!-- Page Header -->
    <div class="ccb-page-header">
        <h1 class="ccb-page-title" id="ccbCurriculumTitle" contenteditable="true"><?php echo esc_html($curriculum_title); ?></h1>
        <p class="ccb-page-subtitle">A comprehensive curriculum covering modern educational practices and methodologies</p>
    </div>

    <div class="ccb-page-content">
        
        <!-- Curriculum Information Section -->
        <div class="ccb-content-section" id="ccbCurriculumInfoSection">
            <div class="ccb-section-header">
                <h2 class="ccb-section-title">Curriculum Information</h2>
                <div class="ccb-section-actions">
                    <button class="ccb-action-btn" title="Edit Information" data-action="edit-info">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="ccb-action-btn" title="AI Generate" data-action="ai-generate-info">
                        <i class="fas fa-magic"></i>
                    </button>
                </div>
            </div>
            
            <!-- Curriculum Basic Fields -->
            <div class="ccb-curriculum-fields">
                <div class="ccb-field-group">
                    <label class="ccb-field-label">
                        <i class="fas fa-bullseye"></i>
                        Curriculum Goal
                    </label>
                    <textarea class="ccb-field-textarea" 
                              id="ccbCurriculumGoal" 
                              data-field="curriculum_goal"
                              placeholder="What is the main goal of this curriculum? What should students achieve?"
                              rows="3"><?php echo esc_textarea($curriculum_goal); ?></textarea>
                </div>
                
                <div class="ccb-field-group">
                    <label class="ccb-field-label">
                        <i class="fas fa-tags"></i>
                        Topic/Subject Area
                    </label>
                    <input type="text" 
                           class="ccb-field-input" 
                           id="ccbCurriculumTopic" 
                           data-field="curriculum_topic"
                           value="<?php echo esc_attr($curriculum_topic); ?>" 
                           placeholder="e.g., Web Development, Digital Marketing, Data Science" />
                </div>
                
                <div class="ccb-field-group">
                    <label class="ccb-field-label">
                        <i class="fas fa-sticky-note"></i>
                        Internal Notes
                    </label>
                    <textarea class="ccb-field-textarea" 
                              id="ccbCurriculumNotes" 
                              data-field="curriculum_notes"
                              placeholder="Internal notes, development notes, requirements, etc."
                              rows="4"><?php echo esc_textarea(get_post_meta($curriculum_id, '_curriculum_notes', true)); ?></textarea>
                </div>
                
                <div class="ccb-field-group">
                    <label class="ccb-field-label">
                        <i class="fas fa-info-circle"></i>
                        Status
                    </label>
                    <?php $curriculum_status = get_post_meta($curriculum_id, '_curriculum_status', true) ?: 'draft'; ?>
                    <select class="ccb-field-select" id="ccbCurriculumStatus" data-field="curriculum_status">
                        <option value="draft" <?php selected($curriculum_status, 'draft'); ?>>Draft</option>
                        <option value="review" <?php selected($curriculum_status, 'review'); ?>>In Review</option>
                        <option value="approved" <?php selected($curriculum_status, 'approved'); ?>>Approved</option>
                        <option value="published" <?php selected($curriculum_status, 'published'); ?>>Published</option>
                        <option value="archived" <?php selected($curriculum_status, 'archived'); ?>>Archived</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Curriculum Overview Section -->
        <div class="ccb-content-section" id="ccbOverviewSection">
            <div class="ccb-section-header">
                <h2 class="ccb-section-title">Curriculum Overview</h2>
                <div class="ccb-section-actions">
                    <button class="ccb-action-btn" title="Edit Section" data-action="edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="ccb-action-btn" title="AI Generate" data-action="ai-generate">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button class="ccb-action-btn" title="Add Template" data-action="template">
                        <i class="fas fa-th-large"></i>
                    </button>
                </div>
            </div>
            
            <!-- Rich Text Toolbar -->
            <div class="ccb-toolbar">
                <button class="ccb-toolbar-btn" data-command="bold" title="Bold">
                    <i class="fas fa-bold"></i>
                </button>
                <button class="ccb-toolbar-btn" data-command="italic" title="Italic">
                    <i class="fas fa-italic"></i>
                </button>
                <button class="ccb-toolbar-btn" data-command="underline" title="Underline">
                    <i class="fas fa-underline"></i>
                </button>
                <div class="ccb-toolbar-separator"></div>
                <button class="ccb-toolbar-btn" data-command="insertUnorderedList" title="Bullet List">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button class="ccb-toolbar-btn" data-command="insertOrderedList" title="Numbered List">
                    <i class="fas fa-list-ol"></i>
                </button>
                <div class="ccb-toolbar-separator"></div>
                <button class="ccb-toolbar-btn" data-command="createLink" title="Insert Link">
                    <i class="fas fa-link"></i>
                </button>
                <button class="ccb-toolbar-btn" data-command="insertImage" title="Insert Image">
                    <i class="fas fa-image"></i>
                </button>
            </div>
            
            <div class="ccb-editor-container" 
                 id="ccbCurriculumOverview" 
                 data-field="curriculum_overview" 
                 data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
                 data-editor-type="overview"
                 data-content="<?php echo esc_attr(get_post_meta($curriculum_id, '_curriculum_overview', true) ?: ''); ?>">
            </div>
        </div>

        <!-- Courses & Modules Section -->
        <div class="ccb-content-section" id="ccbCoursesSection">
            <div class="ccb-section-header">
                <h2 class="ccb-section-title">Courses & Modules</h2>
                <div class="ccb-section-actions">
                    <button class="ccb-action-btn" title="Add Course" data-action="add-course">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="ccb-action-btn" title="Reorder" data-action="reorder">
                        <i class="fas fa-arrows-alt"></i>
                    </button>
                    <button class="ccb-action-btn" title="Import Template" data-action="import-template">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>

            <div id="ccbCoursesContainer" class="ccb-courses-container">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $index => $course): ?>
                        <?php include plugin_dir_path(__FILE__) . 'course-card.php'; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="ccb-empty-state">
                        <div class="ccb-empty-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="ccb-empty-title">No Courses Added Yet</h3>
                        <p class="ccb-empty-description">Get started by adding your first course or selecting from our template library.</p>
                        <div class="ccb-empty-actions">
                            <button class="ccb-btn ccb-btn-primary" id="ccbAddFirstCourse">
                                <i class="fas fa-plus"></i>
                                Add Your First Course
                            </button>
                            <button class="ccb-btn ccb-btn-secondary" id="ccbBrowseTemplates">
                                <i class="fas fa-th-large"></i>
                                Browse Templates
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Drop Zone for New Courses -->
                <div class="ccb-drop-zone" id="ccbCourseDropZone">
                    <div class="ccb-drop-zone-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="ccb-drop-zone-text">
                        Drop a template here or click to add a new course
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning Objectives Section -->
        <div class="ccb-content-section" id="ccbObjectivesSection">
            <div class="ccb-section-header">
                <h2 class="ccb-section-title">Learning Objectives</h2>
                <div class="ccb-section-actions">
                    <button class="ccb-action-btn" title="Add Objective" data-action="add-objective">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="ccb-action-btn" title="AI Generate" data-action="ai-generate-objectives">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button class="ccb-action-btn" title="Bloom's Taxonomy Helper" data-action="blooms-helper">
                        <i class="fas fa-question-circle"></i>
                    </button>
                </div>
            </div>
            
            <div class="ccb-editor-container" 
                 id="ccbLearningObjectives" 
                 data-field="learning_objectives" 
                 data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
                 data-editor-type="objectives"
                 data-content="<?php echo esc_attr(get_post_meta($curriculum_id, '_learning_objectives', true) ?: ''); ?>">
            </div>
            
            <div class="ccb-objectives-helper">
                <h4>Bloom's Taxonomy Reference:</h4>
                <div class="ccb-blooms-levels">
                    <span class="ccb-bloom-level" data-level="remember">Remember</span>
                    <span class="ccb-bloom-level" data-level="understand">Understand</span>
                    <span class="ccb-bloom-level" data-level="apply">Apply</span>
                    <span class="ccb-bloom-level" data-level="analyze">Analyze</span>
                    <span class="ccb-bloom-level" data-level="evaluate">Evaluate</span>
                    <span class="ccb-bloom-level" data-level="create">Create</span>
                </div>
            </div>
        </div>

        <!-- Assessment Strategy Section -->
        <div class="ccb-content-section" id="ccbAssessmentSection">
            <div class="ccb-section-header">
                <h2 class="ccb-section-title">Assessment Strategy</h2>
                <div class="ccb-section-actions">
                    <button class="ccb-action-btn" title="Add Assessment" data-action="add-assessment">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="ccb-action-btn" title="Generate Rubric" data-action="generate-rubric">
                        <i class="fas fa-table"></i>
                    </button>
                </div>
            </div>
            
            <div class="ccb-editor-container" 
                 id="ccbAssessmentStrategy" 
                 data-field="assessment_strategy" 
                 data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>"
                 data-editor-type="assessment"
                 data-content="<?php echo esc_attr(get_post_meta($curriculum_id, '_assessment_strategy', true) ?: ''); ?>">
            </div>
        </div>

        <!-- Resources & Materials Section -->
        <div class="ccb-content-section" id="ccbResourcesSection">
            <div class="ccb-section-header">
                <h2 class="ccb-section-title">Resources & Materials</h2>
                <div class="ccb-section-actions">
                    <button class="ccb-action-btn" title="Add Resource" data-action="add-resource">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="ccb-action-btn" title="Upload File" data-action="upload-file">
                        <i class="fas fa-upload"></i>
                    </button>
                </div>
            </div>
            
            <div class="ccb-resources-grid">
                <div class="ccb-resource-category">
                    <h4>Required Textbooks</h4>
                    <div class="ccb-resource-list">
                        <div class="ccb-resource-item">
                            <i class="fas fa-book"></i>
                            <div class="ccb-resource-info">
                                <h5>Educational Psychology: Theory and Practice</h5>
                                <p>Slavin, R. E. (2019). Pearson Education.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="ccb-resource-category">
                    <h4>Online Resources</h4>
                    <div class="ccb-resource-list">
                        <div class="ccb-resource-item">
                            <i class="fas fa-globe"></i>
                            <div class="ccb-resource-info">
                                <h5>Khan Academy</h5>
                                <p>Free online courses and practice exercises</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Content Editor Styles */
.ccb-content-editor {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    min-height: 200px;
    padding: var(--ccb-spacing-lg);
    color: var(--ccb-text-primary);
    font-family: inherit;
    font-size: 14px;
    line-height: 1.6;
    outline: none;
}

.ccb-content-editor:focus {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

/* Toolbar */
.ccb-toolbar {
    display: flex;
    gap: var(--ccb-spacing-sm);
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-bottom: none;
    border-radius: var(--ccb-border-radius) var(--ccb-border-radius) 0 0;
    margin-bottom: -1px;
}

.ccb-toolbar-btn {
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
    transition: all var(--ccb-transition);
}

.ccb-toolbar-btn:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-text-primary);
}

.ccb-toolbar-btn.active {
    background: var(--ccb-primary-gold);
    color: white;
}

.ccb-toolbar-separator {
    width: 1px;
    background: var(--ccb-border-color);
    margin: 0 var(--ccb-spacing-xs);
}

/* Courses Container */
.ccb-courses-container {
    min-height: 200px;
}

/* Empty State */
.ccb-empty-state {
    text-align: center;
    padding: var(--ccb-spacing-2xl) var(--ccb-spacing-lg);
    background: var(--ccb-bg-elevated);
    border-radius: var(--ccb-border-radius-lg);
    border: 2px dashed var(--ccb-border-color);
}

.ccb-empty-icon {
    font-size: 48px;
    color: var(--ccb-text-muted);
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-empty-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--ccb-text-primary);
    margin: 0 0 var(--ccb-spacing-sm) 0;
}

.ccb-empty-description {
    color: var(--ccb-text-muted);
    margin: 0 0 var(--ccb-spacing-lg) 0;
}

.ccb-empty-actions {
    display: flex;
    gap: var(--ccb-spacing-md);
    justify-content: center;
}

/* Drop Zone */
.ccb-drop-zone {
    border: 2px dashed var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-2xl);
    text-align: center;
    transition: all var(--ccb-transition);
    margin-top: var(--ccb-spacing-lg);
    cursor: pointer;
}

.ccb-drop-zone:hover,
.ccb-drop-zone.dragover {
    border-color: var(--ccb-primary-gold);
    background: rgba(228, 178, 111, 0.1);
}

.ccb-drop-zone-icon {
    font-size: 48px;
    color: var(--ccb-text-muted);
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-drop-zone-text {
    color: var(--ccb-text-muted);
    font-size: 16px;
}

/* Bloom's Taxonomy Helper */
.ccb-objectives-helper {
    background: var(--ccb-bg-elevated);
    padding: var(--ccb-spacing-md);
    border-radius: var(--ccb-border-radius);
    margin-top: var(--ccb-spacing-lg);
}

.ccb-objectives-helper h4 {
    margin: 0 0 var(--ccb-spacing-sm) 0;
    color: var(--ccb-text-primary);
    font-size: 14px;
}

.ccb-blooms-levels {
    display: flex;
    flex-wrap: wrap;
    gap: var(--ccb-spacing-sm);
}

.ccb-bloom-level {
    background: var(--ccb-gradient-secondary);
    color: white;
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--ccb-transition);
}

.ccb-bloom-level:hover {
    transform: translateY(-1px);
    box-shadow: var(--ccb-shadow-sm);
}

/* Resources Grid */
.ccb-resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--ccb-spacing-lg);
}

.ccb-resource-category h4 {
    margin: 0 0 var(--ccb-spacing-md) 0;
    color: var(--ccb-text-primary);
    font-size: 16px;
    font-weight: 600;
}

.ccb-resource-list {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-sm);
}

.ccb-resource-item {
    display: flex;
    align-items: flex-start;
    gap: var(--ccb-spacing-md);
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-elevated);
    border-radius: var(--ccb-border-radius);
    transition: all var(--ccb-transition);
}

.ccb-resource-item:hover {
    background: var(--ccb-hover-bg);
}

.ccb-resource-item i {
    color: var(--ccb-primary-gold);
    font-size: 18px;
    margin-top: 2px;
}

.ccb-resource-info h5 {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-primary);
    font-size: 14px;
    font-weight: 600;
}

.ccb-resource-info p {
    margin: 0;
    color: var(--ccb-text-muted);
    font-size: 12px;
}

/* Curriculum Fields Styles */
.ccb-curriculum-fields {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--ccb-spacing-md);
    margin-bottom: var(--ccb-spacing-lg);
}

.ccb-field-group {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-sm);
}

.ccb-field-label {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
    color: var(--ccb-text-primary);
    font-size: 14px;
    font-weight: 600;
}

.ccb-field-label i {
    color: var(--ccb-primary-gold);
    width: 16px;
    text-align: center;
}

.ccb-field-input,
.ccb-field-textarea,
.ccb-field-select {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    color: var(--ccb-text-primary);
    font-size: 14px;
    line-height: 1.5;
    outline: none;
    transition: all var(--ccb-transition);
    font-family: inherit;
}

.ccb-field-input:focus,
.ccb-field-textarea:focus,
.ccb-field-select:focus {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

.ccb-field-textarea {
    resize: vertical;
    min-height: 80px;
}

.ccb-field-select {
    cursor: pointer;
}

.ccb-field-input::placeholder,
.ccb-field-textarea::placeholder {
    color: var(--ccb-text-muted);
    font-style: italic;
}

/* Notification System */
.ccb-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    max-width: 400px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    animation: ccbSlideIn 0.3s ease-out;
}

@keyframes ccbSlideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.ccb-notification-content {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
    flex: 1;
}

.ccb-notification-content i {
    font-size: 18px;
}

.ccb-notification-success {
    border-left: 4px solid var(--ccb-success);
}

.ccb-notification-success .ccb-notification-content i {
    color: var(--ccb-success);
}

.ccb-notification-error {
    border-left: 4px solid var(--ccb-error);
}

.ccb-notification-error .ccb-notification-content i {
    color: var(--ccb-error);
}

.ccb-notification-info {
    border-left: 4px solid var(--ccb-primary-gold);
}

.ccb-notification-info .ccb-notification-content i {
    color: var(--ccb-primary-gold);
}

.ccb-notification-close {
    background: none;
    border: none;
    color: var(--ccb-text-muted);
    cursor: pointer;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
    margin-left: var(--ccb-spacing-sm);
}

.ccb-notification-close:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-text-primary);
}

/* Save Indicators */
.ccb-save-indicator {
    display: inline-block;
    padding: 2px 6px;
    border-radius: var(--ccb-border-radius-sm);
    font-size: 10px;
    font-weight: 500;
    margin-left: var(--ccb-spacing-xs);
    transition: all var(--ccb-transition);
}

.ccb-save-indicator.ccb-save-saving {
    background: rgba(255, 193, 7, 0.2);
    color: var(--ccb-warning);
}

.ccb-save-indicator.ccb-save-saved {
    background: rgba(40, 167, 69, 0.2);
    color: var(--ccb-success);
}

.ccb-save-indicator.ccb-save-error {
    background: rgba(220, 53, 69, 0.2);
    color: var(--ccb-error);
}

/* Responsive Design */
@media (max-width: 768px) {
    .ccb-toolbar {
        flex-wrap: wrap;
    }
    
    .ccb-empty-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .ccb-resources-grid {
        grid-template-columns: 1fr;
    }
    
    .ccb-curriculum-fields {
        grid-template-columns: 1fr;
        gap: var(--ccb-spacing-sm);
    }
    
    .ccb-field-label {
        font-size: 13px;
    }
    
    .ccb-field-input,
    .ccb-field-textarea,
    .ccb-field-select {
        font-size: 13px;
        padding: var(--ccb-spacing-sm);
    }
    
    /* Editor.js containers in mobile */
    .ccb-editor-container {
        min-height: 80px;
    }
    
    .ccb-editor-container .codex-editor__redactor {
        padding: var(--ccb-spacing-sm);
        font-size: 13px;
    }

    /* Mobile notifications */
    .ccb-notification {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>