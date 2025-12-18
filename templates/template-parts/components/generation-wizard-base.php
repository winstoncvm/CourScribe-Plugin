<?php
// courscribe/templates/template-parts/components/generation-wizard-base.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Generation Wizard Component
 * Reusable component for generating courses, modules, and lessons
 * 
 * @param array $args {
 *     @type string $type              Generation type (course, module, lesson)
 *     @type string $title             Wizard title
 *     @type int    $parent_id         Parent ID (curriculum for courses, course for modules, etc.)
 *     @type array  $templates         Available templates
 *     @type object $tooltips          Tooltips instance
 *     @type string $nonce_action      Nonce action name
 * }
 */

function courscribe_render_generation_wizard($args = []) {
    $defaults = [
        'type' => 'course',
        'title' => 'Generate Content',
        'parent_id' => 0,
        'templates' => [],
        'tooltips' => null,
        'nonce_action' => 'courscribe_generation_nonce',
        'max_count' => 5,
        'default_count' => 2
    ];

    $args = wp_parse_args($args, $defaults);
    extract($args);

    $wizard_id = "cs-generation-wizard-{$type}";
    $current_user = wp_get_current_user();
    ?>

    <div class="cs-generation-wizard" id="<?php echo esc_attr($wizard_id); ?>" data-type="<?php echo esc_attr($type); ?>">
        <!-- Wizard Header -->
        <div class="cs-wizard-header">
            <div class="cs-wizard-title-container">
                <h3 class="cs-wizard-title">
                    <i class="fas fa-magic me-2"></i>
                    <?php echo esc_html($title); ?>
                </h3>
                <div class="cs-wizard-subtitle">
                    AI-powered content generation with full customization control
                </div>
            </div>
            <div class="cs-wizard-progress">
                <div class="cs-progress-bar">
                    <div class="cs-progress-fill" style="width: 0%"></div>
                </div>
                <div class="cs-progress-text">Step 1 of 4</div>
            </div>
        </div>

        <!-- Wizard Steps -->
        <div class="cs-wizard-content">
            <!-- Step 1: Configuration -->
            <div class="cs-wizard-step active" data-step="1" id="<?php echo esc_attr($wizard_id); ?>-step-1">
                <div class="cs-step-header">
                    <div class="cs-step-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="cs-step-info">
                        <h4 class="cs-step-title">Configuration</h4>
                        <p class="cs-step-description">Set up your generation parameters</p>
                    </div>
                </div>

                <div class="cs-step-content">
                    <!-- Basic Settings Grid -->
                    <div class="cs-settings-grid">
                        <!-- Count Selection -->
                        <div class="cs-setting-group">
                            <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-count">
                                <i class="fas fa-list-ol me-2"></i>
                                Number to Generate
                                <span class="cs-required">*</span>
                            </label>
                            <div class="cs-count-selector">
                                <?php for ($i = 1; $i <= $max_count; $i++): ?>
                                <div class="cs-count-option <?php echo $i === $default_count ? 'active' : ''; ?>" data-count="<?php echo $i; ?>">
                                    <div class="cs-count-number"><?php echo $i; ?></div>
                                    <div class="cs-count-label"><?php echo ucfirst($type) . ($i > 1 ? 's' : ''); ?></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="<?php echo esc_attr($wizard_id); ?>-count" value="<?php echo $default_count; ?>">
                        </div>

                        <!-- Difficulty Level -->
                        <div class="cs-setting-group">
                            <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-difficulty">
                                <i class="fas fa-chart-line me-2"></i>
                                Difficulty Level
                            </label>
                            <select id="<?php echo esc_attr($wizard_id); ?>-difficulty" class="cs-premium-select">
                                <option value="beginner">Beginner - New to the topic</option>
                                <option value="intermediate" selected>Intermediate - Some experience</option>
                                <option value="advanced">Advanced - Experienced learners</option>
                                <option value="expert">Expert - Master level content</option>
                            </select>
                        </div>

                        <!-- Target Audience -->
                        <div class="cs-setting-group">
                            <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-audience">
                                <i class="fas fa-users me-2"></i>
                                Target Audience
                            </label>
                            <select id="<?php echo esc_attr($wizard_id); ?>-audience" class="cs-premium-select">
                                <option value="students">Students (Academic)</option>
                                <option value="professionals" selected>Working Professionals</option>
                                <option value="entrepreneurs">Entrepreneurs</option>
                                <option value="educators">Educators & Trainers</option>
                                <option value="general">General Public</option>
                                <option value="children">Children (K-12)</option>
                                <option value="seniors">Senior Learners</option>
                            </select>
                        </div>

                        <!-- Content Tone -->
                        <div class="cs-setting-group">
                            <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-tone">
                                <i class="fas fa-comments me-2"></i>
                                Content Tone
                            </label>
                            <select id="<?php echo esc_attr($wizard_id); ?>-tone" class="cs-premium-select">
                                <option value="professional" selected>Professional</option>
                                <option value="friendly">Friendly & Conversational</option>
                                <option value="formal">Formal & Academic</option>
                                <option value="casual">Casual & Relaxed</option>
                                <option value="motivational">Motivational</option>
                                <option value="humorous">Light & Humorous</option>
                                <option value="technical">Technical & Precise</option>
                            </select>
                        </div>

                        <!-- Content Depth -->
                        <div class="cs-setting-group">
                            <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-depth">
                                <i class="fas fa-layer-group me-2"></i>
                                Content Depth
                            </label>
                            <select id="<?php echo esc_attr($wizard_id); ?>-depth" class="cs-premium-select">
                                <option value="overview">Overview - High-level concepts</option>
                                <option value="detailed" selected>Detailed - Comprehensive coverage</option>
                                <option value="practical">Practical - Hands-on focused</option>
                                <option value="theoretical">Theoretical - Concept-heavy</option>
                                <option value="example-rich">Example-Rich - Many examples</option>
                            </select>
                        </div>

                        <!-- Duration Estimate -->
                        <div class="cs-setting-group">
                            <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-duration">
                                <i class="fas fa-clock me-2"></i>
                                Target Duration
                            </label>
                            <select id="<?php echo esc_attr($wizard_id); ?>-duration" class="cs-premium-select">
                                <option value="30-min">30 Minutes</option>
                                <option value="1-hour" selected>1 Hour</option>
                                <option value="2-hours">2 Hours</option>
                                <option value="half-day">Half Day (4 Hours)</option>
                                <option value="full-day">Full Day (8 Hours)</option>
                                <option value="multi-day">Multi-Day</option>
                            </select>
                        </div>
                    </div>

                    <!-- Template Selection -->
                    <?php if (!empty($templates)): ?>
                    <div class="cs-template-section">
                        <h5 class="cs-section-title">
                            <i class="fas fa-file-alt me-2"></i>
                            Choose Template (Optional)
                        </h5>
                        <div class="cs-template-grid">
                            <div class="cs-template-option active" data-template="">
                                <div class="cs-template-preview">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <div class="cs-template-info">
                                    <h6>AI Generated</h6>
                                    <p>Let AI create from scratch</p>
                                </div>
                            </div>
                            <?php foreach ($templates as $template): ?>
                            <div class="cs-template-option" data-template="<?php echo esc_attr($template['id']); ?>">
                                <div class="cs-template-preview">
                                    <i class="<?php echo esc_attr($template['icon']); ?>"></i>
                                </div>
                                <div class="cs-template-info">
                                    <h6><?php echo esc_html($template['name']); ?></h6>
                                    <p><?php echo esc_html($template['description']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="cs-step-actions">
                    <button type="button" class="cs-btn cs-btn-primary cs-next-step" data-next="2">
                        <span>Next: Content Focus</span>
                        <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Content Focus -->
            <div class="cs-wizard-step" data-step="2" id="<?php echo esc_attr($wizard_id); ?>-step-2">
                <div class="cs-step-header">
                    <div class="cs-step-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="cs-step-info">
                        <h4 class="cs-step-title">Content Focus</h4>
                        <p class="cs-step-description">Define what you want to cover</p>
                    </div>
                </div>

                <div class="cs-step-content">
                    <!-- Custom Instructions -->
                    <div class="cs-instruction-container">
                        <label class="cs-setting-label" for="<?php echo esc_attr($wizard_id); ?>-instructions">
                            <i class="fas fa-edit me-2"></i>
                            Custom Instructions
                            <span class="cs-optional">(Optional)</span>
                        </label>
                        <div class="cs-textarea-enhanced">
                            <textarea id="<?php echo esc_attr($wizard_id); ?>-instructions" 
                                      class="cs-premium-textarea"
                                      placeholder="Provide specific instructions, topics to cover, learning objectives, or any special requirements...&#10;&#10;Examples:&#10;• Focus on practical, hands-on exercises&#10;• Include real-world case studies&#10;• Emphasize problem-solving approaches&#10;• Cover industry best practices"
                                      rows="6"
                                      maxlength="2000"></textarea>
                            <div class="cs-textarea-toolbar">
                                <div class="cs-char-count">
                                    <span class="cs-current">0</span>/<span class="cs-max">2000</span>
                                </div>
                                <div class="cs-suggestions-btn">
                                    <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-show-suggestions">
                                        <i class="fas fa-lightbulb"></i>
                                        Suggestions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Suggestions Panel -->
                    <div class="cs-suggestions-panel" style="display: none;">
                        <h6 class="cs-suggestions-title">Content Suggestions</h6>
                        <div class="cs-suggestions-grid">
                            <div class="cs-suggestion-category">
                                <h7>Learning Approaches</h7>
                                <div class="cs-suggestion-tags">
                                    <span class="cs-suggestion-tag">Hands-on exercises</span>
                                    <span class="cs-suggestion-tag">Case studies</span>
                                    <span class="cs-suggestion-tag">Interactive demos</span>
                                    <span class="cs-suggestion-tag">Group activities</span>
                                    <span class="cs-suggestion-tag">Problem-solving</span>
                                </div>
                            </div>
                            <div class="cs-suggestion-category">
                                <h7>Content Types</h7>
                                <div class="cs-suggestion-tags">
                                    <span class="cs-suggestion-tag">Video tutorials</span>
                                    <span class="cs-suggestion-tag">Written guides</span>
                                    <span class="cs-suggestion-tag">Infographics</span>
                                    <span class="cs-suggestion-tag">Quizzes</span>
                                    <span class="cs-suggestion-tag">Worksheets</span>
                                </div>
                            </div>
                            <div class="cs-suggestion-category">
                                <h7>Special Focus</h7>
                                <div class="cs-suggestion-tags">
                                    <span class="cs-suggestion-tag">Industry trends</span>
                                    <span class="cs-suggestion-tag">Best practices</span>
                                    <span class="cs-suggestion-tag">Common mistakes</span>
                                    <span class="cs-suggestion-tag">Advanced techniques</span>
                                    <span class="cs-suggestion-tag">Career application</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Topics -->
                    <div class="cs-topics-container">
                        <label class="cs-setting-label">
                            <i class="fas fa-list-ul me-2"></i>
                            Key Topics to Include
                            <span class="cs-optional">(Optional)</span>
                        </label>
                        <div class="cs-topics-input-container">
                            <input type="text" 
                                   id="<?php echo esc_attr($wizard_id); ?>-topic-input" 
                                   class="cs-premium-input"
                                   placeholder="Type a topic and press Enter..."
                                   maxlength="100">
                            <div class="cs-topics-list" id="<?php echo esc_attr($wizard_id); ?>-topics-list">
                                <!-- Topics will be added here dynamically -->
                            </div>
                        </div>
                    </div>

                    <!-- Learning Objectives -->
                    <div class="cs-objectives-container">
                        <label class="cs-setting-label">
                            <i class="fas fa-target me-2"></i>
                            Learning Objectives
                            <span class="cs-optional">(Optional)</span>
                        </label>
                        <div class="cs-objectives-input-container">
                            <textarea id="<?php echo esc_attr($wizard_id); ?>-objectives" 
                                      class="cs-premium-textarea"
                                      placeholder="Define what learners should be able to do after completion...&#10;&#10;Example:&#10;• Understand core concepts of [topic]&#10;• Apply practical techniques for [skill]&#10;• Analyze and solve [specific problems]"
                                      rows="4"
                                      maxlength="1000"></textarea>
                            <div class="cs-char-count">
                                <span class="cs-current">0</span>/<span class="cs-max">1000</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cs-step-actions">
                    <button type="button" class="cs-btn cs-btn-secondary cs-prev-step" data-prev="1">
                        <i class="fas fa-arrow-left me-2"></i>
                        <span>Back</span>
                    </button>
                    <button type="button" class="cs-btn cs-btn-primary cs-next-step" data-next="3">
                        <span>Next: Generate Content</span>
                        <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: AI Generation -->
            <div class="cs-wizard-step" data-step="3" id="<?php echo esc_attr($wizard_id); ?>-step-3">
                <div class="cs-step-header">
                    <div class="cs-step-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="cs-step-info">
                        <h4 class="cs-step-title">AI Generation</h4>
                        <p class="cs-step-description">Watch as AI creates your content</p>
                    </div>
                </div>

                <div class="cs-step-content">
                    <!-- Generation Summary -->
                    <div class="cs-generation-summary">
                        <div class="cs-summary-card">
                            <h5>Generation Summary</h5>
                            <div class="cs-summary-details">
                                <div class="cs-summary-item">
                                    <strong>Type:</strong> <span id="cs-summary-type"></span>
                                </div>
                                <div class="cs-summary-item">
                                    <strong>Count:</strong> <span id="cs-summary-count"></span>
                                </div>
                                <div class="cs-summary-item">
                                    <strong>Level:</strong> <span id="cs-summary-difficulty"></span>
                                </div>
                                <div class="cs-summary-item">
                                    <strong>Audience:</strong> <span id="cs-summary-audience"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Generation Progress -->
                    <div class="cs-generation-progress" id="cs-generation-progress" style="display: none;">
                        <div class="cs-progress-container">
                            <div class="cs-progress-spinner">
                                <div class="cs-spinner"></div>
                            </div>
                            <div class="cs-progress-text">
                                <h6 id="cs-progress-title">Initializing AI generation...</h6>
                                <p id="cs-progress-description">Setting up your content parameters</p>
                            </div>
                        </div>
                        <div class="cs-progress-steps">
                            <div class="cs-progress-step active" data-step="analyze">
                                <i class="fas fa-search"></i>
                                <span>Analyzing Requirements</span>
                            </div>
                            <div class="cs-progress-step" data-step="generate">
                                <i class="fas fa-cog"></i>
                                <span>Generating Content</span>
                            </div>
                            <div class="cs-progress-step" data-step="optimize">
                                <i class="fas fa-check"></i>
                                <span>Optimizing Results</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cs-step-actions">
                    <button type="button" class="cs-btn cs-btn-secondary cs-prev-step" data-prev="2">
                        <i class="fas fa-arrow-left me-2"></i>
                        <span>Back</span>
                    </button>
                    <button type="button" class="cs-btn cs-btn-success cs-generate-content" id="cs-start-generation">
                        <i class="fas fa-magic me-2"></i>
                        <span>Generate with AI</span>
                    </button>
                </div>
            </div>

            <!-- Step 4: Review & Customize -->
            <div class="cs-wizard-step" data-step="4" id="<?php echo esc_attr($wizard_id); ?>-step-4">
                <div class="cs-step-header">
                    <div class="cs-step-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="cs-step-info">
                        <h4 class="cs-step-title">Review & Customize</h4>
                        <p class="cs-step-description">Review and customize your generated content</p>
                    </div>
                </div>

                <div class="cs-step-content">
                    <!-- Generated Content Container -->
                    <div class="cs-generated-content" id="cs-generated-content">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>

                <div class="cs-step-actions">
                    <button type="button" class="cs-btn cs-btn-secondary cs-regenerate">
                        <i class="fas fa-redo me-2"></i>
                        <span>Regenerate</span>
                    </button>
                    <button type="button" class="cs-btn cs-btn-success cs-save-content" id="cs-save-generated">
                        <i class="fas fa-save me-2"></i>
                        <span>Save & Add to Curriculum</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Inputs -->
        <input type="hidden" name="parent_id" value="<?php echo esc_attr($parent_id); ?>">
        <input type="hidden" name="generation_type" value="<?php echo esc_attr($type); ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce($nonce_action); ?>">
    </div>

    <?php
}
?>