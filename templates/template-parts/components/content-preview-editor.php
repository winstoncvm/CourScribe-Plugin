<?php
// courscribe/templates/template-parts/components/content-preview-editor.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Preview and Editor Component
 * Displays generated content with inline editing capabilities
 * 
 * @param array $args {
 *     @type string $type       Content type (course, module, lesson)
 *     @type array  $content    Generated content data
 *     @type bool   $editable   Whether content is editable
 * }
 */

function courscribe_render_content_preview($args = []) {
    $defaults = [
        'type' => 'course',
        'content' => [],
        'editable' => true,
        'show_bulk_actions' => true
    ];

    $args = wp_parse_args($args, $defaults);
    extract($args);

    if (empty($content)) {
        return;
    }

    $preview_id = "cs-content-preview-{$type}";
    ?>

    <div class="cs-content-preview" id="<?php echo esc_attr($preview_id); ?>">
        <!-- Preview Header -->
        <div class="cs-preview-header">
            <div class="cs-preview-title">
                <h5>
                    <i class="fas fa-eye me-2"></i>
                    Generated <?php echo ucfirst($type); ?><?php echo count($content) > 1 ? 's' : ''; ?>
                    <span class="cs-count-badge"><?php echo count($content); ?></span>
                </h5>
            </div>
            
            <?php if ($show_bulk_actions && $editable): ?>
            <div class="cs-preview-actions">
                <div class="cs-bulk-actions">
                    <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-select-all">
                        <i class="fas fa-check-square me-1"></i>
                        Select All
                    </button>
                    <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-deselect-all">
                        <i class="fas fa-square me-1"></i>
                        Deselect All
                    </button>
                </div>
                <div class="cs-quality-actions">
                    <button type="button" class="cs-btn cs-btn-sm cs-btn-success cs-enhance-selected">
                        <i class="fas fa-star me-1"></i>
                        Enhance Selected
                    </button>
                    <button type="button" class="cs-btn cs-btn-sm cs-btn-warning cs-regenerate-selected">
                        <i class="fas fa-redo me-1"></i>
                        Regenerate Selected
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Content Items -->
        <div class="cs-preview-content">
            <?php foreach ($content as $index => $item): ?>
            <div class="cs-content-item <?php echo $editable ? 'editable' : 'readonly'; ?>" 
                 data-index="<?php echo $index; ?>"
                 data-item-id="<?php echo esc_attr($item['id'] ?? ''); ?>">
                
                <!-- Item Header -->
                <div class="cs-item-header">
                    <?php if ($editable): ?>
                    <div class="cs-item-selector">
                        <input type="checkbox" 
                               class="cs-item-checkbox" 
                               id="item-<?php echo $index; ?>"
                               checked>
                        <label for="item-<?php echo $index; ?>" class="cs-checkbox-label"></label>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cs-item-info">
                        <div class="cs-item-number"><?php echo $index + 1; ?></div>
                        <div class="cs-item-type"><?php echo ucfirst($type); ?></div>
                    </div>
                    
                    <div class="cs-item-status">
                        <div class="cs-quality-indicator" data-quality="<?php echo esc_attr($item['quality'] ?? 'good'); ?>">
                            <div class="cs-quality-stars">
                                <?php 
                                $quality = $item['quality'] ?? 'good';
                                $stars = $quality === 'excellent' ? 5 : ($quality === 'good' ? 4 : 3);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                <i class="fas fa-star <?php echo $i <= $stars ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="cs-quality-text"><?php echo ucfirst($quality); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($editable): ?>
                    <div class="cs-item-actions">
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-edit-item" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-duplicate-item" title="Duplicate">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-regenerate-item" title="Regenerate">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-item" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Item Content -->
                <div class="cs-item-content">
                    <!-- Title -->
                    <div class="cs-content-field">
                        <label class="cs-field-label">
                            <i class="fas fa-heading me-2"></i>
                            Title
                        </label>
                        <?php if ($editable): ?>
                        <div class="cs-editable-field">
                            <input type="text" 
                                   class="cs-premium-input cs-field-title" 
                                   value="<?php echo esc_attr($item['title'] ?? ''); ?>"
                                   placeholder="Enter title..."
                                   maxlength="100">
                            <div class="cs-field-feedback">
                                <div class="cs-char-count">
                                    <span class="cs-current"><?php echo strlen($item['title'] ?? ''); ?></span>/<span class="cs-max">100</span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="cs-readonly-field">
                            <h6><?php echo esc_html($item['title'] ?? ''); ?></h6>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description/Goal -->
                    <?php if (isset($item['description']) || isset($item['goal'])): ?>
                    <div class="cs-content-field">
                        <label class="cs-field-label">
                            <i class="fas fa-align-left me-2"></i>
                            <?php echo $type === 'course' ? 'Goal' : 'Description'; ?>
                        </label>
                        <?php if ($editable): ?>
                        <div class="cs-editable-field">
                            <textarea class="cs-premium-textarea cs-field-description" 
                                      rows="3"
                                      placeholder="Enter <?php echo $type === 'course' ? 'goal' : 'description'; ?>..."
                                      maxlength="500"><?php echo esc_textarea($item['description'] ?? $item['goal'] ?? ''); ?></textarea>
                            <div class="cs-field-feedback">
                                <div class="cs-char-count">
                                    <span class="cs-current"><?php echo strlen($item['description'] ?? $item['goal'] ?? ''); ?></span>/<span class="cs-max">500</span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="cs-readonly-field">
                            <p><?php echo esc_html($item['description'] ?? $item['goal'] ?? ''); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Topics/Objectives -->
                    <?php if (isset($item['topics']) || isset($item['objectives'])): ?>
                    <div class="cs-content-field">
                        <label class="cs-field-label">
                            <i class="fas fa-list-ul me-2"></i>
                            <?php echo $type === 'course' ? 'Topics' : 'Key Points'; ?>
                        </label>
                        <?php 
                        $items_list = $item['topics'] ?? $item['objectives'] ?? [];
                        if (!is_array($items_list)) {
                            $items_list = array_filter(explode("\n", $items_list));
                        }
                        ?>
                        <div class="cs-items-list">
                            <?php if ($editable): ?>
                            <div class="cs-editable-list" data-field="<?php echo $type === 'course' ? 'topics' : 'objectives'; ?>">
                                <?php foreach ($items_list as $list_index => $list_item): ?>
                                <div class="cs-list-item">
                                    <div class="cs-list-handle">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                    <input type="text" 
                                           class="cs-premium-input cs-list-input" 
                                           value="<?php echo esc_attr(trim($list_item)); ?>"
                                           placeholder="Enter item...">
                                    <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-list-item">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                                <div class="cs-add-list-item">
                                    <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-add-item-btn">
                                        <i class="fas fa-plus me-1"></i>
                                        Add Item
                                    </button>
                                </div>
                            </div>
                            <?php else: ?>
                            <ul class="cs-readonly-list">
                                <?php foreach ($items_list as $list_item): ?>
                                <li><?php echo esc_html(trim($list_item)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Duration (if available) -->
                    <?php if (isset($item['duration'])): ?>
                    <div class="cs-content-field">
                        <label class="cs-field-label">
                            <i class="fas fa-clock me-2"></i>
                            Estimated Duration
                        </label>
                        <?php if ($editable): ?>
                        <div class="cs-duration-selector">
                            <select class="cs-premium-select cs-field-duration">
                                <?php 
                                $duration_options = [
                                    '30-min' => '30 Minutes',
                                    '1-hour' => '1 Hour',
                                    '1.5-hours' => '1.5 Hours',
                                    '2-hours' => '2 Hours',
                                    '3-hours' => '3 Hours',
                                    '4-hours' => '4 Hours',
                                    '6-hours' => '6 Hours',
                                    '8-hours' => '8 Hours',
                                    'multi-day' => 'Multiple Days'
                                ];
                                $current_duration = $item['duration'] ?? '1-hour';
                                foreach ($duration_options as $value => $label): 
                                ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_duration, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="cs-readonly-field">
                            <span class="cs-duration-badge"><?php echo esc_html($item['duration'] ?? 'Not specified'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Level of Learning (for courses) -->
                    <?php if ($type === 'course' && isset($item['level'])): ?>
                    <div class="cs-content-field">
                        <label class="cs-field-label">
                            <i class="fas fa-chart-line me-2"></i>
                            Level of Learning
                        </label>
                        <?php if ($editable): ?>
                        <div class="cs-level-selector">
                            <select class="cs-premium-select cs-field-level">
                                <?php 
                                $level_options = [
                                    'remember' => 'Remember - Recall facts and concepts',
                                    'understand' => 'Understand - Explain ideas or concepts',
                                    'apply' => 'Apply - Use information in new situations',
                                    'analyze' => 'Analyze - Draw connections between ideas',
                                    'evaluate' => 'Evaluate - Justify decisions or opinions',
                                    'create' => 'Create - Produce new or original work'
                                ];
                                $current_level = $item['level'] ?? 'apply';
                                foreach ($level_options as $value => $label): 
                                ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_level, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="cs-readonly-field">
                            <span class="cs-level-badge"><?php echo esc_html(ucfirst($item['level'] ?? 'apply')); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- AI Suggestions -->
                    <?php if ($editable && isset($item['suggestions'])): ?>
                    <div class="cs-content-field cs-suggestions-field">
                        <label class="cs-field-label">
                            <i class="fas fa-lightbulb me-2"></i>
                            AI Suggestions
                        </label>
                        <div class="cs-suggestions-container">
                            <?php foreach ($item['suggestions'] as $suggestion): ?>
                            <div class="cs-suggestion-item">
                                <div class="cs-suggestion-text"><?php echo esc_html($suggestion); ?></div>
                                <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-apply-suggestion">
                                    Apply
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Item Footer -->
                <?php if ($editable): ?>
                <div class="cs-item-footer">
                    <div class="cs-item-stats">
                        <span class="cs-stat">
                            <i class="fas fa-clock me-1"></i>
                            Last modified: <span class="cs-timestamp">just now</span>
                        </span>
                        <span class="cs-stat">
                            <i class="fas fa-magic me-1"></i>
                            AI generated
                        </span>
                    </div>
                    <div class="cs-item-validation">
                        <div class="cs-validation-status valid">
                            <i class="fas fa-check-circle"></i>
                            <span>Valid</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Add More Content -->
        <?php if ($editable): ?>
        <div class="cs-add-more-content">
            <button type="button" class="cs-btn cs-btn-outline cs-btn-lg cs-add-more">
                <i class="fas fa-plus-circle me-2"></i>
                Add More <?php echo ucfirst($type); ?>s
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Content Statistics -->
    <div class="cs-content-stats">
        <div class="cs-stats-grid">
            <div class="cs-stat-item">
                <div class="cs-stat-value" id="cs-total-items"><?php echo count($content); ?></div>
                <div class="cs-stat-label">Total Items</div>
            </div>
            <div class="cs-stat-item">
                <div class="cs-stat-value" id="cs-selected-items"><?php echo count($content); ?></div>
                <div class="cs-stat-label">Selected</div>
            </div>
            <div class="cs-stat-item">
                <div class="cs-stat-value" id="cs-estimated-duration">
                    <?php 
                    $total_minutes = 0;
                    foreach ($content as $item) {
                        if (isset($item['duration'])) {
                            $duration = $item['duration'];
                            if (strpos($duration, 'min') !== false) {
                                $total_minutes += intval($duration);
                            } elseif (strpos($duration, 'hour') !== false) {
                                $total_minutes += intval($duration) * 60;
                            }
                        }
                    }
                    
                    if ($total_minutes > 0) {
                        if ($total_minutes < 60) {
                            echo $total_minutes . ' min';
                        } else {
                            $hours = floor($total_minutes / 60);
                            $mins = $total_minutes % 60;
                            echo $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
                        }
                    } else {
                        echo 'TBD';
                    }
                    ?>
                </div>
                <div class="cs-stat-label">Est. Duration</div>
            </div>
            <div class="cs-stat-item">
                <div class="cs-stat-value" id="cs-quality-score">
                    <?php
                    $total_quality = 0;
                    foreach ($content as $item) {
                        $quality = $item['quality'] ?? 'good';
                        $score = $quality === 'excellent' ? 5 : ($quality === 'good' ? 4 : 3);
                        $total_quality += $score;
                    }
                    $avg_quality = round($total_quality / count($content), 1);
                    echo $avg_quality . '/5';
                    ?>
                </div>
                <div class="cs-stat-label">Quality Score</div>
            </div>
        </div>
    </div>

    <?php
}
?>