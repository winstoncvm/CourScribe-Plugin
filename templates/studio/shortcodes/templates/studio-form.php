<?php
if (!defined('ABSPATH')) {
    exit;
}

$site_url = home_url();
?>

<!-- Styles -->
<link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
<link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
<link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/tabs.css">
<link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">
<link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-icons.css">
<link rel="stylesheet" href="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/css/nucleo-svg.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">

<!-- Scripts -->
<script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
<script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
<script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
<script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
<script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/plugins/chartjs.min.js" defer></script>
<script src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>

<div class="courscribe-main-content position-relative border-radius-lg">
    <div class="py-4 px-0 courscribe-div-center-column w-100">
        <img src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;">
        <h3 class="courscribe-heading">
            Welcome to the savvy side of course creation.<br>
            Create your
            <span>Studio.</span>
        </h3>
        <p class="courscribe-pricing-subheading">This is your course creation station. Feel free to invite employees, peers, or clients to vibe with you on your CourScribe. <a rel="noopener noreferrer" href="<?php echo esc_url( home_url( '/register' ) ); ?>" class="">Sign Up</a></p>
        <p class="courscribe-pricing-subtitle">You can change this anytime.</p>
        <div class="courscribe-form-container">
    <?php echo $output; // Display any messages ?>
    
    <form method="post" class="courscribe-studio-form">
        <?php wp_nonce_field('courscribe_create_studio', 'courscribe_studio_nonce'); ?>
        
        <div class="form-group">
            <label for="courscribe_studio_title">Studio Title<span class="required">*</span></label>
            <input type="text" 
                   name="courscribe_studio_title" 
                   id="courscribe_studio_title" 
                   class="form-control"
                   value="<?php echo esc_attr(isset($_POST['courscribe_studio_title']) ? $_POST['courscribe_studio_title'] : ''); ?>" 
                   required>
        </div>

        <div class="form-group">
            <label for="courscribe_studio_description">Description<span class="required">*</span></label>
            <textarea name="courscribe_studio_description"
                      id="courscribe_studio_description"
                      class="form-control"
                      rows="5"
                      required><?php echo esc_textarea(isset($_POST['courscribe_studio_description']) ? $_POST['courscribe_studio_description'] : ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="courscribe_studio_email">Contact Email<span class="required">*</span></label>
            <input type="email" 
                   name="courscribe_studio_email" 
                   id="courscribe_studio_email" 
                   class="form-control"
                   value="<?php echo esc_attr(isset($_POST['courscribe_studio_email']) ? $_POST['courscribe_studio_email'] : ''); ?>" 
                   required>
        </div>

        <div class="form-group">
            <label for="courscribe_studio_website">Website URL</label>
            <input type="url" 
                   name="courscribe_studio_website" 
                   id="courscribe_studio_website" 
                   class="form-control bg-dark"
                   placeholder="https://example.com"
                   value="<?php echo esc_attr(isset($_POST['courscribe_studio_website']) ? $_POST['courscribe_studio_website'] : ''); ?>">
        </div>

        <div class="form-group">
            <label for="courscribe_studio_address">Address</label>
            <textarea name="courscribe_studio_address" 
                      id="courscribe_studio_address"
                      class="form-control"
                      rows="3"><?php echo esc_textarea(isset($_POST['courscribe_studio_address']) ? $_POST['courscribe_studio_address'] : ''); ?></textarea>
        </div>

        <div class="form-group submit-group">
            <button type="submit" name="courscribe_submit_studio" class="btn btn-primary">Create Studio</button>
        </div>
    </form>
</div>
    </div>
</div>

<style>
.courscribe-form-container {
    max-width: 800px;
    width: 100%;
    margin: 2rem auto;
    padding: 2rem;
}

.courscribe-studio-form {
    background: #2a2a2b;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 0.25rem 0.375rem -0.0625rem rgba(20, 20, 20, 0.12), 
                0 0.125rem 0.25rem -0.0625rem rgba(20, 20, 20, 0.07);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--bs-light);
    font-weight: 500;
}

.form-group label .required {
    color: #dc3545;
    margin-left: 0.25rem;
}

.form-control {
    background-color: var(--bs-gray-dark);
    border: 1px solid var(--bs-gray);
    color: var(--bs-light);
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    width: 100%;
}

/*.form-control:focus {*/
/*    border-color: var(--bs-primary);*/
/*    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);*/
/*}*/

.submit-group {
    margin-top: 2rem;
    text-align: center;
}

.btn-primary {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.courscribe-error,
.courscribe-success {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
}

.courscribe-error {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid #dc3545;
    color: #dc3545;
}

.courscribe-success {
    background-color: rgba(25, 135, 84, 0.1);
    border: 1px solid #198754;
    color: #198754;
}

@media (max-width: 768px) {
    .courscribe-form-container {
        padding: 1rem;
    }

    .courscribe-studio-form {
        padding: 1.5rem;
    }
}
</style>