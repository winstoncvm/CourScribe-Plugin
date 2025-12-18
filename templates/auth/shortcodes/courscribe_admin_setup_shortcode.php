<?php
/**
 * CourScribe Premium Admin Setup Page
 * Personalized welcome experience for Toni to set up her admin account
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode for premium admin setup
add_shortcode('courscribe_admin_setup', 'courscribe_admin_setup_shortcode');

function courscribe_admin_setup_shortcode() {
    $site_url = home_url();
    
    // Check if admin setup is allowed
    $setup_allowed = get_option('courscribe_admin_setup_allowed', false);
    $setup_secret = get_option('courscribe_admin_setup_secret', '');
    
    // Check if secret key is provided in URL
    $provided_secret = isset($_GET['secret']) ? sanitize_text_field($_GET['secret']) : '';
    
    // if (!$setup_allowed || empty($setup_secret) || $provided_secret !== $setup_secret) {
    //     return '<div class="toni-access-denied">
    //                 <div class="toni-error-content">
    //                     <div class="toni-error-icon">
    //                         <i class="fas fa-lock"></i>
    //                     </div>
    //                     <h3>Access Restricted</h3>
    //                     <p>This is a private setup page with restricted access.</p>
    //                     <div class="toni-error-details">
    //                         <small>If you believe you should have access, please check your invitation link.</small>
    //                     </div>
    //                 </div>
    //             </div>';
    // }
    
    // Check if setup is already completed
    $setup_completed = get_option('courscribe_admin_setup_completed', false);
    // if ($setup_completed) {
    //     // Auto-login and redirect to studio creation
    //     return '<div class="toni-setup-complete">
    //                 <div class="toni-success-animation">
    //                     <div class="toni-checkmark">
    //                         <i class="fas fa-check"></i>
    //                     </div>
    //                 </div>
    //                 <h3>Welcome Back!</h3>
    //                 <p>Your CourScribe platform is already set up and ready to go.</p>
    //                 <div class="toni-action-buttons">
    //                     <a href="' . wp_login_url() . '" class="toni-btn toni-btn-primary">
    //                         <i class="fas fa-sign-in-alt"></i>
    //                         Sign In to Continue
    //                     </a>
    //                 </div>
    //             </div>';
    // }

    ob_start();

    $errors = [];
    $success_message = '';

    if (isset($_POST['courscribe_submit_admin_setup']) && 
        isset($_POST['courscribe_admin_setup_nonce']) && 
        wp_verify_nonce($_POST['courscribe_admin_setup_nonce'], 'courscribe_admin_setup')) {
        
        $username = sanitize_user($_POST['courscribe_username']);
        $email = sanitize_email($_POST['courscribe_email']);
        $password = $_POST['courscribe_password'];
        $password_confirm = $_POST['courscribe_password_confirm'];
        $first_name = sanitize_text_field($_POST['courscribe_first_name']);
        $last_name = sanitize_text_field($_POST['courscribe_last_name']);
        $site_title = sanitize_text_field($_POST['courscribe_site_title']);

        // Validate inputs
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

        if (empty($first_name)) {
            $errors[] = 'First name is required.';
        }

        if (empty($last_name)) {
            $errors[] = 'Last name is required.';
        }

        if (empty($site_title)) {
            $errors[] = 'Site title is required.';
        }

        if (empty($errors)) {
            // Create admin user
            $user_id = wp_create_user($username, $password, $email);

            if (!is_wp_error($user_id)) {
                // Update user details
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name . ' ' . $last_name,
                    'role' => 'administrator'
                ]);

                // Add studio_admin role to administrator so they can manage their own studio
                $user = new WP_User($user_id);
                $user->add_role('studio_admin');

                // Set user meta
                update_user_meta($user_id, '_courscribe_user_status', 'active');
                update_user_meta($user_id, '_courscribe_is_site_owner', true);
                update_user_meta($user_id, '_courscribe_registration_date', current_time('mysql'));

                // Update site title
                update_option('blogname', $site_title);
                update_option('courscribe_site_title', $site_title);

                // Mark setup as completed
                update_option('courscribe_admin_setup_completed', true);
                update_option('courscribe_admin_setup_allowed', false);

                // Initialize CourScribe roles
                courscribe_add_custom_roles();

                // Set up initial CourScribe options
                update_option('courscribe_tier', 'pro'); // Default to pro for site owners
                update_option('courscribe_setup_date', current_time('mysql'));

                // Auto-login the user and prepare for studio creation
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $username, get_user_by('ID', $user_id));

                // Store setup completion flag for redirect
                update_option('courscribe_admin_just_created', true);
                
                // Create success response with auto-redirect
                $success_message = 'account_created';
                
                // Log successful setup
                error_log('CourScribe: Admin setup completed for user: ' . $username . ' (ID: ' . $user_id . ') - Auto-login enabled');

            } else {
                $errors[] = 'Failed to create admin account: ' . $user_id->get_error_message();
            }
        }
    }

    ?>
    <!-- Premium Styles for Toni's Admin Setup -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap">

    <style>
        
     .welcome-wrapper{
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        width: 100%;
     }
        .main_title{
            display: none;
        }
        
        .toni-welcome-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 60px 50px;
            box-shadow: 
                0 32px 64px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            max-width: 550px;
            width: 100%;
            position: relative;
            z-index: 1;
            animation: toniSlideUp 0.8s ease-out;
        }
        
        @keyframes toniSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .toni-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .toni-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #F8923E 0%, #F25C3B 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 32px rgba(248, 146, 62, 0.3);
            animation: toniPulse 2s ease-in-out infinite;
        }
        
        @keyframes toniPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .toni-logo i {
            font-size: 32px;
            color: white;
        }
        
        .toni-title {
            font-family: 'Playfair Display', serif;
            color: #FFFFFF;
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        
        .toni-title .highlight {
            background: linear-gradient(135deg, #F8923E 0%, #F25C3B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .toni-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 8px;
        }
        
        .toni-personal-message {
            background: rgba(248, 146, 62, 0.1);
            border: 1px solid rgba(248, 146, 62, 0.2);
            border-radius: 12px;
            padding: 16px 20px;
            margin: 24px 0 32px;
            text-align: left;
        }
        
        .toni-personal-message .message-icon {
            color: #F8923E;
            font-size: 16px;
            margin-right: 8px;
        }
        
        .toni-personal-message p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Error and Success States */
        .toni-access-denied, .toni-setup-complete {
            text-align: center;
            padding: 80px 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 450px;
            margin: 0 auto;
        }
        
        .toni-error-icon, .toni-checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
            color: white;
        }
        
        .toni-error-icon {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        }
        
        .toni-checkmark {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            animation: toniCheckmark 0.6s ease-out;
        }
        
        @keyframes toniCheckmark {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        
        .toni-access-denied h3, .toni-setup-complete h3 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .toni-access-denied p, .toni-setup-complete p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 24px;
        }
        
        /* Form Styles */
        .toni-form {
            margin-top: 32px;
        }
        
        .toni-form-group {
            margin-bottom: 24px;
        }
        
        .toni-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .toni-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        
        .toni-label .required {
            color: #F8923E;
        }
        
        .toni-input {
            width: 100% !important;
            background: rgba(255, 255, 255, 0.05)!important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 12px !important;
            padding: 16px 18px !important;
            color: white !important;
            font-size: 16px !important;
            transition: all 0.3s ease !important;
            font-family: 'Inter', sans-serif !important;
        }
        
        .toni-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.08);
            border-color: #F8923E;
            box-shadow: 0 0 0 3px rgba(248, 146, 62, 0.15);
        }
        
        .toni-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .toni-password-group {
            position: relative;
        }
        
        .toni-password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .toni-password-toggle:hover {
            color: #F8923E;
        }
        
        .toni-btn {
            background: linear-gradient(135deg, #F8923E 0%, #F25C3B 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            padding: 18px 32px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            margin-top: 16px;
            text-decoration: none;
        }
        
        .toni-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(248, 146, 62, 0.4);
            color: white;
        }
        
        .toni-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .toni-btn.toni-btn-primary {
            background: linear-gradient(135deg, #F8923E 0%, #F25C3B 100%);
        }
        
        .toni-error-list {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .toni-error-list h4 {
            color: #EF4444;
            margin: 0 0 12px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .toni-error-list ul {
            margin: 0;
            padding-left: 20px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .toni-error-list li {
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .toni-success-redirect {
            text-align: center;
            padding: 40px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 16px;
            margin-top: 32px;
        }
        
        .toni-success-redirect h3 {
            color: #10B981;
            margin-bottom: 16px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .toni-success-redirect p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 24px;
        }
        
        .toni-loading-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .toni-welcome-container {
                padding: 40px 30px;
                margin: 20px;
            }
            
            .toni-title {
                font-size: 2.25rem;
            }
            
            .toni-form-row {
                grid-template-columns: 1fr;
                gap: 24px;
            }
        }
    </style>
    <section class="welcome-wrapper">
        <div class="toni-welcome-container">
            <div class="toni-header">
                <div class="toni-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="toni-title">Welcome, <span class="highlight">Toni!</span></h1>
                <p class="toni-subtitle">Let's set up your CourScribe platform</p>
                
                <div class="toni-personal-message">
                    <i class="fas fa-heart message-icon"></i>
                    <p>Your premium curriculum development platform is ready to be configured. This one-time setup will give you complete control over your educational content creation journey.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="toni-error-list">
                    <h4><i class="fas fa-exclamation-triangle"></i> Please correct the following:</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message === 'account_created'): ?>
                <div class="toni-success-redirect">
                    <div class="toni-checkmark">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3>Welcome to CourScribe, Toni!</h3>
                    <p>Your admin account has been created successfully. We're now redirecting you to create your first studio...</p>
                    <div class="toni-loading-spinner"></div>
                </div>
                
                <script>
                    // Auto-redirect to studio creation after showing success message
                    setTimeout(function() {
                        window.location.href = '<?php echo home_url('/create-studio'); ?>';
                    }, 3000);
                </script>
            <?php else: ?>
                <form id="toni-setup-form" method="post" class="toni-form">
                    <?php wp_nonce_field('courscribe_admin_setup', 'courscribe_admin_setup_nonce'); ?>
                    
                    <div class="toni-form-group">
                        <label for="courscribe_site_title" class="toni-label">
                            Platform Name <span class="required">*</span>
                        </label>
                        <input type="text" name="courscribe_site_title" id="courscribe_site_title" 
                            class="toni-input"
                            value="<?php echo esc_attr($_POST['courscribe_site_title'] ?? 'Toni\'s CourScribe Studio'); ?>" 
                            required placeholder="e.g., Toni's Education Hub">
                    </div>

                    <div class="toni-form-row">
                        <div class="toni-form-group">
                            <label for="courscribe_first_name" class="toni-label">
                                First Name <span class="required">*</span>
                            </label>
                            <input type="text" name="courscribe_first_name" id="courscribe_first_name" 
                                class="toni-input"
                                value="<?php echo esc_attr($_POST['courscribe_first_name'] ?? 'Toni'); ?>" 
                                required placeholder="Your first name">
                        </div>
                        <div class="toni-form-group">
                            <label for="courscribe_last_name" class="toni-label">
                                Last Name <span class="required">*</span>
                            </label>
                            <input type="text" name="courscribe_last_name" id="courscribe_last_name" 
                                class="toni-input"
                                value="<?php echo esc_attr($_POST['courscribe_last_name'] ?? ''); ?>" 
                                required placeholder="Your last name">
                        </div>
                    </div>

                    <div class="toni-form-group">
                        <label for="courscribe_username" class="toni-label">
                            Username <span class="required">*</span>
                        </label>
                        <input type="text" name="courscribe_username" id="courscribe_username" 
                            class="toni-input"
                            value="<?php echo esc_attr($_POST['courscribe_username'] ?? 'toni'); ?>" 
                            required placeholder="Choose a username">
                    </div>

                    <div class="toni-form-group">
                        <label for="courscribe_email" class="toni-label">
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" name="courscribe_email" id="courscribe_email" 
                            class="toni-input"
                            value="<?php echo esc_attr($_POST['courscribe_email'] ?? ''); ?>" 
                            required placeholder="toni@example.com">
                    </div>

                    <div class="toni-form-group">
                        <label for="courscribe_password" class="toni-label">
                            Password <span class="required">*</span>
                        </label>
                        <div class="toni-password-group">
                            <input type="password" name="courscribe_password" id="courscribe_password" 
                                class="toni-input"
                                required placeholder="Create a secure password (min 8 characters)">
                            <span class="toni-password-toggle" onclick="toggleToniPassword('courscribe_password', this)">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="toni-form-group">
                        <label for="courscribe_password_confirm" class="toni-label">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <div class="toni-password-group">
                            <input type="password" name="courscribe_password_confirm" id="courscribe_password_confirm" 
                                class="toni-input"
                                required placeholder="Confirm your password">
                            <span class="toni-password-toggle" onclick="toggleToniPassword('courscribe_password_confirm', this)">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <button class="toni-btn toni-btn-primary" type="submit" name="courscribe_submit_admin_setup" id="toni-submit-btn">
                        <i class="fas fa-rocket"></i>
                        Create My CourScribe Platform
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>
    <script>
        // Enhanced password toggle for Toni's premium experience
        function toggleToniPassword(inputId, toggleElement) {
            const input = document.getElementById(inputId);
            const icon = toggleElement.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa fa-eye-slash';
                toggleElement.style.color = '#F8923E';
            } else {
                input.type = 'password';
                icon.className = 'fa fa-eye';
                toggleElement.style.color = 'rgba(255, 255, 255, 0.5)';
            }
        }

        // Premium form validation and user experience
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('toni-setup-form');
            const submitBtn = document.getElementById('toni-submit-btn');
            
            if (!form || !submitBtn) return;

            // Add real-time validation
            const inputs = form.querySelectorAll('.toni-input');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });

            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('courscribe_password').value;
                const passwordConfirm = document.getElementById('courscribe_password_confirm').value;
                
                // Clear previous errors
                clearErrors();
                
                let isValid = true;
                let errors = [];
                
                if (password !== passwordConfirm) {
                    errors.push('Passwords do not match');
                    isValid = false;
                }
                
                if (password.length < 8) {
                    errors.push('Password must be at least 8 characters long');
                    isValid = false;
                }
                
                // Validate all required fields
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showErrors(errors);
                    return false;
                }
                
                // Show loading state
                showLoadingState();
            });
            
            function validateField(input) {
                const value = input.value.trim();
                const isRequired = input.hasAttribute('required');
                
                if (isRequired && !value) {
                    input.classList.add('error');
                    input.style.borderColor = '#EF4444';
                    return false;
                } else {
                    input.classList.remove('error');
                    input.style.borderColor = 'rgba(255, 255, 255, 0.15)';
                    return true;
                }
            }
            
            function clearErrors() {
                inputs.forEach(input => {
                    input.classList.remove('error');
                    input.style.borderColor = 'rgba(255, 255, 255, 0.15)';
                });
            }
            
            function showErrors(errors) {
                if (errors.length > 0) {
                    // Create error notification
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'toni-error-notification';
                    errorDiv.innerHTML = `
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                            <div style="color: #EF4444; font-weight: 600; margin-bottom: 8px;">
                                <i class="fas fa-exclamation-triangle"></i> Please check the following:
                            </div>
                            <ul style="margin: 0; padding-left: 20px; color: rgba(255, 255, 255, 0.9);">
                                ${errors.map(error => `<li style="margin-bottom: 4px;">${error}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                    
                    // Insert before form
                    form.parentNode.insertBefore(errorDiv, form);
                    
                    // Remove after 5 seconds
                    setTimeout(() => {
                        errorDiv.remove();
                    }, 5000);
                }
            }
            
            function showLoadingState() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <div class="toni-loading-spinner" style="width: 20px; height: 20px; margin-right: 8px;"></div>
                    Creating Your Platform...
                `;
                
                // Add visual feedback
                submitBtn.style.transform = 'none';
                submitBtn.style.opacity = '0.8';
            }
        });

        // Add some premium polish with entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.toni-welcome-container');
            if (container) {
                setTimeout(() => {
                    container.style.transform = 'translateY(0)';
                    container.style.opacity = '1';
                }, 100);
            }
        });
    </script>

    <?php
    return ob_get_clean();
}

/**
 * Generate a secret key for admin setup
 */
function courscribe_generate_admin_setup_secret() {
    return wp_generate_password(32, false);
}

/**
 * Enable admin setup mode
 */
function courscribe_enable_admin_setup() {
    $secret = courscribe_generate_admin_setup_secret();
    update_option('courscribe_admin_setup_allowed', true);
    update_option('courscribe_admin_setup_secret', $secret);
    update_option('courscribe_admin_setup_completed', false);
    
    return home_url('/courscribe-admin-setup?secret=' . $secret);
}

/**
 * WordPress admin action to generate the setup URL for Toni
 */
add_action('wp_ajax_courscribe_generate_admin_setup_url', 'courscribe_ajax_generate_setup_url');
add_action('wp_ajax_nopriv_courscribe_generate_admin_setup_url', 'courscribe_ajax_generate_setup_url');
function courscribe_ajax_generate_setup_url() {
    // For security, only allow this if no admin setup has been completed
    if (get_option('courscribe_admin_setup_completed', false)) {
        wp_send_json_error(['message' => 'Admin setup has already been completed.']);
        wp_die();
    }
    
    $setup_url = courscribe_enable_admin_setup();
    wp_send_json_success([
        'message' => 'Admin setup URL generated successfully!',
        'setup_url' => $setup_url,
        'instructions' => 'This URL is for Toni\'s one-time admin account setup. Share it securely and delete the page after use.'
    ]);
    wp_die();
}

/**
 * Easy function to call from WordPress admin or functions.php
 */
function courscribe_get_toni_setup_url() {
    // Check if setup is already completed
    if (get_option('courscribe_admin_setup_completed', false)) {
        return 'Admin setup has already been completed.';
    }
    
    $setup_url = courscribe_enable_admin_setup();
    
    // Log the URL generation for security audit
    error_log('CourScribe: Admin setup URL generated for Toni - ' . $setup_url);
    
    return $setup_url;
}