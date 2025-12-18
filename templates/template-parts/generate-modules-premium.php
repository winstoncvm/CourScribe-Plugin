<?php
/**
 * Premium Module Generation Template
 * Enhanced module generation with advanced AI features and comprehensive preview
 * 
 * @since 1.1.9
 */

if (!defined('ABSPATH')) {
    exit;
}

function courscribe_render_premium_module_generator($args = []) {
    // Get context data
    $course_id = $args['course_id'] ?? 0;
    $curriculum_id = $args['curriculum_id'] ?? 0;
    $studio_id = $args['studio_id'] ?? 0;

// Get course information for context
$course = null;
$course_title = '';
$course_goal = '';
if ($course_id) {
    $course = get_post($course_id);
    if ($course) {
        $course_title = $course->post_title;
        $course_goal = get_post_meta($course_id, '_class_goal', true);
    }
}

// Check tier limitations
$tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
$tier_limits = [
    'basics' => ['modules' => 3, 'ai_calls' => 10],
    'plus' => ['modules' => 6, 'ai_calls' => 50],
    'pro' => ['modules' => -1, 'ai_calls' => -1]
];

$max_modules = $tier_limits[$tier]['modules'];
$ai_remaining = courscribe_get_remaining_ai_usage($studio_id);
?>

<!-- Premium Module Generation Modal -->
<div class="modal fade" id="courseGenerateModulesModal" tabindex="-1" aria-labelledby="courseGenerateModulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content cs-modal-dark">
            <div class="modal-header cs-modal-header">
                <div class="cs-modal-title-container">
                    <div class="cs-modal-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h1 class="modal-title fs-4" id="courseGenerateModulesModalLabel">
                            AI Module Generator
                            <span class="cs-premium-badge">PREMIUM</span>
                        </h1>
                        <p class="cs-modal-subtitle mb-0">
                            Create intelligent learning modules for: <strong><?php echo esc_html($course_title); ?></strong>
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
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="cs-context-text">
                            <h6>Smart Context Detection</h6>
                            <p>AI will generate modules based on your course goal and existing content structure</p>
                        </div>
                    </div>
                    <div class="cs-context-stats">
                        <div class="cs-stat-item">
                            <span class="cs-stat-label">Tier:</span>
                            <span class="cs-stat-value cs-tier-<?php echo esc_attr($tier); ?>"><?php echo esc_html(ucfirst($tier)); ?></span>
                        </div>
                        <?php if ($max_modules > 0): ?>
                        <div class="cs-stat-item">
                            <span class="cs-stat-label">Max modules:</span>
                            <span class="cs-stat-value"><?php echo $max_modules; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="cs-stat-item">
                            <span class="cs-stat-label">AI calls remaining:</span>
                            <span class="cs-stat-value"><?php echo $ai_remaining === -1 ? '∞' : $ai_remaining; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Generation Section -->
                <div class="cs-quick-generation-section">
                    <div class="cs-quick-content">
                        <h6><i class="fas fa-bolt me-2"></i>Quick Generate</h6>
                        <p>Generate modules instantly with smart defaults based on your course</p>
                        <div class="cs-quick-options">
                            <select class="cs-quick-count" id="csQuickModuleCount">
                                <?php for ($i = 1; $i <= ($max_modules > 0 ? min(3, $max_modules) : 3); $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Module<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="button" class="cs-quick-generate-btn" id="csQuickGenerateModules">
                                <i class="fas fa-magic me-2"></i>Quick Generate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Generation Wizard Container -->
                <div id="csModuleGenerationWizard">
                    <?php 
                    courscribe_render_generation_wizard([
                        'type' => 'module',
                        'parent_id' => $course_id,
                        'parent_title' => $course_title,
                        'parent_goal' => $course_goal,
                        'curriculum_id' => $curriculum_id,
                        'studio_id' => $studio_id,
                        'tier' => $tier,
                        'max_count' => $max_modules,
                        'wizard_id' => 'moduleWizard',
                        'content_label' => 'modules',
                        'ai_remaining' => $ai_remaining,
                        'specific_fields' => [
                            'duration_options' => [
                                '30-minutes' => '30 minutes',
                                '45-minutes' => '45 minutes',
                                '1-hour' => '1 hour',
                                '90-minutes' => '1.5 hours',
                                '2-hours' => '2 hours'
                            ],
                            'focus_areas' => [
                                'concepts' => 'Key Concepts',
                                'skills' => 'Practical Skills',
                                'applications' => 'Real-world Applications',
                                'assessments' => 'Knowledge Checks',
                                'activities' => 'Interactive Activities'
                            ],
                            'exclude_note' => 'Note: Generated modules will not include methods, materials, or media - you can add these later.'
                        ]
                    ]); 
                    ?>
                </div>

                <!-- Content Preview Container -->
                <div id="csModulePreviewContainer" style="display: none;">
                    <?php 
                    courscribe_render_content_preview([
                        'type' => 'module',
                        'content_label' => 'modules',
                        'preview_id' => 'modulePreview',
                        'singular_label' => 'Module',
                        'plural_label' => 'Modules'
                    ]); 
                    ?>
                </div>
            </div>
            
            <div class="modal-footer cs-modal-footer">
                <div class="cs-footer-info">
                    <span class="cs-tier-badge cs-tier-<?php echo esc_attr($tier); ?>">
                        <?php echo esc_html(ucfirst($tier)); ?> Plan
                    </span>
                    <span class="cs-usage-indicator" id="csModuleUsageIndicator">
                        AI Usage: <span id="csModuleUsageCount"><?php echo $ai_remaining === -1 ? '∞' : $ai_remaining; ?></span> remaining
                    </span>
                </div>
                <div class="cs-footer-actions">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn cs-btn-primary" id="csModuleWizardAction" disabled>
                        <i class="fas fa-magic me-2"></i>Start Generation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include plugin_dir_path(__FILE__) . '../../template-parts/components/generation-wizard-base.php'; ?>
<?php include plugin_dir_path(__FILE__) . '../../template-parts/components/content-preview-editor.php'; ?>

<style>
/* Module Generation Specific Styles */
.cs-tier-basics { color: #28a745; }
.cs-tier-plus { color: #ffc107; }
.cs-tier-pro { color: #17a2b8; }

.cs-exclude-note {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid #ffc107;
    border-radius: 0.5rem;
    padding: 1rem;
    margin: 1rem 0;
    color: #ffc107;
    font-size: 0.9rem;
}

.cs-exclude-note i {
    color: #ffc107;
    margin-right: 0.5rem;
}

/* Module specific form styling */
.cs-module-duration-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.cs-focus-areas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.cs-focus-area-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 0.5rem;
    border: 1px solid #555;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cs-focus-area-item:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: #E4B26F;
}

.cs-focus-area-item input[type="checkbox"] {
    margin-right: 0.5rem;
}

.cs-focus-area-item.selected {
    background: rgba(228, 178, 111, 0.2);
    border-color: #E4B26F;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize module generation wizard
    if (typeof CourScribePremiumGenerator !== 'undefined') {
        window.csModuleGenerator = new CourScribePremiumGenerator({
            type: 'module',
            modalId: 'courseGenerateModulesModal',
            wizardId: 'moduleWizard',
            previewId: 'modulePreview',
            parentId: <?php echo $course_id; ?>,
            parentType: 'course',
            curriculumId: <?php echo $curriculum_id; ?>,
            studioId: <?php echo $studio_id; ?>,
            tier: '<?php echo $tier; ?>',
            maxCount: <?php echo $max_modules > 0 ? $max_modules : 999; ?>,
            aiRemaining: <?php echo $ai_remaining === -1 ? -1 : $ai_remaining; ?>
        });
    }

    // Quick generation handler
    document.getElementById('csQuickGenerateModules')?.addEventListener('click', function() {
        const count = document.getElementById('csQuickModuleCount').value;
        if (window.csModuleGenerator) {
            window.csModuleGenerator.quickGenerate(parseInt(count));
        }
    });
});
</script>
<?php
}