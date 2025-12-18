<?php
// includes/class-courscribe-frontend.php

if (!defined('ABSPATH')) {
    exit;
}

class Courscribe_Frontend {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_curriculum_scripts'));
    }

    public function enqueue_curriculum_scripts() {
        if ($this->is_curriculum_page()) {
            // Enqueue course create.js
            // wp_enqueue_script(
            //     'courscribe-create-course',
            //     plugins_url('assets/js/courscribe/courses/create.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            // // Enqueue course edit.js
            // wp_enqueue_script(
            //     'courscribe-edit-course',
            //     plugins_url('assets/js/courscribe/courses/edit.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            // Enqueue module create.js
            // wp_enqueue_script(
            //     'courscribe-create-module',
            //     plugins_url('assets/js/courscribe/modules/create.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            // Enqueue module edit.js
            // wp_enqueue_script(
            //     'courscribe-edit-module',
            //     plugins_url('assets/js/courscribe/modules/edit.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            // Enqueue lesson create.js
            // wp_enqueue_script(
            //     'courscribe-create-lesson',
            //     plugins_url('assets/js/courscribe/lessons/create.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            // Enqueue lesson edit.js
            // wp_enqueue_script(
            //     'courscribe-edit-lesson',
            //     plugins_url('assets/js/courscribe/lessons/edit.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            // Enqueue AI input field suggestions
            wp_enqueue_script(
                'courscribe-ai-suggestions',
                plugins_url('assets/js/courscribe/ai/input-field-suggestions.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue AI courses generation
            wp_enqueue_script(
                'courscribe-ai-generate-courses',
                plugins_url('assets/js/courscribe/ai/generate-courses.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue AI module generation
            wp_enqueue_script(
                'courscribe-ai-generate-modules',
                plugins_url('assets/js/courscribe/ai/generate-modules.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue AI lesson generation
            wp_enqueue_script(
                'courscribe-ai-generate-lessons',
                plugins_url('assets/js/courscribe/ai/generate-lessons.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue teaching points create.js
            // wp_enqueue_script(
            //     'courscribe-create-teaching-points',
            //     plugins_url('assets/js/courscribe/teaching-points/create.js', dirname(__FILE__)),
            //     array('jquery'),
            //     '1.0.0',
            //     true
            // );

            wp_enqueue_script(
                'transformers',
                'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.6.0',
                [],
                '2.6.0',
                true
            );

            // Enqueue dictation input-field-dictation.js
            wp_enqueue_script(
                'courscribe-dictation',
                plugins_url('assets/js/courscribe/dictation/input-field-dictation.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue courscribe-pdfme.js
            wp_enqueue_script(
                'courscribe-richtexteditor',
                plugins_url('assets/js/courscribe/courscribe-richtexteditor.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue upload images.js
            wp_enqueue_script(
                'courscribe-upload-images',
                plugins_url('assets/js/courscribe/upload/upload-images.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue slide deck generation
            wp_enqueue_script(
                'courscribe-generate-slide-decks',
                plugins_url('assets/js/courscribe/slide-decks/generate-for-course.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Enqueue drag-and-drop sorting
            wp_enqueue_script(
                'courscribe-drag-and-drop',
                plugins_url('assets/js/courscribe/drag-and-drop/sort.js', dirname(__FILE__)),
                array('jquery', 'jquery-ui-sortable'),
                '1.0.0',
                true
            );

            // Enqueue tab switching
            wp_enqueue_script(
                'courscribe-tabs',
                plugins_url('assets/js/courscribe/tabs.js', dirname(__FILE__)),
                array(),
                '1.0.0',
                true
            );
            // Enqueue courscribe accordion
            wp_enqueue_script(
                'courscribe-tabs',
                plugins_url('assets/js/courscribe/accordion.js', dirname(__FILE__)),
                array(),
                '1.0.0',
                true
            );
            // Enqueue stepper
            wp_enqueue_script(
                'courscribe-stepper',
                plugins_url('assets/js/courscribe/stepper.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );
            wp_enqueue_script(
                'modules-premium-enhanced',
                plugins_url('assets/js/courscribe/modules/modules-premium-enhanced.js', dirname(__FILE__)),
                ['jquery'],
                '1.0.0',
                true
            );
            wp_enqueue_script(
                'courscribe-lessons-premium',
                plugins_url('assets/js/courscribe/lessons-premium.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );

            // Localize with ajaxurl and curriculum_id
            $curriculum_id = $this->get_curriculum_id_from_url(); // Implement this method
            wp_localize_script(
                'courscribe-create-course',
                'courscribeAjax',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'curriculum_id' => $curriculum_id ? $curriculum_id : 0,
                    'site_url' => home_url()
                )
            );
        }
        if($this->is_curriculum_preview_page()){
            // Enqueue tab switching
            wp_enqueue_script(
                'courscribe-tabs',
                plugins_url('assets/js/courscribe/tabs.js', dirname(__FILE__)),
                array(),
                '1.0.0',
                true
            );
        }
    }

    private function is_curriculum_page() {
        // Check via query vars
        if (isset($_GET['post_type'], $_GET['p']) && $_GET['post_type'] === 'crscribe_curriculum' && is_singular('crscribe_curriculum')) {
            return true;
        }

        // Also support pretty permalinks
        global $post;
        if ($post instanceof WP_Post && $post->post_type === 'crscribe_curriculum') {
            return true;
        }

        return false;
    }

    private function is_curriculum_preview_page() {
        $current_url = add_query_arg(NULL, NULL);
        $parsed_url = parse_url($current_url);

        // Check if we're on the preview-curriculum path
        if (isset($parsed_url['path']) && strpos($parsed_url['path'], '/preview-curriculum/') !== false) {
            // Check if required parameters exist
            if (isset($_GET['curriculum_id'])) {
                return true;
            }
        }

        return false;
    }
    private function get_curriculum_id_from_url() {
        // If the post_type and ID are passed via query (e.g. ?post_type=crscribe_curriculum&p=132)
        if (isset($_GET['post_type'], $_GET['p']) && $_GET['post_type'] === 'crscribe_curriculum') {
            return intval($_GET['p']);
        }

        // If we're on a singular curriculum post
        if (is_singular('crscribe_curriculum')) {
            global $post;
            return $post instanceof WP_Post ? $post->ID : 0;
        }

        // Optional: fallback based on the slug in the URL path (for non-standard setups)
        $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        if (preg_match('#/([^/]+)/?$#', $request_uri, $matches)) {
            $curriculum_name = sanitize_title($matches[1]);
            $curriculum = get_posts(array(
                'post_type' => 'crscribe_curriculum',
                'name' => $curriculum_name,
                'posts_per_page' => 1,
                'fields' => 'ids',
            ));
            return !empty($curriculum) ? $curriculum[0] : 0;
        }

        return 0;
    }
}

new Courscribe_Frontend();