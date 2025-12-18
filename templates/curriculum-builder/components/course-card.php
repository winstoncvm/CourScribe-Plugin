<?php
/**
 * Course Card Component
 * Individual course display within the curriculum builder
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safely get post meta as array
 * Handles cases where meta might be stored as string or serialized data
 */
// function courscribe_get_meta_as_array_new($post_id, $meta_key, $default = []) {
//     $meta_value = get_post_meta($post_id, $meta_key, true);
    
//     if (is_array($meta_value)) {
//         return $meta_value;
//     }
    
//     if (is_string($meta_value) && !empty($meta_value)) {
//         // Try to unserialize if it's a serialized string
//         $unserialized = @unserialize($meta_value);
//         if (is_array($unserialized)) {
//             return $unserialized;
//         }
        
//         // Try JSON decode as fallback
//         $json_decoded = @json_decode($meta_value, true);
//         if (is_array($json_decoded)) {
//             return $json_decoded;
//         }
//     }
    
//     return $default;
// }

// Ensure we have course data
$course = isset($course) ? $course : null;
$index = isset($index) ? $index : 0;

if (!$course) {
    return;
}

// Get course metadata
$course_id = $course->ID;
$course_title = $course->post_title;
$course_content = $course->post_content;

// Get all course fields
$course_goal = get_post_meta($course_id, '_class_goal', true) ?: '';
$course_objectives = get_post_meta($course_id, '_course_objectives', true);
if (!is_array($course_objectives)) {
    $course_objectives = [];
}
$course_duration = get_post_meta($course_id, '_course_duration', true) ?: '0 hours';
$level_of_learning = get_post_meta($course_id, 'level-of-learning', true) ?: 'Beginner';

// Query modules for this course
$modules_query = new WP_Query([
    'post_type' => 'crscribe_module',
    'post_status' => ['publish', 'draft'],
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_course_id',
            'value' => $course_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'menu_order',
    'order' => 'ASC'
]);

$modules_count = $modules_query->post_count;

// Get total lesson count for this course
$lessons_query = new WP_Query([
    'post_type' => 'crscribe_lesson',
    'post_status' => ['publish', 'draft'],
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_course_id',
            'value' => $course_id,
            'compare' => '='
        ]
    ],
    'no_found_rows' => true
]);
$lessons_count = $lessons_query->post_count;
wp_reset_postdata();
?>

<div class="ccb-course-card" data-course-id="<?php echo esc_attr($course_id); ?>" data-course-order="<?php echo esc_attr($index + 1); ?>">
    
    <!-- Course Card Header -->
    <div class="ccb-course-header">
        <div class="ccb-course-drag-handle" title="Drag to reorder">
            <i class="fas fa-grip-vertical"></i>
        </div>
        <div class="ccb-course-number">
            <span class="ccb-course-number-text">Course <?php echo esc_html($index + 1); ?></span>
        </div>
        <div class="ccb-course-title-section">
            <h3 class="ccb-course-title" contenteditable="true" data-field="title"><?php echo esc_html($course_title); ?></h3>
            <div class="ccb-course-meta">
                <span class="ccb-course-duration">
                    <i class="fas fa-clock"></i>
                    <?php echo esc_html($course_duration); ?>
                </span>
                <span class="ccb-course-level ccb-level-<?php echo esc_attr(strtolower($level_of_learning)); ?>">
                    <i class="fas fa-signal"></i>
                    <?php echo esc_html($level_of_learning); ?>
                </span>
                <span class="ccb-course-modules">
                    <i class="fas fa-puzzle-piece"></i>
                    <?php echo esc_html($modules_count); ?> modules
                </span>
                <span class="ccb-course-lessons">
                    <i class="fas fa-play-circle"></i>
                    <?php echo esc_html($lessons_count); ?> lessons
                </span>
            </div>
        </div>
        <div class="ccb-course-actions">
            <button class="ccb-action-btn ccb-course-edit-btn" title="Edit Course" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-edit"></i>
            </button>
            <button class="ccb-action-btn ccb-course-ai-btn" title="AI Generate Content" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-magic"></i>
            </button>
            <button class="ccb-action-btn ccb-course-delete-btn" title="Delete Course" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <!-- Course Content Section (Collapsible) -->
    <div class="ccb-course-content" id="ccb-course-content-<?php echo esc_attr($course_id); ?>">
        
        <!-- Course Goal -->
        <div class="ccb-course-goal">
            <div class="ccb-content-section-mini">
                <h4 class="ccb-content-section-title">
                    <i class="fas fa-bullseye"></i>
                    Course Goal
                </h4>
                <div class="ccb-editor-container" 
                     id="ccb-course-goal-editor-<?php echo esc_attr($course_id); ?>"
                     data-field="course_goal" 
                     data-course-id="<?php echo esc_attr($course_id); ?>"
                     data-editor-type="simple"
                     data-content="<?php echo esc_attr($course_goal ?: 'Define the main goal for this course...'); ?>">
                </div>
            </div>
        </div>

        <!-- Course Description -->
        <div class="ccb-course-description">
            <div class="ccb-content-section-mini">
                <h4 class="ccb-content-section-title">
                    <i class="fas fa-align-left"></i>
                    Course Description
                </h4>
                <div class="ccb-editor-container" 
                     id="ccb-course-content-editor-<?php echo esc_attr($course_id); ?>"
                     data-field="content" 
                     data-course-id="<?php echo esc_attr($course_id); ?>"
                     data-editor-type="full"
                     data-content="<?php echo esc_attr($course_content ?: 'Add a description for this course...'); ?>">
                </div>
            </div>
        </div>

        <!-- Learning Objectives -->
        <?php if (!empty($course_objectives)): ?>
        <div class="ccb-course-objectives">
            <div class="ccb-content-section-mini">
                <h4 class="ccb-content-section-title">
                    Learning Objectives
                    <button class="ccb-add-objective-btn" title="Add Objective" data-course-id="<?php echo esc_attr($course_id); ?>">
                        <i class="fas fa-plus"></i>
                    </button>
                </h4>
                <div class="ccb-objectives-list" data-course-id="<?php echo esc_attr($course_id); ?>">
                    <?php foreach ($course_objectives as $obj_index => $objective): ?>
                    <div class="ccb-objective-item" data-objective-index="<?php echo esc_attr($obj_index); ?>">
                        <div class="ccb-objective-drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="ccb-objective-content">
                            <div class="ccb-objective-thinking-skill">
                                <select class="ccb-thinking-skill-select" data-field="thinking_skill">
                                    <option value="remember" <?php selected($objective['thinking_skill'] ?? '', 'remember'); ?>>Remember</option>
                                    <option value="understand" <?php selected($objective['thinking_skill'] ?? '', 'understand'); ?>>Understand</option>
                                    <option value="apply" <?php selected($objective['thinking_skill'] ?? '', 'apply'); ?>>Apply</option>
                                    <option value="analyze" <?php selected($objective['thinking_skill'] ?? '', 'analyze'); ?>>Analyze</option>
                                    <option value="evaluate" <?php selected($objective['thinking_skill'] ?? '', 'evaluate'); ?>>Evaluate</option>
                                    <option value="create" <?php selected($objective['thinking_skill'] ?? '', 'create'); ?>>Create</option>
                                </select>
                            </div>
                            <input type="text" 
                                   class="ccb-objective-description" 
                                   data-field="description"
                                   value="<?php echo esc_attr($objective['description'] ?? ''); ?>"
                                   placeholder="Enter learning objective...">
                        </div>
                        <button class="ccb-objective-delete-btn" title="Delete Objective">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modules Section -->
        <div class="ccb-course-modules-section">
            <div class="ccb-content-section-mini">
                <h4 class="ccb-content-section-title">
                    <i class="fas fa-puzzle-piece"></i>
                    Modules (<?php echo esc_html($modules_count); ?>)
                    <button class="ccb-add-module-btn" title="Add Module" data-course-id="<?php echo esc_attr($course_id); ?>">
                        <i class="fas fa-plus"></i>
                    </button>
                </h4>
                
                <?php if ($modules_query->have_posts()): ?>
                <div class="ccb-modules-accordion" data-course-id="<?php echo esc_attr($course_id); ?>">
                    <?php $module_index = 0; while ($modules_query->have_posts()): $modules_query->the_post(); ?>
                        <?php
                        $module = get_post();
                        $module_id = $module->ID;
                        $module_objectives = get_post_meta($module_id, '_module_objectives', true);
                        $module_duration = get_post_meta($module_id, '_module_duration', true) ?: '0 hours';
                        $module_methods = get_post_meta($module_id, '_module_methods', true) ?: [];
                        $module_materials = get_post_meta($module_id, '_module_materials', true) ?: [];
                        $module_media = get_post_meta($module_id, '_module_media', true) ?: [];
                        
                        // Ensure arrays are properly formatted
                        if (!is_array($module_objectives)) $module_objectives = [];
                        if (!is_array($module_methods)) $module_methods = [];
                        if (!is_array($module_materials)) $module_materials = [];
                        if (!is_array($module_media)) $module_media = [];
                        
                        // Get lessons for this module
                        $lessons_query = new WP_Query([
                            'post_type' => 'crscribe_lesson',
                            'post_status' => ['publish', 'draft'],
                            'posts_per_page' => -1,
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
                        $lessons_count = $lessons_query->post_count;
                        ?>
                        
                        <div class="ccb-module-accordion-item" data-module-id="<?php echo esc_attr($module_id); ?>" data-module-order="<?php echo esc_attr($module_index + 1); ?>">
                            
                            <!-- Module Header -->
                            <div class="ccb-module-header">
                                <div class="ccb-module-drag-handle" title="Drag to reorder">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                                <div class="ccb-module-number">
                                    <span>Module <?php echo esc_html($module_index + 1); ?></span>
                                </div>
                                <div class="ccb-module-title-section">
                                    <h5 class="ccb-module-title" contenteditable="true" data-field="title" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php echo esc_html($module->post_title); ?>
                                    </h5>
                                    <div class="ccb-module-meta">
                                        <span class="ccb-module-duration">
                                            <i class="fas fa-clock"></i>
                                            <?php echo esc_html($module_duration); ?>
                                        </span>
                                        <span class="ccb-module-lessons">
                                            <i class="fas fa-play-circle"></i>
                                            <?php echo esc_html($lessons_count); ?> lessons
                                        </span>
                                        <span class="ccb-module-objectives">
                                            <i class="fas fa-target"></i>
                                            <?php echo count($module_objectives); ?> objectives
                                        </span>
                                    </div>
                                </div>
                                <div class="ccb-module-actions">
                                    <button class="ccb-action-btn-mini ccb-module-collapse-btn" 
                                            title="Toggle Module" 
                                            data-module-id="<?php echo esc_attr($module_id); ?>"
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#ccb-module-content-<?php echo esc_attr($module_id); ?>">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <button class="ccb-action-btn-mini ccb-module-edit-btn" title="Edit Module" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="ccb-action-btn-mini ccb-module-ai-btn" title="AI Generate" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                    <button class="ccb-action-btn-mini ccb-module-delete-btn" title="Delete Module" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Module Content (Collapsible) -->
                            <div class="ccb-module-content collapse" id="ccb-module-content-<?php echo esc_attr($module_id); ?>">
                                
                                <!-- Module Description -->
                                <div class="ccb-module-description">
                                    <h6 class="ccb-module-field-title">
                                        <i class="fas fa-align-left"></i>
                                        Module Description
                                    </h6>
                                    <div class="ccb-editor-container" 
                                         id="ccb-module-content-editor-<?php echo esc_attr($module_id); ?>"
                                         data-field="content" 
                                         data-module-id="<?php echo esc_attr($module_id); ?>"
                                         data-editor-type="module"
                                         data-content="<?php echo esc_attr($module->post_content ?: 'Add a description for this module...'); ?>">
                                    </div>
                                </div>
                                
                                <!-- Module Objectives -->
                                <?php if (!empty($module_objectives)): ?>
                                <div class="ccb-module-objectives">
                                    <h6 class="ccb-module-field-title">
                                        <i class="fas fa-target"></i>
                                        Learning Objectives
                                        <button class="ccb-add-objective-btn" title="Add Objective" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </h6>
                                    <div class="ccb-objectives-list" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php foreach ($module_objectives as $obj_index => $objective): ?>
                                        <div class="ccb-objective-item" data-objective-index="<?php echo esc_attr($obj_index); ?>">
                                            <div class="ccb-objective-drag-handle">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>
                                            <div class="ccb-objective-content">
                                                <div class="ccb-objective-thinking-skill">
                                                    <select class="ccb-thinking-skill-select" data-field="thinking_skill">
                                                        <option value="remember" <?php selected($objective['thinking_skill'] ?? '', 'remember'); ?>>Remember</option>
                                                        <option value="understand" <?php selected($objective['thinking_skill'] ?? '', 'understand'); ?>>Understand</option>
                                                        <option value="apply" <?php selected($objective['thinking_skill'] ?? '', 'apply'); ?>>Apply</option>
                                                        <option value="analyze" <?php selected($objective['thinking_skill'] ?? '', 'analyze'); ?>>Analyze</option>
                                                        <option value="evaluate" <?php selected($objective['thinking_skill'] ?? '', 'evaluate'); ?>>Evaluate</option>
                                                        <option value="create" <?php selected($objective['thinking_skill'] ?? '', 'create'); ?>>Create</option>
                                                    </select>
                                                </div>
                                                <input type="text" 
                                                       class="ccb-objective-description" 
                                                       data-field="description"
                                                       value="<?php echo esc_attr($objective['description'] ?? ''); ?>"
                                                       placeholder="Enter learning objective...">
                                            </div>
                                            <button class="ccb-objective-delete-btn" title="Delete Objective">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Teaching Methods -->
                                <div class="ccb-module-methods">
                                    <h6 class="ccb-module-field-title">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        Teaching Methods
                                        <button class="ccb-add-method-btn" title="Add Method" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </h6>
                                    <div class="ccb-methods-list" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php if (!empty($module_methods)): ?>
                                            <?php foreach ($module_methods as $method_index => $method): ?>
                                            <div class="ccb-method-item" data-method-index="<?php echo esc_attr($method_index); ?>">
                                                <div class="ccb-method-drag-handle">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                                <input type="text" 
                                                       class="ccb-method-input" 
                                                       value="<?php echo esc_attr($method); ?>"
                                                       placeholder="e.g., Lecture, Discussion, Hands-on Activity, Group Work...">
                                                <button class="ccb-method-delete-btn" title="Remove Method">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="ccb-empty-methods">
                                            <p>No teaching methods specified. <a href="#" class="ccb-add-method-link" data-module-id="<?php echo esc_attr($module_id); ?>">Add a method</a></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Materials & Resources -->
                                <div class="ccb-module-materials">
                                    <h6 class="ccb-module-field-title">
                                        <i class="fas fa-book"></i>
                                        Materials & Resources
                                        <button class="ccb-add-material-btn" title="Add Material" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </h6>
                                    <div class="ccb-materials-list" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php if (!empty($module_materials)): ?>
                                            <?php foreach ($module_materials as $material_index => $material): ?>
                                            <div class="ccb-material-item" data-material-index="<?php echo esc_attr($material_index); ?>">
                                                <div class="ccb-material-drag-handle">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                                <div class="ccb-material-content">
                                                    <input type="text" 
                                                           class="ccb-material-title" 
                                                           value="<?php echo esc_attr($material['title'] ?? ''); ?>"
                                                           placeholder="Resource title...">
                                                    <input type="text" 
                                                           class="ccb-material-description" 
                                                           value="<?php echo esc_attr($material['description'] ?? ''); ?>"
                                                           placeholder="Resource description or URL...">
                                                </div>
                                                <button class="ccb-material-delete-btn" title="Remove Material">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="ccb-empty-materials">
                                            <p>No materials specified. <a href="#" class="ccb-add-material-link" data-module-id="<?php echo esc_attr($module_id); ?>">Add a material</a></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Media & Multimedia -->
                                <div class="ccb-module-media">
                                    <h6 class="ccb-module-field-title">
                                        <i class="fas fa-photo-video"></i>
                                        Media & Multimedia
                                        <button class="ccb-add-media-btn" title="Add Media" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </h6>
                                    <div class="ccb-media-list" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php if (!empty($module_media)): ?>
                                            <?php foreach ($module_media as $media_index => $media): ?>
                                            <div class="ccb-media-item" data-media-index="<?php echo esc_attr($media_index); ?>">
                                                <div class="ccb-media-drag-handle">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                                <div class="ccb-media-content">
                                                    <select class="ccb-media-type">
                                                        <option value="video" <?php selected($media['type'] ?? '', 'video'); ?>>Video</option>
                                                        <option value="audio" <?php selected($media['type'] ?? '', 'audio'); ?>>Audio</option>
                                                        <option value="image" <?php selected($media['type'] ?? '', 'image'); ?>>Image</option>
                                                        <option value="document" <?php selected($media['type'] ?? '', 'document'); ?>>Document</option>
                                                        <option value="presentation" <?php selected($media['type'] ?? '', 'presentation'); ?>>Presentation</option>
                                                    </select>
                                                    <input type="text" 
                                                           class="ccb-media-title" 
                                                           value="<?php echo esc_attr($media['title'] ?? ''); ?>"
                                                           placeholder="Media title...">
                                                    <input type="text" 
                                                           class="ccb-media-url" 
                                                           value="<?php echo esc_attr($media['url'] ?? ''); ?>"
                                                           placeholder="Media URL or file path...">
                                                </div>
                                                <button class="ccb-media-delete-btn" title="Remove Media">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="ccb-empty-media">
                                            <p>No media specified. <a href="#" class="ccb-add-media-link" data-module-id="<?php echo esc_attr($module_id); ?>">Add media</a></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Lessons Section -->
                                <div class="ccb-module-lessons">
                                    <h6 class="ccb-module-field-title">
                                        <i class="fas fa-play-circle"></i>
                                        Lessons (<?php echo esc_html($lessons_count); ?>)
                                        <button class="ccb-add-lesson-btn" title="Add Lesson" data-module-id="<?php echo esc_attr($module_id); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </h6>
                                    
                                    <?php if ($lessons_query->have_posts()): ?>
                                    <div class="ccb-lessons-list" data-module-id="<?php echo esc_attr($module_id); ?>">
                                        <?php $lesson_index = 0; while ($lessons_query->have_posts()): $lessons_query->the_post(); ?>
                                            <?php
                                            $lesson = get_post();
                                            $lesson_id = $lesson->ID;
                                            $lesson_objectives = get_post_meta($lesson_id, '_lesson_objectives', true);
                                            $lesson_duration = get_post_meta($lesson_id, '_lesson_duration', true) ?: '45 minutes';
                                            $teaching_points = get_post_meta($lesson_id, '_teaching_points', true);
                                            
                                            // Ensure arrays are properly formatted
                                            if (!is_array($lesson_objectives)) $lesson_objectives = [];
                                            if (!is_array($teaching_points)) $teaching_points = [];
                                            ?>
                                            
                                            <div class="ccb-lesson-item" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-lesson-order="<?php echo esc_attr($lesson_index + 1); ?>">
                                                
                                                <!-- Lesson Header -->
                                                <div class="ccb-lesson-header">
                                                    <div class="ccb-lesson-drag-handle" title="Drag to reorder">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </div>
                                                    <div class="ccb-lesson-number">
                                                        <span>Lesson <?php echo esc_html($lesson_index + 1); ?></span>
                                                    </div>
                                                    <div class="ccb-lesson-title-section">
                                                        <div class="ccb-lesson-title" 
                                                             contenteditable="true" 
                                                             data-field="title" 
                                                             data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                                            <?php echo esc_html($lesson->post_title); ?>
                                                        </div>
                                                        <div class="ccb-lesson-meta">
                                                            <span class="ccb-lesson-duration">
                                                                <i class="fas fa-clock"></i>
                                                                <?php echo esc_html($lesson_duration); ?>
                                                            </span>
                                                            <span class="ccb-lesson-teaching-points">
                                                                <i class="fas fa-lightbulb"></i>
                                                                <?php echo count($teaching_points); ?> teaching points
                                                            </span>
                                                            <span class="ccb-lesson-objectives">
                                                                <i class="fas fa-target"></i>
                                                                <?php echo count($lesson_objectives); ?> objectives
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ccb-lesson-actions">
                                                        <button class="ccb-action-btn-tiny ccb-lesson-collapse-btn" 
                                                                title="Toggle Lesson" 
                                                                data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
                                                                data-bs-toggle="collapse" 
                                                                data-bs-target="#ccb-lesson-content-<?php echo esc_attr($lesson_id); ?>">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                        <button class="ccb-action-btn-tiny ccb-lesson-edit-btn" title="Edit Lesson" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="ccb-action-btn-tiny ccb-lesson-ai-btn" title="AI Generate" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                                            <i class="fas fa-magic"></i>
                                                        </button>
                                                        <button class="ccb-action-btn-tiny ccb-lesson-delete-btn" title="Delete Lesson" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <!-- Lesson Content (Collapsible) -->
                                                <div class="ccb-lesson-content collapse" id="ccb-lesson-content-<?php echo esc_attr($lesson_id); ?>">
                                                    
                                                    <!-- Lesson Overview -->
                                                    <div class="ccb-lesson-overview">
                                                        <div class="ccb-lesson-field-title">
                                                            <i class="fas fa-align-left"></i>
                                                            Lesson Overview
                                                        </div>
                                                        <div class="ccb-editor-container" 
                                                             id="ccb-lesson-content-editor-<?php echo esc_attr($lesson_id); ?>"
                                                             data-field="content" 
                                                             data-lesson-id="<?php echo esc_attr($lesson_id); ?>"
                                                             data-editor-type="lesson"
                                                             data-content="<?php echo esc_attr($lesson->post_content ?: 'Add lesson overview...'); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Teaching Points -->
                                                    <div class="ccb-lesson-teaching-points">
                                                        <div class="ccb-lesson-field-title">
                                                            <i class="fas fa-lightbulb"></i>
                                                            Teaching Points
                                                            <button class="ccb-add-teaching-point-btn" title="Add Teaching Point" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                        <div class="ccb-teaching-points-list" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">
                                                            <?php if (!empty($teaching_points)): ?>
                                                                <?php foreach ($teaching_points as $tp_index => $teaching_point): ?>
                                                                <div class="ccb-teaching-point-item" data-teaching-point-index="<?php echo esc_attr($tp_index); ?>">
                                                                    <div class="ccb-teaching-point-drag-handle">
                                                                        <i class="fas fa-grip-vertical"></i>
                                                                    </div>
                                                                    <div class="ccb-teaching-point-content">
                                                                        <input type="text" 
                                                                               class="ccb-teaching-point-title" 
                                                                               value="<?php echo esc_attr($teaching_point['title'] ?? ''); ?>"
                                                                               placeholder="Teaching point title...">
                                                                        <textarea class="ccb-teaching-point-description" 
                                                                                  placeholder="Detailed explanation of the teaching point..."
                                                                                  rows="2"><?php echo esc_textarea($teaching_point['description'] ?? ''); ?></textarea>
                                                                        <input type="text" 
                                                                               class="ccb-teaching-point-example" 
                                                                               value="<?php echo esc_attr($teaching_point['example'] ?? ''); ?>"
                                                                               placeholder="Example or demonstration...">
                                                                        <input type="text" 
                                                                               class="ccb-teaching-point-activity" 
                                                                               value="<?php echo esc_attr($teaching_point['activity'] ?? ''); ?>"
                                                                               placeholder="Suggested activity or exercise...">
                                                                    </div>
                                                                    <button class="ccb-teaching-point-delete-btn" title="Delete Teaching Point">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                            <div class="ccb-empty-teaching-points">
                                                                <p>No teaching points defined. <a href="#" class="ccb-add-teaching-point-link" data-lesson-id="<?php echo esc_attr($lesson_id); ?>">Add a teaching point</a></p>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                            
                                        <?php $lesson_index++; endwhile; ?>
                                        <?php wp_reset_postdata(); ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="ccb-empty-lessons">
                                        <p>No lessons added yet. <a href="#" class="ccb-add-lesson-link" data-module-id="<?php echo esc_attr($module_id); ?>">Add your first lesson</a></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                            </div>
                        </div>
                        
                    <?php $module_index++; endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
                <?php else: ?>
                <div class="ccb-empty-modules">
                    <p>No modules added yet. <a href="#" class="ccb-add-module-link" data-course-id="<?php echo esc_attr($course_id); ?>">Add your first module</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Actions Footer -->
        <div class="ccb-course-actions-footer">
            <button class="ccb-btn ccb-btn-secondary ccb-course-collapse-btn" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-chevron-up"></i>
                Collapse Course
            </button>
            <button class="ccb-btn ccb-btn-primary ccb-course-generate-materials-btn" data-course-id="<?php echo esc_attr($course_id); ?>">
                <i class="fas fa-magic"></i>
                Generate Materials
            </button>
        </div>
    </div>
</div>

<style>
/* Course Card Styles */
.ccb-course-card {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    margin-bottom: var(--ccb-spacing-lg);
    transition: all var(--ccb-transition);
    overflow: hidden;
}

.ccb-course-card:hover {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 4px 12px rgba(228, 178, 111, 0.15);
}

.ccb-course-card.ccb-dragging {
    opacity: 0.7;
    transform: scale(0.98);
}

/* Course Header */
.ccb-course-header {
    display: flex;
    align-items: center;
    padding: var(--ccb-spacing-lg);
    background: var(--ccb-bg-elevated);
    border-bottom: 1px solid var(--ccb-border-color);
    gap: var(--ccb-spacing-md);
}

.ccb-course-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    padding: var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
}

.ccb-course-drag-handle:hover {
    color: var(--ccb-primary-gold);
    background: var(--ccb-hover-bg);
}

.ccb-course-drag-handle:active {
    cursor: grabbing;
}

.ccb-course-number {
    background: var(--ccb-gradient-secondary);
    color: white;
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    border-radius: var(--ccb-border-radius);
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.ccb-course-title-section {
    flex: 1;
    min-width: 0;
}

.ccb-course-title {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-primary);
    font-size: 18px;
    font-weight: 600;
    line-height: 1.3;
    outline: none;
}

.ccb-course-title:focus {
    background: rgba(228, 178, 111, 0.1);
    border-radius: var(--ccb-border-radius-sm);
    padding: 2px 4px;
}

.ccb-course-meta {
    display: flex;
    gap: var(--ccb-spacing-md);
    flex-wrap: wrap;
    align-items: center;
}

.ccb-course-meta span {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
    font-size: 12px;
    color: var(--ccb-text-muted);
}

.ccb-course-meta i {
    width: 12px;
    text-align: center;
}

.ccb-course-level {
    padding: 2px 6px;
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 500;
}

.ccb-course-level.ccb-level-beginner {
    background: rgba(40, 167, 69, 0.2);
    color: var(--ccb-success);
}

.ccb-course-level.ccb-level-intermediate {
    background: rgba(255, 193, 7, 0.2);
    color: var(--ccb-warning);
}

.ccb-course-level.ccb-level-advanced {
    background: rgba(220, 53, 69, 0.2);
    color: var(--ccb-error);
}

.ccb-course-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

.ccb-action-btn {
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

.ccb-action-btn:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-text-primary);
}

.ccb-course-ai-btn:hover {
    background: var(--ccb-primary-gold);
    color: white;
}

.ccb-course-delete-btn:hover {
    background: var(--ccb-error);
    color: white;
}

/* Course Content */
.ccb-course-content {
    padding: var(--ccb-spacing-lg);
}

.ccb-content-section-mini {
    margin-bottom: var(--ccb-spacing-lg);
}

.ccb-content-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 var(--ccb-spacing-md) 0;
    color: var(--ccb-text-primary);
    font-size: 14px;
    font-weight: 600;
}

.ccb-content-editor-mini {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    min-height: 80px;
    padding: var(--ccb-spacing-md);
    color: var(--ccb-text-primary);
    font-size: 14px;
    line-height: 1.5;
    outline: none;
}

.ccb-content-editor-mini:focus {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

.ccb-content-editor-mini:empty:before {
    content: attr(placeholder);
    color: var(--ccb-text-muted);
    font-style: italic;
}

/* Objectives */
.ccb-objectives-list {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-sm);
}

.ccb-objective-item {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-sm);
    transition: all var(--ccb-transition);
}

.ccb-objective-item:hover {
    background: var(--ccb-hover-bg);
}

.ccb-objective-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    font-size: 12px;
}

.ccb-objective-content {
    flex: 1;
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
}

.ccb-thinking-skill-select {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    color: var(--ccb-text-primary);
    font-size: 12px;
    min-width: 100px;
}

.ccb-objective-description {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--ccb-text-primary);
    font-size: 14px;
    outline: none;
}

.ccb-objective-description::placeholder {
    color: var(--ccb-text-muted);
}

.ccb-objective-delete-btn {
    background: none;
    border: none;
    color: var(--ccb-text-muted);
    cursor: pointer;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
}

.ccb-objective-delete-btn:hover {
    color: var(--ccb-error);
    background: rgba(220, 53, 69, 0.1);
}

/* Modules Accordion */
.ccb-modules-accordion {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-md);
}

.ccb-module-accordion-item {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    transition: all var(--ccb-transition);
}

.ccb-module-accordion-item:hover {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 2px 8px rgba(228, 178, 111, 0.1);
}

/* Module Header */
.ccb-module-header {
    display: flex;
    align-items: center;
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
    border-bottom: 1px solid var(--ccb-border-color);
    gap: var(--ccb-spacing-sm);
}

.ccb-module-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
}

.ccb-module-drag-handle:hover {
    color: var(--ccb-primary-gold);
    background: var(--ccb-hover-bg);
}

.ccb-module-number {
    background: var(--ccb-gradient-primary);
    color: white;
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 600;
    font-size: 11px;
    white-space: nowrap;
}

.ccb-module-title-section {
    flex: 1;
    min-width: 0;
}

.ccb-module-title {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-primary);
    font-size: 15px;
    font-weight: 600;
    line-height: 1.3;
    outline: none;
}

.ccb-module-title:focus {
    background: rgba(228, 178, 111, 0.1);
    border-radius: var(--ccb-border-radius-sm);
    padding: 2px 4px;
}

.ccb-module-meta {
    display: flex;
    gap: var(--ccb-spacing-sm);
    flex-wrap: wrap;
    align-items: center;
}

.ccb-module-meta span {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-xs);
    font-size: 11px;
    color: var(--ccb-text-muted);
    background: var(--ccb-bg-elevated);
    padding: 2px 6px;
    border-radius: var(--ccb-border-radius-sm);
}

.ccb-module-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

/* Module Content */
.ccb-module-content {
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
}

.ccb-module-field-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 var(--ccb-spacing-sm) 0;
    color: var(--ccb-text-primary);
    font-size: 13px;
    font-weight: 600;
    gap: var(--ccb-spacing-xs);
}

.ccb-module-field-title i {
    color: var(--ccb-primary-gold);
    margin-right: var(--ccb-spacing-xs);
}

.ccb-module-field-editor {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    min-height: 60px;
    padding: var(--ccb-spacing-sm);
    color: var(--ccb-text-primary);
    font-size: 13px;
    line-height: 1.5;
    outline: none;
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-module-field-editor:focus {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 2px rgba(228, 178, 111, 0.1);
}

.ccb-module-field-editor:empty:before {
    content: attr(placeholder);
    color: var(--ccb-text-muted);
    font-style: italic;
}

/* Module Description, Objectives, Methods, Materials, Media */
.ccb-module-description,
.ccb-module-objectives,
.ccb-module-methods,
.ccb-module-materials,
.ccb-module-media,
.ccb-module-lessons {
    margin-bottom: var(--ccb-spacing-md);
    padding-bottom: var(--ccb-spacing-md);
    border-bottom: 1px solid var(--ccb-border-color);
}

.ccb-module-lessons {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Methods List */
.ccb-methods-list,
.ccb-materials-list,
.ccb-media-list {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-xs);
}

.ccb-method-item,
.ccb-material-item,
.ccb-media-item {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    padding: var(--ccb-spacing-xs);
    transition: all var(--ccb-transition);
}

.ccb-method-item:hover,
.ccb-material-item:hover,
.ccb-media-item:hover {
    background: var(--ccb-hover-bg);
}

.ccb-method-drag-handle,
.ccb-material-drag-handle,
.ccb-media-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    font-size: 10px;
}

.ccb-method-input {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--ccb-text-primary);
    font-size: 12px;
    outline: none;
    padding: var(--ccb-spacing-xs);
}

.ccb-material-content,
.ccb-media-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-xs);
}

.ccb-material-title,
.ccb-material-description,
.ccb-media-title,
.ccb-media-url {
    background: transparent;
    border: none;
    color: var(--ccb-text-primary);
    font-size: 12px;
    outline: none;
    padding: var(--ccb-spacing-xs);
    border-bottom: 1px solid var(--ccb-border-color);
}

.ccb-media-type {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    padding: var(--ccb-spacing-xs);
    color: var(--ccb-text-primary);
    font-size: 11px;
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-method-delete-btn,
.ccb-material-delete-btn,
.ccb-media-delete-btn {
    background: none;
    border: none;
    color: var(--ccb-text-muted);
    cursor: pointer;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
    font-size: 10px;
}

.ccb-method-delete-btn:hover,
.ccb-material-delete-btn:hover,
.ccb-media-delete-btn:hover {
    color: var(--ccb-error);
    background: rgba(220, 53, 69, 0.1);
}

/* Empty States */
.ccb-empty-methods,
.ccb-empty-materials,
.ccb-empty-media,
.ccb-empty-lessons,
.ccb-empty-teaching-points {
    text-align: center;
    padding: var(--ccb-spacing-md);
    color: var(--ccb-text-muted);
    font-size: 12px;
    background: var(--ccb-bg-elevated);
    border-radius: var(--ccb-border-radius-sm);
    border: 1px dashed var(--ccb-border-color);
}

.ccb-add-method-link,
.ccb-add-material-link,
.ccb-add-media-link,
.ccb-add-lesson-link,
.ccb-add-teaching-point-link {
    color: var(--ccb-primary-gold);
    text-decoration: none;
}

/* Lessons List */
.ccb-lessons-list {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-sm);
}

.ccb-lesson-item {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
}

.ccb-lesson-item:hover {
    border-color: var(--ccb-secondary-brown);
    box-shadow: 0 1px 4px rgba(102, 84, 66, 0.1);
}

/* Lesson Header */
.ccb-lesson-header {
    display: flex;
    align-items: center;
    padding: var(--ccb-spacing-sm);
    background: var(--ccb-bg-card);
    border-bottom: 1px solid var(--ccb-border-color);
    gap: var(--ccb-spacing-xs);
}

.ccb-lesson-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    padding: var(--ccb-spacing-xs);
    font-size: 10px;
}

.ccb-lesson-number {
    background: var(--ccb-secondary-brown);
    color: white;
    padding: 2px var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 600;
    font-size: 10px;
    white-space: nowrap;
}

.ccb-lesson-title-section {
    flex: 1;
    min-width: 0;
}

.ccb-lesson-title {
    color: var(--ccb-text-primary);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.3;
    outline: none;
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-lesson-title:focus {
    background: rgba(102, 84, 66, 0.1);
    border-radius: var(--ccb-border-radius-sm);
    padding: 2px 4px;
}

.ccb-lesson-meta {
    display: flex;
    gap: var(--ccb-spacing-xs);
    flex-wrap: wrap;
    align-items: center;
}

.ccb-lesson-meta span {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 10px;
    color: var(--ccb-text-muted);
    background: var(--ccb-bg-elevated);
    padding: 1px 4px;
    border-radius: var(--ccb-border-radius-sm);
}

.ccb-lesson-actions {
    display: flex;
    gap: 2px;
}

.ccb-action-btn-tiny {
    width: 20px;
    height: 20px;
    border: none;
    background: transparent;
    color: var(--ccb-text-muted);
    border-radius: var(--ccb-border-radius-sm);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--ccb-transition);
    font-size: 10px;
}

.ccb-action-btn-tiny:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-text-primary);
}

/* Lesson Content */
.ccb-lesson-content {
    padding: var(--ccb-spacing-sm);
    background: var(--ccb-bg-card);
}

.ccb-lesson-field-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-primary);
    font-size: 11px;
    font-weight: 600;
    gap: var(--ccb-spacing-xs);
}

.ccb-lesson-field-title i {
    color: var(--ccb-secondary-brown);
    margin-right: var(--ccb-spacing-xs);
}

.ccb-lesson-field-editor {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    min-height: 40px;
    padding: var(--ccb-spacing-xs);
    color: var(--ccb-text-primary);
    font-size: 12px;
    line-height: 1.4;
    outline: none;
    margin-bottom: var(--ccb-spacing-sm);
}

.ccb-lesson-field-editor:focus {
    border-color: var(--ccb-secondary-brown);
    box-shadow: 0 0 0 2px rgba(102, 84, 66, 0.1);
}

.ccb-lesson-overview,
.ccb-lesson-objectives,
.ccb-lesson-teaching-points {
    margin-bottom: var(--ccb-spacing-sm);
    padding-bottom: var(--ccb-spacing-sm);
    border-bottom: 1px solid var(--ccb-border-color);
}

.ccb-lesson-teaching-points {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Teaching Points */
.ccb-teaching-points-list {
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-xs);
}

.ccb-teaching-point-item {
    display: flex;
    gap: var(--ccb-spacing-xs);
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    padding: var(--ccb-spacing-xs);
    transition: all var(--ccb-transition);
}

.ccb-teaching-point-item:hover {
    background: var(--ccb-hover-bg);
}

.ccb-teaching-point-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    font-size: 9px;
    padding: var(--ccb-spacing-xs);
}

.ccb-teaching-point-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--ccb-spacing-xs);
}

.ccb-teaching-point-title,
.ccb-teaching-point-example,
.ccb-teaching-point-activity {
    background: transparent;
    border: none;
    color: var(--ccb-text-primary);
    font-size: 11px;
    outline: none;
    padding: 2px;
    border-bottom: 1px solid var(--ccb-border-color);
}

.ccb-teaching-point-description {
    background: transparent;
    border: none;
    color: var(--ccb-text-primary);
    font-size: 11px;
    outline: none;
    padding: 2px;
    border-bottom: 1px solid var(--ccb-border-color);
    resize: vertical;
    font-family: inherit;
}

.ccb-teaching-point-delete-btn {
    background: none;
    border: none;
    color: var(--ccb-text-muted);
    cursor: pointer;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
    font-size: 9px;
}

.ccb-teaching-point-delete-btn:hover {
    color: var(--ccb-error);
    background: rgba(220, 53, 69, 0.1);
}

/* Add Buttons */
.ccb-add-method-btn,
.ccb-add-material-btn,
.ccb-add-media-btn,
.ccb-add-lesson-btn,
.ccb-add-teaching-point-btn {
    background: none;
    border: none;
    color: var(--ccb-primary-gold);
    cursor: pointer;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
    font-size: 10px;
}

.ccb-add-method-btn:hover,
.ccb-add-material-btn:hover,
.ccb-add-media-btn:hover,
.ccb-add-lesson-btn:hover,
.ccb-add-teaching-point-btn:hover {
    background: rgba(228, 178, 111, 0.1);
}

/* Editor.js Container Styles */
.ccb-editor-container {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    min-height: 100px;
    transition: all var(--ccb-transition);
    position: relative;
}

.ccb-editor-container:focus-within {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

.ccb-editor-container .codex-editor {
    background: transparent;
}

.ccb-editor-container .codex-editor__redactor {
    padding: var(--ccb-spacing-md);
    color: var(--ccb-text-primary);
    font-size: 14px;
    line-height: 1.6;
}

.ccb-editor-container .ce-block__content {
    max-width: none;
    margin: 0;
}

.ccb-editor-container .ce-paragraph[data-placeholder]:empty::before {
    color: var(--ccb-text-muted);
    font-style: italic;
}

.ccb-editor-container .ce-toolbar__content {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ccb-editor-container .ce-toolbar__actions {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
}

.ccb-editor-container .ce-toolbox {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ccb-editor-container .ce-toolbox__button {
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-toolbox__button:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-primary-gold);
}

.ccb-editor-container .ce-inline-toolbar {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-sm);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ccb-editor-container .ce-inline-tool {
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-inline-tool:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-primary-gold);
}

/* Save Indicators */
.ccb-save-indicator {
    position: absolute;
    top: var(--ccb-spacing-xs);
    right: var(--ccb-spacing-xs);
    padding: 2px var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    font-size: 10px;
    font-weight: 500;
    opacity: 0;
    transition: opacity var(--ccb-transition);
    pointer-events: none;
    z-index: 10;
}

.ccb-save-indicator.ccb-save-saving {
    background: rgba(255, 193, 7, 0.2);
    color: var(--ccb-warning);
    opacity: 1;
}

.ccb-save-indicator.ccb-save-saved {
    background: rgba(40, 167, 69, 0.2);
    color: var(--ccb-success);
    opacity: 1;
}

.ccb-save-indicator.ccb-save-error {
    background: rgba(220, 53, 69, 0.2);
    color: var(--ccb-error);
    opacity: 1;
}

/* Fallback Editor */
.ccb-fallback-editor {
    width: 100%;
    min-height: 100px;
    padding: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    color: var(--ccb-text-primary);
    font-size: 14px;
    line-height: 1.6;
    font-family: inherit;
    outline: none;
    resize: vertical;
    transition: all var(--ccb-transition);
    box-sizing: border-box;
}

.ccb-fallback-editor:focus {
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

.ccb-fallback-active {
    border: 2px dashed var(--ccb-warning);
    position: relative;
}

.ccb-fallback-active::before {
    content: '⚠️ Fallback Editor Active';
    position: absolute;
    top: -20px;
    left: 0;
    font-size: 10px;
    color: var(--ccb-warning);
    background: var(--ccb-bg-card);
    padding: 2px 6px;
    border-radius: 3px;
}

/* Editor Loading States */
.ccb-editor-container:not(.ccb-editor-ready):not(.ccb-fallback-active) {
    position: relative;
    min-height: 60px;
}

.ccb-editor-container:not(.ccb-editor-ready):not(.ccb-fallback-active)::after {
    content: 'Loading editor...';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--ccb-text-muted);
    font-size: 12px;
    font-style: italic;
}

.ccb-editor-ready {
    /* Editor is successfully loaded */
}

/* Editor.js Dark Theme Adjustments */
.ccb-editor-container .ce-block {
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-header {
    color: var(--ccb-text-primary);
    font-weight: 600;
}

.ccb-editor-container .ce-paragraph {
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-list {
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-quote {
    border-left: 3px solid var(--ccb-primary-gold);
    background: var(--ccb-bg-elevated);
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-warning {
    background: rgba(255, 193, 7, 0.1);
    border-left: 3px solid var(--ccb-warning);
    color: var(--ccb-text-primary);
}

.ccb-editor-container .ce-code {
    background: var(--ccb-bg-elevated);
    color: var(--ccb-text-primary);
    border: 1px solid var(--ccb-border-color);
}

.ccb-editor-container .ce-delimiter {
    color: var(--ccb-text-muted);
}

.ccb-editor-container .cdx-marker {
    background: rgba(228, 178, 111, 0.3);
    color: var(--ccb-text-primary);
}

/* Editor.js Module/Lesson Specific Styles */
.ccb-editor-container[data-editor-type="module"] {
    min-height: 80px;
}

.ccb-editor-container[data-editor-type="lesson"] {
    min-height: 120px;
}

.ccb-editor-container[data-editor-type="simple"] {
    min-height: 60px;
}

.ccb-editor-container[data-editor-type="overview"],
.ccb-editor-container[data-editor-type="objectives"],
.ccb-editor-container[data-editor-type="assessment"] {
    min-height: 200px;
}

.ccb-module-item {
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-md);
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    transition: all var(--ccb-transition);
}

.ccb-module-item:hover {
    background: var(--ccb-hover-bg);
}

.ccb-module-drag-handle {
    color: var(--ccb-text-muted);
    cursor: grab;
    font-size: 12px;
}

.ccb-module-info {
    flex: 1;
    min-width: 0;
}

.ccb-module-title {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-primary);
    font-size: 14px;
    font-weight: 600;
    outline: none;
}

.ccb-module-title:focus {
    background: rgba(228, 178, 111, 0.1);
    border-radius: var(--ccb-border-radius-sm);
    padding: 2px 4px;
}

.ccb-module-summary {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-muted);
    font-size: 12px;
    line-height: 1.4;
}

.ccb-module-meta span {
    font-size: 11px;
    color: var(--ccb-text-muted);
    background: var(--ccb-bg-card);
    padding: 2px 6px;
    border-radius: var(--ccb-border-radius-sm);
}

.ccb-module-actions {
    display: flex;
    gap: var(--ccb-spacing-xs);
}

.ccb-action-btn-mini {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: var(--ccb-text-muted);
    border-radius: var(--ccb-border-radius-sm);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--ccb-transition);
    font-size: 12px;
}

.ccb-action-btn-mini:hover {
    background: var(--ccb-hover-bg);
    color: var(--ccb-text-primary);
}

.ccb-module-delete-btn:hover {
    background: var(--ccb-error);
    color: white;
}

/* Empty State */
.ccb-empty-modules {
    text-align: center;
    padding: var(--ccb-spacing-xl);
    color: var(--ccb-text-muted);
    font-size: 14px;
}

.ccb-add-module-link {
    color: var(--ccb-primary-gold);
    text-decoration: none;
}

.ccb-add-module-link:hover {
    text-decoration: underline;
}

/* Course Actions Footer */
.ccb-course-actions-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--ccb-spacing-lg);
    border-top: 1px solid var(--ccb-border-color);
    margin-top: var(--ccb-spacing-lg);
}

/* Add/Action Buttons */
.ccb-add-objective-btn,
.ccb-add-module-btn {
    background: none;
    border: none;
    color: var(--ccb-primary-gold);
    cursor: pointer;
    padding: var(--ccb-spacing-xs);
    border-radius: var(--ccb-border-radius-sm);
    transition: all var(--ccb-transition);
    font-size: 12px;
}

.ccb-add-objective-btn:hover,
.ccb-add-module-btn:hover {
    background: rgba(228, 178, 111, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .ccb-course-header {
        flex-wrap: wrap;
        gap: var(--ccb-spacing-sm);
    }
    
    .ccb-course-title-section {
        width: 100%;
        order: 3;
    }
    
    .ccb-course-meta {
        gap: var(--ccb-spacing-sm);
    }
    
    .ccb-course-meta span {
        font-size: 11px;
    }
    
    .ccb-objective-content {
        flex-direction: column;
        align-items: stretch;
        gap: var(--ccb-spacing-xs);
    }
    
    .ccb-course-actions-footer {
        flex-direction: column;
        gap: var(--ccb-spacing-md);
    }
}

/* Animation States */
.ccb-course-card.ccb-expanded .ccb-course-content {
    display: block;
}

.ccb-course-card.ccb-collapsed .ccb-course-content {
    display: none;
}

.ccb-course-collapse-btn i {
    transition: transform var(--ccb-transition);
}

.ccb-course-card.ccb-collapsed .ccb-course-collapse-btn i {
    transform: rotate(180deg);
}
</style>