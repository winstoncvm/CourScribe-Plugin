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
function courscribe_render_curriculum_preview($args = []) {
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
                            <div class="module-preview-additional-fields mt-4">
                                <h6 style="margin-left: 1rem;">Additional Details:</h6>
                                <!-- Methods-->
                                <div>
                                    <div class="courscribe-header-with-divider">
                                        <span class="courscribe-title-sm">Methods</span>
                                        <div class="courscribe-divider"></div>
                                    </div>
                                    <ul id="module-methods-list-<?php echo $module->ID; ?>" class="methods-list">
                                        <?php
                                        $methods = maybe_unserialize(get_post_meta($module->ID, '_module_methods', true)) ?: [];
                                        if (!empty($methods) && is_array($methods)) {
                                            foreach ($methods as $index => $method) {
                                                $method_type = isset($method['method_type']) ? esc_html($method['method_type']) : '';
                                                $title = isset($method['title']) ? esc_html($method['title']) : '';
                                                $location = isset($method['location']) ? esc_html($method['location']) : '';
                                                ?>
                                                <li class="moodule-conteiner-body methods-container mb-2" data-method-index="<?php echo $index; ?>">
                                                    <div class="module-body">
                                                        <h6>Method <?php echo $index + 1; ?></h6>
                                                        <div class="module-method-preview">
                                                            <span class="method-type-preview"><?php echo $method_type; ?></span>
                                                            <span class="method-title-preview" title="<?php echo esc_attr($title); ?>"><?php echo esc_html(wp_trim_words($title, 4, '...')); ?></span>
                                                            <div class="method-location-container">
                                                                <a href="<?php echo esc_url($location); ?>" target="_blank" class="method-location-preview"><?php echo $location; ?></a>
                                                                <button class="copy-link-btn" data-url="<?php echo esc_url($location); ?>" title="Copy link to clipboard">
                                                                    <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M6.22519 13.4862L5.36457 14.3416C5.00403 14.6965 4.51843 14.8954 4.01254 14.8954C3.50665 14.8954 3.02105 14.6965 2.66051 14.3416C2.48472 14.1675 2.34518 13.9603 2.24995 13.732C2.15472 13.5036 2.10569 13.2587 2.10569 13.0113C2.10569 12.7639 2.15472 12.519 2.24995 12.2906C2.34518 12.0623 2.48472 11.8551 2.66051 11.681L5.82676 8.54129C6.48232 7.88997 7.71694 6.93158 8.61688 7.8241C8.71372 7.92737 8.83038 8.01006 8.95989 8.06726C9.0894 8.12445 9.2291 8.15497 9.37066 8.15699C9.51222 8.15901 9.65274 8.13248 9.78382 8.079C9.91491 8.02552 10.0339 7.94618 10.1336 7.84572C10.2334 7.74525 10.3119 7.62572 10.3644 7.49425C10.417 7.36279 10.4425 7.22208 10.4394 7.08054C10.4364 6.93899 10.4049 6.79951 10.3468 6.67041C10.2887 6.54131 10.2051 6.42524 10.1012 6.32913C8.57331 4.81186 6.31232 5.09236 4.34351 7.04633L1.17726 10.1871C0.803147 10.556 0.506339 10.9957 0.3042 11.4806C0.102061 11.9656 -0.00135126 12.4859 1.33308e-05 13.0113C-0.00129194 13.5367 0.102147 14.057 0.304283 14.5419C0.506419 15.0268 0.803195 15.4666 1.17726 15.8355C1.93131 16.5834 2.95099 17.0022 4.01307 17C5.04051 17 6.06794 16.6122 6.84888 15.8355L7.71057 14.9801C7.80883 14.883 7.88694 14.7673 7.94041 14.6399C7.99388 14.5124 8.02166 14.3757 8.02216 14.2375C8.02265 14.0993 7.99585 13.9623 7.94329 13.8345C7.89073 13.7067 7.81345 13.5905 7.71588 13.4926C7.51864 13.2949 7.25116 13.1832 6.97185 13.182C6.69254 13.1808 6.42411 13.2902 6.22519 13.4862ZM15.8217 1.2843C14.178 -0.345597 11.8809 -0.433786 10.3615 1.07499L9.28944 2.13963C9.09134 2.33632 8.97949 2.60366 8.97849 2.88282C8.9775 3.16199 9.08744 3.43011 9.28413 3.62822C9.48082 3.82632 9.74815 3.93817 10.0273 3.93917C10.3065 3.94017 10.5746 3.83022 10.7727 3.63353L11.8458 2.56995C12.6321 1.78794 13.6637 2.112 14.3384 2.77927C14.6954 3.13521 14.8931 3.60697 14.8931 4.10954C14.8931 4.61211 14.6954 5.08386 14.3384 5.43875L10.9597 8.78992C9.41481 10.321 8.69019 9.60381 8.381 9.29674C8.28291 9.19935 8.1666 9.12223 8.03871 9.06979C7.91082 9.01735 7.77385 8.99061 7.63562 8.9911C7.4974 8.9916 7.36062 9.01931 7.23311 9.07267C7.10559 9.12602 6.98983 9.20397 6.89244 9.30206C6.79505 9.40015 6.71793 9.51646 6.66549 9.64436C6.61305 9.77225 6.58631 9.90922 6.58681 10.0475C6.5873 10.1857 6.61502 10.3225 6.66837 10.45C6.72172 10.5775 6.79966 10.6933 6.89775 10.7906C7.6075 11.494 8.41713 11.8425 9.265 11.8425C10.3031 11.8425 11.4006 11.3198 12.4451 10.2838L15.8238 6.93371C16.1971 6.56431 16.4933 6.12443 16.6952 5.63962C16.8971 5.15481 17.0007 4.63472 17 4.10954C17.0009 3.58398 16.8972 3.06349 16.6949 2.57843C16.4926 2.09336 16.1958 1.65344 15.8217 1.2843Z" fill="#E4B26F"/>
                                                                    </svg>
                                                                    Copy Link
                                                                </button>
                                                            </div>

                                                        </div>

                                                    </div>

                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <!-- Materials-->
                                <div>
                                    <div class="courscribe-header-with-divider">
                                        <span class="courscribe-title-sm">Materials</span>
                                        <div class="courscribe-divider"></div>
                                    </div>
                                    <ul id="module-materials-list-<?php echo $module->ID; ?>" class="materials-list">
                                        <?php
                                        $materials = maybe_unserialize(get_post_meta($module->ID, '_module_materials', true)) ?: [];
                                        if (!empty($materials) && is_array($materials)) {
                                            foreach ($materials as $index => $material) {
                                                $material_type = isset($material['material_type']) ? esc_html($material['material_type']) : '';
                                                $title = isset($material['title']) ? esc_html($material['title']) : '';
                                                $link = isset($material['link']) ? esc_url($material['link']) : '';
                                                ?>
                                                <li class="moodule-conteiner-body material-item mb-2" data-material-index="<?php echo $index; ?>">
                                                    <div class="module-body">
                                                        <h5>Material <?php echo $index + 1; ?></h5>
                                                        <div class="module-method-preview">
                                                            <span class="method-type-preview"><?php echo $material_type; ?></span>
                                                            <span class="method-title-preview" title="<?php echo esc_attr($title); ?>"><?php echo esc_html(wp_trim_words($title, 4, '...')); ?></span>
                                                            <div class="method-location-container">
                                                                <a href="<?php echo esc_url($link); ?>" target="_blank" class="method-location-preview"><?php echo $location; ?></a>
                                                                <button class="copy-link-btn" data-url="<?php echo esc_url($link); ?>" title="Copy link to clipboard">
                                                                    <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M6.22519 13.4862L5.36457 14.3416C5.00403 14.6965 4.51843 14.8954 4.01254 14.8954C3.50665 14.8954 3.02105 14.6965 2.66051 14.3416C2.48472 14.1675 2.34518 13.9603 2.24995 13.732C2.15472 13.5036 2.10569 13.2587 2.10569 13.0113C2.10569 12.7639 2.15472 12.519 2.24995 12.2906C2.34518 12.0623 2.48472 11.8551 2.66051 11.681L5.82676 8.54129C6.48232 7.88997 7.71694 6.93158 8.61688 7.8241C8.71372 7.92737 8.83038 8.01006 8.95989 8.06726C9.0894 8.12445 9.2291 8.15497 9.37066 8.15699C9.51222 8.15901 9.65274 8.13248 9.78382 8.079C9.91491 8.02552 10.0339 7.94618 10.1336 7.84572C10.2334 7.74525 10.3119 7.62572 10.3644 7.49425C10.417 7.36279 10.4425 7.22208 10.4394 7.08054C10.4364 6.93899 10.4049 6.79951 10.3468 6.67041C10.2887 6.54131 10.2051 6.42524 10.1012 6.32913C8.57331 4.81186 6.31232 5.09236 4.34351 7.04633L1.17726 10.1871C0.803147 10.556 0.506339 10.9957 0.3042 11.4806C0.102061 11.9656 -0.00135126 12.4859 1.33308e-05 13.0113C-0.00129194 13.5367 0.102147 14.057 0.304283 14.5419C0.506419 15.0268 0.803195 15.4666 1.17726 15.8355C1.93131 16.5834 2.95099 17.0022 4.01307 17C5.04051 17 6.06794 16.6122 6.84888 15.8355L7.71057 14.9801C7.80883 14.883 7.88694 14.7673 7.94041 14.6399C7.99388 14.5124 8.02166 14.3757 8.02216 14.2375C8.02265 14.0993 7.99585 13.9623 7.94329 13.8345C7.89073 13.7067 7.81345 13.5905 7.71588 13.4926C7.51864 13.2949 7.25116 13.1832 6.97185 13.182C6.69254 13.1808 6.42411 13.2902 6.22519 13.4862ZM15.8217 1.2843C14.178 -0.345597 11.8809 -0.433786 10.3615 1.07499L9.28944 2.13963C9.09134 2.33632 8.97949 2.60366 8.97849 2.88282C8.9775 3.16199 9.08744 3.43011 9.28413 3.62822C9.48082 3.82632 9.74815 3.93817 10.0273 3.93917C10.3065 3.94017 10.5746 3.83022 10.7727 3.63353L11.8458 2.56995C12.6321 1.78794 13.6637 2.112 14.3384 2.77927C14.6954 3.13521 14.8931 3.60697 14.8931 4.10954C14.8931 4.61211 14.6954 5.08386 14.3384 5.43875L10.9597 8.78992C9.41481 10.321 8.69019 9.60381 8.381 9.29674C8.28291 9.19935 8.1666 9.12223 8.03871 9.06979C7.91082 9.01735 7.77385 8.99061 7.63562 8.9911C7.4974 8.9916 7.36062 9.01931 7.23311 9.07267C7.10559 9.12602 6.98983 9.20397 6.89244 9.30206C6.79505 9.40015 6.71793 9.51646 6.66549 9.64436C6.61305 9.77225 6.58631 9.90922 6.58681 10.0475C6.5873 10.1857 6.61502 10.3225 6.66837 10.45C6.72172 10.5775 6.79966 10.6933 6.89775 10.7906C7.6075 11.494 8.41713 11.8425 9.265 11.8425C10.3031 11.8425 11.4006 11.3198 12.4451 10.2838L15.8238 6.93371C16.1971 6.56431 16.4933 6.12443 16.6952 5.63962C16.8971 5.15481 17.0007 4.63472 17 4.10954C17.0009 3.58398 16.8972 3.06349 16.6949 2.57843C16.4926 2.09336 16.1958 1.65344 15.8217 1.2843Z" fill="#E4B26F"/>
                                                                    </svg>
                                                                    Copy Link
                                                                </button>
                                                            </div>

                                                        </div>
                                                    </div>

                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>

                                <!--   Media-->
                                <div>
                                    <div class="courscribe-header-with-divider">
                                        <span class="courscribe-title-sm">Media</span>
                                        <div class="courscribe-divider"></div>
                                    </div>
                                    <div class="uploaded-files mt-3" id="media-preview-grid-<?php echo $module->ID; ?>">
                                        <p class="uploaded-text">Uploaded Files</p>
                                        <div class="row">
                                            <?php
                                            $media = maybe_unserialize(get_post_meta($module->ID, '_module_media', true)) ?: [];
                                            if (!empty($media) && is_array($media)) {
                                                foreach ($media as $media_url) {
                                                    $file_ext = pathinfo($media_url, PATHINFO_EXTENSION);
                                                    ?>
                                                    <div class="col-md-3 media-item">
                                                        <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) : ?>
                                                            <img src="<?php echo esc_url($media_url); ?>" class="media-preview img-fluid br-2" alt="Media Image" />
                                                        <?php elseif (in_array($file_ext, ['mp4', 'mov', 'avi'])) : ?>
                                                            <video controls class="media-preview br-2">
                                                                <source src="<?php echo esc_url($media_url); ?>" type="video/<?php echo $file_ext; ?>">
                                                                Your browser does not support the video tag.
                                                            </video>
                                                        <?php elseif (in_array($file_ext, ['pdf'])) : ?>
                                                            <embed src="<?php echo esc_url($media_url); ?>" type="application/pdf" class="media-preview" />
                                                        <?php else : ?>
                                                            <a href="<?php echo esc_url($media_url); ?>" target="_blank">
                                                                <div class="file-icon"><i class="fas fa-file"></i> Download File</div>
                                                            </a>
                                                        <?php endif; ?>

                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                echo '<p>No media uploaded yet.</p>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="lesson-xy-acc lessons" id="lessonsAccordion-<?php echo $module->ID; ?>" style="border-radius: 16px;">
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
                                                    <!-- Activities-->
                                                    <h6 style="margin-left: 1rem;">Activities:</h6>
                                                    <ul id="lesson-activities-list-<?php echo esc_attr($lesson->ID); ?>" style="list-style: none" class="lesson-list-activities-container mt-4">
                                                        <?php
                                                        $lesson_activities = get_post_meta($lesson->ID, '_lesson_activities', true);
                                                        if (!empty($lesson_activities) && is_array($lesson_activities)) {
                                                            $activity_number = 1;
                                                            foreach ($lesson_activities as $index => $activity) {
                                                                $activity_id = isset($activity['id']) ? esc_attr($activity['id']) : 'activity-' . esc_attr($lesson->ID) . '-' . $index;
                                                                $type = isset($activity['type']) ? esc_html($activity['type']) : '';
                                                                $title = isset($activity['title']) ? esc_html($activity['title']) : '';
                                                                $instructions = isset($activity['instructions']) ? esc_html($activity['instructions']) : '';
                                                                ?>
                                                                <li class="lesson-list-activity-container mb-3" data-activity-id="<?php echo esc_attr($activity_id); ?>">
                                                                    <!-- Activity -->
                                                                    <div>
                                                                        <div class="courscribe-header-with-divider mb-2">
                                                                            <span class="courscribe-title-sm">Activity <?php echo esc_html($activity_number); ?></span>
                                                                            <div class="courscribe-divider"></div>
                                                                        </div>
                                                                        <div class="lesson-activity-preview-card">
                                                                            <span class="lesson-activity-preview-card-title"><?php echo esc_attr($title); ?></span>
                                                                            <p class="lesson-activity-preview-card-description">
                                                                                <?php echo esc_textarea($instructions); ?>
                                                                            </p>
                                                                            <span class="card-tag"><?php echo esc_attr($type); ?></span>
                                                                        </div>
                                                                    </div>

                                                                </li>
                                                                <?php
                                                                $activity_number++;
                                                            }
                                                        } else {
                                                            echo '<li>No activities added yet.</li>';
                                                        }
                                                        ?>
                                                    </ul>
                                                    <!-- Teaching Points -->
                                                    <h6 style="margin-left: 1rem;">Teaching Points:</h6>
                                                    <div>
                                                        <ul id="teaching-points-list-<?php echo $lesson->ID; ?>" class="teaching-points-container">
                                                            <?php
                                                            $teaching_points = get_post_meta($lesson->ID, '_teaching_points', true);
                                                            if (!empty($teaching_points) && is_array($teaching_points)) {
                                                                foreach ($teaching_points as $index => $point) {
                                                                    ?>
                                                                    <li class="teaching-point-preview-item mb-2" data-point-id="point-<?php echo $lesson->ID; ?>-<?php echo $index; ?>">
                                                                        <span class="teaching-point-list-icon">
                                                                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                                <path d="M0 0h24v24H0z" fill="none"></path>
                                                                                <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                                            </svg>
                                                                        </span>
                                                                        <span class="teaching-point-list-val"><?php echo esc_html($point); ?></span>

                                                                    </li>
                                                                    <?php
                                                                }
                                                            } else {
                                                                echo '<li>No teaching points added yet.</li>';
                                                            }
                                                            ?>
                                                        </ul>
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