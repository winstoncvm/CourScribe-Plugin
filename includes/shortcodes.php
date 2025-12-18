<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$studio_shortcodes_dir = COURScribe_DIR . 'templates/studio/shortcodes/'; 
$auth_shortcodes_dir = COURScribe_DIR . 'templates/auth/shortcodes/';
$courscribe_filters_dir = COURScribe_DIR . 'includes/filters/';
$curriculum_shortcodes_dir = COURScribe_DIR . 'templates/curriculums/shortcodes/';
$courscribe_landing_shortcodes_dir = COURScribe_DIR . 'templates/landing/';
$content_builder_shortcodes_dir = COURScribe_DIR . 'templates/curriculum-builder/';

//Filters
require_once $courscribe_filters_dir . 'courscribe_login_redirect.php';

// Studio Shortcodes
require_once $studio_shortcodes_dir . 'courscribe_studio_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_studio_shortcode_premium.php';
require_once $studio_shortcodes_dir . 'courscribe_select_tribe_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_create_studio_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_user_profile_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_studio_settings.php';
require_once $studio_shortcodes_dir . 'courscribe_premium_pricing_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_premium_settings_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_premium_analytics_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_premium_team_shortcode.php';
require_once $studio_shortcodes_dir . 'courscribe_premium_affiliate_shortcode.php';

// Auth Shortcodes
require_once $auth_shortcodes_dir . 'courscribe_register_shortcode.php';
require_once $auth_shortcodes_dir . 'courscribe_signin_shortcode.php';
require_once $auth_shortcodes_dir . 'courscribe_admin_setup_shortcode.php';
require_once $auth_shortcodes_dir . 'courscribe_set_new_password_shortcode.php';
require_once $auth_shortcodes_dir . 'courscribe_forgot_password_shortcode.php';

//Curriculums Development Shortcodes
require_once $curriculum_shortcodes_dir . 'courscribe_curriculum_manager_shortcode.php';
require_once $curriculum_shortcodes_dir . 'courscribe_single_curriculum_shortcode.php';
//require_once $curriculum_shortcodes_dir . 'courscribe_single_curriculum_shortcode_backup.php';
require_once $curriculum_shortcodes_dir . 'courscribe_curriculum_final_screen.php';

// Content Builder
require_once $content_builder_shortcodes_dir . 'curriculum-content-builder.php';


//Landing page
//require_once $courscribe_landing_shortcodes_dir . 'template-landing-page.php';



