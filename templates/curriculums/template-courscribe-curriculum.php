<?php
/**
 * Template Name: CourScribe Curriculum Page
 * Template for the main curriculum page/archive
 *
 * @package CourScribe
 */

error_log('CourScribe: template-courscribe-curriculum.php loaded');

get_header();

?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php
            // This template is for the curriculum archive/main page
            // Individual curriculum posts should use single-crscribe_curriculum.php
            
            echo '<h1>CourScribe Curriculums</h1>';
            echo '<p>This is the curriculum archive page.</p>';
            
            // You can add curriculum listing logic here if needed
            // Or redirect to the curriculum manager
            ?>
        </main><!-- #main -->
    </div><!-- #primary -->
<?php

get_footer();