<?php
/**
 * CourScribe Rewrite Rules Flush Utility
 * 
 * Run this file from the browser to force flush WordPress rewrite rules
 * URL: http://yoursite.com/wp-content/plugins/courscribe/flush-rewrite-rules.php
 */

// Include WordPress
$wp_config_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path;
} else {
    die('WordPress configuration file not found.');
}

// Force flush rewrite rules
flush_rewrite_rules(true);

// Set option to flush again on next post type registration
update_option('courscribe_flush_rewrite_rules', true);

echo '<h1>CourScribe Rewrite Rules Flushed</h1>';
echo '<p>Permalink structure has been refreshed.</p>';
echo '<p><strong>Next steps:</strong></p>';
echo '<ul>';
echo '<li>Test curriculum links: <a href="' . home_url('/courscribe-curriculum/') . '" target="_blank">' . home_url('/courscribe-curriculum/') . '</a></li>';
echo '<li><a href="' . admin_url() . '" target="_blank">Go to WordPress Admin</a></li>';
echo '</ul>';

// Log the action
error_log('CourScribe: Manual rewrite rules flush completed');