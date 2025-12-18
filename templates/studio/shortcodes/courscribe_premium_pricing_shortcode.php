<?php
// Premium CourScribe Pricing Shortcode - Modern Redesign
// Beautiful, modern pricing interface with premium styling
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_premium_pricing_shortcode() {
    $site_url = home_url();
    $user_id = get_current_user_id();
    $tribe_selected = get_user_meta($user_id, '_courscribe_tribe_selected', true);
    $current_tier = 'basics';
    $current_sku = '';
    $has_active_subscription = false;

    // Get active subscription for the current user
    if (is_user_logged_in()) {
        $subscription_orders = wc_get_orders([
            'limit' => 1,
            'status' => ['completed', 'processing', 'on-hold', 'wc-wps_renewal'],
            'type' => 'shop_order',
            'customer_id' => $user_id,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        foreach ($subscription_orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes') {
                    $current_sku = $product->get_sku();
                    $product_name = strtolower($product->get_name());
                    $subscription_status = $order->get_meta('wps_subscription_status') ?: 'active';
                    
                    $has_active_subscription = in_array($subscription_status, ['active', 'pending-cancel']);
                    
                    if (strpos($product_name, 'pro') !== false) {
                        $current_tier = 'pro';
                    } elseif (strpos($product_name, 'plus') !== false || strpos($product_name, '+') !== false) {
                        $current_tier = 'plus';
                    } else {
                        $current_tier = 'basics';
                    }
                    
                    break 2;
                }
            }
        }
    }

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

    // Determine button states
    $button_states = [
        'basics' => ['enabled' => true, 'text' => 'Get Started Free'],
        'plus-monthly' => ['enabled' => true, 'text' => 'Choose Plus'],
        'plus-yearly' => ['enabled' => true, 'text' => 'Choose Plus'],
        'pro-monthly' => ['enabled' => true, 'text' => 'Choose Pro'],
        'pro-yearly' => ['enabled' => true, 'text' => 'Choose Pro'],
    ];

    if (is_user_logged_in() && $has_active_subscription) {
        $button_states['basics'] = ['enabled' => false, 'text' => 'Current Plan'];
        
        switch ($current_tier) {
            case 'plus':
                if ($current_sku === 'COURSCRIBE-PLUS-MONTHLY') {
                    $button_states['plus-monthly'] = ['enabled' => false, 'text' => 'Current Plan'];
                    $button_states['plus-yearly'] = ['enabled' => true, 'text' => 'Switch to Yearly'];
                } elseif ($current_sku === 'COURSCRIBE-PLUS-YEARLY') {
                    $button_states['plus-yearly'] = ['enabled' => false, 'text' => 'Current Plan'];
                }
                break;
                
            case 'pro':
                if ($current_sku === 'COURSCRIBE-PRO-MONTHLY') {
                    $button_states['pro-monthly'] = ['enabled' => false, 'text' => 'Current Plan'];
                    $button_states['pro-yearly'] = ['enabled' => true, 'text' => 'Switch to Yearly'];
                } elseif ($current_sku === 'COURSCRIBE-PRO-YEARLY') {
                    $button_states['pro-yearly'] = ['enabled' => false, 'text' => 'Current Plan'];
                }
                break;
        }
    } elseif (is_user_logged_in()) {
        $button_states['basics'] = ['enabled' => false, 'text' => 'Current Plan'];
    }

    ob_start();
    ?>

    <!-- Premium Pricing Interface -->
    <div class="courscribe-premium-pricing" id="pricing-app">
        
        <!-- Pricing Hero Section -->
        <div class="pricing-hero">
            <div class="hero-background">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                    <div class="shape shape-4"></div>
                </div>
            </div>
            
            <div class="hero-content">
                <div class="hero-header">
                    <div class="logo-container">
                        <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="CourScribe" class="hero-logo">
                    </div>
                    
                    <h1 class="hero-title">
                        <span class="title-line-1">Choose Your</span>
                        <span class="title-line-2 gradient-text">Creative Journey</span>
                    </h1>
                    
                    <p class="hero-subtitle">
                        Unlock the power of educational content creation with our premium plans.<br>
                        Start free, upgrade anytime, and scale your educational empire.
                    </p>
                    
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number">10K+</div>
                            <div class="stat-label">Educators</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number">50K+</div>
                            <div class="stat-label">Curriculums</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number">99%</div>
                            <div class="stat-label">Satisfaction</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pricing Toggle -->
        <div class="pricing-toggle-container mb-4">
            <div class="toggle-wrapper">
                <div class="toggle-label">Monthly</div>
                <div class="toggle-switch" id="billing-toggle">
                    <div class="toggle-slider"></div>
                </div>
                <div class="toggle-label">
                    Yearly 
                    <span class="save-badge">Save 20%</span>
                </div>
            </div>
        </div>
        
        <!-- Premium Pricing Cards -->
        <div class="pricing-cards-container">
            <div class="pricing-cards-grid">
                
                <!-- Basics Plan -->
                <?php if (isset($products['basics'])): 
                    $product = $products['basics'];
                    $state = $button_states['basics'];
                ?>
                <div class="pricing-card basics-card">
                    <div class="card-header">
                    
                        <h3 class="plan-name"><?php echo esc_html($product['name']); ?></h3>
                        <p class="plan-description"><?php echo wp_kses_post($product['short_description']); ?></p>
                    </div>
                    
                    <div class="price-section">
                        <div class="price-display">
                            <span class="currency">$</span>
                            <span class="price">0</span>
                            <span class="price-suffix">/forever</span>
                        </div>
                        <div class="price-note">Perfect for getting started</div>
                    </div>
                    
                    <div class="features-section">
                        <h4 class="features-title">What's included:</h4>
                        <ul class="features-list">
                            <?php foreach ($product['features'] as $feature): ?>
                                <li class="feature-item">
                                <span class="feature-icon">
                                        <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 0h24v24H0z" fill="none"></path>
                                            <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                        </svg>
                                    </span>
                                    <span><?php echo esc_html($feature); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="card-footer">
                        <button class="plan-button basics-button <?php echo $state['enabled'] ? '' : 'current-plan'; ?>" 
                                data-plan="basics" data-product-id="<?php echo esc_attr($product['product_id']); ?>"
                                <?php echo $state['enabled'] ? '' : 'disabled'; ?>>
                            <span class="button-text"><?php echo esc_html($state['text']); ?></span>
                            <?php if ($state['enabled']): ?>
                                <i class="fas fa-arrow-right button-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-check button-icon"></i>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Plus Plan -->
                <?php if (isset($products['plus-monthly']) && isset($products['plus-yearly'])): 
                    $product_monthly = $products['plus-monthly'];
                    $product_yearly = $products['plus-yearly'];
                    $state_monthly = $button_states['plus-monthly'];
                    $state_yearly = $button_states['plus-yearly'];
                ?>
                <div class="pricing-card plus-card popular">
                    <div class="popular-badge">
                        <i class="fas fa-crown"></i>
                        <span>Most Popular</span>
                    </div>
                    
                    <div class="card-header">
                        
                        <h3 class="plan-name"><?php echo esc_html(str_replace(' (Monthly)', '', $product_monthly['name'])); ?></h3>
                        <p class="plan-description monthly-desc"><?php echo wp_kses_post($product_monthly['short_description']); ?></p>
                        <p class="plan-description yearly-desc" style="display: none;"><?php echo wp_kses_post($product_yearly['short_description']); ?></p>
                    </div>
                    
                    <div class="price-section">
                        <div class="price-display monthly-pricing">
                            <span class="currency">$</span>
                            <span class="price"><?php echo preg_replace('/[^\d.]/', '', strip_tags($product_monthly['price'])); ?></span>
                            <span class="price-suffix">/month</span>
                        </div>
                        <div class="price-display yearly-pricing" style="display: none;">
                            <span class="currency">$</span>
                            <span class="price"><?php echo preg_replace('/[^\d.]/', '', strip_tags($product_yearly['price'])); ?></span>
                            <span class="price-suffix">/year</span>
                        </div>
                        <div class="price-note monthly-note">Billed monthly</div>
                        <div class="price-note yearly-note" style="display: none;">
                            Billed yearly • Save $<?php echo (preg_replace('/[^\d.]/', '', strip_tags($product_monthly['price'])) * 12) - preg_replace('/[^\d.]/', '', strip_tags($product_yearly['price'])); ?>
                        </div>
                    </div>
                    
                    <div class="features-section">
                        <h4 class="features-title">Everything in Basics, plus:</h4>
                        <ul class="features-list">
                            <?php foreach ($product_monthly['features'] as $feature): ?>
                                <li class="feature-item">
                                    <span class="feature-icon">
                                        <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 0h24v24H0z" fill="none"></path>
                                            <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                        </svg>
                                    </span>
                                    <span><?php echo esc_html($feature); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="card-footer">
                        <button class="plan-button plus-button monthly-button <?php echo $state_monthly['enabled'] ? '' : 'current-plan'; ?>" 
                                data-plan="plus-monthly" data-product-id="<?php echo esc_attr($product_monthly['product_id']); ?>"
                                <?php echo $state_monthly['enabled'] ? '' : 'disabled'; ?>>
                            <span class="button-text"><?php echo esc_html($state_monthly['text']); ?></span>
                            <?php if ($state_monthly['enabled']): ?>
                                <i class="fas fa-arrow-right button-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-check button-icon"></i>
                            <?php endif; ?>
                        </button>
                        
                        <button class="plan-button plus-button yearly-button <?php echo $state_yearly['enabled'] ? '' : 'current-plan'; ?>" 
                                style="display: none;" data-plan="plus-yearly" data-product-id="<?php echo esc_attr($product_yearly['product_id']); ?>"
                                <?php echo $state_yearly['enabled'] ? '' : 'disabled'; ?>>
                            <span class="button-text"><?php echo esc_html($state_yearly['text']); ?></span>
                            <?php if ($state_yearly['enabled']): ?>
                                <i class="fas fa-arrow-right button-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-check button-icon"></i>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Pro Plan -->
                <?php if (isset($products['pro-monthly']) && isset($products['pro-yearly'])): 
                    $product_monthly = $products['pro-monthly'];
                    $product_yearly = $products['pro-yearly'];
                    $state_monthly = $button_states['pro-monthly'];
                    $state_yearly = $button_states['pro-yearly'];
                ?>
                <div class="pricing-card pro-card">
                    <div class="card-header">
                        <h3 class="plan-name"><?php echo esc_html(str_replace(' (Monthly)', '', $product_monthly['name'])); ?></h3>
                        <p class="plan-description monthly-desc"><?php echo wp_kses_post($product_monthly['short_description']); ?></p>
                        <p class="plan-description yearly-desc" style="display: none;"><?php echo wp_kses_post($product_yearly['short_description']); ?></p>
                    </div>
                    
                    <div class="price-section">
                        <div class="price-display monthly-pricing">
                            <span class="currency">$</span>
                            <span class="price"><?php echo preg_replace('/[^\d.]/', '', strip_tags($product_monthly['price'])); ?></span>
                            <span class="price-suffix">/month</span>
                        </div>
                        <div class="price-display yearly-pricing" style="display: none;">
                            <span class="currency">$</span>
                            <span class="price"><?php echo preg_replace('/[^\d.]/', '', strip_tags($product_yearly['price'])); ?></span>
                            <span class="price-suffix">/year</span>
                        </div>
                        <div class="price-note monthly-note">Billed monthly</div>
                        <div class="price-note yearly-note" style="display: none;">
                            Billed yearly • Save $<?php echo (preg_replace('/[^\d.]/', '', strip_tags($product_monthly['price'])) * 12) - preg_replace('/[^\d.]/', '', strip_tags($product_yearly['price'])); ?>
                        </div>
                    </div>
                    
                    <div class="features-section">
                        <h4 class="features-title">Everything in Plus, plus:</h4>
                        <ul class="features-list">
                            <?php foreach ($product_monthly['features'] as $feature): ?>
                                <li class="feature-item">
                                    <span class="feature-icon">
                                        <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 0h24v24H0z" fill="none"></path>
                                            <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                        </svg>
                                    </span>
                                    <span><?php echo esc_html($feature); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="card-footer">
                        <button class="plan-button pro-button monthly-button <?php echo $state_monthly['enabled'] ? '' : 'current-plan'; ?>" 
                                data-plan="pro-monthly" data-product-id="<?php echo esc_attr($product_monthly['product_id']); ?>"
                                <?php echo $state_monthly['enabled'] ? '' : 'disabled'; ?>>
                            <span class="button-text"><?php echo esc_html($state_monthly['text']); ?></span>
                            <?php if ($state_monthly['enabled']): ?>
                                <i class="fas fa-arrow-right button-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-check button-icon"></i>
                            <?php endif; ?>
                        </button>
                        
                        <button class="plan-button pro-button yearly-button <?php echo $state_yearly['enabled'] ? '' : 'current-plan'; ?>" 
                                style="display: none;" data-plan="pro-yearly" data-product-id="<?php echo esc_attr($product_yearly['product_id']); ?>"
                                <?php echo $state_yearly['enabled'] ? '' : 'disabled'; ?>>
                            <span class="button-text"><?php echo esc_html($state_yearly['text']); ?></span>
                            <?php if ($state_yearly['enabled']): ?>
                                <i class="fas fa-arrow-right button-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-check button-icon"></i>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Trust Indicators -->
        <div class="trust-section">
            <div class="trust-content">
                <h3 class="trust-title">Trusted by Educators Worldwide</h3>
                <div class="trust-indicators">
                    <div class="trust-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>SSL Secured</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Secure Payments</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-headset"></i>
                        <span>24/7 Support</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-undo"></i>
                        <span>30-Day Guarantee</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="faq-section">
            <div class="faq-header">
                <h2 class="faq-title">Frequently Asked Questions</h2>
                <p class="faq-subtitle">Everything you need to know about our plans</p>
            </div>
            
            <div class="faq-list">
                <div class="faq-item active">
                    <div class="faq-question">
                        <span>Can I switch between plans anytime?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately, and we'll prorate any differences.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Is there a free trial available?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Our Basics plan is completely free forever! For premium plans, we offer a 7-day free trial so you can explore all features risk-free.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>What payment methods do you accept?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards, PayPal, and bank transfers. All payments are processed securely through our encrypted payment system.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I cancel my subscription anytime?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely! You can cancel your subscription at any time from your account settings. You'll continue to have access until the end of your billing period.</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Authentication Modal -->
    <div class="auth-modal-overlay" id="auth-modal" style="display: none;">
        <div class="auth-modal">
            <div class="modal-header">
                <h3 class="modal-title">Get Started with CourScribe</h3>
                <button class="modal-close" id="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="auth-tabs">
                    <button class="auth-tab active" data-tab="register">Create Account</button>
                    <button class="auth-tab" data-tab="login">Sign In</button>
                </div>
                
                <div class="tab-content active" id="register-tab">
                    <form id="modal-register-form">
                        <?php wp_nonce_field('courscribe_register', 'courscribe_register_nonce'); ?>
                        <div class="form-group">
                            <input type="text" name="courscribe_username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="courscribe_email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="courscribe_password" placeholder="Password" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="courscribe_password_confirm" placeholder="Confirm Password" required>
                        </div>
                        <button type="submit" class="auth-button">Create Account</button>
                    </form>
                </div>
                
                <div class="tab-content" id="login-tab">
                    <form id="modal-login-form">
                        <?php wp_nonce_field('courscribe_signin', 'courscribe_signin_nonce'); ?>
                        <div class="form-group">
                            <input type="email" name="courscribe_email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="courscribe_password" placeholder="Password" required>
                        </div>
                        <button type="submit" class="auth-button">Sign In</button>
                    </form>
                </div>
                
                <div id="auth-message" class="auth-message"></div>
            </div>
        </div>
    </div>

    <!-- Premium Pricing Styles -->
    <style>
        .entry-title {
            display: none;
        }
        .container {
            width: 100% !important;
        }
        body:not(.et-tb) #main-content .container, body:not(.et-tb-has-header) #main-content .container {
            padding-top: 0 !important;
        }
        .price { 
            margin: 4px 0; 
            font-family: 'Open Sans'; 
            font-style: normal; 
            font-weight: 700; 
            font-size: 50px; 
            line-height: 46px; 
            background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
            text-fill-color: transparent; 
            display: inline-block; 
        }
        .price-suffix { 
            font-family: 'Open Sans'; 
            font-style: normal; 
            font-weight: 600; 
            font-size: 19px; 
            line-height: 46px; 
            text-align: center; 
            background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
            text-fill-color: transparent; 
            opacity: 1; 
            margin-left: 5px; 
            display: inline-block; 
            vertical-align: top; 
        }
        .feature-icon {
            background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); 
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        }

        .feature-icon svg {
        width: 14px;
        height: 14px;
        }


    /* Reset and Base Styles */
    .courscribe-premium-pricing {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        width: 100%;
        color: #ffffff;
        min-height: 100vh;
        line-height: 1.6;
        overflow-x: hidden;
    }

    .courscribe-premium-pricing * {
        box-sizing: border-box;
    }

    /* Hero Section */
    .pricing-hero {
        position: relative;
        padding: 80px 20px 60px;
        text-align: center;
        overflow: hidden;
    }

    .hero-background {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(228, 178, 111, 0.1), rgba(248, 146, 62, 0.05));
    }

    .floating-shapes {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
    }

    .shape {
        position: absolute;
        border-radius: 50%;
        background: linear-gradient(45deg, rgba(228, 178, 111, 0.1), rgba(248, 146, 62, 0.1));
        animation: float 6s ease-in-out infinite;
    }

    .shape-1 {
        width: 120px;
        height: 120px;
        top: 10%;
        left: 10%;
        animation-delay: 0s;
    }

    .shape-2 {
        width: 80px;
        height: 80px;
        top: 20%;
        right: 15%;
        animation-delay: 2s;
    }

    .shape-3 {
        width: 100px;
        height: 100px;
        bottom: 30%;
        left: 20%;
        animation-delay: 4s;
    }

    .shape-4 {
        width: 60px;
        height: 60px;
        bottom: 20%;
        right: 25%;
        animation-delay: 1s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }

    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 800px;
        margin: 0 auto;
    }

    .logo-container {
        margin-bottom: 40px;
    }

    .hero-logo {
        max-width: 200px;
        height: auto;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 24px;
        line-height: 1.1;
    }

    .title-line-1 {
        display: block;
        color: #ffffff;
    }

    .title-line-2 {
        display: block;
        margin-top: 8px;
    }

    .gradient-text {
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-subtitle {
        font-size: 1.2rem;
        color: #cccccc;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .hero-stats {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        margin-top: 50px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #E4B26F;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #888;
        margin-top: 4px;
    }

    .stat-divider {
        width: 1px;
        height: 40px;
        background: linear-gradient(to bottom, transparent, #333, transparent);
    }

    /* Pricing Toggle */
    .pricing-toggle-container {
        display: flex;
        justify-content: center;
        padding: 40px 20px;
    }

    .toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 16px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50px;
        padding: 8px 20px;
    }

    .toggle-label {
        font-size: 14px;
        font-weight: 500;
        color: #ccc;
        transition: color 0.3s ease;
    }

    .toggle-label.active {
        color: #E4B26F;
    }

    .toggle-switch {
        position: relative;
        width: 48px;
        height: 24px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.3s ease;
        margin-bottom: 22px;
    }

    .toggle-switch.active {
        background: linear-gradient(45deg, #E4B26F, #F8923E);
    }

    .toggle-slider {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        transition: transform 0.3s ease;
    }

    .toggle-switch.active .toggle-slider {
        transform: translateX(24px);
    }

    .save-badge {
        background: linear-gradient(45deg, #4CAF50, #81C784);
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 4px;
    }

    /* Pricing Cards */
    .pricing-cards-container {
        padding: 0 20px 80px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .pricing-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 32px;
        align-items: stretch;
    }

    .pricing-card {
        background: rgba(42, 42, 42, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 32px;
        position: relative;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden; /* Prevent image from overflowing the card */
    }

    /* ::before pseudo-element to display bg image at the top */
    .pricing-card::before {
        content: "";
        position: absolute;
        top: -100px;
        left: 0;
        width: 100%;
        height: 440px; /* adjust this to control height of the top image area */
        background-image: url('http://courscribe-divi.local/wp-content/uploads/2025/04/Mask-Group.png');
        background-size: cover;
        background-position: top center;
        background-repeat: no-repeat;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        z-index: 1;
    }

    /* Ensure card content appears above the background image */
    .pricing-card > * {
        position: relative;
        z-index: 2;
    }

    .pricing-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        border-color: rgba(228, 178, 111, 0.3);
    }

    .pricing-card.popular {
        border-color: #E4B26F;
        box-shadow: 0 20px 40px rgba(228, 178, 111, 0.2);
        transform: scale(1.05);
    }

    .pricing-card.popular:hover {
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

    .card-header {
        text-align: center;
        margin-top: 22px;
       
    }

    .plan-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #1a1a1a;
    }

    .basics-card .plan-icon {
        background: linear-gradient(45deg, #666, #888);
        color: white;
    }

    .pro-card .plan-icon {
        background: linear-gradient(45deg, #FFD700, #FFA500);
        color: #1a1a1a;
    }

    .plan-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: #ffffff;
    }

    .plan-description {
        font-size: 0.9rem;
        color: #aaa;
        line-height: 1.4;
    }

    .price-section {
        text-align: center;
        margin-bottom: 32px;
        padding: 20px 0;
        
    }

    .price-display {
        display: flex;
        justify-content: center;
        align-items: baseline;
        margin-bottom: 8px;
    }

    .currency {
        font-size: 1.5rem;
        font-weight: 600;
        background: linear-gradient(98.16deg, #F15538 19.73%, #F57D3B 97.02%); 
        -webkit-background-clip: text; 
        -webkit-text-fill-color: transparent; 
        background-clip: text; 
        text-fill-color: transparent; 
        margin-right: 4px;
    }

    .price-amount {
        font-size: 3rem;
        font-weight: 700;
        color: #ffffff;
        line-height: 1;
    }

    .price-period {
        font-size: 1rem;
        color: #888;
        margin-left: 4px;
    }

    .price-note {
        font-size: 0.85rem;
        color: #aaa;
    }

    .features-section {
        flex: 1;
        margin-bottom: 32px;
        margin-top: 22px;
    }

    .features-title {
        font-size: 1rem;
        font-weight: 600;
        color: #ccc;
        margin-bottom: 16px;
    }

    .features-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
        font-size: 0.9rem;
        color: #ccc;
    }

    .feature-item i {
        color: #4CAF50;
        font-size: 0.8rem;
    }

    .card-footer {
        margin-top: auto;
    }

    .plan-button {
        width: 100%;
        padding: 16px 24px;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }

    .basics-button {
        background: rgba(255, 255, 255, 0.1);
        color: #ccc;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .basics-button:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .plus-button, .pro-button {
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        color: #1a1a1a;
    }

    .plus-button:hover, .pro-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(228, 178, 111, 0.4);
    }

    .pro-button {
        background: linear-gradient(45deg, #FFD700, #FFA500);
    }

    .pro-button:hover {
        box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
    }

    .plan-button.current-plan {
        background: rgba(76, 175, 80, 0.2);
        color: #4CAF50;
        border: 1px solid rgba(76, 175, 80, 0.3);
        cursor: not-allowed;
    }

    .plan-button.current-plan:hover {
        transform: none;
        box-shadow: none;
    }

    .button-icon {
        font-size: 0.9rem;
        transition: transform 0.3s ease;
    }

    .plan-button:hover .button-icon {
        transform: translateX(2px);
    }

    /* Trust Section */
    .trust-section {
        background: rgba(255, 255, 255, 0.05);
        padding: 60px 20px;
        text-align: center;
    }

    .trust-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .trust-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 40px;
        color: #ffffff;
    }

    .trust-indicators {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 30px;
    }

    .trust-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        transition: transform 0.3s ease;
    }

    .trust-item:hover {
        transform: translateY(-4px);
    }

    .trust-item i {
        font-size: 2rem;
        color: #E4B26F;
    }

    .trust-item span {
        font-size: 0.9rem;
        color: #ccc;
        font-weight: 500;
    }

    /* FAQ Section */
    .faq-section {
        padding: 80px 20px;
        max-width: 800px;
        margin: 0 auto;
    }

    .faq-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .faq-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #ffffff;
    }

    .faq-subtitle {
        font-size: 1.1rem;
        color: #aaa;
    }

    .faq-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .faq-item {
        background: rgba(42, 42, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-item:hover {
        border-color: rgba(228, 178, 111, 0.3);
    }

    .faq-question {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
        color: #ffffff;
    }

    .faq-question i {
        color: #E4B26F;
        transition: transform 0.3s ease;
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 24px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-item.active .faq-answer {
        max-height: 200px;
        padding: 0 24px 20px;
    }

    .faq-answer p {
        color: #ccc;
        line-height: 1.6;
        margin: 0;
    }

    /* Authentication Modal */
    .auth-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    }

    .auth-modal {
        background: #2a2a2a;
        border: 1px solid rgba(228, 178, 111, 0.2);
        border-radius: 20px;
        max-width: 400px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 24px 24px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #ffffff;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        color: #888;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.3s ease;
    }

    .modal-close:hover {
        color: #E4B26F;
    }

    .modal-body {
        padding: 24px;
    }

    .auth-tabs {
        display: flex;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 4px;
        margin-bottom: 24px;
    }

    .auth-tab {
        flex: 1;
        padding: 8px 16px;
        background: none;
        border: none;
        color: #888;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .auth-tab.active {
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        color: #1a1a1a;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: #ffffff;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #E4B26F;
        box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
    }

    .form-group input::placeholder {
        color: #666;
    }

    .auth-button {
        width: 100%;
        padding: 12px 20px;
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        color: #1a1a1a;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .auth-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(228, 178, 111, 0.4);
    }

    .auth-message {
        margin-top: 16px;
        padding: 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        text-align: center;
    }

    .auth-message.error {
        background: rgba(244, 67, 54, 0.1);
        color: #f44336;
        border: 1px solid rgba(244, 67, 54, 0.2);
    }

    .auth-message.success {
        background: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
        border: 1px solid rgba(76, 175, 80, 0.2);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .hero-stats {
            flex-direction: column;
            gap: 20px;
        }

        .stat-divider {
            width: 40px;
            height: 1px;
        }

        .pricing-cards-grid {
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .pricing-card.popular {
            transform: none;
        }

        .pricing-card.popular:hover {
            transform: translateY(-8px);
        }

        .trust-indicators {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .toggle-wrapper {
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }
    }

    @media (max-width: 480px) {
        .hero-title {
            font-size: 2rem;
        }

        .pricing-hero {
            padding: 60px 20px 40px;
        }

        .trust-indicators {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <!-- Premium Pricing JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
        
        // Billing toggle functionality
        const billingToggle = document.getElementById('billing-toggle');
        const toggleLabels = document.querySelectorAll('.toggle-label');
        const monthlyElements = document.querySelectorAll('.monthly-pricing, .monthly-desc, .monthly-button, .monthly-note');
        const yearlyElements = document.querySelectorAll('.yearly-pricing, .yearly-desc, .yearly-button, .yearly-note');
        
        billingToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            
            toggleLabels.forEach((label, index) => {
                if (index === 0) {
                    label.classList.toggle('active', !this.classList.contains('active'));
                } else {
                    label.classList.toggle('active', this.classList.contains('active'));
                }
            });
            
            if (this.classList.contains('active')) {
                // Show yearly
                monthlyElements.forEach(el => el.style.display = 'none');
                yearlyElements.forEach(el => el.style.display = 'block');
            } else {
                // Show monthly
                monthlyElements.forEach(el => el.style.display = 'block');
                yearlyElements.forEach(el => el.style.display = 'none');
            }
        });
        
        // Plan selection
        const planButtons = document.querySelectorAll('.plan-button');
        const modal = document.getElementById('auth-modal');
        const closeModal = document.getElementById('close-modal');
        let selectedProductId = '';
        
        planButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.disabled) return;
                
                selectedProductId = this.getAttribute('data-product-id');
                
                if (isLoggedIn) {
                    addToCartAndCheckout(selectedProductId);
                } else {
                    modal.style.display = 'flex';
                }
            });
        });
        
        // Modal functionality
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
        
        // Auth tabs
        const authTabs = document.querySelectorAll('.auth-tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                authTabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab') + '-tab').classList.add('active');
            });
        });
        
        // FAQ functionality
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', function() {
                item.classList.toggle('active');
            });
        });
        
        // Form submissions
        document.getElementById('modal-register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            handleAuth(this, 'courscribe_register');
        });
        
        document.getElementById('modal-login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            handleAuth(this, 'courscribe_signin');
        });
        
        function handleAuth(form, action) {
            const formData = new FormData(form);
            formData.append('action', action);
            formData.append('courscribe_submit_' + action.split('_')[1], '1');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modal.style.display = 'none';
                    addToCartAndCheckout(selectedProductId);
                } else {
                    showMessage(data.data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred. Please try again.', 'error');
            });
        }
        
        function addToCartAndCheckout(productId) {
            const formData = new FormData();
            formData.append('action', 'courscribe_add_to_cart');
            formData.append('product_id', productId);
            formData.append('nonce', '<?php echo wp_create_nonce('courscribe_add_to_cart_nonce'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.data.checkout_url;
                } else {
                    showMessage(data.data.message || 'Error adding product to cart.', 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred. Please try again.', 'error');
            });
        }
        
        function showMessage(message, type) {
            const messageEl = document.getElementById('auth-message');
            messageEl.textContent = message;
            messageEl.className = 'auth-message ' + type;
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('courscribe_premium_pricing', 'courscribe_premium_pricing_shortcode');

// Include existing AJAX handlers
require_once plugin_dir_path(__FILE__) . 'courscribe_select_tribe_shortcode.php';
?>