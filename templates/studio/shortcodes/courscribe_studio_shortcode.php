<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode for studio page
add_shortcode('courscribe_studio', 'courscribe_studio_shortcode');

function courscribe_studio_shortcode() {
    global $wpdb;
    $site_url = home_url();
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $table_name = $wpdb->prefix . 'courscribe_invites';

    // Early returns for access control
    if (!is_user_logged_in()) {
        return courscribe_retro_tv_error("You must be logged in to view a studio.");
        $redirect_url = home_url('/courscribe-sign-in');
         echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            Swal.fire({
            title: 'Hold on!',
            text: 'You must be logged in to view a studio.',
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#E4B26F',
            allowOutsideClick: false,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Take me there',
            cancelButtonText: 'Cancel'
            }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '" . esc_url($redirect_url) . "';
            }
            });
            </script>";
            return '';
    }

    // Fetch subscription details for studio_admin
    $subscription = null;
    $tier = 'basics'; // Default tier
    $is_subscription_active = false;
    if (in_array('studio_admin', $user_roles)) {
        $subscription_orders = wc_get_orders([
            'limit'  => 1,
            'status' => ['completed', 'processing', 'on-hold', 'wc-wps_renewal'],
            'type'   => 'shop_order',
            'customer_id' => $current_user->ID,
        ]);

        foreach ($subscription_orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes') {
                    $subscription = [
                        'product_name' => $product->get_name(),
                        'status' => $order->get_meta('wps_subscription_status') ?: 'active',
                        'price' => $item->get_total(),
                        'start_date' => $order->get_meta('wps_schedule_start') ?: $order->get_date_created()->date('Y-m-d'),
                        'next_payment' => $order->get_meta('wps_next_payment_date') ?: '',
                        'end_date' => $order->get_meta('wps_susbcription_end') && $order->get_meta('wps_susbcription_end') != '0' ? $order->get_meta('wps_susbcription_end') : 'Indefinite',
                    ];
                    $is_subscription_active = in_array($subscription['status'], ['active', 'pending-cancel']);
                    // Map product to tier
                    $product_name = strtolower($product->get_name());
                    if (strpos($product_name, 'pro') !== false) {
                        $tier = 'pro';
                    } elseif (strpos($product_name, 'plus') !== false || strpos($product_name, '+') !== false) {
                        $tier = 'plus';
                    } else {
                        $tier = 'basics';
                    }
                    break;
                }
            }
            if ($subscription) break;
        }

        // Redirect to pricing page if no active subscription
        if (!$is_subscription_active && in_array('studio_admin', $user_roles)) {
            $pricing_page_id = get_option('courscribe_pricing_page');
            $pricing_url = $pricing_page_id ? get_permalink($pricing_page_id) : home_url('/select-tribe');
            error_log('Courscribe: User ' . $current_user->ID . ' has no active subscription, redirecting to pricing page: ' . $pricing_url);
            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            Swal.fire({
            title: 'Hold on!',
            text: 'You have no active subscription. You’ll be redirected to the pricing page.',
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#E4B26F',
            allowOutsideClick: false,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Take me there',
            cancelButtonText: 'Cancel'
            }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '" . esc_url($pricing_url) . "';
            }
            });
            </script>";
            return '';
        }
        
    }

    // Optimized WP_Query for studio
    $query_args = [
        'post_type'      => 'crscribe_studio',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'cache_results'  => true,
    ];

    if (in_array($in_array_args[0] == 'collaborator', $user_roles)) {
        $studio_guest_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($studio_guest_id) {
            $query_args['guest_id'] = $studio_guest_id;
        }
    } else {
        $query_args['page_author'] = $query_args['author'] = $current_user->ID;
    }

    $cache_key = 'courscribe_studio_' . $current_user->ID;
    $query = wp_cache_get($cache_key);
    if ($query === false) {
        $query = new WP_Query($query_args);
        wp_cache_set($cache_key, $query, '', 21); // Cache for 5 minutes
    }
    

    

    if (!$query->have_posts() && in_array('studio_admin', $user_roles)) {
        $create_studio_page = get_option('courscribe_create_studio_page');
        if ($create_studio_page) {
            error_log('Courscribe: User ' . $current_user->ID . ' has no studio, redirecting to Create Studio: ' . get_permalink($create_studio_page));
            wp_redirect($create_studio_page->get_permalink());
            exit;
        } else {
            return courscribe_retro_tv_error("Failed to create studio page");
            $redirect_url = home_url('/create-studio');
            echo "
            <script src='https://cdn-js.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            Swal.fire({
                title: 'Hold on!',
                text: 'You must create a studio first.',
                icon: 'warning sign',
                showCancelButton: true,
                confirmButtonColor: '#E4A26F',
                allowOutsideClick: true,
                cancelButtonColor: '#d33',
                confirmButtonText: 'OK',
                cancelButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '" . esc_url($redirect_url). "';
                }
            });
            </script>";
            return ob_get_clean();
        }
    }

    if ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $email = get_post_meta($post_id, '_studio_email', true);
        $website = get_post_meta($post_id, '_studio_website', true);
        $address = get_post_meta($post_id, '_studio_address', true);
        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
        $is_public = get_post_meta($post_id, '_studio_is_public', true) ? 'Yes' : 'No';

        // Check permission to view studio
        $can_view = in_array('studio_admin', $user_roles) || current_user_can('read_crscribe_studio', $post_id);
        if (!$can_view) {
            error_log('Courscribe: User ' . $current_user->ID . ' denied access to view studio ID ' . $post_id . '. Roles: [' . implode(', ', $user_roles) . ']');
            return courscribe_retro_tv_error("You do not have permission to view this studio");
        }

        // AJAX handler for fetching user permissions
        add_action('wp_ajax_courscribe_get_user_permissions', function() {
            check_ajax_referer('courscribe_get_permissions', 'nonce');
            $user_id = intval($_POST['user_id']);
            $permissions = get_user_meta($user_id, '_courscribe_collaborator_permissions', true) ?: [];
            wp_send_json($permissions);
        });

        // AJAX handler for marking tour as completed
        add_action('wp_ajax_courscribe_complete_tour', function() {
            check_ajax_referer('courscribe_complete_tour', 'nonce');
            $user_id = get_current_user_id();
            update_user_meta($user_id, '_courscribe_studio_tour_completed', '1');
            wp_send_json_success();
        });

        $current_hour = date('H');
        $greeting = ($current_hour >= 5 && $current_hour < 12) ? 'Good morning' : ($current_hour >= 12 && $current_hour < 18 ? 'Good afternoon' : 'Good evening');

        // Calculate stats for the stats row
        $curriculum_count = wp_count_posts('crscribe_curriculum')->publish;
        $course_count = wp_count_posts('crscribe_course')->publish;
        $module_count = wp_count_posts('crscribe_module')->publish;
        $lesson_count = wp_count_posts('crscribe_lesson')->publish;

        $args = [
            'post_type'      => ['crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'],
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'meta_query'     => [
                [
                    'key'     => '_studio_id',
                    'value'   => $post_id,
                    'compare' => '=='
                ]
            ],
            'fields'         => 'ids',
            'posts_per_page' => -1
        ];

        // $curr_query_args = array(
        //     'post_type' => 'crscribe_curriculum',
        //     'post_status' => ['publish', 'draft', 'pending', 'future'],
        //     'posts_per_page' => -1,
        //     'meta_query' => array(
        //         'relation' => 'AND',
        //         array(
        //             'key' => '_curriculum_status',
        //             'value' => 'archived',
        //             'compare' => '!=',
        //         ),
        //     ),
        // );

        $studio_posts = new WP_Query($args);
        $posts = $studio_posts->posts;
        // $curr = new WP_Query($curr_query_args);
        //get curr count
        // Get post count
        // $curr_count = count($curr->posts);
        // error_log("curr count is: " . $curr_count);

        // Log post titles
        //    if ($curr->have_posts()) {
        //         foreach ($curr->posts as $post) {
        //             $title = get_the_title($post);
        //             $studio_id = get_post_meta($post->ID, '_studio_id', true);

        //             error_log("Curriculum Title: {$title} | _studio_id: {$studio_id}");
        //             error_log($post_id);
        //         }
        //     }


        // Initialize counts
        $curriculum_count = 0;
        $course_count = 0;
        $module_count = 0;
        $lesson_count = 0;

        // Count each post type
        foreach ($posts as $post_id) {
            $post_type = get_post_type($post_id);
            if ($post_type === 'crscribe_curriculum') {
                $curriculum_count++;
            } elseif ($post_type === 'crscribe_course') {
                $course_count++;
            } elseif ($post_type === 'crscribe_module') {
                $module_count++;
            } elseif ($post_type === 'crscribe_lesson') {
                $lesson_count++;
            }
        }
         wp_enqueue_script(
            'hair-diary-js', 
            plugin_dir_url(__FILE__) . '../../../assets/js/ui/sidebar.js', 
            [], 
            '1.0.10'
        );
        // Popup for zero curriculums
        if ($curriculum_count === 0 && in_array('studio_admin', $user_roles)) {
            $curriculum_manager_page_id = get_option('courscribe_curriculum_manager_page');
            $curriculum_manager_url = $curriculum_manager_page_id ? get_permalink($curriculum_manager_page_id) : home_url('/curriculums');
            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Check session storage to show popup only once per session
                // if (!sessionStorage.getItem('courscribe_zero_curriculum_popup_shown')) {
                    Swal.fire({
                        title: 'Welcome to Your Studio!',
                        text: 'It looks like you haven’t created any curriculums yet. Would you like to create your first curriculum now?',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#E4B26F',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, let’s create one!',
                        cancelButtonText: 'Maybe later',
                        allowOutsideClick: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '" . esc_url($curriculum_manager_url) . "';
                        }
                        // Set flag to prevent popup from showing again in this session
                        sessionStorage.setItem('courscribe_zero_curriculum_popup_shown', 'true');
                    });
                // }
            });
            </script>";
        }
        $collaborator_count = count(get_users([
            'role'       => 'collaborator',
            'meta_key'   => '_courscribe_studio_id',
            'meta_value' => $post_id,
            'fields'     => 'ID',
        ]));
        $curriculum_remaining = $tier === 'basics' ? max(0, 1 - $curriculum_count) : ($tier === 'plus' ? max(0, 1 - $curriculum_count) : 'Unlimited');
        $course_remaining = $tier === 'basics' ? max(0, 1 - $course_count) : 'Unlimited';
        $collaborator_remaining = $tier === 'basics' ? max(0, 1 - $collaborator_count) : ($tier === 'plus' ? max(0, 3 - $collaborator_count) : 'Unlimited');

        // Check if user has completed the tour
        $tour_completed = get_user_meta($current_user->ID, '_courscribe_studio_tour_completed', true);
        $run_tour = in_array('studio_admin', $user_roles) && !$tour_completed;
        wp_enqueue_style(
            'myavana-curriculum-frontend-styles',
            plugin_dir_url(__FILE__) . '../../../assets/css/curriculum-frontend.css',
            [],
            '2.0.1'
        );
        wp_enqueue_style(
            'myavana-studio-d-styles',
            plugin_dir_url(__FILE__) . '../../../assets/css/studio.css',
            [],
            '2.0.1'
        );
        wp_enqueue_style(
            'myavana-wrapper-d-styles',
            plugin_dir_url(__FILE__) . '../../../assets/css/wrapper.css',
            [],
            '2.0.1'
        );
        ob_start();
        ?>

        <!-- Styles -->
        <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">

        <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/studio.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
        <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-icons.css">
        <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-svg.css">
        <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">
        <link rel="stylesheet" href="https://unpkg.com/@sjmc11/tourguidejs@0.0.22/dist/css/tour.min.css">

        <!-- Scripts -->
        <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
        <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/studio/studio.js" defer></script>
        <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
        <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
        <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
        <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://unpkg.com/@sjmc11/tourguidejs@0.0.22/dist/tour.js" defer crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        
        <div class="main-container">

            <div class="sidebar" id="sidebar">
                <button class="toggle-sidebar" id="toggleSidebar">
                    <div id="bar1" class="bars"></div>
                    <div id="bar2" class="bars"></div>
                    <div id="bar3" class="bars"></div>
                </button>
                <div class="sidebar-title">Studio Menu</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="#" class="active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="menu-text">Studio</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/curriculums">
                            <i class="fas fa-book"></i>
                            <span class="menu-text">Curriculums</span>
                        </a>
                    </li>
                </ul>
                <div class="sidebar-title">Management</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/collaborators">
                            <i class="fas fa-users"></i>
                            <span class="menu-text">Collaborators</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/profile">
                            <i class="fas fa-user"></i>
                            <span class="menu-text">Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url($site_url); ?>/studio-settings">
                            <i class="fas fa-cog"></i>
                            <span class="menu-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="content">
                
                <!-- Studio Header -->
                <div class="studio-header">
                    <h2 class="courscribe-studio-h2"><?php echo esc_html(get_post_field('post_title', $post_id)); ?></h2>
                    <h3 class="courscribe-h3-white"><?php echo esc_html($greeting); ?>, <span><?php echo esc_html($current_user->user_login); ?></span></h3>
                    <p style="color: #999; margin-bottom: 1rem;">Empowering educators to create exceptional learning experiences</p>
                    <div class="studio-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Created: March 2024</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo esc_html($collaborator_count); ?> Collaborators</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-book"></i>
                            <span><?php echo esc_html($curriculum_count); ?> Curriculums</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Active Status</span>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <a class="card-action" href="<?php echo esc_url($site_url); ?>/curriculums">
                                <i class="fas fa-plus"></i> New Curriculum
                            </a>
                            <?php if (current_user_can('publish_crscribe_studios')) : ?>
                            <button class="card-action" onclick="document.getElementById('courscribe-invite-popup-subsequent').style.display='flex'">
                                <i class="fas fa-user-plus"></i> Invite Collaborator
                            </button>
                            <?php endif ?>
                            <a class="card-action" href="<?php echo esc_url($site_url); ?>/studio-settings">
                                <i class="fas fa-cog"></i> Studio Settings
                            </a>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i>
                                Studio Analytics
                            </h3>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #E4B26F;"><?php echo esc_html($curriculum_count); ?></div>
                                <div style="color: #999; font-size: 0.9rem;">Total Curriculums</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #E4B26F;"><?php echo esc_html($course_count); ?></div>
                                <div style="color: #999; font-size: 0.9rem;">Total Courses</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #E4B26F;"><?php echo esc_html($module_count); ?></div>
                                <div style="color: #999; font-size: 0.9rem;">Total Modules</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #E4B26F;"><?php echo esc_html($lesson_count); ?></div>
                                <div style="color: #999; font-size: 0.9rem;">Total Lessons</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Collaborators and User Management -->
                <?php if (current_user_can('publish_crscribe_studios')) : ?>
                    <div class="collaborators-section mt-4" id="tour-collaborators">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-users"></i>
                                Team Collaborators
                            </h2>
                            <button class="card-action" onclick="document.getElementById('courscribe-invite-popup-subsequent').style.display='flex'">
                                <i class="fas fa-user-plus"></i> Invite New Member
                            </button>
                        </div>
                        <div class="collaborators-grid">
                            
                            <?php
                            $collaborators = get_users([
                                'role'       => 'collaborator',
                                'meta_key'   => '_courscribe_studio_id',
                                'meta_value' => $post_id,
                                'fields'     => ['ID', 'user_email', 'user_login'],
                                'number'     => 10,
                            ]);
                            $invited_emails = $wpdb->get_results($wpdb->prepare("SELECT id, email, invite_code, status, expires_at FROM $table_name WHERE studio_id = %d LIMIT 50", $post_id));

                            $collaborator_lookup = [];
                            foreach ($collaborators as $collab) {
                                $collaborator_lookup[$collab->user_email] = $collab;
                            }
                            $invited_emails_data = [];
                            foreach ($invited_emails as $invite) {
                                $invited_emails_data[$invite->email] = [
                                    'id'         => $invite->id,
                                    'invite_code' => $invite->invite_code,
                                    'status'     => $invite->status,
                                    'expires_at' => $invite->expires_at,
                                ];
                            }

                            // Handle revoke invite
                            if (isset($_POST['courscribe_revoke_invite']) && wp_verify_nonce($_POST['courscribe_revoke_nonce'], 'courscribe_revoke_invite')) {
                                $invite_id = intval($_POST['invite_id']);
                                $delete_result = $wpdb->delete($table_name, ['id' => $invite_id], ['%d']);
                                if ($delete_result !== false) {
                                    echo '<p>Invite revoked successfully!</p>';
                                    error_log('Courscribe: Invite revoked for ID ' . $invite_id . ' by user ' . $current_user->ID);
                                    echo '<meta http-equiv="refresh" content="0">';
                                } else {
                                    echo '<p>Error revoking invite.</p>';
                                    error_log('Courscribe: Failed to revoke invite ID ' . $invite_id . ', Error: ' . $wpdb->last_error);
                                }
                            }

                            // Handle user management
                            if (isset($_POST['courscribe_update_user']) && wp_verify_nonce($_POST['courscribe_user_nonce'], 'courscribe_update_user')) {
                                $user_id = intval($_POST['user_id']);
                                $user = get_userdata($user_id);
                                if ($user && in_array($user->ID, wp_list_pluck($collaborators, 'ID'))) {
                                    $new_email = sanitize_email($_POST['user_email']);
                                    $new_username = sanitize_user($_POST['user_username']);
                                    $permissions = isset($_POST['collaborator_permissions']) ? array_map('sanitize_text_field', $_POST['collaborator_permissions']) : [];

                                    $update_args = ['ID' => $user_id];
                                    if ($new_email && $new_email !== $user->user_email && !email_exists($new_email)) {
                                        $update_args['user_email'] = $new_email;
                                    }
                                    if ($new_username && $new_username !== $user->user_login && !username_exists($new_username)) {
                                        $update_args['user_login'] = $new_username;
                                        $update_args['user_nicename'] = sanitize_title($new_username);
                                    }

                                    $updated = wp_update_user($update_args);
                                    if (!is_wp_error($updated)) {
                                        update_user_meta($user_id, '_courscribe_collaborator_permissions', $permissions);
                                        echo '<p>User updated successfully!</p>';
                                        error_log('Courscribe: User ' . $user_id . ' updated by ' . $current_user->ID);
                                    } else {
                                        echo '<p class="courscribe-error">Error updating user: ' . esc_html($updated->get_error_message()) . '</p>';
                                        error_log('Courscribe: Failed to update user ' . $user_id . ': ' . $updated->get_error_message());
                                    }
                                }
                                echo '<meta http-equiv="refresh" content="0">';
                            }

                            // Handle user deletion
                            if (isset($_POST['courscribe_delete_user']) && wp_verify_nonce($_POST['courscribe_delete_nonce'], 'courscribe_delete_user')) {
                                $user_id = intval($_POST['user_id']);
                                $user = get_userdata($user_id);
                                if ($user && in_array($user->ID, wp_list_pluck($collaborators, 'ID'))) {
                                    $wpdb->delete($table_name, ['email' => $user->user_email, 'studio_id' => $post_id], ['%s', '%d']);
                                    wp_delete_user($user_id);
                                    echo '<p>User deleted successfully!</p>';
                                    error_log('Courscribe: User ' . $user_id . ' deleted by ' . $current_user->ID);
                                    echo '<meta http-equiv="refresh" content="0">';
                                }
                            }

                            if (empty($invited_emails_data)) {
                                echo '<p>No collaborators invited yet.</p>';
                            } else {
                                ?>
                                    
                                <?php
                                foreach ($invited_emails_data as $email => $data) {
                                    $status = isset($collaborator_lookup[$email]) ? 'Accepted' : $data['status'];
                                    $expires_at = strtotime($data['expires_at']);
                                    $is_expired = $expires_at < time();
                                    $expires_display = $is_expired ? 'Expired' : date('Y-m-d H:i:s', $expires_at);
                                    $collaborator = $collaborator_lookup[$email] ?? null;
                                    ?>
                                    <div class="collaborator-card">
                                        <div class="collaborator-info">
                                            <div class="collaborator-avatar">EL</div>
                                            <div class="collaborator-details">
                                                <h4><?php echo $collaborator ? esc_html($collaborator->user_login) : '-'; ?></h4>
                                                <div class="collaborator-role">Subject Matter Expert</div>
                                            </div>
                                        </div>
                                        <div class="collaborator-status">
                                            
                                            <?php if ($status === 'Pending' && !$is_expired) : ?>
                                                <div class="status-dot pending"></div>
                                                <span style="color: #999; font-size: 0.9rem;"><?php echo esc_html($status); ?></span>
                                            <?php elseif ($status === 'Accepted' && $collaborator) : ?>
                                                <div class="status-dot accepted"></div>
                                                <span style="color: #4CAF50; font-size: 0.9rem;"><?php echo esc_html($status); ?></span>
                                            <?php endif; ?>
                                            
                                        </div>
                                        <p>Expires: <span><?php echo esc_html($expires_display); ?></span></p>
                                        <div class="permissions-grid">
                                            <div class="permission-item">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Create Curriculum</span>
                                            </div>
                                            <div class="permission-item active">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Edit Content</span>
                                            </div>
                                            <div class="permission-item">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Manage Modules</span>
                                            </div>
                                            <div class="permission-item">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Delete Curriculum</span>
                                            </div>
                                            <?php echo esc_html($email); ?>
                                        </div>
                                        <div class="collaborator-actions">
                                            <button class="action-btn" onclick="editCollaborator('emily')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn danger">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                            <?php if ($status === 'Pending' && !$is_expired) : ?>
                                                <form method="post" style="display:inline;">
                                                    <?php wp_nonce_field('courscribe_revoke_invite', 'courscribe_revoke_nonce'); ?>
                                                    <input  type="hidden" name="invite_id" value="<?php echo esc_attr($data['id']); ?>">
                                                    <button type="submit" name="courscribe_revoke_invite" value="Remove" class="action-btn">
                                                        <i class="fas fa-user-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($status === 'Accepted' && $collaborator) : ?>
                                                <button class="action-btn" onclick="openPermissionsModal(<?php echo esc_attr($collaborator->ID); ?>, '<?php echo esc_attr($email); ?>', '<?php echo esc_attr($collaborator->user_login); ?>')">Edit</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                    
                                
                            <?php } ?>
                        </div>
                                
                    </div>

                    <!-- Invite Collaborators Popup -->
                    <?php
                    $collaborator_limit = $tier === 'basics' ? 1 : ($tier === 'plus' ? 3 : PHP_INT_MAX);
                    ?>
                    <div id="courscribe-invite-popup-subsequent" class="courscribe-popup" style="display: none;">
                        <div class="courscribe-popup-content">
                            <h3>Invite Collaborators</h3>
                            <p>Invite up to <?php echo $tier === 'pro' ? 'unlimited' : $collaborator_limit; ?> collaborators to your studio.</p>
                            <form method="post" class="courscribe-invite-form">
                                <?php wp_nonce_field('courscribe_invite_collaborators', 'courscribe_invite_nonce'); ?>
                                <div class="form-group">
                                    <label for="courscribe_invite_emails_subsequent">Collaborator Emails (comma-separated):</label>
                                    <textarea name="courscribe_invite_emails" id="courscribe_invite_emails_subsequent" class="form-control" required></textarea>
                                </div>
                                <div class="form-group">
                                    <input type="submit" name="courscribe_submit_invite" value="Send Invites" class="btn btn-primary">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('courscribe-invite-popup-subsequent').style.display='none'">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    if (isset($_POST['courscribe_submit_invite']) && wp_verify_nonce($_POST['courscribe_invite_nonce'], 'courscribe_invite_collaborators')) {
                        $emails = array_map('sanitize_email', array_filter(array_map('trim', explode(',', $_POST['courscribe_invite_emails']))));
                        $current_collaborators = count($collaborators);
                        $emails_to_invite = array_slice($emails, 0, max(0, $collaborator_limit - $current_collaborators));
                        $error_messages = [];
                        error_log('Courscribe: Invite attempt - User ' . $current_user->ID . ', Studio ID: ' . $post_id . ', Tier: ' . $tier . ', Current collaborators: ' . $current_collaborators . ', Limit: ' . $collaborator_limit . ', Emails to invite: ' . count($emails_to_invite));

                        if (empty($emails_to_invite) && !empty($emails)) {
                            $error_messages[] = 'Collaborator limit reached for your tier (' . $tier . ': ' . $collaborator_limit . ').';
                        } else {
                            $register_page_id = get_option('courscribe_register_page');
                            $invite_url_base = $register_page_id ? get_permalink($register_page_id) : home_url('/register');
                            $email_batches = array_chunk($emails_to_invite, 5);
                            foreach ($email_batches as $batch) {
                                foreach ($batch as $email) {
                                    if (!is_email($email)) {
                                        $error_messages[] = 'Invalid email: ' . esc_html($email);
                                        error_log('Courscribe: Invalid email ' . $email . ' provided for invite');
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
                                        error_log('Courscribe: Duplicate invite attempt for ' . $email . ', Studio ID: ' . $post_id);
                                        continue;
                                    }
                                    $invite_code = wp_generate_password(12, false);
                                    $insert_result = $wpdb->insert($table_name, [
                                        'email'      => $email,
                                        'invite_code' => $invite_code,
                                        'studio_id'  => $post_id,
                                        'status'     => 'Pending',
                                        'created_at' => current_time('mysql'),
                                        'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                                    ]);
                                    if ($insert_result === false) {
                                        $error_messages[] = 'Failed to save invite for ' . esc_html($email);
                                        error_log('Courscribe: Failed to insert invite for ' . $email . ', Error: ' . $wpdb->last_error);
                                        continue;
                                    }
                                    $invite_url = add_query_arg(['invite_code' => $invite_code, 'email' => urlencode($email)], $invite_url_base);
                                    $mail_result = wp_mail(
                                        $email,
                                        'Courscribe Collaborator Invitation',
                                        "You have been invited to join a Courscribe studio. Register here: $invite_url\n\nThis invitation expires on " . date('Y-m-d H:i:s', strtotime('+7 days')) . ".",
                                        ['Content-Type: text/plain; charset=UTF-8']
                                    );
                                    if (!$mail_result) {
                                        $error_messages[] = 'Failed to send email to ' . esc_html($email);
                                        error_log('Courscribe: Failed to send invite email to ' . $email . ', Studio ID: ' . $post_id . ', Invite URL: ' . $invite_url);
                                        $wpdb->delete($table_name, ['email' => $email, 'studio_id' => $post_id], ['%s', '%d']);
                                    } else {
                                        error_log('Courscribe: Invite sent to ' . $email . ', Studio ID: ' . $post_id . ', Invite URL: ' . $invite_url);
                                    }
                                }
                                wp_cache_flush();
                            }
                            if (empty($error_messages) && !empty($emails_to_invite)) {
                                echo '<p>Invites sent successfully!</p>';
                            } elseif (!empty($error_messages)) {
                                echo '<p>Errors occurred:<br>' . implode('<br>', array_map('esc_html', $error_messages)) . '</p>';
                            }
                        }
                    }
                    ?>
                <?php endif; ?>

               
                <div class="cs-studio-container">
                    <div class="cs-studio-grid">
                        <!-- Studio Overview -->
                        <div class="cs-studio-card" id="cs-tour-studio-overview">
                            <div class="cs-card-header">
                                <h6 class="cs-card-title">
                                    <i class="fas fa-building"></i>
                                    Studio Overview
                                </h6>
                            </div>
                            <div class="cs-card-body">
                                <ul class="cs-info-list">
                                    <li class="cs-info-item">
                                        <span class="cs-info-label">Contact Email:</span>
                                        <span class="cs-info-value"><?php echo esc_html($email); ?></span>
                                    </li>
                                    <li class="cs-info-item">
                                        <span class="cs-info-label">Website:</span>
                                        <span class="cs-info-value">
                                            <a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_url($website); ?></a>
                                        </span>
                                    </li>
                                    <li class="cs-info-item">
                                        <span class="cs-info-label">Address:</span>
                                        <span class="cs-info-value">
                                            <?php echo nl2br(esc_html($address)); ?>
                                    </li>
                                    <li class="cs-info-item">
                                        <span class="cs-info-label">Public:</span>
                                        <span class="cs-info-value"><?php echo esc_html($is_public); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Studio Tier -->
                        <div class="cs-studio-card" id="cs-tour-studio-tier">
                            <div class="cs-card-header">
                                <h6 class="cs-card-title">
                                    <i class="fas fa-crown"></i>
                                    Studio Tier: <?php echo esc_html(ucfirst($tier)); ?>
                                </h6>
                            </div>
                            <div class="cs-card-body">
                                <div class="cs-subscription-badge">
                                    <i class="fas fa-check-circle"></i>
                                    Active Pro Subscription
                                </div>
                                
                                <?php if ($subscription && in_array('studio_admin', $user_roles)) : ?>
                                    <ul class="cs-info-list" style="margin-bottom: 2rem;">
                                        <li class="cs-info-item">
                                            <span class="cs-info-label">Plan:</span>
                                            <span class="cs-info-value"><?php echo esc_html($subscription['product_name']); ?></span>
                                        </li>
                                        <li class="cs-info-item">
                                            <span class="cs-info-label">Status:</span>
                                            <span class="cs-info-value"><?php echo esc_html(ucfirst($subscription['status'])); ?></span>
                                        </li>
                                        <li class="cs-info-item">
                                            <span class="cs-info-label">Price:</span>
                                            <span class="cs-info-value"><?php echo wc_price($subscription['price']); ?></span>
                                        </li>
                                        <li class="cs-info-item">
                                            <span class="cs-info-label">Next Payment:</span>
                                            <span class="cs-info-value"><?php echo esc_html($subscription['next_payment'] ?: 'N/A'); ?></span>
                                        </li>
                                    </ul>
                                <?php endif; ?>

                                <div class="cs-timeline">
                                    <div class="cs-timeline-item">
                                        <div class="cs-timeline-icon cs-icon-gradient-1">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="cs-timeline-content">
                                            <h6 class="cs-timeline-title">Curriculums</h6>
                                            <span class="cs-timeline-value"><?php echo $tier === 'basics' ? '1' : ($tier === 'plus' ? '1' : 'Unlimited'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="cs-timeline-item">
                                        <div class="cs-timeline-icon cs-icon-gradient-2">
                                            <i class="fas fa-laptop"></i>
                                        </div>
                                        <div class="cs-timeline-content">
                                            <h6 class="cs-timeline-title">Courses</h6>
                                            <span class="cs-timeline-value"><?php echo $tier === 'basics' ? '1' : 'Unlimited'; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="cs-timeline-item">
                                        <div class="cs-timeline-icon cs-icon-gradient-3">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="cs-timeline-content">
                                            <h6 class="cs-timeline-title">Collaborators</h6>
                                            <span class="cs-timeline-value"><?php echo $tier === 'basics' ? '1' : ($tier === 'plus' ? '3' : 'Unlimited'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="cs-timeline-item">
                                        <div class="cs-timeline-icon cs-icon-gradient-4">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <div class="cs-timeline-content">
                                            <h6 class="cs-timeline-title">Modules & Lessons</h6>
                                            <span class="cs-timeline-value">Unlimited</span>
                                        </div>
                                    </div>
                                    
                                    <div class="cs-timeline-item">
                                        <div class="cs-timeline-icon cs-icon-gradient-5">
                                            <i class="fas fa-file-powerpoint"></i>
                                        </div>
                                        <div class="cs-timeline-content">
                                            <h6 class="cs-timeline-title">Slide Deck Generation</h6>
                                            <span class="cs-timeline-value cs-pro-badge"><?php echo $tier === 'pro' ? 'Available' : 'Pro Only'; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="cs-timeline-item">
                                        <div class="cs-timeline-icon cs-icon-gradient-6">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="cs-timeline-content">
                                            <h6 class="cs-timeline-title">Course Document Editing</h6>
                                            <span class="cs-timeline-value cs-pro-badge"><?php echo $tier === 'pro' ? 'Available' : 'Pro Only'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                       

                        
                    </div>
                </div>

                <!-- Studio Settings for Studio Admin -->
                <?php if (in_array('studio_admin', $user_roles)) : ?>
                    <div class="row mt-4" id="tour-studio-settings">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header pb-0 p-3">
                                    <h6 class="mb-0">Studio Settings</h6>
                                </div>
                                <div class="card-body p-3">
                                    <form method="post" class="courscribe-studio-edit-form" enctype="multipart/form-data">
                                        <?php wp_nonce_field('courscribe_edit_studio', 'courscribe_edit_studio_nonce'); ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="courscribe_studio_title">Title:</label>
                                                    <input type="text" name="courscribe_studio_title" id="courscribe_studio_title" class="form-control" value="<?php echo esc_attr(get_post_field('post_title', $post_id)); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="courscribe_studio_email">Contact Email:</label>
                                                    <input type="email" name="courscribe_studio_email" id="courscribe_studio_email" class="form-control" value="<?php echo esc_attr($email); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="courscribe_studio_description">Description:</label>
                                                    <textarea name="courscribe_studio_description" id="courscribe_studio_description" class="form-control" required><?php echo esc_textarea(get_post_field('post_content', $post_id)); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="courscribe_studio_website">Website URL:</label>
                                                    <input type="url" name="courscribe_studio_website" id="courscribe_studio_website" class="form-control" value="<?php echo esc_attr($website); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="courscribe_studio_address">Address:</label>
                                                    <textarea name="courscribe_studio_address" id="courscribe_studio_address" class="form-control"><?php echo esc_textarea($address); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input type="checkbox" name="courscribe_studio_is_public" id="courscribe_studio_is_public" class="form-check-input" value="1" <?php checked($is_public, 'Yes'); ?>>
                                                    <label class="form-check-label" for="courscribe_studio_is_public">Make Studio Public</label>
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
                                    // Studio Settings form submission
                                    if (isset($_POST['courscribe_submit_edit_studio']) && wp_verify_nonce($_POST['courscribe_edit_studio_nonce'], 'courscribe_edit_studio')) {
                                        if (current_user_can('edit_crscribe_studio', $post_id)) {
                                            $title = sanitize_text_field($_POST['courscribe_studio_title']);
                                            $description = wp_kses_post($_POST['courscribe_studio_description']);
                                            $email = sanitize_email($_POST['courscribe_studio_email']);
                                            $website = esc_url_raw($_POST['courscribe_studio_website']);
                                            $address = sanitize_textarea_field($_POST['courscribe_studio_address']);
                                            $is_public = isset($_POST['courscribe_studio_is_public']) ? 'Yes' : 'No';

                                            $update_result = wp_update_post([
                                                'ID'           => $post_id,
                                                'post_title'   => $title,
                                                'post_content' => $description,
                                                'post_type'    => 'crscribe_studio',
                                            ], true);

                                            if (is_wp_error($update_result)) {
                                                echo '<p class="courscribe-error">Error updating studio: ' . esc_html($update_result->get_error_message()) . '</p>';
                                                error_log('Courscribe: Failed to update studio ' . $post_id . ': ' . $update_result->get_error_message());
                                            } else {
                                                update_post_meta($post_id, '_studio_email', $email);
                                                update_post_meta($post_id, '_studio_website', $website);
                                                update_post_meta($post_id, '_studio_address', $address);
                                                update_post_meta($post_id, '_studio_is_public', $is_public);

                                                // Clear cache to reflect updates
                                                wp_cache_delete($cache_key);
                                                wp_cache_flush();

                                                echo '<p>Studio updated successfully!</p>';
                                                error_log('Courscribe: Studio ' . $post_id . ' updated successfully by user ' . $current_user->ID);
                                                echo '<meta http-equiv="refresh" content="0">';
                                            }
                                        } else {
                                            echo '<p class="courscribe-error">You do not have permission to edit this studio.</p>';
                                            error_log('Courscribe: User ' . $current_user->ID . ' attempted to edit studio ' . $post_id . ' without permission');
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription History for Studio Admin -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header pb-0 p-3">
                                    <h6 class="mb-0">Subscription History</h6>
                                </div>
                                <div class="card-body p-3">
                                    <?php
                                    $all_subscriptions = [];
                                    $subscription_orders = wc_get_orders([
                                        'limit'  => -1,
                                        'status' => ['completed', 'processing', 'on-hold', 'wc-wps_renewal', 'cancelled', 'expired'],
                                        'type'   => 'shop_order',
                                        'customer_id' => $current_user->ID,
                                    ]);

                                    foreach ($subscription_orders as $order) {
                                        foreach ($order->get_items() as $item) {
                                            $product = $item->get_product();
                                            if ($product && get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes') {
                                                $all_subscriptions[] = [
                                                    'product_name' => $product->get_name(),
                                                    'status' => $order->get_meta('wps_subscription_status') ?: $order->get_status(),
                                                    'price' => $item->get_total(),
                                                    'start_date' => $order->get_meta('wps_schedule_start') ?: $order->get_date_created()->date('Y-m-d'),
                                                    'next_payment' => $order->get_meta('wps_next_payment_date') ?: 'N/A',
                                                    'end_date' => $order->get_meta('wps_susbcription_end') && $order->get_meta('wps_susbcription_end') != '0' ? $order->get_meta('wps_susbcription_end') : 'Indefinite',
                                                ];
                                            }
                                        }
                                    }

                                    if (empty($all_subscriptions)) : ?>
                                        <p>No subscriptions found.</p>
                                    <?php else : ?>
                                        <div class="table-responsive p-0">
                                            <table class="table align-items-center mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Plan</th>
                                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Price</th>
                                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Start Date</th>
                                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Next Renewal</th>
                                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">End Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($all_subscriptions as $sub) : ?>
                                                        <tr>
                                                            <td class="text-white"><?php echo esc_html($sub['product_name']); ?></td>
                                                            <td><span class="badge badge-sm bg-gradient-<?php echo $sub['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo esc_html(ucfirst($sub['status'])); ?></span></td>
                                                            <td class="text-white"><?php echo wc_price($sub['price']); ?></td>
                                                            <td class="text-white"><?php echo esc_html($sub['start_date']); ?></td>
                                                            <td class="text-white"><?php echo esc_html($sub['next_payment']); ?></td>
                                                            <td class="text-white"><?php echo esc_html($sub['end_date']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php
                // Change Log for Studio Admins
                if (current_user_can('publish_crscribe_studios'))  :
                    global $wpdb;
                    $logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}courscribe_curriculum_log ORDER BY TIMESTAMP DESC LIMIT 50" );

                    // Calculate stats
                    $total_changes = count($logs);
                    $fields_modified = 0;
                    $last_activity = $logs ? $logs[0]->timestamp : 'N/A';
                    foreach ($logs as $log) {
                        $changes = json_decode($log->changes, true);
                        if (is_array($changes)) {
                            $fields_modified += count($changes);
                        }
                    }
                    ?>
                    <div class="cs-changelog-container">
                        <div class="cs-changelog-header">
                            <i class="fas fa-history cs-changelog-icon"></i>
                            <h3 class="cs-changelog-title">Recent Changes</h3>
                        </div>

                        <div class="cs-changelog-stats">
                            <div class="cs-stat-item">
                                <span class="cs-stat-number"><?php echo esc_html($total_changes); ?></span>
                                <span class="cs-stat-label">Recent Changes</span>
                            </div>
                            <div class="cs-stat-item">
                                <span class="cs-stat-number"><?php echo esc_html($fields_modified); ?></span>
                                <span class="cs-stat-label">Fields Modified</span>
                            </div>
                            <div class="cs-stat-item">
                                <span class="cs-stat-number"><?php echo esc_html($last_activity === 'N/A' ? 'N/A' : date('M j, Y', strtotime($last_activity))); ?></span>
                                <span class="cs-stat-label">Last Activity</span>
                            </div>
                        </div>

                        <?php if ($logs) : ?>
                        <div class="cs-changelog-list">
                            <?php foreach ($logs as $log) : 
                                $curriculum = get_post($log->curriculum_id);
                                $curriculum_title = $curriculum ? esc_html($curriculum->post_title) : 'Deleted';
                                $user = get_userdata($log->user_id);
                                $user_name = $user ? esc_html($user->display_name) : 'Unknown';
                                $user_initial = $user_name ? strtoupper(substr($user_name, 0, 1)) : 'U';
                                $action = esc_html(ucfirst($log->action));
                                $timestamp = date('M j, Y \a\t g:i A', strtotime($log->timestamp));
                                $changes = json_decode($log->changes, true);
                                ?>
                                <div class="cs-log-item">
                                    <div class="cs-log-header">
                                        <div class="cs-log-avatar"><?php echo $user_initial; ?></div>
                                        <div class="cs-log-main">
                                            <div class="cs-log-title"><?php echo $curriculum_title; ?></div>
                                            <div class="cs-log-meta">
                                                <span class="cs-log-action"><?php echo $action; ?></span>
                                                <span>by <?php echo $user_name; ?></span>
                                                <span class="cs-log-timestamp"><?php echo $timestamp; ?></span>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-down cs-expand-icon"></i>
                                    </div>
                                    <div class="cs-log-details">
                                        <div class="cs-changes-grid">
                                            <?php
                                            if (is_array($changes) && !empty($changes)) {
                                                foreach ($changes as $field => $change) {
                                                    $old = isset($change['old']) ? esc_html($change['old']) : '-';
                                                    $new = isset($change['new']) ? esc_html($change['new']) : '-';
                                                    $field_name = esc_html(str_replace('_', ' ', ucwords($field)));
                                                    ?>
                                                    <div class="cs-change-item">
                                                        <div class="cs-change-field"><?php echo $field_name; ?></div>
                                                        <div class="cs-change-comparison">
                                                            <div class="cs-change-old">
                                                                <div class="cs-change-label">Previous</div>
                                                                <div class="cs-change-content"><?php echo $old; ?></div>
                                                            </div>
                                                            <div class="cs-change-new">
                                                                <div class="cs-change-label">Updated</div>
                                                                <div class="cs-change-content"><?php echo $new; ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                ?>
                                                <div class="cs-no-changes">No detailed changes available</div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else : ?>
                            <p>No recent changes.</p>
                        <?php endif; ?>
                    </div>

                    <script>
                        class ChangelogManager {
                            constructor() {
                                this.initializeExpandables();
                            }

                            initializeExpandables() {
                                const logItems = document.querySelectorAll('.cs-log-item');
                                logItems.forEach(item => {
                                    const header = item.querySelector('.cs-log-header');
                                    header.addEventListener('click', () => this.toggleLogItem(item));
                                });
                            }

                            toggleLogItem(item) {
                                const isExpanded = item.classList.contains('expanded');
                                
                                // Close all other expanded items
                                document.querySelectorAll('.cs-log-item.expanded').forEach(expandedItem => {
                                    if (expandedItem !== item) {
                                        expandedItem.classList.remove('expanded');
                                    }
                                });

                                // Toggle current item
                                item.classList.toggle('expanded', !isExpanded);
                            }
                        }

                        // Initialize the changelog manager
                        document.addEventListener('DOMContentLoaded', () => {
                            new ChangelogManager();
                        });
                    </script>
                <?php endif; ?>

               

                <!-- Collaborator Permissions Display -->
                <?php if (in_array('collaborator', $user_roles)) : ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header pb-0 p-3">
                                    <h6 class="mb-0">Your Permissions</h6>
                                </div>
                                <div class="card-body p-3">
                                    <?php
                                    $current_permissions = get_user_meta($current_user->ID, '_courscribe_collaborator_permissions', true) ?: [];
                                    $available_permissions = [
                                        'edit_crscribe_curriculums' => 'Edit Curriculums',
                                        'publish_crscribe_curriculums' => 'Publish Curriculums',
                                        'edit_crscribe_courses' => 'Edit Courses',
                                        'publish_crscribe_courses' => 'Publish Courses',
                                        'edit_dtlms_modules' => 'Edit Modules',
                                        'publish_dtlms_modules' => 'Publish Modules',
                                        'edit_dtlms_lessons' => 'Edit Lessons',
                                        'publish_dtlms_lessons' => 'Publish Lessons',
                                        'generate_slide_deck' => 'Generate Slide Deck',
                                        'edit_course_document' => 'Edit Course Document',
                                    ];
                                    if (empty($current_permissions)) {
                                        echo '<p>No permissions assigned.</p>';
                                    } else {
                                        echo '<ul class="list-group">';
                                        foreach ($available_permissions as $perm_key => $perm_label) {
                                            if (in_array($perm_key, $current_permissions)) {
                                                echo '<li class="list-group-item border-0 px-0">' . esc_html($perm_label) . '</li>';
                                            }
                                        }
                                        echo '</ul>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Permissions Modal -->
                <?php if (in_array('studio_admin', $user_roles)) : ?>
                    <div id="courscribe-permissions-modal" class="courscribe-modal" style="display: none;">
                        <div class="courscribe-modal-content">
                            <span class="courscribe-modal-close" onclick="closePermissionsModal()">×</span>
                            <h3>Edit Collaborator Permissions</h3>
                            <form method="post" class="courscribe-user-management-form">
                                <?php wp_nonce_field('courscribe_update_user', 'courscribe_user_nonce'); ?>
                                <input type="hidden" name="user_id" id="modal_user_id">
                                <div class="form-group">
                                    <label for="modal_user_email">Email:</label>
                                    <input type="email" name="user_email" id="modal_user_email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="modal_user_username">Username:</label>
                                    <input type="text" name="user_username" id="modal_user_username" class="form-control" required>
                                </div>
                                <h6>Permissions:</h6>
                                <?php
                                $available_permissions = [
                                    'edit_crscribe_curriculums' => 'Edit Curriculums',
                                    'publish_crscribe_curriculums' => 'Publish Curriculums',
                                    'edit_crscribe_courses' => 'Edit Courses',
                                    'publish_crscribe_courses' => 'Publish Courses',
                                    'edit_dtlms_modules' => 'Edit Modules',
                                    'publish_dtlms_modules' => 'Publish Modules',
                                    'edit_dtlms_lessons' => 'Edit Lessons',
                                    'publish_dtlms_lessons' => 'Publish Lessons',
                                    'generate_slide_deck' => 'Generate Slide Deck',
                                    'edit_course_document' => 'Edit Course Document',
                                ];
                                foreach ($available_permissions as $perm_key => $perm_label) :
                                    ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="collaborator_permissions[]" id="perm_<?php echo esc_attr($perm_key); ?>" class="form-check-input permission-checkbox" value="<?php echo esc_attr($perm_key); ?>" data-perm="<?php echo esc_attr($perm_key); ?>">
                                        <label class="form-check-label" for="perm_<?php echo esc_attr($perm_key); ?>"><?php echo esc_html($perm_label); ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="form-group mt-3">
                                    <input type="submit" name="courscribe_update_user" value="Update" class="btn btn-primary">
                                    <button type="button" class="btn btn-danger" onclick="deleteUser()">Delete User</button>
                                </div>
                            </form>
                            <form method="post" id="delete-user-form" style="display: none;">
                                <?php wp_nonce_field('courscribe_delete_user', 'courscribe_delete_nonce'); ?>
                                <input type="hidden" name="user_id" id="delete_user_id">
                                <input type="submit" name="courscribe_delete_user" value="Delete">
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
              
                
            </div>
          
        </div>
         <!-- Add Collaborator Modal -->
        <div class="modal-overlay" id="collaboratorModal">
            <div class="modal">
                <div class="modal-header">
                    <h2 class="modal-title" id="modalTitle">Add New Collaborator</h2>
                    <button class="modal-close" id="modalClose">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="collaboratorForm">
                        <input type="hidden" id="collaboratorId" name="id">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Editor">Editor</option>
                                <option value="Author">Author</option>
                                <option value="Reviewer">Reviewer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Permissions</label>
                            <div class="permissions-grid">
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-create" class="permission-checkbox" name="permissions[]" value="create">
                                    <label for="perm-create" class="permission-label">Create Content</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-edit" class="permission-checkbox" name="permissions[]" value="edit">
                                    <label for="perm-edit" class="permission-label">Edit Content</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-delete" class="permission-checkbox" name="permissions[]" value="delete">
                                    <label for="perm-delete" class="permission-label">Delete Content</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-publish" class="permission-checkbox" name="permissions[]" value="publish">
                                    <label for="perm-publish" class="permission-label">Publish Content</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-review" class="permission-checkbox" name="permissions[]" value="review">
                                    <label for="perm-review" class="permission-label">Review Content</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-invite" class="permission-checkbox" name="permissions[]" value="invite">
                                    <label for="perm-invite" class="permission-label">Invite Users</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-manage" class="permission-checkbox" name="permissions[]" value="manage">
                                    <label for="perm-manage" class="permission-label">Manage Users</label>
                                </div>
                                <div class="permission-item">
                                    <input type="checkbox" id="perm-settings" class="permission-checkbox" name="permissions[]" value="settings">
                                    <label for="perm-settings" class="permission-label">Change Settings</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Collaborator</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="modal-overlay" id="confirmModal">
            <div class="modal" style="max-width: 400px;">
                <div class="modal-header">
                    <h2 class="modal-title">Confirm Action</h2>
                    <button class="modal-close" id="confirmModalClose">&times;</button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Are you sure you want to delete this collaborator?</p>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="confirmCancel">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmAction">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div class="toast-container" id="toastContainer"></div>

        <script>
            function openPermissionsModal(userId, email, username) {
                const modal = document.getElementById('courscribe-permissions-modal');
                modal.style.display = 'flex';
                document.getElementById('modal_user_id').value = userId;
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('modal_user_email').value = email;
                document.getElementById('modal_user_username').value = username;

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo esc_url(admin_url('')); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const permissions = JSON.parse(xhr.responseText());
                        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                            checkbox.checked = permissions.includes(checkbox.getAttribute('data-perm'));
                        });
                    }
                };
                xhr.send('action=courscribe_get_user_permissions&user_id=' + userId + '&nonce=' + '<?php echo wp_create_nonce('courscribe_get_permissions'); ?>');
            }

            function closePermissionsModal() {
                document.getElementById('courscribe-permissions-modal').style.display = 'none';
            }

            function deleteUser() {
                if (confirm('Are you sure you want to delete this user?')) {
                    document.getElementById('delete-user-form').submit();
                }
            }

            window.onclick = function(event) {
                const modal = document.getElementById('courscribe-permissions-modal');
                if (event.target === modal) {
                    closePermissionsModal();
                }
            }

            // Guided Tour Logic
            document.addEventListener('DOMContentLoaded', function() {
                // const runTour = <?php echo $run_tour ? 'true' : 'false'; ?>;
                const runTour = true;
                if (runTour) {
                    // Wait for TourGuide.js to load
                    const tourScript = document.querySelector('script[src="https://unpkg.com/@sjmc11/tourguidejs@0.0.22/dist/tour.js"]');
                    if (tourScript) {
                        tourScript.addEventListener('load', initializeTour);
                        tourScript.addEventListener('error', function() {
                            console.error('Courscribe: Failed to load TourGuide.js');
                            error_log('Courscribe: Failed to load TourGuide.js from CDN');
                            Swal.fire({
                                title: 'Error',
                                text: 'Unable to load the guided tour. Please try again later.',
                                icon: 'error',
                                confirmButtonColor: '#E4B26F'
                            });
                        });
                    } else {
                        console.error('Courscribe: TourGuide.js script not found');
                        error_log('Courscribe: TourGuide.js script not found in DOM');
                        Swal.fire({
                            title: 'Error',
                            text: 'Unable to load the guided tour. Please try again later.',
                            icon: 'error',
                            confirmButtonColor: '#E4B26F'
                        });
                    }
                }

                function initializeTour() {
                    if (typeof window.TourGuideClient !== 'undefined') {
                        const tour = new window.TourGuideClient({
                            steps: [
                                {
                                    title: 'Welcome to Your Studio',
                                    content: 'This is your CourScribe Studio, the central hub for creating courses.',
                                    target: '#tour-studio-header',
                                    order: 0,
                                    group: 'studio-tour'
                                },
                                {
                                    title: 'Track Your Progress',
                                    content: 'Monitor your stats for curriculums, courses, modules, and lessons here.',
                                    target: '#tour-stats-row',
                                    order: 1,
                                    group: 'studio-tour'
                                },
                                {
                                    title: 'Studio Overview',
                                    content: 'View your contact details and public settings in this section.',
                                    target: '#tour-studio-overview',
                                    order: 2,
                                    group: 'studio-tour'
                                },
                                {
                                    title: 'Your Plan',
                                    content: 'Check your Studio Tier for plan limits and features, like slide deck generation.',
                                    target: '#tour-studio-tier',
                                    order: 3,
                                    group: 'studio-tour'
                                },
                                {
                                    title: 'Manage Settings',
                                    content: 'Update your studio’s details, like title and description, here.',
                                    target: '#tour-studio-settings',
                                    order: 4,
                                    group: 'studio-tour'
                                },
                                {
                                    title: 'Collaborate',
                                    content: 'Invite others to work on your courses in the Collaborators section.',
                                    target: '#tour-collaborators',
                                    order: 5,
                                    group: 'studio-tour'
                                },
                                {
                                    title: 'Ready to Build?',
                                    content: 'Great job! Want to start creating your first curriculum? Let’s go to the Curriculums page!',
                                    target: '',
                                    order: 6,
                                    group: 'studio-tour',
                                    beforeLeave: function() {
                                        Swal.fire({
                                            title: 'Explore Curriculums?',
                                            text: 'Would you like to visit the Curriculums page to learn how to create courses with CourScribe?',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonColor: '#E4B26F',
                                            cancelButtonColor: '#d33',
                                            confirmButtonText: 'Yes, take me there!',
                                            cancelButtonText: 'Not now'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                window.location.href = '<?php echo esc_url(home_url('/curriculums')); ?>';
                                            }
                                        });
                                        // Mark tour as completed via AJAX
                                        const xhr = new XMLHttpRequest();
                                        xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
                                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                                        xhr.send('action=courscribe_complete_tour&nonce=' + '<?php echo wp_create_nonce('courscribe_complete_tour'); ?>');
                                    }
                                }
                            ],
                            dialogWidth: 300,
                            completeButtonText: 'Finish Tour',
                            nextTheme: { background: '#F15538', color: '#FFFFFF' },
                            prevTheme: { background: '#555', color: '#FFFFFF' },
                            closeTheme: { background: '#d33', color: '#FFFFFF' }
                        });
                        tour.start().catch(function(error) {
                            console.error('Courscribe: TourGuide.js failed to start:', error);
                            error_log('Courscribe: TourGuide.js failed to start: ' + error.message);
                            Swal.fire({
                                title: 'Error',
                                text: 'The guided tour could not start. Please try again later.',
                                icon: 'error',
                                confirmButtonColor: '#E4B26F'
                            });
                        });
                    } else {
                        console.error('Courscribe: TourGuideClient is not defined');
                        error_log('Courscribe: TourGuideClient is not defined after script load');
                        Swal.fire({
                            title: 'Error',
                            text: 'Unable to load the guided tour. Please try again later.',
                            icon: 'error',
                            confirmButtonColor: '#E4B26F'
                        });
                    }
                }
            });

            // PHP error logging function (client-side approximation)
            function error_log(message) {
                console.error('message', message);
                // Note: This doesn’t log to WordPress error log; server-side logging is handled in PHP
            }
                </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // DOM Elements
                
                const collaboratorModal = document.getElementById('collaboratorModal');
                const confirmModal = document.getElementById('confirmModal');
                const addCollaboratorBtn = document.getElementById('addCollaboratorBtn');
                const modalClose = document.getElementById('modalClose');
                const confirmModalClose = document.getElementById('confirmModalClose');
                const cancelBtn = document.getElementById('cancelBtn');
                const confirmCancel = document.getElementById('confirmCancel');
                const collaboratorForm = document.getElementById('collaboratorForm');
                const modalTitle = document.getElementById('modalTitle');
                const toastContainer = document.getElementById('toastContainer');
                
                // Mock data for collaborators
                const collaborators = [
                    {
                        id: 1,
                        name: "Sarah Johnson",
                        email: "sarah.j@example.com",
                        role: "Editor",
                        status: "Active",
                        lastActive: "2 hours ago",
                        permissions: ["create", "edit", "review"]
                    },
                    {
                        id: 2,
                        name: "Michael Roberts",
                        email: "michael.r@example.com",
                        role: "Reviewer",
                        status: "Active",
                        lastActive: "1 day ago",
                        permissions: ["review"]
                    },
                    {
                        id: 3,
                        name: "Alex Davis",
                        email: "alex.d@example.com",
                        role: "Author",
                        status: "Active",
                        lastActive: "3 days ago",
                        permissions: ["create", "edit"]
                    },
                    {
                        id: 4,
                        name: "Emily Thompson",
                        email: "emily.t@example.com",
                        role: "Admin",
                        status: "Active",
                        lastActive: "5 hours ago",
                        permissions: ["create", "edit", "delete", "publish", "review", "invite", "manage", "settings"]
                    },
                    {
                        id: 5,
                        name: "James Wilson",
                        email: "james.w@example.com",
                        role: "Author",
                        status: "Pending",
                        lastActive: "Invited 2 days ago",
                        permissions: ["create", "edit"]
                    }
                ];

                

                // Open add collaborator modal
                addCollaboratorBtn.addEventListener('click', function() {
                    modalTitle.textContent = "Add New Collaborator";
                    collaboratorForm.reset();
                    document.getElementById('collaboratorId').value = '';
                    // Uncheck all permission checkboxes
                    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    openModal(collaboratorModal);
                });

                // Edit collaborator - event delegation for dynamically loaded elements
                document.addEventListener('click', function(e) {
                    if (e.target.closest('.edit-collaborator')) {
                        const collaboratorId = parseInt(e.target.closest('.edit-collaborator').getAttribute('data-id'));
                        const collaborator = collaborators.find(c => c.id === collaboratorId);
                        
                        if (collaborator) {
                            modalTitle.textContent = "Edit Collaborator";
                            document.getElementById('collaboratorId').value = collaborator.id;
                            document.getElementById('name').value = collaborator.name;
                            document.getElementById('email').value = collaborator.email;
                            document.getElementById('role').value = collaborator.role;
                            
                            // Uncheck all permission checkboxes first
                            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            
                            // Check the permissions this collaborator has
                            collaborator.permissions.forEach(perm => {
                                const checkbox = document.querySelector(`.permission-checkbox[value="${perm}"]`);
                                if (checkbox) checkbox.checked = true;
                            });
                            
                            openModal(collaboratorModal);
                        }
                    }
                    
                    // Delete collaborator
                    if (e.target.closest('.delete-collaborator')) {
                        const collaboratorId = parseInt(e.target.closest('.delete-collaborator').getAttribute('data-id'));
                        const collaborator = collaborators.find(c => c.id === collaboratorId);
                        
                        if (collaborator) {
                            document.getElementById('confirmMessage').textContent = 
                                `Are you sure you want to delete ${collaborator.name}? This action cannot be undone.`;
                            
                            // Store the collaborator ID in the confirm button
                            document.getElementById('confirmAction').setAttribute('data-id', collaboratorId);
                            
                            openModal(confirmModal);
                        }
                    }
                });

                // Close modals
                modalClose.addEventListener('click', function() {
                    closeModal(collaboratorModal);
                });

                confirmModalClose.addEventListener('click', function() {
                    closeModal(confirmModal);
                });

                cancelBtn.addEventListener('click', function() {
                    closeModal(collaboratorModal);
                });

                confirmCancel.addEventListener('click', function() {
                    closeModal(confirmModal);
                });

                // Confirm delete action
                document.getElementById('confirmAction').addEventListener('click', function() {
                    const collaboratorId = parseInt(this.getAttribute('data-id'));
                    console.log(`Deleting collaborator with ID: ${collaboratorId}`);
                    showToast('Collaborator deleted successfully', 'success');
                    closeModal(confirmModal);
                    // In a real app, you would remove the collaborator from the table here
                });

                // Submit collaborator form
                collaboratorForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const collaboratorData = {};
                    formData.forEach((value, key) => {
                        if (key === 'permissions[]') {
                            if (!collaboratorData.permissions) collaboratorData.permissions = [];
                            collaboratorData.permissions.push(value);
                        } else {
                            collaboratorData[key] = value;
                        }
                    });
                    
                    console.log('Form submitted:', collaboratorData);
                    
                    if (collaboratorData.id) {
                        showToast('Collaborator updated successfully', 'success');
                    } else {
                        showToast('Collaborator added successfully', 'success');
                    }
                    
                    closeModal(collaboratorModal);
                });

                // Helper functions
                function openModal(modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal(modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }

                function showToast(message, type) {
                    const toast = document.createElement('div');
                    toast.className = `toast toast-${type} show`;
                    
                    let icon;
                    switch(type) {
                        case 'success':
                            icon = '<i class="fas fa-check-circle toast-icon"></i>';
                            break;
                        case 'error':
                            icon = '<i class="fas fa-exclamation-circle toast-icon"></i>';
                            break;
                        case 'warning':
                            icon = '<i class="fas fa-exclamation-triangle toast-icon"></i>';
                            break;
                        default:
                            icon = '<i class="fas fa-info-circle toast-icon"></i>';
                    }
                    
                    toast.innerHTML = `
                        ${icon}
                        <span>${message}</span>
                        <button class="toast-close">&times;</button>
                    `;
                    
                    toastContainer.appendChild(toast);
                    
                    // Close button
                    toast.querySelector('.toast-close').addEventListener('click', function() {
                        toast.remove();
                    });
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        toast.remove();
                    }, 5000);
                }

                // Close modal when clicking outside
                window.addEventListener('click', function(e) {
                    if (e.target === collaboratorModal) {
                        closeModal(collaboratorModal);
                    }
                    if (e.target === confirmModal) {
                        closeModal(confirmModal);
                    }
                });
            });
        </script>
        <?php
    } else {
        return ob_get_clean() . courscribe_retro_error("No studio found.");
    }
    wp_reset_postdata();
    return ob_get_clean();
}


add_action('template_redirect', function() {
    // Option 1: From query var
    $redirect_url = get_query_var('courscribe_redirect_url');

    // Option 2: From global (if not using query vars)
    // if (!$redirect_url && isset($GLOBALS['courscribe_redirect_url'])) {
    //     $redirect_url = $GLOBALS['courscribe_redirect_url'];
    // }

    if ($redirect_url) {
        wp_safe_redirect($redirect_url);
        exit;
    }
});
?>