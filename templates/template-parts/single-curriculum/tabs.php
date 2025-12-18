<?php
// Path: templates/template-parts/single-curriculum/tabs.php
if (!defined('ABSPATH')) {
    exit;
}

$all_curriculums_args = [
    'post_type' => 'crscribe_curriculum',
    'post_status' => ['publish', 'draft', 'pending', 'future'],
    'posts_per_page' => 10,
    'meta_query' => [
        [
            'key' => '_curriculum_status',
            'value' => 'archived',
            'compare' => '!=',
        ],
    ],
];

if ($permissions->is_client()) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_client_invites';
    $invited_curriculums = $wpdb->get_col($wpdb->prepare(
        "SELECT curriculum_id FROM $table_name WHERE email = %s AND status = 'Accepted'",
        $current_user->user_email
    ));
    $all_curriculums_args['post__in'] = !empty($invited_curriculums) ? $invited_curriculums : [0];
} elseif ($curriculum_data['studio_id']) {
    $all_curriculums_args['meta_query'][] = [
        'key' => '_studio_id',
        'value' => absint($curriculum_data['studio_id']),
        'compare' => '=',
    ];
} else {
    $all_curriculums_args['post__in'] = [0];
}

$tabs_query = new WP_Query($all_curriculums_args);
if ($tabs_query->have_posts()): ?>
    <div class="pcss3t pcss3t-effect-scale pcss3t-theme-1">
        <div class="scrollable-tabs">
            <?php
            $index = 1;
            $total_posts = $tabs_query->post_count;
            while ($tabs_query->have_posts()): $tabs_query->the_post();
                $tab_post_id = get_the_ID();
                $curriculum_slug = sanitize_title(get_the_title());
                $curriculum_page = get_page_by_path('courscribe-curriculum');
                $curriculum_link = $curriculum_page ? get_permalink($curriculum_page->ID) . $tab_post_id . '/' : home_url('/courscribe-curriculum/' . $curriculum_slug);
                ?>
                <a href="<?php echo esc_url($curriculum_link); ?>"
                   class="<?php echo ($index === 1) ? 'tab-content-first' : (($index === $total_posts) ? 'tab-content-last' : 'tab-content-' . $index); ?> <?php echo ($tab_post_id == $curriculum->ID) ? 'curriculum-checked' : ''; ?>">
                    <label for="tab<?php echo $index; ?>"><span>Curriculum <?php echo $index; ?>:<span><?php the_title(); ?></span></span></label>
                </a>
                <?php $index++; ?>
            <?php endwhile; ?>
        </div>
    </div>
    <?php wp_reset_postdata(); ?>
<?php else: ?>
    <p>No curriculums found for your studios.</p>
<?php endif; ?>