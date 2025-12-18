<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode for set new password
add_shortcode( 'courscribe_set_new_password', 'courscribe_set_new_password_shortcode' );

function courscribe_set_new_password_shortcode() {
    $site_url = home_url();
    if ( is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/studio' ) );
        exit;
    }

    ob_start();

    $errors = [];
    $SUCCESS = '';
    $key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
    $login = isset( $_GET['login'] ) ? sanitize_user( $_GET['login'] ) : '';

    if ( empty( $key ) || empty( $login ) ) {
        return courscribe_retro_tv_error("Invalid password reset link. Please request a new one..");
    }

    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) ) {
        return courscribe_retro_tv_error("Invalid or expired password reset link. Please request a new one.");
       
    }

    if ( isset( $_POST['courscribe_submit_new_password'] ) && isset( $_POST['courscribe_new_password_nonce'] ) && wp_verify_nonce( $_POST['courscribe_new_password_nonce'], 'courscribe_new_password' ) ) {
        $password = isset( $_POST['courscribe_password'] ) ? $_POST['courscribe_password'] : '';
        $password_confirm = isset( $_POST['courscribe_password_confirm'] ) ? $_POST['courscribe_password_confirm'] : '';

        if ( empty( $password ) ) {
            $errors[] = 'Password is required.';
        }

        if ( empty( $password_confirm ) ) {
            $errors[] = 'Password confirmation is required.';
        } elseif ( $password !== $password_confirm ) {
            $errors[] = 'Passwords do not match.';
        }

        if ( empty( $errors ) ) {
            reset_password( $user, $password );
            $success = 'Your password has been updated. Please sign in with your new password.';
            $key = ''; // Clear key to prevent reuse
        }

        if ( ! empty( $errors ) ) {
            echo '<div class="courscribe-error"><p>Please correct the following errors:</p><ul>';
            foreach ( $errors as $error ) {
                echo '<li>' . esc_html( $error ) . '</li>';
            }
            echo '</ul></div>';
        } elseif ( $success ) {
            echo '<p class="courscribe-success">' . esc_html( $success ) . '</p>';
        }
    }

    if ( $success ) {
        ?>
        <main class="main-content position-relative border-radius-lg">
            <div class="container-fluid py-4 courscribe-div-center-column">
                <div class="form-container">
                    <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
                    <p class="signup">
                        <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/signin' ) ); ?>">Return to Sign In</a>
                    </p>
                </div>
            </div>
        </main>
        <?php
    } else {
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
                    <h3 class="courscribe-heading">Set New <span>CourScribe</span> Password</h3>
                    <p class="courscribe-subheading">Enter your new password below.</p>
                    <form method="post" class="form">
                        <?php wp_nonce_field( 'courscribe_new_password', 'courscribe_new_password_nonce' ); ?>
                        <div class="input-group">
                            <label for="courscribe_password">New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="courscribe_password" id="courscribe_password" required placeholder="">
                                <span class="toggle-password" onclick="togglePassword('courscribe_password', this)">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="courscribe_password_confirm">Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="courscribe_password_confirm" id="courscribe_password_confirm" required placeholder="">
                                <span class="toggle-password" onclick="togglePassword('courscribe_password_confirm', this)">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <button class="btn courscribe-stepper-nextBtn" type="submit" name="courscribe_submit_new_password">Set New Password</button>
                    </form>
                    <p class="signup">Remember your password?
                        <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/signin' ) ); ?>">Sign in</a>
                    </p>
                </div>
            </div>
        </main>

        <style>
            .password-wrapper {
                position: relative;
                width: 100%;
            }
            .password-wrapper input {
                width: 100%;
                padding-right: 40px;
            }
            .toggle-password {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                cursor: pointer;
                color: #666;
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
            .courscribe-success {
                color: #28a745;
                font-weight: 600;
                text-align: center;
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
    }

    return ob_get_clean();
}