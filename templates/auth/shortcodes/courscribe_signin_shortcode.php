<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode for sign-in
add_shortcode( 'courscribe_signin', 'courscribe_signin_shortcode' );

function courscribe_signin_shortcode() {
    $site_url = home_url();
    if ( is_user_logged_in() ) {
        //return courscribe_retro_tv_error("You are already logged in.");
        wp_safe_redirect( home_url( '/studio' ) );
        exit;
    }

    ob_start();

    $errors = [];
    $prefill_login = isset( $_GET['email'] ) ? sanitize_text_field( urldecode( $_GET['email'] ) ) : '';
    $prefill_login = isset( $_GET['username'] ) ? sanitize_text_field( urldecode( $_GET['username'] ) ) : $prefill_login;

    if ( isset( $_POST['courscribe_submit_signin'] ) && isset( $_POST['courscribe_signin_nonce'] ) && wp_verify_nonce( $_POST['courscribe_signin_nonce'], 'courscribe_signin' ) ) {
        if ( ! wp_verify_nonce( $_POST['courscribe_signin_nonce'], 'courscribe_signin' ) ) {
        $errors[] = 'Nonce verification failed. Please try again.';
    } else {
        $login = isset( $_POST['courscribe_login'] ) ? sanitize_text_field( $_POST['courscribe_login'] ) : '';
        $password = isset( $_POST['courscribe_password'] ) ? $_POST['courscribe_password'] : '';
        $remember = isset( $_POST['courscribe_remember'] ) ? true : false;

        if ( empty( $login ) ) {
            $errors[] = 'Username or email is required.';
        }

        if ( empty( $password ) ) {
            $errors[] = 'Password is required.';
        }

        if ( empty( $errors ) ) {
            // Determine if login is email or username
            $user_data = null;
            if ( is_email( $login ) ) {
                $user_data = get_user_by( 'email', $login );
                $username = $user_data ? $user_data->user_login : '';
            } else {
                $user_data = get_user_by( 'login', $login );
                $username = $login;
            }

            if ( ! $user_data ) {
                $errors[] = 'Invalid username/email or password.';
            } else {
                $user = wp_signon( [
                    'user_login' => $username,
                    'user_password' => $password,
                    'remember' => $remember,
                ], is_ssl() );

                if ( is_wp_error( $user ) ) {
                    $errors[] = 'Invalid username/email or password.';
                } else {
                    // Update last login time
                    update_user_meta( $user->ID, '_courscribe_last_login', current_time( 'mysql' ) );
                    error_log( 'Redirecting to studio page for user: ' . $user->ID );
                    wp_safe_redirect( home_url( '/studio' ) );
                    exit;
                }
            }
        }

        if ( ! empty( $errors ) ) {
            echo '<div class="courscribe-error"><p>Please correct the following errors:</p><ul>';
            foreach ( $errors as $error ) {
                echo '<li>' . esc_html( $error ) . '</li>';
            }
            echo '</ul></div>';
        }
    }
        
    }

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

    <!-- Form -->
    <main class="main-content position-relative border-radius-lg">
        <div class="container-fluid py-4 courscribe-div-center-column">
            <div class="form-container">
                <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
                <h3 class="courscribe-heading">Welcome back to <span>CourScribe!</span></h3>
                <p class="courscribe-subheading">Sign in to continue building your education empire.</p>
                <!-- Update the form -->
                <form id="signin-form" method="post" class="form">
                    <?php wp_nonce_field( 'courscribe_signin', 'courscribe_signin_nonce' ); ?>
                    <div class="input-group">
                        <label for="courscribe_login">Username or Email:</label>
                        <input type="text" name="courscribe_login" id="courscribe_login" value="<?php echo esc_attr( $prefill_login ?: ( $_POST['courscribe_login'] ?? '' ) ); ?>" required placeholder="Enter your username or email">
                    </div>
                    <div class="input-group">
                        <label for="courscribe_password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="courscribe_password" id="courscribe_password" required placeholder="Enter your password">
                            <span class="toggle-password" onclick="togglePassword('courscribe_password', this)">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                        <div class="remember-me">
                            <label class="checkbox-label">
                                <input type="checkbox" name="courscribe_remember" id="courscribe_remember" value="1">
                                <span class="checkmark"></span>
                                Remember me
                            </label>
                        </div>
                        <div class="forgot">
                            <a rel="noopener noreferrer" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Forgot Password?</a>
                        </div>
                    </div>
                    <button class="btn courscribe-stepper-nextBtn" type="submit" name="courscribe_submit_signin">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <script>
                    jQuery(document).ready(function($) {
                        $('#signin-form').on('submit', function(e) {
                            e.preventDefault();
                            const formData = $(this).serialize() + '&courscribe_submit_signin=1';
                            
                            // Show loading state
                            const $submitBtn = $(this).find('button[type="submit"]');
                            const originalText = $submitBtn.html();
                            $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Signing In...').prop('disabled', true);
                            
                            $.ajax({
                                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                                type: 'POST',
                                data: formData + '&action=courscribe_enhanced_signin',
                                success: function(response) {
                                    if (response.success) {
                                        // Show success message
                                        $('#signin-form').before('<div class="courscribe-success">' + response.data.message + '</div>');
                                        // Redirect after short delay
                                        setTimeout(function() {
                                            window.location.href = response.data.redirect_url || '<?php echo esc_url( home_url( '/studio' ) ); ?>';
                                        }, 1000);
                                    } else {
                                        $('.courscribe-error').remove();
                                        $('#signin-form').before('<div class="courscribe-error">' + response.data.message + '</div>');
                                        // Reset button
                                        $submitBtn.html(originalText).prop('disabled', false);
                                    }
                                },
                                error: function() {
                                    $('.courscribe-error').remove();
                                    $('#signin-form').before('<div class="courscribe-error">Network error. Please check your connection and try again.</div>');
                                    // Reset button
                                    $submitBtn.html(originalText).prop('disabled', false);
                                }
                            });
                        });
                    });
                </script>
                <p class="signup">Don't have an account?
                    <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/courscribe-register' ) ); ?>">Sign up</a>
                </p>
            </div>
        </div>
    </main>

    <style>
        /* Enhanced Premium Auth Styles - Dark Theme */
        .courscribe-landing .form-container {
            background: linear-gradient(135deg, #2a2a2b 0%, #353535 100%);
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid #404040;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .courscribe-landing .courscribe-heading {
            color: #FFFFFF;
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .courscribe-landing .courscribe-heading span {
            background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .courscribe-landing .courscribe-subheading {
            color: #B0B0B0;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .courscribe-landing .input-group {
            margin-bottom: 1.5rem;
        }
        
        .courscribe-landing .input-group label {
            display: block;
            color: #E0E0E0;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .courscribe-landing .input-group input[type="text"],
        .courscribe-landing .input-group input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #404040;
            border-radius: 0.5rem;
            color: #FFFFFF;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .courscribe-landing .input-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: #E4B26F;
            box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.2);
        }
        
        .courscribe-landing .input-group input::placeholder {
            color: #808080;
        }
        
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            padding-right: 40px !important;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #808080;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #E4B26F;
        }
        
        .toggle-password i.fa-eye-slash {
            display: none;
        }
        
        .toggle-password.show i.fa-eye {
            display: none;
        }
        
        .toggle-password.show i.fa-eye-slash {
            display: inline;
        }
        
        .remember-me {
            margin-top: 1rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #E0E0E0;
            font-size: 0.875rem;
            user-select: none;
        }
        
        .checkbox-label input[type="checkbox"] {
            display: none;
        }
        
        .checkmark {
            width: 18px;
            height: 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #404040;
            border-radius: 0.25rem;
            margin-right: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
            border-color: #F8923E;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .courscribe-landing .btn.courscribe-stepper-nextBtn {
            width: 100%;
            background: linear-gradient(90deg, #F8923E 3.57%, #F25C3B 100%);
            color: white;
            border: none;
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .courscribe-landing .btn.courscribe-stepper-nextBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(248, 146, 62, 0.4);
        }
        
        .courscribe-landing .btn.courscribe-stepper-nextBtn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .courscribe-landing .forgot a {
            color: #E4B26F;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }
        
        .courscribe-landing .forgot a:hover {
            color: #F0C788;
            text-decoration: underline;
        }
        
        .courscribe-landing .signup {
            text-align: center;
            margin-top: 2rem;
            color: #B0B0B0;
            font-size: 0.875rem;
        }
        
        .courscribe-landing .signup a {
            color: #E4B26F;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .courscribe-landing .signup a:hover {
            color: #F0C788;
            text-decoration: underline;
        }
        
        .courscribe-error {
            background: rgba(242, 92, 59, 0.1);
            border: 1px solid #F25C3B;
            color: #FF6B6B;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .courscribe-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22C55E;
            color: #4ADE80;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 480px) {
            .courscribe-landing .form-container {
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>

    <script>
        function togglePassword(inputId, toggleElement) {
            const input = document.getElementById(inputId);
            const icon = toggleElement;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.add('show');
            } else {
                input.type = 'password';
                icon.classList.remove('show');
            }
        }
    </script>

    <?php
    return ob_get_clean();
}