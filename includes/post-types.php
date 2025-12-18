<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register custom post types
add_action( 'init', 'courscribe_register_post_types' );

function courscribe_register_post_types() {
    //error_log( 'Courscribe: courscribe_register_post_types called' );

    // Studio post type
    $studio_result = register_post_type( 'crscribe_studio', array(
        'labels' => array(
            'name' => 'Studios',
            'singular_name' => 'Studio',
            'add_new' => 'Add New Studio',
            'add_new_item' => 'Add New Studio',
            'edit_item' => 'Edit Studio',
            'new_item' => 'New Studio',
            'view_item' => 'View Studio',
            'search_items' => 'Search Studios',
            'not_found' => 'No studios found',
            'not_found_in_trash' => 'No studios found in Trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array( 'title', 'editor', 'thumbnail' ),
        'show_in_menu' => true,
        'capability_type' => 'crscribe_studio',
        'map_meta_cap' => true,
        'rewrite' => array( 'slug' => 'courscribe-studio' ),
        'show_in_rest' => true,
    ) );
    if ( is_wp_error( $studio_result ) ) {
        //error_log( 'Courscribe: Failed to register crscribe_studio: ' . $studio_result->get_error_message() );
    } else {
       // error_log( 'Courscribe: Registered post type crscribe_studio' );
    }

    // Curriculum post type
    $curriculum_result = register_post_type( 'crscribe_curriculum', array(
        'labels' => array(
            'name' => 'Curriculums',
            'singular_name' => 'Curriculum',
            'add_new' => 'Add New Curriculum',
            'add_new_item' => 'Add New Curriculum',
            'edit_item' => 'Edit Curriculum',
            'new_item' => 'New Curriculum',
            'view_item' => 'View Curriculum',
            'search_items' => 'Search Curriculums',
            'not_found' => 'No curriculums found',
            'not_found_in_trash' => 'No curriculums found in Trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array( 'title', 'thumbnail' ),
        'show_in_menu' => true,
        'capability_type' => 'crscribe_curriculum',
        'map_meta_cap' => true,
        'rewrite' => array( 'slug' => 'courscribe-curriculum' ),
        'show_in_rest' => true,
    ) );
    if ( is_wp_error( $curriculum_result ) ) {
        error_log( 'Error registering crscribe_curriculum post type: ' . $curriculum_result->get_error_message() );
    } else {
        error_log( 'crscribe_curriculum post type registered successfully.' );
        
        // Check if rewrite rules need to be flushed
        if (get_option('courscribe_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('courscribe_flush_rewrite_rules');
            error_log('CourScribe: Rewrite rules flushed after post type registration');
        }
    }

    // Course post type
    $course_result = register_post_type( 'crscribe_course', array(
        'labels' => array(
            'name' => 'Courses',
            'singular_name' => 'Course',
            'add_new' => 'Add New Course',
            'add_new_item' => 'Add New Course',
            'edit_item' => 'Edit Course',
            'new_item' => 'New Course',
            'view_item' => 'View Course',
            'search_items' => 'Search Courses',
            'not_found' => 'No courses found',
            'not_found_in_trash' => 'No courses found in Trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array( 'title', 'thumbnail' ),
        'show_in_menu' => true,
        'capability_type' => 'crscribe_course',
        'map_meta_cap' => true,
        'rewrite' => array( 'slug' => 'courscribe-course' ),
        'show_in_rest' => true,
    ) );
    if ( is_wp_error( $course_result ) ) {
       //error_log( 'Courscribe: Failed to register crscribe_course: ' . $course_result->get_error_message() );
    } else {
      //  error_log( 'Courscribe: Registered post type crscribe_course' );
    }

    // Module post type
    $module_result = register_post_type( 'crscribe_module', array(
        'labels' => array(
            'name' => 'Modules',
            'singular_name' => 'Module',
            'add_new' => 'Add New Module',
            'add_new_item' => 'Add New Module',
            'edit_item' => 'Edit Module',
            'new_item' => 'New Module',
            'view_item' => 'View Module',
            'search_items' => 'Search Modules',
            'not_found' => 'No modules found',
            'not_found_in_trash' => 'No modules found in Trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array( 'title', 'thumbnail' ),
        'show_in_menu' => true,
        'capability_type' => 'crscribe_module',
        'map_meta_cap' => true,
        'rewrite' => array( 'slug' => 'courscribe-module' ),
        'show_in_rest' => true,
    ) );
    if ( is_wp_error( $module_result ) ) {
        //error_log( 'Courscribe: Failed to register crscribe_module: ' . $module_result->get_error_message() );
    } else {
        //error_log( 'Courscribe: Registered post type crscribe_module' );
    }

    // Lesson post type
    $lesson_result = register_post_type( 'crscribe_lesson', array(
        'labels' => array(
            'name' => 'Lessons',
            'singular_name' => 'Lesson',
            'add_new' => 'Add New Lesson',
            'add_new_item' => 'Add New Lesson',
            'edit_item' => 'Edit Lesson',
            'new_item' => 'New Lesson',
            'view_item' => 'View Lesson',
            'search_items' => 'Search Lessons',
            'not_found' => 'No lessons found',
            'not_found_in_trash' => 'No lessons found in Trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array( 'title', 'thumbnail' ),
        'show_in_menu' => true,
        'capability_type' => 'crscribe_lesson',
        'map_meta_cap' => true,
        'rewrite' => array( 'slug' => 'courscribe-lesson' ),
        'show_in_rest' => true,
    ) );
    if ( is_wp_error( $lesson_result ) ) {
       // error_log( 'Courscribe: Failed to register crscribe_lesson: ' . $lesson_result->get_error_message() );
    } else {
      //  error_log( 'Courscribe: Registered post type crscribe_lesson' );
    }
}