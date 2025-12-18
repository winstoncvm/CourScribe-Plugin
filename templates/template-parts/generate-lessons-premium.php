<?php
/**
 * Premium Lesson Generation Template with Teaching Points
 * Enhanced lesson generation with AI-powered teaching points
 * 
 * @since 1.1.9
 */

if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_premium_lesson_generator($args = []) {
    // Get context data
    $module_id = $args['module_id'] ?? 0;
    $course_id = $args['course_id'] ?? 0;
    $curriculum_id = $args['curriculum_id'] ?? 0;
    $studio_id = $args['studio_id'] ?? 0;

// Get module/course information for context
$parent_post = null;
$parent_title = '';
$parent_goal = '';
$parent_type = '';

if ($module_id) {
    $parent_post = get_post($module_id);
    $parent_type = 'module';
    if ($parent_post) {
        $parent_title = $parent_post->post_title;
        $parent_goal = get_post_meta($module_id, '_module_goal', true);
    }
} elseif ($course_id) {
    $parent_post = get_post($course_id);
    $parent_type = 'course';
    if ($parent_post) {
        $parent_title = $parent_post->post_title;
        $parent_goal = get_post_meta($course_id, '_class_goal', true);
    }
}

// Check tier limitations
$tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
$tier_limits = [
    'basics' => ['lessons' => 10, 'ai_calls' => 10],
    'plus' => ['lessons' => 25, 'ai_calls' => 50],
    'pro' => ['lessons' => -1, 'ai_calls' => -1]
];

$max_lessons = $tier_limits[$tier]['lessons'];
$ai_remaining = courscribe_get_remaining_ai_usage($studio_id);
?>

<!-- Premium Lesson Generation Modal -->
<div class="modal fade" id="generateLessonsModal" tabindex="-1" aria-labelledby="generateLessonsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content cs-modal-dark">
            <div class="modal-header cs-modal-header">
                <div class="cs-modal-title-container">
                    <div class="cs-modal-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <h1 class="modal-title fs-4" id="generateLessonsModalLabel">
                            AI Lesson Generator
                            <span class="cs-premium-badge">PREMIUM</span>
                        </h1>
                        <p class="cs-modal-subtitle mb-0">
                            Create comprehensive lessons with teaching points for: <strong><?php echo esc_html($parent_title); ?></strong>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close cs-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body cs-modal-body p-0">
                <!-- Context Banner -->
                <div class="cs-context-banner">
                    <div class="cs-context-content">
                        <div class="cs-context-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="cs-context-text">
                            <h6>Teaching Points Included</h6>
                            <p>AI will generate lessons with comprehensive teaching points and learning activities</p>
                        </div>
                    </div>
                    <div class="cs-context-stats">
                        <div class="cs-stat-item">
                            <span class="cs-stat-label">Tier:</span>
                            <span class="cs-stat-value cs-tier-<?php echo esc_attr($tier); ?>"><?php echo esc_html(ucfirst($tier)); ?></span>
                        </div>
                        <?php if ($max_lessons > 0): ?>
                        <div class="cs-stat-item">
                            <span class="cs-stat-label">Max lessons:</span>
                            <span class="cs-stat-value"><?php echo $max_lessons; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="cs-stat-item">
                            <span class="cs-stat-label">AI calls remaining:</span>
                            <span class="cs-stat-value"><?php echo $ai_remaining === -1 ? '∞' : $ai_remaining; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Teaching Points Feature Banner -->
                <div class="cs-feature-banner cs-teaching-points-banner">
                    <div class="cs-feature-content">
                        <div class="cs-feature-icon">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div class="cs-feature-text">
                            <h6>Auto-Generated Teaching Points</h6>
                            <p>Each lesson includes detailed teaching points with clear explanations and examples</p>
                        </div>
                    </div>
                    <div class="cs-feature-highlights">
                        <span class="cs-feature-tag"><i class="fas fa-check-circle me-1"></i>Structured Content</span>
                        <span class="cs-feature-tag"><i class="fas fa-check-circle me-1"></i>Clear Examples</span>
                        <span class="cs-feature-tag"><i class="fas fa-check-circle me-1"></i>Learning Activities</span>
                    </div>
                </div>

                <!-- Quick Generation Section -->
                <div class="cs-quick-generation-section">
                    <div class="cs-quick-content">
                        <h6><i class="fas fa-bolt me-2"></i>Quick Generate</h6>
                        <p>Generate lessons instantly with smart defaults and teaching points</p>
                        <div class="cs-quick-options">
                            <select class="cs-quick-count" id="csQuickLessonCount">
                                <?php for ($i = 1; $i <= ($max_lessons > 0 ? min(5, $max_lessons) : 5); $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Lesson<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="button" class="cs-quick-generate-btn" id="csQuickGenerateLessons">
                                <i class="fas fa-magic me-2"></i>Quick Generate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Generation Wizard Container -->
                <div id="csLessonGenerationWizard">
                    <?php 
                    courscribe_render_generation_wizard([
                        'type' => 'lesson',
                        'parent_id' => $module_id ?: $course_id,
                        'parent_title' => $parent_title,
                        'parent_goal' => $parent_goal,
                        'parent_type' => $parent_type,
                        'course_id' => $course_id,
                        'curriculum_id' => $curriculum_id,
                        'studio_id' => $studio_id,
                        'tier' => $tier,
                        'max_count' => $max_lessons,
                        'wizard_id' => 'lessonWizard',
                        'content_label' => 'lessons',
                        'ai_remaining' => $ai_remaining,
                        'specific_fields' => [
                            'duration_options' => [
                                '15-minutes' => '15 minutes',
                                '30-minutes' => '30 minutes',
                                '45-minutes' => '45 minutes',
                                '60-minutes' => '1 hour',
                                '90-minutes' => '1.5 hours'
                            ],
                            'lesson_types' => [
                                'introduction' => 'Introduction Lesson',
                                'concept' => 'Concept Learning',
                                'practical' => 'Practical Application',
                                'assessment' => 'Assessment & Review',
                                'activity' => 'Interactive Activity',
                                'discussion' => 'Discussion-Based',
                                'demonstration' => 'Demonstration/Tutorial'
                            ],
                            'teaching_points_focus' => [
                                'key_concepts' => 'Key Concepts & Definitions',
                                'step_by_step' => 'Step-by-step Procedures',
                                'real_examples' => 'Real-world Examples',
                                'common_mistakes' => 'Common Mistakes to Avoid',
                                'practice_activities' => 'Practice Activities',
                                'assessment_questions' => 'Assessment Questions'
                            ],
                            'include_teaching_points' => true,
                            'teaching_points_note' => 'Each lesson will include 3-5 detailed teaching points with explanations and examples.'
                        ]
                    ]); 
                    ?>
                </div>

                <!-- Content Preview Container -->
                <div id="csLessonPreviewContainer" style="display: none;">
                    <?php 
                    courscribe_render_content_preview([
                        'type' => 'lesson',
                        'content_label' => 'lessons',
                        'preview_id' => 'lessonPreview',
                        'singular_label' => 'Lesson',
                        'plural_label' => 'Lessons',
                        'include_teaching_points' => true
                    ]); 
                    ?>
                </div>
            </div>
            
            <div class="modal-footer cs-modal-footer">
                <div class="cs-footer-info">
                    <span class="cs-tier-badge cs-tier-<?php echo esc_attr($tier); ?>">
                        <?php echo esc_html(ucfirst($tier)); ?> Plan
                    </span>
                    <span class="cs-usage-indicator" id="csLessonUsageIndicator">
                        AI Usage: <span id="csLessonUsageCount"><?php echo $ai_remaining === -1 ? '∞' : $ai_remaining; ?></span> remaining
                    </span>
                </div>
                <div class="cs-footer-actions">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn cs-btn-primary" id="csLessonWizardAction" disabled>
                        <i class="fas fa-magic me-2"></i>Start Generation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Lesson Generation Specific Styles */
.cs-teaching-points-banner {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(23, 162, 184, 0.15) 100%);
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.cs-teaching-points-banner .cs-feature-icon {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.cs-feature-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem;
    margin: 1rem 0;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid #555;
}

.cs-feature-content {
    display: flex;
    align-items: center;
    flex: 1;
}

.cs-feature-icon {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    margin-right: 1rem;
    font-size: 1.1rem;
}

.cs-feature-text h6 {
    color: #fff;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.cs-feature-text p {
    color: #ccc;
    margin-bottom: 0;
    font-size: 0.9rem;
}

.cs-feature-highlights {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.cs-feature-tag {
    font-size: 0.8rem;
    color: #28a745;
    white-space: nowrap;
}

.cs-lesson-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.cs-lesson-type-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 0.5rem;
    border: 1px solid #555;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cs-lesson-type-item:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: #E4B26F;
}

.cs-lesson-type-item input[type="radio"] {
    margin-right: 0.75rem;
}

.cs-lesson-type-item.selected {
    background: rgba(228, 178, 111, 0.2);
    border-color: #E4B26F;
}

.cs-teaching-points-focus-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.cs-teaching-points-note {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 0.5rem;
    padding: 1rem;
    margin: 1rem 0;
    color: #28a745;
    font-size: 0.9rem;
}

.cs-teaching-points-note i {
    color: #28a745;
    margin-right: 0.5rem;
}

/* Teaching Points Preview Styling */
.cs-teaching-points-list {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid #444;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
}

.cs-teaching-point-item {
    display: flex;
    align-items: flex-start;
    padding: 0.75rem;
    border-bottom: 1px solid #333;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.cs-teaching-point-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.cs-teaching-point-number {
    background: #E4B26F;
    color: #000;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.cs-teaching-point-content h6 {
    color: #fff;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.cs-teaching-point-content p {
    color: #ccc;
    margin-bottom: 0;
    font-size: 0.85rem;
    line-height: 1.4;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize lesson generation wizard
    if (typeof CourScribePremiumGenerator !== 'undefined') {
        window.csLessonGenerator = new CourScribePremiumGenerator({
            type: 'lesson',
            modalId: 'generateLessonsModal',
            wizardId: 'lessonWizard',
            previewId: 'lessonPreview',
            parentId: <?php echo $module_id ?: $course_id; ?>,
            parentType: '<?php echo $parent_type; ?>',
            courseId: <?php echo $course_id; ?>,
            curriculumId: <?php echo $curriculum_id; ?>,
            studioId: <?php echo $studio_id; ?>,
            tier: '<?php echo $tier; ?>',
            maxCount: <?php echo $max_lessons > 0 ? $max_lessons : 999; ?>,
            aiRemaining: <?php echo $ai_remaining === -1 ? -1 : $ai_remaining; ?>,
            includeTeachingPoints: true
        });
    }

    // Quick generation handler
    document.getElementById('csQuickGenerateLessons')?.addEventListener('click', function() {
        const count = document.getElementById('csQuickLessonCount').value;
        if (window.csLessonGenerator) {
            window.csLessonGenerator.quickGenerate(parseInt(count));
        }
    });

    // Lesson type selection handlers
    document.querySelectorAll('.cs-lesson-type-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.cs-lesson-type-item').forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
});
</script>
<?php
}