<?php
// courscribe/templates/dashboard/courscribe-expert-reviews-dashboard.php
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_expert_reviews_dashboard() {
    // Permission check
    if (!current_user_can('administrator')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $current_user = wp_get_current_user();
    $site_url = home_url();

    // Get Expert Review product ID
    $expert_review_product_id = wc_get_product_id_by_sku('COURSCRIBE-EXPERT-REVIEW');
    if (!$expert_review_product_id) {
        echo '<div class="wrap courscribe-dashboard"><h1>Expert Reviews Dashboard</h1><p>Expert Review product not found.</p></div>';
        return;
    }

    // Fetch completed orders containing the expert review product
    $orders = wc_get_orders([
        'status' => 'completed',
        'limit' => -1,
    ]);

    $curriculum_ids = [];
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $expert_review_product_id) {
                $curriculum_id = $item->get_meta('_curriculum_id');
                if ($curriculum_id) {
                    $curriculum_ids[] = $curriculum_id;
                }
            }
        }
    }
    $curriculum_ids = array_unique($curriculum_ids);

    if (empty($curriculum_ids)) {
        echo '<div class="wrap courscribe-dashboard"><h1>Expert Reviews Dashboard</h1><p>No curriculums have been paid for expert review.</p></div>';
        return;
    }

    // Query curriculums
    $curriculums = get_posts([
        'post_type' => 'crscribe_curriculum',
        'post__in' => $curriculum_ids,
        'posts_per_page' => -1,
        'post_status' => 'any', // Show all statuses for review
    ]);

    // Render the dashboard
    ?>
     <!-- CourScribe loader -->
    <div id="courscribe-loader" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-75" style="z-index: 9999;">
        <div class="text-center courscribe-loader-dark">
            <div aria-label="Orange and tan hamster running in a metal wheel" role="img" class="wheel-and-hamster">
                <div class="wheel"></div>
                <div class="hamster">
                    <div class="hamster__body">
                        <div class="hamster__head">
                            <div class="hamster__ear"></div>
                            <div class="hamster__eye"></div>
                            <div class="hamster__nose"></div>
                        </div>
                        <div class="hamster__limb hamster__limb--fr"></div>
                        <div class="hamster__limb hamster__limb--fl"></div>
                        <div class="hamster__limb hamster__limb--br"></div>
                        <div class="hamster__limb hamster__limb--bl"></div>
                        <div class="hamster__tail"></div>
                    </div>
                </div>
                <div class="spoke"></div>
            </div>
            <div class="courscribe-loading-text-container">
                <div class="courscribe-loading-text-content">
                    <div class="courscribe-loading-text-content__container">
                        <p class="courscribe-loading-text-content__container__text text-white">
                            CourScribe..
                        </p>
                        <ul class="courscribe-loading-text-content__container__list">
                            <li class="courscribe-loading-text-content__container__list__item text-white">Spinning</li>
                            <li class="courscribe-loading-text-content__container__list__item text-white">Powering</li>
                            <li class="courscribe-loading-text-content__container__list__item text-white">Rolling</li>
                            <li class="courscribe-loading-text-content__container__list__item text-white">Crafting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="wrap courscribe-dashboard">
        <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;">
        <h3 class="courscribe-heading">
            Review Curriculums Paid for Expert Feedback.<br>
            <span>Expert Reviews Dashboard.</span>
        </h3>
        <p class="courscribe-pricing-subheading">Welcome, <?php echo esc_html($current_user->display_name); ?>! Below are curriculums awaiting your expert review.</p>

        <?php foreach ($curriculums as $curriculum) : 
            $post_id = $curriculum->ID;
            $topic = get_post_meta($post_id, '_curriculum_topic', true);
            $title = get_post_meta($post_id, '_curriculum_title', true);
            $goal = get_post_meta($post_id, '_curriculum_goal', true);
            $notes = get_post_meta($post_id, '_curriculum_notes', true);
            $status = get_post_meta($post_id, '_curriculum_status', true) ?: 'draft';
            $studio_id = get_post_meta($post_id, '_studio_id', true);
            $studio = get_post($studio_id);

            // Determine user permissions (simplified for admin)
            $is_client = false; // Admin is not a client
            $is_studio_admin = false; // Assume admin overrides studio admin
            $is_wp_admin = true; // Administrator role
            $is_collaborator = false;
            $can_edit_this_curriculum = true; // Admins can edit
            $can_view_feedback = true; // Admins can view feedback
            $is_form_readonly = false; // Admins can edit, so not readonly
            $curriculum_title_for_data = get_the_title($post_id);
        ?>
            <div class="curriculum-box ml-3">
                <div class="row">
                    <div class="curriculum-96">
                        <?php if ($is_client || $can_edit_this_curriculum): ?>
                            <form method="post" class="courscribe-curriculum-form">
                                <?php wp_nonce_field('courscribe_curriculum', 'courscribe_curriculum_nonce'); ?>
                                <input type="hidden" name="curriculum_id" value="<?php echo esc_attr($post_id); ?>">
                                <div class="my-row mb-3 mt-3">
                                    <div class="my-col-6">
                                        <div class="courscribe-client-review-input-group">
                                            <div class="courscribe-client-review-input-group">
                                                <label for="curriculum_title-<?php echo $post_id; ?>">Title</label>
                                                <div class="curriculum-input-wrapper">
                                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/title-icon.png" alt="Icon" class="curriculum-input-icon">
                                                    <input type="text" 
                                                        id="curriculum_title-<?php echo $post_id; ?>" 
                                                        name="curriculum_title" 
                                                        value="<?php the_title(); ?>" 
                                                        class="form-control" 
                                                        readonly
                                                        
                                                    />
                                                </div>
                                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment"
                                                        data-post-id="<?php echo esc_attr($post_id); ?>" 
                                                        data-curriculum-id="<?php echo esc_attr($post_id); ?>" 
                                                        data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" 
                                                        data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>"
                                                        data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_title]"
                                                        data-field-id="curriculum-title-<?php echo esc_attr($post_id); ?>"
                                                        data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                                        data-current-field-value="<?php the_title(); ?>"
                                                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                        data-post-type="crscribe_curriculum"
                                                        data-field-type="title"
                                                        data-bs-toggle="offcanvas"
                                                        data-bs-target="#courscribeManagerFeedbackOffcanvas"
                                                        aria-controls="courscribeManagerFeedbackOffcanvasLabel"
                                                    >
                                                        <span class="courscribe-client-review-end-adrnment-tooltip">Give Title Feedback</span>
                                                        <span class="text">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                height="1.2em"
                                                                width="1.2em"
                                                            >
                                                                <g style="filter: url(#shadow)">
                                                                <path
                                                                    fill="currentColor"
                                                                    d="M14.2199 21.63C13.0399 21.63 11.3699 20.8 10.0499 16.83L9.32988 14.67L7.16988 13.95C3.20988 12.63 2.37988 10.96 2.37988 9.78001C2.37988 8.61001 3.20988 6.93001 7.16988 5.60001L15.6599 2.77001C17.7799 2.06001 19.5499 2.27001 20.6399 3.35001C21.7299 4.43001 21.9399 6.21001 21.2299 8.33001L18.3999 16.82C17.0699 20.8 15.3999 21.63 14.2199 21.63ZM7.63988 7.03001C4.85988 7.96001 3.86988 9.06001 3.86988 9.78001C3.86988 10.5 4.85988 11.6 7.63988 12.52L10.1599 13.36C10.3799 13.43 10.5599 13.61 10.6299 13.83L11.4699 16.35C12.3899 19.13 13.4999 20.12 14.2199 20.12C14.9399 20.12 16.0399 19.13 16.9699 16.35L19.7999 7.86001C20.3099 6.32001 20.2199 5.06001 19.5699 4.41001C18.9199 3.76001 17.6599 3.68001 16.1299 4.19001L7.63988 7.03001Z"
                                                                ></path>
                                                                <path
                                                                    fill="currentColor"
                                                                    d="M10.11 14.4C9.92005 14.4 9.73005 14.33 9.58005 14.18C9.29005 13.89 9.29005 13.41 9.58005 13.12L13.16 9.53C13.45 9.24 13.93 9.24 14.22 9.53C14.51 9.82 14.51 10.3 14.22 10.59L10.64 14.18C10.5 14.33 10.3 14.4 10.11 14.4Z"
                                                                ></path>
                                                                </g>
                                                                <defs>
                                                                <filter id="shadow">
                                                                    <fedropshadow
                                                                    flood-opacity="0.6"
                                                                    stdDeviation="0.8"
                                                                    dy="1"
                                                                    dx="0"
                                                                    ></fedropshadow>
                                                                </filter>
                                                                </defs>
                                                            </svg>

                                                        </span>
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="my-col-6">
                                            <div class="courscribe-client-review-input-group">
                                                <label for="curriculum_topic-<?php echo $post_id; ?>">Topic</label>
                                                <div class="curriculum-input-wrapper">
                                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/topic-icon.png" alt="Icon" class="curriculum-input-icon">
                                                    <input 
                                                        type="text" 
                                                        id="curriculum_topic-<?php echo $post_id; ?>" 
                                                        name="curriculum_topic" 
                                                        value="<?php echo esc_attr($topic); ?>" 
                                                        class="form-control" 
                                                        readonly
                                                        <?php if($is_form_readonly) echo 'readonly'; ?>
                                                    />
                                                </div>
                                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment"
                                                        data-post-id="<?php echo esc_attr($post_id); ?>" 
                                                        data-curriculum-id="<?php echo esc_attr($post_id); ?>" 
                                                        data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" 
                                                        data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>"
                                                        data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_topic]"
                                                        data-field-id="curriculum-topic-<?php echo esc_attr($post_id); ?>"
                                                        data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                                        data-current-field-value="<?php echo esc_attr($topic); ?>"
                                                        data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                        data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                        data-post-type="crscribe_curriculum"
                                                        data-field-type="topic"
                                                        data-bs-toggle="offcanvas"
                                                        data-bs-target="#courscribeManagerFeedbackOffcanvas"
                                                        aria-controls="courscribeManagerFeedbackOffcanvasLabel"
                                                    >
                                                        <span class="courscribe-client-review-end-adrnment-tooltip">Give Topic Feedback</span>
                                                        <span class="text">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                height="1.2em"
                                                                width="1.2em"
                                                            >
                                                                <g style="filter: url(#shadow)">
                                                                <path
                                                                    fill="currentColor"
                                                                    d="M14.2199 21.63C13.0399 21.63 11.3699 20.8 10.0499 16.83L9.32988 14.67L7.16988 13.95C3.20988 12.63 2.37988 10.96 2.37988 9.78001C2.37988 8.61001 3.20988 6.93001 7.16988 5.60001L15.6599 2.77001C17.7799 2.06001 19.5499 2.27001 20.6399 3.35001C21.7299 4.43001 21.9399 6.21001 21.2299 8.33001L18.3999 16.82C17.0699 20.8 15.3999 21.63 14.2199 21.63ZM7.63988 7.03001C4.85988 7.96001 3.86988 9.06001 3.86988 9.78001C3.86988 10.5 4.85988 11.6 7.63988 12.52L10.1599 13.36C10.3799 13.43 10.5599 13.61 10.6299 13.83L11.4699 16.35C12.3899 19.13 13.4999 20.12 14.2199 20.12C14.9399 20.12 16.0399 19.13 16.9699 16.35L19.7999 7.86001C20.3099 6.32001 20.2199 5.06001 19.5699 4.41001C18.9199 3.76001 17.6599 3.68001 16.1299 4.19001L7.63988 7.03001Z"
                                                                ></path>
                                                                <path
                                                                    fill="currentColor"
                                                                    d="M10.11 14.4C9.92005 14.4 9.73005 14.33 9.58005 14.18C9.29005 13.89 9.29005 13.41 9.58005 13.12L13.16 9.53C13.45 9.24 13.93 9.24 14.22 9.53C14.51 9.82 14.51 10.3 14.22 10.59L10.64 14.18C10.5 14.33 10.3 14.4 10.11 14.4Z"
                                                                ></path>
                                                                </g>
                                                                <defs>
                                                                <filter id="shadow">
                                                                    <fedropshadow
                                                                    flood-opacity="0.6"
                                                                    stdDeviation="0.8"
                                                                    dy="1"
                                                                    dx="0"
                                                                    ></fedropshadow>
                                                                </filter>
                                                                </defs>
                                                            </svg>
                                                        </span>
                                                    </div>
                                            </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                        <div class="courscribe-client-review-input-group">
                                            <label for="curriculum_goal-<?php echo $post_id; ?>">Goal</label>
                                            <div class="curriculum-input-wrapper">
                                                <img src="<?php echo esc_url($site_url); ?>/wp-content/uploads/2024/12/goal-icon.png" alt="Icon" class="curriculum-input-icon">
                                                <input 
                                                    type="text" 
                                                    id="curriculum_goal-<?php echo $post_id; ?>" 
                                                    name="curriculum_goal-<?php echo $post_id; ?>" 
                                                    value="<?php echo esc_attr($goal); ?>" 
                                                    class="form-control" 
                                                    <?php if($is_form_readonly) echo 'readonly'; ?>
                                                    readonly
                                                />
                                            </div>
                                                <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment"
                                                    data-post-id="<?php echo esc_attr($post_id); ?>" 
                                                    data-curriculum-id="<?php echo esc_attr($post_id); ?>" 
                                                    data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" 
                                                    data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>"
                                                    data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_goal]"
                                                    data-field-id="curriculum-goal-<?php echo esc_attr($post_id); ?>"
                                                    data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>"
                                                    data-current-field-value="<?php echo esc_attr($goal); ?>"
                                                    data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                                    data-user-name="<?php echo esc_attr($current_user->display_name); ?>"  
                                                    data-post-type="crscribe_curriculum"
                                                    data-field-type="goal"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#courscribeManagerFeedbackOffcanvas"
                                                    aria-controls="courscribeManagerFeedbackOffcanvasLabel"
                                                >
                                                    <span class="courscribe-client-review-end-adrnment-tooltip">Give Goal Feedback</span>
                                                    <span class="text">
                                                        <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                height="1.2em"
                                                                width="1.2em"
                                                            >
                                                                <g style="filter: url(#shadow)">
                                                                <path
                                                                    fill="currentColor"
                                                                    d="M14.2199 21.63C13.0399 21.63 11.3699 20.8 10.0499 16.83L9.32988 14.67L7.16988 13.95C3.20988 12.63 2.37988 10.96 2.37988 9.78001C2.37988 8.61001 3.20988 6.93001 7.16988 5.60001L15.6599 2.77001C17.7799 2.06001 19.5499 2.27001 20.6399 3.35001C21.7299 4.43001 21.9399 6.21001 21.2299 8.33001L18.3999 16.82C17.0699 20.8 15.3999 21.63 14.2199 21.63ZM7.63988 7.03001C4.85988 7.96001 3.86988 9.06001 3.86988 9.78001C3.86988 10.5 4.85988 11.6 7.63988 12.52L10.1599 13.36C10.3799 13.43 10.5599 13.61 10.6299 13.83L11.4699 16.35C12.3899 19.13 13.4999 20.12 14.2199 20.12C14.9399 20.12 16.0399 19.13 16.9699 16.35L19.7999 7.86001C20.3099 6.32001 20.2199 5.06001 19.5699 4.41001C18.9199 3.76001 17.6599 3.68001 16.1299 4.19001L7.63988 7.03001Z"
                                                                ></path>
                                                                <path
                                                                    fill="currentColor"
                                                                    d="M10.11 14.4C9.92005 14.4 9.73005 14.33 9.58005 14.18C9.29005 13.89 9.29005 13.41 9.58005 13.12L13.16 9.53C13.45 9.24 13.93 9.24 14.22 9.53C14.51 9.82 14.51 10.3 14.22 10.59L10.64 14.18C10.5 14.33 10.3 14.4 10.11 14.4Z"
                                                                ></path>
                                                                </g>
                                                                <defs>
                                                                <filter id="shadow">
                                                                    <fedropshadow
                                                                    flood-opacity="0.6"
                                                                    stdDeviation="0.8"
                                                                    dy="1"
                                                                    dx="0"
                                                                    ></fedropshadow>
                                                                </filter>
                                                                </defs>
                                                            </svg>
                                                    </span>
                                                </div>
                                        </div>
                                </div>
                                <div class="mb-3">
                                    <label for="notes-<?php echo $post_id; ?>">Notes</label>
                                    <?php
                                    $editor_id = 'curriculum_notes-' . $post_id;
                                    $settings = [
                                        'textarea_name' => 'curriculum_notes',
                                        'media_buttons' => false,
                                        'teeny' => true,
                                        'quicktags' => false,
                                        'textarea_rows' => 10,
                                        'editor_height' => 200,
                                        'editor_class' => 'courscribe-readonly-editor',
                                        'readonly'
                                    ];
                                    wp_editor($notes, $editor_id, $settings);
                                    if ($is_form_readonly) {
                                        echo "<script>jQuery(document).ready(function($){ setTimeout(function() { $('#wp-{$editor_id}-wrap').addClass('disabled'); var editor = tinymce.get('{$editor_id}'); if(editor) editor.setMode('readonly'); }, 500); });</script>";
                                        echo "<style>#wp-{$editor_id}-wrap.disabled { pointer-events: none; opacity: 0.7; }</style>";
                                    }
                                    ?>
                                </div>
                                <div class="row mb-3 mt-3" style="align-items: center;">
                                    <div class="col-6">
                                        <div>
                                            <label for="curriculum_status-<?php echo $post_id; ?>">Status</label>
                                            <select id="curriculum_status-<?php echo $post_id; ?>" disabled name="curriculum_status" class="form-control" <?php if($is_form_readonly) echo 'disabled'; ?>>
                                                <option value="draft" <?php selected($status, 'draft'); ?>>Draft</option>
                                                <option value="review" <?php selected($status, 'review'); ?>>Review</option>
                                                <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                                                <option value="published" <?php selected($status, 'published'); ?>>Published</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div>
                                            <label for="curriculum_studio-<?php echo $post_id; ?>">Studio</label>
                                            <select id="curriculum_studio-<?php echo $post_id; ?>" disabled name="curriculum_studio" class="form-control" required <?php if($is_form_readonly) echo 'disabled'; ?>>
                                                <option value="">Select Studio</option>
                                                <?php
                                                $studios = get_posts([
                                                    'post_type' => 'crscribe_studio',
                                                    'post_status' => 'publish',
                                                    'posts_per_page' => -1,
                                                ]);
                                                foreach ($studios as $studio_item) {
                                                    echo '<option value="' . esc_attr($studio_item->ID) . '" ' . selected($studio_id, $studio_item->ID, false) . '>' . esc_html($studio_item->post_title) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php
                                    $load_curriculum_link = get_permalink($post_id);
                                    if ($is_client) {
                                        $load_curriculum_link = add_query_arg('preview_type', 'client-preview', $load_curriculum_link);
                                    } elseif ($is_studio_admin || $is_wp_admin) {
                                        $load_curriculum_link = add_query_arg('preview_type', 'studio-preview', $load_curriculum_link);
                                    }
                                    ?>
                                    <a class="text-white text-sm font-weight-bold mb-0 icon-move-right" href="<?php echo esc_url($load_curriculum_link); ?>">
                                        Load Curriculum
                                        <i class="fas fa-arrow-right text-sm ms-1" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div style="padding: 15px;">
                                <h4><?php the_title(); ?></h4>
                                <p><strong>Topic:</strong> <?php echo esc_html($topic); ?></p>
                                <p><strong>Goal:</strong> <?php echo esc_html($goal); ?></p>
                                <p><strong>Status:</strong> <?php echo esc_html(ucfirst($status)); ?></p>
                                <?php
                                $load_curriculum_link = get_permalink($post_id);
                                if ($is_client) {
                                    $load_curriculum_link = add_query_arg('preview_type', 'client-preview', $load_curriculum_link);
                                } elseif ($is_studio_admin || $is_wp_admin) {
                                    $load_curriculum_link = add_query_arg('preview_type', 'studio-preview', $load_curriculum_link);
                                }
                                ?>
                                <a class="text-white text-sm font-weight-bold mb-0 icon-move-right mt-3 d-block" href="<?php echo esc_url($load_curriculum_link); ?>">
                                    Load Curriculum
                                    <i class="fas fa-arrow-right text-sm ms-1" aria-hidden="true"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="curriculum-4">
                        
                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment"
                                data-post-id="<?php echo esc_attr($post_id); ?>"
                                    data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>"
                                    data-field-id="<?php echo esc_attr($field_id_for_curriculum); ?>"
                                    data-post-type="crscribe_curriculum"
                                    data-field-type="post" 
                                    data-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                    data-user-name="<?php echo esc_attr($current_user->display_name); ?>"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#courscribeManagerFeedbackOffcanvas"
                                    aria-controls="courscribeManagerFeedbackOffcanvasLabel"
                            >
                                <span class="courscribe-client-review-end-adrnment-tooltip">Give Curriculum Feedback</span>
                                <span class="text">
                                    <svg fill="#665442" viewBox="0 0 24 24" height="30px" width="30px" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" d="M7.39999 6.32003L15.89 3.49003C19.7 2.22003 21.77 4.30003 20.51 8.11003L17.68 16.6C15.78 22.31 12.66 22.31 10.76 16.6L9.91999 14.08L7.39999 13.24C1.68999 11.34 1.68999 8.23003 7.39999 6.32003Z"></path>
                                        <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" d="M10.11 13.6501L13.69 10.0601"></path>
                                    </svg>
                                </span>
                            </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Feedback Offcanvas for Curriculum Manager -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="courscribeManagerFeedbackOffcanvas" style="min-width:720px!important" aria-labelledby="courscribeManagerFeedbackOffcanvasLabel">
        <div class="offcanvas-header" style="padding-top:35px; position: relative">
            <h6 class="offcanvas-title" id="courscribeManagerFeedbackOffcanvasLabel">Curriculum Feedback</h6>
            <span data-bs-dismiss="offcanvas" style="position: absolute; top: 124px; right: 20px; cursor: pointer; font-size: 40px;">×</span>
        </div>
        <div class="offcanvas-body p-0" id="courscribe-manager-feedback-container">
            <!-- JS will populate this -->
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {

            // Show loader
            const $loader = $('#courscribe-loader');
            if ($loader.length) {
                $loader.removeClass('d-none');
            } else {
                console.warn('Courscribe loader element not found');
            }

            // Fallback to hide loader after 10 seconds
            setTimeout(() => {
                if (!$loader.hasClass('d-none')) {
                    $loader.addClass('d-none');
                    console.warn('Courscribe loader hidden by fallback timeout');
                }
            }, 4000);
          

            
        });
    </script>
    <script>
    jQuery(document).ready(function($) {
        // ... Your existing JS for add/cancel/archive/delete ...
       
        // Feedback Offcanvas JS (Adapted from courscribe_single_curriculum_shortcode.php)
        let currentManagerAnnotorious = null; // Specific to this offcanvas instance

        $('#courscribeManagerFeedbackOffcanvas').on('show.bs.offcanvas', function(event) {
            var button = $(event.relatedTarget);
            var postId = button.data('post-id'); // This is curriculum_id
            var postTitle = button.data('post-title');
            var fieldId = button.data('field-id'); // e.g., curriculum-overall-{id}
            var postType = button.data('post-type'); // crscribe_curriculum
            var fieldType = button.data('field-type'); // 'post'
            var userId = button.data('user-id');
            var userName = button.data('user-name');
            var isClientUser = <?php echo json_encode($is_client); ?>;

            var $offcanvas = $(this);
            var $offcanvasBody = $offcanvas.find('#courscribe-manager-feedback-container');
            $offcanvas.find('.offcanvas-title').text('Feedback for: ' + postTitle);
            $offcanvasBody.html('<p style="padding:15px;">Loading feedback...</p>');

            fetchAndRenderManagerFeedback();

            function fetchAndRenderManagerFeedback() {
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    method: 'POST',
                    data: {
                        action: 'courscribe_get_feedback',
                        nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                        post_id: postId,
                        post_type: postType,
                        field_id: fieldId
                    },
                    success: function(response) {
                        if (response.success) {
                            renderManagerFeedbackUI(response.data);
                        } else {
                            $offcanvasBody.html('<p style="padding:15px;">Error loading feedback: ' + response.data.message + '</p>');
                        }
                    },
                    error: function() { $offcanvasBody.html('<p style="padding:15px;">Error loading feedback.</p>'); }
                });
            }

            function renderManagerFeedbackUI(feedbackData) {
                var fieldValueHtml = `<div class="courscribe-offcanvas-field-value">${postTitle}</div>`;
                var headerComponent = `
                    <div class="courscribe-offcanvas-header-component p-3">
                        <div class="courscribe-offcanvas-title">Feedback for <span>${postTitle}</span> <div class="pill">${postType.replace('crscribe_', '').toUpperCase()}</div></div>
                        <div class="courscribe-offcanvas-field-type">Field: ${fieldType}</div>
                        <div class="courscribe-offcanvas-field-value">Reviewing: ${fieldValueHtml}</div>
                        <div class="courscribe-feedback-radio">
                            <input type="radio" id="status-open-manager" name="feedback-status-manager" value="Open" label="Open" checked>
                            <input type="radio" id="status-in-progress-manager" name="feedback-status-manager" value="In Progress" label="Mark As In-Progress">
                            <input type="radio" id="status-resolved-manager" name="feedback-status-manager" value="Resolved" label="Mark As Resolved">
                        </div>
                    </div>`;

                var feedbackEntries = feedbackData.map(entry => `
                    <div class="courscribe-feedback-entry ${entry.role === 'Client' ? 'client' : ''} p-3">
                        <img src="<?php echo esc_url(home_url('/wp-content/plugins/courscribe/assets/images/profile.png')); ?>" alt="${entry.user_name} avatar" class="courscribe-feedback-avatar">
                        <div class="courscribe-feedback-content">
                            <div class="courscribe-feedback-user">
                                <div><div class="courscribe-feedback-user-info">${entry.user_name}</div><div class="courscribe-feedback-role">${entry.role}</div></div>
                                <div class="courscribe-feedback-timestamp">${new Date(entry.timestamp).toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true })}</div>
                            </div>
                            <div class="courscribe-feedback-text">${entry.text}</div>
                            ${entry.screenshot_url ? `<img src="${entry.screenshot_url}" class="courscribe-feedback-screenshot" alt="Feedback screenshot" data-screenshot-url="${entry.screenshot_url}" data-annotations='${JSON.stringify(entry.annotations || [])}'>` : ''}
                            <div class="courscribe-feedback-status ${entry.status}">${entry.status.toUpperCase().replace('-', ' ')}</div>
                        </div>
                    </div>`).join('');

                var feedbackComponentHtml = `
                    <div class="courscribe-feedback-component">
                        ${headerComponent}
                        <div class="courscribe-feedback-header mt-3 mb-3 p-3"><h6>Feedback Timeline</h6></div>
                        <div class="courscribe-feedback-timeline">${feedbackEntries}</div>
                        <div class="courscribe-feedback-footer p-3">
                            <button class="courscribe-add-response-btn"><span>Add Open Response</span></button>
                            <button class="courscribe-take-screenshot-btn-manager"><span>Take Screenshot</span></button>
                        </div>
                    </div>`;
                $offcanvasBody.html(feedbackComponentHtml);
                bindManagerFeedbackEvents();
            }

            function bindManagerFeedbackEvents() {
                $offcanvasBody.off('click', '.courscribe-add-response-btn').on('click', '.courscribe-add-response-btn', function() {
                    var $timeline = $(this).closest('.courscribe-feedback-component').find('.courscribe-feedback-timeline');
                    if ($timeline.find('.ai-input-container').length) return; // Prevent multiple textareas

                    var selectedStatus = $('.courscribe-feedback-radio input[name="feedback-status-manager"]:checked').val().toLowerCase().replace(' ', '-');
                    var textField = `
                        <div class="ai-input-container mb-3 mt-3 p-3">
                            <div class="courscribe-feedback-status ${selectedStatus}" style="margin-bottom: 5px;">${selectedStatus.replace('-', ' ').toUpperCase()}</div>
                            <textarea class="ai-input-field" id="manager-feedback-textbox" placeholder="Type your feedback..."></textarea>
                            <div class="ai-input-buttons">
                                <button class="ai-send-button" id="manager-feedback-save"><div class="ai-send-icon"></div></button>
                                <button class="ai-cancel-button" id="manager-feedback-cancel">...</button> <!-- Simplified cancel -->
                            </div>
                        </div>`;
                    $timeline.append(textField);
                    $(this).hide();
                });

                $offcanvasBody.off('click', '#manager-feedback-cancel').on('click', '#manager-feedback-cancel', function() {
                    $(this).closest('.ai-input-container').remove();
                    $offcanvasBody.find('.courscribe-add-response-btn').show();
                });

                $offcanvasBody.off('click', '#manager-feedback-save').on('click', '#manager-feedback-save', function() {
                    var feedbackText = $('#manager-feedback-textbox').val();
                    if (!feedbackText.trim()) return;
                    var selectedStatus = $('.courscribe-feedback-radio input[name="feedback-status-manager"]:checked').val().toLowerCase().replace(' ', '-');

                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', method: 'POST',
                        data: {
                            action: 'courscribe_save_feedback', nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                            post_id: postId, post_type: postType, field_id: fieldId,
                            type: 'response', text: feedbackText, status: selectedStatus
                        },
                        success: function(response) {
                            if (response.success) {
                                fetchAndRenderManagerFeedback(); // Re-render with new feedback
                                updateFeedbackCountOnButton(postId, postType, fieldId);
                            } else { alert('Failed to save feedback: ' + response.data.message); }
                        },
                        error: function() { alert('AJAX error saving feedback.'); }
                    });
                });

                $offcanvasBody.off('click', '.courscribe-take-screenshot-btn-manager').on('click', '.courscribe-take-screenshot-btn-manager', function() {
                    // html2canvas needs a DOM element. For curriculum manager, what to screenshot?
                    // Option 1: Screenshot the whole page (complex).
                    // Option 2: Screenshot a specific curriculum-box (needs ID). The button is inside the loop.
                    // For now, this button will show the image for annotation if one was already part of the feedback.
                    // To take a NEW screenshot, we'd need to target the specific curriculum's div.
                    // Let's assume it's for viewing/annotating an existing screenshot first.
                    // Or, take a screenshot of the curriculum box that triggered this.
                    const $curriculumBoxToScreenshot = $(`.curriculum-box form[method="post"] input[name="curriculum_id"][value="${postId}"]`).closest('.curriculum-box');
                    if (!$curriculumBoxToScreenshot.length) {
                         // Fallback for client view where form might not exist
                        const clientViewBox = button.closest('.curriculum-box');
                        if (clientViewBox.length) {
                             $curriculumBoxToScreenshot = clientViewBox;
                        } else {
                            alert('Target element for screenshot not found.');
                            return;
                        }
                    }


                    $offcanvasBody.html('<p style="padding:15px;">Generating screenshot...</p>');
                    html2canvas($curriculumBoxToScreenshot[0], { scale: 1.5, useCORS: true, allowTaint: true, backgroundColor: '#231f20' }).then(canvas => {
                        var dataUrl = canvas.toDataURL('image/png');
                        $offcanvasBody.html(`
                            <div class="courscribe-screenshot-container p-3">
                                <div id="courscribe-manager-screenshot-wrapper" style="position: relative; width: 100%; overflow: auto;">
                                    <img src="${dataUrl}" class="courscribe-screenshot-img" id="courscribe-manager-screenshot-img" style="max-width: 100%; display: block;">
                                </div>
                                <div class="courscribe-annotation-controls mt-2">
                                    <button class="btn btn-primary courscribe-save-manager-annotation-btn">Save Annotation</button>
                                    <button class="btn btn-secondary courscribe-cancel-manager-annotation-btn">Cancel</button>
                                </div>
                            </div>`);

                        if (currentManagerAnnotorious) currentManagerAnnotorious.destroy();
                        currentManagerAnnotorious = Annotorious.init({ image: 'courscribe-manager-screenshot-img', readOnly: false });
                        currentManagerAnnotorious.setAuthInfo({ id: userId, displayName: userName });
                    }).catch(error => {
                        console.error('Error generating screenshot:', error);
                        fetchAndRenderManagerFeedback(); // Restore UI
                        alert('Failed to generate screenshot.');
                    });
                });
                
                $offcanvasBody.off('click', '.courscribe-feedback-screenshot').on('click', '.courscribe-feedback-screenshot', function() {
                    var screenshotUrl = $(this).data('screenshot-url');
                    var annotations = $(this).data('annotations') || [];
                    // Display existing screenshot and annotations (read-only)
                    $offcanvasBody.html(`
                        <div class="courscribe-screenshot-container p-3">
                            <div id="courscribe-manager-screenshot-wrapper" style="position: relative; width: 100%; overflow: auto;">
                                <img src="${screenshotUrl}" class="courscribe-screenshot-img" id="courscribe-manager-screenshot-img" style="max-width: 100%; display: block;">
                            </div>
                            <div class="courscribe-annotation-controls mt-2">
                                <button class="btn btn-secondary courscribe-cancel-manager-annotation-btn">Close Viewer</button>
                            </div>
                        </div>`);
                    if (currentManagerAnnotorious) currentManagerAnnotorious.destroy();
                    var imgElement = document.getElementById('courscribe-manager-screenshot-img');
                    if (imgElement) {
                         currentManagerAnnotorious = Annotorious.init({ image: imgElement, readOnly: true });
                         currentManagerAnnotorious.setAnnotations(annotations);
                    } else {
                        console.error("Screenshot image element not found for Annotorious");
                    }
                });


                $offcanvasBody.off('click', '.courscribe-save-manager-annotation-btn').on('click', '.courscribe-save-manager-annotation-btn', function() {
                    if (!currentManagerAnnotorious) return;
                    var annotations = currentManagerAnnotorious.getAnnotations();
                    var dataUrl = $('#courscribe-manager-screenshot-img').attr('src');
                    var feedbackText = prompt("Enter a comment for this annotated screenshot (optional):", "Annotated screenshot feedback");


                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', method: 'POST',
                        data: {
                            action: 'courscribe_save_feedback', nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                            post_id: postId, post_type: postType, field_id: fieldId,
                            type: 'feedback', text: feedbackText || 'Annotated screenshot', status: 'Open',
                            screenshot: dataUrl, annotations: JSON.stringify(annotations)
                        },
                        success: function(response) {
                            if (response.success) {
                                if (currentManagerAnnotorious) currentManagerAnnotorious.destroy(); currentManagerAnnotorious = null;
                                fetchAndRenderManagerFeedback();
                                updateFeedbackCountOnButton(postId, postType, fieldId);
                            } else { alert('Failed to save annotated feedback: ' + response.data.message); }
                        },
                        error: function() { alert('AJAX error saving annotated feedback.'); }
                    });
                });

                $offcanvasBody.off('click', '.courscribe-cancel-manager-annotation-btn').on('click', '.courscribe-cancel-manager-annotation-btn', function() {
                    if (currentManagerAnnotorious) currentManagerAnnotorious.destroy(); currentManagerAnnotorious = null;
                    fetchAndRenderManagerFeedback(); // Restore previous feedback view
                });
            }

            // Initial setup for radio buttons etc.
            $('.courscribe-feedback-radio input[name="feedback-status-manager"]').off('change').on('change', function() {
                var selectedStatus = $(this).val();
                $offcanvasBody.find('.courscribe-add-response-btn span').text(`Add ${selectedStatus} Response`);
            });
            setTimeout(function() {
                if (!<?php echo json_encode($is_client); ?>) {
                    $('.courscribe-view-feedback-btn').each(function() {
                        var $btn = $(this);
                        updateFeedbackCountOnButton(
                            $btn.data('post-id'),
                            $btn.data('post-type'),
                            $btn.data('field-id')
                        );
                    });
                }
            }, 500);
        });

        // Function to update feedback counts for admin/studio_admin buttons
        function updateFeedbackCountOnButton(postId, postType, fieldId) {
            var $button = $('.courscribe-view-feedback-btn[data-post-id="' + postId + '"][data-field-id="' + fieldId + '"]');
            if (!$button.length) return;

            $.ajax({
                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', method: 'POST',
                data: {
                    action: 'courscribe_get_feedback_count',
                    nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>', // Make sure this nonce is correct
                    post_id: postId, post_type: postType, field_id: fieldId
                },
                success: function(response) {
                    if (response.success) {
                        $button.find('.feedback-count').text(response.data.count);
                        $button.removeClass('feedback-hidden'); // Show if there's a count or always show for admin?
                        if (response.data.count == 0 && <?php echo json_encode(!$is_client && !$is_wp_admin && !$is_studio_admin); ?>) { // Hide if zero and not admin/owner type
                            // $button.addClass('feedback-hidden');
                        } else {
                             $button.removeClass('feedback-hidden');
                        }
                    }
                }
            });
        }

        // Initial load of feedback counts for relevant users
        if (!<?php echo json_encode($is_client); ?>) {
            $('.courscribe-view-feedback-btn').each(function() {
                var $btn = $(this);
                updateFeedbackCountOnButton($btn.data('post-id'), $btn.data('post-type'), $btn.data('field-id'));
            });
        }
    });
    </script>
    <?php
    // Add this at the end of courscribe_expert_reviews_dashboard()

}