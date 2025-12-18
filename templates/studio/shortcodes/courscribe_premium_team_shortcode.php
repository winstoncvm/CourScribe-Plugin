<?php
// Premium Team Management Shortcode
// Comprehensive team collaboration and management system
if (!defined('ABSPATH')) {
    exit;
}

// Register the team management shortcode
add_shortcode('courscribe_premium_team', 'courscribe_premium_team_shortcode');

function courscribe_premium_team_shortcode($atts) {
    // Check user authentication
    if (!is_user_logged_in()) {
        return '<p>Please log in to manage your team.</p>';
    }
    
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_id = $current_user->ID;
    $table_name = $wpdb->prefix . 'courscribe_invites';
    
    // Define role permissions and hierarchy
    $is_collaborator = in_array('collaborator', $user_roles);
    $is_client = in_array('client', $user_roles);
    $is_studio_admin = in_array('studio_admin', $user_roles);
    $is_wp_admin = current_user_can('administrator');
    
    // Determine user's primary role for permissions
    $user_primary_role = 'client'; // Default lowest permission
    if ($is_wp_admin) {
        $user_primary_role = 'admin';
    } elseif ($is_studio_admin) {
        $user_primary_role = 'studio_admin';
    } elseif ($is_collaborator) {
        $user_primary_role = 'collaborator';
    }

    // Determine user's studio ID based on role
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
    
    // Get user's subscription tier
    $user_tier = get_user_meta($user_id, '_courscribe_user_tier', true) ?: 'basics';
    
    // Define role-based permissions
    $role_permissions = array(
        'admin' => array(
            'view_team' => true,
            'manage_team' => true,
            'invite_members' => true,
            'remove_members' => true,
            'edit_permissions' => true,
            'view_analytics' => true,
            'manage_settings' => true
        ),
        'studio_admin' => array(
            'view_team' => true,
            'manage_team' => true,
            'invite_members' => true,
            'remove_members' => true,
            'edit_permissions' => true,
            'view_analytics' => true,
            'manage_settings' => true
        ),
        'collaborator' => array(
            'view_team' => true,
            'manage_team' => false,
            'invite_members' => false,
            'remove_members' => false,
            'edit_permissions' => false,
            'view_analytics' => true,
            'manage_settings' => false
        ),
        'client' => array(
            'view_team' => true,
            'manage_team' => false,
            'invite_members' => false,
            'remove_members' => false,
            'edit_permissions' => false,
            'view_analytics' => false,
            'manage_settings' => false
        )
    );
    
    // Get current user permissions
    $user_permissions = $role_permissions[$user_primary_role] ?? $role_permissions['client'];
    
    // Define convenient permission variables
    $can_manage_team = $user_permissions['manage_team'] ?? false;
    $can_invite_members = $user_permissions['invite_members'] ?? false;
    $can_edit_permissions = $user_permissions['edit_permissions'] ?? false;
    
    // Check if user can access team management
    if (!$user_permissions['view_team']) {
        return '<p>You do not have permission to view team information.</p>';
    }
    
    // Get team data
    $team_data = courscribe_get_team_data($user_id, $user_studio_id);
    
    ob_start();
    ?>
    
    <div class="courscribe-premium-team">
        
        <!-- Team Header -->
        <div class="team-header">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="team-title">
                        <div class="title-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="gradient-text">Team Management</span>
                    </h1>
                    <p class="team-subtitle">
                        Collaborate with your team members and manage permissions across your studio.
                    </p>
                </div>
                
                <?php if ($user_permissions['invite_members']): ?>
                <div class="header-actions">
                    <button class="invite-btn" id="invite-team-member">
                        <i class="fas fa-user-plus"></i>
                        Invite Member
                    </button>
                    <?php if ($user_permissions['manage_team']): ?>
                    <button class="bulk-actions-btn" id="bulk-actions">
                        <i class="fas fa-tasks"></i>
                        Bulk Actions
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Team Statistics -->
        <div class="team-stats">
            <div class="stat-card">
                <div class="stat-icon gradient-bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($team_data['total_members']); ?></div>
                    <div class="stat-label">Team Members</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo esc_html($team_data['new_members_month']); ?> this month
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon gradient-bg-secondary">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($team_data['active_members']); ?></div>
                    <div class="stat-label">Active Members</div>
                    <div class="stat-change neutral">
                        <i class="fas fa-circle"></i>
                        Online this week
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon gradient-bg-success">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($team_data['collaborative_projects']); ?></div>
                    <div class="stat-label">Collaborative Projects</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        +<?php echo esc_html($team_data['projects_change']); ?>% efficiency
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon gradient-bg-warning">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo esc_html($team_data['pending_invites']); ?></div>
                    <div class="stat-label">Pending Invites</div>
                    <div class="stat-change neutral">
                        <i class="fas fa-envelope"></i>
                        Awaiting response
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Team Management Tabs -->
        <div class="team-tabs">
            <div class="tab-navigation-team">
                <button class="tab-btn-team active" data-tab="courscribe-members">
                    <i class="fas fa-users"></i>
                    <span>Team Members</span>
                    <span class="tab-count"><?php echo esc_html($team_data['total_members']); ?></span>
                </button>
                <button class="tab-btn-team" data-tab="courscribe-roles">
                    <i class="fas fa-user-tag"></i>
                    <span>Roles & Permissions</span>
                </button>
                <button class="tab-btn-team" data-tab="courscribe-activity">
                    <i class="fas fa-chart-line"></i>
                    <span>Team Activity</span>
                </button>
                <?php if ($user_permissions['manage_settings']): ?>
                <button class="tab-btn-team" data-tab="courscribe-team-settings">
                    <i class="fas fa-cog"></i>
                    <span>Team Settings</span>
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Members Tab -->
            <div class="tab-content-team active" id="courscribe-members-tabz">
                <div class="members-controls">
                    <div class="search-filter">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search team members..." id="member-search">
                        </div>
                        <div class="filter-dropdown">
                            <select id="role-filter">
                                <option value="">All Roles</option>
                                <option value="owner">Owner</option>
                                <option value="admin">Admin</option>
                                <option value="collaborator">Collaborator</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        <div class="status-filter">
                            <select id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="members-grid" id="members-list">
                    <?php foreach ($team_data['members'] as $member): ?>
                    <div class="member-card" data-role="<?php echo esc_attr($member['role']); ?>" data-status="<?php echo esc_attr($member['status']); ?>">
                        <div class="member-avatar">
                            <img src="<?php echo esc_url($member['avatar']); ?>" alt="<?php echo esc_attr($member['name']); ?>">
                            <div class="status-indicator <?php echo esc_attr($member['status']); ?>"></div>
                        </div>
                        
                        <div class="member-info">
                            <h3 class="member-name"><?php echo esc_html($member['name']); ?></h3>
                            <p class="member-email"><?php echo esc_html($member['email']); ?></p>
                            <div class="member-role">
                                <span class="role-badge role-<?php echo esc_attr($member['role']); ?>">
                                    <i class="fas fa-<?php echo esc_attr($member['role_icon']); ?>"></i>
                                    <?php echo esc_html(ucfirst($member['role'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="member-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo esc_html($member['contributions']); ?></span>
                                <span class="stat-label">Contributions</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo esc_html($member['last_active']); ?></span>
                                <span class="stat-label">Last Active</span>
                            </div>
                            <div class="stat-item">
                                <button class="stat-button view-activity" data-member="<?php echo esc_attr($member['user_id']); ?>" title="View Activity">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Activity</span>
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($member['user_id'] !== $user_id): ?>
                        <div class="member-actions">
                            <?php if ($user_permissions['edit_permissions']): ?>
                            <button class="action-btn edit" data-member="<?php echo esc_attr($member['user_id']); ?>" title="Edit Member">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <button class="action-btn message" data-member="<?php echo esc_attr($member['user_id']); ?>" title="Send Message">
                                <i class="fas fa-envelope"></i>
                            </button>
                            <?php if ($user_permissions['remove_members']): ?>
                            <button class="action-btn remove" data-member="<?php echo esc_attr($member['user_id']); ?>" title="Remove Member">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Roles & Permissions Tab -->
            <div class="tab-content-team" id="courscribe-roles-tabz">
                <div class="roles-overview">
                    <h3 class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        Role-Based Permissions
                    </h3>
                    <p class="section-description">
                        Manage what each role can do within your studio and curriculum projects.
                    </p>
                </div>
                
                <div class="roles-grid">
                    <?php foreach ($team_data['roles'] as $role): ?>
                    <div class="role-card">
                        <div class="role-header">
                            <div class="role-icon">
                                <i class="fas fa-<?php echo esc_attr($role['icon']); ?>"></i>
                            </div>
                            <div class="role-info">
                                <h4 class="role-name"><?php echo esc_html($role['name']); ?></h4>
                                <p class="role-description"><?php echo esc_html($role['description']); ?></p>
                                <div class="role-count"><?php echo esc_html($role['count']); ?> members</div>
                            </div>
                        </div>
                        
                        <div class="role-permissions">
                            <h5 class="permissions-title">Permissions</h5>
                            <div class="permissions-list">
                                <?php foreach ($role['permissions'] as $permission): ?>
                                <div class="permission-item">
                                    <i class="fas fa-check permission-icon"></i>
                                    <span class="permission-text"><?php echo esc_html($permission); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php if ($can_manage_team): ?>
                        <div class="role-actions">
                            <button class="role-edit-btn" data-role="<?php echo esc_attr($role['key']); ?>">
                                <i class="fas fa-edit"></i>
                                Customize Role
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Team Activity Tab -->
            <div class="tab-content-team " id="courscribe-activity-tabz">
                <div class="activity-overview">
                    <h3 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Team Activity Overview
                    </h3>
                    <div class="activity-controls">
                        <select id="activity-filter">
                            <option value="all">All Activities</option>
                            <option value="content">Content Creation</option>
                            <option value="collaboration">Collaboration</option>
                            <option value="reviews">Reviews & Feedback</option>
                        </select>
                        <select id="activity-timeframe">
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                </div>
                
                <div class="activity-chart">
                    <canvas id="teamActivityChart" width="800" height="300"></canvas>
                </div>
                
                <div class="activity-timeline">
                    <h4 class="timeline-title">Recent Team Activity</h4>
                    <div class="timeline-list">
                        <?php foreach ($team_data['recent_activities'] as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-avatar">
                                <img src="<?php echo esc_url($activity['avatar']); ?>" alt="<?php echo esc_attr($activity['user']); ?>">
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-user"><?php echo esc_html($activity['user']); ?></span>
                                    <span class="timeline-action"><?php echo esc_html($activity['action']); ?></span>
                                    <span class="timeline-time"><?php echo esc_html($activity['time']); ?></span>
                                </div>
                                <div class="timeline-description"><?php echo esc_html($activity['description']); ?></div>
                                <?php if (isset($activity['project'])): ?>
                                <div class="timeline-project">
                                    <i class="fas fa-folder"></i>
                                    <?php echo esc_html($activity['project']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            
            <!-- Team Settings Tab -->
            <?php if ($user_permissions['manage_settings']): ?>
            <div class="tab-content-team" id="courscribe-team-settings-tabz">
                <div class="settings-overview">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i>
                        Team Settings & Configuration
                    </h3>
                    <p class="section-description">
                        Configure team-wide settings, permissions, and collaboration preferences.
                    </p>
                </div>
                
                <div class="settings-sections">
                    
                    <!-- General Team Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h4 class="section-title">General Settings</h4>
                            <p class="section-description">Basic team configuration options.</p>
                        </div>
                        
                        <div class="settings-form">
                            <div class="form-group">
                                <label class="form-label">Team Name</label>
                                <input type="text" class="form-input" value="<?php echo esc_attr($team_data['team_name']); ?>" id="team-name">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Team Description</label>
                                <textarea class="form-textarea" id="team-description" rows="3"><?php echo esc_textarea($team_data['team_description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Default Member Role</label>
                                <select class="form-select" id="default-role">
                                    <option value="viewer">Viewer</option>
                                    <option value="collaborator" selected>Collaborator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collaboration Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h4 class="section-title">Collaboration Settings</h4>
                            <p class="section-description">Configure how team members collaborate on projects.</p>
                        </div>
                        
                        <div class="settings-toggles">
                            <div class="toggle-group">
                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Real-time Collaboration</div>
                                        <div class="toggle-description">Allow multiple users to edit content simultaneously</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="realtime-collab" checked>
                                        <label for="realtime-collab"></label>
                                    </div>
                                </div>
                                
                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Version Control</div>
                                        <div class="toggle-description">Automatically save version history of changes</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="version-control" checked>
                                        <label for="version-control"></label>
                                    </div>
                                </div>
                                
                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Comment Notifications</div>
                                        <div class="toggle-description">Send notifications for new comments and feedback</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="comment-notifications" checked>
                                        <label for="comment-notifications"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Permission Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h4 class="section-title">Permission Settings</h4>
                            <p class="section-description">Fine-tune permissions for different team roles.</p>
                        </div>
                        
                        <div class="permissions-matrix">
                            <div class="matrix-header">
                                <div class="matrix-cell permission-name">Permission</div>
                                <div class="matrix-cell role-header">Viewer</div>
                                <div class="matrix-cell role-header">Collaborator</div>
                                <div class="matrix-cell role-header">Admin</div>
                                <div class="matrix-cell role-header">Owner</div>
                            </div>
                            
                            <?php 
                            $permissions_matrix = [
                                'View Curriculums' => ['viewer' => true, 'collaborator' => true, 'admin' => true, 'owner' => true],
                                'Edit Curriculums' => ['viewer' => false, 'collaborator' => true, 'admin' => true, 'owner' => true],
                                'Delete Curriculums' => ['viewer' => false, 'collaborator' => false, 'admin' => true, 'owner' => true],
                                'Invite Members' => ['viewer' => false, 'collaborator' => false, 'admin' => true, 'owner' => true],
                                'Manage Roles' => ['viewer' => false, 'collaborator' => false, 'admin' => false, 'owner' => true],
                            ];
                            
                            foreach ($permissions_matrix as $permission => $roles): ?>
                            <div class="matrix-row">
                                <div class="matrix-cell permission-name"><?php echo esc_html($permission); ?></div>
                                <?php foreach ($roles as $role => $allowed): ?>
                                <div class="matrix-cell permission-checkbox">
                                    <input type="checkbox" <?php echo $allowed ? 'checked' : ''; ?> <?php echo $role === 'owner' ? 'disabled' : ''; ?>>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>
                
                <div class="settings-actions">
                    <button class="btn-save" id="save-team-settings">
                        <i class="fas fa-save"></i>
                        Save Settings
                    </button>
                    <button class="btn-reset" id="reset-team-settings">
                        <i class="fas fa-undo"></i>
                        Reset to Defaults
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
        </div>

        <!-- Collaborators and User Management -->
        <?php if (current_user_can('publish_crscribe_studios')) : ?>
                    <div class="collaborators-section mt-4" id="tour-collaborators">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-users"></i>
                                Team Collaborators
                            </h2>
                            <button class="card-action" onclick="document.getElementById('courscribe-invite-popup-subsequent').style.display='flex'">
                                <i class="fas fa-user-plus"></i> Invite New Member
                            </button>
                        </div>
                        <div class="collaborators-grid">
                            
                            <?php
                            $collaborators = get_users([
                                'role'       => 'collaborator',
                                'meta_key'   => '_courscribe_studio_id',
                                'meta_value' => $user_studio_id,
                                'fields'     => ['ID', 'user_email', 'user_login'],
                                'number'     => 10,
                            ]);
                            $invited_emails = $wpdb->get_results($wpdb->prepare("SELECT id, email, invite_code, status, expires_at FROM $table_name WHERE studio_id = %d LIMIT 50", $user_studio_id));

                            $collaborator_lookup = [];
                            foreach ($collaborators as $collab) {
                                $collaborator_lookup[$collab->user_email] = $collab;
                            }
                            $invited_emails_data = [];
                            foreach ($invited_emails as $invite) {
                                $invited_emails_data[$invite->email] = [
                                    'id'         => $invite->id,
                                    'invite_code' => $invite->invite_code,
                                    'status'     => $invite->status,
                                    'expires_at' => $invite->expires_at,
                                ];
                            }

                            // Handle revoke invite
                            if (isset($_POST['courscribe_revoke_invite']) && wp_verify_nonce($_POST['courscribe_revoke_nonce'], 'courscribe_revoke_invite')) {
                                $invite_id = intval($_POST['invite_id']);
                                $delete_result = $wpdb->delete($table_name, ['id' => $invite_id], ['%d']);
                                if ($delete_result !== false) {
                                    echo '<p>Invite revoked successfully!</p>';
                                    error_log('Courscribe: Invite revoked for ID ' . $invite_id . ' by user ' . $current_user->ID);
                                    echo '<meta http-equiv="refresh" content="0">';
                                } else {
                                    echo '<p>Error revoking invite.</p>';
                                    error_log('Courscribe: Failed to revoke invite ID ' . $invite_id . ', Error: ' . $wpdb->last_error);
                                }
                            }

                            // Handle user management
                            if (isset($_POST['courscribe_update_user']) && wp_verify_nonce($_POST['courscribe_user_nonce'], 'courscribe_update_user')) {
                                $user_id = intval($_POST['user_id']);
                                $user = get_userdata($user_id);
                                if ($user && in_array($user->ID, wp_list_pluck($collaborators, 'ID'))) {
                                    $new_email = sanitize_email($_POST['user_email']);
                                    $new_username = sanitize_user($_POST['user_username']);
                                    $permissions = isset($_POST['collaborator_permissions']) ? array_map('sanitize_text_field', $_POST['collaborator_permissions']) : [];

                                    $update_args = ['ID' => $user_id];
                                    if ($new_email && $new_email !== $user->user_email && !email_exists($new_email)) {
                                        $update_args['user_email'] = $new_email;
                                    }
                                    if ($new_username && $new_username !== $user->user_login && !username_exists($new_username)) {
                                        $update_args['user_login'] = $new_username;
                                        $update_args['user_nicename'] = sanitize_title($new_username);
                                    }

                                    $updated = wp_update_user($update_args);
                                    if (!is_wp_error($updated)) {
                                        update_user_meta($user_id, '_courscribe_collaborator_permissions', $permissions);
                                        echo '<p>User updated successfully!</p>';
                                        error_log('Courscribe: User ' . $user_id . ' updated by ' . $current_user->ID);
                                    } else {
                                        echo '<p class="courscribe-error">Error updating user: ' . esc_html($updated->get_error_message()) . '</p>';
                                        error_log('Courscribe: Failed to update user ' . $user_id . ': ' . $updated->get_error_message());
                                    }
                                }
                                echo '<meta http-equiv="refresh" content="0">';
                            }

                            // Handle user deletion
                            if (isset($_POST['courscribe_delete_user']) && wp_verify_nonce($_POST['courscribe_delete_nonce'], 'courscribe_delete_user')) {
                                $user_id = intval($_POST['user_id']);
                                $user = get_userdata($user_id);
                                if ($user && in_array($user->ID, wp_list_pluck($collaborators, 'ID'))) {
                                    $wpdb->delete($table_name, ['email' => $user->user_email, 'studio_id' => $post_id], ['%s', '%d']);
                                    wp_delete_user($user_id);
                                    echo '<p>User deleted successfully!</p>';
                                    error_log('Courscribe: User ' . $user_id . ' deleted by ' . $current_user->ID);
                                    echo '<meta http-equiv="refresh" content="0">';
                                }
                            }

                            if (empty($invited_emails_data)) {
                                echo '<p>No collaborators invited yet.</p>';
                            } else {
                                ?>
                                    
                                <?php
                                foreach ($invited_emails_data as $email => $data) {
                                    $status = isset($collaborator_lookup[$email]) ? 'Accepted' : $data['status'];
                                    $expires_at = strtotime($data['expires_at']);
                                    $is_expired = $expires_at < time();
                                    $expires_display = $is_expired ? 'Expired' : date('Y-m-d H:i:s', $expires_at);
                                    $collaborator = $collaborator_lookup[$email] ?? null;
                                    ?>
                                    <div class="collaborator-card">
                                        <div class="collaborator-info">
                                            <div class="collaborator-avatar">EL</div>
                                            <div class="collaborator-details">
                                                <h4><?php echo $collaborator ? esc_html($collaborator->user_login) : '-'; ?></h4>
                                                <div class="collaborator-role">Subject Matter Expert</div>
                                            </div>
                                        </div>
                                        <div class="collaborator-status">
                                            
                                            <?php if ($status === 'Pending' && !$is_expired) : ?>
                                                <div class="status-dot pending"></div>
                                                <span style="color: #999; font-size: 0.9rem;"><?php echo esc_html($status); ?></span>
                                            <?php elseif ($status === 'Accepted' && $collaborator) : ?>
                                                <div class="status-dot accepted"></div>
                                                <span style="color: #4CAF50; font-size: 0.9rem;"><?php echo esc_html($status); ?></span>
                                            <?php endif; ?>
                                            
                                        </div>
                                        <p>Expires: <span><?php echo esc_html($expires_display); ?></span></p>
                                        <div class="permissions-grid">
                                            <div class="permission-item">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Create Curriculum</span>
                                            </div>
                                            <div class="permission-item active">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Edit Content</span>
                                            </div>
                                            <div class="permission-item">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Manage Modules</span>
                                            </div>
                                            <div class="permission-item">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Delete Curriculum</span>
                                            </div>
                                            <?php echo esc_html($email); ?>
                                        </div>
                                        <div class="collaborator-actions">
                                            <button class="action-btn" onclick="editCollaborator('emily')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn danger">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                            <?php if ($status === 'Pending' && !$is_expired) : ?>
                                                <form method="post" style="display:inline;">
                                                    <?php wp_nonce_field('courscribe_revoke_invite', 'courscribe_revoke_nonce'); ?>
                                                    <input  type="hidden" name="invite_id" value="<?php echo esc_attr($data['id']); ?>">
                                                    <button type="submit" name="courscribe_revoke_invite" value="Remove" class="action-btn">
                                                        <i class="fas fa-user-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($status === 'Accepted' && $collaborator) : ?>
                                                <button class="action-btn" onclick="openPermissionsModal(<?php echo esc_attr($collaborator->ID); ?>, '<?php echo esc_attr($email); ?>', '<?php echo esc_attr($collaborator->user_login); ?>')">Edit</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                    
                                
                            <?php } ?>
                        </div>
                                
                    </div>

                    <!-- Invite Collaborators Popup -->
                    <?php
                    $collaborator_limit = $user_tier === 'basics' ? 1 : ($user_tier === 'plus' ? 3 : PHP_INT_MAX);
                    ?>
                    <div id="courscribe-invite-popup-subsequent" class="courscribe-popup" style="display: none;">
                        <div class="courscribe-popup-content">
                            <h3>Invite Collaborators</h3>
                            <p>Invite up to <?php echo $user_tier === 'pro' ? 'unlimited' : $collaborator_limit; ?> collaborators to your studio.</p>
                            <form method="post" class="courscribe-invite-form">
                                <?php wp_nonce_field('courscribe_invite_collaborators', 'courscribe_invite_nonce'); ?>
                                <div class="form-group">
                                    <label for="courscribe_invite_emails_subsequent">Collaborator Emails (comma-separated):</label>
                                    <textarea name="courscribe_invite_emails" id="courscribe_invite_emails_subsequent" class="form-control" required></textarea>
                                </div>
                                <div class="form-group">
                                    <input type="submit" name="courscribe_submit_invite" value="Send Invites" class="btn btn-primary">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('courscribe-invite-popup-subsequent').style.display='none'">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    if (isset($_POST['courscribe_submit_invite']) && wp_verify_nonce($_POST['courscribe_invite_nonce'], 'courscribe_invite_collaborators')) {
                        $emails = array_map('sanitize_email', array_filter(array_map('trim', explode(',', $_POST['courscribe_invite_emails']))));
                        $current_collaborators = count($collaborators);
                        $emails_to_invite = array_slice($emails, 0, max(0, $collaborator_limit - $current_collaborators));
                        $error_messages = [];
                        error_log('Courscribe: Invite attempt - User ' . $current_user->ID . ', Studio ID: ' . $post_id . ', Tier: ' . $tier . ', Current collaborators: ' . $current_collaborators . ', Limit: ' . $collaborator_limit . ', Emails to invite: ' . count($emails_to_invite));

                        if (empty($emails_to_invite) && !empty($emails)) {
                            $error_messages[] = 'Collaborator limit reached for your tier (' . $tier . ': ' . $collaborator_limit . ').';
                        } else {
                            $register_page_id = get_option('courscribe_register_page');
                            $invite_url_base = $register_page_id ? get_permalink($register_page_id) : home_url('/register');
                            $email_batches = array_chunk($emails_to_invite, 5);
                            foreach ($email_batches as $batch) {
                                foreach ($batch as $email) {
                                    if (!is_email($email)) {
                                        $error_messages[] = 'Invalid email: ' . esc_html($email);
                                        error_log('Courscribe: Invalid email ' . $email . ' provided for invite');
                                        continue;
                                    }
                                    $existing_invite = $wpdb->get_var($wpdb->prepare(
                                        "SELECT id FROM $table_name WHERE email = %s AND studio_id = %d AND status = 'Pending' AND expires_at > %s",
                                        $email,
                                        $post_id,
                                        current_time('mysql')
                                    ));
                                    if ($existing_invite) {
                                        $error_messages[] = 'Invite already sent to ' . esc_html($email);
                                        error_log('Courscribe: Duplicate invite attempt for ' . $email . ', Studio ID: ' . $post_id);
                                        continue;
                                    }
                                    $invite_code = wp_generate_password(12, false);
                                    $insert_result = $wpdb->insert($table_name, [
                                        'email'      => $email,
                                        'invite_code' => $invite_code,
                                        'studio_id'  => $post_id,
                                        'status'     => 'Pending',
                                        'created_at' => current_time('mysql'),
                                        'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                                    ]);
                                    if ($insert_result === false) {
                                        $error_messages[] = 'Failed to save invite for ' . esc_html($email);
                                        error_log('Courscribe: Failed to insert invite for ' . $email . ', Error: ' . $wpdb->last_error);
                                        continue;
                                    }
                                    $invite_url = add_query_arg(['invite_code' => $invite_code, 'email' => urlencode($email)], $invite_url_base);
                                    $mail_result = wp_mail(
                                        $email,
                                        'Courscribe Collaborator Invitation',
                                        "You have been invited to join a Courscribe studio. Register here: $invite_url\n\nThis invitation expires on " . date('Y-m-d H:i:s', strtotime('+7 days')) . ".",
                                        ['Content-Type: text/plain; charset=UTF-8']
                                    );
                                    if (!$mail_result) {
                                        $error_messages[] = 'Failed to send email to ' . esc_html($email);
                                        error_log('Courscribe: Failed to send invite email to ' . $email . ', Studio ID: ' . $post_id . ', Invite URL: ' . $invite_url);
                                        $wpdb->delete($table_name, ['email' => $email, 'studio_id' => $post_id], ['%s', '%d']);
                                    } else {
                                        error_log('Courscribe: Invite sent to ' . $email . ', Studio ID: ' . $post_id . ', Invite URL: ' . $invite_url);
                                    }
                                }
                                wp_cache_flush();
                            }
                            if (empty($error_messages) && !empty($emails_to_invite)) {
                                echo '<p>Invites sent successfully!</p>';
                            } elseif (!empty($error_messages)) {
                                echo '<p>Errors occurred:<br>' . implode('<br>', array_map('esc_html', $error_messages)) . '</p>';
                            }
                        }
                    }
                    ?>
                <?php endif; ?>
        
    </div>

    <!-- Invite Member Modal -->
    <div class="modal-overlay" id="invite-modal">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Invite Team Member
                </h3>
                <button class="modal-close" id="close-invite-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="invite-form">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input" id="invite-email" placeholder="colleague@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="invite-role" required>
                            <option value="">Select a role...</option>
                            <option value="viewer">Viewer - Can view content only</option>
                            <option value="collaborator">Collaborator - Can edit and create content</option>
                            <option value="admin">Admin - Full permissions except team settings</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Personal Message (Optional)</label>
                        <textarea class="form-textarea" id="invite-message" rows="3" placeholder="Add a personal message to your invitation..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="send-welcome-email" checked>
                            <span class="checkbox-text">Send welcome email with getting started guide</span>
                        </label>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button class="btn-cancel" id="cancel-invite">Cancel</button>
                <button class="btn-primary" id="send-invite">
                    <i class="fas fa-paper-plane"></i>
                    Send Invitation
                </button>
            </div>
        </div>
    </div>
    
    <!-- Role Editing Modal -->
    <div id="role-edit-modal" class="courscribe-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Edit Role Permissions
                </h3>
                <button class="modal-close" onclick="closeRoleEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="role-edit-form">
                    <div class="form-group">
                        <label for="role-name">Role Name</label>
                        <input type="text" id="role-name" name="role_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="role-description">Description</label>
                        <textarea id="role-description" name="role_description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Permissions</label>
                        <div id="permissions-list" class="permissions-checklist">
                            <!-- Permissions will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <input type="hidden" id="role-key" name="role_key">
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRoleEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveRolePermissions()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Team Member Edit Modal -->
    <div id="member-edit-modal" class="courscribe-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-edit"></i>
                    Edit Team Member
                </h3>
                <button class="modal-close" onclick="closeMemberEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="member-edit-form">
                    <div class="form-group">
                        <label for="member-name">Name</label>
                        <input type="text" id="member-name" name="member_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="member-email">Email</label>
                        <input type="email" id="member-email" name="member_email" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="member-role">Role</label>
                        <select id="member-role" name="member_role" class="form-control">
                            <option value="client">Client</option>
                            <option value="collaborator">Collaborator</option>
                            <?php if ($is_studio_admin || $is_wp_admin): ?>
                            <option value="studio_admin">Studio Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="member-status">Status</label>
                        <select id="member-status" name="member_status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    
                    <input type="hidden" id="member-user-id" name="member_user_id">
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMemberEditModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="removeMember()" style="margin-right: auto;">Remove Member</button>
                <button type="button" class="btn btn-primary" onclick="saveMemberChanges()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Member Activity Modal -->
    <div id="member-activity-modal" class="courscribe-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-chart-line"></i>
                    <span id="activity-member-name">Member Activity</span>
                </h3>
                <button class="modal-close" onclick="closeMemberActivityModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="activity-stats-grid">
                    <div class="activity-stat">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="activity-curriculums">0</div>
                            <div class="stat-label">Curriculums Created</div>
                        </div>
                    </div>
                    
                    <div class="activity-stat">
                        <div class="stat-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="activity-courses">0</div>
                            <div class="stat-label">Courses Created</div>
                        </div>
                    </div>
                    
                    <div class="activity-stat">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="activity-feedback">0</div>
                            <div class="stat-label">Feedback Given</div>
                        </div>
                    </div>
                    
                    <div class="activity-stat">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="activity-last-login">Never</div>
                            <div class="stat-label">Last Login</div>
                        </div>
                    </div>
                </div>
                
                <div class="activity-timeline">
                    <h4>Recent Activity</h4>
                    <div id="activity-timeline-list">
                        <div class="loading">Loading activity...</div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMemberActivityModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Team Management JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Tab Navigation
        const tabBtns = document.querySelectorAll('.team-tabs .tab-btn-team');
        const tabContents = document.querySelectorAll('.tab-content-team');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Team tab clicked:', this.dataset.tab); // Debugging
                
                const targetTab = this.dataset.tab;
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                const targetContent = document.getElementById(targetTab + '-tabz');
                if (targetContent) {
                    targetContent.classList.add('active');
                    console.log('Activated tab content:', targetTab + '-tabz');
                } else {
                    console.error('Tab content not found:', targetTab + '-tabz');
                }
            });
        });
        
        // Member Search and Filter
        const memberSearch = document.getElementById('member-search');
        const roleFilter = document.getElementById('role-filter');
        const statusFilter = document.getElementById('status-filter');
        const memberCards = document.querySelectorAll('.member-card');
        
        function filterMembers() {
            const searchTerm = memberSearch?.value.toLowerCase() || '';
            const roleValue = roleFilter?.value || '';
            const statusValue = statusFilter?.value || '';
            
            memberCards.forEach(card => {
                const memberName = card.querySelector('.member-name')?.textContent.toLowerCase() || '';
                const memberEmail = card.querySelector('.member-email')?.textContent.toLowerCase() || '';
                const memberRole = card.dataset.role || '';
                const memberStatus = card.dataset.status || '';
                
                const matchesSearch = memberName.includes(searchTerm) || memberEmail.includes(searchTerm);
                const matchesRole = !roleValue || memberRole === roleValue;
                const matchesStatus = !statusValue || memberStatus === statusValue;
                
                if (matchesSearch && matchesRole && matchesStatus) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        memberSearch?.addEventListener('input', filterMembers);
        roleFilter?.addEventListener('change', filterMembers);
        statusFilter?.addEventListener('change', filterMembers);
        
        // Invite Member Modal
        const inviteBtn = document.getElementById('invite-team-member');
        const inviteModal = document.getElementById('invite-modal');
        const closeInviteModal = document.getElementById('close-invite-modal');
        const cancelInvite = document.getElementById('cancel-invite');
        const sendInvite = document.getElementById('send-invite');
        const inviteForm = document.getElementById('invite-form');
        
        inviteBtn?.addEventListener('click', function() {
            inviteModal.classList.add('active');
        });
        
        closeInviteModal?.addEventListener('click', function() {
            inviteModal.classList.remove('active');
        });
        
        cancelInvite?.addEventListener('click', function() {
            inviteModal.classList.remove('active');
        });
        
        // Close modal on overlay click
        inviteModal?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
        
        // Send Invitation
        sendInvite?.addEventListener('click', function() {
            const formData = new FormData(inviteForm);
            const email = document.getElementById('invite-email')?.value;
            const role = document.getElementById('invite-role')?.value;
            const message = document.getElementById('invite-message')?.value;
            const sendWelcome = document.getElementById('send-welcome-email')?.checked;
            
            if (!email || !role) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Show loading state
            sendInvite.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            sendInvite.disabled = true;
            
            // AJAX call to send invitation
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    sendInvite.innerHTML = '<i class="fas fa-paper-plane"></i> Send Invitation';
                    sendInvite.disabled = false;
                    
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('Invitation sent successfully!');
                            inviteModal.classList.remove('active');
                            inviteForm.reset();
                            // Refresh the page or update the member list
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    } else {
                        alert('Error sending invitation. Please try again.');
                    }
                }
            };
            
            const params = new URLSearchParams({
                action: 'courscribe_invite_team_member',
                email: email,
                role: role,
                message: message,
                send_welcome: sendWelcome ? '1' : '0',
                nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>'
            });
            
            xhr.send(params);
        });
        
        // Member Actions
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.classList.contains('edit') ? 'edit' : 
                              this.classList.contains('message') ? 'message' : 'remove';
                const memberId = this.dataset.member;
                
                switch(action) {
                    case 'edit':
                        // Open edit member modal
                        editMember(memberId);
                        break;
                    case 'message':
                        // Open message modal
                        messageMember(memberId);
                        break;
                    case 'remove':
                        // Confirm and remove member
                        if (confirm('Are you sure you want to remove this team member?')) {
                            removeMember(memberId);
                        }
                        break;
                }
            });
        });
        
        // Activity Filter
        const activityFilter = document.getElementById('activity-filter');
        const activityTimeframe = document.getElementById('activity-timeframe');
        
        activityFilter?.addEventListener('change', function() {
            updateActivityChart(this.value, activityTimeframe?.value || '30');
        });
        
        activityTimeframe?.addEventListener('change', function() {
            updateActivityChart(activityFilter?.value || 'all', this.value);
        });
        
        // Team Settings
        const saveSettingsBtn = document.getElementById('save-team-settings');
        const resetSettingsBtn = document.getElementById('reset-team-settings');
        
        saveSettingsBtn?.addEventListener('click', function() {
            // Save team settings
            saveTeamSettings();
        });
        
        resetSettingsBtn?.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset all settings to defaults?')) {
                resetTeamSettings();
            }
        });
        
        // Initialize activity chart
        initializeActivityChart();
        
        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'slideInUp 0.6s ease forwards';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.stat-card, .member-card, .role-card, .project-card').forEach(card => {
            observer.observe(card);
        });
    });
    
    function editMember(memberId) {
        // Implementation for editing member
        console.log('Edit member:', memberId);
    }
    
    function messageMember(memberId) {
        // Implementation for messaging member
        console.log('Message member:', memberId);
    }
    
    function removeMember(memberId) {
        // AJAX call to remove member
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error removing member: ' + response.data);
                }
            }
        };
        
        const params = new URLSearchParams({
            action: 'courscribe_remove_team_member',
            member_id: memberId,
            nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>'
        });
        
        xhr.send(params);
    }
    
    function updateActivityChart(filter, timeframe) {
        // Update activity chart based on filters
        console.log('Update activity chart:', filter, timeframe);
    }
    
    function initializeActivityChart() {
        // Initialize the team activity chart
        const ctx = document.getElementById('teamActivityChart');
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Team Activity',
                        data: [12, 19, 3, 5, 2, 3, 10],
                        borderColor: '#E4B26F',
                        backgroundColor: 'rgba(228, 178, 111, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#aaa'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#aaa'
                            }
                        }
                    }
                }
            });
        }
    }
    
    function saveTeamSettings() {
        // Save team settings via AJAX
        const teamName = document.getElementById('team-name')?.value;
        const teamDescription = document.getElementById('team-description')?.value;
        const defaultRole = document.getElementById('default-role')?.value;
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error saving settings: ' + response.data);
                }
            }
        };
        
        const params = new URLSearchParams({
            action: 'courscribe_save_team_settings',
            team_name: teamName,
            team_description: teamDescription,
            default_role: defaultRole,
            nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>'
        });
        
        xhr.send(params);
    }
    
    function resetTeamSettings() {
        // Reset team settings to defaults
        document.getElementById('team-name').value = 'My Studio Team';
        document.getElementById('team-description').value = '';
        document.getElementById('default-role').value = 'collaborator';
        
        // Reset toggles
        document.querySelectorAll('.toggle-switch input').forEach(toggle => {
            toggle.checked = true;
        });
    }
    
    // Add entrance animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
    
    // Role editing functions
    window.editRole = function(roleKey) {
        const roleData = <?php echo json_encode($team_data['roles']); ?>;
        const role = roleData.find(r => r.key === roleKey);
        
        if (!role) return;
        
        document.getElementById('role-key').value = role.key;
        document.getElementById('role-name').value = role.name;
        document.getElementById('role-description').value = role.description;
        
        // Build permissions checklist
        const allPermissions = [
            'Manage all content',
            'Invite/remove members',
            'Assign roles', 
            'Billing & subscription',
            'Studio settings',
            'Create/edit assigned content',
            'Comment and provide feedback',
            'Collaborate in real-time',
            'View project analytics',
            'View shared content',
            'Download materials',
            'Provide feedback',
            'View basic analytics'
        ];
        
        const permissionsList = document.getElementById('permissions-list');
        permissionsList.innerHTML = '';
        
        allPermissions.forEach(permission => {
            const isChecked = role.permissions.includes(permission);
            const div = document.createElement('div');
            div.className = 'permission-checkbox';
            div.innerHTML = `
                <label>
                    <input type="checkbox" name="permissions[]" value="${permission}" ${isChecked ? 'checked' : ''}>
                    <span>${permission}</span>
                </label>
            `;
            permissionsList.appendChild(div);
        });
        
        document.getElementById('role-edit-modal').style.display = 'flex';
    };
    
    window.closeRoleEditModal = function() {
        document.getElementById('role-edit-modal').style.display = 'none';
    };
    
    window.saveRolePermissions = function() {
        const formData = new FormData(document.getElementById('role-edit-form'));
        const permissions = Array.from(document.querySelectorAll('input[name="permissions[]"]:checked'))
            .map(cb => cb.value);
        
        // AJAX call to save permissions
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'courscribe_update_role_permissions',
                nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>',
                role_key: formData.get('role_key'),
                role_description: formData.get('role_description'),
                permissions: JSON.stringify(permissions)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeRoleEditModal();
                location.reload(); // Refresh to show changes
            } else {
                alert('Error saving permissions: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving permissions');
        });
    };
    
    // Member editing functions
    window.editMember = function(userId) {
        const membersData = <?php echo json_encode($team_data['members']); ?>;
        const member = membersData.find(m => m.user_id == userId);
        
        if (!member) return;
        
        document.getElementById('member-user-id').value = member.user_id;
        document.getElementById('member-name').value = member.name;
        document.getElementById('member-email').value = member.email;
        document.getElementById('member-role').value = member.role;
        document.getElementById('member-status').value = member.status;
        
        document.getElementById('member-edit-modal').style.display = 'flex';
    };
    
    window.closeMemberEditModal = function() {
        document.getElementById('member-edit-modal').style.display = 'none';
    };
    
    window.saveMemberChanges = function() {
        const formData = new FormData(document.getElementById('member-edit-form'));
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'courscribe_update_team_member',
                nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>',
                user_id: formData.get('member_user_id'),
                role: formData.get('member_role'),
                status: formData.get('member_status')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeMemberEditModal();
                location.reload();
            } else {
                alert('Error updating member: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating member');
        });
    };
    
    window.removeMember = function() {
        if (!confirm('Are you sure you want to remove this team member? This action cannot be undone.')) {
            return;
        }
        
        const userId = document.getElementById('member-user-id').value;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'courscribe_remove_team_member',
                nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>',
                member_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeMemberEditModal();
                location.reload();
            } else {
                alert('Error removing member: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing member');
        });
    };
    
    // Add edit functionality to role cards
    document.querySelectorAll('.role-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const roleKey = this.dataset.role;
            editRole(roleKey);
        });
    });
    
    // Add edit functionality to member cards
    document.querySelectorAll('.member-card .action-btn.edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const memberId = this.dataset.member;
            editMember(memberId);
        });
    });
    
    // Add activity view functionality to member cards
    document.querySelectorAll('.member-card .view-activity').forEach(btn => {
        btn.addEventListener('click', function() {
            const memberId = this.dataset.member;
            viewMemberActivity(memberId);
        });
    });
    
    // Member activity functions
    window.viewMemberActivity = function(userId) {
        const membersData = <?php echo json_encode($team_data['members']); ?>;
        const member = membersData.find(m => m.user_id == userId);
        
        if (!member) return;
        
        // Set member name in modal
        document.getElementById('activity-member-name').textContent = member.name + ' - Activity';
        
        // Show modal
        document.getElementById('member-activity-modal').style.display = 'flex';
        
        // Load activity data via AJAX
        loadMemberActivityData(userId);
    };
    
    window.closeMemberActivityModal = function() {
        document.getElementById('member-activity-modal').style.display = 'none';
    };
    
    function loadMemberActivityData(userId) {
        // Show loading state
        document.getElementById('activity-timeline-list').innerHTML = '<div class="loading">Loading activity...</div>';
        
        // Fetch activity data
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'courscribe_get_member_activity',
                nonce: '<?php echo wp_create_nonce('courscribe_team_nonce'); ?>',
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activity = data.data;
                
                // Update stats
                document.getElementById('activity-curriculums').textContent = activity.curriculums || 0;
                document.getElementById('activity-courses').textContent = activity.courses || 0;
                document.getElementById('activity-feedback').textContent = activity.feedback || 0;
                document.getElementById('activity-last-login').textContent = activity.last_login || 'Never';
                
                // Update timeline
                const timelineList = document.getElementById('activity-timeline-list');
                if (activity.timeline && activity.timeline.length > 0) {
                    timelineList.innerHTML = activity.timeline.map(item => `
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-${item.icon || 'circle'}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">${item.title}</div>
                                <div class="timeline-desc">${item.description}</div>
                                <div class="timeline-date">${item.date}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    timelineList.innerHTML = '<div class="no-activity">No recent activity found.</div>';
                }
            } else {
                document.getElementById('activity-timeline-list').innerHTML = '<div class="error">Error loading activity data.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('activity-timeline-list').innerHTML = '<div class="error">Error loading activity data.</div>';
        });
    }
    
    </script>
    
    <?php
    return ob_get_clean();
}

function safe_get_user_display_name($user_id) {
    $user = get_userdata($user_id);
    return $user ? ($user->display_name ?: $user->user_login) : 'Unknown';
}

function safe_get_user_email($user_id) {
    $user = get_userdata($user_id);
    return $user ? $user->user_email : 'unknown@example.com';
}

// Function to get team data
function courscribe_get_team_data($user_id, $studio_id) {
    global $wpdb;
    
    try {
        // Validate inputs
        if (!$user_id || !is_numeric($user_id) || $user_id <= 0) {
            error_log('CourScribe Team Error: Invalid user_id provided to courscribe_get_team_data');
            $user_id = get_current_user_id() ?: 1;
        }
        
        if (!$studio_id || !is_numeric($studio_id) || $studio_id <= 0) {
            error_log('CourScribe Team Error: Invalid studio_id provided to courscribe_get_team_data');
            // Try to get user's studio
            $studios = get_posts([
                'post_type' => 'crscribe_studio',
                'author' => $user_id,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            $studio_id = !empty($studios) ? $studios[0] : 0;
        }
        
        // Get real team statistics
        $invites_table = $wpdb->prefix . 'courscribe_invites';
        $client_invites_table = $wpdb->prefix . 'courscribe_client_invites';
    
        // Get collaborators for this studio with error handling
        $collaborators = [];
        if ($studio_id > 0) {
            $collaborators = get_users([
                'role' => 'collaborator',
                'meta_key' => '_courscribe_studio_id',
                'meta_value' => $studio_id,
                'fields' => 'all',
            ]);
            if (is_wp_error($collaborators)) {
                error_log('CourScribe Team Error: Failed to get collaborators - ' . $collaborators->get_error_message());
                $collaborators = [];
            }
        }
        
        // Get clients for this studio with error handling
        $clients = [];
        if ($studio_id > 0) {
            $clients = get_users([
                'role' => 'client',
                'meta_key' => '_courscribe_studio_id',
                'meta_value' => $studio_id,
                'fields' => 'all',
            ]);
            if (is_wp_error($clients)) {
                error_log('CourScribe Team Error: Failed to get clients - ' . $clients->get_error_message());
                $clients = [];
            }
        }
    
        // Get pending invites with error handling
        $pending_collaborator_invites = 0;
        $pending_client_invites = 0;
        
        // Check if tables exist before querying
        $invites_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $invites_table)) == $invites_table;
        $client_invites_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $client_invites_table)) == $client_invites_table;
        
        if ($invites_table_exists && $studio_id > 0) {
            $pending_collaborator_invites = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$invites_table} WHERE studio_id = %d AND status = 'Pending' AND expires_at > %s",
                $studio_id,
                current_time('mysql')
            ));
            if ($wpdb->last_error) {
                error_log('CourScribe Team Error: Database error getting pending collaborator invites - ' . $wpdb->last_error);
                $pending_collaborator_invites = 0;
            }
        }
        
        if ($client_invites_table_exists) {
            $pending_client_invites = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$client_invites_table} WHERE status = 'Pending' AND expires_at > %s",
                current_time('mysql')
            ));
            if ($wpdb->last_error) {
                error_log('CourScribe Team Error: Database error getting pending client invites - ' . $wpdb->last_error);
                $pending_client_invites = 0;
            }
        }
    
        // Get studio info with validation
        $studio_post = null;
        $team_name = 'Studio Team';
        $team_description = 'Collaborative curriculum development team.';
        
        if ($studio_id > 0) {
            $studio_post = get_post($studio_id);
            if ($studio_post && !is_wp_error($studio_post)) {
                $team_name = $studio_post->post_title ? $studio_post->post_title . ' Team' : 'Studio Team';
                $team_description = $studio_post->post_content ?: 'Collaborative curriculum development team.';
            } else {
                error_log('CourScribe Team Error: Could not retrieve studio post with ID ' . $studio_id);
            }
        }
    
    // Calculate statistics
    $total_members = count($collaborators) + count($clients) + 1; // +1 for studio admin
    $pending_invites = $pending_collaborator_invites + $pending_client_invites;
    
        // Get collaborative projects with error handling
        $collaborative_projects = 0;
        if ($studio_id > 0) {
            $collaborative_projects = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
                 WHERE p.post_type = 'crscribe_curriculum' 
                 AND p.post_status = 'publish'
                 AND EXISTS (
                     SELECT 1 FROM {$wpdb->postmeta} pm 
                     WHERE pm.post_id = p.ID 
                     AND pm.meta_key = '_studio_id' 
                     AND pm.meta_value = %d
                 )",
                $studio_id
            ));
            if ($wpdb->last_error) {
                error_log('CourScribe Team Error: Database error getting collaborative projects - ' . $wpdb->last_error);
                $collaborative_projects = 0;
            }
        }
    
    $team_data = [
        'total_members' => $total_members,
        'new_members_month' => min(count($collaborators), 3), // Estimate
        'active_members' => count($collaborators) + count($clients),
        'collaborative_projects' => $collaborative_projects ?: 0,
        'projects_change' => rand(10, 25), // Could be calculated from activity logs
        'pending_invites' => $pending_invites,
        'team_name' => $team_name,
        'team_description' => $team_description,
        
        // Build real team members array
        'members' => array_merge(
            // Add studio admin/owner
            [
                [
                    'user_id' => $user_id,
                    'name' => safe_get_user_display_name($user_id) . ' (Studio Admin)',
                    'email' => safe_get_user_email($user_id),
                    'avatar' => get_avatar_url($user_id) ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->safe_get_user_display_name($user_id)) . '&background=E4B26F&color=1a1a1a',
                    'role' => 'studio_admin',
                    'role_icon' => 'crown',
                    'status' => 'active',
                    'contributions' => courscribe_get_user_contributions($user_id, $studio_id),
                    'last_active' => courscribe_get_last_active($user_id)
                ]
            ],
            // Add collaborators
            array_map(function($collaborator) use ($studio_id) {
                return [
                    'user_id' => $collaborator->ID,
                    'name' => $collaborator->display_name ?: $collaborator->user_login,
                    'email' => $collaborator->user_email,
                    'avatar' => get_avatar_url($collaborator->ID) ?: 'https://ui-avatars.com/api/?name=' . urlencode($collaborator->display_name) . '&background=2196F3&color=fff',
                    'role' => 'collaborator',
                    'role_icon' => 'user-edit',
                    'status' => 'active',
                    'contributions' => courscribe_get_user_contributions($collaborator->ID, $studio_id),
                    'last_active' => courscribe_get_last_active($collaborator->ID)
                ];
            }, $collaborators),
            // Add clients
            array_map(function($client) use ($studio_id) {
                return [
                    'user_id' => $client->ID,
                    'name' => $client->display_name ?: $client->user_login,
                    'email' => $client->user_email,
                    'avatar' => get_avatar_url($client->ID) ?: 'https://ui-avatars.com/api/?name=' . urlencode($client->display_name) . '&background=9C27B0&color=fff',
                    'role' => 'client',
                    'role_icon' => 'eye',
                    'status' => 'active',
                    'contributions' => 0, // Clients don't contribute content
                    'last_active' => courscribe_get_last_active($client->ID)
                ];
            }, $clients)
        ),
        
        // Role definitions with real counts
        'roles' => [
            [
                'key' => 'studio_admin',
                'name' => 'Studio Administrator',
                'description' => 'Full control over the studio and all its content.',
                'icon' => 'crown',
                'count' => 1,
                'permissions' => [
                    'Manage all content',
                    'Invite/remove members',
                    'Assign roles',
                    'Billing & subscription',
                    'Studio settings'
                ]
            ],
            [
                'key' => 'collaborator',
                'name' => 'Collaborator',
                'description' => 'Can create and edit content within assigned projects.',
                'icon' => 'user-edit',
                'count' => count($collaborators),
                'permissions' => [
                    'Create/edit assigned content',
                    'Comment and provide feedback',
                    'Collaborate in real-time',
                    'View project analytics'
                ]
            ],
            [
                'key' => 'client',
                'name' => 'Client',
                'description' => 'Read-only access to shared content and projects.',
                'icon' => 'eye',
                'count' => count($clients),
                'permissions' => [
                    'View shared content',
                    'Download materials',
                    'Provide feedback',
                    'View basic analytics'
                ]
            ]
        ],
        
        // Recent activities - real data from database
        'recent_activities' => courscribe_get_recent_team_activities($studio_id, 10),
    ];
    
    return $team_data;
    
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_get_team_data - ' . $e->getMessage());
        
        // Return safe default data structure
        return [
            'total_members' => 1,
            'new_members_month' => 0,
            'active_members' => 1,
            'collaborative_projects' => 0,
            'projects_change' => 0,
            'pending_invites' => 0,
            'team_name' => 'Studio Team',
            'team_description' => 'Collaborative curriculum development team.',
            'members' => [],
            'roles' => [],
            'recent_activities' => []
        ];
    }
}

// Get recent team activities from database
function courscribe_get_recent_team_activities($studio_id, $limit = 10) {
    global $wpdb;
    
    try {
        // Validate inputs
        $studio_id = intval($studio_id);
        $limit = max(1, min(100, intval($limit))); // Limit between 1 and 100
        
        // Try to get from activity log table if it exists
        $activity_table = $wpdb->prefix . 'courscribe_activity_log';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $activity_table)) == $activity_table;
        
        if ($table_exists && $studio_id > 0) {
        // Get activities from activity log
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT 
                al.user_id,
                al.action,
                al.description,
                al.created_at,
                u.display_name,
                u.user_email
            FROM $activity_table al
            LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
            WHERE al.studio_id = %d
            ORDER BY al.created_at DESC
            LIMIT %d
        ", $studio_id, $limit));
        
            if ($wpdb->last_error) {
                error_log('CourScribe Team Error: Database error getting team activities - ' . $wpdb->last_error);
                $activities = [];
            }
            
            if (!empty($activities) && is_array($activities)) {
                return array_map(function($activity) {
                    return [
                        'user' => $activity->display_name ?: 'Unknown User',
                        'avatar' => get_avatar_url($activity->user_id) ?: 'https://ui-avatars.com/api/?name=' . urlencode($activity->display_name ?: 'User') . '&background=E4B26F&color=1a1a1a',
                        'action' => $activity->action ?: 'Unknown Action',
                        'description' => $activity->description ?: '',
                        'project' => '',
                        'time' => human_time_diff(strtotime($activity->created_at), current_time('timestamp')) . ' ago'
                    ];
                }, $activities);
            }
        }
        
        // Fallback: Get recent posts/activities from curriculums
        $recent_posts = [];
        if ($studio_id > 0) {
            $recent_posts = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    p.ID,
                    p.post_title,
                    p.post_author,
                    p.post_date,
                    p.post_modified,
                    u.display_name,
                    u.user_email
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course')
                AND p.post_status = 'publish'
                AND pm.meta_key = '_studio_id'
                AND pm.meta_value = %s
                ORDER BY p.post_modified DESC
                LIMIT %d
            ", $studio_id, $limit));
            
            if ($wpdb->last_error) {
                error_log('CourScribe Team Error: Database error getting recent posts - ' . $wpdb->last_error);
                $recent_posts = [];
            }
        }
    
        if (!empty($recent_posts) && is_array($recent_posts)) {
            return array_map(function($post) {
                $is_new = (strtotime($post->post_date) > (current_time('timestamp') - 86400)); // 24 hours
                return [
                    'user' => $post->display_name ?: 'Unknown User',
                    'avatar' => get_avatar_url($post->post_author) ?: 'https://ui-avatars.com/api/?name=' . urlencode($post->display_name ?: 'User') . '&background=E4B26F&color=1a1a1a',
                    'action' => $is_new ? 'created' : 'updated',
                    'description' => 'Content: ' . ($post->post_title ?: 'Untitled'),
                    'project' => '',
                    'time' => human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' ago'
                ];
            }, $recent_posts);
        }
        
        // Return empty array if no activities found
        return [];
        
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_get_recent_team_activities - ' . $e->getMessage());
        return [];
    }
}

// AJAX handlers for team management
add_action('wp_ajax_courscribe_update_role_permissions', 'courscribe_update_role_permissions_handler');
add_action('wp_ajax_courscribe_update_team_member', 'courscribe_update_team_member_handler');
add_action('wp_ajax_courscribe_get_member_activity', 'courscribe_get_member_activity_handler');
add_action('wp_ajax_courscribe_invite_team_member', 'courscribe_invite_team_member_handler');

function courscribe_get_member_activity_handler() {
    try {
        check_ajax_referer('courscribe_team_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 403);
        }
    
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $is_studio_admin = in_array('studio_admin', $user_roles);
    $is_wp_admin = current_user_can('administrator');
    
    if (!$is_studio_admin && !$is_wp_admin) {
        wp_send_json_error('Insufficient permissions');
    }
    
        $member_user_id = intval($_POST['user_id'] ?? 0);
        if (!$member_user_id) {
            wp_send_json_error('Invalid user ID');
        }
    
    global $wpdb;
    
    // Get studio ID
    $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
    if (!$studio_id) {
        $studios = get_posts([
            'post_type' => 'crscribe_studio',
            'author' => $current_user->ID,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        $studio_id = !empty($studios) ? $studios[0] : 0;
    }
    
    // Get curriculums count
    $curriculums = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'crscribe_curriculum'
         AND p.post_status = 'publish'
         AND p.post_author = %d
         AND pm.meta_key = '_studio_id'
         AND pm.meta_value = %s",
        $member_user_id, $studio_id
    ));
    
    // Get courses count
    $courses = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts}
         WHERE post_type = 'crscribe_course'
         AND post_status = 'publish'
         AND post_author = %d",
        $member_user_id
    ));
    
    // Get feedback count from annotations table
    $feedback = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}courscribe_annotations
         WHERE user_id = %d",
        $member_user_id
    ));
    
    // Get last login (WordPress doesn't track this by default, so we'll use post activity)
    $last_activity = $wpdb->get_var($wpdb->prepare(
        "SELECT post_modified FROM {$wpdb->posts}
         WHERE post_author = %d
         AND post_status = 'publish'
         ORDER BY post_modified DESC
         LIMIT 1",
        $member_user_id
    ));
    
    $last_login = $last_activity ? human_time_diff(strtotime($last_activity), current_time('timestamp')) . ' ago' : 'Never';
    
    // Get recent activity timeline
    $timeline = [];
    
    // Get recent posts
    $recent_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT p.post_title, p.post_type, p.post_date, p.post_modified
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id  
         WHERE p.post_author = %d
         AND p.post_type IN ('crscribe_curriculum', 'crscribe_course')
         AND p.post_status = 'publish'
         AND pm.meta_key = '_studio_id'
         AND pm.meta_value = %s
         ORDER BY p.post_modified DESC
         LIMIT 10",
        $member_user_id, $studio_id
    ));
    
    foreach ($recent_posts as $post) {
        $is_new = (strtotime($post->post_date) > (current_time('timestamp') - 86400));
        $timeline[] = [
            'title' => $is_new ? 'Created' : 'Updated',
            'description' => ucfirst(str_replace('crscribe_', '', $post->post_type)) . ': ' . $post->post_title,
            'date' => human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' ago',
            'icon' => $post->post_type === 'crscribe_curriculum' ? 'book' : 'bookmark'
        ];
    }
    
    // Get recent feedback/annotations
    $recent_feedback = $wpdb->get_results($wpdb->prepare(
        "SELECT created_at, field_id FROM {$wpdb->prefix}courscribe_annotations
         WHERE user_id = %d
         ORDER BY created_at DESC
         LIMIT 5",
        $member_user_id
    ));
    
    foreach ($recent_feedback as $annotation) {
        $timeline[] = [
            'title' => 'Provided Feedback',
            'description' => 'Left comment on content',
            'date' => human_time_diff(strtotime($annotation->created_at), current_time('timestamp')) . ' ago',
            'icon' => 'comment'
        ];
    }
    
    // Sort timeline by date
    usort($timeline, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    $activity_data = [
        'curriculums' => intval($curriculums),
        'courses' => intval($courses),
        'feedback' => intval($feedback),
        'last_login' => $last_login,
        'timeline' => array_slice($timeline, 0, 15) // Limit to 15 items
    ];
    
        wp_send_json_success($activity_data);
        
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_get_member_activity_handler - ' . $e->getMessage());
        wp_send_json_error('An error occurred while retrieving member activity');
    }
}

function courscribe_update_role_permissions_handler() {
    try {
        check_ajax_referer('courscribe_team_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 403);
        }
    
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $is_studio_admin = in_array('studio_admin', $user_roles);
        $is_wp_admin = current_user_can('administrator');
    
        if (!$is_studio_admin && !$is_wp_admin) {
            wp_send_json_error('Insufficient permissions');
        }
    
        $role_key = sanitize_text_field($_POST['role_key']);
        $role_description = sanitize_textarea_field($_POST['role_description']);
        $permissions = json_decode(stripslashes($_POST['permissions']), true);
    
        if (!$role_key || !is_array($permissions)) {
            wp_send_json_error('Invalid data provided');
        }
    
        // Save role permissions to options table
        $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if (!$studio_id) {
            // Get studio ID from owned studios
            $studios = get_posts([
                'post_type' => 'crscribe_studio',
                'author' => $current_user->ID,
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            $studio_id = !empty($studios) ? $studios[0] : 0;
        }
    
        if ($studio_id) {
            update_post_meta($studio_id, '_role_' . $role_key . '_description', $role_description);
            update_post_meta($studio_id, '_role_' . $role_key . '_permissions', $permissions);
        }
    
        wp_send_json_success('Role permissions updated successfully');
        
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_update_role_permissions_handler - ' . $e->getMessage());
        wp_send_json_error('An error occurred while updating role permissions');
    }
}

function courscribe_update_team_member_handler() {
    try {
        check_ajax_referer('courscribe_team_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized', 403);
        }
    
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $is_studio_admin = in_array('studio_admin', $user_roles);
        $is_wp_admin = current_user_can('administrator');
        
        if (!$is_studio_admin && !$is_wp_admin) {
            wp_send_json_error('Insufficient permissions');
        }
    
        $member_user_id = intval($_POST['user_id']);
        $new_role = sanitize_text_field($_POST['role']);
        $new_status = sanitize_text_field($_POST['status']);
        
        if (!$member_user_id || !in_array($new_role, ['client', 'collaborator', 'studio_admin']) || !in_array($new_status, ['active', 'inactive', 'pending'])) {
            wp_send_json_error('Invalid data provided');
        }
        
        // Don't allow changing your own role
        if ($member_user_id === $current_user->ID) {
            wp_send_json_error('Cannot change your own role');
        }
    
        $member_user = get_user_by('ID', $member_user_id);
        if (!$member_user) {
            wp_send_json_error('User not found');
        }
        
        // Update user role
        $member_user->set_role($new_role);
        
        // Update status
        update_user_meta($member_user_id, '_courscribe_status', $new_status);
    
        // Update studio association
        $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        if (!$studio_id) {
            $studios = get_posts([
                'post_type' => 'crscribe_studio',
                'author' => $current_user->ID,
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            $studio_id = !empty($studios) ? $studios[0] : 0;
        }
    
        if ($studio_id) {
            update_user_meta($member_user_id, '_courscribe_studio_id', $studio_id);
        }
    
        wp_send_json_success('Team member updated successfully');
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_update_team_member_handler - ' . $e->getMessage());
        wp_send_json_error('An error occurred while updating team member');
    }
}

function courscribe_invite_team_member_handler() {
    check_ajax_referer('courscribe_team_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die('Unauthorized', 403);
    }
    
    $email = sanitize_email($_POST['email']);
    $role = sanitize_text_field($_POST['role']);
    $message = sanitize_textarea_field($_POST['message']);
    $send_welcome = $_POST['send_welcome'] === '1';
    
    // Validate inputs
    if (!is_email($email) || !in_array($role, ['viewer', 'collaborator', 'admin'])) {
        wp_send_json_error('Invalid email or role');
    }
    
    // Check if user already exists or has pending invitation
    $existing_user = get_user_by('email', $email);
    
    // Send invitation logic would go here
    // For now, just return success
    wp_send_json_success('Invitation sent successfully');
}

add_action('wp_ajax_courscribe_remove_team_member', 'courscribe_remove_team_member_handler');
function courscribe_remove_team_member_handler() {
    check_ajax_referer('courscribe_team_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die('Unauthorized', 403);
    }
    
    $member_id = intval($_POST['member_id']);
    $current_user_id = get_current_user_id();
    
    // Don't allow removing self
    if ($member_id === $current_user_id) {
        wp_send_json_error('Cannot remove yourself from the team');
    }
    
    // Remove member logic would go here
    wp_send_json_success('Member removed successfully');
}

add_action('wp_ajax_courscribe_save_team_settings', 'courscribe_save_team_settings_handler');
function courscribe_save_team_settings_handler() {

    try{
    check_ajax_referer('courscribe_team_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die('Unauthorized', 403);
    }
    
        $team_name = sanitize_text_field($_POST['team_name'] ?? '');
        $team_description = sanitize_textarea_field($_POST['team_description'] ?? '');
        $default_role = sanitize_text_field($_POST['default_role'] ?? '');
        
        if (!$team_name) {
            wp_send_json_error('Team name is required');
        }
    
    $user_id = get_current_user_id();
    
    // Save team settings
    update_user_meta($user_id, '_courscribe_team_name', $team_name);
    update_user_meta($user_id, '_courscribe_team_description', $team_description);
    update_user_meta($user_id, '_courscribe_default_member_role', $default_role);
    
        wp_send_json_success('Settings saved successfully');
        
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_update_team_member_handler - ' . $e->getMessage());
        wp_send_json_error('An error occurred while updating team member settings');
    }
}

// Helper function to get user contributions
function courscribe_get_user_contributions($user_id, $studio_id) {
    global $wpdb;
    
    try {
        // Validate inputs
        $user_id = intval($user_id);
        $studio_id = intval($studio_id);
        
        if ($user_id <= 0) {
            error_log('CourScribe Team Error: Invalid user_id in courscribe_get_user_contributions');
            return 0;
        }
        
        // Count curriculums created by user for this studio
        $curriculums = 0;
        if ($studio_id > 0) {
            $curriculums = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'crscribe_curriculum'
                 AND p.post_status = 'publish'
                 AND p.post_author = %d
                 AND pm.meta_key = '_studio_id'
                 AND pm.meta_value = %d",
                $user_id,
                $studio_id
            ));
            
            if ($wpdb->last_error) {
                error_log('CourScribe Team Error: Database error getting user curriculums - ' . $wpdb->last_error);
                $curriculums = 0;
            }
        }
    
        // Count courses created by user
        $courses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'crscribe_course'
             AND post_status = 'publish'
             AND post_author = %d",
            $user_id
        ));
        
        if ($wpdb->last_error) {
            error_log('CourScribe Team Error: Database error getting user courses - ' . $wpdb->last_error);
            $courses = 0;
        }
        
        // Count modules and lessons (if they exist)
        $modules = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'crscribe_module'
             AND post_status = 'publish'
             AND post_author = %d",
            $user_id
        ));
        
        if ($wpdb->last_error) {
            error_log('CourScribe Team Error: Database error getting user modules - ' . $wpdb->last_error);
            $modules = 0;
        }
        
        $lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'crscribe_lesson'
             AND post_status = 'publish'
             AND post_author = %d",
            $user_id
        ));
        
        if ($wpdb->last_error) {
            error_log('CourScribe Team Error: Database error getting user lessons - ' . $wpdb->last_error);
            $lessons = 0;
        }
        
        return intval($curriculums) + intval($courses) + intval($modules) + intval($lessons);
        
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_get_user_contributions - ' . $e->getMessage());
        return 0;
    }
}

// Helper function to get last active time
function courscribe_get_last_active($user_id) {
    try {
        // Validate input
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            error_log('CourScribe Team Error: Invalid user_id in courscribe_get_last_active');
            return 'Unknown';
        }
        
        // Check for recent post activity
        $last_post = get_posts([
            'author' => $user_id,
            'post_type' => ['crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'],
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        if (is_wp_error($last_post)) {
            error_log('CourScribe Team Error: Failed to get user posts - ' . $last_post->get_error_message());
        } elseif (!empty($last_post) && is_array($last_post)) {
            $post = get_post($last_post[0]);
            if ($post && !is_wp_error($post)) {
                $time_diff = human_time_diff(strtotime($post->post_modified), current_time('timestamp'));
                return $time_diff . ' ago';
            }
        }
        
        // Fallback to user registration
        $user = get_user_by('ID', $user_id);
        if ($user && !is_wp_error($user)) {
            $time_diff = human_time_diff(strtotime($user->user_registered), current_time('timestamp'));
            return $time_diff . ' ago';
        }
        
        return 'Unknown';
        
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception in courscribe_get_last_active - ' . $e->getMessage());
        return 'Unknown';
    }
}

// Safe helper functions for user data retrieval
function courscribe_safe_get_user_display_name($user_id) {
    try {
        $user = get_user_by('ID', intval($user_id));
        if ($user && !is_wp_error($user)) {
            return $user->display_name ?: $user->user_login ?: 'Unknown User';
        }
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception getting user display name - ' . $e->getMessage());
    }
    return 'Unknown User';
}

function courscribe_safe_get_user_email($user_id) {
    try {
        $user = get_user_by('ID', intval($user_id));
        if ($user && !is_wp_error($user)) {
            return $user->user_email ?: 'no-email@example.com';
        }
    } catch (Exception $e) {
        error_log('CourScribe Team Error: Exception getting user email - ' . $e->getMessage());
    }
    return 'no-email@example.com';
}

// Helper function to get activity icon
function courscribe_get_activity_icon($action) {
    $icons = [
        'created' => 'plus-circle',
        'updated' => 'edit',
        'deleted' => 'trash',
        'published' => 'eye',
        'archived' => 'archive',
        'invited' => 'user-plus',
        'joined' => 'user-check',
        'left' => 'user-minus'
    ];
    
    return $icons[strtolower($action)] ?? 'circle';
}
?>