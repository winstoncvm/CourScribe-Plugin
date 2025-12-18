<?php
// courscribe/templates/courscribe-studio-page.php
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('courscribe_studio_page', 'courscribe_studio_page_shortcode');

function courscribe_studio_page_shortcode($atts) {
    global $wpdb;
    $site_url = home_url();
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $table_name = $wpdb->prefix . 'courscribe_invites';

    $atts = shortcode_atts(['studio_id' => 0], $atts);
    $studio_id = intval($atts['studio_id']) ?: (isset($_GET['studio_id']) ? intval($_GET['studio_id']) : 0);

    $query_args = [
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'no_found_rows' => true,
        'fields' => 'ids',
        'cache_results' => true,
    ];
    if ($studio_id) {
        $query_args['p'] = $studio_id;
    } elseif (in_array('collaborator', $user_roles)) {
        $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($studio_id) {
            $query_args['p'] = $studio_id;
        }
    } else {
        $query_args['author'] = $current_user->ID;
    }

    $query = new WP_Query($query_args);
    ob_start();

    if (!$query->have_posts()) {
        return ob_get_clean() . '<p>No studio found.</p>';
    }

    $query->the_post();
    $post_id = get_the_ID();
    $email = get_post_meta($post_id, '_studio_email', true);
    $website = get_post_meta($post_id, '_studio_website', true);
    $address = get_post_meta($post_id, '_studio_address', true);
    $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
    $is_public = get_post_meta($post_id, '_studio_is_public', true) ? 'Yes' : 'No';
    $tier = get_option('courscribe_tier', 'basics');

    if (!in_array('studio_admin', $user_roles) && !current_user_can('read_crscribe_studio', $post_id)) {
        return ob_get_clean() . '<p>You do not have permission to view this studio.</p>';
    }

    $curriculum_count = wp_count_posts('crscribe_curriculum')->publish;
    $course_count = wp_count_posts('crscribe_course')->publish;
    $module_count = wp_count_posts('crscribe_module')->publish;
    $lesson_count = wp_count_posts('crscribe_lesson')->publish;
    $collaborator_count = count(get_users([
        'role' => 'collaborator',
        'meta_key' => '_courscribe_studio_id',
        'meta_value' => $post_id,
        'fields' => 'ID',
    ]));
    ?>
    <!-- Styles and Scripts -->
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/chartjs.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>

    <div class="courscribe-studio-page w-100">
        <!-- Loader -->
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

        <!-- Studio Header -->
        <div class="container-fluid">
            <h2 class="courscribe-studio-h2"><?php the_title(); ?></h2>
            <h3 class="courscribe-h3-white">Welcome, <span><?php echo esc_html($current_user->user_login); ?></span></h3>
            <p class="courscribe-p-gray">Manage your studio and its curriculums below.</p>
        </div>

        <!-- Stats Row -->
        <div class="container-fluid py-4">
            <div class="row mt-3 mb-3">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card bg-gradient-dark">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-capitalize font-weight-bold text-white">Curriculums</p>
                                        <h5 class="font-weight-bolder mb-0 text-white"><?php echo esc_html($curriculum_count); ?></h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                        <i class="fas fa-book text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card bg-gradient-dark">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-capitalize font-weight-bold text-white">Courses</p>
                                        <h5 class="font-weight-bolder mb-0 text-white"><?php echo esc_html($course_count); ?></h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                        <i class="fas fa-laptop text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card bg-gradient-dark">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-capitalize font-weight-bold text-white">Modules</p>
                                        <h5 class="font-weight-bolder mb-0 text-white"><?php echo esc_html($module_count); ?></h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                        <i class="fas fa-book-open text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card bg-gradient-dark">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-capitalize font-weight-bold text-white">Lessons</p>
                                        <h5 class="font-weight-bolder mb-0 text-white"><?php echo esc_html($lesson_count); ?></h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                        <i class="fas fa-file-alt text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Studio Overview and Curriculums -->
            <div class="row">
                <!-- Studio Overview -->
                <div class="col-12 col-xl-4">
                    <div class="card bg-gradient-dark h-100">
                        <div class="card-header pb-0 p-3">
                            <h6 class="mb-0 text-white">Studio Overview</h6>
                        </div>
                        <div class="card-body p-3">
                            <ul class="list-group">
                                <li class="list-group-item border-0 px-0 text-white"><strong>Contact Email:</strong> <?php echo esc_html($email); ?></li>
                                <li class="list-group-item border-0 px-0 text-white"><strong>Website:</strong> <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a></li>
                                <li class="list-group-item border-0 px-0 text-white"><strong>Address:</strong> <?php echo nl2br(esc_html($address)); ?></li>
                                <li class="list-group-item border-0 px-0 text-white"><strong>Public:</strong> <?php echo esc_html($is_public); ?></li>
                                <li class="list-group-item border-0 px-0 text-white"><strong>Collaborators:</strong> <?php echo esc_html($collaborator_count); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Curriculums -->
                <div class="col-12 col-xl-8">
                    <div class="card bg-gradient-dark h-100">
                        <div class="card-header pb-0 p-3">
                            <h6 class="mb-0 text-white">Curriculums</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php
                            $curriculums = new WP_Query([
                                'post_type' => 'crscribe_curriculum',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'meta_query' => [
                                    ['key' => '_studio_id', 'value' => $post_id],
                                ],
                            ]);
                            if ($curriculums->have_posts()) :
                                ?>
                                <div class="row">
                                    <?php while ($curriculums->have_posts()) : $curriculums->the_post(); ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card bg-gradient-dark">
                                                <div class="card-body">
                                                    <h5 class="card-title text-white"><?php the_title(); ?></h5>
                                                    <p class="card-text text-white"><?php echo wp_trim_words(get_the_content(), 20); ?></p>
                                                    <a href="<?php echo get_permalink(); ?>" class="btn btn-outline-primary">View Curriculum</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; wp_reset_postdata(); ?>
                                </div>
                            <?php else : ?>
                                <p class="text-white">No curriculums found for this studio.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Studio Settings for Studio Admin -->
            <?php if (in_array('studio_admin', $user_roles)) : ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-gradient-dark">
                            <div class="card-header pb-0 p-3">
                                <h6 class="mb-0 text-white">Studio Settings</h6>
                            </div>
                            <div class="card-body p-3">
                                <form method="post" class="courscribe-studio-edit-form" enctype="multipart/form-data">
                                    <?php wp_nonce_field('courscribe_edit_studio', 'courscribe_edit_studio_nonce'); ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="courscribe_studio_title" class="text-white">Title:</label>
                                                <input type="text" name="courscribe_studio_title" id="courscribe_studio_title" class="form-control bg-gradient-dark text-white" value="<?php echo esc_attr(get_the_title()); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="courscribe_studio_email" class="text-white">Contact Email:</label>
                                                <input type="email" name="courscribe_studio_email" id="courscribe_studio_email" class="form-control bg-gradient-dark text-white" value="<?php echo esc_attr($email); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="courscribe_studio_description" class="text-white">Description:</label>
                                                <textarea name="courscribe_studio_description" id="courscribe_studio_description" class="form-control bg-gradient-dark text-white" required><?php echo esc_textarea(get_the_content()); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="courscribe_studio_website" class="text-white">Website URL:</label>
                                                <input type="url" name="courscribe_studio_website" id="courscribe_studio_website" class="form-control bg-gradient-dark text-white" value="<?php echo esc_attr($website); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="courscribe_studio_address" class="text-white">Address:</label>
                                                <textarea name="courscribe_studio_address" id="courscribe_studio_address" class="form-control bg-gradient-dark text-white"><?php echo esc_textarea($address); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" name="courscribe_studio_is_public" id="courscribe_studio_is_public" class="form-check-input" value="1" <?php checked($is_public, 'Yes'); ?>>
                                                <label class="form-check-label text-white" for="courscribe_studio_is_public">Make Studio Public</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <input type="submit" name="courscribe_submit_edit_studio" value="Update Studio" class="btn btn-primary">
                                        </div>
                                    </div>
                                </form>
                                <?php
                                if (isset($_POST['courscribe_submit_edit_studio']) && wp_verify_nonce($_POST['courscribe_edit_studio_nonce'], 'courscribe_edit_studio')) {
                                    $title = sanitize_text_field($_POST['courscribe_studio_title']);
                                    $description = sanitize_textarea_field($_POST['courscribe_studio_description']);
                                    $email = sanitize_email($_POST['courscribe_studio_email']);
                                    $website = esc_url_raw($_POST['courscribe_studio_website']);
                                    $address = sanitize_textarea_field($_POST['courscribe_studio_address']);
                                    $is_public = isset($_POST['courscribe_studio_is_public']) ? 'Yes' : 'No';

                                    wp_update_post([
                                        'ID' => $post_id,
                                        'post_title' => $title,
                                        'post_content' => $description,
                                    ]);
                                    update_post_meta($post_id, '_studio_email', $email);
                                    update_post_meta($post_id, '_studio_website', $website);
                                    update_post_meta($post_id, '_studio_address', $address);
                                    update_post_meta($post_id, '_studio_is_public', $is_public);

                                    echo '<div class="alert alert-success text-white" role="alert">Studio updated successfully!</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Collaborators -->
            <?php if (current_user_can('publish_crscribe_studios')) : ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-gradient-dark">
                            <div class="card-header pb-0 p-3">
                                <h6 class="mb-0 text-white">Collaborators</h6>
                            </div>
                            <div class="card-body p-3">
                                <p>
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('courscribe-invite-popup').style.display='flex'">Invite Collaborators</button>
                                </p>
                                <?php
                                $collaborators = get_users([
                                    'role' => 'collaborator',
                                    'meta_key' => '_courscribe_studio_id',
                                    'meta_value' => $post_id,
                                    'fields' => ['ID', 'user_email', 'user_login'],
                                    'number' => 10,
                                ]);
                                $invited_emails = $wpdb->get_results($wpdb->prepare("SELECT id, email, invite_code, status, expires_at FROM $table_name WHERE studio_id = %d LIMIT 50", $post_id));

                                $collaborator_lookup = [];
                                foreach ($collaborators as $collab) {
                                    $collaborator_lookup[$collab->user_email] = $collab;
                                }
                                $invited_emails_data = [];
                                foreach ($invited_emails as $invite) {
                                    $invited_emails_data[$invite->email] = [
                                        'id' => $invite->id,
                                        'invite_code' => $invite->invite_code,
                                        'status' => $invite->status,
                                        'expires_at' => $invite->expires_at,
                                    ];
                                }

                                if (isset($_POST['courscribe_revoke_invite']) && wp_verify_nonce($_POST['courscribe_revoke_nonce'], 'courscribe_revoke_invite')) {
                                    $invite_id = intval($_POST['invite_id']);
                                    $wpdb->delete($table_name, ['id' => $invite_id], ['%d']);
                                    echo '<div class="alert alert-success text-white" role="alert">Invite revoked successfully!</div>';
                                }

                                if (empty($invited_emails_data)) {
                                    echo '<p class="text-white">No collaborators invited yet.</p>';
                                } else {
                                    ?>
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Email</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Username</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Expires</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        foreach ($invited_emails_data as $email => $data) {
                                            $status = isset($collaborator_lookup[$email]) ? 'Accepted' : $data['status'];
                                            $expires_at = strtotime($data['expires_at']);
                                            $is_expired = $expires_at < time();
                                            $expires_display = $is_expired ? 'Expired' : date('Y-m-d H:i:s', $expires_at);
                                            $collaborator = $collaborator_lookup[$email] ?? null;
                                            ?>
                                            <tr>
                                                <td class="text-white"><?php echo esc_html($email); ?></td>
                                                <td class="text-white"><?php echo $collaborator ? esc_html($collaborator->user_login) : '-'; ?></td>
                                                <td>
                                                    <?php if ($status === 'Pending' && !$is_expired) : ?>
                                                        <span class="badge badge-sm bg-gradient-success"><?php echo esc_html($status); ?></span>
                                                    <?php elseif ($status === 'Accepted' && $collaborator) : ?>
                                                        <span class="badge badge-sm bg-gradient-success"><?php echo esc_html($status); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-white"><?php echo esc_html($expires_display); ?></td>
                                                <td>
                                                    <?php if ($status === 'Pending' && !$is_expired) : ?>
                                                        <form method="post" style="display:inline;">
                                                            <?php wp_nonce_field('courscribe_revoke_invite', 'courscribe_revoke_nonce'); ?>
                                                            <input type="hidden" name="invite_id" value="<?php echo esc_attr($data['id']); ?>">
                                                            <input type="submit" name="courscribe_revoke_invite" value="Remove" class="courscribe-revoke-button">
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                                <!-- Invite Popup -->
                                <div id="courscribe-invite-popup" class="courscribe-popup" style="display: none;">
                                    <div class="courscribe-popup-content">
                                        <h3>Invite Collaborators</h3>
                                        <form method="post" class="courscribe-invite-form">
                                            <?php wp_nonce_field('courscribe_invite_collaborators', 'courscribe_invite_nonce'); ?>
                                            <div class="form-group">
                                                <label for="courscribe_invite_emails" class="text-white">Collaborator Emails (comma-separated):</label>
                                                <textarea name="courscribe_invite_emails" id="courscribe_invite_emails" class="form-control bg-gradient-dark text-white" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <input type="submit" name="courscribe_submit_invite" value="Send Invites" class="btn btn-primary">
                                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('courscribe-invite-popup').style.display='none'">Close</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php
                                if (isset($_POST['courscribe_submit_invite']) && wp_verify_nonce($_POST['courscribe_invite_nonce'], 'courscribe_invite_collaborators')) {
                                    $emails = array_map('sanitize_email', array_filter(array_map('trim', explode(',', $_POST['courscribe_invite_emails']))));
                                    $collaborator_limit = $tier === 'basics' ? 1 : ($tier === 'plus' ? 3 : PHP_INT_MAX);
                                    $emails_to_invite = array_slice($emails, 0, max(0, $collaborator_limit - $collaborator_count));
                                    $error_messages = [];

                                    if (empty($emails_to_invite) && !empty($emails)) {
                                        $error_messages[] = 'Collaborator limit reached for your tier.';
                                    } else {
                                        $register_page_id = get_option('courscribe_register_page');
                                        $invite_url_base = $register_page_id ? get_permalink($register_page_id) : home_url('/register');
                                        foreach ($emails_to_invite as $email) {
                                            if (!is_email($email)) {
                                                $error_messages[] = 'Invalid email: ' . esc_html($email);
                                                continue;
                                            }
                                            $existing_invite = $wpdb->get_var($wpdb->prepare(
                                                "SELECT id FROM $table_name WHERE email = %s AND studio_id = %d AND status = 'Pending' AND expires_at > %s",
                                                $email,
                                                $post_id,
                                                current_time('mysql')
                                            ));
                                            if ($existing_invite) {
                                                $error_messages[] = 'Invite already sent to ' . esc_html($email);
                                                continue;
                                            }
                                            $invite_code = wp_generate_password(12, false);
                                            $wpdb->insert($table_name, [
                                                'email' => $email,
                                                'invite_code' => $invite_code,
                                                'studio_id' => $post_id,
                                                'status' => 'Pending',
                                                'created_at' => current_time('mysql'),
                                                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                                            ]);
                                            $invite_url = add_query_arg(['invite_code' => $invite_code, 'email' => urlencode($email)], $invite_url_base);
                                            $mail_result = wp_mail(
                                                $email,
                                                'Courscribe Collaborator Invitation',
                                                "You have been invited to join a Courscribe studio. Register here: $invite_url\n\nThis invitation expires on " . date('Y-m-d H:i:s', strtotime('+7 days')) . ".",
                                                ['Content-Type: text/plain; charset=UTF-8']
                                            );
                                            if (!$mail_result) {
                                                $error_messages[] = 'Failed to send email to ' . esc_html($email);
                                                $wpdb->delete($table_name, ['email' => $email, 'studio_id' => $post_id], ['%s', '%d']);
                                            }
                                        }
                                        if (empty($error_messages) && !empty($emails_to_invite)) {
                                            echo '<div class="alert alert-success text-white" role="alert">Invites sent successfully!</div>';
                                        } elseif (!empty($error_messages)) {
                                            echo '<div class="alert alert-danger text-white" role="alert">Errors occurred:<br>' . implode('<br>', array_map('esc_html', $error_messages)) . '</div>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .courscribe-studio-page { max-width: 1480px; margin: 0 auto; padding: 20px; background: #2F2E30; border-radius: 21px; }
            .courscribe-studio-page .card { background: #3A393C; color: #FFFFFF; border: none; border-radius: 10px; }
            .courscribe-studio-page .card-header { background: transparent; border-bottom: 1px solid #555; }
            .courscribe-studio-page .list-group-item { background: transparent; color: #FFFFFF; }
            .courscribe-studio-edit-form .form-control, .courscribe-invite-form .form-control {
                background: #3A393C; color: #FFFFFF; border: 1px solid #555; border-radius: 5px;
            }
            .courscribe-studio-edit-form .btn-primary, .courscribe-invite-form .btn-primary {
                background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); border: none;
            }
            .courscribe-invite-form .btn-secondary { background: #555; border: none; }
            .courscribe-revoke-button { background: #d63638; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 5px; }
            .courscribe-revoke-button:hover { background: #b32d2e; }
            .courscribe-popup { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center; z-index: 1000; }
            .courscribe-popup-content { background: #2F2E30; padding: 20px; max-width: 500px; width: 90%; border-radius: 10px; color: #FFFFFF; }
            .alert-success { background: #28a745; border: none; border-radius: 5px; }
            .alert-danger { background: #dc3232; border: none; border-radius: 5px; }
            .form-check-input:checked { background-color: #F15538; border-color: #F15538; }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    document.getElementById('courscribe-loader').style.display = 'none';
                }, 10000);
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
                }, 10000);


                // Initialize Settings Form
                const settingsForm = document.querySelector('.courscribe-settings-form');
                if (settingsForm) {
                    settingsForm.addEventListener('submit', function(e) {
                        const loader = document.getElementById('courscribe-loader');
                        if (loader) {
                            loader.style.display = 'flex';
                        }
                    });
                }

                // Initialize Studio Page Invite Form
                const inviteForm = document.querySelector('.courscribe-invite-form');
                if (inviteForm) {
                    inviteForm.addEventListener('submit', function(e) {
                        const loader = document.getElementById('courscribe-loader');
                        if (loader) {
                            loader.style.display = 'flex';
                        }
                    });
                }

            });
        </script>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
?>