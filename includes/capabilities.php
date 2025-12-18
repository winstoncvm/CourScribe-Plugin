<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Initialize and assign capabilities for Courscribe roles
 */
add_action( 'admin_init', 'courscribe_initialize_roles_and_capabilities' );

function courscribe_initialize_roles_and_capabilities() {
    // Define all Courscribe capabilities
    $courscribe_capabilities = array(
        'access_courscribe',
        // Studio capabilities
        'edit_crscribe_studio',
        'read_crscribe_studio',
        'delete_crscribe_studio',
        'edit_crscribe_studios',
        'edit_others_crscribe_studios',
        'publish_crscribe_studios',
        'read_private_crscribe_studios',
        'delete_crscribe_studios',
        'delete_private_crscribe_studios',
        'delete_published_crscribe_studios',
        'delete_others_crscribe_studios',
        'edit_private_crscribe_studios',
        'edit_published_crscribe_studios',
        'create_crscribe_studios',
        // Curriculum capabilities
        'edit_crscribe_curriculum',
        'read_crscribe_curriculum',
        'delete_crscribe_curriculum',
        'edit_crscribe_curriculums',
        'edit_others_crscribe_curriculums',
        'publish_crscribe_curriculums',
        'read_private_crscribe_curriculums',
        'delete_crscribe_curriculums',
        'delete_private_crscribe_curriculums',
        'delete_published_crscribe_curriculums',
        'delete_others_crscribe_curriculums',
        'edit_private_crscribe_curriculums',
        'edit_published_crscribe_curriculums',
        'create_crscribe_curriculums',
        // Module capabilities
        'edit_crscribe_module',
        'read_crscribe_module',
        'delete_crscribe_module',
        'edit_crscribe_modules',
        'edit_others_crscribe_modules',
        'publish_crscribe_modules',
        'read_private_crscribe_modules',
        'delete_crscribe_modules',
        'delete_private_crscribe_modules',
        'delete_published_crscribe_modules',
        'delete_others_crscribe_modules',
        'edit_private_crscribe_modules',
        'edit_published_crscribe_modules',
        'create_crscribe_modules',
        // Lesson capabilities
        'edit_crscribe_lesson',
        'read_crscribe_lesson',
        'delete_crscribe_lesson',
        'edit_crscribe_lessons',
        'edit_others_crscribe_lessons',
        'publish_crscribe_lessons',
        'read_private_crscribe_lessons',
        'delete_crscribe_lessons',
        'delete_private_crscribe_lessons',
        'delete_published_crscribe_lessons',
        'delete_others_crscribe_lessons',
        'edit_private_crscribe_lessons',
        'edit_published_crscribe_lessons',
        'create_crscribe_lessons',
        // Course capabilities
        'edit_crscribe_course',
        'read_crscribe_course',
        'delete_crscribe_course',
        'edit_crscribe_courses',
        'edit_others_crscribe_courses',
        'publish_crscribe_courses',
        'read_private_crscribe_courses',
        'delete_crscribe_courses',
        'delete_private_crscribe_courses',
        'delete_published_crscribe_courses',
        'delete_others_crscribe_courses',
        'edit_private_crscribe_courses',
        'edit_published_crscribe_courses',
        'create_crscribe_courses',
    );

    // Create or update Studio Admin role
    $studio_admin_role = get_role( 'studio_admin' );
    if ( ! $studio_admin_role ) {
        $studio_admin_role = add_role( 'studio_admin', 'Studio Admin', array( 'read' => true ) );
//        error_log( 'Courscribe: Created Studio Admin role' );
    }

    // Assign capabilities to Administrator role
    $admin_role = get_role( 'administrator' );
    if ( $admin_role ) {
        foreach ( $courscribe_capabilities as $cap ) {
            $admin_role->add_cap( $cap );
//            error_log( 'Courscribe: Assigned capability ' . $cap . ' to administrator' );
        }
        // Debug specific curriculum capabilities
        $key_caps = array(
            'edit_crscribe_curriculum',
            'edit_crscribe_curriculums',
            'publish_crscribe_curriculums',
            'create_crscribe_curriculums',
        );
        foreach ( $key_caps as $cap ) {
            if ( $admin_role->has_cap( $cap ) ) {
//                error_log( 'Courscribe: Administrator has capability ' . $cap );
            } else {
                error_log( 'Courscribe: ERROR - Administrator missing capability ' . $cap );
            }
        }
//        error_log( 'Courscribe: All capabilities assigned to administrator' );
    } else {
        error_log( 'Courscribe: ERROR - Administrator role not found' );
    }

    // Assign capabilities to Studio Admin role (same as Administrator for Courscribe)
    if ( $studio_admin_role ) {
        foreach ( $courscribe_capabilities as $cap ) {
            $studio_admin_role->add_cap( $cap );
            //error_log( 'Courscribe: Assigned capability ' . $cap . ' to studio_admin' );
        }
        // Debug specific curriculum capabilities
        foreach ( $key_caps as $cap ) {
            if ( $studio_admin_role->has_cap( $cap ) ) {
                //error_log( 'Courscribe: Studio Admin has capability ' . $cap );
            } else {
                error_log( 'Courscribe: ERROR - Studio Admin missing capability ' . $cap );
            }
        }
        //error_log( 'Courscribe: All capabilities assigned to studio_admin' );
    } else {
        error_log( 'Courscribe: ERROR - Studio Admin role not found' );
    }

    // Update Collaborator role with limited capabilities
    $collaborator_role = get_role( 'collaborator' );
    if ( ! $collaborator_role ) {
        $collaborator_role = add_role( 'collaborator', 'Collaborator', array( 'read' => true ) );
       // error_log( 'Courscribe: Created Collaborator role' );
    }
    if ( $collaborator_role ) {
        $collaborator_caps = array(
            'access_courscribe' => true,
            'read_crscribe_studio' => true,
            'read_crscribe_curriculum' => true,
            'read_crscribe_module' => true,
            'read_crscribe_lesson' => true,
            'read' => true,
        );
        // Remove all Courscribe capabilities first
        foreach ( $courscribe_capabilities as $cap ) {
            $collaborator_role->remove_cap( $cap );
        }
        // Add limited capabilities
        foreach ( $collaborator_caps as $cap => $grant ) {
            $collaborator_role->add_cap( $cap, $grant );
//            error_log( 'Courscribe: Assigned capability ' . $cap . ' to collaborator' );
        }
//        error_log( 'Courscribe: Collaborator capabilities assigned' );
    }
}

// Refresh capabilities on login for Studio Admin and Collaborator
add_action( 'wp_login', 'courscribe_refresh_user_capabilities', 10, 2 );

function courscribe_refresh_user_capabilities( $user_login, $user ) {
    // Refresh Studio Admin capabilities
    if ( in_array( 'studio_admin', (array) $user->roles ) ) {
        $role = get_role( 'studio_admin' );
        if ( $role ) {
            $caps = array(
                'access_courscribe' => true,
                'edit_crscribe_studio' => true,
                'read_crscribe_studio' => true,
                'delete_crscribe_studio' => true,
                'edit_crscribe_studios' => true,
                'edit_others_crscribe_studios' => true,
                'publish_crscribe_studios' => true,
                'read_private_crscribe_studios' => true,
                'delete_crscribe_studios' => true,
                'delete_private_crscribe_studios' => true,
                'delete_published_crscribe_studios' => true,
                'delete_others_crscribe_studios' => true,
                'edit_private_crscribe_studios' => true,
                'edit_published_crscribe_studios' => true,
                'create_crscribe_studios' => true,
                'edit_crscribe_curriculum' => true,
                'read_crscribe_curriculum' => true,
                'delete_crscribe_curriculum' => true,
                'edit_crscribe_curriculums' => true,
                'edit_others_crscribe_curriculums' => true,
                'publish_crscribe_curriculums' => true,
                'read_private_crscribe_curriculums' => true,
                'delete_crscribe_curriculums' => true,
                'delete_private_crscribe_curriculums' => true,
                'delete_published_crscribe_curriculums' => true,
                'delete_others_crscribe_curriculums' => true,
                'edit_private_crscribe_curriculums' => true,
                'edit_published_crscribe_curriculums' => true,
                'create_crscribe_curriculums' => true,
                'edit_crscribe_module' => true,
                'read_crscribe_module' => true,
                'delete_crscribe_module' => true,
                'edit_crscribe_modules' => true,
                'edit_others_crscribe_modules' => true,
                'publish_crscribe_modules' => true,
                'read_private_crscribe_modules' => true,
                'delete_crscribe_modules' => true,
                'delete_private_crscribe_modules' => true,
                'delete_published_crscribe_modules' => true,
                'delete_others_crscribe_modules' => true,
                'edit_private_crscribe_modules' => true,
                'edit_published_crscribe_modules' => true,
                'create_crscribe_modules' => true,
                'edit_crscribe_lesson' => true,
                'read_crscribe_lesson' => true,
                'delete_crscribe_lesson' => true,
                'edit_crscribe_lessons' => true,
                'edit_others_crscribe_lessons' => true,
                'publish_crscribe_lessons' => true,
                'read_private_crscribe_lessons' => true,
                'delete_crscribe_lessons' => true,
                'delete_private_crscribe_lessons' => true,
                'delete_published_crscribe_lessons' => true,
                'delete_others_crscribe_lessons' => true,
                'edit_private_crscribe_lessons' => true,
                'edit_published_crscribe_lessons' => true,
                'create_crscribe_lessons' => true,
                'edit_crscribe_course' => true,
                'read_crscribe_course' => true,
                'delete_crscribe_course' => true,
                'edit_crscribe_courses' => true,
                'edit_others_crscribe_courses' => true,
                'publish_crscribe_courses' => true,
                'read_private_crscribe_courses' => true,
                'delete_crscribe_courses' => true,
                'delete_private_crscribe_courses' => true,
                'delete_published_crscribe_courses' => true,
                'delete_others_crscribe_courses' => true,
                'edit_private_crscribe_courses' => true,
                'edit_published_crscribe_courses' => true,
                'create_crscribe_courses' => true,
                'read' => true,
            );
            foreach ( $caps as $cap => $grant ) {
                $role->add_cap( $cap, $grant );
//                error_log( 'Courscribe: Refreshed capability ' . $cap . ' for studio_admin' );
            }
//            error_log( 'Courscribe: Studio Admin capabilities refreshed for user ' . $user->ID );
        }
    }

    // Refresh Collaborator capabilities
    if ( in_array( 'collaborator', (array) $user->roles ) ) {
        $role = get_role( 'collaborator' );
        if ( $role ) {
            $caps = array(
                'access_courscribe' => true,
                'read_crscribe_studio' => true,
                'read_crscribe_curriculum' => true,
                'read_crscribe_module' => true,
                'read_crscribe_lesson' => true,
                'read' => true,
            );
            foreach ( $caps as $cap => $grant ) {
                $role->add_cap( $cap, $grant );
                //error_log( 'Courscribe: Refreshed capability ' . $cap . ' for collaborator' );
            }
//            error_log( 'Courscribe: Collaborator capabilities refreshed for user ' . $user->ID );
        }
    }
}

// Ensure capabilities are mapped to custom post types
add_action( 'init', 'courscribe_map_capabilities_to_post_types' );

function courscribe_map_capabilities_to_post_types() {
    $post_types = array(
        'crscribe_studio' => array(
            'capability_type' => 'crscribe_studio',
            'map_meta_cap' => true,
        ),
        'crscribe_curriculum' => array(
            'capability_type' => 'crscribe_curriculum',
            'map_meta_cap' => true,
        ),
        'crscribe_module' => array(
            'capability_type' => 'crscribe_module',
            'map_meta_cap' => true,
        ),
        'crscribe_lesson' => array(
            'capability_type' => 'crscribe_lesson',
            'map_meta_cap' => true,
        ),
        'crscribe_course' => array(
            'capability_type' => 'crscribe_course',
            'map_meta_cap' => true,
        ),
    );

    foreach ( $post_types as $post_type => $args ) {
        $existing = get_post_type_object( $post_type );
        if ( $existing ) {
            $existing->capability_type = $args['capability_type'];
            $existing->map_meta_cap = $args['map_meta_cap'];
//            error_log( 'Courscribe: Mapped capabilities for post type ' . $post_type );
        }
    }
}