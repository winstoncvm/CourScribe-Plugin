<?php

function add_course_to_curriculum() {
    check_ajax_referer('custom_nonce', 'security');

    $curriculum_id = intval($_POST['curriculum_id']);
    $course_title = sanitize_text_field($_POST['course_title']);
    $course_content = wp_kses_post($_POST['course_content']);

    $new_course = array(
        'post_title'   => $course_title,
        'post_content' => $course_content,
        'post_status'  => 'publish',
        'post_type'    => 'course',
        'meta_input'   => array(
            'curriculum_id' => $curriculum_id
        ),
    );

    $course_id = wp_insert_post($new_course);

    if ($course_id) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_add_course_to_curriculum', 'add_course_to_curriculum');



add_action('acf/save_post', 'update_objective_title', 20);
function update_objective_title($post_id) {
    // Check if it's an 'objective' post type
    if (get_post_type($post_id) !== 'objective') {
        return;
    }

    // Get the field values
    $thinking_skill = get_field('thinking_skill', $post_id);
    $action_verb = get_field('action_verb', $post_id);
    $description = get_field('description', $post_id);

    // Generate the title
    $new_title = $thinking_skill . ' ' . $action_verb . ': ' . $description;

    // Trim the title if it exceeds 255 characters (max title length in WP)
    if (strlen($new_title) > 255) {
        $new_title = substr($new_title, 0, 252) . '...';
    }

    // Update the post title
    $post_data = array(
        'ID'         => $post_id,
        'post_title' => wp_strip_all_tags($new_title),
    );

    // Remove the action to avoid infinite loop
    remove_action('acf/save_post', 'update_objective_title', 20);
    wp_update_post($post_data);
    add_action('acf/save_post', 'update_objective_title', 20);
}




