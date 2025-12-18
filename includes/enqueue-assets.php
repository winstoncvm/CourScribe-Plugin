<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue CSS and JS assets for Courscribe shortcodes
 */
function courscribe_enqueue_assets() {
    // Check if a Courscribe shortcode is present in the content
    global $post;
    $shortcodes = ['courscribe_select_tribe']; // Add other shortcodes as needed
    $has_shortcode = false;

    if ( is_a( $post, 'WP_Post' ) ) {
        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                $has_shortcode = true;
                break;
            }
        }
    }

    // Enqueue assets only if a shortcode is present
    if ( $has_shortcode ) {
        // Enqueue Google Fonts
        wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700', [], null );

        // Enqueue Nucleo Icons and other CSS files
        wp_enqueue_style( 'nucleo-icons', plugins_url( 'assets/css/nucleo-icons.css', COURScribe_DIR ), [], '1.0.0' );
        wp_enqueue_style( 'nucleo-svg', plugins_url( 'assets/css/nucleo-svg.css', COURScribe_DIR ), [], '1.0.0' );
        wp_enqueue_style( 'soft-ui-dashboard', plugins_url( 'assets/css/soft-ui-dashboard.css', COURScribe_DIR ), [], '1.0.7' );
        wp_enqueue_style( 'courscribe-lessons-premium', plugins_url( 'assets/css/lessons-premium.css', COURScribe_DIR ), [], '1.0.7' );

        // Enqueue Font Awesome
        wp_enqueue_script( 'font-awesome', 'https://kit.fontawesome.com/42d5adcbca.js', [], null, true );

        // Enqueue Core JS Files
        wp_enqueue_script( 'popper-js', plugins_url( 'assets/js/core/popper.min.js', COURScribe_DIR ), [], '1.0.0', true );
        wp_enqueue_script( 'bootstrap-js', plugins_url( 'assets/js/core/bootstrap.min.js', COURScribe_DIR ), ['popper-js'], '1.0.0', true );
        wp_enqueue_script( 'perfect-scrollbar-js', plugins_url( 'assets/js/plugins/perfect-scrollbar.min.js', COURScribe_DIR ), [], '1.0.0', true );
        wp_enqueue_script( 'smooth-scrollbar-js', plugins_url( 'assets/js/plugins/smooth-scrollbar.min.js', COURScribe_DIR ), [], '1.0.0', true );

        // Enqueue the main JS file for the dashboard
        wp_enqueue_script( 'soft-ui-dashboard', plugins_url( 'assets/js/soft-ui-dashboard.min.js', COURScribe_DIR ), ['bootstrap-js'], '1.0.7', true );
    }
}
add_action( 'wp_enqueue_scripts', 'courscribe_enqueue_assets' );
?>