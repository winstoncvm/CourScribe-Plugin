<?php
/**
 * Enhanced Authentication Handlers for CourScribe
 * Supports username OR email login, improved security, and premium auth system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced AJAX handler for sign in - supports username OR email
 */
add_action('wp_ajax_courscribe_enhanced_signin', 'courscribe_enhanced_signin_handler');
add_action('wp_ajax_nopriv_courscribe_enhanced_signin', 'courscribe_enhanced_signin_handler');

function courscribe_enhanced_signin_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['courscribe_signin_nonce'], 'courscribe_signin')) {
        wp_send_json_error(['message' => 'Security validation failed. Please refresh and try again.']);
        return;
    }

    // Sanitize inputs
    $login = sanitize_text_field($_POST['courscribe_login']); // Can be username or email
    $password = $_POST['courscribe_password'];
    $remember = isset($_POST['courscribe_remember']) ? true : false;

    // Validate inputs
    if (empty($login) || empty($password)) {
        wp_send_json_error(['message' => 'Username/email and password are required.']);
        return;
    }

    // Determine if login is email or username
    $user_data = null;
    if (is_email($login)) {
        // Login with email
        $user_data = get_user_by('email', $login);
        if (!$user_data) {
            wp_send_json_error(['message' => 'Invalid email or password.']);
            return;
        }
        $username = $user_data->user_login;
    } else {
        // Login with username
        $user_data = get_user_by('login', $login);
        if (!$user_data) {
            wp_send_json_error(['message' => 'Invalid username or password.']);
            return;
        }
        $username = $login;
    }

    // Enhanced security: check if user is active
    $user_status = get_user_meta($user_data->ID, '_courscribe_user_status', true);
    if ($user_status === 'suspended' || $user_status === 'banned') {
        wp_send_json_error(['message' => 'Your account has been suspended. Please contact support.']);
        return;
    }

    // Attempt login
    $credentials = [
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember,
    ];

    $user = wp_signon($credentials, is_ssl());

    if (is_wp_error($user)) {
        // Log failed login attempt
        error_log('CourScribe: Failed login attempt for user: ' . $login . ' from IP: ' . $_SERVER['REMOTE_ADDR']);
        
        // Generic error message for security
        wp_send_json_error(['message' => 'Invalid username/email or password.']);
        return;
    }

    // Log successful login
    error_log('CourScribe: Successful login for user: ' . $user->user_login . ' (ID: ' . $user->ID . ')');
    
    // Update last login time
    update_user_meta($user->ID, '_courscribe_last_login', current_time('mysql'));

    // Check user role and redirect accordingly
    $redirect_url = courscribe_get_user_redirect_url($user);

    wp_send_json_success([
        'message' => 'Login successful! Redirecting...',
        'redirect_url' => $redirect_url,
        'user' => [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles
        ]
    ]);
}

/**
 * Get appropriate redirect URL based on user role
 */
function courscribe_get_user_redirect_url($user) {
    if (in_array('administrator', $user->roles)) {
        return admin_url('admin.php?page=courscribe');
    } elseif (in_array('studio_admin', $user->roles)) {
        return home_url('/studio');
    } elseif (in_array('collaborator', $user->roles)) {
        return home_url('/studio');
    } elseif (in_array('client', $user->roles)) {
        return home_url('/my-curriculums');
    }
    
    // Default redirect for other roles
    return home_url('/studio');
}

/**
 * Enhanced user registration handler with improved security
 */
add_action('wp_ajax_courscribe_enhanced_register', 'courscribe_enhanced_register_handler');
add_action('wp_ajax_nopriv_courscribe_enhanced_register', 'courscribe_enhanced_register_handler');

function courscribe_enhanced_register_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['courscribe_register_nonce'], 'courscribe_register')) {
        wp_send_json_error(['message' => 'Security validation failed. Please refresh and try again.']);
        return;
    }

    // Sanitize inputs
    $username = sanitize_user($_POST['courscribe_username']);
    $email = sanitize_email($_POST['courscribe_email']);
    $password = $_POST['courscribe_password'];
    $password_confirm = $_POST['courscribe_password_confirm'];
    $first_name = sanitize_text_field($_POST['courscribe_first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['courscribe_last_name'] ?? '');

    // Validate inputs
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (username_exists($username)) {
        $errors[] = 'Username already exists.';
    } elseif (!validate_username($username)) {
        $errors[] = 'Username contains invalid characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!is_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (email_exists($email)) {
        $errors[] = 'Email address is already registered.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!empty($errors)) {
        wp_send_json_error(['message' => implode(' ', $errors)]);
        return;
    }

    // Create user
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Registration failed: ' . $user_id->get_error_message()]);
        return;
    }

    // Update user meta
    if (!empty($first_name)) {
        update_user_meta($user_id, 'first_name', $first_name);
    }
    if (!empty($last_name)) {
        update_user_meta($user_id, 'last_name', $last_name);
    }

    // Set default role (will be studio_admin for new registrations)
    $user = new WP_User($user_id);
    $user->set_role('studio_admin');

    // Set user status as active
    update_user_meta($user_id, '_courscribe_user_status', 'active');
    update_user_meta($user_id, '_courscribe_registration_date', current_time('mysql'));
    
    // Initialize onboarding flow for new users
    update_user_meta($user_id, '_courscribe_onboarding_step', 'welcome');
    update_user_meta($user_id, '_courscribe_first_login', 'pending');
    update_user_meta($user_id, '_courscribe_user_tier', 'basics');

    // Log successful registration
    error_log('CourScribe: New user registered - Username: ' . $username . ' Email: ' . $email . ' ID: ' . $user_id);

    // Auto-login the user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    wp_send_json_success([
        'message' => 'Registration successful! Welcome to CourScribe!',
        'redirect_url' => home_url('/studio'),
        'user' => [
            'id' => $user_id,
            'username' => $username,
            'email' => $email,
            'display_name' => $first_name . ' ' . $last_name
        ]
    ]);
}

/**
 * Password reset request handler
 */
add_action('wp_ajax_courscribe_reset_password', 'courscribe_reset_password_handler');
add_action('wp_ajax_nopriv_courscribe_reset_password', 'courscribe_reset_password_handler');

function courscribe_reset_password_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['courscribe_reset_nonce'], 'courscribe_reset_password')) {
        wp_send_json_error(['message' => 'Security validation failed. Please refresh and try again.']);
        return;
    }

    $login = sanitize_text_field($_POST['courscribe_login']);

    if (empty($login)) {
        wp_send_json_error(['message' => 'Username or email is required.']);
        return;
    }

    // Find user by email or username
    $user_data = null;
    if (is_email($login)) {
        $user_data = get_user_by('email', $login);
    } else {
        $user_data = get_user_by('login', $login);
    }

    if (!$user_data) {
        // For security, don't reveal if user exists
        wp_send_json_success(['message' => 'If an account with that username/email exists, you will receive a password reset email.']);
        return;
    }

    // Generate reset key
    $reset_key = get_password_reset_key($user_data);

    if (is_wp_error($reset_key)) {
        wp_send_json_error(['message' => 'Unable to generate reset key. Please try again.']);
        return;
    }

    // Send reset email (WordPress handles this)
    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user_data->user_login), 'login');
    
    // Custom email content
    $message = "Hello " . $user_data->display_name . ",\n\n";
    $message .= "You have requested a password reset for your CourScribe account.\n\n";
    $message .= "Click the following link to reset your password:\n";
    $message .= $reset_url . "\n\n";
    $message .= "If you did not request this, please ignore this email.\n\n";
    $message .= "Best regards,\nThe CourScribe Team";

    $subject = "CourScribe - Password Reset Request";
    
    wp_mail($user_data->user_email, $subject, $message);

    wp_send_json_success(['message' => 'If an account with that username/email exists, you will receive a password reset email.']);
}

/**
 * User logout handler
 */
add_action('wp_ajax_courscribe_logout', 'courscribe_logout_handler');

function courscribe_logout_handler() {
    wp_logout();
    wp_send_json_success(['message' => 'Logged out successfully', 'redirect_url' => home_url()]);
}