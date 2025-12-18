<?php
// Path: includes/helpers/class-courscribe-assets.php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('courscribe_enqueue_single_curriculum_scripts')) {
function courscribe_enqueue_single_curriculum_scripts($params = []) {
    $defaults = [
        'curriculum_id' => 0,
        'is_client' => false,
        'studio_id' => 0,
        'view_mode' => 'view',
        'can_edit' => false,
        'current_user_id' => get_current_user_id(),
        'target_user_id' => 0,
        'is_studio_admin' => false,
        'is_collaborator' => false,
        'course_id' => 0,
    ];
    $params = wp_parse_args($params, $defaults);

    error_log('CourScribe: Enqueue single curriculum scripts with params: ' . print_r($params, true));

    // Set base path to plugin root (move up three directories from templates/curriculums/helpers/)
    $base_path = plugin_dir_url(dirname(__FILE__, 3)); // Points to wp-content/plugins/courscribe/

    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue Bootstrap
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.0',
        true
    );

    // Enqueue Annotorious
    // wp_enqueue_script(
    //     'annotorious',
    //     'https://cdn.jsdelivr.net/npm/@recogito/annotorious@2.7.12/dist/annotorious.min.js',
    //     [],
    //     '2.7.12',
    //     true
    // );
    // wp_enqueue_style(
    //     'annotorious-css',
    //     'https://cdn.jsdelivr.net/npm/@recogito/annotorious@2.7.12/dist/annotorious.min.css',
    //     [],
    //     '2.7.12'
    // );

    // Enqueue html2canvas
    wp_enqueue_script(
        'html2canvas',
        'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
        [],
        '1.4.1',
        true
    );

    // Enqueue TourGuide.js
    wp_enqueue_script(
        'tourguide',
        'https://unpkg.com/@sjmc11/tourguidejs/dist/tour.js',
        [],
        null,
        ['strategy' => 'module']
    );
    wp_enqueue_style(
        'tourguide-css',
        'https://unpkg.com/@sjmc11/tourguidejs/dist/css/tour.min.css',
        [],
        null
    );

    // Enqueue alertbox
    wp_enqueue_script(
        'alertbox',
        'https://cdn.jsdelivr.net/gh/noumanqamar450/alertbox@main/version/1.0.2/alertbox.min.js',
        [],
        '1.0.2',
        true
    );

    // Enqueue custom scripts
    wp_enqueue_script(
        'courscribe-edit-curriculum-feedback',
        $base_path . 'assets/js/courscribe-feedback.js',
        ['jquery', 'annotorious', 'html2canvas', 'bootstrap'],
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'courscribe-edit-curriculum-accordion',
        $base_path . 'assets/js/courscribe-accordion.js',
        ['jquery'],
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'courscribe-edit-curriculum-tour',
        $base_path . 'assets/js/courscribe-tour.js',
        ['jquery', 'tourguide'],
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'courscribe-edit-curriculum-stepper',
        $base_path . 'assets/js/courscribe/stepper.js',
        ['jquery'],
        '1.0.0',
        true
    );
    // wp_enqueue_script(
    //     'courscribe-edit-curriculum-create-course',
    //     $base_path . 'assets/js/courscribe/courses/create.js',
    //     ['jquery'],
    //     '1.0.0',
    //     true
    // );
    // wp_enqueue_script(
    //     'courscribe-edit-curriculum-edit-course',
    //     $base_path . 'assets/js/courscribe/courses/edit.js',
    //     ['jquery'],
    //     '1.0.0',
    //     true
    // );
    wp_enqueue_script(
        'courscribe-edit-curriculum-create-module',
        $base_path . 'assets/js/courscribe/modules/create.js',
        ['jquery'],
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'modules-premium-enhanced',
        $base_path . 'assets/js/courscribe/modules/modules-premium-enhanced.js',
        ['jquery'],
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'courscribe-edit-curriculum-create-lesson',
        $base_path . 'assets/js/courscribe/lessons/create.js',
        ['jquery'],
        '1.0.0',
        true
    );
    // wp_enqueue_script(
    //     'courscribe-edit-curriculum-edit-lesson',
    //     $base_path . 'assets/js/courscribe/lessons/edit.js',
    //     ['jquery'],
    //     '1.0.0',
    //     true
    // );
    // wp_enqueue_script(
    //     'courscribe-edit-curriculum-create-teaching-points',
    //     $base_path . 'assets/js/courscribe/teaching-points/create.js',
    //     ['jquery'],
    //     '1.0.0',
    //     true
    // );
    // wp_enqueue_script(
    //     'courscribe-edit-curriculum-upload-images',
    //     $base_path . 'assets/js/courscribe/upload/upload-images.js',
    //     ['jquery'],
    //     '1.0.0',
    //     true
    // );
    wp_enqueue_script(
        'courscribe-edit-curriculum-slide-decks',
        $base_path . 'assets/js/courscribe/slide-decks/generate-for-course.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    // Enqueue premium generation assets
    wp_enqueue_script(
        'courscribe-generation-wizard-premium',
        $base_path . 'assets/js/courscribe/generation-wizard-premium.js',
        ['jquery', 'bootstrap'],
        '1.0.0',
        true
    );
    

    // Enqueue styles
    wp_enqueue_style(
        'courscribe-edit-curriculum-single-curriculum',
        $base_path . 'assets/css/soft-ui-dashboard.css',
        [],
        '1.0.0'
    );
    wp_enqueue_style(
        'curriculum-edit-curriculum-frontend',
        $base_path . 'assets/css/curriculum-frontend.css',
        [],
        '1.0.0'
    );
    wp_enqueue_style(
        'tabs-edit-curriculum',
        $base_path . 'assets/css/tabs.css',
        [],
        '1.0.0'
    );
    wp_enqueue_style(
        'dashboard-style-edit-curriculum',
        $base_path . 'assets/css/dashboard-style.css',
        [],
        '1.0.0'
    );
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css',
        [],
        '3.1.0'
    );
    wp_enqueue_style(
        'open-sans',
        'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700',
        [],
        null
    );
    wp_enqueue_style(
        'studio-edit-curriculum',
        $base_path . 'assets/css/studio.css',
        [],
        '1.0.0'
    );
    
    // Enqueue premium generation styles
    wp_enqueue_style(
        'courscribe-generation-wizard-premium',
        $base_path . 'assets/css/generation-wizard-premium.css',
        [],
        '1.0.0'
    );

    // Pass PHP variables
    wp_localize_script(
        'courscribe-edit-curriculum-feedback',
        'courscribeFeedback',
        [
            'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('courscribe_nonce'),
            'isClient' => $params['is_client'] ? true : false,
            'avatarUrl' => esc_url(home_url('/wp-content/plugins/courscribe/assets/images/profile.png')),
            'courseId' => absint($params['curriculum_id']),
        ]
    );

    wp_localize_script(
        'courscribe-edit-curriculum-tour',
        'courscribeTour',
        [
            'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('courscribe_nonce'),
            'curriculumId' => absint($params['curriculum_id']),
        ]
    );

    wp_localize_script(
        'courscribe-edit-curriculum-feedback',
        'courscribe_single_curriculum_vars',
        [
            'ajax_url' => esc_url(admin_url('admin-ajax.php')),
            'nonce' => wp_create_nonce('courscribe_nonce'),
            'current_user_id' => absint($params['current_user_id']),
            'is_client' => $params['is_client'] ? '1' : '0',
            'curriculum_id' => absint($params['curriculum_id']),
            'studio_id' => absint($params['studio_id']),
            'view_mode' => sanitize_text_field($params['view_mode']),
            'can_edit' => $params['can_edit'] ? '1' : '0',
            'target_user_id' => absint($params['target_user_id']),
            'is_studio_admin' => $params['is_studio_admin'] ? '1' : '0',
            'is_collaborator' => $params['is_collaborator'] ? '1' : '0',
        ]
    );

    // Localize script for premium generation system (courses, modules, lessons)
    wp_localize_script(
        'courscribe-generation-wizard-premium',
        'courscribeAjax',
        [
            'ajaxurl' => esc_url(admin_url('admin-ajax.php')),
            'generation_nonce' => wp_create_nonce('courscribe_generate_courses_nonce'),
            'module_generation_nonce' => wp_create_nonce('courscribe_generate_modules_nonce'),
            'lesson_generation_nonce' => wp_create_nonce('courscribe_generate_lessons_nonce'),
            'curriculum_id' => absint($params['curriculum_id']),
            'user_id' => absint($params['current_user_id']),
            'studio_id' => absint($params['studio_id']),
            'is_client' => $params['is_client'] ? '1' : '0',
        ]
    );

    wp_add_inline_script(
        'courscribe-edit-curriculum-feedback',
        'jQuery(document).ready(function($) {
            const siteUrl = window.location.origin;
            Feedback({
                h2cPath: siteUrl + "/wp-content/plugins/courscribe/assets/js/html2canvas.js"
            });
        });'
    );
}
}