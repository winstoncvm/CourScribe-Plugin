<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// AJAX handlers for premium onboarding
add_action('wp_ajax_courscribe_update_onboarding_step', 'courscribe_update_onboarding_step_handler');
add_action('wp_ajax_courscribe_set_tier', 'courscribe_set_tier_handler');
add_action('wp_ajax_courscribe_complete_onboarding', 'courscribe_complete_onboarding_handler');

/**
 * Update onboarding step
 */
function courscribe_update_onboarding_step_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_onboarding')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    $user_id = get_current_user_id();
    $step = sanitize_text_field($_POST['step'] ?? '');
    
    $valid_steps = ['welcome', 'pricing', 'studio', 'complete'];
    if (!in_array($step, $valid_steps)) {
        wp_send_json_error(['message' => 'Invalid step.']);
    }

    // Update onboarding step
    update_user_meta($user_id, '_courscribe_onboarding_step', $step);
    
    // Log the step change
    error_log("CourScribe: User {$user_id} moved to onboarding step: {$step}");

    wp_send_json_success(['message' => 'Onboarding step updated.', 'step' => $step]);
}

/**
 * Set user tier (for basics tier selection)
 */
function courscribe_set_tier_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_onboarding')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    $user_id = get_current_user_id();
    $tier = sanitize_text_field($_POST['tier'] ?? '');
    
    if ($tier === 'basics') {
        // Mark tribe as selected for basics tier
        update_user_meta($user_id, '_courscribe_tribe_selected', 'basics');
        
        // Log the tier selection
        error_log("CourScribe: User {$user_id} selected basics tier via onboarding");
        
        wp_send_json_success(['message' => 'Tier set to basics.']);
    } else {
        wp_send_json_error(['message' => 'Invalid tier.']);
    }
}

/**
 * Complete onboarding process
 */
function courscribe_complete_onboarding_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_onboarding')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    $user_id = get_current_user_id();
    
    // Mark onboarding as complete
    update_user_meta($user_id, '_courscribe_onboarding_step', 'complete');
    update_user_meta($user_id, '_courscribe_first_login', 'completed');
    update_user_meta($user_id, '_courscribe_onboarding_completed', current_time('mysql'));
    
    // Check if user has premium features and set appropriate flags
    $user_tier = courscribe_get_user_tier($user_id);
    if (in_array($user_tier, ['plus', 'pro'])) {
        update_user_meta($user_id, '_courscribe_premium_onboarding_completed', current_time('mysql'));
    }
    
    // Log completion
    error_log("CourScribe: User {$user_id} completed premium onboarding with tier: {$user_tier}");

    wp_send_json_success([
        'message' => 'Onboarding completed successfully!',
        'tier' => $user_tier,
        'redirect_url' => home_url('/studio/')
    ]);
}

/**
 * Enhanced login redirect for premium onboarding
 */
add_filter('login_redirect', 'courscribe_premium_login_redirect', 5, 3);

function courscribe_premium_login_redirect($redirect_to, $request, $user) {
    // Only handle studio_admin users
    if (!isset($user->roles) || !is_object($user) || !in_array('studio_admin', $user->roles)) {
        return $redirect_to;
    }

    $user_id = $user->ID;
    $onboarding_step = get_user_meta($user_id, '_courscribe_onboarding_step', true);
    $first_login = get_user_meta($user_id, '_courscribe_first_login', true);
    $has_studio = courscribe_user_has_studio($user_id);
    $tribe_selected = get_user_meta($user_id, '_courscribe_tribe_selected', true);

    error_log("CourScribe: Premium login redirect - User {$user_id}, Step: {$onboarding_step}, First login: {$first_login}, Has studio: " . ($has_studio ? 'yes' : 'no') . ", Tribe selected: " . ($tribe_selected ? 'yes' : 'no'));

    // For first-time users or incomplete onboarding, redirect to welcome page
    if ($first_login !== 'completed' || (!$onboarding_step && !$has_studio)) {
        $welcome_page = get_option('courscribe_welcome_page');
        if ($welcome_page) {
            $welcome_url = get_permalink($welcome_page);
            if ($welcome_url) {
                // Set initial onboarding step if not set
                if (!$onboarding_step) {
                    update_user_meta($user_id, '_courscribe_onboarding_step', 'welcome');
                }
                
                error_log("CourScribe: Redirecting user {$user_id} to premium welcome page: {$welcome_url}");
                return $welcome_url;
            }
        }
    }

    // For users in the middle of onboarding, redirect to welcome page
    if (in_array($onboarding_step, ['welcome', 'pricing', 'studio']) && $onboarding_step !== 'complete') {
        $welcome_page = get_option('courscribe_welcome_page');
        if ($welcome_page) {
            $welcome_url = get_permalink($welcome_page);
            if ($welcome_url) {
                error_log("CourScribe: Redirecting user {$user_id} to continue onboarding at step: {$onboarding_step}");
                return $welcome_url;
            }
        }
    }

    // Default to studio page for completed onboarding
    $studio_page = get_option('courscribe_studio_page');
    if ($studio_page) {
        $studio_url = get_permalink($studio_page);
        if ($studio_url) {
            error_log("CourScribe: Redirecting user {$user_id} to studio page (onboarding complete)");
            return $studio_url;
        }
    }

    return $redirect_to;
}

/**
 * Enhanced studio creation with onboarding integration
 */
add_action('wp_ajax_courscribe_create_studio_onboarding', 'courscribe_create_studio_onboarding_handler');

function courscribe_create_studio_onboarding_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_create_studio')) {
        wp_send_json_error(['message' => 'Security verification failed.']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    $current_user = wp_get_current_user();

    // Check if user can create studio
    if (!in_array('studio_admin', $current_user->roles) && !current_user_can('publish_crscribe_studios')) {
        wp_send_json_error(['message' => 'You do not have permission to create studios.']);
    }

    // Check if user already has a studio
    if (courscribe_user_has_studio($current_user->ID)) {
        wp_send_json_error(['message' => 'You already have a studio.']);
    }

    // Validate and sanitize form data
    $form_data = courscribe_validate_studio_form_data($_POST);
    if (is_wp_error($form_data)) {
        wp_send_json_error(['message' => $form_data->get_error_message()]);
    }

    // Create studio post
    $post_data = [
        'post_title' => $form_data['title'],
        'post_content' => $form_data['description'],
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'post_author' => $current_user->ID,
    ];

    $studio_id = wp_insert_post($post_data, true);
    if (is_wp_error($studio_id)) {
        wp_send_json_error(['message' => 'Failed to create studio: ' . $studio_id->get_error_message()]);
    }

    // Save studio meta data
    courscribe_save_studio_meta_data($studio_id, $form_data);

    // Associate studio with user
    update_user_meta($current_user->ID, 'courscribe_studio_id', $studio_id);

    // Update onboarding progress
    update_user_meta($current_user->ID, '_courscribe_onboarding_step', 'complete');
    update_user_meta($current_user->ID, '_courscribe_first_login', 'completed');

    // Get user's tier for success message
    $user_tier = courscribe_get_user_tier($current_user->ID);
    $is_premium = in_array($user_tier, ['plus', 'pro']);

    error_log("CourScribe: Studio {$studio_id} created via onboarding for user {$current_user->ID} (tier: {$user_tier})");

    wp_send_json_success([
        'message' => 'Studio created successfully!',
        'studio_id' => $studio_id,
        'is_premium' => $is_premium,
        'tier' => $user_tier,
        'redirect_url' => home_url('/welcome/?step=complete')
    ]);
}

/**
 * Validate studio form data
 */
function courscribe_validate_studio_form_data($post_data) {
    $data = [
        'title' => sanitize_text_field($post_data['courscribe_studio_title'] ?? ''),
        'description' => wp_kses_post($post_data['courscribe_studio_description'] ?? ''),
        'email' => sanitize_email($post_data['courscribe_studio_email'] ?? ''),
        'website' => esc_url_raw($post_data['courscribe_studio_website'] ?? ''),
        'address' => sanitize_text_field($post_data['courscribe_studio_address'] ?? ''),
        'enable_ai_generation' => isset($post_data['enable_ai_generation']) ? 1 : 0,
        'enable_collaboration' => isset($post_data['enable_collaboration']) ? 1 : 0,
        'enable_analytics' => isset($post_data['enable_analytics']) ? 1 : 0,
    ];

    $errors = [];

    if (empty($data['title'])) {
        $errors[] = 'Studio name is required.';
    }

    if (empty($data['description'])) {
        $errors[] = 'Studio description is required.';
    }

    if (empty($data['email'])) {
        $errors[] = 'Contact email is required.';
    } elseif (!is_email($data['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL.';
    }

    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode(' ', $errors));
    }

    return $data;
}

/**
 * Save studio meta data
 */
function courscribe_save_studio_meta_data($studio_id, $form_data) {
    update_post_meta($studio_id, '_studio_email', $form_data['email']);
    update_post_meta($studio_id, '_studio_website', $form_data['website']);
    update_post_meta($studio_id, '_studio_address', $form_data['address']);
    
    // Save premium options
    update_post_meta($studio_id, '_enable_ai_generation', $form_data['enable_ai_generation']);
    update_post_meta($studio_id, '_enable_collaboration', $form_data['enable_collaboration']);
    update_post_meta($studio_id, '_enable_analytics', $form_data['enable_analytics']);
    
    // Save creation timestamp
    update_post_meta($studio_id, '_studio_created_via_onboarding', current_time('mysql'));
}

/**
 * Add admin notice for onboarding completion
 */
add_action('admin_notices', 'courscribe_onboarding_completion_notice');

function courscribe_onboarding_completion_notice() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    $user_id = get_current_user_id();
    $onboarding_completed = get_user_meta($user_id, '_courscribe_onboarding_completed', true);
    $notice_dismissed = get_user_meta($user_id, '_courscribe_onboarding_notice_dismissed', true);

    if ($onboarding_completed && !$notice_dismissed) {
        $user_tier = courscribe_get_user_tier($user_id);
        $is_premium = in_array($user_tier, ['plus', 'pro']);
        
        ?>
        <div class="notice notice-success is-dismissible courscribe-onboarding-notice">
            <p>
                <strong>🎉 Welcome to CourScribe!</strong>
                <?php if ($is_premium): ?>
                    Your premium studio has been set up successfully. 
                    <a href="<?php echo home_url('/studio/'); ?>">Start creating your first curriculum</a> with AI-powered tools.
                <?php else: ?>
                    Your studio has been created successfully. 
                    <a href="<?php echo home_url('/studio/'); ?>">Start building your curriculum</a> or 
                    <a href="<?php echo home_url('/select-tribe/'); ?>">upgrade to unlock premium features</a>.
                <?php endif; ?>
            </p>
        </div>
        <script>
        jQuery(document).on('click', '.courscribe-onboarding-notice .notice-dismiss', function() {
            jQuery.post(ajaxurl, {
                action: 'courscribe_dismiss_onboarding_notice',
                nonce: '<?php echo wp_create_nonce('courscribe_dismiss_notice'); ?>'
            });
        });
        </script>
        <?php
    }
}

/**
 * Dismiss onboarding notice
 */
add_action('wp_ajax_courscribe_dismiss_onboarding_notice', 'courscribe_dismiss_onboarding_notice_handler');

function courscribe_dismiss_onboarding_notice_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_dismiss_notice')) {
        wp_die('Security check failed');
    }

    $user_id = get_current_user_id();
    update_user_meta($user_id, '_courscribe_onboarding_notice_dismissed', 1);
    
    wp_send_json_success();
}

/**
 * Register onboarding analytics
 */
add_action('wp_footer', 'courscribe_onboarding_analytics');

function courscribe_onboarding_analytics() {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $onboarding_step = get_user_meta($user_id, '_courscribe_onboarding_step', true);
    
    if ($onboarding_step && is_page('welcome')) {
        ?>
        <script>
        // Track onboarding step view
        if (typeof gtag !== 'undefined') {
            gtag('event', 'onboarding_step_view', {
                'custom_parameter': '<?php echo esc_js($onboarding_step); ?>',
                'user_tier': '<?php echo esc_js(courscribe_get_user_tier($user_id)); ?>'
            });
        }
        </script>
        <?php
    }
}
?>