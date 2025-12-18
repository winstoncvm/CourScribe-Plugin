<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Shortcode for studio creation
add_shortcode( 'courscribe_create_studio', 'courscribe_create_studio_shortcode' );

/**
 * Shortcode for creating a new studio.
 *
 * @return string The HTML output for the studio creation form.
 */
function courscribe_create_studio_shortcode() {
    ob_start();
    $site_url = home_url();
    // Early returns for access control
    if (!is_user_logged_in()) {
        return courscribe_retro_tv_error("You must be logged in to create a studio.");
    }

    $current_user = wp_get_current_user();
    if (!can_user_create_studio($current_user)) {
        return courscribe_retro_tv_error("You do not have permission to create studios.");
    }

    // Check if user already has a studio
    $studio_query = new WP_Query([
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'author' => $current_user->ID,
        'posts_per_page' => 1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    // Debug: Log studio query results
    error_log('Courscribe: Studio query found posts: ' . ($studio_query->have_posts() ? 'Yes' : 'No'));
    if ($studio_query->have_posts()) {
        error_log('Courscribe: Found studio ID: ' . implode(', ', $studio_query->posts));
    }


    // If user has a studio, redirect them to the studio page
    if ($studio_query->have_posts()) {
        $studio_url = home_url('/studio/');
        echo "<script>window.location.href = '" . esc_url($studio_url) . "';</script>";
        return '';
    }

    // Initialize output buffer for potential messages
    $output = '';

    // Handle form submission
    if (is_valid_form_submission()) {
        $result = process_studio_creation($current_user);
        if (is_wp_error($result)) {
            $output .= get_error_message($result);
        } else {
            // Debug: Log successful creation
            error_log("Courscribe: Studio created with ID {$result} for user {$current_user->ID}");
            $studio_url = home_url('/select-tribe');
            echo "<script>window.location.href = '" . esc_url($studio_url) . "';</script>";
            return '';
        }
    }

    // Render form
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

    <div class="courscribe-create-studio courscribe-curriculum-manager">
        <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
        <h3 class="courscribe-heading">Build Your Creative Hub!<br>
            Launch Your Studio with <span>CourScribe.</span></h3>
        <p class="courscribe-subheading">Set up your studio to organize, create, and collaborate on your educational content empire.</p>
        <div class="curriculum-box">
            <form method="post" class="courscribe-curriculum-form">
                <?php wp_nonce_field('courscribe_create_studio', 'courscribe_studio_nonce'); ?>
                <div class="mb-3">
                    <label for="courscribe_studio_title">Studio Title <span style="color: red;">*</span></label>
                    <input type="text" id="courscribe_studio_title" name="courscribe_studio_title" class="form-control bg-dark text-light ml-2 pl-2" required />
                </div>
                <div class="mb-3">
                    <label for="courscribe_studio_description">Description <span style="color: red;">*</span></label>
                    <?php
                    wp_editor('', 'courscribe_studio_description', array(
                        'textarea_name' => 'courscribe_studio_description',
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => false,
                        'textarea_rows' => 5,
                        'editor_height' => 150,
                        'editor_class' => 'form-control bg-dark text-light'
                    ));
                    ?>
                </div>
                <div class="mb-3">
                    <label for="courscribe_studio_email">Contact Email <span style="color: red;">*</span></label>
                    <input type="email" id="courscribe_studio_email" name="courscribe_studio_email" class="form-control bg-dark text-light ml-2 pl-2" required />
                </div>
                <div class="mb-3">
                    <label for="courscribe_studio_website">Website</label>
                    <input type="url" id="courscribe_studio_website" name="courscribe_studio_website" class="form-control bg-dark text-light ml-2 pl-2" placeholder="https://example.com" />
                </div>
                <div class="mb-3">
                    <label for="courscribe_studio_address">Address</label>
                    <textarea id="courscribe_studio_address" name="courscribe_studio_address" class="form-control bg-dark text-light ml-2 pl-2" rows="3"></textarea>
                </div>
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <button type="submit" class="btn courscribe-stepper-nextBtn" name="courscribe_submit_studio">Create Studio</button>
                </div>
            </form>
        </div>
        <style>
            .courscribe-create-studio { max-width: 800px; margin: 0 auto; padding: 20px; }
            .courscribe-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
            .courscribe-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
            .form-control { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; }
            .curriculum-box { background: #2a2a2a; padding: 20px; border-radius: 8px; }
            .curriculum-input-wrapper { display: flex; align-items: center; }
            .curriculum-input-icon { width: 20px; height: 20px; margin-right: 10px; }
            .courscribe-curriculum-form label { color: #fff; font-weight: bold; }
            .courscribe-p-gray { color: #adb5bd; }
        </style>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Check if user can create a studio.
 *
 * @param WP_User $user The user to check.
 * @return bool
 */
function can_user_create_studio($user) {
    return in_array('studio_admin', $user->roles) || current_user_can('publish_crscribe_studios');
}

/**
 * Validate form submission.
 *
 * @return bool
 */
function is_valid_form_submission() {
    return isset($_POST['courscribe_submit_studio']) && 
           isset($_POST['courscribe_studio_nonce']) && 
           wp_verify_nonce($_POST['courscribe_studio_nonce'], 'courscribe_create_studio');
}

/**
 * Process the studio creation.
 *
 * @param WP_User $user Current user.
 * @return int|WP_Error Post ID on success, WP_Error on failure.
 */
function process_studio_creation($user) {
    $form_data = validate_and_sanitize_form_data();
    if (is_wp_error($form_data)) {
        return $form_data;
    }

    $post_data = array(
        'post_title' => $form_data['title'],
        'post_content' => $form_data['description'],
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'post_author' => $user->ID,
    );

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        error_log("Courscribe: Failed to create studio: " . $post_id->get_error_message());
        return $post_id;
    }

    // Save meta data and assign studio to user
    save_studio_meta_data($post_id, $form_data);

    // Assign studio_admin role if not already set
    if (!in_array('studio_admin', $user->roles)) {
        $user->add_role('studio_admin');
        error_log("Courscribe: Assigned studio_admin role to user {$user->ID}");
    }

    // Store studio ID in user meta for explicit association
    update_user_meta($user->ID, 'courscribe_studio_id', $post_id);
    error_log("Courscribe: Associated studio {$post_id} with user {$user->ID} via user meta");

    return $post_id;
}

/**
 * Validate and sanitize form data.
 *
 * @return array|WP_Error Sanitized data or WP_Error on validation failure.
 */
function validate_and_sanitize_form_data() {
    $data = array(
        'title' => isset($_POST['courscribe_studio_title']) ? sanitize_text_field($_POST['courscribe_studio_title']) : '',
        'description' => isset($_POST['courscribe_studio_description']) ? wp_kses_post($_POST['courscribe_studio_description']) : '',
        'email' => isset($_POST['courscribe_studio_email']) ? sanitize_email($_POST['courscribe_studio_email']) : '',
        'website' => isset($_POST['courscribe_studio_website']) ? esc_url_raw($_POST['courscribe_studio_website']) : '',
        'address' => isset($_POST['courscribe_studio_address']) ? sanitize_textarea_field($_POST['courscribe_studio_address']) : ''
    );

    $errors = validate_form_data($data);
    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode(' ', $errors));
    }

    return $data;
}

/**
 * Validate form data.
 *
 * @param array $data Form data.
 * @return array List of error messages.
 */
function validate_form_data($data) {
    $errors = array();
    
    if (empty($data['title'])) {
        $errors[] = 'Studio Title is required.';
    }
    if (empty($data['description'])) {
        $errors[] = 'Description is required.';
    }
    if (empty($data['email'])) {
        $errors[] = 'Contact Email is required.';
    } elseif (!is_email($data['email'])) {
        $errors[] = 'Please enter a valid Contact Email.';
    }
    if (!empty($data['website']) && filter_var($data['website'], FILTER_VALIDATE_URL) === false) {
        $errors[] = 'Please enter a valid Website URL.';
    }

    return $errors;
}

/**
 * Save studio meta data.
 *
 * @param int $post_id The post ID.
 * @param array $data The form data.
 */
function save_studio_meta_data($post_id, $data) {
    update_post_meta($post_id, '_studio_email', $data['email']);
    update_post_meta($post_id, '_studio_website', $data['website']);
    update_post_meta($post_id, '_studio_address', $data['address']);
}

/**
 * Handle successful studio creation.
 *
 * @param int $post_id The created post ID.
 * @return void
 */


add_action('template_redirect', 'courscribe_check_studio_and_redirect');

function courscribe_check_studio_and_redirect() {
    if (!is_page('create-studio')) return; // Replace with your page slug

    if (!is_user_logged_in()) return;

    $current_user = wp_get_current_user();

    if (!can_user_create_studio($current_user)) return;

    $studio_query = new WP_Query([
        'post_type' => 'crscribe_studio',
        'post_status' => 'publish',
        'author' => $current_user->ID,
        'posts_per_page' => 1,
        'no_found_rows' => true,
        'fields' => 'ids',
    ]);

    if ($studio_query->have_posts()) {
        wp_safe_redirect(home_url('/studio/')); // or get_permalink($studio_query->posts[0])
        exit;
    }
}
/**
 * Render the studio creation form.
 *
 * @return string The HTML form.
 */
