<?php
/**
 * CourScribe Curriculum URL Debug Utility
 * 
 * Run this file from the browser to debug curriculum URL generation
 * URL: http://yoursite.com/wp-content/plugins/courscribe/debug-curriculum-urls.php
 */

// Include WordPress
$wp_config_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path;
} else {
    die('WordPress configuration file not found.');
}

echo '<h1>CourScribe Curriculum URL Debug</h1>';

// Get all curriculum posts
$curriculums = get_posts([
    'post_type' => 'crscribe_curriculum',
    'post_status' => ['publish', 'private', 'draft'],
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
]);

if (empty($curriculums)) {
    echo '<p><strong>No curriculum posts found.</strong></p>';
    echo '<p>Post statuses checked: publish, private, draft</p>';
} else {
    echo '<h2>Found ' . count($curriculums) . ' curriculum posts:</h2>';
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Title</th>';
    echo '<th>Slug (post_name)</th>';
    echo '<th>Status</th>';
    echo '<th>get_permalink()</th>';
    echo '<th>Manual URL</th>';
    echo '<th>Test Link</th>';
    echo '</tr>';
    
    foreach ($curriculums as $curriculum) {
        $permalink = get_permalink($curriculum->ID);
        $manual_url = home_url('/courscribe-curriculum/' . $curriculum->post_name . '/');
        
        echo '<tr>';
        echo '<td>' . $curriculum->ID . '</td>';
        echo '<td>' . esc_html($curriculum->post_title) . '</td>';
        echo '<td>' . esc_html($curriculum->post_name) . '</td>';
        echo '<td>' . $curriculum->post_status . '</td>';
        echo '<td><code>' . esc_html($permalink) . '</code></td>';
        echo '<td><code>' . esc_html($manual_url) . '</code></td>';
        echo '<td><a href="' . esc_url($manual_url) . '" target="_blank">Test</a></td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<h2>Rewrite Rules</h2>';
echo '<p>Current curriculum rewrite base: <strong>courscribe-curriculum</strong></p>';

// Check if post type is registered
$post_type_object = get_post_type_object('crscribe_curriculum');
if ($post_type_object) {
    echo '<p>✅ Post type <code>crscribe_curriculum</code> is registered</p>';
    echo '<p>Rewrite slug: <code>' . $post_type_object->rewrite['slug'] . '</code></p>';
    echo '<p>Has archive: ' . ($post_type_object->has_archive ? 'Yes' : 'No') . '</p>';
    echo '<p>Public: ' . ($post_type_object->public ? 'Yes' : 'No') . '</p>';
} else {
    echo '<p>❌ Post type <code>crscribe_curriculum</code> is NOT registered</p>';
}

echo '<h2>Actions</h2>';
echo '<p><a href="' . home_url('/wp-content/plugins/courscribe/flush-rewrite-rules.php') . '">🔄 Flush Rewrite Rules</a></p>';
echo '<p><a href="' . admin_url() . '" target="_blank">📝 Go to WordPress Admin</a></p>';
echo '<p><a href="' . home_url('/courscribe-curriculum/') . '" target="_blank">📚 Curriculum Archive</a></p>';
?>