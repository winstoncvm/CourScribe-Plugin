<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CourScribe Asset Management
 * Handles enqueuing of CSS and JavaScript files
 */

function courscribe_enqueue_assets() {
    $plugin_url = plugin_dir_url(__DIR__);
    $version = '1.1.9';
    
    // Get current post and check if it's a studio page or onboarding page
    global $post;
    $is_studio_page = ($post && $post->post_type === 'crscribe_studio') || 
                      has_shortcode(get_post()->post_content ?? '', 'courscribe_premium_studio') ||
                      has_shortcode(get_post()->post_content ?? '', 'courscribe_curriculum_manager');
                      
    $is_onboarding_page = has_shortcode(get_post()->post_content ?? '', 'courscribe_welcome') ||
                          has_shortcode(get_post()->post_content ?? '', 'courscribe_create_studio') ||
                          has_shortcode(get_post()->post_content ?? '', 'courscribe_select_tribe');
    
    // Enqueue premium onboarding assets
    if ($is_onboarding_page) {
        wp_enqueue_style(
            'courscribe-onboarding-premium',
            $plugin_url . 'assets/css/onboarding-premium.css',
            array(),
            $version
        );
        
        wp_enqueue_script(
            'courscribe-onboarding-premium',
            $plugin_url . 'assets/js/courscribe/premium-onboarding.js',
            array('jquery'),
            $version,
            true
        );
    }
    
    // Enqueue premium studio assets
    if ($is_studio_page) {
        // Premium Studio CSS
        wp_enqueue_style(
            'courscribe-studio-premium',
            $plugin_url . 'assets/css/studio-premium.css',
            array(),
            $version
        );
        
        // Premium Tour System
        wp_enqueue_script(
            'courscribe-premium-tour',
            $plugin_url . 'assets/js/courscribe/premium-tour.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localize script with user data
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $user_tier = courscribe_get_user_tier($user_id);
            
            wp_localize_script('courscribe-premium-tour', 'courscribeTourData', array(
                'userTier' => $user_tier,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('courscribe_tour'),
                'isPremium' => in_array($user_tier, ['plus', 'pro'])
            ));
        }
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Premium Studio JavaScript
        wp_enqueue_script(
            'courscribe-studio-premium',
            $plugin_url . 'assets/js/studio-premium.js',
            array('jquery', 'chartjs'),
            $version,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('courscribe-studio-premium', 'courscribeAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_ajax_nonce'),
            'currentUserId' => get_current_user_id(),
            'studioId' => $post ? $post->ID : 0,
            'siteUrl' => home_url(),
            'pluginUrl' => $plugin_url,
            'tier' => get_option('courscribe_tier', 'basics'),
            'strings' => array(
                'loading' => __('Loading...', 'courscribe'),
                'error' => __('An error occurred', 'courscribe'),
                'success' => __('Success!', 'courscribe'),
                'confirm' => __('Are you sure?', 'courscribe'),
                'cancel' => __('Cancel', 'courscribe'),
                'save' => __('Save', 'courscribe'),
                'delete' => __('Delete', 'courscribe')
            )
        ));
        
        // Add premium fonts
        wp_enqueue_style(
            'courscribe-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            array(),
            null
        );
        
        // FontAwesome for icons
        wp_enqueue_style(
            'courscribe-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
    }
    
    // Enqueue dashboard assets for admin pages
    if (is_admin() && (get_current_screen()->id ?? '') === 'crscribe_studio') {
        wp_enqueue_style(
            'courscribe-studio-dashboard-premium',
            $plugin_url . 'assets/css/studio-dashboard-premium.css',
            array(),
            $version
        );
    }
    
    // Enqueue general courscribe assets for any courscribe-related pages
    if (is_singular(array('crscribe_studio', 'crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'))) {
        wp_enqueue_style(
            'courscribe-general',
            $plugin_url . 'assets/css/courscribe-general.css',
            array(),
            $version
        );
        
        wp_enqueue_script(
            'courscribe-general',
            $plugin_url . 'assets/js/courscribe-general.js',
            array('jquery'),
            $version,
            true
        );
    }
}

// Hook into wp_enqueue_scripts
add_action('wp_enqueue_scripts', 'courscribe_enqueue_assets');

/**
 * Enqueue admin assets
 */
function courscribe_admin_enqueue_assets($hook) {
    $plugin_url = plugin_dir_url(__DIR__);
    $version = '1.1.9';
    
    // Only load on CourScribe admin pages
    if (strpos($hook, 'courscribe') !== false || 
        in_array(get_current_screen()->post_type ?? '', array('crscribe_studio', 'crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'))) {
        
        // Admin dashboard CSS
        wp_enqueue_style(
            'courscribe-admin',
            $plugin_url . 'assets/css/courscribe-admin.css',
            array(),
            $version
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'courscribe-admin',
            $plugin_url . 'assets/js/courscribe-admin.js',
            array('jquery'),
            $version,
            true
        );
        
        // Localize admin script
        wp_localize_script('courscribe-admin', 'courscribeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('courscribe_admin_nonce'),
            'pluginUrl' => $plugin_url
        ));
    }
}

// Hook into admin_enqueue_scripts
add_action('admin_enqueue_scripts', 'courscribe_admin_enqueue_assets');

/**
 * Add inline styles for custom CSS variables
 */
function courscribe_add_inline_styles() {
    // Get current theme colors or use defaults
    $primary_color = get_theme_mod('courscribe_primary_color', '#E4B26F');
    $secondary_color = get_theme_mod('courscribe_secondary_color', '#F8923E');
    
    $custom_css = "
        :root {
            --courscribe-primary: {$primary_color};
            --courscribe-secondary: {$secondary_color};
        }
    ";
    
    wp_add_inline_style('courscribe-studio-premium', $custom_css);
}

// Hook to add inline styles
add_action('wp_enqueue_scripts', 'courscribe_add_inline_styles', 20);

/**
 * Preload critical resources
 */
function courscribe_preload_resources() {
    if (is_singular('crscribe_studio') || has_shortcode(get_post()->post_content ?? '', 'courscribe_premium_studio')) {
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
        echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
        echo '<link rel="preload" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" as="script">' . "\n";
    }
}

// Hook to add preload links
add_action('wp_head', 'courscribe_preload_resources', 1);

/**
 * Add critical CSS inline for better performance
 */
function courscribe_add_critical_css() {
    if (is_singular('crscribe_studio') || has_shortcode(get_post()->post_content ?? '', 'courscribe_premium_studio')) {
        echo '<style id="courscribe-critical-css">
            .courscribe-studio-premium{font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#0F0F23 0%,#1A1A2E 100%);color:#FFFFFF;min-height:100vh;line-height:1.6}
            .studio-header{background:rgba(22,36,71,0.9);backdrop-filter:blur(20px);border-bottom:1px solid rgba(228,178,111,0.2);padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
            .loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,15,35,0.9);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:2000}
        </style>' . "\n";
    }
}

// Hook to add critical CSS
add_action('wp_head', 'courscribe_add_critical_css', 2);

/**
 * Remove unused CSS and JS to improve performance
 */
function courscribe_optimize_assets() {
    if (is_singular('crscribe_studio') || has_shortcode(get_post()->post_content ?? '', 'courscribe_premium_studio')) {
        // Remove theme styles that might conflict
        wp_dequeue_style('divi-style');
        wp_dequeue_style('et-builder-modules-style');
        
        // Remove unnecessary scripts
        wp_dequeue_script('divi-custom-script');
        wp_dequeue_script('et_monarch');
    }
}

// Hook to optimize assets
add_action('wp_enqueue_scripts', 'courscribe_optimize_assets', 100);

/**
 * Add Schema markup for studio pages
 */
function courscribe_add_schema_markup() {
    if (is_singular('crscribe_studio')) {
        global $post;
        $studio_email = get_post_meta($post->ID, '_studio_email', true);
        $studio_website = get_post_meta($post->ID, '_studio_website', true);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: get_the_content(),
            'url' => get_permalink(),
        );
        
        if ($studio_email) {
            $schema['email'] = $studio_email;
        }
        
        if ($studio_website) {
            $schema['sameAs'] = $studio_website;
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}

// Hook to add schema markup
add_action('wp_head', 'courscribe_add_schema_markup');

/**
 * Add security headers for premium studio pages
 */
function courscribe_add_security_headers() {
    if (is_singular('crscribe_studio') || has_shortcode(get_post()->post_content ?? '', 'courscribe_premium_studio')) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

// Hook to add security headers
add_action('send_headers', 'courscribe_add_security_headers');
?>