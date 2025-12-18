<?php
// courscribe/templates/template-parts/generate-courses-premium.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Course Generation Interface
 * 
 * A comprehensive, wizard-based course generation system with AI integration,
 * real-time preview, customization options, and advanced user controls.
 *
 * Features:
 * - 4-step wizard interface
 * - Advanced AI configuration options
 * - Real-time content preview and editing
 * - Bulk operations and quality control
 * - Template system integration
 * - Comprehensive validation
 * - Mobile-responsive design
 *
 * @param array $args {
 *     @type int    $curriculum_id     Curriculum ID
 *     @type string $curriculum_title  Curriculum title
 *     @type string $curriculum_topic  Curriculum topic
 *     @type string $curriculum_goal   Curriculum goal
 *     @type object $tooltips          Tooltips instance
 *     @type string $site_url          Site URL
 * }
 */

function courscribe_render_premium_course_generator($args = []) {
    $defaults = [
        'curriculum_id' => 0,
        'curriculum_title' => '',
        'curriculum_topic' => '',
        'curriculum_goal' => '',
        'tooltips' => null,
        'site_url' => home_url(),
    ];

    $args = wp_parse_args($args, $defaults);
    extract($args);

    // Get user information and permissions
    $current_user = wp_get_current_user();
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
    
    // Tier-based limits
    $tier_limits = [
        'basics' => ['max_courses' => 1, 'ai_generations' => 10],
        'plus' => ['max_courses' => 2, 'ai_generations' => 50],
        'pro' => ['max_courses' => -1, 'ai_generations' => -1]
    ];
    
    $current_limit = $tier_limits[$tier];
    
    // Course templates (can be loaded from database or defined here)
    $course_templates = [
        [
            'id' => 'business-basics',
            'name' => 'Business Fundamentals',
            'description' => 'Essential business concepts and practices',
            'icon' => 'fas fa-briefcase',
            'topics' => ['Strategy', 'Operations', 'Marketing', 'Finance'],
            'difficulty' => 'intermediate'
        ],
        [
            'id' => 'technical-training',
            'name' => 'Technical Skills',
            'description' => 'Hands-on technical training structure',
            'icon' => 'fas fa-code',
            'topics' => ['Theory', 'Practice', 'Projects', 'Assessment'],
            'difficulty' => 'advanced'
        ],
        [
            'id' => 'soft-skills',
            'name' => 'Soft Skills Development',
            'description' => 'Communication and leadership skills',
            'icon' => 'fas fa-users',
            'topics' => ['Communication', 'Leadership', 'Teamwork', 'Problem Solving'],
            'difficulty' => 'beginner'
        ],
        [
            'id' => 'compliance-training',
            'name' => 'Compliance & Safety',
            'description' => 'Regulatory and safety training',
            'icon' => 'fas fa-shield-alt',
            'topics' => ['Regulations', 'Procedures', 'Best Practices', 'Assessment'],
            'difficulty' => 'intermediate'
        ]
    ];

    // Include required components
    require_once COURSCRIBE_PLUGIN_PATH . 'templates/template-parts/components/generation-wizard-base.php';
    require_once COURSCRIBE_PLUGIN_PATH . 'templates/template-parts/components/content-preview-editor.php';
    ?>

    <!-- Premium Course Generation Modal -->
    <div class="modal fade cs-generation-modal" id="cs-course-generation-modal" tabindex="-1" aria-labelledby="cs-course-generation-title" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down">
            <div class="modal-content cs-modal-content">
                <!-- Modal Header -->
                <div class="modal-header cs-modal-header">
                    <div class="cs-modal-title-container">
                        <h4 class="modal-title" id="cs-course-generation-title">
                            <div class="cs-title-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="cs-title-text">
                                <span class="cs-main-title">AI Course Generator</span>
                                <span class="cs-sub-title">Create courses for "<?php echo esc_html($curriculum_title); ?>"</span>
                            </div>
                        </h4>
                        <div class="cs-tier-badge">
                            <span class="cs-tier-label"><?php echo ucfirst($tier); ?> Plan</span>
                            <span class="cs-tier-limit">
                                <?php 
                                if ($current_limit['max_courses'] === -1) {
                                    echo 'Unlimited courses';
                                } else {
                                    echo $current_limit['max_courses'] . ' course' . ($current_limit['max_courses'] > 1 ? 's' : '') . ' max';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="cs-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body cs-modal-body">
                    <!-- Context Information -->
                    <div class="cs-context-banner">
                        <div class="cs-context-info">
                            <div class="cs-context-item">
                                <strong>Curriculum:</strong> <?php echo esc_html($curriculum_title); ?>
                            </div>
                            <?php if ($curriculum_topic): ?>
                            <div class="cs-context-item">
                                <strong>Topic:</strong> <?php echo esc_html($curriculum_topic); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($curriculum_goal): ?>
                            <div class="cs-context-item">
                                <strong>Goal:</strong> <?php echo esc_html($curriculum_goal); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="cs-ai-usage">
                            <div class="cs-usage-indicator">
                                <i class="fas fa-robot"></i>
                                <span>AI Credits: </span>
                                <strong id="cs-ai-credits-remaining">
                                    <?php 
                                    if ($current_limit['ai_generations'] === -1) {
                                        echo 'Unlimited';
                                    } else {
                                        // You would fetch actual usage from database
                                        echo $current_limit['ai_generations'] . ' remaining';
                                    }
                                    ?>
                                </strong>
                            </div>
                        </div>
                    </div>

                    <!-- Generation Wizard -->
                    <?php
                    courscribe_render_generation_wizard([
                        'type' => 'course',
                        'title' => 'Generate Courses with AI',
                        'parent_id' => $curriculum_id,
                        'templates' => $course_templates,
                        'tooltips' => $tooltips,
                        'nonce_action' => 'courscribe_generate_courses_nonce',
                        'max_count' => $current_limit['max_courses'] === -1 ? 5 : $current_limit['max_courses'],
                        'default_count' => 1
                    ]);
                    ?>

                    <!-- Advanced Settings Panel (Collapsible) -->
                    <div class="cs-advanced-panel" id="cs-advanced-settings">
                        <div class="cs-panel-header">
                            <button type="button" class="cs-panel-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#cs-advanced-content" aria-expanded="false">
                                <i class="fas fa-cogs me-2"></i>
                                Advanced Settings
                                <i class="fas fa-chevron-down cs-toggle-icon"></i>
                            </button>
                        </div>
                        <div class="collapse" id="cs-advanced-content">
                            <div class="cs-panel-content">
                                <div class="row">
                                    <!-- AI Model Selection -->
                                    <div class="col-md-6">
                                        <div class="cs-setting-group">
                                            <label class="cs-setting-label" for="cs-ai-model">
                                                <i class="fas fa-brain me-2"></i>
                                                AI Model
                                            </label>
                                            <select id="cs-ai-model" class="cs-premium-select">
                                                <option value="gemini-pro" selected>Gemini Pro (Recommended)</option>
                                                <option value="gemini-standard">Gemini Standard</option>
                                                <option value="gpt-4">GPT-4 (Premium)</option>
                                                <option value="claude-3">Claude 3 (Premium)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Creativity Level -->
                                    <div class="col-md-6">
                                        <div class="cs-setting-group">
                                            <label class="cs-setting-label" for="cs-creativity">
                                                <i class="fas fa-palette me-2"></i>
                                                Creativity Level
                                            </label>
                                            <div class="cs-slider-container">
                                                <input type="range" 
                                                       id="cs-creativity" 
                                                       class="cs-range-slider"
                                                       min="0" 
                                                       max="100" 
                                                       value="70">
                                                <div class="cs-slider-labels">
                                                    <span>Conservative</span>
                                                    <span>Balanced</span>
                                                    <span>Creative</span>
                                                    <span>Innovative</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Content Complexity -->
                                    <div class="col-md-12 mb-2 mt-2">
                                        <div class="cs-setting-group">
                                            <label class="cs-setting-label" for="cs-complexity">
                                                <i class="fas fa-layer-group me-2"></i>
                                                Content Complexity
                                            </label>
                                            <div class="cs-complexity-selector">
                                                <div class="cs-complexity-option active" data-level="1">
                                                    <div class="cs-complexity-bars">
                                                        <div class="cs-bar active"></div>
                                                        <div class="cs-bar"></div>
                                                        <div class="cs-bar"></div>
                                                    </div>
                                                    <span>Simple</span>
                                                </div>
                                                <div class="cs-complexity-option" data-level="2">
                                                    <div class="cs-complexity-bars">
                                                        <div class="cs-bar active"></div>
                                                        <div class="cs-bar active"></div>
                                                        <div class="cs-bar"></div>
                                                    </div>
                                                    <span>Moderate</span>
                                                </div>
                                                <div class="cs-complexity-option" data-level="3">
                                                    <div class="cs-complexity-bars">
                                                        <div class="cs-bar active"></div>
                                                        <div class="cs-bar active"></div>
                                                        <div class="cs-bar active"></div>
                                                    </div>
                                                    <span>Complex</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <!-- Language & Localization -->
                                    <div class="col-md-6">
                                        <div class="cs-setting-group">
                                            <label class="cs-setting-label" for="cs-language">
                                                <i class="fas fa-globe me-2"></i>
                                                Content Language
                                            </label>
                                            <select id="cs-language" class="cs-premium-select">
                                                <option value="en" selected>English</option>
                                                <option value="es">Spanish</option>
                                                <option value="fr">French</option>
                                                <option value="de">German</option>
                                                <option value="pt">Portuguese</option>
                                                <option value="it">Italian</option>
                                                <option value="ja">Japanese</option>
                                                <option value="ko">Korean</option>
                                                <option value="zh">Chinese</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Industry Focus -->
                                    <div class="col-md-6">
                                        <div class="cs-setting-group">
                                            <label class="cs-setting-label" for="cs-industry">
                                                <i class="fas fa-industry me-2"></i>
                                                Industry Focus
                                            </label>
                                            <select id="cs-industry" class="cs-premium-select">
                                                <option value="">Generic (No Industry Focus)</option>
                                                <option value="technology">Technology</option>
                                                <option value="healthcare">Healthcare</option>
                                                <option value="finance">Finance</option>
                                                <option value="education">Education</option>
                                                <option value="manufacturing">Manufacturing</option>
                                                <option value="retail">Retail</option>
                                                <option value="consulting">Consulting</option>
                                                <option value="nonprofit">Non-profit</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quality Controls -->
                                <div class="cs-quality-controls">
                                    <h6 class="cs-section-subtitle">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Quality Controls
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="cs-toggle-setting">
                                                <input type="checkbox" id="cs-enable-fact-check" checked>
                                                <label for="cs-enable-fact-check" class="cs-toggle-label">
                                                    <span class="cs-toggle-slider"></span>
                                                    <span class="cs-toggle-text">Enable Fact Checking</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="cs-toggle-setting">
                                                <input type="checkbox" id="cs-grammar-check" checked>
                                                <label for="cs-grammar-check" class="cs-toggle-label">
                                                    <span class="cs-toggle-slider"></span>
                                                    <span class="cs-toggle-text">Grammar Optimization</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="cs-toggle-setting">
                                                <input type="checkbox" id="cs-plagiarism-check">
                                                <label for="cs-plagiarism-check" class="cs-toggle-label">
                                                    <span class="cs-toggle-slider"></span>
                                                    <span class="cs-toggle-text">Plagiarism Detection</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer cs-modal-footer">
                    <div class="cs-footer-info">
                        <div class="cs-generation-cost">
                            <i class="fas fa-info-circle me-1"></i>
                            <span>This generation will use <strong id="cs-cost-estimate">1</strong> AI credit(s)</span>
                        </div>
                    </div>
                    <div class="cs-footer-actions">
                        <button type="button" class="cs-btn cs-btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </button>
                        <div class="cs-action-group">
                            <button type="button" class="cs-btn cs-btn-outline cs-save-draft" disabled>
                                <i class="fas fa-save me-2"></i>
                                Save Draft
                            </button>
                            <button type="button" class="cs-btn cs-btn-success cs-start-wizard">
                                <i class="fas fa-magic me-2"></i>
                                Start Generation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Generation Button (Alternative Entry Point) -->
    <div class="cs-quick-generation">
        <button type="button" 
                class="cs-btn cs-btn-ai cs-quick-generate" 
                data-bs-toggle="modal" 
                data-bs-target="#cs-course-generation-modal">
            <div class="cs-btn-content">
                <div class="cs-btn-icon">
                    <i class="fas fa-magic"></i>
                </div>
                <div class="cs-btn-text">
                    <span class="cs-btn-title">Generate Courses with AI</span>
                    <span class="cs-btn-subtitle">Create professional courses in minutes</span>
                </div>
            </div>
            <div class="cs-btn-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>
        
        <!-- Quick Stats -->
        <div class="cs-quick-stats">
            <div class="cs-stat-item">
                <div class="cs-stat-number">2.5k+</div>
                <div class="cs-stat-label">Courses Generated</div>
            </div>
            <div class="cs-stat-item">
                <div class="cs-stat-number">95%</div>
                <div class="cs-stat-label">Satisfaction Rate</div>
            </div>
            <div class="cs-stat-item">
                <div class="cs-stat-number">< 2 min</div>
                <div class="cs-stat-label">Average Generation Time</div>
            </div>
        </div>
    </div>

    <!-- Hidden Data -->
    <script type="application/json" id="cs-curriculum-context">
    {
        "curriculum_id": <?php echo json_encode($curriculum_id); ?>,
        "curriculum_title": <?php echo json_encode($curriculum_title); ?>,
        "curriculum_topic": <?php echo json_encode($curriculum_topic); ?>,
        "curriculum_goal": <?php echo json_encode($curriculum_goal); ?>,
        "tier": <?php echo json_encode($tier); ?>,
        "limits": <?php echo json_encode($current_limit); ?>,
        "templates": <?php echo json_encode($course_templates); ?>,
        "user_id": <?php echo json_encode($current_user->ID); ?>,
        "studio_id": <?php echo json_encode($studio_id); ?>
    }
    </script>

    <?php
}

// Example usage function for integration
function courscribe_display_premium_course_generator() {
    // This would be called from your main curriculum template
    $curriculum_id = get_the_ID();
    $curriculum = get_post($curriculum_id);
    
    if (!$curriculum || $curriculum->post_type !== 'crscribe_curriculum') {
        return;
    }

    $tooltips = new CourScribe_Tooltips();
    
    courscribe_render_premium_course_generator([
        'curriculum_id' => $curriculum_id,
        'curriculum_title' => $curriculum->post_title,
        'curriculum_topic' => get_post_meta($curriculum_id, '_class_topic', true),
        'curriculum_goal' => get_post_meta($curriculum_id, '_class_goal', true),
        'tooltips' => $tooltips,
        'site_url' => home_url()
    ]);
}
?>