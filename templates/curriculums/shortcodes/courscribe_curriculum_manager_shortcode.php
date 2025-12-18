<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Path: templates/curriculums/shortcodes/courscribe_curriculum_manager_shortcode.php

// Helper function to generate curriculum URLs using ID for reliability
function courscribe_get_curriculum_url($curriculum_id) {
    $curriculum_page = get_page_by_path('courscribe-curriculum');
    if ($curriculum_page) {
        // Use ID-based URLs for reliability - curriculums can have same titles
        return get_permalink($curriculum_page->ID) . $curriculum_id . '/';
    }
    return home_url('/?p=' . $curriculum_id);
}

add_shortcode( 'courscribe_curriculum_manager', 'courscribe_curriculum_manager_shortcode' );

function courscribe_curriculum_manager_shortcode() {
    global $wpdb;
    $site_url = home_url();

    if ( ! is_user_logged_in() ) {
        return courscribe_retro_tv_error("Please log in to manage curriculums.");
    }

    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $table_name = $wpdb->prefix . 'courscribe_invites';

    $tier = get_option( 'courscribe_tier', 'basics' );
    $is_collaborator = in_array( 'collaborator', $user_roles );
    $is_client = in_array( 'client', $user_roles );
    $is_studio_admin = in_array( 'studio_admin', $user_roles );
    $is_wp_admin = current_user_can('administrator');

    $can_manage = current_user_can( 'edit_crscribe_curriculums' ) ||
        $is_collaborator ||
        $is_studio_admin ||
        $is_client;

    if ( ! $can_manage ) {
        return courscribe_retro_tv_error("You do not have permission to manage curriculums.");
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
        // if (!$is_subscription_active && in_array('studio_admin', $user_roles)) {
        //     $pricing_page_id = get_option('courscribe_pricing_page');
        //     $pricing_url = $pricing_page_id ? get_permalink($pricing_page_id) : home_url('/select-tribe');
        //     error_log('Courscribe: User ' . $current_user->ID . ' has no active subscription, redirecting to pricing page: ' . $pricing_url);
        //     echo "
        //     <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        //     <script>
        //     Swal.fire({
        //     title: 'Hold on!',
        //     text: 'You have no active subscription. You’ll be redirected to the pricing page.',
        //     icon: 'warning',
        //     showCancelButton: false,
        //     confirmButtonColor: '#E4B26F',
        //     allowOutsideClick: false,
        //     cancelButtonColor: '#d33',
        //     confirmButtonText: 'Take me there',
        //     cancelButtonText: 'Cancel'
        //     }).then((result) => {
        //     if (result.isConfirmed) {
        //         window.location.href = '" . esc_url($pricing_url) . "';
        //     }
        //     });
        //     </script>";
        //     return '';
        // }
        
    }

    // Determine user's studio ID for autofill
    $user_studio_id = 0;
    if ($is_collaborator || $is_client) {
        $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($is_client && !$user_studio_id) {
            global $wpdb;
            $invite_table = $wpdb->prefix . 'courscribe_client_invites';
            $first_invite = $wpdb->get_row($wpdb->prepare(
                "SELECT curriculum_id FROM $invite_table WHERE email = %s AND status = 'Accepted' ORDER BY created_at ASC LIMIT 1",
                $current_user->user_email
            ));
            if ($first_invite) {
                $user_studio_id = get_post_meta($first_invite->curriculum_id, '_studio_id', true);
            }
        }
    } elseif ($is_studio_admin || $is_wp_admin) {
        $studios = get_posts(array(
            'post_type' => 'crscribe_studio',
            'author' => $current_user->ID,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));
        if (!empty($studios)) {
            $user_studio_id = $studios[0];
        }
    }
     wp_enqueue_script(
        'hair-diary-js', 
        plugin_dir_url(__FILE__) . '../../../assets/js/ui/sidebar.js', 
        [], 
        '1.0.10'
    );
    wp_enqueue_style('myavana-studio-c-styles', plugin_dir_url(__FILE__) . '../../../assets/css/studio.css', [], '2.0.1');
    wp_enqueue_style('myavana-wrapper-styles', plugin_dir_url(__FILE__) . '../../../assets/css/wrapper.css', [], '2.0.1');

    ob_start();
    $message = '';

   

    echo $message;

    $steps = [
        'Curriculums Stage',
        'Courses Stage',
        'Modules Stage',
        'Lessons Stage',
        'Teaching Points Stage'
    ];
    $tooltips = CourScribe_Tooltips::get_instance();
    $currentStep = 0;
    $user_name = $current_user->display_name;
    $current_hour = date("H");
    $greeting = (($current_hour >= 5 && $current_hour < 12) ? "Good morning" : ($current_hour >= 12 && $current_hour < 18)) ? "Good afternoon" : "Good evening";
    $studio_name_display = $user_studio_id ? get_post($user_studio_id)->post_title : ($is_wp_admin ? "All Studios (Admin View)" : 'No Studio Associated');

    // Get curriculums for this studio
    $studio_curriculums = get_posts(array(
        'post_type' => 'crscribe_curriculum',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_studio_id',
                'value' => $user_studio_id,
                'compare' => '='
            )
        ),
        'fields' => 'ids'
    ));
    ?>
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/tabs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/nucleo-icons.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/nucleo-svg.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/studio.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@recogito/annotorious@2.7.12/dist/annotorious.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@recogito/annotorious@2.7.12/dist/annotorious.min.js"></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/chartjs.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/html2canvas.js" defer></script>
    <script src="https://unpkg.com/@sjmc11/tourguidejs/dist/tour.js" crossorigin="anonymous" referrerpolicy="no-referrer" type="module"></script>
    <link rel="stylesheet" href="https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css">

    <!-- Simplified Styles -->
    <style>
        .courscribe-curriculum-manager { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .courscribe-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .courscribe-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .courscribe-error ul { margin: 10px 0 0 20px; }
        .form-control { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .courscribe-give-feedback-btn, .courscribe-view-feedback-btn {
            background-color: #E9B56F; color: #231f20; border: none; border-radius: 8px; padding: 8px 12px; font-size: 0.9em; cursor: pointer; text-align: center; display: block;
        }
        .courscribe-view-feedback-btn { background-color: #5C52A2; color: white; }
        .courscribe-feedback-adornment-wrapper { margin-top: 5px; }
        .feedback-hidden { display: none !important; }
        /* Premium Modal Styles */
        .courscribe-modal-overlay { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(35, 31, 32, 0.8); 
            backdrop-filter: blur(5px);
            display: none; 
            justify-content: center; 
            align-items: center; 
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }
        .courscribe-modal { 
            background: #2a2a2b;
            border-radius: 16px; 
            box-shadow: 0 20px 60px rgba(35, 31, 32, 0.3);
            max-width: 450px; 
            width: 100%;
            overflow: hidden;
            transform: scale(0.9);
            animation: modalSlideIn 0.4s ease forwards;
            align-self: center;
            position: relative;
            margin-left: 30%;
            display: flex;
            flex-direction: column;
        }
        .courscribe-modal-header { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white; 
            padding: 20px 25px; 
            text-align: center;
            position: relative;
        }
        .courscribe-modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.1;
        }
        .courscribe-modal-icon { 
            font-size: 28px; 
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        .courscribe-modal-title { 
            font-size: 18px; 
            font-weight: 600; 
            margin: 0;
            position: relative;
            z-index: 1;
        }
        .courscribe-modal-body { 
            padding: 30px 25px; 
            text-align: center;
        }
        .courscribe-modal-message { 
            font-size: 16px; 
            color: #fff; 
            margin: 0 0 25px 0; 
            line-height: 1.5;
        }
        .courscribe-modal-curriculum-name {
            background: #f8f9fa;
            color: #E4B26F;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            margin: 15px 0;
            border-left: 4px solid #E4B26F;
        }
        .courscribe-modal-actions { 
            display: flex; 
            gap: 15px; 
            justify-content: center;
            flex-wrap: wrap;
        }
        .courscribe-modal-btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease;
            min-width: 120px;
            position: relative;
            overflow: hidden;
            
        }
        .courscribe-modal-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .courscribe-modal-btn:hover::before {
            left: 100%;
        }
        .courscribe-modal-btn-danger { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        .courscribe-modal-btn-danger:hover { 
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            transform: translateY(-2px);
        }
        .courscribe-modal-btn-secondary { 
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .courscribe-modal-btn-secondary:hover { 
            background: linear-gradient(135deg, #545b62 0%, #495057 100%);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalSlideIn {
            from { 
                transform: scale(0.9) translateY(-50px); 
                opacity: 0; 
            }
            to { 
                transform: scale(1) translateY(0); 
                opacity: 1; 
            }
        }
        .tg-dialog {background-color:#2a2a2b}
        .tg-dialog {background-color:#2a2a2b}
        /* Floating Help Button Styles */
        .courscribe-help-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #665442;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            font-size: 24px;
            z-index: 1000;
            transition: background-color 0.3s;
        }
        .courscribe-help-toggle:hover {
            background-color: #E4B26F;
        }
        /* Tour Guide Custom Styles */
        .tg-dialog {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-family: 'Open Sans', sans-serif;
        }
        .tg-dialog-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #E4B26F;
            margin-bottom: 10px;
        }
        .tg-dialog-body {
            font-size: 1em;
            color: #d1d1d1;
            line-height: 1.5;
        }
        .tg-step-actions .btn {
            margin: 5px;
            padding: 8px 16px;
            font-size: 0.9em;
        }
        .tg-dialog-close-btn svg{
            fill: #fff;
        }
        .tg-dialog .tg-dialog-dots>span.tg-dot.tg-dot-active {
            background: #E4B26F;
        }
        
        /* Recent Changes Filters and Pagination Styles */
        .cs-changelog-filters {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .cs-filter-form {
            margin: 0;
        }
        .cs-filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .cs-filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cs-search-input, .cs-action-filter, .cs-per-page-filter {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 140px;
        }
        .cs-search-input {
            min-width: 200px;
        }
        .cs-filter-btn, .cs-clear-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .cs-filter-btn {
            background: #E4B26F;
            color: #231f20;
            font-weight: 500;
        }
        .cs-filter-btn:hover {
            background: #d4a05c;
            color: #231f20;
        }
        .cs-clear-btn {
            background: #6c757d;
            color: white;
        }
        .cs-clear-btn:hover {
            background: #545b62;
            color: white;
        }
        
        /* Pagination Styles */
        .cs-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px 0;
            border-top: 1px solid #e9ecef;
        }
        .cs-pagination-info {
            color: #6c757d;
            font-size: 14px;
        }
        .cs-pagination-nav {
            display: flex;
            gap: 5px;
        }
        .cs-page-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            color: #495057;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .cs-page-btn:hover {
            background: #E4B26F;
            color: #231f20;
            border-color: #E4B26F;
        }
        .cs-page-btn.active {
            background: #E4B26F;
            color: #231f20;
            border-color: #E4B26F;
            font-weight: 600;
        }
        
        /* No Changes Message */
        .cs-no-changes {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .cs-no-changes i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        .cs-no-changes p {
            font-size: 16px;
            margin-bottom: 15px;
        }
        .cs-clear-filters-btn {
            background: #E4B26F;
            color: #231f20;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .cs-clear-filters-btn:hover {
            background: #d4a05c;
            color: #231f20;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .cs-filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            .cs-filter-group {
                width: 100%;
                justify-content: stretch;
            }
            .cs-search-input, .cs-action-filter, .cs-per-page-filter {
                width: 100%;
                min-width: auto;
            }
            .cs-pagination {
                flex-direction: column;
                gap: 15px;
            }
        }
        
        /* Archived Curriculums Section Styles */
        .courscribe-archived-section {
            margin: 40px 0 20px 0;
            border: 1px solid #E4B26F;
            border-radius: 12px;
            /* overflow: hidden; */
        }
        .courscribe-archived-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .courscribe-archived-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.6s;
        }
        .courscribe-archived-header:hover::before {
            left: 100%;
        }
        .courscribe-archived-header:hover {
            background: linear-gradient(135deg, #5a6268 0%, #43494e 100%);
        }
        .courscribe-archived-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .courscribe-archived-count {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 500;
        }
        .courscribe-archived-toggle {
            transition: transform 0.3s ease;
            position: relative;
            z-index: 1;
        }
        .courscribe-archived-toggle.expanded {
            transform: rotate(180deg);
        }
        .courscribe-archived-content {
            padding: 20px;
        }
        .courscribe-archived-info {
            border: 1px solid #E4B26F;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #E4B26F;
        }
        .courscribe-archived-info i {
            margin-right: 8px;
        }
        .archived-curriculum-box {
            opacity: 0.85;
            border: 2px dashed #E4B26F;
            background: #2a2a2b;
            position: relative;
        }
        .archived-curriculum-box::before {
            content: 'ARCHIVED';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: 600;
            z-index: 2;
        }
        .archived-meta {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #E4B26F;
        }
        .archived-meta p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #6c757d;
        }
        .archived-status {
            background: #232323;
            color: #856404;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .courscribe-archived-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .courscribe-unarchive-btn, .courscribe-delete-archived-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .courscribe-unarchive-btn {
            background: #E4B26F;
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        .courscribe-unarchive-btn:hover {
            background: #E4B26F;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            transform: translateY(-2px);
        }
        .courscribe-delete-archived-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        .courscribe-delete-archived-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            transform: translateY(-2px);
        }
    </style>

     <!-- Floating Help Button -->
    <button class="courscribe-help-toggle" title="Start Guided Tour">
        <i class="fa fa-question"></i>
    </button>
    <div class="content">
            <h2 class="courscribe-studio-h2"><?php echo esc_html( $studio_name_display ); ?></h2>
            <h3 class="courscribe-h3-white"><?php echo esc_html($greeting); ?>, <span><?php echo esc_html($user_name); ?></span></h3>
            <p class="courscribe-p-gray">Which curriculum would you like to work on today?</p>

            

            <!-- Create Curriculum Form -->
            <?php if (!$is_client && (current_user_can('edit_crscribe_curriculums') || $is_studio_admin || ($is_collaborator && in_array('edit_crscribe_curriculums', get_user_meta($current_user->ID, '_courscribe_collaborator_permissions', true) ?: [])))) : ?>
                <div class="curriculum-header-enhanced">
                <div class="header-left">
                    <div class="curriculum-summary">
                        <div class="summary-item">
                            <div class="summary-number"><?php echo count($studio_curriculums); ?></div>
                            <div class="summary-label">Total</div>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-item">
                            <div class="summary-number"><?php echo count(array_filter($studio_curriculums, function($id) { return get_post_status($id) === 'publish'; })); ?></div>
                            <div class="summary-label">Active</div>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-item">
                            <div class="summary-number"><?php echo count(array_filter($studio_curriculums, function($id) { return get_post_meta($id, '_curriculum_archived', true) === 'yes'; })); ?></div>
                            <div class="summary-label">Archived</div>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <div class="curriculum-actions-enhanced">
                        <button class="action-btn-enhanced secondary"  title="Export All">
                            <i class="fas fa-download"></i>
                            <span>Export</span>
                        </button>
                        <button class="action-btn-enhanced secondary"  title="Import">
                            <i class="fas fa-upload"></i>
                            <span>Import</span>
                        </button>
                        <button class="action-btn-enhanced primary pulse" id="addCurriculumBtn" title="Create New">
                            <i class="fas fa-plus-circle"></i>
                            <span>New Curriculum</span>
                            <div class="btn-shine"></div>
                        </button>
                    </div>
                </div>
            </div>
            
            <div id="createCurriculumForm" style="display: none;">
                <div class="curriculum-box">
                    <div class="row">
                        <form method="post" class="courscribe-curriculum-form">
                            <?php wp_nonce_field( 'courscribe_curriculum', 'courscribe_curriculum_nonce' ); ?>
                            <input type="hidden" name="curriculum_id" value="0">
                            <div class="row mb-3 mt-3" style="align-items: center;">
                                <div class="col-6">
                                    <label for="curriculum_title">Title</label>
                                    <div class="curriculum-input-wrapper">
                                        <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/title-icon.png" alt="Icon" class="curriculum-input-icon">
                                        <input type="text" id="curriculum_title" name="curriculum_title" class="form-control bg-dark text-light ml-2" style="margin-left:14px;" required />
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label for="curriculum_topic">Topic</label>
                                    <div class="curriculum-input-wrapper">
                                        <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/topic-icon.png" alt="Icon" class="curriculum-input-icon">
                                        <input type="text" id="curriculum_topic" name="curriculum_topic" class="form-control bg-dark text-light ml-2" style="margin-left:14px;" required />
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="curriculum_goal">Goal</label>
                                <div class="curriculum-input-wrapper">
                                    <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/goal-icon.png" alt="Icon" class="curriculum-input-icon">
                                    <input type="text" id="curriculum_goal" name="curriculum_goal" class="form-control bg-dark text-light ml-2" style="margin-left:14px;" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="curriculum_notes_new">Notes</label>
                                <?php
                                wp_editor('', 'curriculum_notes_new', array(
                                    'textarea_name' => 'curriculum_notes',
                                    'media_buttons' => false, 'teeny' => true, 'quicktags' => false, 'textarea_rows' => 10, 'editor_height' => 200
                                ));
                                ?>
                            </div>
                            <div class="row mb-3 mt-3" style="align-items: center;">
                                <div class="col-6">
                                    <label for="curriculum_status">Status</label>
                                    <select id="curriculum_status" name="curriculum_status" class="form-control">
                                        <option value="draft">Draft</option>
                                        <option value="review">Review</option>
                                        <option value="approved">Approved</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="curriculum_studio">Studio</label>
                                    <input type="hidden" name="curriculum_studio" value="<?php echo esc_attr($user_studio_id); ?>">
                                    <input type="text" class="form-control" value="<?php echo esc_attr($studio_name_display); ?>" disabled>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <a id="cancelCurriculumBtn" class="text-white text-sm font-weight-bold mb-0 icon-move-right" href="#">Cancel <i class="fas fa-arrow-right text-sm ms-1" aria-hidden="true"></i></a>
                                <div class="d-flex justify-content-end align-items-center gap-4">
                                    <button type="button" class="Documents-btn courscribe-save-curriculum" data-action="create">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Curriculum List -->
            
            <?php
        
            $query_args = array(
                'post_type' => 'crscribe_curriculum',
                'post_status' => ['publish', 'draft', 'pending', 'future'],
                'posts_per_page' => 10,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_curriculum_status',
                        'value' => 'archived',
                        'compare' => '!=',
                    ),
                ),
            );

            if ($is_client) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'courscribe_client_invites';
                $invited_curriculums = $wpdb->get_col($wpdb->prepare(
                    "SELECT curriculum_id FROM $table_name WHERE email = %s AND status = 'Accepted'",
                    $current_user->user_email
                ));
                $query_args['post__in'] = !empty($invited_curriculums) ? $invited_curriculums : [0];
            } else {
                if ($is_collaborator && $user_studio_id) {
                    $query_args['meta_query'][] = array(
                        'key' => '_studio_id',
                        'value' => $user_studio_id,
                        'compare' => '=',
                    );
                } elseif ($is_studio_admin) {
                    $admin_studios = get_posts(array(
                        'post_type' => 'crscribe_studio',
                        'post_status' => 'publish',
                        'author' => $current_user->ID,
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    ));
                    if (!empty($admin_studios)) {
                        $query_args['meta_query'][] = array(
                            'key' => '_studio_id',
                            'value' => $admin_studios,
                            'compare' => 'IN',
                        );
                    } else {
                        $query_args['post__in'] = [0];
                    }
                }
            }

            $query = new WP_Query( $query_args );

            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) : $query->the_post();
                    $post_id = get_the_ID();
                    $curriculum_title_for_data = get_the_title();
                    $topic = get_post_meta( $post_id, '_curriculum_topic', true );
                    $goal = get_post_meta( $post_id, '_curriculum_goal', true );
                    $notes = get_post_meta( $post_id, '_curriculum_notes', true );
                    $status = get_post_meta( $post_id, '_curriculum_status', true ) ?: 'draft';
                    $current_curriculum_studio_id = get_post_meta( $post_id, '_studio_id', true );
                    $studio_name = $current_curriculum_studio_id ? get_post($current_curriculum_studio_id)->post_title : 'No Studio';

                    $can_edit_this_curriculum = $is_studio_admin || $is_wp_admin || ($is_collaborator && in_array('edit_crscribe_curriculums', get_user_meta($current_user->ID, '_courscribe_collaborator_permissions', true) ?: []) && get_user_meta($current_user->ID, '_courscribe_studio_id', true) == $current_curriculum_studio_id);
                    $is_form_readonly = $is_client || !$can_edit_this_curriculum;
                    $can_view_feedback = $is_studio_admin || $is_wp_admin;

                    // Check feedback count
                    $feedback_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_annotations WHERE post_id = %d AND post_type = %s AND field_id = %s",
                        $post_id, 'crscribe_curriculum', 'curriculum-overall-' . $post_id
                    ));

                    ?>
                    <div class="curriculum-box mb-4">
                        <div class="row">
                            <div class="curriculum-96">
                                <?php if ($is_client || $can_edit_this_curriculum): ?>
                                    <form method="post" class="courscribe-curriculum-form">
                                        <?php wp_nonce_field( 'courscribe_curriculum', 'courscribe_curriculum_nonce' ); ?>
                                        <input type="hidden" name="curriculum_id" value="<?php echo esc_attr( $post_id ); ?>">
                                        <div class="row mb-3 mt-3" style="align-items: center;">
                                            <div class="col-6">
                                                <?php if ($is_client) : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <input class="courscribe-client-review-input" name="curriculums-client-review-input-[<?php echo esc_attr($post_id); ?>][curriculum_title]" placeholder="Enter new item here" type="text" value="<?php the_title(); ?>" id="courscribe-client-review-input-field" disabled>
                                                        <div class="courscribe-client-review-submit-button" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_title]" data-field-id="curriculum-title-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php the_title(); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="title" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel"><span>Give Title Feedback</span></div>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <label for="curriculum_title-<?php echo $post_id; ?>">Title</label>
                                                        <div class="curriculum-input-wrapper">
                                                            <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/title-icon.png" alt="Icon" class="curriculum-input-icon">
                                                            <input type="text" style="margin-left:14px !important;" id="curriculum_title-<?php echo $post_id; ?>" name="curriculum_title" value="<?php the_title(); ?>" class="form-control" required <?php if($is_form_readonly) echo 'readonly'; ?> />
                                                        </div>
                                                        <?php if ($can_view_feedback && $feedback_count > 0) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_title]" data-field-id="curriculum-title-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php the_title(); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="title" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Title Feedback</span>
                                                                <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6">
                                                <?php if ($is_client) : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <input class="courscribe-client-review-input" name="curriculums-client-review-input-[<?php echo esc_attr($post_id); ?>][curriculum_topic]" placeholder="Enter new item here" type="text" value="<?php echo esc_attr( $topic ); ?>" id="courscribe-client-review-input-field" disabled>
                                                        <div class="courscribe-client-review-submit-button" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_topic]" data-field-id="curriculum-topic-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $topic ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="topic" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel"><span>Give Topic Feedback</span></div>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <label for="curriculum_topic-<?php echo $post_id; ?>">Topic</label>
                                                        <div class="curriculum-input-wrapper">
                                                            <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/topic-icon.png" alt="Icon" class="curriculum-input-icon">
                                                            <input type="text" id="curriculum_topic-<?php echo $post_id; ?>" name="curriculum_topic" style="margin-left:14px;" value="<?php echo esc_attr( $topic ); ?>" class="form-control" required <?php if($is_form_readonly) echo 'readonly'; ?> />
                                                        </div>
                                                        <?php if ($can_view_feedback && $feedback_count > 0) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_topic]" data-field-id="curriculum-topic-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $topic ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="topic" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Topic Feedback</span>
                                                                <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <?php if ($is_client) : ?>
                                                <div class="courscribe-client-review-input-group">
                                                    <input class="courscribe-client-review-input" name="curriculums-client-review-input-[<?php echo esc_attr($post_id); ?>][curriculum_goal]" placeholder="Enter new item here" type="text" value="<?php echo esc_attr( $goal ); ?>" id="courscribe-client-review-input-field" disabled>
                                                    <div class="courscribe-client-review-submit-button" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_goal]" data-field-id="curriculum-goal-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $goal ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="goal" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel"><span>Give Goal Feedback</span></div>
                                                </div>
                                            <?php else : ?>
                                                <div class="courscribe-client-review-input-group">
                                                    <label for="curriculum_goal-<?php echo $post_id; ?>">Goal</label>
                                                    <div class="curriculum-input-wrapper">
                                                        <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/goal-icon.png" alt="Icon" class="curriculum-input-icon">
                                                        <input type="text" id="curriculum_goal-<?php echo $post_id; ?>" style="margin-left:14px;" name="curriculum_goal" value="<?php echo esc_attr( $goal ); ?>" class="form-control" <?php if($is_form_readonly) echo 'readonly'; ?> />
                                                    </div>
                                                    <?php if ($can_view_feedback && $feedback_count > 0) : ?>
                                                        <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_goal]" data-field-id="curriculum-goal-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $goal ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="goal" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                                            <span class="courscribe-client-review-end-adrnment-tooltip">View Goal Feedback</span>
                                                            <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="notes-<?php echo $post_id; ?>">Notes</label>
                                            <?php
                                            $editor_id = 'curriculum_notes-' . $post_id;
                                            $settings = array(
                                                'textarea_name' => 'curriculum_notes',
                                                'media_buttons' => false,
                                                'teeny' => true,
                                                'quicktags' => false,
                                                'textarea_rows' => 10,
                                                'editor_height' => 200,
                                                'editor_class' => $is_form_readonly ? 'courscribe-readonly-editor' : ''
                                            );
                                            wp_editor($notes, $editor_id, $settings);
                                            if ($is_form_readonly) {
                                                echo "<script>jQuery(document).ready(function($){ setTimeout(function() { $('#wp-{$editor_id}-wrap').addClass('disabled'); var editor = tinymce.get('{$editor_id}'); if(editor) editor.setMode('readonly'); }, 500); });</script>";
                                                echo "<style>#wp-{$editor_id}-wrap.disabled { pointer-events: none; opacity: 0.7; }</style>";
                                            }
                                            ?>
                                        </div>
                                        <div class="row mb-3 mt-3" style="align-items: center;">
                                            <div class="col-6">
                                                <label for="curriculum_status-<?php echo $post_id; ?>">Status</label>
                                                <select id="curriculum_status-<?php echo $post_id; ?>" name="curriculum_status" class="form-control" <?php if($is_form_readonly) echo 'disabled'; ?>>
                                                    <option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
                                                    <option value="review" <?php selected( $status, 'review' ); ?>>Review</option>
                                                    <option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
                                                    <option value="published" <?php selected( $status, 'published' ); ?>>Published</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label for="curriculum_studio-<?php echo $post_id; ?>">Studio</label>
                                                <input type="hidden" name="curriculum_studio" value="<?php echo esc_attr($current_curriculum_studio_id); ?>">
                                                <input type="text" class="form-control" value="<?php echo esc_attr($studio_name); ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php
                                            // Generate curriculum URL using helper function
                                            $load_curriculum_link = courscribe_get_curriculum_url($post_id);
                                            error_log('CourScribe: Generated curriculum URL for ID ' . $post_id . ': ' . $load_curriculum_link);
                                            $url = add_query_arg(
                                                array(
                                                    'post_id' => $post_id,
                                                    'user_id' => get_current_user_id(), // or any dynamic variable
                                                    'view' => 'edit' // optional param
                                                ),
                                                $site_url . '/edit-curriculum/' . $post_id
                                            );
                                            ?>
                                             <a class="text-white text-sm font-weight-bold mb-0 icon-move-right" href="<?php echo esc_url($url); ?>">
                                                Load Curriculum <i class="fas fa-arrow-right text-sm ms-1" aria-hidden="true"></i>
                                            </a>
                                            <?php if (!$is_client && $can_edit_this_curriculum): ?>
                                            <div class="d-flex justify-content-end align-items-center gap-4">
                                                <button type="button" class="btn courscribe-stepper-prevBtn courscribe-save-curriculum" data-action="update" data-curriculum-id="<?php echo esc_attr($post_id); ?>"><span>Save Changes</span></button>
                                            </div>
                                            <?php endif ?>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <form  class="courscribe-curriculum-form">
                                        <?php wp_nonce_field( 'courscribe_curriculum', 'courscribe_curriculum_nonce' ); ?>
                                        <input type="hidden" name="curriculum_id" value="<?php echo esc_attr( $post_id ); ?>">
                                        <div class="row mb-3 mt-3" style="align-items: center;">
                                            <div class="col-6">
                                                <?php if ($is_client) : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <input class="courscribe-client-review-input" name="curriculums-client-review-input-[<?php echo esc_attr($post_id); ?>][curriculum_title]" placeholder="Enter new item here" type="text" value="<?php the_title(); ?>" id="courscribe-client-review-input-field" disabled>
                                                        <div class="courscribe-client-review-submit-button" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_title]" data-field-id="curriculum-title-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php the_title(); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="title" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel"><span>Give Title Feedback</span></div>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <label for="curriculum_title-<?php echo $post_id; ?>">Title</label>
                                                        <div class="curriculum-input-wrapper">
                                                            <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/title-icon.png" alt="Icon" class="curriculum-input-icon">
                                                            <input type="text" id="curriculum_title-<?php echo $post_id; ?>" name="curriculum_title" value="<?php the_title(); ?>" class="form-control" required <?php if($is_form_readonly) echo 'readonly'; ?> />
                                                        </div>
                                                        <?php if ($can_view_feedback && $feedback_count > 0) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_title]" data-field-id="curriculum-title-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php the_title(); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="title" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Title Feedback</span>
                                                                <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6">
                                                <?php if ($is_client) : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <input class="courscribe-client-review-input" name="curriculums-client-review-input-[<?php echo esc_attr($post_id); ?>][curriculum_topic]" placeholder="Enter new item here" type="text" value="<?php echo esc_attr( $topic ); ?>" id="courscribe-client-review-input-field" disabled>
                                                        <div class="courscribe-client-review-submit-button" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_topic]" data-field-id="curriculum-topic-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $topic ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="topic" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel"><span>Give Topic Feedback</span></div>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="courscribe-client-review-input-group">
                                                        <label for="curriculum_topic-<?php echo $post_id; ?>">Topic</label>
                                                        <div class="curriculum-input-wrapper">
                                                            <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/topic-icon.png" alt="Icon" class="curriculum-input-icon">
                                                            <input type="text" id="curriculum_topic-<?php echo $post_id; ?>" name="curriculum_topic" value="<?php echo esc_attr( $topic ); ?>" class="form-control" required <?php if($is_form_readonly) echo 'readonly'; ?> />
                                                        </div>
                                                        <?php if ($can_view_feedback && $feedback_count > 0) : ?>
                                                            <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_topic]" data-field-id="curriculum-topic-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $topic ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="topic" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                                                <span class="courscribe-client-review-end-adrnment-tooltip">View Topic Feedback</span>
                                                                <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <?php if ($is_client) : ?>
                                                <div class="courscribe-client-review-input-group">
                                                    <input class="courscribe-client-review-input" name="curriculums-client-review-input-[<?php echo esc_attr($post_id); ?>][curriculum_goal]" placeholder="Enter new item here" type="text" value="<?php echo esc_attr( $goal ); ?>" id="courscribe-client-review-input-field" disabled>
                                                    <div class="courscribe-client-review-submit-button" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_goal]" data-field-id="curriculum-goal-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $goal ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="goal" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel"><span>Give Goal Feedback</span></div>
                                                </div>
                                            <?php else : ?>
                                                <div class="courscribe-client-review-input-group">
                                                    <label for="curriculum_goal-<?php echo $post_id; ?>">Goal</label>
                                                    <div class="curriculum-input-wrapper">
                                                        <img src="<?= home_url(); ?>/wp-content/uploads/2024/12/goal-icon.png" alt="Icon" class="curriculum-input-icon">
                                                        <input type="text" id="curriculum_goal-<?php echo $post_id; ?>" name="curriculum_goal" value="<?php echo esc_attr( $goal ); ?>" class="form-control" <?php if($is_form_readonly) echo 'readonly'; ?> />
                                                    </div>
                                                    <?php if ($can_view_feedback && $feedback_count > 0) : ?>
                                                        <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-curriculum-id="<?php echo esc_attr($post_id); ?>" data-curriculum-title="<?php echo esc_attr(get_the_title($post_id)); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-name="curriculums[<?php echo esc_attr($post_id); ?>][curriculum_goal]" data-field-id="curriculum-goal-<?php echo esc_attr($post_id); ?>" data-post-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-current-field-value="<?php echo esc_attr( $goal ); ?>" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-post-type="crscribe_curriculum" data-field-type="goal" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                                            <span class="courscribe-client-review-end-adrnment-tooltip">View Goal Feedback</span>
                                                            <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="notes-<?php echo $post_id; ?>">Notes</label>
                                            <?php
                                            $editor_id = 'curriculum_notes-' . $post_id;
                                            $settings = array(
                                                'textarea_name' => 'curriculum_notes',
                                                'media_buttons' => false,
                                                'teeny' => true,
                                                'quicktags' => false,
                                                'textarea_rows' => 10,
                                                'editor_height' => 200,
                                                'editor_class' => $is_form_readonly ? 'courscribe-readonly-editor' : ''
                                            );
                                            wp_editor($notes, $editor_id, $settings);
                                            if ($is_form_readonly) {
                                                echo "<script>jQuery(document).ready(function($){ setTimeout(function() { $('#wp-{$editor_id}-wrap').addClass('disabled'); var editor = tinymce.get('{$editor_id}'); if(editor) editor.setMode('readonly'); }, 500); });</script>";
                                                echo "<style>#wp-{$editor_id}-wrap.disabled { pointer-events: none; opacity: 0.7; }</style>";
                                            }
                                            ?>
                                        </div>
                                        <div class="row mb-3 mt-3" style="align-items: center;">
                                            <div class="col-6">
                                                <label for="curriculum_status-<?php echo $post_id; ?>">Status</label>
                                                <select id="curriculum_status-<?php echo $post_id; ?>" name="curriculum_status" class="form-control" <?php if($is_form_readonly) echo 'disabled'; ?>>
                                                    <option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
                                                    <option value="review" <?php selected( $status, 'review' ); ?>>Review</option>
                                                    <option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
                                                    <option value="published" <?php selected( $status, 'published' ); ?>>Published</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label for="curriculum_studio-<?php echo $post_id; ?>">Studio</label>
                                                <input type="hidden" name="curriculum_studio" value="<?php echo esc_attr($current_curriculum_studio_id); ?>">
                                                <input type="text" class="form-control" value="<?php echo esc_attr($studio_name); ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php
                                            // Generate curriculum URL using helper function
                                            $load_curriculum_link = courscribe_get_curriculum_url($post_id);
                                            error_log('CourScribe: Generated curriculum URL for ID ' . $post_id . ': ' . $load_curriculum_link);
                                            $url = add_query_arg(
                                                array(
                                                    'post_id' => $post_id,
                                                    'user_id' => get_current_user_id(), // or any dynamic variable
                                                    'view' => 'edit' // optional param
                                                ),
                                                $site_url . '/edit-curriculum/' . $post_id
                                            );
                                            ?>
                                            <a class="text-white text-sm font-weight-bold mb-0 icon-move-right" href="<?php echo esc_url($url); ?>">
                                                Load Curriculum <i class="fas fa-arrow-right text-sm ms-1" aria-hidden="true"></i>
                                            </a>
                                            <?php if (!$is_client && $can_edit_this_curriculum): ?>
                                            <div class="d-flex justify-content-end align-items-center gap-4">
                                                <button type="button" class="btn courscribe-stepper-prevBtn courscribe-save-curriculum" data-action="update" data-curriculum-id="<?php echo esc_attr($post_id); ?>">
                                                    <span>Save Changes</span></button>
                                            </div>
                                            <?php endif ?>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="curriculum-4">
                                <?php if (!$is_client && ($is_studio_admin || $is_wp_admin || (get_post($post_id)->post_author == $current_user->ID))): ?>
                                <div class="courscribe-archive-form" data-action="archive" data-curriculum-id="<?php echo esc_attr($post_id); ?>">
                                    <?php wp_nonce_field('courscribe_archive_curriculum', 'courscribe_archive_nonce'); ?>
                                    <input type="hidden" name="curriculum_id" value="<?php echo esc_attr($post_id); ?>">
                                    <button 
                                    type="button" 
                                    class="card-action courscribe-action-button pbl-2" 
                                    data-action="archive" 
                                    data-curriculum-id="<?php echo esc_attr($post_id); ?>">
                                    <i class="fas fa-archive"></i> 
                                    <div class="tooltip">Archive Curriculum</div>
                               </button>
                                </div>
                                <button class="bin-button courscribe-action-button" data-action="delete" data-curriculum-id="<?php echo esc_attr($post_id); ?>">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 39 7"
                                    class="bin-top"
                                >
                                    <line stroke-width="4" stroke="white" y2="5" x2="39" y1="5"></line>
                                    <line
                                    stroke-width="3"
                                    stroke="white"
                                    y2="1.5"
                                    x2="26.0357"
                                    y1="1.5"
                                    x1="12"
                                    ></line>
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 33 39"
                                    class="bin-bottom"
                                >
                                    <mask fill="white" id="path-1-inside-1_8_19">
                                    <path
                                        d="M0 0H33V35C33 37.2091 31.2091 39 29 39H4C1.79086 39 0 37.2091 0 35V0Z"
                                    ></path>
                                    </mask>
                                    <path
                                    mask="url(#path-1-inside-1_8_19)"
                                    fill="white"
                                    d="M0 0H33H0ZM37 35C37 39.4183 33.4183 43 29 43H4C-0.418278 43 -4 39.4183 -4 35H4H29H37ZM4 43C-0.418278 43 -4 39.4183 -4 35V0H4V35V43ZM37 0V35C37 39.4183 33.4183 43 29 43V35V0H37Z"
                                    ></path>
                                    <path stroke-width="4" stroke="white" d="M12 6L12 29"></path>
                                    <path stroke-width="4" stroke="white" d="M21 6V29"></path>
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 89 80"
                                    class="garbage"
                                >
                                    <path
                                    fill="white"
                                    d="M20.5 10.5L37.5 15.5L42.5 11.5L51.5 12.5L68.75 0L72 11.5L79.5 12.5H88.5L87 22L68.75 31.5L75.5066 25L86 26L87 35.5L77.5 48L70.5 49.5L80 50L77.5 71.5L63.5 58.5L53.5 68.5L65.5 70.5L45.5 73L35.5 79.5L28 67L16 63L12 51.5L0 48L16 25L22.5 17L20.5 10.5Z"
                                    ></path>
                                </svg>
                                </button>

                                
                                <?php endif; ?>
                                <?php
                                $field_id_for_curriculum = 'curriculum-overall-' . $post_id;
                                if ($is_client) : ?>
                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment" data-post-id="<?php echo esc_attr($post_id); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-id="<?php echo esc_attr($field_id_for_curriculum); ?>" data-post-type="crscribe_curriculum" data-field-type="post" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                        <span class="courscribe-client-review-end-adrnment-tooltip">Give Curriculum Feedback</span>
                                        <span class="text">
                                            <svg fill="#665442" viewBox="0 0 24 24" height="30px" width="30px" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" d="M7.39999 6.32003L15.89 3.49003C19.7 2.22003 21.77 4.30003 20.51 8.11003L17.68 16.6C15.78 22.31 12.66 22.31 10.76 16.6L9.91999 14.08L7.39999 13.24C1.68999 11.34 1.68999 8.23003 7.39999 6.32003Z"></path>
                                                <path stroke-linejoin="round" stroke-linecap="round" stroke-width="1.5" d="M10.11 13.6501L13.69 10.0601"></path>
                                            </svg>
                                        </span>
                                    </div>
                                <?php elseif ($can_view_feedback && $feedback_count > 0) : ?>
                                    <div class="courscribe-client-review-end-adrnment-tooltip-container courscribe-feedback-adornment courscribe-view-feedback-btn" data-post-id="<?php echo esc_attr($post_id); ?>" data-post-title="<?php echo esc_attr($curriculum_title_for_data); ?>" data-field-id="<?php echo esc_attr($field_id_for_curriculum); ?>" data-post-type="crscribe_curriculum" data-field-type="post" data-user-id="<?php echo esc_attr($current_user->ID); ?>" data-user-name="<?php echo esc_attr($current_user->display_name); ?>" data-bs-toggle="offcanvas" data-bs-target="#courscribeManagerFeedbackOffcanvas" aria-controls="courscribeManagerFeedbackOffcanvasLabel">
                                        <span class="courscribe-client-review-end-adrnment-tooltip">View Curriculum Feedback</span>
                                        <span class="text"><?php echo esc_html($feedback_count); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endwhile;
            else :
                echo '<p>No curriculums found.</p>';
            endif;
            wp_reset_postdata();
            
            // Display archived curriculums in a redesigned section
            $archived_query = new WP_Query([
                'post_type' => 'crscribe_curriculum',
                'post_status' => ['publish', 'draft', 'pending', 'future'],
                'posts_per_page' => 10,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_curriculum_status',
                        'value' => 'archived',
                        'compare' => '!=',
                    ),
                ),
            ]);

            if ($archived_query->have_posts() && ($is_studio_admin || $is_wp_admin)) :
            ?>
            <div class="courscribe-archived-section card shadow-sm mt-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <div class="courscribe-archived-title">
                        <i class="fas fa-archive me-2"></i>
                        <span>Archived Curriculums</span>
                        <span class="courscribe-archived-count badge bg-secondary ms-2"><?php echo $archived_query->found_posts; ?></span>
                    </div>
                    <button class="btn btn-sm btn-outline-primary toggle-archived-btn">
                        <i class="fas fa-chevron-down"></i> Toggle
                    </button>
                </div>
                <div class="card-body courscribe-archived-content" style="display: none;">
                    <div class="courscribe-archived-info alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <p class="mb-0">Archived curriculums are hidden from the main view but can be restored or permanently deleted.</p>
                    </div>
                    <div class="curriculum-grid-container">
                        <?php while ($archived_query->have_posts()) : $archived_query->the_post();
                            $archived_id = get_the_ID();
                            $archived_title = get_post_meta($archived_id, '_title', true) ?: get_the_title();
                            $archived_topic = get_post_meta($archived_id, '_topic', true) ?: 'N/A';
                            $archived_goal = get_post_meta($archived_id, '_goal', true) ?: 'N/A';
                            $archived_studio_id = get_post_meta($archived_id, '_studio_id', true);
                            $archived_studio_name = $archived_studio_id ? get_the_title($archived_studio_id) : 'Unknown Studio';
                            $archived_date = get_the_date('M j, Y', $archived_id);
                        ?>
                        <div class="curriculum-box archived-curriculum-box card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="card-title"><?php echo esc_html($archived_title); ?></h5>
                                        <p class="card-text"><strong>Topic:</strong> <?php echo esc_html($archived_topic); ?></p>
                                        <p class="card-text"><strong>Goal:</strong> <?php echo esc_html($archived_goal); ?></p>
                                        <p class="card-text"><strong>Studio:</strong> <?php echo esc_html($archived_studio_name); ?></p>
                                        <p class="card-text"><strong>Archived:</strong> <?php echo esc_html($archived_date); ?></p>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center">
                                        <div class="courscribe-archived-actions w-100">
                                            <button type="button" 
                                                    class="btn btn-success btn-sm courscribe-unarchive-btn me-2" 
                                                    data-curriculum-id="<?php echo esc_attr($archived_id); ?>"
                                                    data-curriculum-title="<?php echo esc_attr($archived_title); ?>">
                                                <i class="fas fa-undo me-1"></i> Restore
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm courscribe-delete-archived-btn" 
                                                    data-action="delete"
                                                    data-curriculum-id="<?php echo esc_attr($archived_id); ?>">
                                                <i class="fas fa-trash me-1"></i> Delete Forever
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <script>
            class ArchivedCurriculumManager {
                constructor() {
                    this.initToggle();
                }

                initToggle() {
                    const toggleBtn = document.querySelector('.toggle-archived-btn');
                    const content = document.querySelector('.courscribe-archived-content');
                    const icon = toggleBtn.querySelector('i');

                    toggleBtn.addEventListener('click', () => {
                        const isHidden = content.style.display === 'none';
                        content.style.display = isHidden ? 'block' : 'none';
                        icon.classList.toggle('fa-chevron-down', !isHidden);
                        icon.classList.toggle('fa-chevron-up', isHidden);
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                new ArchivedCurriculumManager();
            });
            </script>
            <?php
            endif;
            wp_reset_postdata();
            ?>
            <?php
            // Change Log for Studio Admins and Collaborators
            if ( $is_studio_admin || current_user_can( 'administrator' ) || $is_collaborator ) :
                global $wpdb;
                
                // Get parameters for filtering and pagination
                $per_page = isset($_GET['changes_per_page']) ? max(3, min(50, intval($_GET['changes_per_page']))) : 3;
                $current_page = isset($_GET['changes_page']) ? max(1, intval($_GET['changes_page'])) : 1;
                $search_term = isset($_GET['changes_search']) ? sanitize_text_field($_GET['changes_search']) : '';
                $action_filter = isset($_GET['changes_action']) ? sanitize_text_field($_GET['changes_action']) : '';
                
                $offset = ($current_page - 1) * $per_page;
                
                // Build query with filters
                $where_conditions = [];
                $where_params = [];
                
                // Add studio ID filter
                $studio_ids = [];
                if ($is_collaborator && $user_studio_id) {
                    $studio_ids = [$user_studio_id];
                } elseif ($is_studio_admin) {
                    $admin_studios = get_posts(array(
                        'post_type' => 'crscribe_studio',
                        'post_status' => 'publish',
                        'author' => $current_user->ID,
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                    ));
                    $studio_ids = !empty($admin_studios) ? $admin_studios : [0];
                } elseif (current_user_can('administrator')) {
                    // Admins can see all studios, no need to filter by studio_id unless specific logic is required
                    $studio_ids = null;
                }
                
                if (!empty($studio_ids)) {
                    $placeholders = implode(',', array_fill(0, count($studio_ids), '%d'));
                    $where_conditions[] = "pm.meta_key = '_studio_id' AND pm.meta_value IN ($placeholders)";
                    $where_params = array_merge($where_params, $studio_ids);
                }
                
                if (!empty($search_term)) {
                    $where_conditions[] = "(c.post_title LIKE %s OR u.display_name LIKE %s)";
                    $where_params[] = '%' . $wpdb->esc_like($search_term) . '%';
                    $where_params[] = '%' . $wpdb->esc_like($search_term) . '%';
                }
                
                if (!empty($action_filter)) {
                    $where_conditions[] = "l.action = %s";
                    $where_params[] = $action_filter;
                }
                
                $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                
                // Get total count for pagination
                $count_query = "SELECT COUNT(*) 
                                FROM {$wpdb->prefix}courscribe_curriculum_log l 
                                LEFT JOIN {$wpdb->posts} c ON l.curriculum_id = c.ID 
                                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
                                LEFT JOIN {$wpdb->postmeta} pm ON c.ID = pm.post_id 
                                {$where_clause}";
                
                $total_logs = $wpdb->get_var($wpdb->prepare($count_query, ...$where_params));
                $total_pages = ceil($total_logs / $per_page);
                
                // Get logs with pagination and filters
                $logs_query = "SELECT l.*, c.post_title as curriculum_title, u.display_name as user_name 
                            FROM {$wpdb->prefix}courscribe_curriculum_log l 
                            LEFT JOIN {$wpdb->posts} c ON l.curriculum_id = c.ID 
                            LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
                            LEFT JOIN {$wpdb->postmeta} pm ON c.ID = pm.post_id 
                            {$where_clause} 
                            ORDER BY l.timestamp DESC 
                            LIMIT %d OFFSET %d";
                
                $logs = $wpdb->get_results($wpdb->prepare($logs_query, ...array_merge($where_params, [$per_page, $offset])));
                
                // Calculate stats (for all logs, not just current page)
                $stats_query = "SELECT l.* 
                                FROM {$wpdb->prefix}courscribe_curriculum_log l 
                                LEFT JOIN {$wpdb->posts} c ON l.curriculum_id = c.ID 
                                LEFT JOIN {$wpdb->postmeta} pm ON c.ID = pm.post_id 
                                {$where_clause} 
                                ORDER BY l.timestamp DESC 
                                LIMIT 50";
                
                $all_logs = $wpdb->get_results($wpdb->prepare($stats_query, ...$where_params));
                $total_changes = count($all_logs);
                $fields_modified = 0;
                $last_activity = $all_logs ? $all_logs[0]->timestamp : 'N/A';
                foreach ($all_logs as $log) {
                    $changes = json_decode($log->changes, true);
                    if (is_array($changes)) {
                        $fields_modified += count($changes);
                    }
                }
                
                // Get unique actions for filter dropdown
                $actions_query = "SELECT DISTINCT l.action 
                                FROM {$wpdb->prefix}courscribe_curriculum_log l 
                                LEFT JOIN {$wpdb->posts} c ON l.curriculum_id = c.ID 
                                LEFT JOIN {$wpdb->postmeta} pm ON c.ID = pm.post_id 
                                {$where_clause} 
                                ORDER BY l.action";
                
                $actions = $wpdb->get_col($wpdb->prepare($actions_query, ...$where_params));
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

                    <!-- Filters and Search -->
                    <div class="cs-changelog-filters">
                        <form method="GET" class="cs-filter-form">
                            <?php
                            // Preserve other URL parameters
                            foreach ($_GET as $key => $value) {
                                if (!in_array($key, ['changes_search', 'changes_action', 'changes_per_page', 'changes_page'])) {
                                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                                }
                            }
                            ?>
                            <div class="cs-filter-row">
                                <div class="cs-filter-group">
                                    <input type="text" name="changes_search" placeholder="Search by curriculum or user..." 
                                        value="<?php echo esc_attr($search_term); ?>" class="cs-search-input">
                                </div>
                                <div class="cs-filter-group">
                                    <select name="changes_action" class="cs-action-filter">
                                        <option value="">All Actions</option>
                                        <?php foreach ($actions as $action): ?>
                                            <option value="<?php echo esc_attr($action); ?>" <?php selected($action_filter, $action); ?>>
                                                <?php echo esc_html(ucfirst($action)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="cs-filter-group">
                                    <select name="changes_per_page" class="cs-per-page-filter">
                                        <option value="3" <?php selected($per_page, 3); ?>>3 per page</option>
                                        <option value="10" <?php selected($per_page, 10); ?>>10 per page</option>
                                        <option value="25" <?php selected($per_page, 25); ?>>25 per page</option>
                                        <option value="50" <?php selected($per_page, 50); ?>>50 per page</option>
                                    </select>
                                </div>
                                <div class="cs-filter-group">
                                    <button type="submit" class="cs-filter-btn">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <?php if ($search_term || $action_filter): ?>
                                        <a href="<?php echo esc_url(remove_query_arg(['changes_search', 'changes_action', 'changes_page'])); ?>" 
                                        class="cs-clear-btn">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($logs) : ?>
                    <div class="cs-changelog-list">
                        <?php foreach ($logs as $log) : 
                            $curriculum_title = $log->curriculum_title ? esc_html($log->curriculum_title) : 'Deleted';
                            $user_name = $log->user_name ? esc_html($log->user_name) : 'Unknown';
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
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="cs-pagination">
                            <div class="cs-pagination-info">
                                Showing <?php echo (($current_page - 1) * $per_page + 1); ?> to <?php echo min($current_page * $per_page, $total_logs); ?> of <?php echo $total_logs; ?> changes
                            </div>
                            <div class="cs-pagination-nav">
                                <?php if ($current_page > 1): ?>
                                    <a href="<?php echo esc_url(add_query_arg('changes_page', 1)); ?>" class="cs-page-btn">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg('changes_page', $current_page - 1)); ?>" class="cs-page-btn">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                for ($page = $start_page; $page <= $end_page; $page++):
                                ?>
                                    <a href="<?php echo esc_url(add_query_arg('changes_page', $page)); ?>" 
                                    class="cs-page-btn <?php echo $page === $current_page ? 'active' : ''; ?>">
                                        <?php echo $page; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="<?php echo esc_url(add_query_arg('changes_page', $current_page + 1)); ?>" class="cs-page-btn">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg('changes_page', $total_pages)); ?>" class="cs-page-btn">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php else : ?>
                        <div class="cs-no-changes">
                            <i class="fas fa-info-circle"></i>
                            <p>No recent changes found.</p>
                            <?php if ($search_term || $action_filter): ?>
                                <a href="<?php echo esc_url(remove_query_arg(['changes_search', 'changes_action', 'changes_page'])); ?>" 
                                class="cs-clear-filters-btn">Clear filters to view all changes</a>
                            <?php endif; ?>
                        </div>
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
            


            <!-- Premium Delete Confirmation Modal -->
            <div id="deleteConfirmationModal" class="courscribe-modal-overlay">
                <div class="courscribe-modal">
                    <div class="courscribe-modal-header">
                        <div class="courscribe-modal-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="courscribe-modal-title">Confirm Deletion</h3>
                    </div>
                    <div class="courscribe-modal-body">
                        <p class="courscribe-modal-message">
                            Are you sure you want to permanently delete this curriculum? This action cannot be undone and all associated data will be permanently removed.
                        </p>
                        <div class="courscribe-modal-curriculum-name" id="deleteCurriculumName">
                            <!-- Curriculum name will be populated by JavaScript -->
                        </div>
                        <form method="post" id="deleteCurriculumForm">
                            <?php wp_nonce_field( 'courscribe_delete_curriculum', 'courscribe_delete_nonce' ); ?>
                            <input type="hidden" name="curriculum_id" id="deleteCurriculumId">
                            <div class="courscribe-modal-actions">
                                <button type="button" class="courscribe-modal-btn courscribe-modal-btn-secondary" id="cancelDelete">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" name="courscribe_delete_curriculum" class="courscribe-modal-btn courscribe-modal-btn-danger">
                                    <i class="fas fa-trash"></i> Delete Forever
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Premium Archive Confirmation Modal -->
            <div id="archiveConfirmationModal" class="courscribe-modal-overlay">
                <div class="modal-dialog">
                    <div class="courscribe-modal">
                        <div class="courscribe-modal-header" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                            <div class="courscribe-modal-icon">
                                <i class="fas fa-archive"></i>
                            </div>
                            <h3 class="courscribe-modal-title">Archive Curriculum</h3>
                        </div>
                        <div class="courscribe-modal-body">
                            <p class="courscribe-modal-message">
                                Are you sure you want to archive this curriculum? Archived curriculums can be restored later but will be hidden from the main view.
                            </p>
                            <div class="courscribe-modal-curriculum-name" id="archiveCurriculumName" style="border-left-color: #ffc107;">
                                <!-- Curriculum name will be populated by JavaScript -->
                            </div>
                            <form method="post" id="archiveCurriculumForm">
                                <?php wp_nonce_field( 'courscribe_archive_curriculum', 'courscribe_archive_nonce' ); ?>
                                <input type="hidden" name="curriculum_id" id="archiveCurriculumId">
                                <div class="courscribe-modal-actions">
                                    <button type="button" class="courscribe-modal-btn courscribe-modal-btn-secondary" style="cursor: pointer;" id="cancelArchive">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <button type="submit" name="courscribe_archive_curriculum" class="courscribe-modal-btn" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3); cursor: pointer;">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Offcanvas -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="courscribeManagerFeedbackOffcanvas" style="min-width:720px!important" aria-labelledby="courscribeManagerFeedbackOffcanvasLabel">
                <div class="offcanvas-header" style="padding-top:35px; position: relative">
                    <h6 class="offcanvas-title" id="courscribeManagerFeedbackOffcanvasLabel">Curriculum Feedback</h6>
                    <span data-bs-dismiss="offcanvas" style="position: absolute; top: 124px; right: 20px; cursor: pointer; font-size: 40px;">×</span>
                </div>
                <div class="offcanvas-body p-0" id="courscribe-manager-feedback-container"></div>
            </div>

            <!-- JavaScript -->
            
            <script>
                jQuery(document).ready(function($) {

                    function showNotification(message) {
                        // Create notification element
                        const notification = document.createElement('div');
                        notification.style.cssText = `
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            color: white;
                            padding: 15px 25px;
                            border-radius: 10px;
                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                            z-index: 2000;
                            font-weight: bold;
                            transform: translateX(400px);
                            transition: transform 0.3s ease;
                        `;
                        notification.textContent = message;
                        
                        document.body.appendChild(notification);
                        
                        // Slide in
                        setTimeout(() => {
                            notification.style.transform = 'translateX(0)';
                        }, 100);
                        
                        // Slide out and remove
                        setTimeout(() => {
                            notification.style.transform = 'translateX(400px)';
                            setTimeout(() => {
                                document.body.removeChild(notification);
                            }, 300);
                        }, 3000);
                    }
                   
                    // Message display helper
                    function showMessage(message, type = 'success') {
                        const $messageDiv = $('<div>').addClass(`courscribe-${type}`).html(message);
                        $('.courscribe-curriculum-manager').prepend($messageDiv);
                        setTimeout(() => $messageDiv.fadeOut(500, () => $messageDiv.remove()), 5000);
                    }

                    // Disable button during AJAX
                    function toggleButton($button, disable = true) {
                        $button.prop('disabled', disable).css('opacity', disable ? 0.6 : 1);
                    }

                    // Check for duplicate curriculum (client-side)
                    function checkDuplicateCurriculum(title, studio_id, curriculum_id = 0) {
                        return $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            method: 'POST',
                            data: {
                                action: 'courscribe_check_duplicate_curriculum',
                                nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                                title: title,
                                studio_id: studio_id,
                                curriculum_id: curriculum_id
                            }
                        });
                    }

                    // Handle curriculum create/update
                    $('.courscribe-save-curriculum').on('click', function() {
                        const $button = $(this);
                        const $button_span = $button.find('span'); 
                        $button.addClass('saving')
                        $button_span.text('Saving...');
                        const $form = $button.closest('.courscribe-curriculum-form');
                        $form.css('opacity', 0.8);
                        const action = $button.data('action');
                        const curriculum_id = $form.find('input[name="curriculum_id"]').val();
                        const title = $form.find('input[name="curriculum_title"]').val().trim();
                        const topic = $form.find('input[name="curriculum_topic"]').val().trim();
                        const goal = $form.find('input[name="curriculum_goal"]').val().trim();
                        const notes = tinymce.get($form.find('.wp-editor-area').attr('id')).getContent();
                        const status = $form.find('select[name="curriculum_status"]').val();
                        const studio_id = $form.find('input[name="curriculum_studio"]').val();
                        const nonce = $form.find('input[name="courscribe_curriculum_nonce"]').val();

                        if (!title || !topic || !studio_id) {
                            showMessage('Please fill in all required fields.', 'error');
                            return;
                        }

                        toggleButton($button);

                        // Client-side duplicate check for create action
                        if (action === 'create') {
                            checkDuplicateCurriculum(title, studio_id).then(response => {
                                if (response.success && response.data.exists) {
                                    showMessage('A curriculum with this title already exists for this studio.', 'error');
                                    toggleButton($button, false);
                                    return;
                                }
                                submitCurriculum();
                            }).catch(() => {
                                showMessage('Error checking for duplicates.', 'error');
                                $button.removeClass('saving');
                                $button.addClass('saving-error');
                                setTimeout(() => {
                                    toggleButton($button, false);
                                }, 5000);
                                
                            });
                        } else {
                            try {
                                submitCurriculum();
                                $form.css('opacity', 1);
                            } catch (error) {
                                $button.removeClass('saving');
                                $button_span.text(error.message)
                                $form.css('opacity', 1);
                            }
                            
                        }

                        function submitCurriculum() {
                            $.ajax({
                                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                                method: 'POST',
                                data: {
                                    action: action === 'create' ? 'courscribe_create_curriculum' : 'courscribe_update_curriculum',
                                    nonce: nonce,
                                    curriculum_id: curriculum_id,
                                    title: title,
                                    topic: topic,
                                    goal: goal,
                                    notes: notes,
                                    status: status,
                                    studio_id: studio_id
                                },
                                success: function(response) {
                                    if (response.success) {
                                        showMessage(response.data.message);
                                        $button.removeClass('saving');
                                        $button_span.text('Update Successful! ✨');
                                        setTimeout(() => {
                                          $button_span.text('Save Changes');  
                                        }, 2500);
                                        
                                        if (action === 'create') {
                                            $('#createCurriculumForm').slideUp();
                                            // Optionally reload curriculum list or append new curriculum
                                            location.reload(); // Simple reload for now
                                        }
                                    } else {
                                        showMessage(response.data.message, 'error');
                                    }
                                    toggleButton($button, false);
                                },
                                error: function() {
                                    showMessage('Error processing request.', 'error');
                                    toggleButton($button, false);
                                }
                            });
                        }
                    });

                    // Handle archive and delete actions
                    $('.courscribe-action-button').on('click', function() {
                        const $button = $(this);
                        const action = $button.data('action');
                        
                        const curriculum_id = $button.data('curriculum-id');
                        const nonce = $button.closest('.courscribe-archive-form').find('input[name="courscribe_archive_nonce"]').val() || 
                                    $('#deleteCurriculumForm').find('input[name="courscribe_delete_nonce"]').val();

                        if (action === 'delete') {
                            const curriculumTitle = $button.closest('.curriculum-box').find('input[name="title"]').val() || 
                                                  $button.closest('.curriculum-box').find('.curriculum-title').text() ||
                                                  'Untitled Curriculum';
                            $('#deleteCurriculumId').val(curriculum_id);
                            $('#deleteCurriculumName').text(curriculumTitle);
                            $('#deleteConfirmationModal').css('display', 'flex');
                            return;
                        }
                        
                        if (action === 'archive') {
                            const curriculumTitle = $button.closest('.curriculum-box').find('input[name="title"]').val() || 
                                                  $button.closest('.curriculum-box').find('.curriculum-title').text() ||
                                                  'Untitled Curriculum';
                            $('#archiveCurriculumId').val(curriculum_id);
                            $('#archiveCurriculumName').text(curriculumTitle);
                            $('#archiveConfirmationModal').css('display', 'flex');
                            return;
                        }

                        toggleButton($button);

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            method: 'POST',
                            data: {
                                action: `courscribe_${action}_curriculum`,
                                nonce: nonce,
                                curriculum_id: curriculum_id
                            },
                            success: function(response) {
                                if (response.success) {
                                    showMessage(response.data.message);
                                    $button.closest('.curriculum-box').fadeOut(500, () => $(this).remove());
                                } else {
                                    showMessage(response.data.message, 'error');
                                } 
                                toggleButton($button, false);
                            },
                            error: function() {
                                showMessage('Error processing request.', 'error');
                                toggleButton($button, false);
                            }
                        });
                    });

                    // Delete confirmation
                    $('#deleteCurriculumForm').on('submit', function(e) {
                        e.preventDefault();
                        const $button = $(this).find('button[type="submit"]');
                        const curriculum_id = $('#deleteCurriculumId').val();
                        const nonce = $(this).find('input[name="courscribe_delete_nonce"]').val();

                        toggleButton($button);

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            method: 'POST',
                            data: {
                                action: 'courscribe_delete_curriculum',
                                nonce: nonce,
                                curriculum_id: curriculum_id
                            },
                            success: function(response) {
                                if (response.success) {
                                    showMessage(response.data.message);
                                    $(`.curriculum-box input[name="curriculum_id"][value="${curriculum_id}"]`).closest('.curriculum-box').fadeOut(500, () => $(this).remove());
                                    closeDeleteModal();
                                } else {
                                    showMessage(response.data.message, 'error');
                                }
                                toggleButton($button, false);
                            },
                            error: function() {
                                showMessage('Error deleting curriculum.', 'error');
                                toggleButton($button, false);
                            }
                        });
                    });

                    // Enhanced modal handling
                    $('#cancelDelete').on('click', function() {
                        closeDeleteModal();
                    });
                    
                    // Click outside modal to close
                    $('#deleteConfirmationModal').on('click', function(e) {
                        if (e.target === this) {
                            closeDeleteModal();
                        }
                    });
                    
                    // ESC key to close modal
                    $(document).on('keydown', function(e) {
                        if (e.key === 'Escape' && $('#deleteConfirmationModal').is(':visible')) {
                            closeDeleteModal();
                        }
                    });
                    
                    function closeDeleteModal() {
                        $('#deleteConfirmationModal').css('display', 'none');
                        $('#deleteCurriculumId').val('');
                        $('#deleteCurriculumName').text('');
                    }
                    
                    // Archive modal handlers
                    $('#cancelArchive').on('click', function() {
                        closeArchiveModal();
                    });
                    
                    // Click outside modal to close
                    $('#archiveConfirmationModal').on('click', function(e) {
                        if (e.target === this) {
                            closeArchiveModal();
                        }
                    });
                    
                    // ESC key to close archive modal
                    $(document).on('keydown', function(e) {
                        if (e.key === 'Escape' && $('#archiveConfirmationModal').is(':visible')) {
                            closeArchiveModal();
                        }
                    });
                    
                    function closeArchiveModal() {
                        $('#archiveConfirmationModal').css('display', 'none');
                        $('#archiveCurriculumId').val('');
                        $('#archiveCurriculumName').text('');
                    }
                    
                    // Handle archive form submission
                    $('#archiveCurriculumForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        const curriculum_id = $('#archiveCurriculumId').val();
                        const nonce = $('#archiveCurriculumForm').find('input[name="courscribe_archive_nonce"]').val();
                        
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            method: 'POST',
                            data: {
                                action: 'courscribe_archive_curriculum',
                                courscribe_archive_nonce: nonce,
                                curriculum_id: curriculum_id
                            },
                            success: function(response) {
                                if (response.success) {
                                    showMessage(response.data.message);
                                    $(`.curriculum-box input[name="curriculum_id"][value="${curriculum_id}"]`).closest('.curriculum-box').fadeOut(500);
                                    closeArchiveModal();
                                } else {
                                    showMessage(response.data.message, 'error');
                                }
                            },
                            error: function() {
                                showMessage('Error archiving curriculum.', 'error');
                            }
                        });
                    });
                    
                    // Handle unarchive functionality
                    $('.courscribe-unarchive-btn').on('click', function() {
                        const $button = $(this);
                        const curriculum_id = $button.data('curriculum-id');
                        const curriculum_title = $button.data('curriculum-title');
                        
                        if (confirm(`Are you sure you want to restore "${curriculum_title}"? It will be moved back to the active curriculums.`)) {
                            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Restoring...');
                            
                            $.ajax({
                                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                                method: 'POST',
                                data: {
                                    action: 'courscribe_unarchive_curriculum',
                                    curriculum_id: curriculum_id,
                                    nonce: '<?php echo wp_create_nonce("courscribe_unarchive"); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        showMessage('Curriculum restored successfully!');
                                        $button.closest('.archived-curriculum-box').fadeOut(500, function() {
                                            $(this).remove();
                                            // Update archived count
                                            const $count = $('.courscribe-archived-count');
                                            const currentCount = parseInt($count.text().replace(/[()]/g, ''));
                                            if (currentCount <= 1) {
                                                $('.courscribe-archived-section').fadeOut();
                                            } else {
                                                $count.text(`(${currentCount - 1})`);
                                            }
                                        });
                                    } else {
                                        showMessage(response.data || 'Error restoring curriculum.', 'error');
                                        $button.prop('disabled', false).html('<i class="fas fa-undo"></i> Restore');
                                    }
                                },
                                error: function() {
                                    showMessage('Error restoring curriculum.', 'error');
                                    $button.prop('disabled', false).html('<i class="fas fa-undo"></i> Restore');
                                }
                            });
                        }
                    });
                    
                    // Toggle archived section
                    window.toggleArchivedSection = function() {
                        const $content = $('.courscribe-archived-content');
                        const $toggle = $('.courscribe-archived-toggle');
                        
                        if ($content.is(':visible')) {
                            $content.slideUp(300);
                            $toggle.removeClass('expanded');
                        } else {
                            $content.slideDown(300);
                            $toggle.addClass('expanded');
                        }
                    };

                    // Initialize the help toggle button
                    $('.courscribe-help-toggle').on('click', function() {
                        if ($('#createCurriculumForm').is(':visible')) {
                            $('#createCurriculumForm').slideUp();
                        }
                        tg.start();
                    });

                    $('#addCurriculumBtn').on('click', function() {
                        $('#createCurriculumForm').slideToggle();
                    });

                    $('#cancelCurriculumBtn').on('click', function(e) {
                        e.preventDefault();
                        $('#createCurriculumForm').slideUp();
                    });

                    // Existing tour guide and feedback code remains unchanged
                    const tg = new tourguide.TourGuideClient({
                        progressBar: "#999",
                        exitOnEscape: true,
                        exitOnClickOutside: false,
                        closeButton: true,
                        completeOnFinish: false,
                        finishButton: '<button class="btn btn-secondary">Skip Tour</button>',
                        steps: [
                            {
                                title: "Welcome to Curriculum Creation (Step 1/8)",
                                content: "Let's create a new curriculum! Click the 'Add Curriculum' button to start.",
                                target: "#addCurriculumBtn",
                                order: 0,
                                beforeShow: () => document.querySelector("#addCurriculumBtn") ? true : false,
                                beforeLeave: () => {
                                    return new Promise((resolve) => {
                                        $('#createCurriculumForm').slideToggle();
                                        resolve(true);
                                    });
                                }
                            },
                            {
                                title: "Curriculum Title (Step 2/8)",
                                content: "Enter a clear, descriptive title that reflects the curriculum's focus, e.g., 'Introduction to Data Science'.",
                                target: "#curriculum_title",
                                order: 1,
                                beforeShow: () => document.querySelector("#curriculum_title") ? true : false
                            },
                            {
                                title: "Curriculum Topic (Step 3/8)",
                                content: "Specify the main subject, such as 'Python Programming' or 'Algebra Fundamentals'.",
                                target: "#curriculum_topic",
                                order: 2,
                                beforeShow: () => document.querySelector("#curriculum_topic") ? true : false
                            },
                            {
                                title: "Curriculum Goal (Step 4/8)",
                                content: "Define the learning outcome, e.g., 'Students will master basic Python syntax.' Use SMART goals.",
                                target: "#curriculum_goal",
                                order: 3,
                                beforeShow: () => document.querySelector("#curriculum_goal") ? true : false
                            },
                            {
                                title: "Curriculum Notes (Step 5/8)",
                                content: "Add detailed notes to outline key concepts or resources, e.g., textbooks or teaching methods.",
                                target: "#curriculum_notes_new-wrap",
                                order: 4,
                                beforeShow: () => document.querySelector("#curriculum_notes_new-wrap") ? true : false
                            },
                            {
                                title: "Curriculum Status (Step 6/8)",
                                content: "Choose the status: 'Draft' for initial work, 'Review' for feedback, or 'Approved' for finalized drafts.",
                                target: "#curriculum_status",
                                order: 5,
                                beforeShow: () => document.querySelector("#curriculum_status") ? true : false
                            },
                            {
                                title: "Associated Studio (Step 7/8)",
                                content: "This field shows the studio linked to your curriculum, pre-filled based on your settings.",
                                target: "input[name='curriculum_studio'] + input[disabled]",
                                order: 6,
                                beforeShow: () => document.querySelector("input[name='curriculum_studio'] + input[disabled]") ? true : false
                            },
                            {
                                title: "Save Your Work (Step 8/8)",
                                content: "Click 'Save Changes' to store your curriculum. Ensure all required fields are filled.",
                                target: "button.courscribe-save-curriculum[data-action='create']",
                                order: 7,
                                beforeShow: () => document.querySelector("button.courscribe-save-curriculum[data-action='create']") ? true : false
                            },
                            {
                                title: "Viewing Feedback (Optional)",
                                content: "Admins can view client and collaborator feedback here to refine the curriculum.",
                                target: ".courscribe-view-feedback-btn",
                                order: 8,
                                beforeShow: () => {
                                    const $feedbackBtn = $('.courscribe-view-feedback-btn').first();
                                    if ($feedbackBtn.length && $feedbackBtn.find('.text').text() > 0) {
                                        $feedbackBtn.removeClass('feedback-hidden').css('display', 'block');
                                        return true;
                                    }
                                    return false;
                                }
                            }
                        ]
                    });

                    // Check tour completion
                    $('.courscribe-help-toggle').on('click', debounce(function() {
                        if (localStorage.getItem('courscribeTourCompleted')) {
                            if (!confirm('You’ve completed the tour before. Want to take it again?')) {
                                return;
                            }
                        }
                        if ($('#createCurriculumForm').is(':visible')) {
                            $('#createCurriculumForm').slideUp();
                        }
                        tg.start().then(() => {
                            localStorage.setItem('courscribeTourCompleted', 'true');
                        });
                    }, 300));

                    // Existing feedback offcanvas code remains unchanged
                    let currentManagerAnnotorious = null;
                    $('#courscribeManagerFeedbackOffcanvas').on('show.bs.offcanvas', function(event) {
                        var button = $(event.relatedTarget);
                        var postId = button.data('post-id');
                        var postTitle = button.data('post-title');
                        var fieldId = button.data('field-id');
                        var postType = button.data('post-type');
                        var fieldType = button.data('field-type');
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
                                if ($timeline.find('.ai-input-container').length) return;

                                var selectedStatus = $('.courscribe-feedback-radio input[name="feedback-status-manager"]:checked').val().toLowerCase().replace(' ', '-');
                                var textField = `
                                    <div class="ai-input-container mb-3 mt-3 p-3">
                                        <div class="courscribe-feedback-status ${selectedStatus}" style="margin-bottom: 5px;">${selectedStatus.replace('-', ' ').toUpperCase()}</div>
                                        <textarea class="ai-input-field" id="manager-feedback-textbox" placeholder="Type your feedback..."></textarea>
                                        <div class="ai-input-buttons">
                                            <button class="ai-send-button" id="manager-feedback-save"><div class="ai-send-icon"></div></button>
                                            <button class="ai-cancel-button" id="manager-feedback-cancel">...</button>
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
                                            fetchAndRenderManagerFeedback();
                                            updateFeedbackCountOnButton(postId, postType, fieldId);
                                        } else { alert('Failed to save feedback: ' + response.data.message); }
                                    },
                                    error: function() { alert('AJAX error saving feedback.'); }
                                });
                            });

                            $offcanvasBody.off('click', '.courscribe-take-screenshot-btn-manager').on('click', '.courscribe-take-screenshot-btn-manager', function() {
                                const $curriculumBoxToScreenshot = $(`.curriculum-box form input[name="curriculum_id"][value="${postId}"]`).closest('.curriculum-box');
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
                                    fetchAndRenderManagerFeedback();
                                    alert('Failed to generate screenshot.');
                                });
                            });

                            $offcanvasBody.off('click', '.courscribe-feedback-screenshot').on('click', '.courscribe-feedback-screenshot', function() {
                                var screenshotUrl = $(this).data('screenshot-url');
                                var annotations = $(this).data('annotations') || [];
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
                                            if (currentManagerAnnotorious) currentManagerAnnotorious.destroy();
                                            currentManagerAnnotorious = null;
                                            fetchAndRenderManagerFeedback();
                                            updateFeedbackCountOnButton(postId, postType, fieldId);
                                        } else { alert('Failed to save annotated feedback: ' + response.data.message); }
                                    },
                                    error: function() { alert('AJAX error saving annotated feedback.'); }
                                });
                            });

                            $offcanvasBody.off('click', '.courscribe-cancel-manager-annotation-btn').on('click', '.courscribe-cancel-manager-annotation-btn', function() {
                                if (currentManagerAnnotorious) currentManagerAnnotorious.destroy();
                                currentManagerAnnotorious = null;
                                fetchAndRenderManagerFeedback();
                            });
                        }

                        $('.courscribe-feedback-radio input[name="feedback-status-manager"]').off('change').on('change', function() {
                            var selectedStatus = $(this).val();
                            $offcanvasBody.find('.courscribe-add-response-btn span').text(`Add ${selectedStatus} Response`);
                        });
                    });

                    function updateFeedbackCountOnButton(postId, postType, fieldId) {
                        var $button = $('.courscribe-view-feedback-btn[data-post-id="' + postId + '"][data-field-id="' + fieldId + '"]');
                        if (!$button.length) return;

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', method: 'POST',
                            data: {
                                action: 'courscribe_get_feedback_count',
                                nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                                post_id: postId, post_type: postType, field_id: fieldId
                            },
                            success: function(response) {
                                if (response.success) {
                                    $button.find('.text').text(response.data.count);
                                    if (response.data.count > 0) {
                                        $button.removeClass('feedback-hidden');
                                    } else {
                                        $button.addClass('feedback-hidden');
                                    }
                                }
                            }
                        });
                    }

                    if (!<?php echo json_encode($is_client); ?>) {
                        $('.courscribe-view-feedback-btn').each(function() {
                            var $btn = $(this);
                            updateFeedbackCountOnButton($btn.data('post-id'), $btn.data('post-type'), $btn.data('field-id'));
                        });
                    }
                });
            </script>
        </div>
    <?php
    return ob_get_clean();
}