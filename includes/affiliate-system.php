<?php
// CourScribe Affiliate System
// Complete affiliate tracking, commission management, and analytics
if (!defined('ABSPATH')) {
    exit;
}

// Create affiliate tables on plugin activation
register_activation_hook(__FILE__, 'courscribe_create_affiliate_tables');

function courscribe_create_affiliate_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Affiliate tracking table
    $affiliate_tracking_table = $wpdb->prefix . 'courscribe_affiliate_tracking';
    $sql1 = "CREATE TABLE $affiliate_tracking_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        affiliate_id int(11) NOT NULL,
        referral_code varchar(50) NOT NULL,
        click_id varchar(100) NOT NULL,
        ip_address varchar(45),
        user_agent text,
        referrer_url text,
        landing_page text,
        click_time datetime DEFAULT CURRENT_TIMESTAMP,
        converted tinyint(1) DEFAULT 0,
        conversion_time datetime NULL,
        conversion_value decimal(10,2) DEFAULT 0,
        commission_amount decimal(10,2) DEFAULT 0,
        commission_rate decimal(5,2) DEFAULT 0,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY affiliate_id (affiliate_id),
        KEY referral_code (referral_code),
        KEY click_time (click_time),
        KEY converted (converted)
    ) $charset_collate;";

    // Affiliate commissions table
    $affiliate_commissions_table = $wpdb->prefix . 'courscribe_affiliate_commissions';
    $sql2 = "CREATE TABLE $affiliate_commissions_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        affiliate_id int(11) NOT NULL,
        referral_id int(11),
        tracking_id int(11),
        customer_id int(11),
        order_id int(11),
        product_id int(11),
        commission_amount decimal(10,2) NOT NULL,
        commission_rate decimal(5,2) NOT NULL,
        order_total decimal(10,2),
        commission_type varchar(20) DEFAULT 'sale',
        status varchar(20) DEFAULT 'pending',
        paid_date datetime NULL,
        payout_id int(11) NULL,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY affiliate_id (affiliate_id),
        KEY customer_id (customer_id),
        KEY order_id (order_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Affiliate payouts table
    $affiliate_payouts_table = $wpdb->prefix . 'courscribe_affiliate_payouts';
    $sql3 = "CREATE TABLE $affiliate_payouts_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        affiliate_id int(11) NOT NULL,
        payout_amount decimal(10,2) NOT NULL,
        payout_method varchar(20) NOT NULL,
        payout_email varchar(100),
        payout_details text,
        status varchar(20) DEFAULT 'pending',
        requested_date datetime DEFAULT CURRENT_TIMESTAMP,
        processed_date datetime NULL,
        transaction_id varchar(100),
        fees decimal(10,2) DEFAULT 0,
        net_amount decimal(10,2),
        notes text,
        processed_by int(11) NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY affiliate_id (affiliate_id),
        KEY status (status),
        KEY requested_date (requested_date)
    ) $charset_collate;";

    // Affiliate referrals table
    $affiliate_referrals_table = $wpdb->prefix . 'courscribe_affiliate_referrals';
    $sql4 = "CREATE TABLE $affiliate_referrals_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        affiliate_id int(11) NOT NULL,
        referred_user_id int(11) NOT NULL,
        tracking_id int(11),
        referral_code varchar(50),
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        first_purchase_date datetime NULL,
        lifetime_value decimal(10,2) DEFAULT 0,
        total_commissions decimal(10,2) DEFAULT 0,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY affiliate_id (affiliate_id),
        KEY referred_user_id (referred_user_id),
        KEY referral_code (referral_code),
        UNIQUE KEY unique_referral (affiliate_id, referred_user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);

    error_log('CourScribe: Affiliate tables created successfully');
}

// Track affiliate clicks
function courscribe_track_affiliate_click($referral_code, $landing_page = '') {
    global $wpdb;
    
    if (empty($referral_code)) {
        return false;
    }
    
    // Get affiliate user by referral code
    $affiliate_id = courscribe_get_affiliate_by_code($referral_code);
    if (!$affiliate_id) {
        return false;
    }
    
    // Generate unique click ID
    $click_id = courscribe_generate_click_id();
    
    // Get visitor information
    $ip_address = courscribe_get_visitor_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer_url = $_SERVER['HTTP_REFERER'] ?? '';
    $landing_page = $landing_page ?: $_SERVER['REQUEST_URI'] ?? '';
    
    // Insert tracking record
    $tracking_table = $wpdb->prefix . 'courscribe_affiliate_tracking';
    $result = $wpdb->insert(
        $tracking_table,
        array(
            'affiliate_id' => $affiliate_id,
            'referral_code' => $referral_code,
            'click_id' => $click_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'referrer_url' => $referrer_url,
            'landing_page' => $landing_page,
            'click_time' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        // Set cookie for conversion tracking
        setcookie('courscribe_affiliate_click', $click_id, time() + (30 * 24 * 60 * 60), '/');
        
        // Update affiliate stats
        courscribe_update_affiliate_stats($affiliate_id, 'click');
        
        return $click_id;
    }
    
    return false;
}

// Track affiliate conversions
function courscribe_track_affiliate_conversion($user_id, $order_id, $order_total) {
    global $wpdb;
    
    // Check for affiliate click cookie
    $click_id = $_COOKIE['courscribe_affiliate_click'] ?? '';
    if (empty($click_id)) {
        return false;
    }
    
    // Get tracking record
    $tracking_table = $wpdb->prefix . 'courscribe_affiliate_tracking';
    $tracking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tracking_table WHERE click_id = %s AND converted = 0",
        $click_id
    ));
    
    if (!$tracking) {
        return false;
    }
    
    // Calculate commission
    $commission_rate = courscribe_get_affiliate_commission_rate($tracking->affiliate_id);
    $commission_amount = ($order_total * $commission_rate) / 100;
    
    // Update tracking record
    $wpdb->update(
        $tracking_table,
        array(
            'converted' => 1,
            'conversion_time' => current_time('mysql'),
            'conversion_value' => $order_total,
            'commission_amount' => $commission_amount,
            'commission_rate' => $commission_rate
        ),
        array('id' => $tracking->id),
        array('%d', '%s', '%f', '%f', '%f'),
        array('%d')
    );
    
    // Create commission record
    courscribe_create_commission_record(
        $tracking->affiliate_id,
        $user_id,
        $order_id,
        $commission_amount,
        $commission_rate,
        $order_total,
        $tracking->id
    );
    
    // Create referral record
    courscribe_create_referral_record($tracking->affiliate_id, $user_id, $tracking->id, $tracking->referral_code);
    
    // Update affiliate stats
    courscribe_update_affiliate_stats($tracking->affiliate_id, 'conversion', $commission_amount);
    
    // Clear cookie
    setcookie('courscribe_affiliate_click', '', time() - 3600, '/');
    
    return true;
}

// Create commission record
function courscribe_create_commission_record($affiliate_id, $customer_id, $order_id, $commission_amount, $commission_rate, $order_total, $tracking_id = null) {
    global $wpdb;
    
    $commissions_table = $wpdb->prefix . 'courscribe_affiliate_commissions';
    
    $result = $wpdb->insert(
        $commissions_table,
        array(
            'affiliate_id' => $affiliate_id,
            'tracking_id' => $tracking_id,
            'customer_id' => $customer_id,
            'order_id' => $order_id,
            'commission_amount' => $commission_amount,
            'commission_rate' => $commission_rate,
            'order_total' => $order_total,
            'commission_type' => 'sale',
            'status' => 'pending'
        ),
        array('%d', '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s')
    );
    
    if ($result) {
        // Send notification to affiliate
        courscribe_send_commission_notification($affiliate_id, $commission_amount);
    }
    
    return $result;
}

// Create referral record
function courscribe_create_referral_record($affiliate_id, $referred_user_id, $tracking_id, $referral_code) {
    global $wpdb;
    
    $referrals_table = $wpdb->prefix . 'courscribe_affiliate_referrals';
    
    // Check if referral already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $referrals_table WHERE affiliate_id = %d AND referred_user_id = %d",
        $affiliate_id,
        $referred_user_id
    ));
    
    if ($existing) {
        return $existing;
    }
    
    $result = $wpdb->insert(
        $referrals_table,
        array(
            'affiliate_id' => $affiliate_id,
            'referred_user_id' => $referred_user_id,
            'tracking_id' => $tracking_id,
            'referral_code' => $referral_code,
            'first_purchase_date' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );
    
    return $result;
}

// Get affiliate by code
function courscribe_get_affiliate_by_code($referral_code) {
    global $wpdb;
    
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_courscribe_affiliate_code' AND meta_value = %s",
        $referral_code
    ));
    
    return $user_id;
}

// Generate click ID
function courscribe_generate_click_id() {
    return 'click_' . time() . '_' . wp_generate_password(8, false);
}

// Get visitor IP
function courscribe_get_visitor_ip() {
    $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Get affiliate commission rate
function courscribe_get_affiliate_commission_rate($affiliate_id) {
    $rate = get_user_meta($affiliate_id, '_courscribe_commission_rate', true);
    return $rate ?: 30; // Default 30%
}

// Update affiliate stats
function courscribe_update_affiliate_stats($affiliate_id, $action, $amount = 0) {
    switch ($action) {
        case 'click':
            $current_clicks = get_user_meta($affiliate_id, '_courscribe_total_clicks', true) ?: 0;
            update_user_meta($affiliate_id, '_courscribe_total_clicks', $current_clicks + 1);
            break;
            
        case 'conversion':
            $current_conversions = get_user_meta($affiliate_id, '_courscribe_total_conversions', true) ?: 0;
            $current_earnings = get_user_meta($affiliate_id, '_courscribe_total_earnings', true) ?: 0;
            $current_pending = get_user_meta($affiliate_id, '_courscribe_pending_earnings', true) ?: 0;
            
            update_user_meta($affiliate_id, '_courscribe_total_conversions', $current_conversions + 1);
            update_user_meta($affiliate_id, '_courscribe_total_earnings', $current_earnings + $amount);
            update_user_meta($affiliate_id, '_courscribe_pending_earnings', $current_pending + $amount);
            break;
    }
}

// Send commission notification
function courscribe_send_commission_notification($affiliate_id, $commission_amount) {
    $user = get_userdata($affiliate_id);
    if (!$user) return;
    
    $subject = 'New Commission Earned - $' . number_format($commission_amount, 2);
    $message = "Congratulations! You've earned a new commission of $" . number_format($commission_amount, 2) . " from your CourScribe referral.";
    
    wp_mail($user->user_email, $subject, $message);
}

// AJAX Handlers
add_action('wp_ajax_courscribe_request_payout', 'courscribe_handle_payout_request');
add_action('wp_ajax_courscribe_generate_referral_link', 'courscribe_handle_generate_referral_link');

function courscribe_handle_payout_request() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_affiliate_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }
    
    $user_id = get_current_user_id();
    $amount = floatval($_POST['amount'] ?? 0);
    $method = sanitize_text_field($_POST['method'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    
    // Validation
    if ($amount < 50) {
        wp_send_json_error(['message' => 'Minimum payout amount is $50.']);
    }
    
    $pending_earnings = get_user_meta($user_id, '_courscribe_pending_earnings', true) ?: 0;
    if ($amount > $pending_earnings) {
        wp_send_json_error(['message' => 'Insufficient balance.']);
    }
    
    // Create payout request
    global $wpdb;
    $payouts_table = $wpdb->prefix . 'courscribe_affiliate_payouts';
    
    $payout_email = get_user_meta($user_id, '_courscribe_payout_email', true) ?: get_userdata($user_id)->user_email;
    
    $result = $wpdb->insert(
        $payouts_table,
        array(
            'affiliate_id' => $user_id,
            'payout_amount' => $amount,
            'payout_method' => $method,
            'payout_email' => $payout_email,
            'notes' => $notes,
            'status' => 'pending',
            'net_amount' => $amount // No fees for now
        ),
        array('%d', '%f', '%s', '%s', '%s', '%s', '%f')
    );
    
    if ($result) {
        // Update pending earnings
        update_user_meta($user_id, '_courscribe_pending_earnings', $pending_earnings - $amount);
        
        // Send admin notification
        courscribe_send_payout_admin_notification($user_id, $amount, $method);
        
        wp_send_json_success(['message' => 'Payout request submitted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to submit payout request.']);
    }
}

function courscribe_handle_generate_referral_link() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_affiliate_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }
    
    $user_id = get_current_user_id();
    $link_type = sanitize_text_field($_POST['link_type'] ?? 'pricing');
    $custom_params = sanitize_text_field($_POST['custom_params'] ?? '');
    
    // Get affiliate code
    $affiliate_code = get_user_meta($user_id, '_courscribe_affiliate_code', true);
    if (!$affiliate_code) {
        $affiliate_code = courscribe_generate_affiliate_code($user_id);
    }
    
    // Generate link based on type
    $base_url = home_url();
    switch ($link_type) {
        case 'pricing':
            $url = $base_url . '/select-tribe';
            break;
        case 'home':
            $url = $base_url;
            break;
        case 'register':
            $url = $base_url . '/courscribe-register';
            break;
        case 'demo':
            $url = $base_url . '/demo';
            break;
        default:
            $url = $base_url;
    }
    
    // Add affiliate parameter
    $referral_link = add_query_arg('ref', $affiliate_code, $url);
    
    // Add custom parameters if provided
    if (!empty($custom_params)) {
        $referral_link .= '&' . $custom_params;
    }
    
    wp_send_json_success([
        'link' => $referral_link,
        'affiliate_code' => $affiliate_code
    ]);
}

function courscribe_send_payout_admin_notification($affiliate_id, $amount, $method) {
    $user = get_userdata($affiliate_id);
    $admin_email = get_option('admin_email');
    
    $subject = 'New Affiliate Payout Request - $' . number_format($amount, 2);
    $message = "New payout request from affiliate: {$user->display_name} ({$user->user_email})\n";
    $message .= "Amount: $" . number_format($amount, 2) . "\n";
    $message .= "Method: {$method}\n";
    $message .= "Date: " . current_time('mysql') . "\n\n";
    $message .= "Review and process in the admin dashboard.";
    
    wp_mail($admin_email, $subject, $message);
}

// Hook into WooCommerce order completion for commission tracking
add_action('woocommerce_order_status_completed', 'courscribe_track_woocommerce_conversion');
add_action('woocommerce_order_status_processing', 'courscribe_track_woocommerce_conversion');

function courscribe_track_woocommerce_conversion($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    $user_id = $order->get_user_id();
    $order_total = $order->get_total();
    
    courscribe_track_affiliate_conversion($user_id, $order_id, $order_total);
}

// Hook into WordPress registration for click tracking
add_action('wp_login', 'courscribe_check_affiliate_referral', 10, 2);

function courscribe_check_affiliate_referral($user_login, $user) {
    // Check if user came from affiliate link
    if (isset($_GET['ref'])) {
        $referral_code = sanitize_text_field($_GET['ref']);
        courscribe_track_affiliate_click($referral_code);
    }
}

// Add affiliate code to user registration
add_action('user_register', 'courscribe_handle_affiliate_registration');

function courscribe_handle_affiliate_registration($user_id) {
    // Check for affiliate click cookie
    $click_id = $_COOKIE['courscribe_affiliate_click'] ?? '';
    if (!empty($click_id)) {
        // Store affiliate association for later conversion tracking
        update_user_meta($user_id, '_courscribe_affiliate_click_id', $click_id);
    }
}

// Enable affiliate status for user
function courscribe_enable_affiliate($user_id, $commission_rate = 30) {
    update_user_meta($user_id, '_courscribe_affiliate_enabled', true);
    update_user_meta($user_id, '_courscribe_commission_rate', $commission_rate);
    
    // Generate affiliate code if not exists
    $affiliate_code = get_user_meta($user_id, '_courscribe_affiliate_code', true);
    if (!$affiliate_code) {
        courscribe_generate_affiliate_code($user_id);
    }
    
    // Initialize stats
    update_user_meta($user_id, '_courscribe_total_clicks', 0);
    update_user_meta($user_id, '_courscribe_total_conversions', 0);
    update_user_meta($user_id, '_courscribe_total_earnings', 0);
    update_user_meta($user_id, '_courscribe_pending_earnings', 0);
    update_user_meta($user_id, '_courscribe_total_referrals', 0);
}

// Generate affiliate code function
function courscribe_generate_affiliate_code($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $code = strtoupper(substr($user->user_login, 0, 4) . substr(md5($user_id . time()), 0, 4));
    
    // Ensure uniqueness
    while (courscribe_get_affiliate_by_code($code)) {
        $code = strtoupper(substr($user->user_login, 0, 4) . substr(md5($user_id . time() . rand()), 0, 4));
    }
    
    update_user_meta($user_id, '_courscribe_affiliate_code', $code);
    return $code;
}
?>