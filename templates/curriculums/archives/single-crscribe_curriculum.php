<?php
/**
 * Template Name: Single Curriculum
 * Template for displaying single crscribe_curriculum posts.
 *
 * @package CourScribe
 */

// Add debugging
error_log('CourScribe: Single curriculum template loaded for post ID: ' . get_the_ID());

get_header();

?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php
            while ( have_posts() ) : the_post();
                // Check if post exists and is correct type
                $current_post = get_post();
                error_log('CourScribe: Processing post - ID: ' . $current_post->ID . ', Type: ' . $current_post->post_type . ', Status: ' . $current_post->post_status);
                
                // Check if shortcode function exists
                if (shortcode_exists('courscribe_single_curriculum')) {
                    error_log('CourScribe: courscribe_single_curriculum shortcode exists, executing...');
                    echo do_shortcode( '[courscribe_single_curriculum]' );
                } else {
                    error_log('CourScribe: courscribe_single_curriculum shortcode does not exist!');
                    echo '<div class="courscribe-error">';
                    echo '<h1>' . get_the_title() . '</h1>';
                    echo '<p><strong>Error:</strong> courscribe_single_curriculum shortcode not found.</p>';
                    echo '<p>Post ID: ' . get_the_ID() . '</p>';
                    echo '<p>Post Type: ' . get_post_type() . '</p>';
                    echo '<p>Post Status: ' . get_post_status() . '</p>';
                    echo '<div class="content">' . apply_filters('the_content', get_the_content()) . '</div>';
                    echo '</div>';
                }
            endwhile;
            ?>
        </main><!-- #main -->
    </div><!-- #primary -->
<?php

get_footer();