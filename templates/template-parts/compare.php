<?php

?>
<div class="mb-3 w-100">
    <h6 style="margin-left: 1rem;">Objectives:</h6>
    <ul id="module-objectives-list-<?php echo $module->ID; ?>" class="cs-objectives-list">
        <?php
        if (!empty($module_objectives) && is_array($module_objectives)) {
            $objective_number = 1;
            foreach ($module_objectives as $index => $objective) {
                error_log('Objective ' . $index . ' for module ' . $module->ID . ': ' . print_r($objective, true));
                $objective_id = 'objective-' . $module->ID . '-' . $index;
                $thinking_skill = isset($objective['thinking_skill']) ? esc_html($objective['thinking_skill']) : '';
                $action_verb = isset($objective['action_verb']) ? esc_html($objective['action_verb']) : '';
                $description = isset($objective['description']) ? esc_html($objective['description']) : '';
                ?>
                <li class="cs-objective-item animate-slide-in cs-objective-item-<?php echo $module->ID; ?> mb-3" 
                    data-objective-id="<?php echo $objective_id; ?>" 
                    data-module-id="<?php echo $module->ID; ?>">
                    
                    <div class="courscribe-header-with-divider mb-2">
                        <span class="courscribe-title-sm">Objective <?php echo esc_html($objective_number); ?>:</span>
                        <div class="courscribe-divider"></div>
                        <?php if ($is_client) : ?>
                            <!-- Client feedback button -->
                        <?php elseif ($can_view_feedback) : ?>
                            <!-- Feedback view button -->
                        <?php else : ?>
                            <?php if (!$is_client) : ?>
                                <?php
                                $remove_button = '<button type="button" class="remove-btn btn-sm cs-remove-objective" data-objective-id="' . $objective_id . '">Remove</button>';
                                echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                    'title' => 'Remove Objective',
                                    'description' => 'Delete this objective from your course. Available in all packages.',
                                    'required_package' => 'CourScribe Basics'
                                ]);
                                ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="objective-row mb-2">
                        <label for="thinking-skill-<?php echo $objective_id; ?>">Select the Thinking Skill</label>
                        
                        <?php if ($is_client) : ?>
                            <span><?php echo esc_html($thinking_skill ?: 'Not set'); ?></span>
                        <?php else : ?>
                            <select id="module-thinking-skill-<?php echo $objective_id; ?>" 
                                    class="form-control bg-dark text-light cs-thinking-skill" 
                                    data-objective-id="<?php echo $objective_id; ?>" 
                                    data-module-id="<?php echo $module->ID; ?>"
                                    style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                <?php
                                $skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                foreach ($skills as $skill) {
                                    $selected = ($thinking_skill == $skill) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($skill) . '" ' . $selected . '>' . esc_html($skill) . '</option>';
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="courscribe-header-with-divider mb-2 mt-2">
                        <span class="courscribe-title-sm">Forms the Objectives</span>
                        <div class="courscribe-divider"></div>
                    </div>
                    <div class="objective-row mb-4">
                        <label for="action-verb-<?php echo $objective_id; ?>">By the end of this Module they will: Objective <?php echo $objective_number; ?></label>
                        <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                            <?php if ($is_client) : ?>
                                <div class="courscribe-row">
                                    <span class="client-preview-action-verb"><?php echo esc_html($action_verb ?: 'Not set'); ?></span>
                                    <div class="client-preview-action-verb-description">
                                        <?php echo esc_attr($description); ?>
                                    </div>
                                </div>
                            <?php else : ?>
                                <select id="module-action-verb-<?php echo $objective_id; ?>" 
                                        class="form-control bg-dark text-light cs-action-verb" 
                                        data-objective-id="<?php echo $objective_id; ?>" 
                                        data-module-id="<?php echo $module->ID; ?>"
                                        style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                    <!-- This will be populated by JavaScript -->
                                    <?php if ($action_verb) : ?>
                                        <option value="<?php echo esc_attr($action_verb); ?>" selected><?php echo esc_html($action_verb); ?></option>
                                    <?php endif; ?>
                                </select>
                                <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                                    <textarea 
                                        id="module-objective-description-<?php echo $module->ID; ?>-<?php echo $objective_id; ?>"
                                        class="form-control bg-dark text-light cs-objective-description"
                                        data-objective-id="<?php echo $objective_id; ?>"
                                        data-module-id="<?php echo $module->ID; ?>"
                                        placeholder="Enter objective description"
                                        rows="2"
                                        style="flex:1"><?php echo esc_textarea($description); ?></textarea>
                                    <?php
                                    $ai_button = '<button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                                        data-field-id="module-objective-description-' . $module->ID . '-' . $objective_id . '"
                                        data-bs-toggle="modal"
                                        data-bs-target="#inputAiSuggestionsModal"
                                        data-module-id="' . esc_attr($module->ID) . '"
                                        data-module-name="' . esc_attr($module->post_title) . '"
                                        data-module-goal="' . esc_attr($module_goal) . '"
                                        data-course-name="' . esc_attr($course_title) . '"
                                        data-course-goal="' . esc_attr($course_goal) . '"
                                        data-thinking-skill="' . esc_attr($thinking_skill) . '"
                                        data-action-verb="' . esc_attr($action_verb) . '">
                                        <i class="fa fa-magic"></i>
                                    </button>';
                                    echo $tooltips->wrap_button_with_tooltip($ai_button, [
                                        'description' => 'Get AI-generated suggestions for your module objective (requires CourScribe Pro)',
                                        'required_package' => 'CourScribe Pro (Agency)',
                                        'title' => 'Get AI-generated suggestions'
                                    ]);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php
                $objective_number++;
            }
        } else {
            echo '<li>No objectives added yet.</li>';
        }
        ?>
    </ul>
    <?php if (!$is_client) : ?>
    <button id="addModuleListObjectiveBtn" 
            type="button" 
            class="add-objective mb-4 cs-add-objective" 
            data-module-id="<?php echo $module->ID; ?>">
        <i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add Objective
    </button>
    <?php endif ?>
</div>
<!-- current -->
<div class="mb-3 w-100">
    <h6 style="margin-left: 1rem;">Objectives:</h6>
    <ul id="module-objectives-list-<?php echo $module->ID; ?>" class="cs-objectives-list">
        <?php
        if (!empty($module_objectives) && is_array($module_objectives)) {
            $objective_number = 1;
            foreach ($module_objectives as $index => $objective) {
                error_log('Objective ' . $index . ' for module ' . $module->ID . ': ' . print_r($objective, true));
                $objective_id = 'objective-' . $module->ID . '-' . $index;
                $thinking_skill = isset($objective['thinking_skill']) ? esc_html($objective['thinking_skill']) : '';
                $action_verb = isset($objective['action_verb']) ? esc_html($objective['action_verb']) : '';
                $description = isset($objective['description']) ? esc_html($objective['description']) : '';
                ?>
                <li class="cs-objective-item animate-slide-in cs-objective-item-<?php echo $module->ID; ?> mb-3" 
                    data-objective-id="<?php echo $objective_id; ?>" 
                    data-module-id="<?php echo $module->ID; ?>">
                    
                    <div class="courscribe-header-with-divider mb-2">
                        <span class="courscribe-title-sm">Objective <?php echo esc_html($objective_number); ?>:</span>
                        <div class="courscribe-divider"></div>
                        <?php if ($is_client) : ?>
                            <!-- Client feedback button -->
                        <?php elseif ($can_view_feedback) : ?>
                            <!-- Feedback view button -->
                        <?php else : ?>
                            <?php if (!$is_client) : ?>
                                <?php
                                $remove_button = '<button type="button" class="remove-btn btn-sm cs-remove-objective" data-objective-id="' . $objective_id . '">Remove</button>';
                                echo $tooltips->wrap_button_with_tooltip($remove_button, [
                                    'title' => 'Remove Objective',
                                    'description' => 'Delete this objective from your course. Available in all packages.',
                                    'required_package' => 'CourScribe Basics'
                                ]);
                                ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="objective-row mb-2">
                        <label for="thinking-skill-<?php echo $objective_id; ?>">Select the Thinking Skill</label>
                        
                        <?php if ($is_client) : ?>
                            <span><?php echo esc_html($thinking_skill ?: 'Not set'); ?></span>
                        <?php else : ?>
                            <select id="module-thinking-skill-<?php echo $objective_id; ?>" 
                                    class="form-control bg-dark text-light cs-thinking-skill" 
                                    data-objective-id="<?php echo $objective_id; ?>" 
                                    data-module-id="<?php echo $module->ID; ?>"
                                    style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                <?php
                                $skills = ['Know', 'Comprehend', 'Apply', 'Analyze', 'Evaluate', 'Create'];
                                foreach ($skills as $skill) {
                                    $selected = ($thinking_skill == $skill) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($skill) . '" ' . $selected . '>' . esc_html($skill) . '</option>';
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="courscribe-header-with-divider mb-2 mt-2">
                        <span class="courscribe-title-sm">Forms the Objectives</span>
                        <div class="courscribe-divider"></div>
                    </div>
                    <div class="objective-row mb-4">
                        <label for="action-verb-<?php echo $objective_id; ?>">By the end of this Module they will: Objective <?php echo $objective_number; ?></label>
                        <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                            <?php if ($is_client) : ?>
                                <div class="courscribe-row">
                                    <span class="client-preview-action-verb"><?php echo esc_html($action_verb ?: 'Not set'); ?></span>
                                    <div class="client-preview-action-verb-description">
                                        <?php echo esc_attr($description); ?>
                                    </div>
                                </div>
                            <?php else : ?>
                                <select id="module-action-verb-<?php echo $objective_id; ?>" 
                                        class="form-control bg-dark text-light cs-action-verb" 
                                        data-objective-id="<?php echo $objective_id; ?>" 
                                        data-module-id="<?php echo $module->ID; ?>"
                                        style="min-width: 120px; max-width: 180px; padding-inline: 0.5rem;">
                                    <!-- This will be populated by JavaScript -->
                                    <?php if ($action_verb) : ?>
                                        <option value="<?php echo esc_attr($action_verb); ?>" selected><?php echo esc_html($action_verb); ?></option>
                                    <?php endif; ?>
                                </select>
                                <div class="d-flex w-100 my-mr-1 mb-2 gap2 align-center-row-div">
                                    <textarea 
                                        id="module-objective-description-<?php echo $module->ID; ?>-<?php echo $objective_id; ?>"
                                        class="form-control bg-dark text-light cs-objective-description"
                                        data-objective-id="<?php echo $objective_id; ?>"
                                        data-module-id="<?php echo $module->ID; ?>"
                                        placeholder="Enter objective description"
                                        rows="2"
                                        style="flex:1"><?php echo esc_textarea($description); ?></textarea>
                                    <?php
                                    $ai_button = '<button id="open-input-ai-suggestions-modal" class="ai-suggest-button"
                                        data-field-id="module-objective-description-' . $module->ID . '-' . $objective_id . '"
                                        data-bs-toggle="modal"
                                        data-bs-target="#inputAiSuggestionsModal"
                                        data-module-id="' . esc_attr($module->ID) . '"
                                        data-module-name="' . esc_attr($module->post_title) . '"
                                        data-module-goal="' . esc_attr($module_goal) . '"
                                        data-course-name="' . esc_attr($course_title) . '"
                                        data-course-goal="' . esc_attr($course_goal) . '"
                                        data-thinking-skill="' . esc_attr($thinking_skill) . '"
                                        data-action-verb="' . esc_attr($action_verb) . '">
                                        <i class="fa fa-magic"></i>
                                    </button>';
                                    echo $tooltips->wrap_button_with_tooltip($ai_button, [
                                        'description' => 'Get AI-generated suggestions for your module objective (requires CourScribe Pro)',
                                        'required_package' => 'CourScribe Pro (Agency)',
                                        'title' => 'Get AI-generated suggestions'
                                    ]);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php
                $objective_number++;
            }
        } else {
            echo '<li>No objectives added yet.</li>';
        }
        ?>
    </ul>
    <?php if (!$is_client) : ?>
    <button id="addModuleListObjectiveBtn" 
            type="button" 
            class="add-objective mb-4 cs-add-objective" 
            data-module-id="<?php echo $module->ID; ?>">
        <i class="fa fa-plus me-2 custom-icon" aria-hidden="true"></i>Add Objective
    </button>
    <?php endif ?>
</div>