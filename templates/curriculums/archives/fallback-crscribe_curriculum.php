<?php
get_header();
?>
<div class="courscribe-curriculum-single">
    <h1><?php the_title(); ?></h1>
    <div class="content"><?php the_content(); ?></div>
    <p>Curriculum ID: <?php echo get_the_ID(); ?></p>
    <p><strong>Warning:</strong> Fallback template loaded because single-crscribe_curriculum.php is missing.</p>
</div>
<?php
get_footer();
