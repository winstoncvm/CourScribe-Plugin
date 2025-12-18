<?php
// courscribe-register-shortcode.php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode for registration
add_shortcode('courscribe_register', 'courscribe_register_shortcode');

function courscribe_register_shortcode() {
    $site_url = home_url();
    if (is_user_logged_in()) {
        return courscribe_retro_tv_error("You are already logged in.");
    }
    if (!get_option('users_can_register')) {
        return courscribe_retro_tv_error("Registration is currently disabled. Please contact the site administrator.");
    }

    ob_start();

    $prefill_email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';
    $invite_code = isset($_GET['invite_code']) ? sanitize_text_field($_GET['invite_code']) : '';
    $curriculum_id = isset($_GET['curriculum_id']) ? intval($_GET['curriculum_id']) : 0;
    $studio_id = isset($_GET['studio_id']) ? intval($_GET['studio_id']) : 0;

    ?>
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/tabs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-icons.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-svg.css">
    <link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/chartjs.min.js" defer></script>
    <script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>

    <!-- Form -->
    <main class="main-content position-relative border-radius-lg">
        <div class="container-fluid py-4 courscribe-div-center-column" style="height: 100vh;">
            <div class="form-container">
                <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
                <h3 class="courscribe-heading">Reclaim your time!<br>
                    Get started with <span>CourScribe.</span></h3>
                <p class="courscribe-subheading">Create a new account to capture your genius and create your education empire.</p>
                <form method="post" class="form" id="courscribe-register-form">
                    <?php wp_nonce_field('courscribe_register', 'courscribe_register_nonce'); ?>
                    <input type="hidden" name="invite_code" value="<?php echo esc_attr($invite_code); ?>">
                    <input type="hidden" name="curriculum_id" value="<?php echo esc_attr($curriculum_id); ?>">
                    <input type="hidden" name="studio_id" value="<?php echo esc_attr($studio_id); ?>">
                    <div class="input-group">
                        <label for="courscribe_username">Username</label>
                        <input type="text" name="courscribe_username" id="courscribe_username" placeholder="Enter username" value="<?php echo esc_attr($_POST['courscribe_username'] ?? ''); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="courscribe_email">Email</label>
                        <input type="email" name="courscribe_email" id="courscribe_email" placeholder="Enter email" value="<?php echo esc_attr($prefill_email ?: ($_POST['courscribe_email'] ?? '')); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="courscribe_password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="courscribe_password" id="courscribe_password" placeholder="Enter password" required>
                            <span class="toggle-password" onclick="togglePassword('courscribe_password', this)">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="courscribe_password_confirm">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="courscribe_password_confirm" id="courscribe_password_confirm" placeholder="Confirm password" required>
                            <span class="toggle-password" onclick="togglePassword('courscribe_password_confirm', this)">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                        <div class="forgot">
                            <a rel="noopener noreferrer" href="<?php echo esc_url(home_url('/lost-password')); ?>">Forgot Password?</a>
                        </div>
                    </div>
                    <button class="btn courscribe-stepper-nextBtn" type="submit" name="courscribe_submit_register" id="courscribe-submit-register">Create CourScribe Account</button>
                </form>
                <div id="courscribe-register-error" class="courscribe-error" style="display: none;"></div>
               
                <p class="signup">Already have an account?
                    <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/courscribe-sign-in' ) ); ?>">Sign in</a>
                </p>
            </div>
        </div>
    </main>

    <style>
        .wp-block-post-title {
            display: none;
        }
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
        .courscribe-error {
            color: #d9534f;
            margin-bottom: 15px;
        }
        .courscribe-stepper-nextBtn {
            background-color: #FBC275 !important;
            color: #2F2E30 !important;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        .courscribe-stepper-nextBtn:disabled {
            background-color: #cccccc !important;
            cursor: not-allowed;
        }
        .courscribe-signin-link {
            color: #FBC275 !important;
            text-decoration: underline;
        }
        .courscribe-signin-link:hover {
            color: #e0a458 !important;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            const form = $('#courscribe-register-form');
            const submitButton = $('#courscribe-submit-register');
            const errorDiv = $('#courscribe-register-error');

            // Enable button by default
            submitButton.prop('disabled', false);

            // Basic form validation to enable/disable button
            form.on('input', function() {
                const username = $('#courscribe_username').val().trim();
                const email = $('#courscribe_email').val().trim();
                const password = $('#courscribe_password').val().trim();
                const passwordConfirm = $('#courscribe_password_confirm').val().trim();

                if (username && email && password && passwordConfirm) {
                    submitButton.prop('disabled', false);
                } else {
                    submitButton.prop('disabled', true);
                }
            });

            // Form submission with AJAX
            form.on('submit', function(e) {
                e.preventDefault();
                errorDiv.hide().empty();

                const formData = $(this).serialize() + '&courscribe_submit_register=1&action=courscribe_register';

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Handle redirect based on invite type
                            <?php if ($invite_type === 'client' && $curriculum_id) : ?>
                                window.location.href = '<?php echo esc_url(add_query_arg(['preview_type' => 'client-preview'], get_permalink($curriculum_id))); ?>';
                            <?php else : ?>
                                window.location.href = '<?php echo esc_url(home_url('/create-studio')); ?>';
                            <?php endif; ?>
                        } else {
                            errorDiv.html('<p>' + response.data.message + '</p>').show();
                        }
                    },
                    error: function() {
                        errorDiv.html('<p>An error occurred. Please try again.</p>').show();
                    }
                });
            });

            // Toggle password visibility
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

            // Expose togglePassword to global scope for inline onclick
            window.togglePassword = togglePassword;
        });
    </script>

    <?php
    return ob_get_clean();
}
?>