<?php
// courscribe/courscribe.php
if (!defined('ABSPATH')) {
    exit;
}

//// Include files
require_once plugin_dir_path(__FILE__) . '../templates/dashboard/courscribe-dashboard.php';
require_once plugin_dir_path(__FILE__) . '../templates/dashboard/courscribe-curriculums-dashboard.php';
require_once plugin_dir_path(__FILE__) . '../templates/dashboard/courscribe-courses-dashboard.php';
require_once plugin_dir_path(__FILE__) . '../templates/dashboard/courscribe-modules-dashboard.php';
require_once plugin_dir_path(__FILE__) . '../templates/dashboard/courscribe-lessons-dashboard.php';
require_once plugin_dir_path(__FILE__) . '../templates/courscribe-studio-page.php';
require_once plugin_dir_path(__FILE__) . '../templates/courscribe-setting.php';
require_once plugin_dir_path(__FILE__) . '../templates/dashboard/courscribe-studios-dashboard.php';

// Register post types
// function courscribe_register_post_types() {
//     $post_types = [
//         'crscribe_studio' => [
//             'labels' => [
//                 'name' => 'Studios',
//                 'singular_name' => 'Studio',
//                 'add_new' => 'Add New Studio',
//                 'add_new_item' => 'Add New Studio',
//                 'edit_item' => 'Edit Studio',
//                 'new_item' => 'New Studio',
//                 'view_item' => 'View Studio',
//                 'search_items' => 'Search Studios',
//                 'not_found' => 'No studios found',
//                 'not_found_in_trash' => 'No studios found in Trash',
//             ],
//             'public' => true,
//             'has_archive' => true,
//             'supports' => ['title', 'editor', 'thumbnail'],
//             'show_in_menu' => 'courscribe',
//             'capability_type' => 'crscribe_studio',
//             'map_meta_cap' => true,
//             'rewrite' => ['slug' => 'courscribe-studio'],
//             'show_in_rest' => true,
//         ],
//         'crscribe_curriculum' => [
//             'labels' => [
//                 'name' => 'Curriculums',
//                 'singular_name' => 'Curriculum',
//                 'add_new' => 'Add New Curriculum',
//                 'add_new_item' => 'Add New Curriculum',
//                 'edit_item' => 'Edit Curriculum',
//                 'new_item' => 'New Curriculum',
//                 'view_item' => 'View Curriculum',
//                 'search_items' => 'Search Curriculums',
//                 'not_found' => 'No curriculums found',
//                 'not_found_in_trash' => 'No curriculums found in Trash',
//             ],
//             'public' => true,
//             'has_archive' => true,
//             'supports' => ['title', 'thumbnail'],
//             'show_in_menu' => 'courscribe',
//             'capability_type' => 'crscribe_curriculum',
//             'map_meta_cap' => true,
//             // 'rewrite' => ['slug' => 'courscribe-curriculum', 'with_front' => false],
//             'rewrite' => array('slug' => 'courscribe-curriculum'), 
//             'show_in_rest' => true,
//             'publicly_queryable' => true,
//         ],
//         'crscribe_course' => [
//             'labels' => [
//                 'name' => 'Courses',
//                 'singular_name' => 'Course',
//                 'add_new' => 'Add New Course',
//                 'add_new_item' => 'Add New Course',
//                 'edit_item' => 'Edit Course',
//                 'new_item' => 'New Course',
//                 'view_item' => 'View Course',
//                 'search_items' => 'Search Courses',
//                 'not_found' => 'No courses found',
//                 'not_found_in_trash' => 'No courses found in Trash',
//             ],
//             'public' => true,
//             'has_archive' => true,
//             'supports' => ['title', 'editor', 'thumbnail'],
//             'show_in_menu' => 'courscribe',
//             'capability_type' => 'crscribe_course',
//             'map_meta_cap' => true,
//             'rewrite' => ['slug' => 'courscribe-course'],
//             'show_in_rest' => true,
//         ],
//         'crscribe_module' => [
//             'labels' => [
//                 'name' => 'Modules',
//                 'singular_name' => 'Module',
//                 'add_new' => 'Add New Module',
//                 'add_new_item' => 'Add New Module',
//                 'edit_item' => 'Edit Module',
//                 'new_item' => 'New Module',
//                 'view_item' => 'View Module',
//                 'search_items' => 'Search Modules',
//                 'not_found' => 'No modules found',
//                 'not_found_in_trash' => 'No modules found in Trash',
//             ],
//             'public' => true,
//             'has_archive' => true,
//             'supports' => ['title', 'editor', 'thumbnail'],
//             'show_in_menu' => 'courscribe',
//             'capability_type' => 'crscribe_module',
//             'map_meta_cap' => true,
//             'rewrite' => ['slug' => 'courscribe-module'],
//             'show_in_rest' => true,
//         ],
//         'crscribe_lesson' => [
//             'labels' => [
//                 'name' => 'Lessons',
//                 'singular_name' => 'Lesson',
//                 'add_new' => 'Add New Lesson',
//                 'add_new_item' => 'Add New Lesson',
//                 'edit_item' => 'Edit Lesson',
//                 'new_item' => 'New Lesson',
//                 'view_item' => 'View Lesson',
//                 'search_items' => 'Search Lessons',
//                 'not_found' => 'No lessons found',
//                 'not_found_in_trash' => 'No lessons found in Trash',
//             ],
//             'public' => true,
//             'has_archive' => true,
//             'supports' => ['title', 'editor', 'thumbnail'],
//             'show_in_menu' => 'courscribe',
//             'capability_type' => 'crscribe_lesson',
//             'map_meta_cap' => true,
//             'rewrite' => ['slug' => 'courscribe-lesson'],
//             'show_in_rest' => true,
//         ],
//     ];

//     foreach ($post_types as $post_type => $args) {
//         $result = register_post_type($post_type, $args);
//         if (is_wp_error($result)) {
//             //error_log("Courscribe: Failed to register $post_type: " . $result->get_error_message());
//         } else {
//             //error_log("Courscribe: Registered post type $post_type");
//         }
//     }
// }
add_action('init', 'courscribe_register_post_types');

// Flush rewrite rules on plugin activation
function courscribe_flush_rewrites() {
    // courscribe_register_post_types();
    flush_rewrite_rules();
}
register_activation_hook(plugin_dir_path(__FILE__) . '../courscribe.php', 'courscribe_flush_rewrites');

// Add admin menu
function courscribe_admin_menu() {
    add_menu_page(
        'Courscribe',
        'Courscribe',
        'manage_options',
        'courscribe',
        'courscribe_dashboard',
        'dashicons-book',
        20
    );
    add_submenu_page(
        'courscribe',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'courscribe',
        'courscribe_dashboard',
        0
    );
    add_submenu_page(
        'courscribe',
        'Expert Reviews Dashboard',
        'Expert Reviews',
        'manage_options', // Restricts to administrators
        'courscribe_expert_reviews',
        'courscribe_expert_reviews_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Subscriptions & Purchases',
        'Subscriptions & Purchases',
        'manage_options',
        'courscribe_subscriptions_purchases',
        'courscribe_subscriptions_purchases_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Curriculums Dashboard',
        'Curriculums',
        'manage_options',
        'courscribe_curriculums',
        'courscribe_curriculums_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Courses Dashboard',
        'Courses',
        'manage_options',
        'courscribe_courses',
        'courscribe_courses_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Modules Dashboard',
        'Modules',
        'manage_options',
        'courscribe_modules',
        'courscribe_modules_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Lessons Dashboard',
        'Lessons',
        'manage_options',
        'courscribe_lessons',
        'courscribe_lessons_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Studios Dashboard',
        'Studios',
        'manage_options',
        'courscribe_studios',
        'courscribe_render_studios_dashboard'
    );
    add_submenu_page(
        'courscribe',
        'Settings',
        'Settings',
        'manage_options',
        'courscribe_settings',
        'courscribe_render_settings_page'
    );
    add_submenu_page(
        'Courscribe Reviews',
        'Client Reviews',
        'edit_crscribe_curriculums',
        'courscribe-annotations',
        'courscribe_annotations_admin_page'
    );
    add_submenu_page(
        'courscribe',
        'Waitlist',
        'Waitlist',
        'manage_options',
        'courscribe_waitlist',
        'courscribe_waitlist_dashboard'
    );

}
add_action('admin_menu', 'courscribe_admin_menu');

// Enqueue scripts and styles
function courscribe_enqueue_scripts_dash($hook) {
    if (!in_array($hook, [
        'toplevel_page_courscribe',
        'courscribe_page_courscribe_curriculums',
        'courscribe_page_courscribe_courses',
        'courscribe_page_courscribe_modules',
        'courscribe_page_courscribe_lessons',
        'courscribe_page_courscribe_studios',
        'courscribe_page_courscribe_settings',
        'courscribe_page_courscribe_expert_reviews',
        'courscribe_page_courscribe_waitlist',
        'courscribe_page_courscribe_subscriptions_purchases'
    ])) {
        return;
    }

    $site_url = home_url();

    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', [], '5.3.2');

    wp_enqueue_style('courscribe-curriculum', "$site_url/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css", [], '1.0.0');
    wp_enqueue_style('courscribe-tabs', "$site_url/wp-content/plugins/courscribe/assets/css/tabs.css", [], '1.0.0');
    wp_enqueue_style('courscribe-dashboard', "$site_url/wp-content/plugins/courscribe/assets/css/courscribe-dashboard.css", [], '1.0.0');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
    wp_enqueue_style('open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700', [], null);
    wp_enqueue_style('nucleo-icons', "$site_url/wp-content/plugins/courscribe/assets/css/nucleo-icons.css", [], '1.0.0');
    wp_enqueue_style('nucleo-svg', "$site_url/wp-content/plugins/courscribe/assets/css/nucleo-svg.css", [], '1.0.0');
    wp_enqueue_style('soft-ui-dashboard', "$site_url/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css", [], '1.0.7');

    wp_enqueue_script('courscribe-popper', "$site_url/wp-content/plugins/courscribe/assets/js/core/popper.min.js", [], '1.0.0', true);
    wp_enqueue_script('courscribe-bootstrap', "$site_url/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js", ['courscribe-popper'], '1.0.0', true);
    wp_enqueue_script('courscribe-perfect-scrollbar', "$site_url/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js", [], '1.0.0', true);
    wp_enqueue_script('courscribe-smooth-scrollbar', "$site_url/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js", [], '1.0.0', true);
    wp_enqueue_script('courscribe-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
    wp_enqueue_script('courscribe-dashboard', "$site_url/wp-content/plugins/courscribe/assets/js/courscribe-dashboard.js", ['jquery', 'courscribe-bootstrap', 'courscribe-chartjs'], '1.0.0', true);
    wp_enqueue_script('courscribe-soft-ui', "$site_url/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js", [], '1.0.7', true);

    wp_localize_script('courscribe-dashboard', 'courscribeAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('courscribe_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'courscribe_enqueue_scripts_dash');

function courscribe_waitlist_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    $current_user = wp_get_current_user();
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_waitlist';
    $waitlist = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
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
            Welcome to the savvy side of course creation.<br>
            <span>Courscribe Waitlist.</span>
        </h3>
        <p class="courscribe-pricing-subheading">Welcome, <?php echo esc_html($current_user->display_name); ?>! Manage your educational content and track progress below.</p>

        <div class="card mb-4" style="margin-left:46px;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line me-2"></i>Waitlist</h2>
            </div>
            <div class="card-body" style="margin-left:26px;">
                <div class="row">
                    <?php if (empty($waitlist)) : ?>
                    <p>No emails in the waitlist yet.</p>
                    <?php else : ?>
                        <table class="waitlist-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Joined At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waitlist as $entry) : ?>
                                    <tr>
                                        <td style="padding-left:16px;"><?php echo esc_html($entry->email); ?></td>
                                        <td><?php echo esc_html($entry->created_at); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
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
    </div>
    <?php
}


function courscribe_subscriptions_purchases_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Verify WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_die('WooCommerce plugin is not active.');
    }

    global $wpdb;

    // Define site URL for logo
    $site_url = home_url();

    // Fetch orders containing subscription products
    $subscription_orders = wc_get_orders([
        'limit'  => -1,
        'status' => ['completed', 'processing', 'on-hold', 'wc-wps_renewal'],
        'type'   => 'shop_order',
    ]);

    $subscriptions = [];
    foreach ($subscription_orders as $order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $is_subscription = get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes';
                if ($is_subscription) {
                    $start_date_str = $order->get_meta('wps_schedule_start') ?: $order->get_date_created()->date('Y-m-d');
                    $start_date = new DateTime($start_date_str);

                    $interval_unit = get_post_meta($product->get_id(), 'wps_sfw_subscription_interval', true);
                    $interval_count = (int) get_post_meta($product->get_id(), 'wps_sfw_subscription_number', true);

                    // Calculate next payment and end date
                    $next_payment_meta = $order->get_meta('wps_next_payment_date');
                    $end_date_meta = $order->get_meta('wps_susbcription_end');

                    $interval_spec = 'P' . $interval_count . strtoupper(substr($interval_unit, 0, 1));
                    $next_payment_date = $next_payment_meta ? new DateTime($next_payment_meta) : (clone $start_date)->add(new DateInterval($interval_spec));
                    $end_date = $end_date_meta && $end_date_meta != '0' ? new DateTime($end_date_meta) : (clone $start_date)->add(new DateInterval($interval_spec));

                    // Get studio name
                    $user_id = $order->get_user_id();
                    $cache_key = 'courscribe_studio_' . $user_id;
                    $studio_id = wp_cache_get($cache_key);
                    if (false === $studio_id) {
                        $user = get_userdata($user_id);
                        $user_roles = $user ? $user->roles : [];
                        $query_args = [
                            'post_type'      => 'crscribe_studio',
                            'post_status'    => 'publish',
                            'posts_per_page' => 1,
                            'no_found_rows'  => true,
                            'fields'         => 'ids',
                            'cache_results'  => true,
                        ];

                        if (in_array('collaborator', $user_roles)) {
                            $studio_id = get_user_meta($user_id, '_courscribe_studio_id', true);
                            if ($studio_id) {
                                $query_args['p'] = $studio_id;
                            }
                        } else {
                            $query_args['author'] = $user_id;
                        }

                        $query = new WP_Query($query_args);
                        $studio_id = $query->have_posts() ? $query->posts[0] : 0;
                        wp_cache_set($cache_key, $studio_id, '', 300);
                    }

                    $studio_name = $studio_id ? get_the_title($studio_id) : 'N/A';

                    $subscriptions[] = [
                        'order'         => $order,
                        'item'          => $item,
                        'user_id'       => $user_id,
                        'status'        => $order->get_meta('wps_subscription_status') ?: 'active',
                        'start_date'    => $start_date->format('Y-m-d'),
                        'next_payment'  => $next_payment_date->format('Y-m-d'),
                        'end_date'      => $end_date ? $end_date->format('Y-m-d') : 'Indefinite',
                        'price'         => $item->get_total(),
                        'product_name'  => $product->get_name(),
                        'interval'      => $interval_unit,
                        'number'        => $interval_count,
                        'studio_name'   => $studio_name,
                    ];
                    break;
                }
            }
        }
    }

    // Calculate stats
    $total_subscriptions = count($subscriptions);
    $subscriptions_this_month = 0;
    $total_amount = 0;
    $total_amount_this_month = 0;
    $studio_ids = [];
    foreach ($subscriptions as $sub) {
        $total_amount += (float) $sub['price'];
        if (strpos($sub['start_date'], '2025-05') === 0) {
            $subscriptions_this_month++;
            $total_amount_this_month += (float) $sub['price'];
        }
        $studio_id = get_user_meta($sub['user_id'], '_courscribe_studio_id', true) ?: $sub['order']->get_meta('_courscribe_studio_id', true);
        if ($studio_id) {
            $studio_ids[$studio_id] = true;
        }
    }
    $total_studios = (int) wp_count_posts('crscribe_studio')->publish;
    $active_studios = count($studio_ids);

    // Prepare subscriptions chart data
    $chart_data = [];
    foreach ($subscriptions as $sub) {
        $month = substr($sub['start_date'], 0, 7);
        $chart_data[$month] = isset($chart_data[$month]) ? $chart_data[$month] + 1 : 1;
    }
    $chart_labels = json_encode(array_keys($chart_data));
    $chart_values = json_encode(array_values($chart_data));

    // Prepare income chart data
    $income_data = [];
    foreach ($subscriptions as $sub) {
        $month = substr($sub['start_date'], 0, 7);
        $income_data[$month] = isset($income_data[$month]) ? $income_data[$month] + (float) $sub['price'] : (float) $sub['price'];
    }
    $income_labels = json_encode(array_keys($income_data));
    $income_values = json_encode(array_values($income_data));

    // Debug subscriptions
    if (empty($subscriptions) && defined('WP_DEBUG') && WP_DEBUG) {
        global $wpdb;
        error_log('Courscribe: No subscription orders found.');
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'shop_order'");
        error_log('Courscribe: Total shop_order posts: ' . $count);
        $products = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wps_sfw_product' AND meta_value = 'yes')");
        error_log('Courscribe: Subscription products: ' . print_r($products, true));
        foreach ($subscription_orders as $order) {
            $meta = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = {$order->get_id()} AND meta_key LIKE '%subscription%'");
            error_log('Courscribe: Order {$order->get_id()} meta: ' . print_r($meta, true));
        }
    }

    // Fetch Expert Review product ID
    $expert_review_product_id = wc_get_product_id_by_sku('COURSCRIBE-EXPERT-REVIEW');
    if (!$expert_review_product_id) {
        error_log('Courscribe: Expert Review product SKU COURSCRIBE-EXPERT-REVIEW not found.');
    }

    // Fetch orders containing Expert Review product
    $expert_review_orders = [];
    $orders = wc_get_orders([
        'limit'  => -1,
        'status' => ['completed', 'processing', 'on-hold'],
        'type'   => 'shop_order',
    ]);

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $expert_review_product_id) {
                $expert_review_orders[] = [
                    'order'        => $order,
                    'item'         => $item,
                    'curriculum_id' => $item->get_meta('_curriculum_id'),
                ];
                break;
            }
        }
    }

    // Debug Expert Review orders
    if (empty($expert_review_orders) && defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Courscribe: No Expert Review orders found. Product ID: ' . $expert_review_product_id);
        error_log('Courscribe: Total orders checked: ' . count($orders));
    }

    // Get current user
    $current_user = wp_get_current_user();
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
                        <p class="courscribe-loading-text-content__container__text text-white">CourScribe..</p>
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
            Subscriptions & Payments.<br/>
            <span>Subscriptions Dashboard.</span>
        </h3>
        <p class="courscribe-pricing-subheading">Welcome, <?php echo esc_html($current_user->display_name); ?>! View stats details, and activity logs below.</p>

        <!-- Stats Section -->
        <div class="card mb-4" style="margin-left:46px;">
            <div class="card-header">
                <h2 class="courscribe-heading"><i class="fas fa-chart-line me-2"></i>Overview</h2>
            </div>
            <div class="card-body">
                <div class="row" id="courscribe-stats">
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Studios</h5>
                                <p class="card-text text-muted"><?php echo esc_html($total_studios); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Subscriptions</h5>
                                <p class="card-text"><?php echo esc_html($total_subscriptions); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Subscriptions This Month</h5>
                                <p class="card-text"><?php echo esc_html($subscriptions_this_month); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Active Studios</h5>
                                <p class="card-text"><?php echo esc_html($active_studios); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Amount</h5>
                                <p class="card-text"><?php echo wc_price($total_amount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total This Month</h5>
                                <p class="card-text"><?php echo wc_price($total_amount_this_month); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscriptions Chart -->
        <div class="card mb-4" style="margin-left:46px;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-bar me-2"></i>Subscriptions Over Time</h2>
            </div>
            <div class="card-body">
                <canvas id="subscriptionsChart" height="100"></canvas>
            </div>
        </div>

        <!-- Income Chart -->
        <div class="card mb-4" style="margin-left:46px;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line me-2"></i>Income Over Time</h2>
            </div>
            <div class="card-body">
                <canvas id="incomeChart" height="100"></canvas>
            </div>
        </div>

        <!-- Subscriptions Table -->
        <div class="card mb-4" style="margin-left:46px;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line me-2"></i>Studio Subscriptions Statistics</h2>
            </div>
            <div class="card-body">
                <?php if (empty($subscriptions)) : ?>
                    <p>No subscriptions found.</p>
                <?php else : ?>
                    <table class="waitlist-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Studio</th>
                                <th>Subscription</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Start Date</th>
                                <th>Next Renewal</th>
                                <th>End Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription) :
                                $user = get_userdata($subscription['user_id']);
                                $interval = $subscription['number'] . ' ' . $subscription['interval'];
                                ?>
                                <tr>
                                    <td><?php echo $user ? esc_html($user->display_name . ' (' . $user->user_email . ')') : 'Unknown'; ?></td>
                                    <td><?php echo esc_html($subscription['studio_name']); ?></td>
                                    <td><?php echo esc_html($subscription['product_name'] . ' (' . $interval . ')'); ?></td>
                                    <td><?php echo esc_html(ucfirst($subscription['status'])); ?></td>
                                    <td><?php echo wc_price($subscription['price'] ?: 0); ?></td>
                                    <td><?php echo esc_html($subscription['start_date'] ?: 'N/A'); ?></td>
                                    <td><?php echo esc_html($subscription['next_payment'] ?: 'N/A'); ?></td>
                                    <td><?php echo esc_html($subscription['end_date'] ?: 'Indefinite'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expert Review Purchases Table -->
        <div class="card mb-4" style="margin-left:56px;">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line me-2"></i>Expert Review Purchases</h2>
            </div>
            <div class="card-body">
                <?php if (empty($expert_review_orders)) : ?>
                    <p>No Expert Review purchases found.</p>
                <?php else : ?>
                    <table class="waitlist-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Product</th>
                                <th>Order Status</th>
                                <th>Price</th>
                                <th>Purchase Date</th>
                                <th>Curriculum ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expert_review_orders as $order_data) :
                                $order = $order_data['order'];
                                $item = $order_data['item'];
                                $user = get_userdata($order->get_user_id());
                                $product = $item->get_product();
                                ?>
                                <tr>
                                    <td><?php echo $user ? esc_html($user->display_name . ' (' . $user->user_email . ')') : 'Unknown'; ?></td>
                                    <td><?php echo $product ? esc_html($product->get_name()) : 'N/A'; ?></td>
                                    <td><?php echo esc_html(ucfirst($order->get_status())); ?></td>
                                    <td><?php echo wc_price($order->get_total()); ?></td>
                                    <td><?php echo esc_html($order->get_date_created()->date('Y-m-d')); ?></td>
                                    <td><?php echo esc_html($order_data['curriculum_id'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            // Show loader initially
            const $loader = $('#courscribe-loader');
            if ($loader.length) {
                $loader.removeClass('d-none');
                // Hide loader once content is loaded
                $(window).on('load', function() {
                    $loader.addClass('d-none');
                });
                // Fallback timeout to hide loader after 4 seconds
                setTimeout(() => {
                    $loader.addClass('d-none');
                }, 4000);
            } else {
                console.warn('Courscribe loader element not found');
            }

            // Subscriptions Chart
            const ctx = document.getElementById('subscriptionsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo $chart_labels; ?>,
                    datasets: [{
                        label: 'Subscriptions',
                        data: <?php echo $chart_values; ?>,
                        backgroundColor: 'rgba(241, 85, 56, 0.6)',
                        borderColor: 'rgba(241, 85, 56, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    },
                    plugins: {
                        legend: { display: true }
                    }
                }
            });

            // Income Chart
            const incomeCtx = document.getElementById('incomeChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $income_labels; ?>,
                    datasets: [{
                        label: 'Income ($)',
                        data: <?php echo $income_values; ?>,
                        fill: false,
                        borderColor: 'rgba(241, 105, 56, 1)',
                        tension: 0.3
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: { display: true }
                    }
                }
            });
        });
    </script>
    <style>
        .courscribe-dashboard h1 {
            margin-bottom: 20px;
        }
        .courscribe-dashboard h2 {
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .courscribe-dashboard table {
            margin-bottom: 20px;
        }
    </style>
    <?php
}
// AJAX handler for dashboard stats
function courscribe_get_dashboard_stats_old() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    global $wpdb;
    $stats = [
        'studios' => wp_count_posts('crscribe_studio')->publish,
        'curriculums' => wp_count_posts('crscribe_curriculum')->publish,
        'courses' => wp_count_posts('crscribe_course')->publish,
        'modules' => wp_count_posts('crscribe_module')->publish,
        'lessons' => wp_count_posts('crscribe_lesson')->publish,
        'users' => count_users()['total_users'],
        'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_course_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'recent_logs' => $wpdb->get_results(
            "SELECT l.*, u.user_login FROM {$wpdb->prefix}courscribe_course_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            ORDER BY l.timestamp DESC LIMIT 10",
            ARRAY_A
        ),
    ];

    wp_send_json_success($stats);
    wp_die();
}
add_action('wp_ajax_courscribe_get_dashboard_stats_old', 'courscribe_get_dashboard_stats_old');

// AJAX handler for studios dashboard stats
function courscribe_get_studios_stats() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    global $wpdb;
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;

    $query_args = [
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'numberposts' => -1,
    ];
    if (in_array('collaborator', $user_roles)) {
        $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($studio_id) {
            $query_args['p'] = $studio_id;
        }
    } else {
        $query_args['author'] = $current_user->ID;
    }
    $studios = get_posts($query_args);
    $studio_ids = wp_list_pluck($studios, 'ID');

    $stats = [
        'total_studios' => count($studios),
        'total_curriculums' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_curriculum' AND p.post_status = 'publish' AND pm.meta_key = '_studio_id' AND pm.meta_value IN (" . implode(',', array_fill(0, count($studio_ids), '%d')) . ")", $studio_ids),
        'total_collaborators' => count(get_users([
            'role' => 'collaborator',
            'meta_key' => '_courscribe_studio_id',
            'meta_value' => $studio_ids,
            'fields' => 'ID',
        ])),
        'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_studio_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)"),
    ];

    $chart_data = [];
    $labels = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = $date;
        $chart_data[] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_studio_log WHERE DATE(timestamp) = %s",
            $date
        ));
    }

    $logs = [];
    if ($studio_ids) {
        foreach ($studio_ids as $studio_id) {
            $studio_logs = $wpdb->get_results($wpdb->prepare(
                "SELECT timestamp, user_id, action, studio_id, changes AS details FROM {$wpdb->prefix}courscribe_studio_log WHERE studio_id = %d ORDER BY timestamp DESC LIMIT 50",
                $studio_id
            ));
            foreach ($studio_logs as $log) {
                $logs[$studio_id][] = [
                    'timestamp' => $log->timestamp,
                    'user' => get_userdata($log->user_id)->user_login,
                    'action' => $log->action,
                    'studio_id' => $log->studio_id,
                    'details' => $log->details,
                ];
            }
        }
    }

    wp_send_json_success([
        'stats' => $stats,
        'logs' => $logs,
        'activity_chart' => [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Studio Actions',
                'data' => $chart_data,
                'borderColor' => '#F15538',
                'backgroundColor' => 'rgba(241, 85, 56, 0.2)',
                'fill' => true,
            ]],
        ],
    ]);
    wp_die();
}
add_action('wp_ajax_courscribe_get_studios_stats', 'courscribe_get_studios_stats');

// AJAX handler for curriculums dashboard stats
function courscribe_get_curriculums_stats() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    global $wpdb;
    $curriculum_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_curriculum'");

    $stats = [
        'total_curriculums' => $curriculum_count,
        'total_courses' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_course' AND p.post_status = 'publish' AND pm.meta_key = '_curriculum_id'"),
        'total_modules' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_module' AND p.post_status = 'publish' AND pm.meta_key = '_curriculum_id'"),
        'total_lessons' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_lesson' AND p.post_status = 'publish' AND pm.meta_key = '_curriculum_id'"),
        'activity_logs' => $wpdb->get_results(
            "SELECT l.*, u.user_login, 'course' as type, pm.meta_value as curriculum_id 
            FROM {$wpdb->prefix}courscribe_course_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON l.course_id = pm.post_id AND pm.meta_key = '_curriculum_id'
            WHERE pm.meta_value IS NOT NULL
            UNION
            SELECT l.*, u.user_login, 'module' as type, pm.meta_value as curriculum_id 
            FROM {$wpdb->prefix}courscribe_module_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON l.module_id = pm.post_id AND pm.meta_key = '_curriculum_id'
            WHERE pm.meta_value IS NOT NULL
            UNION
            SELECT l.*, u.user_login, 'lesson' as type, pm.meta_value as curriculum_id 
            FROM {$wpdb->prefix}courscribe_lesson_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON l.lesson_id = pm.post_id AND pm.meta_key = '_curriculum_id'
            WHERE pm.meta_value IS NOT NULL
            ORDER BY timestamp DESC LIMIT 50",
            ARRAY_A
        ),
        'activity_chart' => $wpdb->get_results(
            "SELECT DATE(timestamp) as date, 
                    SUM(CASE WHEN l.table_name = 'course' THEN 1 ELSE 0 END) as course_actions,
                    SUM(CASE WHEN l.table_name = 'module' THEN 1 ELSE 0 END) as module_actions,
                    SUM(CASE WHEN l.table_name = 'lesson' THEN 1 ELSE 0 END) as lesson_actions
            FROM (
                SELECT 'course' as table_name, timestamp FROM {$wpdb->prefix}courscribe_course_log
                UNION ALL
                SELECT 'module' as table_name, timestamp FROM {$wpdb->prefix}courscribe_module_log
                UNION ALL
                SELECT 'lesson' as table_name, timestamp FROM {$wpdb->prefix}courscribe_lesson_log
            ) l
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC",
            ARRAY_A
        ),
    ];

    wp_send_json_success($stats);
    wp_die();
}
add_action('wp_ajax_courscribe_get_curriculums_stats', 'courscribe_get_curriculums_stats');

// AJAX handler for courses dashboard stats
function courscribe_get_courses_stats() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    global $wpdb;
    $stats = [
        'total_courses' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_course'"),
        'total_modules' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_module' AND p.post_status = 'publish' AND pm.meta_key = '_course_id'"),
        'total_lessons' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_lesson' AND p.post_status = 'publish' AND pm.meta_key = '_course_id'"),
        'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_course_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'activity_logs' => $wpdb->get_results(
            "SELECT l.*, u.user_login, pm.meta_value as curriculum_id 
            FROM {$wpdb->prefix}courscribe_course_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON l.course_id = pm.post_id AND pm.meta_key = '_curriculum_id'
            WHERE l.timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY l.timestamp DESC LIMIT 50",
            ARRAY_A
        ),
        'activity_chart' => $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as actions
            FROM {$wpdb->prefix}courscribe_course_log
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC",
            ARRAY_A
        ),
    ];

    wp_send_json_success($stats);
    wp_die();
}
add_action('wp_ajax_courscribe_get_courses_stats', 'courscribe_get_courses_stats');

// AJAX handler for modules dashboard stats
function courscribe_get_modules_stats() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    global $wpdb;
    $stats = [
        'total_modules' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_module' AND post_status = 'publish'"),
        'total_lessons' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'crscribe_lesson' AND p.post_status = 'publish' AND pm.meta_key = '_module_id'"),
        'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_module_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'activity_logs' => $wpdb->get_results(
            "SELECT l.*, u.user_login, pm.meta_value as course_id 
            FROM {$wpdb->prefix}courscribe_module_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON l.module_id = pm.post_id AND pm.meta_key = '_course_id'
            WHERE l.timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY l.timestamp DESC LIMIT 50",
            ARRAY_A
        ),
        'activity_chart' => $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as actions
            FROM {$wpdb->prefix}courscribe_module_log
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC",
            ARRAY_A
        ),
    ];

    wp_send_json_success($stats);
    wp_die();
}
add_action('wp_ajax_courscribe_get_modules_stats', 'courscribe_get_modules_stats');

// AJAX handler for lessons dashboard stats
function courscribe_get_lessons_stats() {
    check_ajax_referer('courscribe_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    global $wpdb;
    $stats = [
        'total_lessons' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crscribe_lesson' AND post_status = 'publish'"),
        'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}courscribe_lesson_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'activity_logs' => $wpdb->get_results(
            "SELECT l.*, u.user_login, pm.meta_value as module_id 
            FROM {$wpdb->prefix}courscribe_lesson_log l
            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
            LEFT JOIN {$wpdb->postmeta} pm ON l.lesson_id = pm.post_id AND pm.meta_key = '_module_id'
            WHERE l.timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY l.timestamp DESC LIMIT 50",
            ARRAY_A
        ),
        'activity_chart' => $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as actions
            FROM {$wpdb->prefix}courscribe_lesson_log
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC",
            ARRAY_A
        ),
    ];

    wp_send_json_success($stats);
    wp_die();
}
add_action('wp_ajax_courscribe_get_lessons_stats', 'courscribe_get_lessons_stats');

// Create log tables
function courscribe_create_log_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Course log table
    $course_table = $wpdb->prefix . 'courscribe_course_log';
    $sql_course = "CREATE TABLE $course_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        course_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        action VARCHAR(255) NOT NULL,
        changes LONGTEXT NOT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY course_id (course_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // Module log table
    $module_table = $wpdb->prefix . 'courscribe_module_log';
    $sql_module = "CREATE TABLE $module_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        module_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        action VARCHAR(255) NOT NULL,
        changes LONGTEXT NOT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY module_id (module_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // Lesson log table
    $lesson_table = $wpdb->prefix . 'courscribe_lesson_log';
    $sql_lesson = "CREATE TABLE $lesson_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        lesson_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        action VARCHAR(255) NOT NULL,
        changes LONGTEXT NOT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY lesson_id (lesson_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_course);
    dbDelta($sql_module);
    dbDelta($sql_lesson);
}
register_activation_hook(__FILE__, 'courscribe_create_log_tables');

// Register settings
function courscribe_register_settings() {
    register_setting('courscribe_settings', 'courscribe_tier', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('courscribe_settings', 'courscribe_register_page', ['sanitize_callback' => 'absint']);
    register_setting('courscribe_settings', 'courscribe_create_studio_page', ['sanitize_callback' => 'absint']);
    register_setting('courscribe_settings', 'courscribe_select_tribe_page', ['sanitize_callback' => 'absint']);
    register_setting('courscribe_settings', 'courscribe_studio_page', ['sanitize_callback' => 'absint']);
    register_setting('courscribe_settings', 'courscribe_welcome_page', ['sanitize_callback' => 'absint']);
    register_setting('courscribe_settings', 'courscribe_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('courscribe_settings', 'courscribe_email_notifications', ['sanitize_callback' => 'absint']);
    register_setting('courscribe_settings', 'courscribe_cache_enabled', ['sanitize_callback' => 'absint']);

    add_settings_section(
        'courscribe_main',
        'Courscribe Configuration',
        function() { echo '<p>Configure Courscribe settings below.</p>'; },
        'courscribe_settings'
    );

    add_settings_field(
        'courscribe_tier',
        'Select Tier',
        'courscribe_tier_select_callback',
        'courscribe_settings',
        'courscribe_main',
        ['label_for' => 'courscribe_tier']
    );

    $pages = [
        'courscribe_register_page' => 'Register Page',
        'courscribe_create_studio_page' => 'Create Studio Page',
        'courscribe_select_tribe_page' => 'Select Tribe Page',
        'courscribe_studio_page' => 'Studio Page',
        'courscribe_welcome_page' => 'Welcome Page',
    ];
    foreach ($pages as $option => $label) {
        add_settings_field(
            $option,
            $label,
            'courscribe_page_select_callback',
            'courscribe_settings',
            'courscribe_main',
            ['option' => $option, 'label_for' => $option]
        );
    }

    add_settings_field(
        'courscribe_api_key',
        'API Key',
        function() {
            $api_key = get_option('courscribe_api_key', '');
            echo '<input type="text" name="courscribe_api_key" id="courscribe_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        },
        'courscribe_settings',
        'courscribe_main',
        ['label_for' => 'courscribe_api_key']
    );

    add_settings_field(
        'courscribe_email_notifications',
        'Email Notifications',
        function() {
            $enabled = get_option('courscribe_email_notifications', 1);
            echo '<input type="checkbox" name="courscribe_email_notifications" id="courscribe_email_notifications" value="1" ' . checked(1, $enabled, false) . '>';
        },
        'courscribe_settings',
        'courscribe_main',
        ['label_for' => 'courscribe_email_notifications']
    );

    add_settings_field(
        'courscribe_cache_enabled',
        'Enable Caching',
        function() {
            $enabled = get_option('courscribe_cache_enabled', 1);
            echo '<input type="checkbox" name="courscribe_cache_enabled" id="courscribe_cache_enabled" value="1" ' . checked(1, $enabled, false) . '>';
        },
        'courscribe_settings',
        'courscribe_main',
        ['label_for' => 'courscribe_cache_enabled']
    );
}
add_action('admin_init', 'courscribe_register_settings');

function courscribe_tier_select_callback() {
    $current_tier = get_option('courscribe_tier', 'basics');
    $tiers = [
        'basics' => 'Basics (Free)',
        'plus' => 'Plus ($14/mo or $140/yr)',
        'pro' => 'Pro ($37/mo or $370/yr)',
    ];
    ?>
    <select name="courscribe_tier" id="courscribe_tier">
        <?php foreach ($tiers as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_tier, $value); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <p class="description">Upgrade at <a href="https://courscribe.com/pricing" target="_blank">Courscribe Pricing</a>.</p>
    <?php
}

function courscribe_page_select_callback($args) {
    $option = $args['option'];
    $selected = get_option($option);
    wp_dropdown_pages([
        'name' => $option,
        'selected' => $selected,
        'show_option_none' => '— Select —',
    ]);
}

// Dashboard callback
function courscribe_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_dashboard();
}

// Curriculums dashboard callback
function courscribe_curriculums_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_curriculums_dashboard();
}

// Courses dashboard callback
function courscribe_courses_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_courses_dashboard();
}

// Modules dashboard callback
function courscribe_modules_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_modules_dashboard();
}

// Lessons dashboard callback
function courscribe_lessons_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_lessons_dashboard();
}

// Studios dashboard callback
function courscribe_studios_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_studios_dashboard();
}

// Settings page
function courscribe_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    courscribe_render_settings_page();
}
function courscribe_annotations_admin_page() {
    global $wpdb;
    $annotations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}courscribe_annotations");
    ?>
    <div class="wrap">
        <h1>Courscribe Annotations</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Post ID</th>
                <th>Post Type</th>
                <th>Field</th>
                <th>Annotation</th>
                <th>User</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($annotations as $annotation) : ?>
                <tr>
                    <td><?php echo esc_html($annotation->id); ?></td>
                    <td><?php echo esc_html($annotation->post_id); ?></td>
                    <td><?php echo esc_html($annotation->post_type); ?></td>
                    <td><?php echo esc_html($annotation->field_id); ?></td>
                    <td><?php echo esc_html(json_decode($annotation->annotation_data)->text); ?></td>
                    <td><?php echo esc_html(get_userdata($annotation->user_id)->display_name); ?></td>
                    <td><?php echo esc_html($annotation->status); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>