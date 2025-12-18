<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'courscribe_curriculum_final_screen', 'courscribe_curriculum_final_screen_shortcode' );

function courscribe_curriculum_final_screen_shortcode() {
    $site_url = home_url();
    $tooltips = CourScribe_Tooltips::get_instance();
    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        error_log( 'CourScribe: User not logged in' );
        return '<p>Please log in to view this page.</p>';
    }

    // Get current user and roles
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_role_string = implode( ', ', $user_roles );

    // Get URL parameters
    $curriculum_id = isset( $_GET['curriculum_id'] ) ? intval( $_GET['curriculum_id'] ) : 0;
    $preview_type = isset( $_GET['preview_type'] ) ? sanitize_text_field( $_GET['preview_type'] ) : 'default';

    // Set heading based on preview type
    switch ( $preview_type ) {
        case 'studio-preview':
            $heading_text = 'Curriculum Map';
            break;
        case 'client-preview':
            $heading_text = 'Client Preview';
            break;
        case 'course-preview':
            $heading_text = 'Course Preview';
            break;
        default:
            $heading_text = 'Curriculum Preview';
    }

    // Permission check
    $is_collaborator = in_array( 'collaborator', $user_roles );
    $is_studio_admin = in_array( 'studio_admin', $user_roles );
    $is_client = in_array( 'client', $user_roles );
    $collaborator_permissions = $is_collaborator ? get_user_meta( $current_user->ID, '_courscribe_collaborator_permissions', true ) : [];
    $can_view = current_user_can( 'edit_crscribe_curriculums' ) ||
                ( $is_collaborator && is_array( $collaborator_permissions ) && in_array( 'edit_crscribe_curriculums', $collaborator_permissions ) ) ||
                $is_studio_admin ||
                ( $is_client && $preview_type === 'client-preview' ) ||
                ( $is_collaborator && $preview_type === 'studio-preview' );
    error_log( 'CourScribe: Can view = ' . ( $can_view ? 'true' : 'false' ) . ', Roles = ' . print_r( $user_roles, true ) . ', Collaborator permissions = ' . print_r( $collaborator_permissions, true ) );

    if ( ! $can_view ) {
        error_log( 'CourScribe: Permission check failed for user ID ' . $current_user->ID );
        return '<p>You do not have permission to view this curriculum.</p>';
    }

    // Get curriculum post
    $curriculum = get_post( $curriculum_id );
    error_log( 'CourScribe: Curriculum ID = ' . $curriculum_id . ', Post found = ' . ( $curriculum ? 'true' : 'false' ) );

    if ( ! $curriculum || $curriculum->post_type !== 'crscribe_curriculum' ) {
        error_log( 'CourScribe: Invalid or missing curriculum for ID ' . $curriculum_id );
        return '<p>Curriculum not found or you do not have access.</p>';
    }

    // Get studio ID for access check
    $studio_id = get_post_meta( $curriculum_id, '_studio_id', true );
    error_log( 'CourScribe: Curriculum studio ID = ' . $studio_id );

    // Determine user's studio ID
    $user_studio_id = 0;
    if ( $is_collaborator ) {
        $user_studio_id = get_user_meta( $current_user->ID, '_courscribe_studio_id', true );
    } elseif ( $is_studio_admin ) {
        $admin_studios = get_posts( [
            'post_type'      => 'crscribe_studio',
            'post_status'    => 'publish',
            'author'         => $current_user->ID,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ] );
        $user_studio_id = ! empty( $admin_studios ) ? $admin_studios[0] : 0;
    }
    error_log( 'CourScribe: User studio ID = ' . $user_studio_id );

    // Check studio access
    if ( $studio_id && $user_studio_id && $studio_id != $user_studio_id ) {
        error_log( 'CourScribe: Studio ID mismatch. Curriculum studio = ' . $studio_id . ', User studio = ' . $user_studio_id );
        return '<p>You do not have permission to view this curriculum.</p>';
    }

    // Get curriculum goal
    $curriculum_goal = get_post_meta( $curriculum_id, '_curriculum_goal', true ) ?: 'No goal set';

    // Query curriculums for tabs
    $query_args = [
        'post_type'      => 'crscribe_curriculum',
        'post_status'    => [ 'publish', 'draft', 'pending', 'future' ],
        'posts_per_page' => 10,
        'meta_query'     => [
            [
                'key'     => '_curriculum_status',
                'value'   => 'archived',
                'compare' => '!=',
            ],
        ],
    ];

    if ( $user_studio_id ) {
        $query_args['meta_query'][] = [
            'key'     => '_studio_id',
            'value'   => absint( $user_studio_id ),
            'compare' => '=',
        ];
    } else {
        $query_args['post__in'] = [ 0 ]; // No studio, no results
    }

    $query = new WP_Query( $query_args );
    error_log( 'CourScribe: Curriculum query results = ' . $query->post_count . ' posts' );

    // Query courses
    $courses_query = new WP_Query( [
        'post_type'      => 'crscribe_course',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_curriculum_id',
                'value'   => $curriculum_id,
                'compare' => '=',
            ],
        ],
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ] );
    error_log( 'CourScribe: Course query results = ' . $courses_query->post_count . ' courses' );

    // Get Expert Review product ID
    $expert_review_product_id = wc_get_product_id_by_sku( 'COURSCRIBE-EXPERT-REVIEW' );

    ob_start();
    ?>
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/curriculum-frontend.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/tabs.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.1.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/nucleo-icons.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/nucleo-svg.css">
    <link rel="stylesheet" href="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/css/soft-ui-dashboard.css?v=1.0.7">

    <!-- Scripts -->
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/core/popper.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/core/bootstrap.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/perfect-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/plugins/smooth-scrollbar.min.js" defer></script>
    <script src="<?php echo esc_url( $site_url ); ?>/wp-content/plugins/courscribe/assets/js/soft-ui-dashboard.min.js?v=1.0.7" defer></script>
    <script src="<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/pdfme.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="http://SortableJS.github.io/Sortable/Sortable.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-sortablejs@latest/jquery-sortable.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@nightvisi0n/pdfme-generator@1.0.14-12/dist/index.min.js"></script>

    <link href='https://cdn.jsdelivr.net/npm/froala-editor@latest/css/froala_editor.pkgd.min.css' rel='stylesheet' type='text/css' />
    <script type='text/javascript' src='https://cdn.jsdelivr.net/npm/froala-editor@latest/js/froala_editor.pkgd.min.js'></script>
    <script type="text/javascript" src='<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/richtexteditor/rte.js'></script>
    <script>RTE_DefaultConfig.url_base='<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/richtexteditor'</script>
    <script type="text/javascript" src='<?php echo $site_url; ?>/wp-content/plugins/courscribe/assets/js/richtexteditor/plugins/all_plugins.js'></script>
    <!-- Fallback inline styles for accordion -->
    <style>
        .courscribe-xy-acc-item { margin-bottom: 1rem; border: 1px solid #444; border-radius: 8px; background: #333; }
        .courscribe-xy-acc_title { display: flex; align-items: center; padding: 1rem; cursor: pointer; }
        .accordion-button { background: none; border: none; color: #FBC275; font-size: 1.2rem; padding: 0 1rem 0 0; }
        .accordion-button i { transition: transform 0.3s; }
        .accordion-button[aria-expanded="true"] i { transform: rotate(180deg); }
        .courscribe-courses-header { flex-grow: 1; }
        .course-title-span { color: #fff; font-weight: 600; }
        .courscribe-xy-acc_panel { display: none; padding: 1rem; background: #2a2a2a; color: #fff; }
        .courscribe-xy-acc_panel.show { display: block; }
    </style>

    <div class="w-100" style="min-width: 980px; margin: 0; padding: 20px; color: #ffffff; background: #2F2E30;">
        <?php if ( $query->have_posts() ) : ?>
            <h3 class="courscribe-heading"><?php echo esc_html( $heading_text ); ?>: <span><?php echo esc_html( $curriculum->post_title ); ?></span></h3>
            <div class="pcss3t pcss3t-effect-scale pcss3t-theme-1">
                <div class="scrollable-tabs">
                    <?php
                    $index = 1;
                    while ( $query->have_posts() ) : $query->the_post();
                        $post_id = get_the_ID();
                        $curriculum_slug = sanitize_title( get_the_title() );
                        $curriculum_link = home_url( '/courscribe-curriculum/' . $curriculum_slug );
                        ?>
                        <a href="<?php echo esc_url( $curriculum_link ); ?>" class="<?php echo ( $post_id == $curriculum_id ) ? 'curriculum-checked' : ''; ?>">
                            <span>Curriculum <?php echo $index; ?>: <?php the_title(); ?></span>
                        </a>
                        <?php $index++; ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>

            <div class="course-stage-wrapper mb-4">
                <div style="background: #222222; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 1rem; margin-block: 24px; overflow: hidden; border: 1px solid #222222; width: 100%; box-sizing: border-box;">
                    <img src="<?php echo home_url(); ?>/wp-content/plugins/courscribe/assets/images/Vector.png" alt="Icon" style="width: 24px; height: 24px;">
                    <span style="color: #E9B56F; font-weight: 600; white-space: nowrap;">Curriculum Goal: </span>
                    <span style="color: #E9B56F; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html( $curriculum_goal ); ?></span>
                </div>

                <div class="courscribe-xy-acc accordion" id="coursesAccordion">
                    <?php if ( $courses_query->have_posts() ) : ?>
                        <?php while ( $courses_query->have_posts() ) : $courses_query->the_post(); ?>
                            <?php
                            $course_id = get_the_ID();
                            $course = get_post( $course_id );
                            ?>
                            <div class="courscribe-xy-acc-item accordion-item as-course mb-4" data-course-id="<?php echo esc_attr( $course->ID ); ?>">
                                <div class="courscribe-xy-acc_title accordion-header" id="heading-<?php echo esc_attr( $course_id ); ?>">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo esc_attr( $course_id ); ?>" aria-expanded="true" aria-controls="collapse-<?php echo esc_attr( $course_id ); ?>">
                                        <i class="fa fa-chevron-down me-2 custom-icon"></i>
                                    </button>
                                    <div class="courscribe-courses-header" id="course-header-<?php echo esc_attr( $course->ID ); ?>">
                                        <div class="header-row-courses">
                                            <span class="course-title-span"><?php echo esc_html( $course->post_title ); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapse-<?php echo esc_attr( $course_id ); ?>" class="courscribe-xy-acc_panel accordion-collapse collapse" aria-labelledby="heading-<?php echo esc_attr( $course_id ); ?>" data-bs-parent="#coursesAccordion">
                                    <div class="accordion-body">
                                        <!-- TeachingPoints -->
                                        <?php
                                        courscribe_render_curriculum_preview([
                                            'course_id'     => $course->ID,
                                            'course_title'  => $course->post_title,
                                            'curriculum_id' => $curriculum_id,
                                            'tooltips'      => $tooltips,
                                            'site_url'      => $site_url,
                                        ]);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p>No courses added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <p>No curriculums found for your studio.</p>
        <?php endif; ?>
    </div>

    <!-- Previous and Next buttons -->
    <div class="stepper-buttons mt-3">
        <a href="<?php echo get_permalink($curriculum_id); ?>" class="btn courscribe-stepper-prevBtn"><span class="texst">Previous</span></a>
    </div>
    <div class="next-steps-container mt-3">
        <h5 class="next-steps-title">Next Steps</h5>
        <div class="next-steps-grid">
            <div class="next-step-card"
                 id="client-review-button"
                 type="button"
                 class="btn btn-primary">
                <div class="next-step-item-start">
                    <svg width="44" height="39" viewBox="0 0 44 39" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.9163 16.0837C20.3692 14.5372 18.2712 13.6684 16.0837 13.6684C13.8961 13.6684 11.7981 14.5372 10.251 16.0837L4.41637 21.9164C2.86919 23.4636 2 25.562 2 27.75C2 29.9381 2.86919 32.0365 4.41637 33.5837C5.96355 35.1309 8.06197 36.0001 10.25 36.0001C12.4381 36.0001 14.5365 35.1309 16.0837 33.5837L19 30.6673" stroke="#FBC275" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16.0836 21.9163C17.6308 23.4629 19.7288 24.3316 21.9163 24.3316C24.1039 24.3316 26.2019 23.4629 27.749 21.9163L33.5836 16.0837C35.1308 14.5365 36 12.4381 36 10.25C36 8.06197 35.1308 5.96355 33.5836 4.41637C32.0364 2.86919 29.938 2 27.75 2C25.5619 2 23.4635 2.86919 21.9163 4.41637L19 7.33272" stroke="#FBC275" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="15.9998" y="11" width="27" height="27" rx="13.5" fill="#625242"/>
                        <path d="M43.3894 35.444L38.1011 30.1557C39.1932 28.5085 39.8332 26.5367 39.8332 24.4167C39.8332 18.6728 35.1603 14 29.4165 14C23.6728 14 18.9998 18.6731 18.9998 24.4167C18.9998 30.1606 23.6726 34.8334 29.4165 34.8334C31.5363 34.8334 33.5082 34.1935 35.1555 33.1014L40.4438 38.3896C41.2566 39.2035 42.5768 39.2035 43.3897 38.3896C44.2035 37.5766 44.2035 36.257 43.3895 35.444L43.3894 35.444ZM22.1251 24.4166C22.1251 20.3957 25.396 17.1249 29.4168 17.1249C33.4375 17.1249 36.7085 20.3959 36.7085 24.4166C36.7085 28.4374 33.4375 31.7083 29.4168 31.7083C25.3957 31.7083 22.1251 28.4373 22.1251 24.4166Z" fill="#FBC275"/>
                    </svg>
                    <p>Client Preview</p>
                </div>
                <div class="next-step-item-end">
                    <svg width="119" height="119" viewBox="0 0 119 119" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g opacity="0.3" filter="url(#filter0_d_0_1)">
                            <rect width="30" height="30" rx="15" transform="matrix(0.00105114 0.999999 0.999999 -0.00105114 44 40.0315)" fill="#FBC275"/>
                        </g>
                        <path d="M58.0597 50.1196C57.789 50.1197 57.5287 50.2239 57.3205 50.4218C56.9146 50.828 56.9149 51.4944 57.3211 51.9003L60.4771 55.0644L57.3234 58.2308C56.9175 58.637 56.9178 59.3033 57.324 59.7093C57.7302 60.1152 58.3966 60.1149 58.8025 59.7087L62.7055 55.8028C62.9033 55.6049 63.0073 55.3445 63.0072 55.0634C63.0071 54.7823 62.8925 54.522 62.705 54.3243L58.799 50.4212C58.5907 50.2235 58.32 50.1195 58.0597 50.1196Z" fill="#FBC275"/>
                        <defs>
                            <filter id="filter0_d_0_1" x="0.0158691" y="0.0157204" width="118" height="118" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                <feOffset dy="4"/>
                                <feGaussianBlur stdDeviation="22"/>
                                <feComposite in2="hardAlpha" operator="out"/>
                                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.13 0"/>
                                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_0_1"/>
                                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_0_1" result="shape"/>
                            </filter>
                        </defs>
                    </svg>
                </div>
            </div>
            <div class="next-step-card"
                 id="expert-feedback-button"
                 type="button"
                 class="btn btn-primary"
                 data-product-id="<?php echo esc_attr( $expert_review_product_id ); ?>"
                 data-curriculum-id="<?php echo esc_attr( $curriculum_id ); ?>">
                <div class="next-step-item-start">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20.7789 0C20.4491 0.00139505 20.1333 0.132812 19.9 0.366066C19.6667 0.59932 19.535 0.915166 19.5339 1.24527V21.25C19.5337 21.5873 19.6698 21.9104 19.9112 22.1459C20.1528 22.3814 20.479 22.5092 20.8163 22.5005C21.1534 22.4919 21.4729 22.3474 21.7019 22.0996L25.9377 17.5051H38.745C39.0784 17.5062 39.3982 17.3745 39.6339 17.1387C39.8694 16.9032 40.0014 16.5832 40 16.2501V1.24531C39.9986 0.913566 39.8658 0.596327 39.6303 0.362824C39.3951 0.129291 39.0767 -0.00129284 38.7449 0.000107141L20.7789 0ZM22.0265 2.49997H37.5002V15.0048H25.3929C25.0436 15.0017 24.7088 15.1451 24.4699 15.4002L22.026 18.0491L22.0265 2.49997ZM29.7244 3.39367C29.3622 3.40511 29.0226 3.57363 28.7942 3.85488L27.2439 5.72513L24.9833 6.63584C24.6418 6.77618 24.3801 7.06049 24.2679 7.41233C24.1557 7.76389 24.2051 8.14724 24.4021 8.45945L25.706 10.52L25.8745 12.9368C25.9002 13.3062 26.088 13.6452 26.3879 13.8623C26.6876 14.0797 27.0682 14.1528 27.427 14.0624L29.7808 13.4569L32.1438 14.0478C32.5035 14.1366 32.8838 14.0618 33.1826 13.8431C33.4814 13.6243 33.6678 13.2845 33.6918 12.9151L33.8456 10.4835L35.1346 8.42291C35.33 8.1079 35.3754 7.72286 35.2591 7.37128C35.1425 7.01945 34.8763 6.73764 34.5317 6.60151L32.2685 5.71031L30.7107 3.84283C30.5229 3.61739 30.262 3.46532 29.9735 3.41315C29.8912 3.39809 29.8078 3.39167 29.7244 3.39362L29.7244 3.39367ZM29.7587 6.59898L30.5642 7.55597C30.6976 7.71389 30.8678 7.83638 31.0597 7.91255L32.2219 8.37403L31.5531 9.42616C31.444 9.60389 31.3809 9.80618 31.3701 10.0146L31.2894 11.2548L30.0832 10.9543C29.8801 10.9052 29.6677 10.9077 29.4657 10.9618L28.262 11.2693L28.1741 10.0243C28.1596 9.81786 28.0943 9.61809 27.9838 9.44314L27.3172 8.38845L28.4721 7.92222H28.4718C28.6646 7.84465 28.8348 7.72049 28.9676 7.56089L29.7587 6.59898ZM9.66067 11.6918C5.48063 11.6918 2.0656 15.1044 2.0656 19.2846C2.0656 21.8219 3.3279 24.0733 5.2517 25.4539C2.13703 27.0613 1.10645e-05 30.3059 1.10645e-05 34.048V38.7452C-0.00138402 39.0767 0.129198 39.3951 0.362736 39.6303C0.596274 39.8658 0.913519 39.9986 1.24503 40H18.0766C18.4081 39.9986 18.7256 39.8658 18.9591 39.6303C19.1924 39.3951 19.323 39.0767 19.3216 38.7452V34.048C19.3216 30.3054 17.186 27.0587 14.0701 25.4517C15.9931 24.0712 17.2562 21.8218 17.2562 19.2847C17.2562 15.1047 13.841 11.6919 9.66112 11.6919L9.66067 11.6918ZM9.66067 14.1918C12.4896 14.1918 14.7536 16.4557 14.7536 19.2844C14.7536 22.1133 12.4897 24.3794 9.66067 24.3794C6.83164 24.3794 4.56769 22.1133 4.56769 19.2844C4.56769 16.4555 6.83164 14.1918 9.66067 14.1918ZM9.66067 26.8795C13.6506 26.8795 16.8214 30.0574 16.8214 34.0472L16.8212 37.4992H2.49961V34.0472C2.49961 30.0574 5.67042 26.8795 9.66039 26.8795H9.66067Z" fill="#FBC275"/>
                    </svg>
                    <p>Expert Feedback</p>
                </div>
                <div class="next-step-item-end">
                    <svg width="119" height="119" viewBox="0 0 119 119" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g opacity="0.3" filter="url(#filter0_d_0_1)">
                            <rect width="30" height="30" rx="15" transform="matrix(0.00105114 0.999999 0.999999 -0.00105114 44 40.0315)" fill="#FBC275"/>
                        </g>
                        <path d="M58.0597 50.1196C57.789 50.1197 57.5287 50.2239 57.3205 50.4218C56.9146 50.828 56.9149 51.4944 57.3211 51.9003L60.4771 55.0644L57.3234 58.2308C56.9175 58.637 56.9178 59.3033 57.324 59.7093C57.7302 60.1152 58.3966 60.1149 58.8025 59.7087L62.7055 55.8028C62.9033 55.6049 63.0073 55.3445 63.0072 55.0634C63.0071 54.7823 62.8925 54.522 62.705 54.3243L58.799 50.4212C58.5907 50.2235 58.32 50.1195 58.0597 50.1196Z" fill="#FBC275"/>
                        <defs>
                            <filter id="filter0_d_0_1" x="0.0158691" y="0.0157204" width="118" height="118" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                <feOffset dy="4"/>
                                <feGaussianBlur stdDeviation="22"/>
                                <feComposite in2="hardAlpha" operator="out"/>
                                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.13 0"/>
                                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_0_1"/>
                                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_0_1" result="shape"/>
                            </filter>
                        </defs>
                    </svg>
                </div>
            </div>
            <a class="next-step-card" 
               href="<?php echo esc_url(add_query_arg(['curriculum_id' => $curriculum_id], home_url('/courscribe-curriculum-builder/'))); ?>" 
               id="create-materials-button"
               data-curriculum-id="<?php echo esc_attr($curriculum_id); ?>">
                <div class="next-step-item-start">
                    <svg width="31" height="40" viewBox="0 0 31 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M28.2275 7.80957L23.4653 2.91604C22.3045 1.74 20.7367 1.05423 19.0857 1H6.3975C4.96563 1 3.59326 1.56894 2.58078 2.58078C1.56892 3.59341 1 4.96567 1 6.3975V33.6031C1 35.0342 1.56894 36.4073 2.58078 37.4192C3.5934 38.4318 4.96567 39 6.3975 39H24.6582C26.0901 39 27.4625 38.4318 28.475 37.4192C29.4868 36.4074 30.0557 35.0343 30.0557 33.6031V12.3309C30.0725 10.6401 29.4143 9.01269 28.2275 7.8092L28.2275 7.80957ZM26.6619 9.33156C26.8468 9.5179 27.0117 9.72332 27.1545 9.94478H23.3223C22.4686 9.94478 21.6499 9.60495 21.0465 9.00166C20.4433 8.39837 20.1034 7.57972 20.1034 6.72586V3.38635C20.7854 3.59407 21.4032 3.97056 21.8996 4.48146L26.6619 9.33156ZM27.8663 33.6027C27.8663 34.4534 27.5279 35.269 26.9262 35.8709C26.3252 36.4727 25.5088 36.8102 24.658 36.8102H6.39727C5.54653 36.8102 4.73019 36.4727 4.12909 35.8709C3.52732 35.2691 3.18901 34.4535 3.18901 33.6027V6.3854C3.18901 5.53467 3.52732 4.71909 4.12909 4.11723C4.73009 3.51546 5.54646 3.17791 6.39727 3.17791H17.9141V6.72524C17.9317 8.15406 18.5067 9.5195 19.517 10.5306C20.5281 11.541 21.8935 12.116 23.3224 12.1335H27.8548V12.287L27.8663 33.6027Z" fill="#FBC275" stroke="#FBC275" stroke-width="0.5"/>
                        <path d="M19.9071 19.995H16.6233V16.7104C16.6233 16.1056 16.133 15.6161 15.5282 15.6161C14.9234 15.6161 14.4331 16.1056 14.4331 16.7104V19.995H11.1493C10.5445 19.995 10.0542 20.4853 10.0542 21.0901C10.0542 21.6942 10.5445 22.1845 11.1493 22.1845H14.4339V25.469H14.4331C14.4331 26.0739 14.9234 26.5641 15.5282 26.5641C16.1331 26.5641 16.6233 26.0739 16.6233 25.469V22.1845H19.9079H19.9071C20.512 22.1845 21.0022 21.6942 21.0022 21.0901C21.0022 20.4853 20.512 19.995 19.9071 19.995H19.9071Z" fill="#FBC275" stroke="#FBC275" stroke-width="0.5"/>
                    </svg>
                    <p>Create Materials</p>
                </div>
                <div class="next-step-item-end">
                    <svg width="119" height="119" viewBox="0 0 119 119" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g opacity="0.3" filter="url(#filter0_d_0_1)">
                            <rect width="30" height="30" rx="15" transform="matrix(0.00105114 0.999999 0.999999 -0.00105114 44 40.0315)" fill="#FBC275"/>
                        </g>
                        <path d="M58.0597 50.1196C57.789 50.1197 57.5287 50.2239 57.3205 50.4218C56.9146 50.828 56.9149 51.4944 57.3211 51.9003L60.4771 55.0644L57.3234 58.2308C56.9175 58.637 56.9178 59.3033 57.324 59.7093C57.7302 60.1152 58.3966 60.1149 58.8025 59.7087L62.7055 55.8028C62.9033 55.6049 63.0073 55.3445 63.0072 55.0634C63.0071 54.7823 62.8925 54.522 62.705 54.3243L58.799 50.4212C58.5907 50.2235 58.32 50.1195 58.0597 50.1196Z" fill="#FBC275"/>
                        <defs>
                            <filter id="filter0_d_0_1" x="0.0158691" y="0.0157204" width="118" height="118" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                <feOffset dy="4"/>
                                <feGaussianBlur stdDeviation="22"/>
                                <feComposite in2="hardAlpha" operator="out"/>
                                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.13 0"/>
                                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_0_1"/>
                                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_0_1" result="shape"/>
                            </filter>
                        </defs>
                    </svg>
                </div>
            </a>
        </div>
    </div>

    <!-- Modal for client review -->
    <div id="auth-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="auth-modal-content">
            <span id="close-modal" style="position: absolute; top: 14px; right: 20px; cursor: pointer; font-size: 40px;">×</span>
            <img src="<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/images/logo.png" alt="Logo" style="max-width: 200px; display: block; margin: 0 auto 20px;" />
            <h3 class="courscribe-heading">Client <span>Preview</span> Link</h3>
            <p class="courscribe-subheading">Send an invite to your client.</p>
            <div class="center" style="flex-direction:column">
                <form id="courscribe-invite-client-form">
                    <?php wp_nonce_field('courscribe_invite_client', 'courscribe_invite_client_nonce'); ?>
                    <input type="hidden" name="curriculum_id" id="curriculum_id" value="<?php echo $curriculum_id; ?>">
                    <div class="client-preview-form-group">
                        <label for="client_email">Client Email <span style="color: red;">*</span></label>
                        <input type="email" id="client_email" name="client_email" required>
                    </div>
                    <div class="client-preview-form-group">
                        <label for="client_name">Client Name <span style="color: red;">*</span></label>
                        <input type="text" id="client_name" name="client_name" required>
                    </div>
                </form>
                <button type="button" class="btn courscribe-stepper-nextBtn" id="send-invite-btn">Send For Review</button>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.courscribe-xy-acc').on('click', '.accordion-button', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $titleWrapper = $button.closest('.courscribe-xy-acc_title');
                var $item = $titleWrapper.closest('.courscribe-xy-acc-item');
                var $content = $item.find('.courscribe-xy-acc_panel');

                $titleWrapper.toggleClass('courscribe-xy-acc_title_active');
                if ($content.hasClass('courscribe-xy-acc_panel_col')) {
                    $content.removeClass('anim_out').addClass('anim_in');
                } else {
                    $content.removeClass('anim_in').addClass('anim_out');
                }
                $content.toggleClass('courscribe-xy-acc_panel_col');
            });

            $('.lesson-xy-acc').on('click', '.accordion-button', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $titleWrapper = $button.closest('.lesson-xy-acc_title');
                var $item = $titleWrapper.closest('.lesson-xy-acc-item');
                var $content = $item.find('.lesson-xy-acc_panel');

                $titleWrapper.toggleClass('lesson-xy-acc_title_active');
                if ($content.hasClass('lesson-xy-acc_panel_col')) {
                    $content.removeClass('anim_out').addClass('anim_in');
                } else {
                    $content.removeClass('anim_in').addClass('anim_out');
                }
                $content.toggleClass('lesson-xy-acc_panel_col');
            });

            // Expert Feedback button handler
            $('#expert-feedback-button').on('click', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var curriculumId = $(this).data('curriculum-id');
                var isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
                var isStudioAdmin = <?php echo $is_studio_admin ? 'true' : 'false'; ?>;

                if (!isLoggedIn) {
                    alert('Please log in to request Expert Feedback.');
                    window.location.href = '<?php echo wp_login_url( get_permalink() ); ?>';
                    return;
                }

                if (!isStudioAdmin) {
                    alert('Only Studio Admins can request Expert Feedback.');
                    return;
                }

                if (!productId) {
                    alert('Expert Review product is not available.');
                    return;
                }

                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: {
                        action: 'courscribe_add_expert_review_to_cart',
                        product_id: productId,
                        curriculum_id: curriculumId,
                        nonce: '<?php echo wp_create_nonce( 'courscribe_add_to_cart' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo wc_get_checkout_url(); ?>';
                        } else {
                            alert('Error adding Expert Review to cart: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('An error occurred while adding to cart. Please try again.');
                    }
                });
            });

            // Client Preview modal
            const modal = document.getElementById('auth-modal');
            const closeModal = document.getElementById('close-modal');
            const clientPreviewButton = document.getElementById('client-review-button');

            clientPreviewButton.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'block';
            });

            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        });
    </script>

    <?php courscribe_invite_client_modal(); ?>
    <?php include plugin_dir_path(__FILE__) . '../../template-parts/courscribe-loader.php'; ?>

    <script src="https://cdn.jsdelivr.net/gh/noumanqamar450/alertbox@main/version/1.0.2/alertbox.min.js"></script>
    <?php
    return ob_get_clean();
}

// AJAX handler for adding Expert Review to cart
add_action( 'wp_ajax_courscribe_add_expert_review_to_cart', 'courscribe_add_expert_review_to_cart' );
function courscribe_add_expert_review_to_cart() {
    check_ajax_referer( 'courscribe_add_to_cart', 'nonce' );

    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    $curriculum_id = isset( $_POST['curriculum_id'] ) ? intval( $_POST['curriculum_id'] ) : 0;

    if ( ! $product_id || ! $curriculum_id ) {
        wp_send_json_error( [ 'message' => 'Invalid product or curriculum ID.' ] );
    }

    // Clear cart to ensure only Expert Review is purchased
    WC()->cart->empty_cart();

    // Add product to cart with curriculum ID as custom data
    $cart_item_data = [ 'curriculum_id' => $curriculum_id ];
    $added = WC()->cart->add_to_cart( $product_id, 1, 0, [], $cart_item_data );

    if ( $added ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( [ 'message' => 'Failed to add product to cart.' ] );
    }
}

// Add curriculum ID to order meta after purchase
add_action( 'woocommerce_checkout_create_order_line_item', 'courscribe_add_curriculum_id_to_order_item', 10, 4 );
function courscribe_add_curriculum_id_to_order_item( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['curriculum_id'] ) ) {
        $item->add_meta_data( '_curriculum_id', $values['curriculum_id'], true );
    }
}
?>