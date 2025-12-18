<?php
// WooCommerce Order Received Page Customization
// Beautiful premium styling for order completion and studio redirect
if (!defined('ABSPATH')) {
    exit;
}

// Add premium styling and redirect button to order received page
add_action('woocommerce_thankyou', 'courscribe_customize_order_received_page', 20);
function courscribe_customize_order_received_page($order_id) {
    if (!$order_id) return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    $user_id = $order->get_user_id();
    if (!$user_id) return;
    
    // Get studio page URL
    $studio_page_id = get_option('courscribe_studio_page');
    $studio_url = $studio_page_id ? get_permalink($studio_page_id) : home_url('/studio/');
    
    // Check if this is a CourScribe subscription product
    $is_courscribe_order = false;
    $subscription_tier = 'basics';
    $product_names = [];
    
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
            $sku = $product->get_sku();
            $product_names[] = $product->get_name();
            
            if (strpos($sku, 'COURSCRIBE') === 0) {
                $is_courscribe_order = true;
                
                if (strpos($sku, 'PRO') !== false) {
                    $subscription_tier = 'pro';
                } elseif (strpos($sku, 'PLUS') !== false) {
                    $subscription_tier = 'plus';
                }
            }
        }
    }
    
    if (!$is_courscribe_order) return;
    
    // Update user tier and onboarding status
    update_user_meta($user_id, '_courscribe_user_tier', $subscription_tier);
    update_user_meta($user_id, '_courscribe_tribe_selected', '1');
    update_user_meta($user_id, '_courscribe_onboarding_step', 'complete');
    
    ?>
    <div class="courscribe-order-success-premium">
        
        <!-- Success Hero Section -->
        <div class="success-hero">
        <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;">
            <!-- <div class="success-animation">
               
                <div class="success-rings">
                    <div class="ring ring-1"></div>
                    <div class="ring ring-2"></div>
                    <div class="ring ring-3"></div>
                </div>
            </div> -->
            
            <div class="success-content">
                <h1 class="success-title">
                    🎉 <span class="gradient-text">Welcome to CourScribe!</span>
                </h1>
                <p class="success-subtitle">
                    Your subscription is now active and ready to transform your curriculum creation process.
                </p>
            </div>
        </div>
        
        <!-- Order Details Card -->
        <div class="order-details-premium">
            <div class="order-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="header-content">
                        <h3>Order Confirmation</h3>
                        <p>Order #<?php echo $order->get_order_number(); ?></p>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="order-info">
                        <div class="info-item">
                            <div class="info-label">Plan Selected:</div>
                            <div class="info-value">
                                <span class="plan-badge tier-<?php echo $subscription_tier; ?>">
                                    <i class="fas fa-<?php echo $subscription_tier === 'basics' ? 'star' : ($subscription_tier === 'plus' ? 'rocket' : 'crown'); ?>"></i>
                                    <?php echo implode(', ', $product_names); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Total Amount:</div>
                            <div class="info-value price">
                                <?php echo $order->get_formatted_order_total(); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Payment Status:</div>
                            <div class="info-value">
                                <span class="status-badge paid">
                                    <i class="fas fa-check-circle"></i>
                                    Paid
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Subscription Status:</div>
                            <div class="info-value">
                                <span class="status-badge active">
                                    <i class="fas fa-play-circle"></i>
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Features Unlocked -->
        <div class="features-unlocked">
            <h2 class="features-title">🚀 Features Now Available</h2>
            <div class="features-grid">
                <?php 
                $features = [];
                if ($subscription_tier === 'basics') {
                    $features = [
                        'Create 1 Curriculum',
                        'Unlimited Courses',
                        'Drag & Drop Editor',
                        'Chat/Email Support'
                    ];
                } elseif ($subscription_tier === 'plus') {
                    $features = [
                        'Everything in Basics',
                        'AI Content Generation',
                        'Voice Dictation',
                        'CourseProfit LAB Access'
                    ];
                } else { // pro
                    $features = [
                        'Everything in Plus',
                        'Unlimited Curriculums',
                        'Client Feedback Links',
                        'AI Full Course Builder',
                        'White-labeled Options',
                        'Priority Support'
                    ];
                }
                
                foreach ($features as $feature): ?>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="feature-text"><?php echo esc_html($feature); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="cta-section">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Start Creating?</h2>
                <p class="cta-subtitle">
                    Your premium studio is set up and waiting. Let's create your first curriculum!
                </p>
                
                <div class="cta-buttons">
                    <a href="<?php echo esc_url($studio_url); ?>" class="btn-primary-large">
                        <div class="btn-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="btn-content">
                            <span class="btn-title">Go to Your Studio</span>
                            <span class="btn-subtitle">Start creating amazing content</span>
                        </div>
                        <div class="btn-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="btn-glow"></div>
                    </a>
                    
                    <a href="<?php echo esc_url($studio_url . '?tour=1'); ?>" class="btn-secondary-large">
                        <div class="btn-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="btn-content">
                            <span class="btn-title">Take a Tour</span>
                            <span class="btn-subtitle">Learn the basics first</span>
                        </div>
                    </a>
                </div>
                
                <div class="support-info">
                    <div class="support-item">
                        <i class="fas fa-headset"></i>
                        <span>Need help? Our support team is available 24/7</span>
                    </div>
                    <div class="support-item">
                        <i class="fas fa-book"></i>
                        <span>Check out our <a href="#" class="support-link">Getting Started Guide</a></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Next Steps -->
        <div class="next-steps">
            <h3 class="steps-title">What's Next?</h3>
            <div class="steps-list">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Explore Your Studio</h4>
                        <p>Familiarize yourself with the dashboard and available tools</p>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Create Your First Curriculum</h4>
                        <p>Use our guided process to build your first educational content</p>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Invite Your Team</h4>
                        <p>Add collaborators and start working together on your content</p>
                    </div>
                    <div class="step-status">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Premium Order Success Styles -->
    <style>
        .woocommerce-order-details,
        .woocommerce-notice,
        .woocommerce-customer-details,
        .wp-block-site-logo,
        .entry-title,
        .wp-block-spacer{
            display: none;
        }
        body:not(.et-tb) #main-content .container, body:not(.et-tb-has-header) #main-content .container {
            padding-top: 8px !important;
        }
        .container {
            width: 100% !important;
        }
    .courscribe-order-success-premium {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #ffffff;
        padding: 0 20px 40px 20px;
        margin: 0;
        border-radius: 0;
    }

    /* Success Hero */
    .success-hero {
        text-align: center;
        padding: 60px 20px;
        position: relative;
        overflow: hidden;
    }

    .success-animation {
        position: relative;
        display: inline-block;
        margin-bottom: 40px;
    }

    .checkmark-circle {
        width: 120px;
        height: 120px;
        background: linear-gradient(45deg, #4CAF50, #81C784);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 2;
        animation: checkmarkPop 0.6s ease-out;
    }

    .checkmark {
        font-size: 48px;
        color: white;
        animation: checkmarkBounce 0.8s ease-out 0.3s both;
    }

    .success-rings {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .ring {
        position: absolute;
        border: 2px solid rgba(76, 175, 80, 0.3);
        border-radius: 50%;
        animation: ringExpand 2s ease-out infinite;
    }

    .ring-1 {
        width: 140px;
        height: 140px;
        margin: -70px 0 0 -70px;
        animation-delay: 0s;
    }

    .ring-2 {
        width: 180px;
        height: 180px;
        margin: -90px 0 0 -90px;
        animation-delay: 0.5s;
    }

    .ring-3 {
        width: 220px;
        height: 220px;
        margin: -110px 0 0 -110px;
        animation-delay: 1s;
    }

    @keyframes checkmarkPop {
        0% { transform: scale(0); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    @keyframes checkmarkBounce {
        0% { transform: scale(0) rotate(0deg); opacity: 0; }
        50% { transform: scale(1.2) rotate(5deg); opacity: 1; }
        100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }

    @keyframes ringExpand {
        0% { transform: scale(1); opacity: 0.6; }
        100% { transform: scale(1.5); opacity: 0; }
    }

    .success-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 16px;
        line-height: 1.2;
    }

    .gradient-text {
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .success-subtitle {
        font-size: 1.1rem;
        color: #cccccc;
        line-height: 1.6;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Order Details */
    .order-details-premium {
        max-width: 800px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }

    .order-card {
        background: rgba(42, 42, 42, 0.8);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(228, 178, 111, 0.2);
        border-radius: 20px;
        overflow: hidden;
    }

    .card-header {
        padding: 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 16px;
        background: linear-gradient(135deg, rgba(228, 178, 111, 0.1), rgba(248, 146, 62, 0.05));
    }

    .header-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1a1a1a;
        font-size: 20px;
    }

    .header-content h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 4px 0;
        color: #ffffff;
    }

    .header-content p {
        font-size: 0.9rem;
        color: #aaa;
        margin: 0;
    }

    .card-body {
        padding: 24px;
    }

    .order-info {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.9rem;
        color: #aaa;
        font-weight: 500;
    }

    .info-value {
        font-size: 0.95rem;
        color: #ffffff;
        font-weight: 600;
    }

    .info-value.price {
        font-size: 1.1rem;
        color: #E4B26F;
    }

    .plan-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .plan-badge.tier-basics {
        background: rgba(102, 102, 102, 0.2);
        color: #ffffff;
        border: 1px solid rgba(102, 102, 102, 0.3);
    }

    .plan-badge.tier-plus {
        background: rgba(33, 150, 243, 0.2);
        color: #64B5F6;
        border: 1px solid rgba(33, 150, 243, 0.3);
    }

    .plan-badge.tier-pro {
        background: rgba(255, 215, 0, 0.2);
        color: #FFD700;
        border: 1px solid rgba(255, 215, 0, 0.3);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-badge.paid {
        background: rgba(76, 175, 80, 0.2);
        color: #4CAF50;
        border: 1px solid rgba(76, 175, 80, 0.3);
    }

    .status-badge.active {
        background: rgba(33, 150, 243, 0.2);
        color: #2196F3;
        border: 1px solid rgba(33, 150, 243, 0.3);
    }

    /* Features Unlocked */
    .features-unlocked {
        max-width: 1000px;
        margin: 0 auto 60px;
        padding: 0 20px;
        text-align: center;
    }

    .features-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 40px;
        color: #ffffff;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-2px);
        border-color: rgba(228, 178, 111, 0.3);
        background: rgba(255, 255, 255, 0.08);
    }

    .feature-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(45deg, #4CAF50, #81C784);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        flex-shrink: 0;
    }

    .feature-text {
        font-size: 0.9rem;
        color: #cccccc;
        font-weight: 500;
        text-align: left;
    }

    /* CTA Section */
    .cta-section {
        max-width: 800px;
        margin: 0 auto 60px;
        padding: 0 20px;
        text-align: center;
    }

    .cta-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #ffffff;
    }

    .cta-subtitle {
        font-size: 1.1rem;
        color: #cccccc;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }

    .btn-primary-large {
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        color: #1a1a1a;
        text-decoration: none;
        padding: 20px 32px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-width: 280px;
    }

    .btn-primary-large:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 35px rgba(228, 178, 111, 0.4);
        color: #1a1a1a;
        text-decoration: none;
    }

    .btn-secondary-large {
        background: rgba(255, 255, 255, 0.1);
        color: #cccccc;
        text-decoration: none;
        padding: 20px 32px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
        min-width: 280px;
    }

    .btn-secondary-large:hover {
        transform: translateY(-4px);
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        text-decoration: none;
    }

    .btn-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .btn-primary-large .btn-icon {
        background: rgba(26, 26, 26, 0.2);
    }

    .btn-content {
        flex: 1;
        text-align: left;
    }

    .btn-title {
        display: block;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .btn-subtitle {
        display: block;
        font-size: 0.85rem;
        opacity: 0.8;
        line-height: 1.2;
        margin-top: 2px;
    }

    .btn-arrow {
        font-size: 16px;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }

    .btn-primary-large:hover .btn-arrow {
        transform: translateX(4px);
    }

    .btn-glow {
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s ease;
    }

    .btn-primary-large:hover .btn-glow {
        left: 100%;
    }

    .support-info {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: center;
    }

    .support-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #aaa;
    }

    .support-item i {
        color: #E4B26F;
    }

    .support-link {
        color: #E4B26F;
        text-decoration: none;
    }

    .support-link:hover {
        color: #F8923E;
        text-decoration: underline;
    }

    /* Next Steps */
    .next-steps {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .steps-title {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 30px;
        color: #ffffff;
    }

    .steps-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .step-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .step-item:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(228, 178, 111, 0.3);
    }

    .step-number {
        width: 32px;
        height: 32px;
        background: linear-gradient(45deg, #E4B26F, #F8923E);
        color: #1a1a1a;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .step-content {
        flex: 1;
    }

    .step-content h4 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 4px 0;
        color: #ffffff;
    }

    .step-content p {
        font-size: 0.9rem;
        color: #aaa;
        margin: 0;
        line-height: 1.4;
    }

    .step-status {
        color: #E4B26F;
        font-size: 16px;
        flex-shrink: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .success-title {
            font-size: 2rem;
        }

        .cta-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn-primary-large,
        .btn-secondary-large {
            min-width: auto;
            width: 100%;
            max-width: 320px;
        }

        .order-info {
            gap: 12px;
        }

        .info-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .courscribe-order-success-premium {
            padding: 20px 10px;
            margin: -20px -10px 20px -10px;
        }

        .success-hero {
            padding: 40px 10px;
        }

        .checkmark-circle {
            width: 100px;
            height: 100px;
        }

        .checkmark {
            font-size: 40px;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add entrance animations
        const elements = document.querySelectorAll('.order-card, .feature-card, .step-item');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.6s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 * index);
        });
        
        // Auto-redirect after 30 seconds (optional)
        setTimeout(() => {
            const autoRedirect = confirm('Would you like to go to your studio now?');
            if (autoRedirect) {
                window.location.href = '<?php echo esc_url($studio_url); ?>';
            }
        }, 30000);
    });
    </script>
    <?php
}

// Hide default WooCommerce order details on CourScribe orders
add_action('woocommerce_order_details_after_order_table', 'courscribe_hide_default_order_details', 5);
function courscribe_hide_default_order_details($order) {
    $is_courscribe_order = false;
    
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && strpos($product->get_sku(), 'COURSCRIBE') === 0) {
            $is_courscribe_order = true;
            break;
        }
    }
    
    if ($is_courscribe_order) {
        echo '<style>
            .woocommerce-order-overview,
            .woocommerce-customer-details,
            .woocommerce-bacs-bank-details {
                display: none !important;
            }
        </style>';
    }
}

// Add custom CSS to WooCommerce pages
add_action('wp_head', 'courscribe_woocommerce_custom_css');
function courscribe_woocommerce_custom_css() {
    if (is_wc_endpoint_url('order-received')) {
        echo '<style>
            body.woocommerce-order-received {
                background: linear-gradient(135deg, #0F0F23 0%, #1A1A2E 100%) !important;
            }
            .woocommerce {
                background: transparent;
            }
            .woocommerce-message {
                background: rgba(76, 175, 80, 0.1) !important;
                color: #4CAF50 !important;
                border: 1px solid rgba(76, 175, 80, 0.3) !important;
            }
        </style>';
    }
}
?>