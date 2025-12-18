<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Activation hook to add roles and flush rewrite rules
register_activation_hook( COURScribe_DIR . 'courscribe.php', 'courscribe_activate' );

function courscribe_activate() {
    courscribe_add_custom_roles();
    courscribe_register_post_types();
    flush_rewrite_rules();
    if ( ! get_option( 'courscribe_tier' ) ) {
        update_option( 'courscribe_tier', 'basics' );
    }
    error_log( 'Courscribe activated: roles added, post types registered, rewrite rules flushed' );
}