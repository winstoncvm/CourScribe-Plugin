<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode for tribe selection (Legacy - use courscribe_premium_pricing instead)
add_shortcode( 'courscribe_select_tribe', 'courscribe_select_tribe_shortcode' );

// Redirect to premium pricing shortcode
add_shortcode( 'courscribe_select_tribe_premium', 'courscribe_redirect_to_premium_pricing' );
function courscribe_redirect_to_premium_pricing() {
    return do_shortcode('[courscribe_premium_pricing]');
}

function courscribe_select_tribe_shortcode() {
    $site_url = home_url();
    $user_id = get_current_user_id();
    $tribe_selected = get_user_meta($user_id, '_courscribe_tribe_selected', true);
    $current_tier = 'basics'; // Default to basics
    $current_sku = '';
    $subscription_data = [];
    $has_active_subscription = false;

    // Debugging: Log user ID
    error_log('User ID: ' . $user_id);
    error_log('Checking subscriptions for user...');

    // Get active subscription for the current user
    if (is_user_logged_in()) {
        error_log('Checking orders for subscription products...');
        
        $subscription_orders = wc_get_orders([
            'limit' => 1,
            'status' => ['completed', 'processing', 'on-hold', 'wc-wps_renewal'],
            'type' => 'shop_order',
            'customer_id' => $user_id,
            'orderby' => 'date',
            'order' => 'DESC', // Get most recent order first
        ]);
        
        foreach ($subscription_orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes') {
                    $current_sku = $product->get_sku();
                    $product_name = strtolower($product->get_name());
                    $subscription_status = $order->get_meta('wps_subscription_status') ?: 'active';
                    
                    error_log('Order ID: ' . $order->get_id());
                    error_log('Order Product SKU: ' . $current_sku);
                    error_log('Order Product Name: ' . $product_name);
                    error_log('Subscription Status: ' . $subscription_status);
                    
                    // Determine if subscription is active
                    $has_active_subscription = in_array($subscription_status, ['active', 'pending-cancel']);
                    
                    // Map product to tier
                    if (strpos($product_name, 'pro') !== false) {
                        $current_tier = 'pro';
                    } elseif (strpos($product_name, 'plus') !== false || strpos($product_name, '+') !== false) {
                        $current_tier = 'plus';
                    } else {
                        $current_tier = 'basics';
                    }
                    
                    break 2; // Exit both loops
                }
            }
        }
        
        if (!$has_active_subscription) {
            error_log('No active subscription found in orders.');
        }
    } else {
        error_log('User not logged in.');
    }

    error_log('Determined Current Tier: ' . $current_tier);
    error_log('Current SKU: ' . $current_sku);

    // Fetch WooCommerce products
    $products = [];
    $skus = [
        'basics' => 'COURSCRIBE-BASICS',
        'plus-monthly' => 'COURSCRIBE-PLUS-MONTHLY',
        'plus-yearly' => 'COURSCRIBE-PLUS-YEARLY',
        'pro-monthly' => 'COURSCRIBE-PRO-MONTHLY',
        'pro-yearly' => 'COURSCRIBE-PRO-YEARLY',
    ];

    foreach ($skus as $key => $sku) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $features = [];
                $attributes = $product->get_attributes();
                if (isset($attributes['features'])) {
                    $features = explode(', ', $attributes['features']->get_options()[0]);
                }
                
                $price_raw = $product->get_price();
                $suffix = '';
                
                if ($price_raw) {
                    if ($key !== 'basics') {
                        $suffix = strpos($key, 'monthly') !== false ? '/month' : '/year';
                    }
                    $price_formatted = wc_price($price_raw, [
                        'decimals' => (floor($price_raw) == $price_raw) ? 0 : 2,
                    ]);
                } else {
                    $price_formatted = 'Free';
                }

                $products[$key] = [
                    'name' => $product->get_name(),
                    'price' => $price_formatted,
                    'price_suffix' => $suffix,
                    'description' => $product->get_description(),
                    'short_description' => $product->get_short_description(),
                    'features' => $features,
                    'permalink' => $product->get_permalink(),
                    'sku' => $sku,
                    'product_id' => $product_id,
                ];
            }
        }
    }

    // Determine button states based on current tier and SKU
    $button_states = [
        'basics' => ['enabled' => true, 'text' => 'Select Plan'],
        'plus-monthly' => ['enabled' => true, 'text' => 'Select Plan'],
        'plus-yearly' => ['enabled' => true, 'text' => 'Select Plan'],
        'pro-monthly' => ['enabled' => true, 'text' => 'Select Plan'],
        'pro-yearly' => ['enabled' => true, 'text' => 'Select Plan'],
    ];

    // If user has an active subscription
    if (is_user_logged_in() && $has_active_subscription) {
        error_log('User has active subscription - configuring button states');
        
        // Basics should always be disabled if user has any paid subscription
        $button_states['basics'] = ['enabled' => false, 'text' => 'Current Plan: Basics'];
        
        switch ($current_tier) {
            case 'plus':
                if ($current_sku === 'COURSCRIBE-PLUS-MONTHLY') {
                    $button_states['plus-monthly'] = ['enabled' => false, 'text' => 'Current Plan'];
                    $button_states['plus-yearly'] = ['enabled' => true, 'text' => 'Upgrade to Yearly'];
                    $button_states['pro-monthly'] = ['enabled' => true, 'text' => 'Upgrade to Pro'];
                    $button_states['pro-yearly'] = ['enabled' => true, 'text' => 'Upgrade to Pro'];
                } elseif ($current_sku === 'COURSCRIBE-PLUS-YEARLY') {
                    $button_states['plus-yearly'] = ['enabled' => false, 'text' => 'Current Plan'];
                    $button_states['pro-monthly'] = ['enabled' => true, 'text' => 'Upgrade to Pro'];
                    $button_states['pro-yearly'] = ['enabled' => true, 'text' => 'Upgrade to Pro'];
                }
                break;
                
            case 'pro':
                if ($current_sku === 'COURSCRIBE-PRO-MONTHLY') {
                    $button_states['pro-monthly'] = ['enabled' => false, 'text' => 'Current Plan'];
                    $button_states['pro-yearly'] = ['enabled' => true, 'text' => 'Upgrade to Yearly'];
                } elseif ($current_sku === 'COURSCRIBE-PRO-YEARLY') {
                    $button_states['pro-yearly'] = ['enabled' => false, 'text' => 'Current Plan'];
                }
                break;
        }
    } elseif (is_user_logged_in()) {
        // User is logged in but has no active subscription (basics)
        $button_states['basics'] = ['enabled' => false, 'text' => 'Current Plan'];
    }

    error_log('Final Button States: ' . print_r($button_states, true));
 
    ob_start();
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

    <!-- Scripts -->
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/chartjs.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <div class="courscribe-main-content position-relative border-radius-lg">
        <div class="py-4 px-0 courscribe-div-center-column w-100">
            <div class="form-container-one">
                <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;">
                <h3 class="courscribe-heading">
                    Welcome to the savvy side of course creation.<br>
                    Select your
                    <span>Premier Plan.</span>
                </h3>
                <p class="courscribe-pricing-subheading">First try CourScribe free for 7 days. <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/courscribe-register' ) ); ?>" class="">Sign Up</a></p>
                <p class="courscribe-pricing-subtitle">Powerful tool. Premium plans.</p>
                <div class="courscribe-pricing-table">
                    <div class="pricing-toggle">
                        <button class="toggle-button active" data-period="monthly">Monthly</button>
                        <button class="toggle-button" data-period="yearly">Yearly (Save ~17%)</button>
                    </div>
                    <div class="courscribe-tribe-options">
                        <?php if ( isset( $products['basics'] ) ) :
                            $product = $products['basics'];
                            $state = $button_states['basics'];
                            ?>
                            <div class="tribe-option">
                                <div class="tribe-option-header">
                                    
                                    <div class="tribe-options-heading-items">
                                        <h3><?php echo esc_html( $product['name'] ); ?></h3>
                                        <div class="short-description"><?php echo wp_kses_post( $product['short_description'] ); ?></div>
                                        <div class="price">
                                            <?php echo wp_kses_post( $product['price'] ); ?>
                                            <?php if ( ! empty( $product['price_suffix'] ) ) : ?>
                                                <span class="price-suffix"><?php echo esc_html( $product['price_suffix'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <ul class="features">
                                    <?php foreach ( $product['features'] as $feature ) : ?>
                                        <li><?php echo esc_html( $feature ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="#" class="button<?php echo $state['enabled'] ? '' : ' disabled'; ?>" data-plan="basics" data-product-id="<?php echo esc_attr( $product['product_id'] ); ?>">
                                    <?php echo esc_html( $state['text'] ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $products['plus-monthly'] ) && isset( $products['plus-yearly'] ) ) :
                            $product_monthly = $products['plus-monthly'];
                            $product_yearly = $products['plus-yearly'];
                            $state_monthly = $button_states['plus-monthly'];
                            $state_yearly = $button_states['plus-yearly'];
                            ?>
                            <div class="tribe-option plus-option">
                                <div class="tribe-option-header">
                                   
                                    <div class="tribe-options-heading-items">
                                        <h3><?php echo esc_html( str_replace( ' (Monthly)', '', $product_monthly['name'] ) ); ?></h3>
                                        <div class="short-description monthly-short-desc"><?php echo wp_kses_post( $product_monthly['short_description'] ); ?></div>
                                        <div class="short-description yearly-short-desc" style="display: none;"><?php echo wp_kses_post( $product_yearly['short_description'] ); ?></div>
                                        <div class="price monthly-price">
                                            <?php echo wp_kses_post( $product_monthly['price'] ); ?>
                                            <?php if ( ! empty( $product_monthly['price_suffix'] ) ) : ?>
                                                <span class="price-suffix" style="color: white;"><?php echo esc_html( $product_monthly['price_suffix'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="price yearly-price" style="display: none;">
                                            <?php echo wp_kses_post( $product_yearly['price'] ); ?>
                                            <?php if ( ! empty( $product_yearly['price_suffix'] ) ) : ?>
                                                <span class="price-suffix"><?php echo esc_html( $product_yearly['price_suffix'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <ul class="features">
                                    <?php foreach ( $product_monthly['features'] as $feature ) : ?>
                                        <li><?php echo esc_html( $feature ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="#" class="button monthly-button<?php echo $state_monthly['enabled'] ? '' : ' disabled'; ?>" data-plan="plus-monthly" data-product-id="<?php echo esc_attr( $product_monthly['product_id'] ); ?>">
                                    <?php echo esc_html( $state_monthly['text'] ); ?>
                                </a>
                                <a href="#" class="button yearly-button<?php echo $state_yearly['enabled'] ? '' : ' disabled'; ?>" style="display: none;" data-plan="plus-yearly" data-product-id="<?php echo esc_attr( $product_yearly['product_id'] ); ?>">
                                    <?php echo esc_html( $state_yearly['text'] ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $products['pro-monthly'] ) && isset( $products['pro-yearly'] ) ) :
                            $product_monthly = $products['pro-monthly'];
                            $product_yearly = $products['pro-yearly'];
                            $state_monthly = $button_states['pro-monthly'];
                            $state_yearly = $button_states['pro-yearly'];
                            ?>
                            <div class="tribe-option pro-option">
                                <div class="tribe-option-header">
                                   
                                    <div class="tribe-options-heading-items">
                                        <h3><?php echo esc_html( str_replace( ' (Monthly)', '', $product_monthly['name'] ) ); ?></h3>
                                        <div class="short-description monthly-short-desc"><?php echo wp_kses_post( $product_monthly['short_description'] ); ?></div>
                                        <div class="short-description yearly-short-desc" style="display: none;"><?php echo wp_kses_post( $product_yearly['short_description'] ); ?></div>
                                        <div class="price monthly-price">
                                            <?php echo wp_kses_post( $product_monthly['price'] ); ?>
                                            <?php if ( ! empty( $product_monthly['price_suffix'] ) ) : ?>
                                                <span class="price-suffix"><?php echo esc_html( $product_monthly['price_suffix'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="price yearly-price" style="display: none;">
                                            <?php echo wp_kses_post( $product_yearly['price'] ); ?>
                                            <?php if ( ! empty( $product_yearly['price_suffix'] ) ) : ?>
                                                <span class="price-suffix"><?php echo esc_html( $product_yearly['price_suffix'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <ul class="features">
                                    <?php foreach ( $product_monthly['features'] as $feature ) : ?>
                                        <li><?php echo esc_html( $feature ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="#" class="button monthly-button<?php echo $state_monthly['enabled'] ? '' : ' disabled'; ?>" data-plan="pro-monthly" data-product-id="<?php echo esc_attr( $product_monthly['product_id'] ); ?>">
                                    <?php echo esc_html( $state_monthly['text'] ); ?>
                                </a>
                                <a href="#" class="button yearly-button<?php echo $state_yearly['enabled'] ? '' : ' disabled'; ?>" style="display: none;" data-plan="pro-yearly" data-product-id="<?php echo esc_attr( $product_yearly['product_id'] ); ?>">
                                    <?php echo esc_html( $state_yearly['text'] ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for registration/login -->
    <div id="auth-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="auth-modal-content">
            <span id="close-modal" style="position: absolute; top: 14px; right: 20px; cursor: pointer; font-size: 40px;">×</span>
            <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
            <h3 class="courscribe-heading">Reclaim your time!<br>
                Get started with <span>CourScribe.</span></h3>
            <p class="courscribe-subheading">Create a new account or login to capture your genius and create your education empire.</p>
            <div class="center">
                <label id="auth-tabs" for="filter" class="auth-modal-switch" aria-label="Toggle Filter">
                    <input type="checkbox" id="filter" />
                    <span class="tab-button" data-tab="login">Sign In</span>
                    <span class="tab-button active" data-tab="register">Register</span>
                </label>
            </div>

            <div id="register-tab" class="tab-content">
                <form id="modal-register-form">
                    <?php wp_nonce_field( 'courscribe_register', 'courscribe_register_nonce' ); ?>
                    <input type="text" name="courscribe_username" placeholder="Username" required>
                    <input type="email" name="courscribe_email" placeholder="Email" required>
                    <input type="password" name="courscribe_password" placeholder="Password" required>
                    <input type="password" name="courscribe_password_confirm" placeholder="Confirm Password" required>
                    <button type="submit" class="btn">Register</button>
                </form>
            </div>
            <div id="login-tab" class="tab-content" style="display: none;">
                <form id="modal-login-form">
                    <?php wp_nonce_field( 'courscribe_signin', 'courscribe_signin_nonce' ); ?>
                    <input type="email" name="courscribe_email" placeholder="Email" required>
                    <input type="password" name="courscribe_password" placeholder="Password" required>
                    <button type="submit" class="btn">Log In</button>
                </form>
            </div>
            <div id="auth-message" style="color: red; margin-top: 10px;"></div>
        </div>
    </div>

    <style>
        .courscribe-pricing-table { width: 100% !important; min-width: 920px; height: 100%; margin: 0; padding: 20px; text-align: center; }
        .courscribe-pricing-table h2 { margin-bottom: 10px; font-size: 2em; }
        .courscribe-pricing-table p { margin-bottom: 20px; color: #555; }
        .pricing-toggle { margin-bottom: 30px; }
        .toggle-button { background: #f0f0f0; border: 1px solid #ddd; padding: 10px 20px; cursor: pointer; font-size: 1em; }
        .toggle-button.active { background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%); color: white; border-color: rgba(239, 67, 57, 0.2); }
        .toggle-button:first-child { border-radius: 4px 0 0 4px; }
        .toggle-button:last-child { border-radius: 0 4px 4px 0; }
        .courscribe-tribe-options {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between; /* or center, depending on layout */
            gap: 20px;
            padding: 20px 0;
            width: 100%;
        }

        .tribe-option {
            flex: 1 1 calc(33.333% - 20px); /* one-third minus the gap */
            padding: 20px;
            transition: transform 0.2s ease;
            background: #2F2E30;
            box-shadow: 0px 4px 44px rgba(0, 0, 0, 0.13);
            border-radius: 21px;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            position: relative;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            overflow: hidden; /* Prevent image from overflowing the card */
        }
        

       

        /* ::before pseudo-element to display bg image at the top */
        .tribe-option::before {
            content: "";
            position: absolute;
            top: -100px;
            left: 0;
            width: 100%;
            height: 380px; /* adjust this to control height of the top image area */
            background-image: url('http://courscribe-divi.local/wp-content/uploads/2025/04/Mask-Group.png');
            background-size: cover;
            background-position: top center;
            background-repeat: no-repeat;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            z-index: 1;
        }

        /* Ensure card content appears above the background image */
        .tribe-option > * {
            position: relative;
            z-index: 2;
        }

        

        .tribe-option.popular {
            border-color: #E4B26F;
            box-shadow: 0 20px 40px rgba(228, 178, 111, 0.2);
            transform: scale(1.05);
        }

        .tribe-option.popular:hover {
            transform: scale(1.05) translateY(-8px);
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(45deg, #E4B26F, #F8923E);
            color: #1a1a1a;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }


        @media (max-width: 900px) {
            .tribe-option {
                flex: 1 1 calc(50% - 20px); /* two columns on smaller screens */
            }
        }

        @media (max-width: 600px) {
            .tribe-option {
                flex: 1 1 100%; /* full width on small devices */
            }
        }

        .tribe-option:hover {
            transform: translateY(-5px);
        }

        .tribe-option-header {
            margin-bottom: 60px;
        }

        .tribe-option-header img {
            opacity: 0.1;
            box-shadow: 0px 4px 44px rgba(0, 0, 0, 0.13);
            background: #FCE2E1;
            border-radius: 116.5px;
            transform: matrix(0.98, -0.19, 0.2, 0.98, 0, 0);
            position: absolute;
            top: -80px;
            left: 0;
            object-fit: cover;
        }

        .tribe-options-heading-items {
            z-index: 1;
        }
        .tribe-option h3 { margin: 0 0 6px; font-family: 'Open Sans'; font-style: normal; font-weight: 600; font-size: 26px; line-height: 46px; text-align: center; color: #FFFFFF; }
        .tribe-option .price { margin: 4px 0; font-family: 'Open Sans'; font-style: normal; font-weight: 700; font-size: 50px; line-height: 46px; background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; display: inline-block; }
        .price-suffix { font-family: 'Open Sans'; font-style: normal; font-weight: 600; font-size: 19px; line-height: 46px; text-align: center; color: #FFFFFF !important; opacity: 1; margin-left: 5px; display: inline-block; vertical-align: top; }
        .tribe-option .short-description { margin-bottom: 10px; font-family: 'Open Sans'; font-style: normal; font-weight: 400; font-size: 14px; line-height: 26px; text-align: center; color: #FFFFFF; padding-inline:20px; }
        .tribe-option .features { list-style: none !important; padding: 0; text-align: left; margin-top: 60px; }
        .tribe-option .features li { position: relative; padding-left: 28px; margin-bottom: 12px; line-height: 1.5; }
        .tribe-option .features li::before { content: ''; position: absolute; left: 0; top: 7px; width: 12px; height: 12px; background: #F15538; border-radius: 50%; }
        .tribe-option .features li { padding-left: 20px; font-family: 'Open Sans'; font-style: normal; font-weight: 600; font-size: 16px; color: #FFFFFF; }
        .tribe-option .features li:last-child { border-bottom: none; }
        .tribe-option .button { display: inline-block; color: #231F20; padding: 12px 25px; text-decoration: none; font-size: 1em; background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); border-radius: 26px; }
        .tribe-option .button:hover:not(.disabled) { background: #E4B26F; }
        .tribe-option .button.disabled { background: #ccc; cursor: not-allowed; pointer-events: none; }
        @media (max-width: 768px) { .courscribe-tribe-options { flex-direction: column; align-items: center; } .courscribe-pricing-table { min-width: unset; } }
        
        .tab-content { margin-top: 20px; }
        .tab-content input { display: block; width: 100%; margin-bottom: 10px; padding: 8px; }
        .btn { background: #FBC275; color: #2F2E30; padding: 10px; border: none; cursor: pointer; width: 100%; }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
            const selectPlanButtons = document.querySelectorAll('.button');
            const modal = document.getElementById('auth-modal');
            const closeModal = document.getElementById('close-modal');
            let selectedProductId = '';

            // Handle "Select Plan" button clicks
            selectPlanButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    selectedProductId = this.getAttribute('data-product-id');
                    
                    if (isLoggedIn) {
                        // For logged-in users, add to cart and redirect to checkout
                        jQuery.ajax({
                            url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                            type: 'POST',
                            data: {
                                action: 'courscribe_add_to_cart',
                                product_id: selectedProductId,
                                nonce: '<?php echo wp_create_nonce( 'courscribe_add_to_cart_nonce' ); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    window.location.href = response.data.checkout_url;
                                } else {
                                    alert(response.data.message || 'Error adding product to cart.');
                                }
                            },
                            error: function() {
                                alert('An error occurred. Please try again.');
                            }
                        });
                    } else {
                        // For non-logged-in users, show modal
                        modal.style.display = 'block';
                    }
                });
            });

            // Close modal
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
                document.getElementById('auth-message').innerHTML = '';
            });

            // Tab switching
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
                    document.getElementById(this.getAttribute('data-tab') + '-tab').style.display = 'block';
                });
            });

            // Registration form submission
            jQuery('#modal-register-form').on('submit', function(e) {
                e.preventDefault();
                const formData = jQuery(this).serialize() + '&courscribe_submit_register=1';
                jQuery.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: formData + '&action=courscribe_register',
                    success: function(response) {
                        if (response.success) {
                            modal.style.display = 'none';
                            // Add product to cart and redirect to checkout
                            jQuery.ajax({
                                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                                type: 'POST',
                                data: {
                                    action: 'courscribe_add_to_cart',
                                    product_id: selectedProductId,
                                    nonce: '<?php echo wp_create_nonce( 'courscribe_add_to_cart_nonce' ); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        window.location.href = response.data.checkout_url;
                                    } else {
                                        alert(response.data.message || 'Error adding product to cart.');
                                    }
                                },
                                error: function() {
                                    alert('An error occurred. Please try again.');
                                }
                            });
                        } else {
                            document.getElementById('auth-message').innerHTML = response.data.message;
                        }
                    },
                    error: function() {
                        document.getElementById('auth-message').innerHTML = 'An error occurred. Please try again.';
                    }
                });
            });

            // Login form submission
            jQuery('#modal-login-form').on('submit', function(e) {
                e.preventDefault();
                const formData = jQuery(this).serialize() + '&courscribe_submit_signin=1';
                jQuery.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: formData + '&action=courscribe_signin',
                    success: function(response) {
                        if (response.success) {
                            modal.style.display = 'none';
                            // Add product to cart and redirect to checkout
                            jQuery.ajax({
                                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                                type: 'POST',
                                data: {
                                    action: 'courscribe_add_to_cart',
                                    product_id: selectedProductId,
                                    nonce: '<?php echo wp_create_nonce( 'courscribe_add_to_cart_nonce' ); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        window.location.href = response.data.checkout_url;
                                    } else {
                                        alert(response.data.message || 'Error adding product to cart.');
                                    }
                                },
                                error: function() {
                                    alert('An error occurred. Please try again.');
                                }
                            });
                        } else {
                            document.getElementById('auth-message').innerHTML = response.data.message;
                        }
                    },
                    error: function() {
                        document.getElementById('auth-message').innerHTML = 'An error occurred. Please try again.';
                    }
                });
            });

            // Pricing toggle
            const toggleButtons = document.querySelectorAll(".toggle-button");
            const plusOption = document.querySelector(".plus-option");
            const proOption = document.querySelector(".pro-option");

            toggleButtons.forEach(button => {
                button.addEventListener("click", function() {
                    toggleButtons.forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");

                    const period = this.getAttribute("data-period");
                    if (period === "monthly") {
                        plusOption.querySelector(".monthly-price").style.display = "block";
                        plusOption.querySelector(".yearly-price").style.display = "none";
                        plusOption.querySelector(".monthly-short-desc").style.display = "block";
                        plusOption.querySelector(".yearly-short-desc").style.display = "none";
                        plusOption.querySelector(".monthly-button").style.display = "inline-block";
                        plusOption.querySelector(".yearly-button").style.display = "none";
                        proOption.querySelector(".monthly-price").style.display = "block";
                        proOption.querySelector(".yearly-price").style.display = "none";
                        proOption.querySelector(".monthly-short-desc").style.display = "block";
                        proOption.querySelector(".yearly-short-desc").style.display = "none";
                        proOption.querySelector(".monthly-button").style.display = "inline-block";
                        proOption.querySelector(".yearly-button").style.display = "none";
                    } else {
                        plusOption.querySelector(".monthly-price").style.display = "none";
                        plusOption.querySelector(".yearly-price").style.display = "block";
                        plusOption.querySelector(".monthly-short-desc").style.display = "none";
                        plusOption.querySelector(".yearly-short-desc").style.display = "block";
                        plusOption.querySelector(".monthly-button").style.display = "none";
                        plusOption.querySelector(".yearly-button").style.display = "inline-block";
                        proOption.querySelector(".monthly-price").style.display = "none";
                        proOption.querySelector(".yearly-price").style.display = "block";
                        proOption.querySelector(".monthly-short-desc").style.display = "none";
                        proOption.querySelector(".yearly-short-desc").style.display = "block";
                        proOption.querySelector(".monthly-button").style.display = "none";
                        proOption.querySelector(".yearly-button").style.display = "inline-block";
                    }
                });
            });
        });
    </script>

    <?php
    return ob_get_clean();
}

// AJAX handler for registration
add_action( 'wp_ajax_courscribe_register', 'courscribe_ajax_register' );
add_action( 'wp_ajax_nopriv_courscribe_register', 'courscribe_ajax_register' );

function courscribe_ajax_register() {
    if ( ! wp_verify_nonce( $_POST['courscribe_register_nonce'], 'courscribe_register' ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce.' ] );
    }

    $username = sanitize_user( $_POST['courscribe_username'] );
    $email = sanitize_email( $_POST['courscribe_email'] );
    $password = $_POST['courscribe_password'];
    $password_confirm = $_POST['courscribe_password_confirm'];

    if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $password_confirm ) ) {
        wp_send_json_error( [ 'message' => 'All fields are required.' ] );
    }

    if ( username_exists( $username ) ) {
        wp_send_json_error( [ 'message' => 'Username already exists.' ] );
    }

    if ( email_exists( $email ) ) {
        wp_send_json_error( [ 'message' => 'Email already registered.' ] );
    }

    if ( $password !== $password_confirm ) {
        wp_send_json_error( [ 'message' => 'Passwords do not match.' ] );
    }

    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
    }

    wp_update_user( [ 'ID' => $user_id, 'role' => 'studio_admin' ] );
    
    // Initialize onboarding flow for new users
    update_user_meta($user_id, '_courscribe_onboarding_step', 'welcome');
    update_user_meta($user_id, '_courscribe_first_login', 'pending');
    update_user_meta($user_id, '_courscribe_user_tier', 'basics');
    
    // Log new user registration for onboarding tracking
    error_log("CourScribe: New user registered - ID: {$user_id}, Username: {$username}, Email: {$email}");
    
    wp_signon( [ 'user_login' => $username, 'user_password' => $password, 'remember' => true ], false );
    wp_send_json_success();
}

// AJAX handler for login
add_action( 'wp_ajax_courscribe_signin', 'courscribe_ajax_signin' );
add_action( 'wp_ajax_nopriv_courscribe_signin', 'courscribe_ajax_signin' );

function courscribe_ajax_signin() {
    if ( ! wp_verify_nonce( $_POST['courscribe_signin_nonce'], 'courscribe_signin' ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce.' ] );
    }

    $email = sanitize_email( $_POST['courscribe_email'] );
    $password = $_POST['courscribe_password'];

    if ( empty( $email ) || empty( $password ) ) {
        wp_send_json_error( [ 'message' => 'All fields are required.' ] );
    }

    $user = wp_signon( [ 'user_login' => $email, 'user_password' => $password, 'remember' => true ], false );
    if ( is_wp_error( $user ) ) {
        wp_send_json_error( [ 'message' => 'Invalid email or password.' ] );
    }

    wp_send_json_success();
}

// AJAX handler for adding product to cart and redirecting to checkout
add_action( 'wp_ajax_courscribe_add_to_cart', 'courscribe_add_to_cart' );
add_action( 'wp_ajax_nopriv_courscribe_add_to_cart', 'courscribe_add_to_cart' );

function courscribe_add_to_cart() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'courscribe_add_to_cart_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce.' ] );
    }

    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => 'Invalid product ID.' ] );
    }

    // Initialize WooCommerce cart
    if ( ! WC()->cart ) {
        wp_send_json_error( [ 'message' => 'Cart is not initialized.' ] );
    }

    // Clear cart to avoid conflicts (optional, depending on your requirements)
    WC()->cart->empty_cart();

    // Add product to cart
    $added = WC()->cart->add_to_cart( $product_id, 1 );

    if ( $added ) {
        // Get checkout URL
        $checkout_url = wc_get_checkout_url();
        wp_send_json_success( [ 'checkout_url' => $checkout_url ] );
    } else {
        wp_send_json_error( [ 'message' => 'Failed to add product to cart.' ] );
    }
}
?>