<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shortcode for forgot password
add_shortcode( 'courscribe_forgot_password', 'courscribe_forgot_password_shortcode' );

function courscribe_forgot_password_shortcode() {
    $site_url = home_url();
    if ( is_user_logged_in() ) {
        wp_safe_redirect( home_url( '/studio' ) );
        exit;
    }

    ob_start();

    $errors = [];
    $success = '';
    $prefill_email = isset( $_GET['email'] ) ? sanitize_email( urldecode( $_GET['email'] ) ) : '';

    if ( isset( $_POST['courscribe_submit_forgot_password'] ) && isset( $_POST['courscribe_forgot_password_nonce'] ) && wp_verify_nonce( $_POST['courscribe_forgot_password_nonce'], 'courscribe_forgot_password' ) ) {
        $email = isset( $_POST['courscribe_email'] ) ? sanitize_email( $_POST['courscribe_email'] ) : '';

        if ( empty( $email ) ) {
            $errors[] = 'Email is required.';
        } elseif ( ! is_email( $email ) ) {
            $errors[] = 'Please enter a valid email address.';
        } elseif ( ! email_exists( $email ) ) {
            $errors[] = 'No account found with this email address.';
        }

        if ( empty( $errors ) ) {
            $user = get_user_by( 'email', $email );
            $reset_key = get_password_reset_key( $user );
            if ( ! is_wp_error( $reset_key ) ) {
                $reset_url = add_query_arg(
                    [
                        'key' => $reset_key,
                        'login' => rawurlencode( $user->user_login ),
                    ],
                    home_url( '/set-new-password' )
                );
                $subject = 'CourScribe Password Reset';
                $message = "Hello,\n\nYou requested a password reset for your CourScribe account. Please click the following link to set a new password:\n\n{$reset_url}\n\nIf you did not request this, please ignore this email.\n\nBest,\nCourScribe Team";
                $headers = ['Content-Type: text/plain; charset=UTF-8'];
                if ( wp_mail( $email, $subject, $message, $headers ) ) {
                    $success = 'A password reset link has been sent to your email address.';
                } else {
                    $errors[] = 'Failed to send the reset email. Please try again later.';
                }
            } else {
                $errors[] = 'Error generating reset link. Please try again.';
            }
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
                <h3 class="courscribe-heading">Reset your <span>CourScribe</span> Password</h3>
                <p class="courscribe-subheading">Enter your email address to receive a password reset link.</p>
                <form method="post" class="form">
                    <?php wp_nonce_field( 'courscribe_forgot_password', 'courscribe_forgot_password_nonce' ); ?>
                    <div class="input-group">
                        <label for="courscribe_email">Email:</label>
                        <input type="email" name="courscribe_email" id="courscribe_email" value="<?php echo esc_attr( $prefill_email ?: ( $_POST['courscribe_email'] ?? '' ) ); ?>" required>
                    </div>
                    <button class="btn courscribe-stepper-nextBtn" type="submit" name="courscribe_submit_forgot_password">Send Reset Link</button>
                </form>
                <p class="signup">Remember your password?
                    <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/signin' ) ); ?>">Sign in</a>
                </p>
                <p class="signup">Don't have an account?
                    <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/register' ) ); ?>">Sign up</a>
                </p>
            </div>
        </div>
    </main>

    <style>
        .courscribe-success {
            color: #28a745;
            font-weight: 600;
            text-align: center;
        }
    </style>

    <?php
    return ob_after_signin();
}