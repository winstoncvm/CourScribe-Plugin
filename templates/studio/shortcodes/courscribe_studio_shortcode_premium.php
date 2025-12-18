<?php
// Premium CourScribe Studio Shortcode - Modern Redesign
// Beautiful, modern studio interface with elegant tabs and brand colors
if (!defined('ABSPATH')) {
    exit;
}

function courscribe_premium_studio_shortcode($atts) {
    // Check authentication and permissions
    if (!is_user_logged_in()) {
        return courscribe_premium_auth_required();
    }

    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;

    $is_collaborator = in_array( 'collaborator', $user_roles );
    $is_client = in_array( 'client', $user_roles );
    $is_studio_admin = in_array( 'studio_admin', $user_roles );
    $is_wp_admin = current_user_can('administrator');
    
    // Get studio information
    global $post;
    $post_id = $post->ID;
    $site_url = home_url();
    $plugin_url = plugin_dir_url(__FILE__ . '/../../../');

    // Determine user's studio ID for autofill
    $user_studio_id = 0;
    if ($is_collaborator || $is_client) {
        $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if ($is_client && !$user_studio_id) {
            global $wpdb;
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
    
    // Get studio meta data
    $email = get_post_meta($user_studio_id, '_studio_email', true) ?: 'Not set';
    $website = get_post_meta($user_studio_id, '_studio_website', true) ?: 'Not set';
    $address = get_post_meta($user_studio_id, '_studio_address', true) ?: 'Not set';
    $is_public = get_post_meta($user_studio_id, '_studio_is_public', true) === 'Yes' ? 'Public' : 'Private';
    $tier = courscribe_get_user_tier($current_user->ID);
    
    // Calculate statistics for this studio only - using the correct count query
    $args = [
        'post_type'      => ['crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'],
        'post_status' => ['publish', 'draft', 'pending', 'future'],
        'meta_query'     => [
            [
                'key'     => '_studio_id',
                'value'   => $user_studio_id,
                'compare' => '=='
            ]
        ],
        'fields'         => 'ids',
        'posts_per_page' => -1
    ];   
    
    $studio_posts = new WP_Query($args);
    $posts = $studio_posts->posts;    
    
    // Initialize counts
    $curriculum_count = 0;
    $course_count = 0;
    $module_count = 0;
    $lesson_count = 0;

    // Count each post type
    foreach ($posts as $post_id) {
        $post_type = get_post_type($post_id);
        if ($post_type === 'crscribe_curriculum') {
            $curriculum_count++;
        } elseif ($post_type === 'crscribe_course') {
            $course_count++;
        } elseif ($post_type === 'crscribe_module') {
            $module_count++;
        } elseif ($post_type === 'crscribe_lesson') {
            $lesson_count++;
        }
    }
    
    // Reset post data to avoid conflicts
    wp_reset_postdata();
    
    // Get collaborators
    $collaborators = get_users([
        'role' => 'collaborator',
        'meta_key' => '_courscribe_studio_id',
        'meta_value' => $user_studio_id,
        'fields' => ['ID', 'user_email', 'user_login', 'display_name'],
        'number' => 10,
    ]);
    
    
    $studio_name_display = $user_studio_id ? get_post($user_studio_id)->post_title : ($is_wp_admin ? "All Studios (Admin View)" : 'No Studio Associated');
    ob_start();
    ?>
    
    <!-- Premium Studio Interface -->
    <div class="courscribe-studio-premium-redesign" id="studio-app">
        
        <!-- Studio Hero Section -->
        <div class="studio-hero">
            <div class="hero-content">
                <div class="studio-welcome">
                    <div class="welcome-text">
                        <h1 class="studio-title">
                            <span class="gradient-text"><?php echo esc_html( $studio_name_display ); ?></span>
                            <?php if ($tier !== 'basics'): ?>
                                <div class="premium-badge">
                                    <i class="fas fa-crown"></i>
                                    <span><?php echo esc_html(ucfirst($tier)); ?></span>
                                </div>
                            <?php endif; ?>
                        </h1>
                        <p class="studio-subtitle">
                            Welcome back, <strong><?php echo esc_html($current_user->display_name); ?></strong>! 
                            Ready to create amazing educational content?
                        </p>
                    </div>
                    
                    <div class="quick-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo esc_html($curriculum_count); ?></div>
                            <div class="stat-label">Curriculums</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo esc_html($course_count); ?></div>
                            <div class="stat-label">Courses</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo esc_html($lesson_count); ?></div>
                            <div class="stat-label">Lessons</div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-actions">
                    <a href="/curriculums" class="btn-premium-large" id="create-curriculum-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create New Curriculum</span>
                        <div class="btn-glow"></div>
                    </a>
                    
                    <div class="secondary-actions">
                        <button class="btn-secondary" onclick="courscribeTour.startTour()">
                            <i class="fas fa-route"></i>
                            <span>Take a Tour</span>
                        </button>
                        <button class="btn-secondary" onclick="handleInviteTeam()">
                            <?php if ($is_wp_admin): ?>
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Go to Dashboard</span>
                            <?php else: ?>
                                <i class="fas fa-book-open"></i>
                                <span>View Curriculums</span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Floating Elements -->
            <div class="hero-decoration">
                <div class="floating-icon icon-1">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="floating-icon icon-2">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="floating-icon icon-3">
                    <i class="fas fa-rocket"></i>
                </div>
            </div>
        </div>
        
        <!-- Modern Tab Navigation -->
        <div class="premium-tabs-container">
            <div class="tabs-wrapper">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="dashboard">
                        <div class="tab-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span class="tab-label">Dashboard</span>
                        <div class="tab-indicator"></div>
                    </button>
                    
                    <button class="tab-btn" data-tab="curriculums" onclick="handleCurriculumTabClick(event)">
                        <div class="tab-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <span class="tab-label">Curriculums</span>
                        <div class="tab-indicator"></div>
                    </button>
                    
                    <button class="tab-btn" data-tab="courscribe-team">
                        <div class="tab-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="tab-label">Team</span>
                        <div class="tab-indicator"></div>
                    </button>
                    
                    <button class="tab-btn" data-tab="analytics">
                        <div class="tab-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="tab-label">Analytics</span>
                        <div class="tab-indicator"></div>
                    </button>
                    
                    <button class="tab-btn" data-tab="affiliate">
                        <div class="tab-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <span class="tab-label">Affiliate</span>
                        <div class="tab-indicator"></div>
                    </button>
                    
                    <button class="tab-btn" data-tab="courscribe-settings">
                        <div class="tab-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="tab-label">Settings</span>
                        <div class="tab-indicator"></div>
                    </button>
                </div>
                
                <div class="tab-actions">
                    <div class="user-profile">
                        <button class="profile-btn" id="user-menu-btn">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="profile-info">
                                <span class="profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                                <span class="profile-role">Studio Admin</span>
                            </div>
                            <i class="fas fa-chevron-down profile-arrow"></i>
                        </button>
                        
                        <div class="profile-dropdown" id="user-dropdown">
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <div class="user-name"><?php echo esc_html($current_user->display_name); ?></div>
                                    <div class="user-email"><?php echo esc_html($current_user->user_email); ?></div>
                                </div>
                            </div>
                            <div class="dropdown-content">
                                <?php if ($tier !== 'pro'): ?>
                                    <a href="<?php echo home_url('/select-tribe/'); ?>" class="dropdown-item premium">
                                        <i class="fas fa-crown"></i>
                                        <span>Upgrade Plan</span>
                                        <div class="upgrade-badge">Pro</div>
                                    </a>
                                <?php endif; ?>
                                <a href="#" class="dropdown-item" onclick="switchTab('settings')">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="dropdown-item logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Content Container -->
        <div class="tab-content-container">
            
            <!-- Dashboard Tab -->
            <div class="tab-content active" id="dashboard-tab">
                <!-- Enhanced Stats Grid -->
                <div class="premium-stats-grid">
                    <div class="stat-card curriculum-stat">
                        <div class="stat-visual">
                            <div class="stat-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-ring">
                                    <svg width="60" height="60">
                                        <circle cx="30" cy="30" r="25" stroke="rgba(228, 178, 111, 0.2)" stroke-width="4" fill="none"/>
                                        <circle cx="30" cy="30" r="25" stroke="#E4B26F" stroke-width="4" fill="none" 
                                                stroke-dasharray="157" stroke-dashoffset="<?php echo 157 - (($curriculum_count / 10) * 157); ?>"
                                                class="progress-circle" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo esc_html($curriculum_count); ?></div>
                            <div class="stat-label">Curriculums</div>
                            <div class="stat-subtitle">
                                <?php if ($tier === 'basics'): ?>
                                    of 1 available
                                <?php else: ?>
                                    created this month
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card course-stat">
                        <div class="stat-visual">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-chart">
                                <div class="mini-bars">
                                    <div class="bar" style="height: 60%;"></div>
                                    <div class="bar" style="height: 40%;"></div>
                                    <div class="bar" style="height: 80%;"></div>
                                    <div class="bar" style="height: 100%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo esc_html($course_count); ?></div>
                            <div class="stat-label">Courses</div>
                            <div class="stat-change positive">
                                <i class="fas fa-trending-up"></i>
                                <span>Growing</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card module-stat">
                        <div class="stat-visual">
                            <div class="stat-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="stat-dots">
                                <?php for ($i = 0; $i < min(12, $module_count); $i++): ?>
                                    <div class="dot active"></div>
                                <?php endfor; ?>
                                <?php for ($i = $module_count; $i < 12; $i++): ?>
                                    <div class="dot"></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo esc_html($module_count); ?></div>
                            <div class="stat-label">Modules</div>
                            <div class="stat-subtitle">across all courses</div>
                        </div>
                    </div>
                    
                    <div class="stat-card lesson-stat">
                        <div class="stat-visual">
                            <div class="stat-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div class="stat-wave">
                                <div class="wave-line"></div>
                                <div class="wave-line"></div>
                                <div class="wave-line"></div>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo esc_html($lesson_count); ?></div>
                            <div class="stat-label">Lessons</div>
                            <div class="stat-subtitle">ready to teach</div>
                        </div>
                    </div>
                </div>
                
                <!-- Premium Dashboard Content -->
                <div class="premium-dashboard-grid">
                    
                    <!-- Quick Actions Card -->
                    <!-- <div class="premium-card quick-actions-card">
                        <div class="card-header">
                            <div class="header-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="header-content">
                                <h3 class="card-title">Quick Actions</h3>
                                <p class="card-subtitle">Get started with common tasks</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="action-grid">
                                <button class="action-item primary" onclick="createNewCurriculum()">
                                    <div class="action-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">New Curriculum</span>
                                        <span class="action-desc">Start creating content</span>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </button>
                                
                                <?php if ($tier !== 'basics'): ?>
                                <button class="action-item secondary" onclick="inviteCollaborator()">
                                    <div class="action-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">Invite Team</span>
                                        <span class="action-desc">Add collaborators</span>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </button>
                                <?php endif; ?>
                                
                                <button class="action-item tertiary" onclick="exportContent()">
                                    <div class="action-icon">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">Export Content</span>
                                        <span class="action-desc">Generate materials</span>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </button>
                                
                                <button class="action-item tertiary" onclick="switchTab('analytics')">
                                    <div class="action-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="action-content">
                                        <span class="action-title">View Analytics</span>
                                        <span class="action-desc">Track progress</span>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div> -->
                    
                    <!-- Recent Activity -->
                    <!-- <div class="dashboard-card recent-activity">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock"></i>
                                Recent Changes
                            </h3>
                            <div class="card-actions">
                                <div class="activity-filter">
                                    <select id="activity-filter-select" class="form-control-sm">
                                        <option value="all">All Activities</option>
                                        <option value="curriculum">Curriculum</option>
                                        <option value="course">Course</option>
                                        <option value="module">Module</option>
                                        <option value="lesson">Lesson</option>
                                    </select>
                                </div>
                                <button class="card-action" id="view-all-activity">View All</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="activity-feed" id="activity-feed">
                                <div class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading recent activity...</p>
                                </div>
                            </div>
                            <div class="activity-pagination" id="activity-pagination" style="display: none;">
                                <button class="btn btn-sm btn-secondary" id="activity-prev-btn" disabled>
                                    <i class="fas fa-chevron-left"></i>
                                    Previous
                                </button>
                                <span class="pagination-info" id="activity-page-info">Page 1 of 1</span>
                                <button class="btn btn-sm btn-secondary" id="activity-next-btn" disabled>
                                    Next
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div> -->
                    
                    <!-- Studio Progress -->
                    <!-- <div class="dashboard-card studio-progress">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie"></i>
                                Studio Progress
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="progress-chart">
                                <canvas id="progress-chart" width="300" height="200"></canvas>
                            </div>
                            <div class="progress-stats">
                                <div class="progress-stat">
                                    <div class="progress-dot completed"></div>
                                    <span>Completed: 75%</span>
                                </div>
                                <div class="progress-stat">
                                    <div class="progress-dot in-progress"></div>
                                    <span>In Progress: 20%</span>
                                </div>
                                <div class="progress-stat">
                                    <div class="progress-dot pending"></div>
                                    <span>Pending: 5%</span>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    
                    <!-- Studio Overview -->
                    <div class="dashboard-card studio-overview">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building"></i>
                                Studio Overview
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="overview-items">
                                <div class="overview-item">
                                    <div class="overview-label">Status</div>
                                    <div class="overview-value">
                                        <span class="status-badge <?php echo strtolower($is_public); ?>">
                                            <?php echo esc_html($is_public); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="overview-item">
                                    <div class="overview-label">Team Members</div>
                                    <div class="overview-value"><?php echo count($collaborators); ?></div>
                                </div>
                                <div class="overview-item">
                                    <div class="overview-label">Contact</div>
                                    <div class="overview-value"><?php echo esc_html($email); ?></div>
                                </div>
                                <?php if ($website !== 'Not set'): ?>
                                <div class="overview-item">
                                    <div class="overview-label">Website</div>
                                    <div class="overview-value">
                                        <a href="<?php echo esc_url($website); ?>" target="_blank" class="external-link">
                                            <?php echo esc_html($website); ?>
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Curriculums Tab -->
            <div class="tab-content" id="curriculums-tab">
                <!-- <div class="tab-header">
                    <div class="header-content">
                        <h2 class="tab-title">Your Curriculums</h2>
                        <p class="tab-subtitle">Create, manage, and organize your educational content</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-premium" onclick="createNewCurriculum()">
                            <i class="fas fa-plus"></i>
                            <span>New Curriculum</span>
                        </button>
                    </div>
                </div> -->
                
                <!-- Enhanced Curriculum Manager Content -->
                <div class="premium-curriculum-wrapper">
                   
                    
                    <?php 
                    // Get curriculum manager content
                    $curriculum_content = do_shortcode('[courscribe_curriculum_manager]');
                    echo $curriculum_content;
                    ?>
                </div>
            </div>
            
            <!-- Team Tab -->
            <div class="tab-content" id="courscribe-team-tab">
                <?php 
                // Load team management shortcode
                echo do_shortcode('[courscribe_premium_team]');
                ?>
            </div>
            
            <!-- Analytics Tab -->
            <div class="tab-content" id="analytics-tab">
                <?php 
                // Load analytics shortcode
                echo do_shortcode('[courscribe_premium_analytics]');
                ?>
            </div>
            
            <!-- Affiliate Tab -->
            <div class="tab-content" id="affiliate-tab">
                <?php 
                // Load affiliate dashboard shortcode
                echo do_shortcode('[courscribe_premium_affiliate]');
                ?>
            </div>
            
            <!-- Settings Tab -->
            <div class="tab-content" id="courscribe-settings-tab">
                <?php 
                // Load premium settings shortcode
                echo do_shortcode('[courscribe_premium_settings]');
                ?>
            </div>
            
        </main>
        
        <!-- Modals and Overlays -->
        <div class="modal-overlay" id="modal-overlay">
            <!-- Invite Modal -->
            <div class="modal" id="invite-modal">
                <div class="modal-header">
                    <h3>Invite Team Member</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form class="invite-form" id="invite-form">
                        <?php wp_nonce_field('courscribe_premium_studio', 'courscribe_studio_nonce'); ?>
                        <div class="form-group">
                            <label for="invite-email">Email Address</label>
                            <input type="email" id="invite-email" name="invite_email" 
                                   class="form-control" placeholder="colleague@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="invite-role">Role</label>
                            <select id="invite-role" name="invite_role" class="form-control">
                                <option value="collaborator">Collaborator</option>
                                <option value="editor">Editor</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="invite-message">Personal Message (Optional)</label>
                            <textarea id="invite-message" name="invite_message" 
                                      class="form-control" rows="3" 
                                      placeholder="Add a personal note to your invitation..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Send Invitation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Archive Confirmation Modal -->
            <div class="modal premium-modal" id="archive-modal">
                <div class="modal-header">
                    <div class="modal-icon archive">
                        <i class="fas fa-archive"></i>
                    </div>
                    <h3>Archive Curriculum</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="confirmation-content">
                        <p class="confirmation-text">
                            Are you sure you want to archive "<span id="archive-curriculum-title"></span>"?
                        </p>
                        <div class="confirmation-details">
                            <div class="detail-item">
                                <i class="fas fa-info-circle"></i>
                                <span>The curriculum will be moved to archived section</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-undo"></i>
                                <span>You can restore it at any time</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-warning" id="confirm-archive-btn">
                            <i class="fas fa-archive"></i>
                            Archive Curriculum
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Delete Confirmation Modal -->
            <div class="modal premium-modal danger" id="delete-modal">
                <div class="modal-header">
                    <div class="modal-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Delete Curriculum</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="confirmation-content">
                        <p class="confirmation-text">
                            Are you sure you want to permanently delete "<span id="delete-curriculum-title"></span>"?
                        </p>
                        <div class="confirmation-details danger">
                            <div class="detail-item">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>This action cannot be undone</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-trash"></i>
                                <span>All curriculum content will be permanently lost</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="delete-confirmation-text" class="danger-label">
                                Type "<strong>DELETE</strong>" to confirm:
                            </label>
                            <input type="text" id="delete-confirmation-text" class="form-control danger" 
                                   placeholder="Type DELETE here" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm-delete-btn" disabled>
                            <i class="fas fa-trash"></i>
                            Delete Permanently
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loading-overlay" style="display: none;">
            <div class="loader">
                <div class="loader-spinner"></div>
                <div class="loader-text">Loading...</div>
            </div>
        </div>
        
    </div>
    
            </div>
    </div>

    

    <!-- Include studio premium functionality -->
    <script src="<?php echo $plugin_url; ?>assets/js/studio-premium.js?v=<?php echo time(); ?>"></script>
    
    <!-- Premium Studio JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initPremiumStudio();
    });

    function initPremiumStudio() {
        // Tab switching functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;
                switchTab(tabId);
            });
        });

        // User dropdown functionality
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');

        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });

            document.addEventListener('click', () => {
                userDropdown.classList.remove('show');
            });
        }

        // Initialize charts if available
        if (typeof Chart !== 'undefined') {
            initCharts();
        }

        // Animate stats on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        });

        document.querySelectorAll('.stat-card').forEach(card => {
            observer.observe(card);
        });
        
        // Initialize hero section buttons
        const createCurriculumBtn = document.getElementById('create-curriculum-btn');
        if (createCurriculumBtn) {
            createCurriculumBtn.addEventListener('click', createNewCurriculum);
        }
        
        // Initialize invite form
        const inviteForm = document.getElementById('invite-form');
        if (inviteForm) {
            inviteForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await handleInviteSubmission(this);
            });
        }
        
        // Initialize CourScribe Tour functionality
        window.courscribeTour = {
            currentStep: 0,
            isActive: false,
            overlay: null,
            
            steps: [
                {
                    target: '.studio-hero .hero-content',
                    title: 'Welcome to CourScribe Studio!',
                    content: 'Your complete educational content creation platform. Build professional curriculums, courses, and lessons with AI-powered assistance.',
                    position: 'bottom'
                },
                {
                    target: '.quick-stats',
                    title: 'Studio Overview',
                    content: 'Monitor your content creation progress. Track curriculums, courses, and lessons at a glance to stay organized.',
                    position: 'bottom'
                },
                {
                    target: '#create-curriculum-btn',
                    title: 'Start Creating Content',
                    content: 'Ready to build your first curriculum? Click here to begin creating professional educational content with our guided workflow.',
                    position: 'bottom'
                },
                {
                    target: '[data-tab="dashboard"]',
                    title: 'Dashboard',
                    content: 'Your control center with key metrics, studio overview, and quick access to important features.',
                    position: 'bottom'
                },
                {
                    target: '[data-tab="curriculums"]', 
                    title: 'Curriculum Builder',
                    content: 'Create and manage your educational content. Build structured courses with modules, lessons, and AI-generated materials.',
                    position: 'bottom'
                },
                {
                    target: '[data-tab="courscribe-team"]',
                    title: 'Team Management',
                    content: 'Collaborate with your team. Invite colleagues, assign roles, and work together on curriculum development.',
                    position: 'bottom'
                },
                {
                    target: '[data-tab="analytics"]',
                    title: 'Analytics & Insights', 
                    content: 'Track your content performance and team productivity with detailed analytics and reporting.',
                    position: 'bottom'
                },
                {
                    target: '[data-tab="affiliate"]',
                    title: 'Affiliate Program',
                    content: 'Earn by sharing CourScribe. Access your referral links, track earnings, and grow your educational network.',
                    position: 'bottom'
                },
                {
                    target: '[data-tab="courscribe-settings"]',
                    title: 'Studio Settings',
                    content: 'Personalize your workspace. Configure studio preferences, manage your profile, and customize your experience.',
                    position: 'bottom'
                }
            ],
            
            startTour: function() {
                this.currentStep = 0;
                this.isActive = true;
                this.createOverlay();
                this.showStep(0);
                this.trackEvent('tour_started', { source: 'studio_hero' });
            },
            
            createOverlay: function() {
                if (this.overlay) return;
                
                this.overlay = document.createElement('div');
                this.overlay.className = 'courscribe-tour-overlay';
                this.overlay.innerHTML = `
                    <div class="tour-backdrop"></div>
                    <div class="tour-tooltip" id="tour-tooltip">
                        <div class="tour-header">
                            <h3 class="tour-title"></h3>
                            <button class="tour-close" onclick="courscribeTour.endTour()">&times;</button>
                        </div>
                        <div class="tour-content">
                            <p class="tour-text"></p>
                        </div>
                        <div class="tour-footer">
                            <div class="tour-progress">
                                <span class="tour-step-indicator"></span>
                            </div>
                            <div class="tour-actions">
                                <button class="tour-btn tour-btn-secondary" onclick="courscribeTour.previousStep()">Previous</button>
                                <button class="tour-btn tour-btn-primary" onclick="courscribeTour.nextStep()">Next</button>
                                <button class="tour-btn tour-btn-primary" onclick="courscribeTour.endTour()" style="display: none;">Finish</button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add tour styles
                const tourStyles = document.createElement('style');
                tourStyles.textContent = `
                    .courscribe-tour-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 10000;
                        pointer-events: none;
                    }
                    
                    .tour-backdrop {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.8);
                        pointer-events: auto;
                    }
                    
                    .tour-tooltip {
                        position: absolute;
                        background: linear-gradient(135deg, #2a2a2b 0%, #353535 100%);
                        border: 1px solid rgba(228, 178, 111, 0.3);
                        border-radius: 16px;
                        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
                        max-width: 320px;
                        pointer-events: auto;
                        z-index: 10001;
                        animation: tourSlideIn 0.3s ease-out;
                    }
                    
                    @keyframes tourSlideIn {
                        from { opacity: 0; transform: translateY(10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .tour-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 16px 20px 12px;
                        border-bottom: 1px solid rgba(228, 178, 111, 0.2);
                    }
                    
                    .tour-title {
                        color: #E4B26F;
                        font-size: 16px;
                        font-weight: 600;
                        margin: 0;
                    }
                    
                    .tour-close {
                        background: none;
                        border: none;
                        color: rgba(255, 255, 255, 0.6);
                        font-size: 20px;
                        cursor: pointer;
                        padding: 4px;
                        border-radius: 4px;
                        transition: all 0.2s ease;
                    }
                    
                    .tour-close:hover {
                        color: #fff;
                        background: rgba(228, 178, 111, 0.2);
                    }
                    
                    .tour-content {
                        padding: 12px 20px;
                    }
                    
                    .tour-text {
                        color: rgba(255, 255, 255, 0.9);
                        font-size: 14px;
                        line-height: 1.5;
                        margin: 0;
                    }
                    
                    .tour-footer {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 12px 20px 16px;
                        border-top: 1px solid rgba(228, 178, 111, 0.1);
                    }
                    
                    .tour-step-indicator {
                        color: rgba(255, 255, 255, 0.6);
                        font-size: 12px;
                    }
                    
                    .tour-actions {
                        display: flex;
                        gap: 8px;
                    }
                    
                    .tour-btn {
                        padding: 6px 12px;
                        border-radius: 6px;
                        font-size: 12px;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        border: none;
                    }
                    
                    .tour-btn-secondary {
                        background: rgba(255, 255, 255, 0.1);
                        color: rgba(255, 255, 255, 0.8);
                    }
                    
                    .tour-btn-secondary:hover {
                        background: rgba(255, 255, 255, 0.2);
                        color: #fff;
                    }
                    
                    .tour-btn-primary {
                        background: linear-gradient(135deg, #F8923E 0%, #F25C3B 100%);
                        color: white;
                    }
                    
                    .tour-btn-primary:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 4px 12px rgba(248, 146, 62, 0.4);
                    }
                    
                    .tour-highlight {
                        position: relative;
                        z-index: 10002 !important;
                        border-radius: 8px !important;
                        box-shadow: 0 0 0 4px rgba(228, 178, 111, 0.4) !important;
                        animation: tourPulse 2s ease-in-out infinite;
                    }
                    
                    @keyframes tourPulse {
                        0%, 100% { box-shadow: 0 0 0 4px rgba(228, 178, 111, 0.4); }
                        50% { box-shadow: 0 0 0 8px rgba(228, 178, 111, 0.2); }
                    }
                `;
                
                document.head.appendChild(tourStyles);
                document.body.appendChild(this.overlay);
            },
            
            showStep: function(stepIndex) {
                if (stepIndex >= this.steps.length || stepIndex < 0) return;
                
                const step = this.steps[stepIndex];
                const target = document.querySelector(step.target);
                const tooltip = document.getElementById('tour-tooltip');
                
                if (!target || !tooltip) return;
                
                // Remove previous highlight
                document.querySelectorAll('.tour-highlight').forEach(el => {
                    el.classList.remove('tour-highlight');
                });
                
                // Highlight current target
                target.classList.add('tour-highlight');
                
                // Update tooltip content
                tooltip.querySelector('.tour-title').textContent = step.title;
                tooltip.querySelector('.tour-text').textContent = step.content;
                tooltip.querySelector('.tour-step-indicator').textContent = `${stepIndex + 1} of ${this.steps.length}`;
                
                // Position tooltip
                this.positionTooltip(tooltip, target, step.position);
                
                // Update button states
                const prevBtn = tooltip.querySelector('.tour-btn-secondary');
                const nextBtn = tooltip.querySelector('.tour-btn-primary:not([onclick*="endTour"])');
                const finishBtn = tooltip.querySelector('.tour-btn-primary[onclick*="endTour"]');
                
                prevBtn.style.display = stepIndex === 0 ? 'none' : 'inline-block';
                
                if (stepIndex === this.steps.length - 1) {
                    nextBtn.style.display = 'none';
                    finishBtn.style.display = 'inline-block';
                } else {
                    nextBtn.style.display = 'inline-block';
                    finishBtn.style.display = 'none';
                }
                
                this.currentStep = stepIndex;
            },
            
            positionTooltip: function(tooltip, target, position) {
                const targetRect = target.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                let top, left;
                
                switch (position) {
                    case 'bottom':
                        top = targetRect.bottom + 10;
                        left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                        break;
                    case 'top':
                        top = targetRect.top - tooltipRect.height - 10;
                        left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                        break;
                    case 'left':
                        top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                        left = targetRect.left - tooltipRect.width - 10;
                        break;
                    case 'right':
                        top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                        left = targetRect.right + 10;
                        break;
                    default:
                        top = targetRect.bottom + 10;
                        left = targetRect.left;
                }
                
                // Keep tooltip within viewport
                top = Math.max(10, Math.min(top, window.innerHeight - tooltipRect.height - 10));
                left = Math.max(10, Math.min(left, window.innerWidth - tooltipRect.width - 10));
                
                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
            },
            
            nextStep: function() {
                if (this.currentStep < this.steps.length - 1) {
                    this.showStep(this.currentStep + 1);
                }
            },
            
            previousStep: function() {
                if (this.currentStep > 0) {
                    this.showStep(this.currentStep - 1);
                }
            },
            
            endTour: function() {
                this.isActive = false;
                
                // Remove highlight
                document.querySelectorAll('.tour-highlight').forEach(el => {
                    el.classList.remove('tour-highlight');
                });
                
                // Remove overlay
                if (this.overlay) {
                    this.overlay.remove();
                    this.overlay = null;
                }
                
                this.trackEvent('tour_completed', { 
                    steps_completed: this.currentStep + 1,
                    total_steps: this.steps.length
                });
            },
            
            trackEvent: function(event, data) {
                console.log('CourScribe Tour:', event, data);
                // Track with analytics if available
                if (typeof gtag !== 'undefined') {
                    gtag('event', event, {
                        event_category: 'tour',
                        event_label: event,
                        ...data
                    });
                }
            }
        };
    }

    function handleCurriculumTabClick(event) {
        event.preventDefault();
        // Redirect to curriculums page instead of showing tab content
        window.location.href = '/curriculums';
        return false;
    }
    
    function switchTab(tabId) {
        // Special handling for curriculums tab
        if (tabId === 'curriculums') {
            window.location.href = '/curriculums';
            return;
        }
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`${tabId}-tab`).classList.add('active');

        // Track analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'tab_switch', {
                'tab_name': tabId
            });
        }
    }

    function createNewCurriculum() {
        // Trigger curriculum creation
        if (typeof courscribeTour !== 'undefined') {
            courscribeTour.trackEvent('create_curriculum_clicked', {
                'source': 'studio_hero'
            });
        }
        
        // Switch to curriculums tab and trigger new curriculum modal
        switchTab('curriculums');
        
        // Wait for tab switch, then trigger curriculum creation
        setTimeout(() => {
            const createBtn = document.querySelector('#curriculums-tab .create-curriculum-btn, #curriculums-tab .btn-premium');
            if (createBtn) {
                createBtn.click();
            } else {
                // Fallback: trigger curriculum creation via event
                document.dispatchEvent(new CustomEvent('createCurriculum'));
            }
        }, 100);
    }

    function handleInviteTeam() {
        // Check user role and redirect accordingly
        const isAdmin = <?php echo current_user_can('administrator') ? 'true' : 'false'; ?>;
        
        if (isAdmin) {
            // Redirect to admin dashboard for administrators
            window.location.href = '<?php echo admin_url('admin.php?page=courscribe'); ?>';
        } else {
            // Redirect to curriculums page for other roles
            window.location.href = '/curriculums';
        }
    }
    
    function inviteCollaborator() {
        // Switch to team tab and open invite modal
        switchTab('courscribe-team');
        
        // Wait for tab switch, then trigger invite modal
        setTimeout(() => {
            const inviteBtn = document.querySelector('#courscribe-team-tab #invite-team-member');
            if (inviteBtn) {
                inviteBtn.click();
            } else {
                // Fallback: show modal directly
                showModal('invite-modal');
            }
        }, 100);
    }

    function exportContent() {
        // Switch to curriculums tab and trigger export
        switchTab('curriculums');
        
        // Wait for tab switch, then show export options
        setTimeout(() => {
            // Check if there are any curriculums to export
            const curriculumCards = document.querySelectorAll('#curriculums-tab .curriculum-card');
            if (curriculumCards.length === 0) {
                alert('No curriculums available to export. Create a curriculum first.');
                return;
            }
            
            // Show export options or trigger existing export functionality
            const exportBtn = document.querySelector('#curriculums-tab .export-btn, [data-action="export"]');
            if (exportBtn) {
                exportBtn.click();
            } else {
                // Fallback: show simple export dialog
                const curriculumTitles = Array.from(curriculumCards).map(card => 
                    card.querySelector('.curriculum-title')?.textContent || 'Untitled'
                );
                alert('Export functionality will be available soon.\n\nAvailable curriculums:\n• ' + curriculumTitles.join('\n• '));
            }
        }, 100);
    }

    function initCharts() {
        // Initialize Chart.js charts if available
        const contentChart = document.getElementById('content-chart');
        const activityChart = document.getElementById('activity-chart');

        if (contentChart) {
            // Add chart initialization code here
        }

        if (activityChart) {
            // Add chart initialization code here
        }
    }
    
    // Modal management functions
    function showModal(modalId) {
        const overlay = document.getElementById('modal-overlay');
        const modal = document.getElementById(modalId);
        if (overlay && modal) {
            overlay.style.display = 'flex';
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal() {
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            // Hide all modals
            overlay.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }
    }
    
    // Handle invite form submission
    async function handleInviteSubmission(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'courscribe_send_invitation',
                    nonce: '<?php echo wp_create_nonce('courscribe_premium_studio'); ?>',
                    invite_email: formData.get('invite_email'),
                    invite_role: formData.get('invite_role'), 
                    invite_message: formData.get('invite_message')
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message
                showNotification('Invitation sent successfully!', 'success');
                form.reset();
                closeModal();
                
                // Refresh team tab if it's active
                const teamTab = document.getElementById('courscribe-team-tab');
                if (teamTab && teamTab.classList.contains('active')) {
                    // Trigger refresh of team content
                    setTimeout(() => {
                        location.reload(); // Simple refresh for now
                    }, 1000);
                }
            } else {
                showNotification(result.data?.message || 'Failed to send invitation', 'error');
            }
        } catch (error) {
            console.error('Invitation error:', error);
            showNotification('Failed to send invitation. Please try again.', 'error');
        } finally {
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
    
    // Simple notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">&times;</button>
        `;
        
        // Notification styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #2a2a2b 0%, #353535 100%);
            border: 1px solid ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#E4B26F'};
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 400px;
            color: white;
            transform: translateX(420px);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove
        setTimeout(() => {
            notification.style.transform = 'translateX(420px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.transform = 'translateX(420px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }
    
    function getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle', 
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    // Global functions for easier access
    window.createNewCurriculum = createNewCurriculum;
    window.inviteCollaborator = inviteCollaborator;
    window.handleInviteTeam = handleInviteTeam;
    window.handleCurriculumTabClick = handleCurriculumTabClick;
    window.exportContent = exportContent;
    window.showModal = showModal;
    window.closeModal = closeModal;
    window.showNotification = showNotification;
    </script>

    <?php
    return ob_get_clean();
}

// Helper function for authentication required message
function courscribe_premium_auth_required() {
    return '<div class="courscribe-auth-required">
        <div class="auth-message">
            <i class="fas fa-lock"></i>
            <h3>Authentication Required</h3>
            <p>Please log in to access your premium studio.</p>
            <a href="' . wp_login_url(get_permalink()) . '" class="btn">Login to Studio</a>
        </div>
    </div>';
}

// Register the shortcode
add_shortcode('courscribe_premium_studio', 'courscribe_premium_studio_shortcode');
?>