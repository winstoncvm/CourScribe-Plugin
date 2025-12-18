<?php
// courscribe-dashboard/templates/teaching-points.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lessons template
 *
 * @param array $args {
 *     @type int    $course_id         Course post ID
 *     @type string $course_title      Course title
 *     @type int    $curriculum_id     Curriculum post ID
 *     @type object $tooltips          CourScribe_Tooltips instance
 *     @type string $site_url          Site URL for assets
 * }
 */
function courscribe_render_teaching_points($args = []) {
    // Default values
    $defaults = [
        'course_id'     => 0,
        'course_title'  => '',
        'curriculum_id' => 0,
        'tooltips'      => null,
        'site_url'      => home_url(),
    ];

    $args = wp_parse_args($args, $defaults);
    $course_id = absint($args['course_id']);
    $course_title = esc_html($args['course_title']);
    $curriculum_id = absint($args['curriculum_id']);
    $tooltips = $args['tooltips'];
    $site_url = esc_url_raw($args['site_url']);

     // Determine user roles
    $current_user = wp_get_current_user();
    $is_client = in_array('client', (array) $current_user->roles);
    $is_studio_admin = in_array('studio_admin', (array) $current_user->roles);
    $is_collaborator = in_array('collaborator', (array) $current_user->roles);
    $can_view_feedback = $is_studio_admin || $is_collaborator;

    if (!$course_id || !$tooltips instanceof CourScribe_Tooltips) {
        return; // Exit if required args are missing
    }

    // Fetch course meta
    $course_goal = esc_html(get_post_meta($course_id, '_class_goal', true));
    ?>

    <div class="courscribe-teachingPoints">

        <div style="background: #2F2E30; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #2F2E30; width: 100%; box-sizing: border-box;">
            <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" style="width: 24px; height: 24px;">

            <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">
                                                            Course Goal:
                                                        </span>

            <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?php echo esc_html(get_post_meta($course_id, '_class_goal', true)); ?>
                                                        </span>
        </div>
        <?php
        // Fetch all modules from the course custom field
        $modules = get_posts([
            'post_type' => 'crscribe_module',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_course_id',
                    'value' => $course_id,
                    'compare' => '=',
                ],
            ],
        ]);
        // Check if modules exist
        if ($modules) {
            // Create the module tabs container
            ?>
            <div class="module-tabs-teachingPoint-module">
                <div class="tab-links-teachingPoint-module">
                    <?php
                    $tab_index = 1;
                    foreach ($modules as $module) {
                        ?>
                        <button class="tab-link-teachingPoint-module <?php echo $tab_index === 1 ? 'active' : ''; ?>" data-tab="tab-teachingPoint-module-<?php echo $module->ID; ?>">
                            <span> Module <?php echo $tab_index; ?>:<span><?php echo esc_html($module->post_title); ?></span></span>
                        </button>
                        <?php
                        $tab_index++;
                    }
                    ?>
                </div>
                <div class="tab-contents">
                    <?php
                    // Reset index for lessons in each module
                    $tab_index = 1;
                    foreach ($modules as $module) {
                        ?>
                        <div class="tab-content-teachingPoint-module <?php echo $tab_index === 1 ? 'active' : ''; ?>" id="tab-teachingPoint-module-<?php echo $module->ID; ?>">

                            <div style="background: #2F2E30; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #2F2E30; width: 100%; box-sizing: border-box;">
                                <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" style="width: 24px; height: 24px;">

                                <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">
                                                            Module Goal:
                                                        </span>

                                <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?php echo esc_html(get_post_meta($module->ID, 'module-goal', true)); ?>
                                                        </span>
                            </div>
                            <!-- Child Modules Section (Tabs) -->
                           

                            <div class="lesson-xy-acc lessons" id="lessonsAccordion-<?php echo $module->ID; ?>">
                                <?php
                                // Fetch lessons for the current module
                                $lessons = get_posts([
                                    'post_type' => 'crscribe_lesson',
                                    'post_status' => 'publish',
                                    'numberposts' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => '_module_id',
                                            'value' => $module->ID,
                                            'compare' => '=',
                                        ],
                                    ],
                                ]);
                                $lessons = is_array($lessons) ? $lessons : [];
                                $lesson_index = 1;
                                if ($lessons) {
                                    foreach ($lessons as $lesson) {
                                        ?>
                                        <div class="lesson-xy-acc-item accordion-item lesson-item ml-2 mr-2">
                                            <div class="lesson-xy-acc_title accordion-header-lesson" id="heading-lesson-<?php echo $lesson->ID; ?>">
                                                <span style="color: #E5C9A6; font-size: 16px; font-weight: 600; letter-spacing: 0.5px; padding-left: 1rem;">Lesson <?php echo $lesson_index; ?>: <?php echo esc_html($lesson->post_title); ?></span>
                                                <button class="accordion-button lesson" style="background: transparent!important;" type="button"   >
                                                    <i class="fa fa-chevron-down me-2 custom-icon"></i>
                                                </button>

                                            </div>
                                            <div id="collapse-lesson-<?php echo $lesson->ID; ?>" class="lesson-xy-acc_panel lesson-xy-acc_panel_col">
                                                <div class="accordion-body">
                                                    <div style="background: #3F3935; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #5D4F42; width: 100%; box-sizing: border-box;">
                                                        <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/Vector.png" alt="Icon" style="width: 24px; height: 24px;">

                                                        <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">
                                                            Lesson Goal:
                                                        </span>

                                                            <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?php echo esc_html(get_post_meta($lesson->ID, 'lesson-goal', true)); ?>
                                                        </span>
                                                    </div>
                                                    <!-- Teaching Points -->
                                                    <div class="teaching-points-section">
                                                        <div class="courscribe-header-with-divider mb-2">
                                                            <span class="courscribe-title">Teaching Points:</span>
                                                            <div class="courscribe-divider"></div>
                                                        </div>
                                                        
                                                        <ol id="teaching-points-list-<?php echo $lesson->ID; ?>" class="ol-days" style="--month:'Teaching Point'">
                                                            <?php
                                                            $teaching_points = get_post_meta($lesson->ID, '_teaching_points', true);
                                                            if (!empty($teaching_points) && is_array($teaching_points)) {
                                                                foreach ($teaching_points as $index => $point) {
                                                                    ?>
                                                                    <li data-point-id="point-<?php echo $lesson->ID; ?>-<?php echo $index; ?>">
                                                                        <span class="ol-text">
                                                                            <?php echo esc_html($point); ?>
                                                                        </span>
                                                                        <div class="ol-actions">
                                                                            <button class="ol-icon-btn edit-teaching-point" title="Edit">✏️</button>
                                                                            <button class="ol-icon-btn delete-teaching-point" title="Delete">🗑️</button>
                                                                        </div>
                                                                    </li>
                                                                    <?php
                                                                }
                                                            } else {
                                                                echo '<li>No teaching points added yet.</li>';
                                                            }
                                                            ?>
                                                        </ol>

                                                        
                                                        
                                                        <?php if (!$is_client) : ?>
                                                        <h4 class="courscribe-heading mt-3 mb-3">Add New Teaching Points</h4>
                                                        <div class="courscribe-header-with-divider mb-2">
                                                            <span class="courscribe-title-sm">AI Teaching Point Suggestions</span>
                                                            <div class="courscribe-divider"></div>
                                                        </div>
                                                        <!-- AI Input Section -->
  
                                                        <div class="AI-Input" data-lesson-id="<?php echo $lesson->ID; ?>">
                                                            <input id="voice" type="checkbox" />
                                                            <label for="voice">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="var(--neutral-color)" viewBox="0 0 16 16">
                                                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                                                </svg>
                                                            </label>
                                                            <input id="mic-<?php echo $lesson->ID; ?>" class="mic" type="checkbox" />
                                                            <label for="mic-<?php echo $lesson->ID; ?>">
                                                                <svg viewBox="0 0 16 16" fill="var(--neutral-color)" height="30" width="30" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M3.5 6.5A.5.5 0 0 1 4 7v1a4 4 0 0 0 8 0V7a.5.5 0 0 1 1 0v1a5 5 0 0 1-4.5 4.975V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 .5-.5"/>
                                                                    <path d="M10 8a2 2 0 1 1-4 0V3a2 2 0 1 1 4 0zM8 0a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V3a3 3 0 0 0-3-3"/>
                                                                </svg>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="var(--neutral-color)" viewBox="0 0 16 16">
                                                                    <path d="M13 8c0 .564-.094 1.107-.266 1.613l-.814-.814A4 4 0 0 0 12 8V7a.5.5 0 0 1 1 0zm-5 4c.818 0 1.578-.245 2.212-.667l.718.719a5 5 0 0 1-2.43.923V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 1 0v1a4 4 0 0 0 4 4m3-9v4.879l-1-1V3a2 2 0 0 0-3.997-.118l-.845-.845A3.001 3.001 0 0 1 11 3"/>
                                                                    <path d="m9.486 10.607-.748-.748A2 2 0 0 1 6 8v-.878l-1-1V8a3 3 0 0 0 4.486 2.607m-7.84-9.253 12 12 .708-.708-12-12z"/>
                                                                </svg>
                                                            </label>
                                                            <div class="chat-marquee" id="chat-marquee-<?php echo $lesson->ID; ?>">
                                                            </div>
                                                            <div class="chat-container">
                                                                <label for="chat-input-<?php echo $lesson->ID; ?>" class="chat-wrapper">
                                                                    <p id="status-<?php echo $lesson->ID; ?>">Ready</p>
                                                                    <textarea class="chat-input" id="chat-input-<?php echo $lesson->ID; ?>" name="chat-input-<?php echo $lesson->ID; ?>" placeholder="Add new teaching point..."></textarea>
                                                                    <div class="transcription-prompt">
                                                                        <div class="transcription-prompt-buttons">
                                                                            <button class="append-button">Append</button>
                                                                            <button class="replace-button">Replace</button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="button-bar">
                                                                        <div class="left-buttons">
                                                                            <input id="search" class="search-checkbox" type="checkbox" data-lesson-id="<?php echo $lesson->ID; ?>" data-lesson-name="<?php echo esc_html($lesson->post_title); ?>" data-lesson-goal=" <?php echo esc_html(get_post_meta($lesson->ID, 'lesson-goal', true)); ?>" />
                                                                            <label for="search">
                                                                                <svg viewBox="0 0 16 16" fill="var(--neutral-color)" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                                                                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855q-.215.403-.395.872c.705.157 1.472.257 2.282.287zM4.249 3.539q.214-.577.481-1.078a7 7 0 0 1 .597-.933A7 7 0 0 0 3.051 3.05q.544.277 1.198.49zM3.509 7.5c.036-1.07.188-2.087.436-3.008a9 9 0 0 1-1.565-.667A6.96 6.96 0 0 0 1.018 7.5zm1.4-2.741a12.3 12.3 0 0 0-.4 2.741H7.5V5.091c-.91-.03-1.783-.145-2.591-.332M8.5 5.09V7.5h2.99a12.3 12.3 0 0 0-.399-2.741c-.808.187-1.681.301-2.591.332zM4.51 8.5c.035.987.176 1.914.399 2.741A13.6 13.6 0 0 1 7.5 10.91V8.5zm3.99 0v2.409c.91.03 1.783.145 2.591.332.223-.827.364-1.754.4-2.741zm-3.282 3.696q.18.469.395.872c.552 1.035 1.218 1.65 1.887 1.855V11.91c-.81.03-1.577.13-2.282.287zm.11 2.276a7 7 0 0 1-.598-.933 9 9 0 0 1-.481-1.079 8.4 8.4 0 0 0-1.198.49 7 7 0 0 0 2.276 1.522zm-1.383-2.964A13.4 13.4 0 0 1 3.508 8.5h-2.49a6.96 6.96 0 0 0 1.362 3.675c.47-.258.995-.482 1.565-.667m6.728 2.964a7 7 0 0 0 2.275-1.521 8.4 8.4 0 0 0-1.197-.49 9 9 0 0 1-.481 1.078 7 7 0 0 1-.597.933M8.5 11.909v3.014c.67-.204 1.335-.82 1.887-1.855q.216-.403.395-.872A12.6 12.6 0 0 0 8.5 11.91zm3.555-.401c.57.185 1.095.409 1.565.667A6.96 6.96 0 0 0 14.982 8.5h-2.49a13.4 13.4 0 0 1-.437 3.008M14.982 7.5a6.96 6.96 0 0 0-1.362-3.675c-.47.258-.995.482-1.565.667.248.92.4 1.938.437 3.008zM11.27 2.461q.266.502.482 1.078a8.4 8.4 0 0 0 1.196-.49 7 7 0 0 0-2.275-1.52c.218.283.418.597.597.932m-.488 1.343a8 8 0 0 0-.395-.872C9.835 1.897 9.17 1.282 8.5 1.077V4.09c.81-.03 1.577-.13 2.282-.287z"/>
                                                                                </svg>
                                                                            </label>
                                                                        </div>
                                                                        <div class="right-buttons">
                                                                           
                                                                            <label for="voice">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="var(--neutral-color)" viewBox="0 0 16 16">
                                                                                    <path d="M3.5 6.5A.5.5 0 0 1 4 7v1a4 4 0 0 0 8 0V7a.5.5 0 0 1 1 0v1a5 5 0 0 1-4.5 4.975V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 .5-.5"/>
                                                                                    <path d="M10 8a2 2 0 1 1-4 0V3a2 2 0 1 1 4 0zM8 0a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V3a3 3 0 0 0-3-3"/>
                                                                                </svg>
                                                                            </label>
                                                                            <button class="save-teaching-point">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="var(--neutral-color)" viewBox="0 0 16 16">
                                                                                    <path d="M16 8A8 8 0 1 0 0 8a8 8 0 0 0 16 0m-7.5 3.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707z"></path>
                                                                                </svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Save and Delete Buttons -->
                                                        <div class="objective-row mt-2 mb-2">
                                                            <?php if (!$is_client) : ?>
                                                            <button class="btn courscribe-stepper-prevBtn save-lesson-teaching-points" data-lesson-id="<?php echo $lesson->ID; ?>" data-module-id="<?php echo $module->ID; ?>">
                                                                <span class="texst">Save Teaching Points</span> 
                                                            </button>
                                                            <?php endif ?>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </div>

                                        </div>
                                        <?php
                                        $lesson_index++;
                                    }
                                } else {
                                    echo '<p>No lessons added yet.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                        $tab_index++;
                    }
                    ?>
                </div>
            </div>
            <?php
        } else {
            echo '<p>No modules available for this course.</p>';
        }
        ?>
    </div>
    <?php
}