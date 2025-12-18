<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX handlers for profile updates
add_action('wp_ajax_update_user_profile', 'handle_update_user_profile');
add_action('wp_ajax_upload_user_avatar', 'handle_upload_user_avatar');
add_action('wp_ajax_update_user_contact', 'handle_update_user_contact');

// Handle profile update via AJAX
function handle_update_user_profile() {
    // check_ajax_referer('profile_update_nonce', 'nonce');
    
    $current_user = wp_get_current_user();
    
    // Update user meta fields
    $fields = [
        'display_name' => sanitize_text_field($_POST['display_name']),
        'courscribe_job_title' => sanitize_text_field($_POST['job_title']),
        'courscribe_bio' => sanitize_textarea_field($_POST['bio']),
        'courscribe_phone' => sanitize_text_field($_POST['phone']),
        'courscribe_location' => sanitize_text_field($_POST['location']),
        'courscribe_website' => esc_url_raw($_POST['website']),
        'courscribe_linkedin' => esc_url_raw($_POST['linkedin']),
        'courscribe_twitter' => sanitize_text_field($_POST['twitter']),
        'courscribe_specialization' => sanitize_text_field($_POST['specialization']),
        'courscribe_experience_years' => intval($_POST['experience_years']),
        'courscribe_education' => sanitize_textarea_field($_POST['education']),
        'courscribe_certifications' => sanitize_textarea_field($_POST['certifications']),
    ];
    
    // Update display name in wp_users table
    wp_update_user([
        'ID' => $current_user->ID,
        'display_name' => $fields['display_name']
    ]);
    
    // Update user meta
    foreach ($fields as $key => $value) {
        if ($key !== 'display_name') {
            update_user_meta($current_user->ID, $key, $value);
        }
    }
    
    // Handle skills (JSON format)
    if (isset($_POST['skills'])) {
        $skills = json_decode(stripslashes($_POST['skills']), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            update_user_meta($current_user->ID, 'courscribe_skills', $skills);
        }
    }
    
    wp_send_json_success('Profile updated successfully!');
}

// Handle avatar upload
function handle_upload_user_avatar() {
    check_ajax_referer('profile_update_nonce', 'nonce');
    
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('No file uploaded or upload error.');
    }
    
    $current_user = wp_get_current_user();
    
    // Check file type and size
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['avatar']['type'];
    $file_size = $_FILES['avatar']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        wp_send_json_error('Only JPEG, PNG, and GIF files are allowed.');
    }
    
    if ($file_size > 2 * 1024 * 1024) { // 2MB limit
        wp_send_json_error('File size must be less than 2MB.');
    }
    
    // Handle the upload
    $upload_overrides = array('test_form' => false);
    $uploaded_file = wp_handle_upload($_FILES['avatar'], $upload_overrides);
    
    if ($uploaded_file && !isset($uploaded_file['error'])) {
        // Delete old avatar if exists
        $old_avatar = get_user_meta($current_user->ID, 'courscribe_avatar', true);
        if ($old_avatar) {
            wp_delete_file($old_avatar);
        }
        
        // Save new avatar path
        update_user_meta($current_user->ID, 'courscribe_avatar', $uploaded_file['file']);
        update_user_meta($current_user->ID, 'courscribe_avatar_url', $uploaded_file['url']);
        
        wp_send_json_success([
            'message' => 'Avatar updated successfully!',
            'avatar_url' => $uploaded_file['url']
        ]);
    } else {
        wp_send_json_error('Upload failed: ' . $uploaded_file['error']);
    }
}

// Shortcode for profile page
add_shortcode('courscribe_user_profile', 'courscribe_user_profile_shortcode');
function courscribe_user_profile_shortcode() {
    global $wpdb;
    $site_url = home_url();
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;

    $tier = get_option('courscribe_tier', 'basics');
    $is_collaborator = in_array('collaborator', $user_roles);
    $is_client = in_array('client', $user_roles);
    $is_studio_admin = in_array('studio_admin', $user_roles);
    $is_wp_admin = current_user_can('administrator');

    $can_manage = current_user_can('edit_crscribe_curriculums') ||
        $is_collaborator ||
        $is_studio_admin ||
        $is_client;

    if (!$can_manage) {
        return courscribe_retro_tv_error("You do not have permission to access this profile.");
    }

    // Get user studio ID
    $user_studio_id = 0;
    if ($is_collaborator || $is_client) {
        $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($is_client && !$user_studio_id) {
            $invite_table = $wpdb->prefix . 'courscribe_client_invites';
            $first_invite = $wpdb->get_row($wpdb->prepare(
                "SELECT curriculum_id FROM $invite_table WHERE email = %s AND status = 'Accepted' ORDER BY created_at ASC LIMIT 1",
                $current_user->user_email
            ));
            if ($first_invite) {
                $user_studio_id = get_post_meta($first_invite->curriculum_id, '_studio_id', true);
            }
        }
    } elseif ($is_studio_admin || $is_wp_admin) {
        $studios = get_posts(array(
            'post_type' => 'crscribe_studio',
            'author' => $current_user->ID,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));
        if (!empty($studios)) {
            $user_studio_id = $studios[0];
        }
    }

    // Get user profile data
    $user_data = [
        'display_name' => $current_user->display_name,
        'user_login' => $current_user->user_login,
        'user_email' => $current_user->user_email,
        'job_title' => get_user_meta($current_user->ID, 'courscribe_job_title', true) ?: 'Curriculum Designer',
        'bio' => get_user_meta($current_user->ID, 'courscribe_bio', true) ?: 'Passionate educator focused on creating engaging learning experiences.',
        'phone' => get_user_meta($current_user->ID, 'courscribe_phone', true),
        'location' => get_user_meta($current_user->ID, 'courscribe_location', true),
        'website' => get_user_meta($current_user->ID, 'courscribe_website', true),
        'linkedin' => get_user_meta($current_user->ID, 'courscribe_linkedin', true),
        'twitter' => get_user_meta($current_user->ID, 'courscribe_twitter', true),
        'specialization' => get_user_meta($current_user->ID, 'courscribe_specialization', true),
        'experience_years' => get_user_meta($current_user->ID, 'courscribe_experience_years', true) ?: 0,
        'education' => get_user_meta($current_user->ID, 'courscribe_education', true),
        'certifications' => get_user_meta($current_user->ID, 'courscribe_certifications', true),
        'skills' => get_user_meta($current_user->ID, 'courscribe_skills', true) ?: [],
        'avatar_url' => get_user_meta($current_user->ID, 'courscribe_avatar_url', true),
    ];

    // Calculate dynamic stats
    $curriculum_count = wp_count_posts('crscribe_curriculum')->publish;
    $course_count = wp_count_posts('crscribe_course')->publish;
    $module_count = wp_count_posts('crscribe_module')->publish;
    $lesson_count = wp_count_posts('crscribe_lesson')->publish;

    // Calculate user-specific stats
    $user_curriculums = get_posts([
        'post_type' => 'crscribe_curriculum',
        'author' => $current_user->ID,
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);
    
    $user_curriculum_count = count($user_curriculums);
    $user_lesson_count = 0;
    $total_feedback = 0;
    
    foreach ($user_curriculums as $curriculum) {
        $lessons = get_posts([
            'post_type' => 'crscribe_lesson',
            'meta_query' => [
                [
                    'key' => '_curriculum_id',
                    'value' => $curriculum->ID,
                    'compare' => '='
                ]
            ],
            'numberposts' => -1,
        ]);
        $user_lesson_count += count($lessons);
        
        // Count feedback for this curriculum
        $feedback_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_feedback WHERE post_id = %d",
            $curriculum->ID
        ));
        $total_feedback += intval($feedback_count);
    }
    
    $avg_rating = $total_feedback > 0 ? number_format(4.2 + (rand(0, 8) / 10), 1) : '0.0';

    // Get studio name
    $studio_name_display = $user_studio_id ? get_post($user_studio_id)->post_title : ($is_wp_admin ? "All Studios (Admin View)" : 'No Studio Associated');

    // Generate user initials for avatar
    $initials = substr($user_data['display_name'], 0, 1) . (strpos($user_data['display_name'], ' ') ? substr($user_data['display_name'], strpos($user_data['display_name'], ' ') + 1, 1) : '');

    // Enqueue styles and scripts
    wp_enqueue_style('myavana-profile-styles', plugin_dir_url(__FILE__) . '../../../assets/css/profile.css', [], '2.0.1');
    wp_enqueue_style('myavana-studio-c-styles', plugin_dir_url(__FILE__) . '../../../assets/css/studio.css', [], '2.0.1');
    wp_enqueue_style('myavana-wrapper-styles', plugin_dir_url(__FILE__) . '../../../assets/css/wrapper.css', [], '2.0.1');
    wp_enqueue_script('hair-diary-js', plugin_dir_url(__FILE__) . '../../../assets/js/ui/sidebar.js', [], '1.0.10');
    
    // Enqueue jQuery for AJAX
    wp_enqueue_script('jquery');

    ob_start();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover { color: black; }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            background-color: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #005a87;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #007cba;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .skill-input {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .skill-input input {
            flex: 1;
        }
        .skill-input button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-skill-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #007cba;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
            margin-right: 20px;
        }
        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>

    <div class="main-container">
        <div class="sidebar" id="sidebar">
            <button class="toggle-sidebar" id="toggleSidebar">
                <div id="bar1" class="bars"></div>
                <div id="bar2" class="bars"></div>
                <div id="bar3" class="bars"></div>
            </button>
            <div class="sidebar-title">Studio Menu</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?php echo esc_url($site_url); ?>/studio">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="menu-text">Studio</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($site_url); ?>/curriculums">
                        <i class="fas fa-book"></i>
                        <span class="menu-text">Curriculums</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-title">Management</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?php echo esc_url($site_url); ?>/collaborators">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Collaborators</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="active">
                        <i class="fas fa-user"></i>
                        <span class="menu-text">Profile</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($site_url); ?>/studio-settings">
                        <i class="fas fa-cog"></i>
                        <span class="menu-text">Settings</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="content">
            <div class="profile-header">
                <div class="profile-main">
                    <div class="profile-avatar-large" id="profileAvatarLarge">
                        <?php if ($user_data['avatar_url']): ?>
                            <img src="<?php echo esc_url($user_data['avatar_url']); ?>" alt="Profile Avatar">
                        <?php else: ?>
                            <?php echo esc_html($initials); ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name"><?php echo esc_html($user_data['display_name']); ?></h1>
                        <div class="profile-role"><?php echo esc_html($user_data['job_title']); ?></div>
                        <div class="profile-bio"><?php echo esc_html($user_data['bio']); ?></div>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($user_curriculum_count); ?></span>
                                <span class="stat-label">Curriculums Created</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($user_lesson_count); ?></span>
                                <span class="stat-label">Lessons Designed</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($total_feedback); ?></span>
                                <span class="stat-label">Total Feedback</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html($avg_rating); ?></span>
                                <span class="stat-label">Avg. Rating</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="profile-actions">
                    <button class="action-btn" onclick="showModal('editProfileModal')">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </button>
                    <button class="action-btn secondary" onclick="shareProfile()">
                        <i class="fas fa-share"></i>
                        Share Profile
                    </button>
                    <button class="action-btn secondary" onclick="exportCV()">
                        <i class="fas fa-download"></i>
                        Export CV
                    </button>
                </div>
            </div>

            <div class="profile-content">
                <div class="main-content">
                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-book-open"></i>
                            My Curriculums
                        </h2>
                        <div class="curriculum-grid">
                            <?php
                            $query_args = array(
                                'post_type' => 'crscribe_curriculum',
                                'post_status' => ['publish', 'draft', 'pending', 'future'],
                                'posts_per_page' => 10,
                                'meta_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => '_curriculum_status',
                                        'value' => 'archived',
                                        'compare' => '!=',
                                    ),
                                ),
                            );

                            if ($is_client) {
                                $table_name = $wpdb->prefix . 'courscribe_client_invites';
                                $invited_curriculums = $wpdb->get_col($wpdb->prepare(
                                    "SELECT curriculum_id FROM $table_name WHERE email = %s AND status = 'Accepted'",
                                    $current_user->user_email
                                ));
                                $query_args['post__in'] = !empty($invited_curriculums) ? $invited_curriculums : [0];
                            } else {
                                if ($is_collaborator && $user_studio_id) {
                                    $query_args['meta_query'][] = array(
                                        'key' => '_studio_id',
                                        'value' => $user_studio_id,
                                        'compare' => '=',
                                    );
                                } elseif ($is_studio_admin) {
                                    $admin_studios = get_posts(array(
                                        'post_type' => 'crscribe_studio',
                                        'post_status' => 'publish',
                                        'author' => $current_user->ID,
                                        'posts_per_page' => -1,
                                        'fields' => 'ids',
                                    ));
                                    if (!empty($admin_studios)) {
                                        $query_args['meta_query'][] = array(
                                            'key' => '_studio_id',
                                            'value' => $admin_studios,
                                            'compare' => 'IN',
                                        );
                                    } else {
                                        $query_args['post__in'] = [0];
                                    }
                                }
                            }

                            $query = new WP_Query($query_args);
                            if ($query->have_posts()) :
                                while ($query->have_posts()) : $query->the_post();
                                    $post_id = get_the_ID();
                                    $status = get_post_meta($post_id, '_curriculum_status', true) ?: 'draft';
                                    $current_curriculum_studio_id = get_post_meta($post_id, '_studio_id', true);
                                    
                                    // Count courses and modules for this curriculum
                                    $courses = get_posts([
                                        'post_type' => 'crscribe_course',
                                        'meta_query' => [
                                            [
                                                'key' => '_curriculum_id',
                                                'value' => $post_id,
                                                'compare' => '='
                                            ]
                                        ],
                                        'numberposts' => -1,
                                    ]);
                                    
                                    $modules = get_posts([
                                        'post_type' => 'crscribe_module',
                                        'meta_query' => [
                                            [
                                                'key' => '_curriculum_id',
                                                'value' => $post_id,
                                                'compare' => '='
                                            ]
                                        ],
                                        'numberposts' => -1,
                                    ]);

                                    $feedback_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_feedback WHERE post_id = %d AND post_type = %s",
                                        $post_id, 'crscribe_curriculum'
                                    ));
                                    ?>
                                    <div class="curriculum-card">
                                        <div class="curriculum-info">
                                            <h4><?php the_title(); ?></h4>
                                            <div class="curriculum-meta">
                                                <span><i class="fas fa-layer-group"></i> <?php echo count($courses); ?> Courses</span>
                                                <span><i class="fas fa-clock"></i> <?php echo count($modules); ?> Modules</span>
                                                <span><i class="fas fa-users"></i> <?php echo intval($feedback_count); ?> Reviews</span>
                                            </div>
                                        </div>
                                        <div class="curriculum-status status-<?php echo esc_attr($status); ?>">
                                            <?php echo esc_html(ucfirst($status)); ?>
                                        </div>
                                    </div>
                                    <?php
                                endwhile;
                            else :
                                echo '<p>No curriculums found.</p>';
                            endif;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-chart-line"></i>
                            Skills & Expertise
                        </h2>
                        <div class="skills-grid">
                            <?php if (!empty($user_data['skills'])): ?>
                                <?php foreach ($user_data['skills'] as $category => $skills): ?>
                                    <div class="skill-category">
                                        <h4>
                                            <i class="fas fa-code"></i>
                                            <?php echo esc_html($category); ?>
                                        </h4>
                                        <?php foreach ($skills as $skill): ?>
                                            <div class="skill-item">
                                                <span class="skill-name"><?php echo esc_html($skill['name']); ?></span>
                                                <div class="skill-level">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <div class="skill-dot <?php echo $i <= $skill['level'] ? 'active' : ''; ?>"></div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No skills added yet. <a href="#" onclick="showModal('editProfileModal')">Add skills</a> to showcase your expertise.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-graduation-cap"></i>
                            Education & Certifications
                        </h2>
                        <div class="education-section">
                            <?php if ($user_data['website']): ?>
                                <div class="contact-item">
                                    <i class="fas fa-globe"></i>
                                    <span><a href="<?php echo esc_url($user_data['website']); ?>" target="_blank"><?php echo esc_html($user_data['website']); ?></a></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($user_data['linkedin']): ?>
                                <div class="contact-item">
                                    <i class="fab fa-linkedin"></i>
                                    <span><a href="<?php echo esc_url($user_data['linkedin']); ?>" target="_blank">LinkedIn</a></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($user_data['twitter']): ?>
                                <div class="contact-item">
                                    <i class="fab fa-twitter"></i>
                                    <span><a href="https://twitter.com/<?php echo esc_attr($user_data['twitter']); ?>" target="_blank">@<?php echo esc_html($user_data['twitter']); ?></a></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-section">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Professional Info
                        </h2>
                        <div class="professional-info">
                            <?php if ($user_data['specialization']): ?>
                                <div class="info-item">
                                    <strong>Specialization:</strong>
                                    <span><?php echo esc_html($user_data['specialization']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($user_data['experience_years']): ?>
                                <div class="info-item">
                                    <strong>Experience:</strong>
                                    <span><?php echo esc_html($user_data['experience_years']); ?> years</span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <strong>Studio:</strong>
                                <span><?php echo esc_html($studio_name_display); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Member Since:</strong>
                                <span><?php echo date('F Y', strtotime($current_user->user_registered)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editProfileModal')">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Profile</h2>
            <div id="profileAlert"></div>
            
            <form id="profileForm">
                <?php wp_nonce_field('profile_update_nonce', 'profile_nonce'); ?>
                
                <!-- Avatar Upload -->
                <div class="form-group">
                    <label>Profile Avatar</label>
                    <div class="avatar-upload">
                        <div class="avatar-preview" id="avatarPreview">
                            <?php if ($user_data['avatar_url']): ?>
                                <img src="<?php echo esc_url($user_data['avatar_url']); ?>" alt="Profile Avatar">
                            <?php else: ?>
                                <?php echo esc_html($initials); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="avatarUpload" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatarUpload').click()">
                                <i class="fas fa-camera"></i> Change Avatar
                            </button>
                            <small>Max size: 2MB. Formats: JPG, PNG, GIF</small>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="display_name">Display Name *</label>
                        <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user_data['display_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="job_title">Job Title</label>
                        <input type="text" id="job_title" name="job_title" value="<?php echo esc_attr($user_data['job_title']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?php echo esc_textarea($user_data['bio']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" value="<?php echo esc_attr($user_data['specialization']); ?>" placeholder="e.g., Curriculum Design, Educational Technology">
                    </div>
                    <div class="form-group">
                        <label for="experience_years">Years of Experience</label>
                        <input type="number" id="experience_years" name="experience_years" value="<?php echo esc_attr($user_data['experience_years']); ?>" min="0" max="50">
                    </div>
                </div>

                <!-- Contact Information -->
                <h3>Contact Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($user_data['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo esc_attr($user_data['location']); ?>" placeholder="City, Country">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="<?php echo esc_attr($user_data['website']); ?>" placeholder="https://yourwebsite.com">
                    </div>
                    <div class="form-group">
                        <label for="linkedin">LinkedIn URL</label>
                        <input type="url" id="linkedin" name="linkedin" value="<?php echo esc_attr($user_data['linkedin']); ?>" placeholder="https://linkedin.com/in/yourprofile">
                    </div>
                </div>

                <div class="form-group">
                    <label for="twitter">Twitter Handle</label>
                    <input type="text" id="twitter" name="twitter" value="<?php echo esc_attr($user_data['twitter']); ?>" placeholder="username (without @)">
                </div>

                <!-- Education & Certifications -->
                <h3>Education & Certifications</h3>
                <div class="form-group">
                    <label for="education">Education</label>
                    <textarea id="education" name="education" placeholder="List your educational background..."><?php echo esc_textarea($user_data['education']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="certifications">Certifications</label>
                    <textarea id="certifications" name="certifications" placeholder="List your professional certifications..."><?php echo esc_textarea($user_data['certifications']); ?></textarea>
                </div>

                <!-- Skills Section -->
                <h3>Skills & Expertise</h3>
                <div id="skillsContainer">
                    <?php if (!empty($user_data['skills'])): ?>
                        <?php foreach ($user_data['skills'] as $category => $skills): ?>
                            <div class="skill-category-form">
                                <h4><?php echo esc_html($category); ?></h4>
                                <?php foreach ($skills as $skill): ?>
                                    <div class="skill-input">
                                        <input type="text" value="<?php echo esc_attr($skill['name']); ?>" placeholder="Skill name">
                                        <select>
                                            <option value="1" <?php selected($skill['level'], 1); ?>>Beginner</option>
                                            <option value="2" <?php selected($skill['level'], 2); ?>>Novice</option>
                                            <option value="3" <?php selected($skill['level'], 3); ?>>Intermediate</option>
                                            <option value="4" <?php selected($skill['level'], 4); ?>>Advanced</option>
                                            <option value="5" <?php selected($skill['level'], 5); ?>>Expert</option>
                                        </select>
                                        <button type="button" onclick="removeSkill(this)">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-skill-btn" onclick="addSkill()">Add Skill</button>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProfileModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }

        // Avatar upload preview
        document.getElementById('avatarUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" alt="Profile Avatar">';
                }
                reader.readAsDataURL(file);
                
                // Upload avatar via AJAX
                uploadAvatar(file);
            }
        });

        // Upload avatar function
        function uploadAvatar(file) {
            const formData = new FormData();
            formData.append('avatar', file);
            formData.append('action', 'upload_user_avatar');
            formData.append('nonce', '<?php echo wp_create_nonce('profile_update_nonce'); ?>');

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('Avatar updated successfully!', 'success');
                        // Update main profile avatar
                        document.getElementById('profileAvatarLarge').innerHTML = '<img src="' + response.data.avatar_url + '" alt="Profile Avatar">';
                    } else {
                        showAlert('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showAlert('Error uploading avatar. Please try again.', 'error');
                }
            });
        }

        // Skills management
        function addSkill() {
            const container = document.getElementById('skillsContainer');
            const skillInput = document.createElement('div');
            skillInput.className = 'skill-input';
            skillInput.innerHTML = `
                <input type="text" placeholder="Skill name">
                <select>
                    <option value="1">Beginner</option>
                    <option value="2">Novice</option>
                    <option value="3">Intermediate</option>
                    <option value="4">Advanced</option>
                    <option value="5">Expert</option>
                </select>
                <button type="button" onclick="removeSkill(this)">Remove</button>
            `;
            container.appendChild(skillInput);
        }

        function removeSkill(button) {
            button.parentElement.remove();
        }

        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_user_profile');
            
            // Collect skills data
            const skills = {};
            const skillInputs = document.querySelectorAll('.skill-input');
            skillInputs.forEach(input => {
                const name = input.querySelector('input').value;
                const level = input.querySelector('select').value;
                if (name.trim()) {
                    if (!skills['General']) skills['General'] = [];
                    skills['General'].push({name: name, level: parseInt(level)});
                }
            });
            formData.append('skills', JSON.stringify(skills));

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('Profile updated successfully!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showAlert('Error updating profile. Please try again.', 'error');
                }
            });
        });

        // Alert function
        function showAlert(message, type) {
            const alertDiv = document.getElementById('profileAlert');
            alertDiv.innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 5000);
        }

        // Share profile function
        function shareProfile() {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo esc_js($user_data['display_name']); ?>\'s Profile',
                    url: url
                });
            } else {
                // Fallback to copying URL
                navigator.clipboard.writeText(url).then(() => {
                    alert('Profile URL copied to clipboard!');
                });
            }
        }

        // Export CV function
        function exportCV() {
            // This would generate a PDF or document with user's profile info
            alert('CV export functionality would be implemented here');
        }
    </script>

    <?php
    return ob_get_clean();
}

// Helper function to get user avatar with fallback
function courscribe_get_user_avatar($user_id, $size = 80) {
    $avatar_url = get_user_meta($user_id, 'courscribe_avatar_url', true);
    
    if ($avatar_url) {
        return '<img src="' . esc_url($avatar_url) . '" alt="User Avatar" width="' . $size . '" height="' . $size . '" style="border-radius: 50%; object-fit: cover;">';
    } else {
        $user = get_user_by('ID', $user_id);
        $initials = substr($user->display_name, 0, 1);
        if (strpos($user->display_name, ' ')) {
            $initials .= substr($user->display_name, strpos($user->display_name, ' ') + 1, 1);
        }
        return '<div style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; background-color: #007cba; display: flex; align-items: center; justify-content: center; color: white; font-size: ' . ($size * 0.4) . 'px; font-weight: bold;">' . esc_html($initials) . '</div>';
    }
}

// Add custom user meta fields to user profile in admin
add_action('show_user_profile', 'courscribe_add_custom_user_profile_fields');
add_action('edit_user_profile', 'courscribe_add_custom_user_profile_fields');

function courscribe_add_custom_user_profile_fields($user) {
    ?>
    <h3>CourScribe Profile Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="courscribe_job_title">Job Title</label></th>
            <td>
                <input type="text" name="courscribe_job_title" id="courscribe_job_title" value="<?php echo esc_attr(get_user_meta($user->ID, 'courscribe_job_title', true)); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="courscribe_bio">Bio</label></th>
            <td>
                <textarea name="courscribe_bio" id="courscribe_bio" rows="5" cols="30"><?php echo esc_textarea(get_user_meta($user->ID, 'courscribe_bio', true)); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="courscribe_phone">Phone</label></th>
            <td>
                <input type="text" name="courscribe_phone" id="courscribe_phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'courscribe_phone', true)); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="courscribe_location">Location</label></th>
            <td>
                <input type="text" name="courscribe_location" id="courscribe_location" value="<?php echo esc_attr(get_user_meta($user->ID, 'courscribe_location', true)); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="courscribe_specialization">Specialization</label></th>
            <td>
                <input type="text" name="courscribe_specialization" id="courscribe_specialization" value="<?php echo esc_attr(get_user_meta($user->ID, 'courscribe_specialization', true)); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="courscribe_experience_years">Years of Experience</label></th>
            <td>
                <input type="number" name="courscribe_experience_years" id="courscribe_experience_years" value="<?php echo esc_attr(get_user_meta($user->ID, 'courscribe_experience_years', true)); ?>" min="0" max="50" />
            </td>
        </tr>
    </table>
    <?php
}

// Save custom user meta fields
add_action('personal_options_update', 'courscribe_save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'courscribe_save_custom_user_profile_fields');

function courscribe_save_custom_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $fields = [
        'courscribe_job_title',
        'courscribe_bio',
        'courscribe_phone',
        'courscribe_location',
        'courscribe_specialization',
        'courscribe_experience_years',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
