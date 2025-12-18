<?php
// courscribe-review-system.php

// templates/curriculums/parts/courscribe-review-syatem.php
// Function to display the invite client modal
function courscribe_invite_client_modal() {
    ?>
    <div id="courscribe-invite-client-modal123" class="modal fade" tabindex="-1" role="dialog" style="color: #f1f1f1; background-color: #2F2E30; border-radius: 30px; padding: 40px;">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="background-color: #2F2E30; border-radius: 30px; border: none;">
                <div class="modal-header" style="border-bottom: none;">
                    <h5 class="modal-title" style="color: #fff; font-size: 32px; font-weight: 600;">Invite Client for Review</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background-color: rgba(251, 194, 117, 0.1); border-radius: 18.5px; padding: 6px; border: 1px solid #f1f1f1;">
                        <span aria-hidden="true" style="color: #f1f1f1; font-size: 24px;">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="courscribe-invite-client-form">
                        <?php wp_nonce_field('courscribe_invite_client', 'courscribe_invite_client_nonce'); ?>
                        <div class="form-group">
                            <label for="client_email" style="color: #f1f1f1;">Client Email <span style="color: red;">*</span></label>
                            <input type="email" class="form-control" id="client_email" name="client_email" required style="background-color: #3f3e40; color: #f1f1f1; border: 1px solid #f1f1f1;">
                        </div>
                        <div class="form-group">
                            <label for="client_name" style="color: #f1f1f1;">Client Name <span style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="client_name" name="client_name" required style="background-color: #3f3e40; color: #f1f1f1; border: 1px solid #f1f1f1;">
                        </div>
                        <input type="hidden" id="curriculum_id" name="curriculum_id" value="<?php echo get_the_ID(); ?>">
                    </form>
                </div>
                <div class="modal-footer" style="border-top: none;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background-color: #989898; color: #f1f1f1;">Close</button>
                    <button type="button" class="btn btn-primary" id="send-invite-btn" style="background-color: #FBC275; color: #2F2E30;">Send Invite</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler to send the client invite
function courscribe_send_client_invite() {
    check_ajax_referer('courscribe_invite_client', 'courscribe_invite_client_nonce');

    $client_email = sanitize_email($_POST['client_email']);
    $client_name = sanitize_text_field($_POST['client_name']);
    $curriculum_id = intval($_POST['curriculum_id']);

    if (!is_email($client_email)) {
        wp_send_json_error(['message' => 'Invalid email address.']);
    }

    // Generate invite code and save to database
    $invite_code = wp_generate_password(12, false);
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_client_invites';
    $wpdb->insert($table_name, [
        'email' => $client_email,
        'name' => $client_name,
        'invite_code' => $invite_code,
        'curriculum_id' => $curriculum_id,
        'status' => 'Pending',
        'created_at' => current_time('mysql'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
    ]);

    // Get curriculum and studio details
    $curriculum = get_post($curriculum_id);
    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
    $studio = get_post($studio_id);
    $ Kuser = wp_get_current_user();

    // Prepare email content
    $preview_link = add_query_arg([
        'invite_code' => $invite_code,
        'email' => urlencode($client_email),
        'curriculum_id' => $curriculum_id,
        'studio_id' => $studio_id
    ], home_url('/courscribe-register'));

    $email_content = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                .header { background-color: #2F2E30; padding: 20px; text-align: center; }
                .header img { max-width: 150px; }
                .content { padding: 30px; }
                .content h1 { color: #2F2E30; font-size: 24px; margin-bottom: 20px; }
                .content p { color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #FBC275; color: #2F2E30; text-decoration: none; font-weight: bold; border-radius: 5px; }
                .footer { background-color: #2F2E30; color: #ffffff; text-align: center; padding: 15px; font-size: 14px; }
                .footer a { color: #FBC275; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . esc_url(home_url('/wp-content/plugins/courscribe/assets/images/logo.png')) . '" alt="Courscribe Logo">
                </div>
                <div class="content">
                    <h1>Invitation to Review Curriculum</h1>
                    <p>Dear ' . esc_html($client_name) . ',</p>
                    <p>You have been invited by ' . esc_html($user->display_name) . ' from ' . esc_html($studio->post_title) . ' to review the curriculum "<strong>' . esc_html($curriculum->post_title) . '</strong>".</p>
                    <p>Please register or log in to access the curriculum preview and provide your feedback. This invitation expires on ' . date('F j, Y', strtotime('+3 days')) . '.</p>
                    <p style="text-align: center;">
                        <a href="' . esc_url($preview_link) . '" class="button">View Curriculum</a>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' Courscribe. All rights reserved.</p>
                    <p><a href="' . esc_url(home_url()) . '">Visit our website</a> | <a href="' . esc_url(home_url('/contact')) . '">Contact Us</a></p>
                </div>
            </div>
        </body>
        </html>
    ';

    // Send email
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $mail_result = wp_mail($client_email, 'Courscribe Client Review Invitation', $email_content, $headers);

    if ($mail_result) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Failed to send email.']);
    }
}
add_action('wp_ajax_courscribe_send_client_invite', 'courscribe_send_client_invite');


// Function to handle client preview page logic
function courscribe_handle_client_preview() {
    if (isset($_GET['invite_code']) && isset($_GET['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_client_invites';
        $invite_code = sanitize_text_field($_GET['invite_code']);
        $email = sanitize_email(urldecode($_GET['email']));
        $curriculum_id = get_the_ID();

        // Validate invite
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT id, curriculum_id, status, expires_at FROM $table_name WHERE email = %s AND invite_code = %s AND curriculum_id = %d",
            $email,
            $invite_code,
            $curriculum_id
        ));

        if (!$invite || $invite->status !== 'Pending' || strtotime($invite->expires_at) < time()) {
            return '<p>Invalid or expired invite.</p>';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            // Redirect to registration page with invite details
            $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
            $redirect_url = add_query_arg([
                'email' => urlencode($email),
                'invite_code' => $invite_code,
                'curriculum_id' => $curriculum_id,
                'studio_id' => $studio_id,
            ], home_url('/courscribe-register'));
            wp_redirect($redirect_url);
            exit;
        } else {
            // Verify logged-in user's email
            $current_user = wp_get_current_user();
            if ($current_user->user_email === $email) {
                // Ensure user has client role
                if (!in_array('client', $current_user->roles)) {
                    wp_update_user(['ID' => $current_user->ID, 'role' => 'client']);
                    $studio_id = get_post_meta($curriculum_id, '_studio_id', true);
                    update_user_meta($current_user->ID, '_courscribe_studio_id', $studio_id);
                    $wpdb->update($table_name, ['status' => 'Accepted'], ['id' => $invite->id]);
                }
                // Redirect to set preview_type
                $redirect_url = add_query_arg(['preview_type' => 'client-preview'], get_permalink($curriculum_id));
                wp_redirect($redirect_url);
                exit;
            } else {
                return '<p>This invite is for a different email address.</p>';
            }
        }
    }
}

// Function to display off-canvas registration/login form
function courscribe_client_registration_offcanvas($email, $invite_code, $curriculum_id) {
    $site_url = home_url();
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

    <main class="main-content position-relative border-radius-lg">
        <div class="container-fluid py-4 courscribe-div-center-column">
            <div class="form-container">
                <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
                <h3 class="courscribe-heading">Reclaim your time!<br>
                    Get started with <span>CourScribe.</span></h3>

                <p class="courscribe-subheading">Create a new account to capture your genius and create your education empire.</p>
                <form id="courscribe-client-registration-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                    <?php wp_nonce_field('courscribe_client_register', 'courscribe_client_register_nonce'); ?>
                    <div class="mb-3">
                        <label for="client_email" class="form-label" style="color: #f1f1f1;">Email</label>
                        <input type="email" class="form-control" id="client_email" name="client_email" value="<?php echo esc_attr($email); ?>" readonly style="background-color: #3f3e40; color: #f1f1f1; border: 1px solid #f1f1f1;">
                    </div>
                    <div class="mb-3">
                        <label for="client_username" class="form-label" style="color: #f1f1f1;">Username <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="client_username" name="client_username" required style="background-color: #3f3e40; color: #f1f1f1; border: 1px solid #f1f1f1;">
                    </div>
                    <div class="mb-3">
                        <label for="client_password" class="form-label" style="color: #f1f1f1;">Password <span style="color: red;">*</span></label>
                        <input type="password" class="form-control" id="client_password" name="client_password" required style="background-color: #3f3e40; color: #f1f1f1; border: 1px solid #f1f1f1;">
                    </div>
                    <input type="hidden" name="invite_code" value="<?php echo esc_attr($invite_code); ?>">
                    <input type="hidden" name="curriculum_id" value="<?php echo esc_attr($curriculum_id); ?>">
                    <input type="hidden" name="action" value="courscribe_client_register">
                    <button type="submit" class="btn btn-primary" style="background-color: #FBC275; color: #2F2E30;">Register</button>
                </form>

                <p class="signup">Already have an account?
                    <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="">Sign in</a>
                </p>
            </div>
        </div>
    </main>

    <script>
        jQuery(document).ready(function($) {
            // Show off-canvas on page load
            $('#clientRegistrationOffcanvas').offcanvas('show');

            // Handle form submission
            $('#courscribe-client-registration-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo esc_url(add_query_arg(['preview_type' => 'client-preview'], get_permalink($curriculum_id))); ?>';
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An unexpected error occurred.');
                    }
                });
            });
        });
    </script>
    <?php
}

// JavaScript to handle the modal and form submission
function courscribe_invite_client_script() {
    $site_url = home_url();
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#send-invite-btn').on('click', function() {
                const modal = document.getElementById('auth-modal');
                var formData = $('#courscribe-invite-client-form').serialize();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: formData + '&action=courscribe_send_client_invite',
                    success: function(response) {
                        if (response.success) {
                            $('#auth-modal .auth-modal-content').html(`
                            <span id="close-modal" style="position: absolute; top: 14px; right: 20px; cursor: pointer; font-size: 40px;">×</span>
                                <div class="client-review-success-container">
                                    <div>
                                    <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
                                    <h3>Shared Successfully</h3>
                                    <p>Feedback and comments from client will be shared on your email.</p> 
                                    </div>
                                    <svg width="339" height="303" viewBox="0 0 339 303" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M335.305 249.395C332.228 268.33 318.363 282.991 303.602 295.803C301.541 297.593 299.476 299.347 297.405 301.067C297.39 301.075 297.376 301.091 297.362 301.1C297.262 301.182 297.162 301.265 297.07 301.347C296.643 301.701 296.215 302.055 295.791 302.406L296.057 302.489L296.886 302.743C296.601 302.665 296.321 302.591 296.036 302.513C295.951 302.489 295.863 302.469 295.779 302.444C285.992 299.801 276.006 296.9 268.006 290.706C259.706 284.274 253.99 273.308 256.852 263.205C257.229 261.881 257.752 260.603 258.412 259.396C258.682 258.89 258.98 258.402 259.293 257.92C261.184 254.948 263.833 252.534 266.967 250.926C270.1 249.318 273.606 248.573 277.123 248.769C280.64 248.964 284.041 250.093 286.977 252.039C289.913 253.985 292.278 256.678 293.828 259.841C293.688 248.708 283.52 240.59 273.982 234.845C264.441 229.103 253.638 222.9 250.639 212.172C248.966 206.204 250.373 200.057 253.657 194.863C253.759 194.705 253.861 194.547 253.967 194.393C257.994 188.319 264.165 183.989 271.246 182.266C284.458 179.269 298.305 184.79 309.142 192.925C326.584 206.013 338.803 227.867 335.305 249.395Z" fill="#747374"/>
                                        <path d="M307.281 228.478C310.329 232.165 312.823 236.276 314.684 240.682C316.284 244.628 317.285 248.791 317.654 253.033C318.457 261.747 316.804 270.555 313.507 278.62C310.943 284.742 307.615 290.516 303.602 295.803C301.541 297.593 299.475 299.347 297.405 301.067C297.39 301.075 297.376 301.091 297.362 301.1C297.262 301.182 297.162 301.265 297.07 301.347C296.642 301.701 296.215 302.055 295.791 302.406L296.057 302.489L296.886 302.743C296.601 302.665 296.321 302.591 296.036 302.513C295.951 302.489 295.863 302.469 295.779 302.444C295.791 295.485 294.136 288.624 290.951 282.436C287.766 276.248 283.144 270.913 277.473 266.88C271.798 262.918 265.267 260.354 258.412 259.396C258.682 258.89 258.98 258.402 259.292 257.92C261.945 258.331 264.555 258.976 267.094 259.847C276.166 262.936 284.002 268.863 289.443 276.752C294.336 283.921 297.083 292.338 297.359 301.013C297.701 300.62 298.043 300.218 298.373 299.822C304.672 292.357 310.06 284.01 313.271 274.737C316.079 266.874 316.911 258.441 315.692 250.181C314.259 241.188 309.654 233.251 303.566 226.591C297.046 219.463 289.217 213.405 281.023 208.326C272.63 203.113 263.631 198.947 254.225 195.92C254.013 195.851 253.836 195.702 253.73 195.505C253.624 195.309 253.598 195.079 253.657 194.863C253.694 194.672 253.806 194.503 253.967 194.393C254.051 194.344 254.144 194.315 254.241 194.307C254.338 194.3 254.435 194.314 254.526 194.349C255.687 194.724 256.841 195.106 257.99 195.512C267.555 198.89 276.664 203.444 285.107 209.068C293.26 214.485 301.088 220.853 307.281 228.478Z" fill="white"/>
                                        <path d="M140.927 185.24C139.987 185.238 139.082 184.879 138.396 184.236C137.709 183.592 137.294 182.712 137.232 181.773L136.682 173.37C136.617 172.389 136.946 171.422 137.594 170.682C138.243 169.943 139.159 169.491 140.14 169.427L205.974 165.116C207.013 165.047 208.054 165.184 209.039 165.518C210.024 165.852 210.934 166.377 211.716 167.063C212.499 167.748 213.138 168.582 213.598 169.515C214.058 170.448 214.33 171.462 214.398 172.5C214.466 173.538 214.329 174.58 213.994 175.565C213.66 176.55 213.135 177.459 212.448 178.241C211.762 179.023 210.929 179.662 209.995 180.122C209.062 180.582 208.048 180.853 207.009 180.92L141.175 185.231C141.092 185.237 141.01 185.239 140.927 185.24Z" fill="url(#paint0_linear_201_573)"/>
                                        <path d="M217.35 302.02H208.928C207.945 302.019 207.002 301.628 206.307 300.932C205.612 300.237 205.221 299.294 205.22 298.311V228.125C205.221 227.142 205.612 226.199 206.307 225.504C207.002 224.809 207.945 224.418 208.928 224.417H217.35C218.333 224.418 219.275 224.809 219.971 225.504C220.666 226.199 221.057 227.142 221.058 228.125V298.311C221.057 299.294 220.666 300.237 219.971 300.932C219.275 301.628 218.333 302.019 217.35 302.02Z" fill="url(#paint1_linear_201_573)"/>
                                        <path d="M204.559 151.17C220.629 151.17 233.657 138.142 233.657 122.072C233.657 106.002 220.629 92.9739 204.559 92.9739C188.489 92.9739 175.461 106.002 175.461 122.072C175.461 138.142 188.489 151.17 204.559 151.17Z" fill="url(#paint2_linear_201_573)"/>
                                        <path d="M188.809 132.007C187.873 131.7 187.014 131.195 186.289 130.528C185.809 130.051 185.439 129.476 185.204 128.841C184.97 128.206 184.877 127.528 184.933 126.854C184.963 126.374 185.104 125.907 185.345 125.49C185.586 125.073 185.92 124.718 186.32 124.451C187.361 123.784 188.753 123.782 190.17 124.406L190.116 113.047L191.257 113.041L191.32 126.396L190.441 125.843C189.422 125.202 187.966 124.752 186.937 125.412C186.682 125.586 186.472 125.817 186.321 126.086C186.171 126.355 186.084 126.655 186.069 126.963C186.03 127.465 186.1 127.97 186.274 128.443C186.448 128.915 186.722 129.345 187.077 129.702C188.337 130.906 190.175 131.282 192.272 131.619L192.091 132.745C190.977 132.598 189.879 132.351 188.809 132.007Z" fill="white"/>
                                        <path d="M178.353 112.473L178.203 113.604L184.296 114.409L184.445 113.278L178.353 112.473Z" fill="white"/>
                                        <path d="M197.585 115.012L197.436 116.143L203.528 116.948L203.678 115.817L197.585 115.012Z" fill="white"/>
                                        <path d="M225.369 227H188.631C187.668 226.999 186.745 226.611 186.065 225.923C185.384 225.234 185.001 224.3 185 223.326L192.691 163.646C192.697 162.677 193.082 161.749 193.762 161.066C194.442 160.382 195.362 159.999 196.32 160H209.358C214.566 160.006 219.558 162.102 223.241 165.828C226.923 169.554 228.994 174.606 229 179.875V223.326C228.999 224.3 228.616 225.234 227.935 225.923C227.255 226.611 226.332 226.999 225.369 227Z" fill="#656565"/>
                                        <path d="M205.104 104.814C201.214 101.595 196.014 104.639 191.615 104.207C187.407 103.794 184.019 100.09 183.096 96.1048C182.019 91.455 184.354 86.7317 187.87 83.7531C191.721 80.4908 196.892 79.4771 201.819 79.9735C207.467 80.5424 212.67 83.071 217.354 86.1601C221.874 89.0468 240.044 138.447 243.783 142.292C247.132 145.853 249.994 150.119 250.974 154.978C251.865 159.394 251.342 164.316 248.794 168.119C247.442 170.069 245.609 171.638 243.474 172.674C241.249 173.82 238.868 174.654 236.716 175.942C233.461 177.89 230.338 181.861 231.446 185.909C231.684 186.794 232.135 187.607 232.761 188.275C233.513 189.079 234.82 187.974 234.066 187.168C232.743 185.753 232.757 183.833 233.415 182.102C234.2 180.15 235.625 178.522 237.456 177.486C239.709 176.14 242.208 175.288 244.52 174.058C246.734 172.932 248.643 171.287 250.085 169.265C252.806 165.329 253.545 160.259 252.823 155.596C252.041 150.551 249.354 146.003 246.023 142.212C242.397 138.087 223.974 88.6211 219.463 85.5175C214.624 82.1873 209.294 79.3954 203.437 78.4603C198.359 77.6497 192.885 78.3121 188.513 81.1537C184.432 83.806 181.349 88.2939 181.128 93.2473C181.057 95.5005 181.589 97.7318 182.667 99.7115C183.745 101.691 185.332 103.348 187.263 104.511C189.243 105.645 191.53 106.13 193.801 105.895C196.229 105.693 198.648 104.903 201.098 105.063C202.207 105.109 203.27 105.515 204.126 106.22C204.976 106.923 205.946 105.511 205.104 104.814Z" fill="white"/>
                                        <path d="M233.82 137.664C230.131 134.062 215.461 89.7041 211 87L213.467 87.4684C217.919 90.3756 232.453 133.725 236.031 137.589C239.32 141.14 241.971 145.4 242.743 150.127C243.456 154.495 242.726 159.243 240.041 162.93C238.618 164.825 236.734 166.365 234.548 167.42C232.267 168.573 229.8 169.371 227.576 170.631C225.769 171.602 224.363 173.127 223.588 174.955C222.939 176.577 222.924 178.376 224.231 179.701C224.975 180.456 223.684 181.491 222.942 180.738C222.325 180.112 221.879 179.351 221.644 178.522C220.551 174.729 223.633 171.01 226.846 169.185C228.97 167.978 231.32 167.197 233.516 166.124C235.623 165.154 237.432 163.684 238.767 161.857C241.282 158.294 241.797 153.684 240.918 149.547C239.951 144.996 237.126 141 233.82 137.664Z" fill="white"/>
                                        <path d="M215.634 136.236C213.044 132.042 208.228 88.0634 204.65 84.3964L206.907 85.5514C210.42 89.3882 215.377 132.468 217.79 136.85C220.006 140.877 221.416 145.312 220.885 149.567C220.395 153.499 218.407 157.312 214.819 159.623C212.933 160.797 210.698 161.529 208.303 161.755C205.788 162.035 203.19 161.957 200.703 162.346C198.696 162.618 196.926 163.482 195.685 164.799C194.619 165.979 194.12 167.504 195.024 169.031C195.538 169.901 194.013 170.385 193.499 169.518C193.072 168.796 192.847 168.012 192.844 167.235C192.812 163.676 196.794 161.459 200.388 160.893C202.765 160.519 205.245 160.576 207.656 160.337C209.953 160.158 212.096 159.463 213.878 158.32C217.269 156.062 219.012 152.301 219.28 148.515C219.574 144.349 217.926 140.086 215.634 136.236Z" fill="white"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M171.004 255.975C171.031 255.461 171.183 254.957 171.452 254.509L202.128 203.516C202.558 202.802 203.252 202.29 204.058 202.091C204.863 201.892 205.713 202.023 206.423 202.455L212.495 206.163C213.204 206.597 213.712 207.297 213.91 208.108C214.107 208.919 213.977 209.776 213.548 210.491L187.197 254.295L206.573 284.335C207.106 285.162 207.289 286.166 207.082 287.127C206.874 288.089 206.293 288.928 205.467 289.462L198.39 294.026C197.563 294.558 196.559 294.74 195.598 294.533C194.637 294.326 193.797 293.746 193.263 292.92L171.433 259.075C170.934 258.306 170.74 257.379 170.888 256.475C170.899 256.411 170.911 256.347 170.925 256.282C170.947 256.179 170.974 256.076 171.004 255.975Z" fill="url(#paint3_linear_201_573)"/>
                                        <path d="M144.385 33.4197C143.841 32.1287 142.931 31.0255 141.767 30.2466C140.602 29.4676 139.235 29.0471 137.835 29.0372H7.08753C5.20811 29.0382 3.40595 29.7852 2.077 31.1141C0.748052 32.4431 0.00100986 34.2452 0 36.1247V201.532C0.00100986 203.411 0.748052 205.213 2.077 206.542C3.40595 207.871 5.20811 208.618 7.08753 208.619H137.839C139.718 208.617 141.52 207.869 142.848 206.541C144.177 205.212 144.924 203.411 144.927 201.532V36.1247C144.93 35.1962 144.747 34.2765 144.39 33.4197H144.385ZM143.348 201.532C143.346 202.994 142.765 204.395 141.731 205.429C140.698 206.462 139.296 207.044 137.835 207.045H7.08753C5.62547 207.045 4.22335 206.464 3.18943 205.431C2.15552 204.397 1.57443 202.995 1.57393 201.533V36.1256C1.57592 34.6639 2.15746 33.2627 3.19103 32.2291C4.2246 31.1956 5.62585 30.614 7.08753 30.612H137.839C138.94 30.6162 140.015 30.9484 140.927 31.5661C141.838 32.1839 142.545 33.0592 142.957 34.0804C143.037 34.2839 143.106 34.4915 143.164 34.7024C143.289 35.1665 143.353 35.645 143.353 36.1256L143.348 201.532Z" fill="white"/>
                                        <path d="M117.1 68.0894H77.7167C76.8811 68.0894 76.0796 67.7574 75.4888 67.1665C74.8979 66.5756 74.5659 65.7743 74.5659 64.9386C74.5659 64.103 74.8979 63.3016 75.4888 62.7107C76.0796 62.1198 76.8811 61.7879 77.7167 61.7879H117.1C117.935 61.7879 118.737 62.1198 119.327 62.7107C119.918 63.3016 120.25 64.103 120.25 64.9386C120.25 65.7743 119.918 66.5756 119.327 67.1665C118.737 67.7574 117.935 68.0894 117.1 68.0894Z" fill="white"/>
                                        <path d="M167.591 247.656C166.633 247.622 165.721 247.344 165.046 246.88C164.371 246.416 163.986 245.803 163.971 245.168L185.251 207.354C185.244 207.032 185.333 206.714 185.513 206.418C185.693 206.122 185.96 205.855 186.299 205.63C186.639 205.406 187.043 205.23 187.491 205.111C187.938 204.992 188.418 204.934 188.905 204.939L225.076 205.94C226.059 205.95 227.007 206.218 227.712 206.685C228.417 207.153 228.821 207.781 228.835 208.431L246.528 246.045C246.535 246.367 246.446 246.685 246.266 246.981C246.086 247.277 245.819 247.544 245.48 247.769C245.14 247.993 244.735 248.169 244.288 248.288C243.841 248.407 243.361 248.465 242.874 248.46L167.731 247.659C167.684 247.659 167.637 247.658 167.591 247.656Z" fill="#747374"/>
                                        <path d="M117.1 84.63H77.717C77.3029 84.6306 76.8927 84.5495 76.5099 84.3914C76.1272 84.2333 75.7794 84.0013 75.4863 83.7087C75.1933 83.4161 74.9609 83.0685 74.8023 82.686C74.6437 82.3035 74.562 81.8934 74.562 81.4793C74.562 81.0652 74.6437 80.6551 74.8023 80.2726C74.9609 79.89 75.1933 79.5425 75.4863 79.2499C75.7794 78.9573 76.1272 78.7253 76.5099 78.5672C76.8927 78.4091 77.3029 78.328 77.717 78.3286H117.1C117.514 78.328 117.924 78.4091 118.307 78.5672C118.69 78.7253 119.037 78.9573 119.33 79.2499C119.624 79.5425 119.856 79.89 120.015 80.2726C120.173 80.6551 120.255 81.0652 120.255 81.4793C120.255 81.8934 120.173 82.3035 120.015 82.686C119.856 83.0685 119.624 83.4161 119.33 83.7087C119.037 84.0013 118.69 84.2333 118.307 84.3914C117.924 84.5495 117.514 84.6306 117.1 84.63Z" fill="white"/>
                                        <path d="M57.5096 94.0483H28.7288C27.789 94.0473 26.8879 93.6736 26.2234 93.009C25.5589 92.3445 25.1851 91.4434 25.1841 90.5036V56.54C25.1851 55.6002 25.5589 54.6992 26.2234 54.0346C26.8879 53.3701 27.789 52.9963 28.7288 52.9953H57.5096C58.4494 52.9963 59.3504 53.3701 60.015 54.0346C60.6795 54.6992 61.0533 55.6002 61.0543 56.54V90.5036C61.0533 91.4434 60.6795 92.3445 60.015 93.009C59.3504 93.6736 58.4494 94.0473 57.5096 94.0483Z" fill="url(#paint4_linear_201_573)"/>
                                        <path d="M116.826 118.499H27.8212C26.9855 118.499 26.1841 118.167 25.5932 117.577C25.0024 116.986 24.6704 116.184 24.6704 115.349C24.6704 114.513 25.0024 113.712 25.5932 113.121C26.1841 112.53 26.9855 112.198 27.8212 112.198H116.826C117.662 112.198 118.463 112.53 119.054 113.121C119.645 113.712 119.977 114.513 119.977 115.349C119.977 116.184 119.645 116.986 119.054 117.577C118.463 118.167 117.662 118.499 116.826 118.499Z" fill="#747374"/>
                                        <path d="M116.826 135.04H27.8212C26.9855 135.04 26.1841 134.708 25.5932 134.117C25.0024 133.526 24.6704 132.725 24.6704 131.889C24.6704 131.054 25.0024 130.252 25.5932 129.661C26.1841 129.071 26.9855 128.739 27.8212 128.739H116.826C117.662 128.739 118.463 129.071 119.054 129.661C119.645 130.252 119.977 131.054 119.977 131.889C119.977 132.725 119.645 133.526 119.054 134.117C118.463 134.708 117.662 135.04 116.826 135.04Z" fill="#747374"/>
                                        <path d="M116.826 151.581H27.8213C26.9864 151.58 26.1861 151.247 25.5961 150.657C25.0062 150.066 24.6748 149.265 24.6748 148.43C24.6748 147.595 25.0062 146.794 25.5961 146.204C26.1861 145.613 26.9864 145.28 27.8213 145.279H116.826C117.241 145.279 117.651 145.36 118.033 145.518C118.416 145.676 118.764 145.908 119.057 146.201C119.35 146.493 119.583 146.841 119.741 147.223C119.9 147.606 119.981 148.016 119.981 148.43C119.981 148.844 119.9 149.254 119.741 149.637C119.583 150.019 119.35 150.367 119.057 150.66C118.764 150.952 118.416 151.184 118.033 151.342C117.651 151.5 117.241 151.581 116.826 151.581Z" fill="#747374"/>
                                        <path d="M116.826 168.122H27.8212C26.9855 168.122 26.1841 167.79 25.5932 167.199C25.0024 166.608 24.6704 165.806 24.6704 164.971C24.6704 164.135 25.0024 163.334 25.5932 162.743C26.1841 162.152 26.9855 161.82 27.8212 161.82H116.826C117.662 161.82 118.463 162.152 119.054 162.743C119.645 163.334 119.977 164.135 119.977 164.971C119.977 165.806 119.645 166.608 119.054 167.199C118.463 167.79 117.662 168.122 116.826 168.122Z" fill="#747374"/>
                                        <path d="M116.826 184.662H27.8212C26.9855 184.662 26.1841 184.33 25.5932 183.739C25.0024 183.149 24.6704 182.347 24.6704 181.511C24.6704 180.676 25.0024 179.874 25.5932 179.284C26.1841 178.693 26.9855 178.361 27.8212 178.361H116.826C117.662 178.361 118.463 178.693 119.054 179.284C119.645 179.874 119.977 180.676 119.977 181.511C119.977 182.347 119.645 183.149 119.054 183.739C118.463 184.33 117.662 184.662 116.826 184.662Z" fill="#747374"/>
                                        <path d="M145.362 60.8133C162.155 60.8133 175.769 47.1998 175.769 30.4066C175.769 13.6135 162.155 0 145.362 0C128.569 0 114.956 13.6135 114.956 30.4066C114.956 47.1998 128.569 60.8133 145.362 60.8133Z" fill="url(#paint5_linear_201_573)"/>
                                        <path d="M142.349 42.0059C141.665 42.007 140.999 41.7857 140.452 41.3753L140.418 41.3499L133.272 35.8834C132.941 35.6297 132.663 35.3133 132.454 34.9522C132.246 34.5911 132.11 34.1925 132.056 33.7791C132.001 33.3656 132.028 32.9455 132.136 32.5426C132.244 32.1398 132.43 31.7621 132.684 31.4311C132.937 31.1001 133.254 30.8224 133.615 30.6137C133.976 30.4051 134.375 30.2696 134.788 30.2149C135.202 30.1603 135.622 30.1877 136.025 30.2955C136.427 30.4032 136.805 30.5893 137.136 30.843L141.765 34.3924L152.702 20.1231C152.956 19.7923 153.272 19.5147 153.633 19.3061C153.994 19.0975 154.393 18.962 154.806 18.9075C155.219 18.8529 155.639 18.8802 156.042 18.9879C156.444 19.0957 156.822 19.2816 157.153 19.5353L157.154 19.536L157.086 19.6302L157.155 19.536C157.823 20.0489 158.26 20.8057 158.37 21.6402C158.48 22.4747 158.254 23.3189 157.743 23.9873L144.878 40.7636C144.58 41.1502 144.197 41.463 143.759 41.6777C143.321 41.8925 142.84 42.0035 142.352 42.0021L142.349 42.0059Z" fill="white"/>
                                        <path d="M337.74 303H130.868C130.719 303 130.576 302.941 130.47 302.835C130.365 302.73 130.306 302.587 130.306 302.438C130.306 302.289 130.365 302.146 130.47 302.04C130.576 301.935 130.719 301.876 130.868 301.876H337.74C337.889 301.876 338.032 301.935 338.137 302.04C338.243 302.146 338.302 302.289 338.302 302.438C338.302 302.587 338.243 302.73 338.137 302.835C338.032 302.941 337.889 303 337.74 303Z" fill="white"/>
                                        <path d="M184.932 203.272C185.266 201.937 186.466 201 187.842 201H226.685C228.46 201 229.847 202.532 229.67 204.299L229.27 208.299C229.117 209.832 227.826 211 226.285 211H186.842C184.891 211 183.459 209.166 183.932 207.272L184.932 203.272Z" fill="#C4C4C4"/>
                                        <path d="M216.115 233.102C215.824 233.672 215.356 234.155 214.767 234.491C214.178 234.828 213.493 235.005 212.795 235L204.584 234.936C204.11 234.932 203.64 234.845 203.203 234.678C202.766 234.511 202.37 234.269 202.037 233.965C201.704 233.66 201.441 233.3 201.263 232.905C201.085 232.509 200.996 232.086 201 231.66L201.558 173.876C201.567 172.965 201.775 172.065 202.171 171.226C202.567 170.388 203.143 169.628 203.867 168.989C204.59 168.351 205.446 167.846 206.386 167.505C207.326 167.164 208.332 166.992 209.346 167C210.36 167.008 211.362 167.195 212.295 167.551C213.229 167.907 214.075 168.425 214.786 169.074C215.496 169.724 216.058 170.493 216.438 171.337C216.818 172.182 217.009 173.085 217 173.996L216.442 231.78C216.438 232.237 216.327 232.687 216.115 233.102Z" fill="url(#paint6_linear_201_573)"/>
                                        <defs>
                                        <linearGradient id="paint0_linear_201_573" x1="175.545" y1="165.098" x2="175.545" y2="185.24" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        <linearGradient id="paint1_linear_201_573" x1="213.139" y1="224.417" x2="213.139" y2="302.02" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        <linearGradient id="paint2_linear_201_573" x1="204.559" y1="92.9739" x2="204.559" y2="151.17" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        <linearGradient id="paint3_linear_201_573" x1="189.002" y1="248.793" x2="189.002" y2="294.617" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        <linearGradient id="paint4_linear_201_573" x1="43.1192" y1="52.9953" x2="43.1192" y2="94.0483" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        <linearGradient id="paint5_linear_201_573" x1="145.362" y1="0" x2="145.362" y2="60.8133" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        <linearGradient id="paint6_linear_201_573" x1="209" y1="167" x2="209" y2="235" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#F04A3A"/>
                                        <stop offset="1" stop-color="#FBAF3F"/>
                                        </linearGradient>
                                        </defs>
                                    </svg>

                                </div>
                            `);
                            
                            // Optional: attach handler to the close button
                            $('#close-modal').on('click', function () {
                                modal.style.display = 'none';
                            });
                        }
                        else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An unexpected error occurred.');
                    }
                });
                
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'courscribe_invite_client_script');
// AJAX handler for client registration
function courscribe_client_register() {
    check_ajax_referer('courscribe_client_register', 'courscribe_client_register_nonce');

    $email = sanitize_email($_POST['client_email']);
    $username = sanitize_user($_POST['client_username']);
    $password = $_POST['client_password'];
    $invite_code = sanitize_text_field($_POST['invite_code']);
    $curriculum_id = intval($_POST['curriculum_id']);

    // Validate inputs
    if (username_exists($username)) {
        wp_send_json_error(['message' => 'Username already exists.']);
    }
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Email already registered. Please log in.']);
    }

    // Validate invite
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_client_invites';
    $invite = $wpdb->get_row($wpdb->prepare(
        "SELECT id, curriculum_id FROM $table_name WHERE email = %s AND invite_code = %s AND status = 'Pending' AND expires_at > %s",
        $email,
        $invite_code,
        current_time('mysql')
    ));

    if (!$invite || $invite->curriculum_id != $curriculum_id) {
        wp_send_json_error(['message' => 'Invalid or expired invite.']);
    }

    // Register user
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    // Assign client role and update invite
    wp_update_user(['ID' => $user_id, 'role' => 'client']);
    $wpdb->update($table_name, ['status' => 'Accepted'], ['id' => $invite->id]);

    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    wp_send_json_success();
}
add_action('wp_ajax_courscribe_client_register', 'courscribe_client_register');
add_action('wp_ajax_nopriv_courscribe_client_register', 'courscribe_client_register');

// Update collaborator invite email to match professional design
function courscribe_send_collaborator_invite($email, $studio_id, $invite_code) {
    $studio = get_post($studio_id);
    $user = wp_get_current_user();
    $register_page_id = get_option('courscribe_register_page');
    $invite_url_base = $register_page_id ? get_permalink($register_page_id) : home_url('/courscribe-register');
    $invite_url = add_query_arg(['invite_code' => $invite_code, 'email' => urlencode($email), 'studio_id' => $studio_id], $invite_url_base);

    $email_content = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                .header { background-color: #2F2E30; padding: 20px; text-align: center; }
                .header img { max-width: 150px; }
                .content { padding: 30px; }
                .content h1 { color: #2F2E30; font-size: 24px; margin-bottom: 20px; }
                .content p { color: #666666; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #FBC275; color: #2F2E30; text-decoration: none; font-weight: bold; border-radius: 5px; }
                .footer { background-color: #2F2E30; color: #ffffff; text-align: center; padding: 15px; font-size: 14px; }
                .footer a { color: #FBC275; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . esc_url(home_url('/wp-content/plugins/courscribe/assets/images/logo.png')) . '" alt="Courscribe Logo">
                </div>
                <div class="content">
                    <h1>Invitation to Join Studio</h1>
                    <p>Dear Collaborator,</p>
                    <p>You have been invited by ' . esc_html($user->display_name) . ' to join the studio "<strong>' . esc_html($studio->post_title) . '</strong>" as a collaborator on Courscribe.</p>
                    <p>Please register or log in to join the studio and start contributing. This invitation expires on ' . date('F j, Y', strtotime('+7 days')) . '.</p>
                    <p style="text-align: center;">
                        <a href="' . esc_url($invite_url) . '" class="button">Join Studio</a>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' Courscribe. All rights reserved.</p>
                    <p><a href="' . esc_url(home_url()) . '">Visit our website</a> | <a href="' . esc_url(home_url('/contact')) . '">Contact Us</a></p>
                </div>
            </div>
        </body>
        </html>
    ';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    return wp_mail($email, 'Courscribe Collaborator Invitation', $email_content, $headers);
}

// Hook to handle collaborator invites (replace your existing logic)
function courscribe_handle_collaborator_invite() {
    if (isset($_POST['courscribe_submit_invite']) && wp_verify_nonce($_POST['courscribe_invite_nonce'], 'courscribe_invite_collaborators')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_invites';
        $post_id = intval($_POST['studio_id']); // Assuming studio_id is passed in the form
        $emails = array_map('sanitize_email', array_filter(array_map('trim', explode(',', $_POST['courscribe_invite_emails']))));
        $current_user = wp_get_current_user();

        // Get tier and collaborator limit (adjust based on your tier logic)
        $tier = get_user_meta($current_user->ID, '_courscribe_tier', true) ?: 'Basic';
        $collaborator_limit = ($tier === 'Pro') ? 10 : 5; // Example limits
        $collaborators = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_courscribe_studio_id' AND meta_value = %d",
            $post_id
        ));
        $current_collaborators = count($collaborators);
        $emails_to_invite = array_slice($emails, 0, max(0, $collaborator_limit - $current_collaborators));
        $error_messages = [];

        if (empty($emails_to_invite) && !empty($emails)) {
            $error_messages[] = 'Collaborator limit reached for your tier (' . $tier . ': ' . $collaborator_limit . ').';
        } else {
            $email_batches = array_chunk($emails_to_invite, 5);
            foreach ($email_batches as $batch) {
                foreach ($batch as $email) {
                    if (!is_email($email)) {
                        $error_messages[] = 'Invalid email: ' . esc_html($email);
                        continue;
                    }
                    $existing_invite = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table_name WHERE email = %s AND studio_id = %d AND status = 'Pending' AND expires_at > %s",
                        $email,
                        $post_id,
                        current_time('mysql')
                    ));
                    if ($existing_invite) {
                        $error_messages[] = 'Invite already sent to ' . esc_html($email);
                        continue;
                    }
                    $invite_code = wp_generate_password(12, false);
                    $insert_result = $wpdb->insert($table_name, [
                        'email' => $email,
                        'invite_code' => $invite_code,
                        'studio_id' => $post_id,
                        'status' => 'Pending',
                        'created_at' => current_time('mysql'),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                    ]);
                    if ($insert_result === false) {
                        $error_messages[] = 'Failed to save invite for ' . esc_html($email);
                        continue;
                    }
                    $mail_result = courscribe_send_collaborator_invite($email, $post_id, $invite_code);
                    if (!$mail_result) {
                        $error_messages[] = 'Failed to send email to ' . esc_html($email);
                        $wpdb->delete($table_name, ['email' => $email, 'studio_id' => $post_id], ['%s', '%d']);
                    }
                }
                wp_cache_flush();
            }
            if (empty($error_messages) && !empty($emails_to_invite)) {
                echo '<p>Invites sent successfully!</p>';
            } elseif (!empty($error_messages)) {
                echo '<p>Errors occurred:<br>' . implode('<br>', array_map('esc_html', $error_messages)) . '</p>';
            }
        }
    }
}
add_action('wp', 'courscribe_handle_collaborator_invite');

function courscribe_update_curriculum() {
    check_ajax_referer('courscribe_curriculum', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Permission denied']);
        wp_die();
    }

    parse_str($_POST['data'], $form_data);
    $curriculum_id = intval($form_data['curriculum_id']);

    update_post_meta($curriculum_id, '_curriculum_topic', sanitize_text_field($form_data['curriculum_title']));
    update_post_meta($curriculum_id, '_curriculum_topic', sanitize_text_field($form_data['curriculum_topic']));
    update_post_meta($curriculum_id, '_curriculum_goal', sanitize_text_field($form_data['curriculum_goal']));
    update_post_meta($curriculum_id, '_curriculum_notes', wp_kses_post($form_data['curriculum_notes']));
    update_post_meta($curriculum_id, '_curriculum_status', sanitize_text_field($form_data['curriculum_status']));
    update_post_meta($curriculum_id, '_studio_id', intval($form_data['curriculum_studio']));

    wp_send_json_success();
    wp_die();
}
add_action('wp_ajax_courscribe_update_curriculum', 'courscribe_update_curriculum');
function courscribe_archive_curriculum() {
    check_ajax_referer('courscribe_archive', 'nonce');
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    $post_id = intval($_POST['post_id']);
    update_post_meta($post_id, '_curriculum_status', 'archived');
    wp_send_json_success();
    wp_die();
}
add_action('wp_ajax_courscribe_archive_curriculum', 'courscribe_archive_curriculum');

function courscribe_delete_curriculum() {
    check_ajax_referer('courscribe_delete', 'nonce');
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    $post_id = intval($_POST['post_id']);
    wp_delete_post($post_id, true);
    wp_send_json_success();
    wp_die();
}
add_action('wp_ajax_courscribe_delete_curriculum', 'courscribe_delete_curriculum');