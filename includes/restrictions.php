<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Restrict studio creation to one
add_filter( 'wp_insert_post_data', 'courscribe_restrict_studio_creation', 10, 2 );
function courscribe_restrict_studio_creation( $data, $postarr ) {
    if ( $data['post_type'] === 'crscribe_studio' && $data['post_status'] === 'publish' ) {
        if ( ! current_user_can( 'publish_crscribe_studios' ) ) {
            wp_die( 'You do not have permission to create studios.' );
        }
        $current_user_id = get_current_user_id();
        $studio_query = new WP_Query( [
            'post_type' => 'crscribe_studio',
            'post_status' => 'publish',
            'author' => $current_user_id,
            'posts_per_page' => -1,
            'no_found_rows' => true,
        ] );
        $studio_count = $studio_query->found_posts;
        wp_reset_postdata();
        error_log( 'Courscribe: Restrict Studio Creation - User ' . $current_user_id . ', Studio count: ' . $studio_count );
        if ( $studio_count >= 1 ) {
            wp_die( 'Only one studio is allowed per user.' );
        }
    }
    return $data;
}

// Enforce tier limits
add_filter( 'wp_insert_post_data', 'courscribe_enforce_tier_limits', 10, 2 );
function courscribe_enforce_tier_limits( $data, $postarr ) {
    $user_id = get_current_user_id();
    $tier = get_user_meta( $user_id, '_courscribe_user_tier', true ) ?: 'basics';
    $post_type = $data['post_type'];

    if ( $data['post_status'] !== 'publish' ) {
        return $data;
    }

    $counts = [
        'crscribe_curriculum' => wp_count_posts( 'crscribe_curriculum' )->publish,
        'crscribe_course' => wp_count_posts( 'crscribe_course' )->publish,
        'dtlms_module' => wp_count_posts( 'dtlms_module' )->publish,
        'dtlms_lesson' => wp_count_posts( 'dtlms_lesson' )->publish,
//        'dtlms_teaching_point' => wp_count_posts( 'dtlms_teaching_point' )->publish,
    ];

//    if ( $tier === 'basics' ) {
//        if ( $post_type === 'crscribe_curriculum' && $counts['crscribe_curriculum'] >= 10 ) {
//            wp_die( 'Basics tier allows only 1 curriculum. Upgrade to Pro for unlimited curriculums.' );
//        }
//        if ( $post_type === 'crscribe_course' && $counts['crscribe_course'] >= 10 ) {
//            wp_die( 'Basics tier allows only 1 course. Upgrade to Plus for unlimited courses.' );
//        }
//        if ( $post_type === 'dtlms_module' && $counts['dtlms_module'] >= 30 ) {
//            wp_die( 'Basics tier allows only 3 modules. Upgrade to Plus for unlimited modules.' );
//        }
//        if ( $post_type === 'dtlms_lesson' && $counts['dtlms_lesson'] >= 30 ) {
//            wp_die( 'Basics tier allows only 3 lessons. Upgrade to Plus for unlimited lessons.' );
//        }
////        if ( $post_type === 'dtlms_teaching_point' && $counts['dtlms_teaching_point'] >= 3 ) {
////            wp_die( 'Basics tier allows only 3 teaching points. Upgrade to Plus for unlimited teaching points.' );
////        }
//    } elseif ( $tier === 'plus' ) {
//        if ( $post_type === 'crscribe_curriculum' && $counts['crscribe_curriculum'] >= 10 ) {
//            wp_die( 'Plus tier allows only 1 curriculum. Upgrade to Pro for unlimited curriculums.' );
//        }
//    }

    return $data;
}

// Disable "Add New" for studios in admin if one exists
add_action( 'admin_menu', 'courscribe_disable_studio_add_new' );
function courscribe_disable_studio_add_new() {
    $user_id = get_current_user_id();
    $studio_count = wp_count_posts( 'crscribe_studio' )->publish;
    if ( $studio_count >= 1 ) {
        remove_submenu_page( 'edit.php?post_type=crscribe_studio', 'post-new.php?post_type=crscribe_studio' );
    }
}
?>