<?php
// Path: includes/helpers/class-courscribe-curriculum.php

if (!defined('ABSPATH')) {
    exit;
}

class CourScribe_Curriculum {
    public function get_curriculum($atts) {
        $curriculum = null;
        $view_mode = 'view';
        $target_user_id = null;

        // Method 1: URL parameters
        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        $url_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        $url_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';

        if ($post_id) {
            $curriculum = get_post($post_id);
            if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
                $view_mode = $url_view ?: 'view';
                $target_user_id = $url_user_id ?: null;
                error_log('CourScribe: Found curriculum from URL parameters - ID: ' . $curriculum->ID . ', View: ' . $view_mode);
            } else {
                $curriculum = null;
                error_log('CourScribe: Invalid post_id in URL parameters: ' . $post_id);
            }
        }

        // Method 2: Shortcode attributes
        if (!$curriculum && isset($atts['post_id'])) {
            $post_id = absint($atts['post_id']);
            $curriculum = get_post($post_id);
            if ($curriculum && $curriculum->post_type === 'crscribe_curriculum') {
                $view_mode = isset($atts['view']) ? sanitize_text_field($atts['view']) : 'view';
                $target_user_id = isset($atts['user_id']) ? absint($atts['user_id']) : null;
                error_log('CourScribe: Found curriculum from shortcode attributes - ID: ' . $curriculum->ID);
            } else {
                $curriculum = null;
            }
        }

        // Method 3: Global post
        if (!$curriculum) {
            global $post;
            if ($post && $post->post_type === 'crscribe_curriculum') {
                $curriculum = $post;
                error_log('CourScribe: Found curriculum from global post - ID: ' . $curriculum->ID);
            }
        }

        // Method 4: Queried object
        if (!$curriculum) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'crscribe_curriculum') {
                $curriculum = $queried_object;
                error_log('CourScribe: Found curriculum from queried object - ID: ' . $curriculum->ID);
            }
        }

        // Method 5: URL slug
        if (!$curriculum) {
            $curriculum_slug = get_query_var('curriculum_slug') ?: basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            $curriculum_posts = get_posts([
                'post_type' => 'crscribe_curriculum',
                'post_status' => ['publish', 'draft', 'pending', 'future', 'trash'],
                'name' => $curriculum_slug,
                'posts_per_page' => 1
            ]);
            if (!empty($curriculum_posts)) {
                $curriculum = $curriculum_posts[0];
            }
        }

        return [
            'curriculum' => $curriculum,
            'view_mode' => $view_mode,
            'target_user_id' => $target_user_id,
            'studio_id' => $curriculum ? get_post_meta($curriculum->ID, '_studio_id', true) : null
        ];
    }
}