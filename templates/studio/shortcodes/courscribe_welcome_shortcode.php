<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
add_action('wp_enqueue_scripts', 'courscribe_welcome_enqueue_scripts');
function courscribe_welcome_enqueue_scripts() {
    wp_localize_script('courscribe-welcome-frontend-script', 'courscribeAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('courscribe_onboarding')
    ]);
}
// Premium Welcome/Onboarding Shortcode
add_shortcode('courscribe_welcome', 'courscribe_welcome_shortcode');

function courscribe_welcome_shortcode() {
    if (!is_user_logged_in()) {
        return courscribe_retro_tv_error("Please log in to access the onboarding experience.");
    }

    $current_user = wp_get_current_user();
    $site_url = home_url();
    
    // Get user's subscription tier
    $user_tier = courscribe_get_user_tier($current_user->ID);
    $is_premium = in_array($user_tier, ['plus', 'pro']);
    
    // Check onboarding progress
    $onboarding_step = get_user_meta($current_user->ID, '_courscribe_onboarding_step', true) ?: 'welcome';
    $has_studio = courscribe_user_has_studio($current_user->ID);
    $tribe_selected = get_user_meta($current_user->ID, '_courscribe_tribe_selected', true);
    
    ob_start();
    ?>
    
    <!-- Premium Onboarding Styles -->
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/welcome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    
    <div class="courscribe-welcome-onboarding">
        <!-- Premium Background Animation -->
        <div class="premium-bg-animation">
            <div class="floating-element element-1"></div>
            <div class="floating-element element-2"></div>
            <div class="floating-element element-3"></div>
        </div>
        
        <!-- Progress Bar -->
        <div class="onboarding-progress-container">
            <div class="progress-bar-wrapper">
                <div class="progress-bar">
                    <div class="progress-fill" data-progress="<?php echo courscribe_get_onboarding_progress($current_user->ID); ?>"></div>
                </div>
                <div class="progress-steps">
                    <div class="step <?php echo $onboarding_step == 'welcome' ? 'active' : ($onboarding_step != 'welcome' ? 'completed' : ''); ?>">
                        <i class="fas fa-hand-paper"></i> 
                        <span>Welcome</span>
                    </div>
                    <div class="step <?php echo $onboarding_step == 'pricing' ? 'active' : (in_array($onboarding_step, ['studio', 'complete']) ? 'completed' : ''); ?>">
                        <i class="fas fa-crown"></i>
                        <span>Choose Plan</span>
                    </div>
                    <div class="step <?php echo $onboarding_step == 'studio' ? 'active' : ($onboarding_step == 'complete' ? 'completed' : ''); ?>">
                        <i class="fas fa-building"></i>
                        <span>Create Studio</span>
                    </div>
                    <div class="step <?php echo $onboarding_step == 'complete' ? 'active completed' : ''; ?>">
                        <i class="fas fa-rocket"></i>
                        <span>Launch</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Content -->
        <div class="welcome-content-container">
            <?php if ($onboarding_step == 'welcome'): ?>
                <!-- Welcome Step -->
                <div class="welcome-step animated-fade-in">
                    <div class="welcome-hero">
                        <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" 
                             alt="CourScribe Logo" class="welcome-logo">
                        <h1 class="welcome-title">
                            Welcome to <span class="gradient-text">CourScribe</span>
                            <?php if ($is_premium): ?>
                                <i class="fas fa-crown premium-badge" title="Premium User"></i>
                            <?php endif; ?>
                        </h1>
                        <p class="welcome-subtitle">
                            <?php if ($is_premium): ?>
                                🎉 <strong>Premium Experience Unlocked!</strong><br>
                                Let's set up your professional curriculum development studio with all premium features.
                            <?php else: ?>
                                Transform your educational content creation with our powerful curriculum development platform.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="welcome-features">
                        <div class="feature-grid">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <h3>AI-Powered Generation</h3>
                                <p>Create courses, modules, and lessons with intelligent AI assistance</p>
                                <?php if (!$is_premium): ?>
                                    <span class="premium-tag">Premium Feature</span>
                                <?php endif; ?>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3>Team Collaboration</h3>
                                <p>Invite collaborators and clients for seamless teamwork</p>
                                <?php if ($user_tier == 'basics'): ?>
                                    <span class="premium-tag">Plus Feature</span>
                                <?php endif; ?>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-presentation"></i>
                                </div>
                                <h3>Slide Deck Export</h3>
                                <p>Generate professional presentations automatically</p>
                                <?php if ($user_tier != 'pro'): ?>
                                    <span class="premium-tag">Pro Feature</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="welcome-actions">
                        <?php if (!$tribe_selected && !$is_premium): ?>
                            <button class="btn-premium" onclick="courscribeNextStep('pricing')">
                                <i class="fas fa-crown"></i>
                                Choose Your Plan
                                <span class="btn-shine"></span>
                            </button>
                            <button class="btn-secondary" onclick="courscribeSkipToPlan('basics')">
                                Continue with Free Plan
                            </button>
                        <?php else: ?>
                            <button class="btn-premium" onclick="courscribeNextStep('studio')">
                                <i class="fas fa-arrow-right"></i>
                                Let's Create Your Studio
                                <span class="btn-shine"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($onboarding_step == 'pricing'): ?>
                <!-- Pricing Step -->
                <div class="pricing-step animated-fade-in">
                    <div class="pricing-header">
                        <h2>Choose Your <span class="gradient-text">CourScribe</span> Plan</h2>
                        <p>Select the perfect plan for your curriculum development needs</p>
                    </div>
                    
                    <!-- Embed existing pricing shortcode with enhanced styling -->
                    <div class="premium-pricing-wrapper">
                        <?php echo do_shortcode('[courscribe_select_tribe]'); ?>
                    </div>
                    
                    <div class="pricing-actions">
                        <button class="btn-outline" onclick="courscribePrevStep('welcome')">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </button>
                    </div>
                </div>
                
            <?php elseif ($onboarding_step == 'studio'): ?>
                <!-- Studio Creation Step -->
                <div class="studio-step animated-fade-in">
                    <div class="studio-header">
                        <h2>Create Your <span class="gradient-text">Studio</span></h2>
                        <p>Your studio is the central hub for all your curriculum development projects</p>
                        
                        <?php if ($is_premium): ?>
                            <div class="premium-benefits">
                                <div class="benefit-item">
                                    <i class="fas fa-infinity"></i>
                                    <span>Unlimited Curriculums</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-users"></i>
                                    <span>Team Collaboration</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="fas fa-robot"></i>
                                    <span>AI Content Generation</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Enhanced Studio Creation Form -->
                    <div class="premium-studio-form">
                        <?php echo courscribe_render_premium_studio_form($current_user, $is_premium); ?>
                    </div>
                    
                    <div class="studio-actions">
                        <button class="btn-outline" onclick="courscribePrevStep('<?php echo $tribe_selected ? 'welcome' : 'pricing'; ?>')">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </button>
                    </div>
                </div>
                
            <?php elseif ($onboarding_step == 'complete'): ?>
                <!-- Completion Step -->
                <div class="complete-step animated-fade-in">
                    <div class="success-animation">
                        <div class="success-checkmark">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="confetti"></div>
                    </div>
                    
                    <h2>🎉 Welcome to <span class="gradient-text">CourScribe</span>!</h2>
                    <p class="success-message">
                        Your studio has been created successfully. You're ready to start building amazing educational content.
                    </p>
                    
                    <?php if ($is_premium): ?>
                        <div class="premium-success-features">
                            <h3>Your Premium Features Are Ready:</h3>
                            <div class="feature-checklist">
                                <div class="check-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>AI-powered content generation</span>
                                </div>
                                <div class="check-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Unlimited curriculum creation</span>
                                </div>
                                <div class="check-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Team collaboration tools</span>
                                </div>
                                <div class="check-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Professional slide deck export</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="launch-actions">
                        <button class="btn-premium btn-large" onclick="courscribeLaunchStudio()">
                            <i class="fas fa-rocket"></i>
                            Launch Your Studio
                            <span class="btn-shine"></span>
                        </button>
                        
                        <div class="quick-actions">
                            <button class="btn-outline" onclick="courscribeStartTour()">
                                <i class="fas fa-route"></i>
                                Take a Quick Tour
                            </button>
                            <button class="btn-outline" onclick="courscribeCreateFirstCurriculum()">
                                <i class="fas fa-plus"></i>
                                Create First Curriculum
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Premium Onboarding JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // Initialize premium animations
        courscribeInitPremiumOnboarding();
        
        // Update progress bar
        const progressBar = $('.progress-fill');
        const targetProgress = progressBar.data('progress');
        setTimeout(() => {
            progressBar.css('width', targetProgress + '%');
        }, 500);
    });
    
    function courscribeInitPremiumOnboarding() {
        // Add floating animation
        $('.floating-element').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.5) + 's',
                'animation-duration': (3 + index) + 's'
            });
        });
        
        // Add entrance animations
        $('.animated-fade-in').addClass('animate');
    }
    
    function courscribeNextStep(step) {
        courscribeUpdateOnboardingStep(step);
    }
    
    function courscribePrevStep(step) {
        courscribeUpdateOnboardingStep(step);
    }
    
    function courscribeSkipToPlan(tier) {
        // Set basic tier and continue to studio
        $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
            action: 'courscribe_set_tier',
            tier: tier,
            nonce: '<?php echo wp_create_nonce('courscribe_onboarding'); ?>'
        }, function(response) {
            if (response.success) {
                courscribeUpdateOnboardingStep('studio');
            }
        });
    }
    
    function courscribeUpdateOnboardingStep(step) {
        $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
            action: 'courscribe_update_onboarding_step',
            step: step,
            nonce: '<?php echo wp_create_nonce('courscribe_onboarding'); ?>'
        }, function(response) {
            if (response.success) {
                window.location.reload();
            }
        });
    }
    
    function courscribeLaunchStudio() {
        window.location.href = '<?php echo home_url('/studio/'); ?>';
    }
    
    function courscribeStartTour() {
        // Redirect to studio with tour parameter
        window.location.href = '<?php echo home_url('/studio/?tour=1'); ?>';
    }
    
    function courscribeCreateFirstCurriculum() {
        // Redirect to curriculum creation
        window.location.href = '<?php echo home_url('/studio/?create_curriculum=1'); ?>';
    }
    </script>

    
    
    <?php
    return ob_get_clean();
}

// Helper functions
function courscribe_get_user_tier($user_id) {
    // Get user's subscription tier from WooCommerce
    $tier = 'basics'; // Default
    
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders([
            'limit' => 1,
            'status' => ['completed', 'processing', 'on-hold'],
            'customer_id' => $user_id,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes') {
                    $product_name = strtolower($product->get_name());
                    if (strpos($product_name, 'pro') !== false) {
                        $tier = 'pro';
                    } elseif (strpos($product_name, 'plus') !== false) {
                        $tier = 'plus';
                    }
                    break 2;
                }
            }
        }
    }
    
    return $tier;
}

function courscribe_user_has_studio($user_id) {
    $studio_query = new WP_Query([
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    
    return $studio_query->have_posts();
}

function courscribe_get_onboarding_progress($user_id) {
    $step = get_user_meta($user_id, '_courscribe_onboarding_step', true) ?: 'welcome';
    
    $progress_map = [
        'welcome' => 25,
        'pricing' => 50,
        'studio' => 75,
        'complete' => 100
    ];
    
    return $progress_map[$step] ?? 25;
}

function courscribe_render_premium_studio_form($user, $is_premium) {
    ob_start();
    ?>
    <form method="post" class="premium-studio-creation-form" id="premiumStudioForm">
        <?php wp_nonce_field('courscribe_create_studio', 'courscribe_studio_nonce'); ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="courscribe_studio_title">
                    <i class="fas fa-building"></i>
                    Studio Name <span class="required">*</span>
                </label>
                <input type="text" 
                       id="courscribe_studio_title" 
                       name="courscribe_studio_title" 
                       class="premium-input" 
                       placeholder="My Educational Studio"
                       required />
            </div>
            
            <div class="form-group">
                <label for="courscribe_studio_email">
                    <i class="fas fa-envelope"></i>
                    Contact Email <span class="required">*</span>
                </label>
                <input type="email" 
                       id="courscribe_studio_email" 
                       name="courscribe_studio_email" 
                       class="premium-input" 
                       placeholder="contact@mystudio.com"
                       value="<?php echo esc_attr($user->user_email); ?>"
                       required />
            </div>
        </div>
        
        <div class="form-group">
            <label for="courscribe_studio_description">
                <i class="fas fa-align-left"></i>
                Studio Description <span class="required">*</span>
            </label>
            <textarea id="courscribe_studio_description" 
                      name="courscribe_studio_description" 
                      class="premium-textarea" 
                      rows="4"
                      placeholder="Describe your studio's mission and the type of educational content you create..."
                      required></textarea>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="courscribe_studio_website">
                    <i class="fas fa-globe"></i>
                    Website (Optional)
                </label>
                <input type="url" 
                       id="courscribe_studio_website" 
                       name="courscribe_studio_website" 
                       class="premium-input" 
                       placeholder="https://mystudio.com" />
            </div>
            
            <div class="form-group">
                <label for="courscribe_studio_address">
                    <i class="fas fa-map-marker-alt"></i>
                    Location (Optional)
                </label>
                <input type="text" 
                       id="courscribe_studio_address" 
                       name="courscribe_studio_address" 
                       class="premium-input" 
                       placeholder="City, Country" />
            </div>
        </div>
        
        <?php if ($is_premium): ?>
            <div class="premium-options">
                <h4><i class="fas fa-crown"></i> Premium Features</h4>
                <div class="option-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_ai_generation" value="1" checked>
                        <span class="checkmark"></span>
                        Enable AI Content Generation
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_collaboration" value="1" checked>
                        <span class="checkmark"></span>
                        Enable Team Collaboration
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="enable_analytics" value="1" checked>
                        <span class="checkmark"></span>
                        Enable Advanced Analytics
                    </label>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" name="courscribe_submit_studio" class="btn-premium btn-large">
                <i class="fas fa-rocket"></i>
                Create My Studio
                <span class="btn-shine"></span>
            </button>
        </div>
    </form>
    
    
    
    <script>
    // Add form validation and enhancement
    jQuery(document).ready(function($) {
        $('#premiumStudioForm').on('submit', function(e) {
            // Add loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating Studio...');
            submitBtn.prop('disabled', true);
            
            // The form will submit normally, but we've added a loading state
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
?>