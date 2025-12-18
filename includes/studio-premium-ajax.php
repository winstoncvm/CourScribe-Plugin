<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent class redeclaration
if (class_exists('Courscribe_Studio_Premium_Ajax')) {
    return;
}

/**
 * Premium Studio AJAX Handlers
 * Handles all AJAX requests for the premium studio interface
 */
class Courscribe_Studio_Premium_Ajax {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    public function init_hooks() {
        // Studio statistics
        add_action('wp_ajax_courscribe_get_studio_stats', array($this, 'get_studio_stats'));
        
        // Recent activity
        add_action('wp_ajax_courscribe_get_recent_activity', array($this, 'get_recent_activity'));
        
        // Curriculums management
        add_action('wp_ajax_courscribe_get_curriculums', array($this, 'get_curriculums'));
        add_action('wp_ajax_courscribe_create_curriculum', array($this, 'create_curriculum'));
        add_action('wp_ajax_courscribe_edit_curriculum', array($this, 'edit_curriculum'));
        add_action('wp_ajax_courscribe_delete_curriculum', array($this, 'delete_curriculum'));
        
        // Team management
        add_action('wp_ajax_courscribe_get_team_members', array($this, 'get_team_members'));
        add_action('wp_ajax_courscribe_send_invitation', array($this, 'send_invitation'));
        add_action('wp_ajax_courscribe_remove_member', array($this, 'remove_member'));
        add_action('wp_ajax_courscribe_edit_member_permissions', array($this, 'edit_member_permissions'));
        
        // Studio settings
        add_action('wp_ajax_courscribe_save_studio_info', array($this, 'save_studio_info'));
        add_action('wp_ajax_courscribe_get_studio_settings', array($this, 'get_studio_settings'));
        
        // Analytics
        add_action('wp_ajax_courscribe_get_analytics_data', array($this, 'get_analytics_data'));
        add_action('wp_ajax_courscribe_get_content_chart_data', array($this, 'get_content_chart_data'));
        add_action('wp_ajax_courscribe_get_activity_chart_data', array($this, 'get_activity_chart_data'));
        
        // Progress tracking
        add_action('wp_ajax_courscribe_update_progress', array($this, 'update_progress'));
        add_action('wp_ajax_courscribe_get_progress_data', array($this, 'get_progress_data'));
        
        // Drag and drop curriculum reordering
        add_action('wp_ajax_courscribe_update_curriculum_order', array($this, 'update_curriculum_order'));
        
        // Archived curriculums
        add_action('wp_ajax_courscribe_get_archived_curriculums', array($this, 'get_archived_curriculums'));
        add_action('wp_ajax_courscribe_restore_curriculum', array($this, 'restore_curriculum'));
        add_action('wp_ajax_courscribe_delete_curriculum_permanently', array($this, 'delete_curriculum_permanently'));
        add_action('wp_ajax_courscribe_archive_curriculum', array($this, 'archive_curriculum'));
        
        // Activity logging
        add_action('wp_ajax_courscribe_log_activity', array($this, 'log_activity'));
    }
    
    /**
     * Get studio statistics
     */
    public function get_studio_stats() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $current_user = wp_get_current_user();
        $studio_id = $this->get_current_studio_id();
        
        if (!$studio_id) {
            wp_send_json_error('Studio not found');
        }
        
        try {
            $stats = array(
                'total_curriculums' => $this->get_curriculum_count($studio_id),
                'total_courses' => $this->get_course_count($studio_id),
                'total_modules' => $this->get_module_count($studio_id),
                'total_lessons' => $this->get_lesson_count($studio_id),
                'total_collaborators' => $this->get_collaborator_count($studio_id),
                'active_users' => $this->get_active_users_count($studio_id),
                'completion_rate' => $this->get_completion_rate($studio_id),
                'monthly_growth' => $this->get_monthly_growth($studio_id)
            );
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            error_log('CourScribe Studio Stats Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load statistics');
        }
    }
    
    /**
     * Get recent activity
     */
    public function get_recent_activity() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        
        // Get parameters for filtering and pagination
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 5);
        $offset = ($page - 1) * $per_page;
        
        try {
            global $wpdb;
            
            // First try to get from activity log table if it exists
            $activity_table = $wpdb->prefix . 'courscribe_activity_log';
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $activity_table)) == $activity_table;
            
            if ($table_exists) {
                // Use activity log table
                $where_conditions = array("studio_id = %d");
                $where_values = array($studio_id);
                
                if ($filter !== 'all') {
                    $where_conditions[] = "action LIKE %s";
                    $where_values[] = '%' . $filter . '%';
                }
                
                $where_clause = implode(' AND ', $where_conditions);
                
                // Get total count
                $total_query = $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$activity_table} WHERE {$where_clause}",
                    $where_values
                );
                $total_items = $wpdb->get_var($total_query);
                $total_pages = ceil($total_items / $per_page);
                
                // Get activities
                $activities_query = $wpdb->prepare(
                    "SELECT al.*, u.display_name as user_name 
                     FROM {$activity_table} al 
                     LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
                     WHERE {$where_clause} 
                     ORDER BY al.timestamp DESC 
                     LIMIT %d OFFSET %d",
                    array_merge($where_values, array($per_page, $offset))
                );
                
                $activities = $wpdb->get_results($activities_query, ARRAY_A);
                
                // Format activities from log
                $formatted_activities = array();
                foreach ($activities as $activity) {
                    $formatted_activities[] = array(
                        'type' => $this->get_log_activity_type($activity['action']),
                        'user_name' => $activity['user_name'] ?: 'Unknown User',
                        'description' => $activity['description'],
                        'timestamp' => $activity['timestamp'],
                        'action' => $activity['action'],
                        'metadata' => json_decode($activity['metadata'], true)
                    );
                }
            } else {
                // Fallback to posts table
                $post_type_filter = '';
                if ($filter !== 'all') {
                    $post_type_filter = $wpdb->prepare(" AND p.post_type = %s", 'crscribe_' . $filter);
                }
                
                // Get total count
                $total_query = $wpdb->prepare("
                    SELECT COUNT(DISTINCT p.ID)
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson')
                    AND p.post_status = 'publish'
                    AND (pm.meta_key = '_studio_id' AND pm.meta_value = %s)
                    {$post_type_filter}
                ", $studio_id);
                
                $total_items = $wpdb->get_var($total_query);
                $total_pages = ceil($total_items / $per_page);
                
                // Get recent posts/updates related to the studio
                $activities = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        p.ID,
                        p.post_title,
                        p.post_type,
                        p.post_modified,
                        p.post_author,
                        u.display_name as user_name,
                        u.user_email
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson')
                    AND p.post_status = 'publish'
                    AND (pm.meta_key = '_studio_id' AND pm.meta_value = %s)
                    {$post_type_filter}
                    ORDER BY p.post_modified DESC
                    LIMIT %d OFFSET %d
                ", $studio_id, $per_page, $offset));
                
                $formatted_activities = array();
                foreach ($activities as $activity) {
                    $formatted_activities[] = array(
                        'id' => $activity->ID,
                        'type' => $this->get_activity_type($activity->post_type),
                        'description' => $this->get_activity_description($activity),
                        'user_name' => $activity->user_name,
                        'user_email' => $activity->user_email,
                        'timestamp' => $activity->post_modified,
                        'post_type' => $activity->post_type,
                        'post_title' => $activity->post_title
                    );
                }
            }
            
            wp_send_json_success(array(
                'activities' => $formatted_activities,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_items' => $total_items,
                    'per_page' => $per_page
                )
            ));
            
        } catch (Exception $e) {
            error_log('CourScribe Recent Activity Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load recent activity');
        }
    }
    
    /**
     * Get activity type for log entries
     */
    private function get_log_activity_type($action) {
        $action_types = array(
            'curriculum_created' => 'create',
            'curriculum_updated' => 'edit',
            'curriculum_archived' => 'archive',
            'curriculum_restored' => 'undo',
            'curriculum_deleted_permanently' => 'delete',
            'curriculum_reorder' => 'sort',
            'course_created' => 'create',
            'course_updated' => 'edit',
            'module_created' => 'create',
            'module_updated' => 'edit',
            'lesson_created' => 'create',
            'lesson_updated' => 'edit',
            'collaborator_invited' => 'users',
            'collaborator_removed' => 'user-times'
        );
        
        return $action_types[$action] ?? 'circle';
    }
    
    /**
     * Get curriculums for the studio
     */
    public function get_curriculums() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        
        try {
            $curriculums = get_posts(array(
                'post_type' => 'crscribe_curriculum',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_studio_id',
                        'value' => $studio_id,
                        'compare' => '='
                    )
                ),
                'orderby' => 'post_modified',
                'order' => 'DESC'
            ));
            
            $formatted_curriculums = array();
            foreach ($curriculums as $curriculum) {
                $formatted_curriculums[] = array(
                    'id' => $curriculum->ID,
                    'title' => $curriculum->post_title,
                    'description' => $curriculum->post_excerpt,
                    'status' => $this->get_curriculum_status($curriculum->ID),
                    'progress' => $this->calculate_curriculum_progress($curriculum->ID),
                    'course_count' => $this->get_curriculum_course_count($curriculum->ID),
                    'module_count' => $this->get_curriculum_module_count($curriculum->ID),
                    'lesson_count' => $this->get_curriculum_lesson_count($curriculum->ID),
                    'created_date' => $curriculum->post_date,
                    'modified_date' => $curriculum->post_modified,
                    'author' => get_userdata($curriculum->post_author)->display_name,
                    'permalink' => get_permalink($curriculum->ID)
                );
            }
            
            wp_send_json_success($formatted_curriculums);
            
        } catch (Exception $e) {
            error_log('CourScribe Get Curriculums Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load curriculums');
        }
    }
    
    /**
     * Get team members
     */
    public function get_team_members() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        
        try {
            // Get collaborators
            $collaborators = get_users(array(
                'role' => 'collaborator',
                'meta_key' => '_courscribe_studio_id',
                'meta_value' => $studio_id,
                'fields' => array('ID', 'user_email', 'user_login', 'display_name', 'user_registered')
            ));
            
            // Get studio admin
            $studio_post = get_post($studio_id);
            $studio_admin = get_userdata($studio_post->post_author);
            
            $formatted_members = array();
            
            // Add studio admin
            if ($studio_admin) {
                $formatted_members[] = array(
                    'id' => $studio_admin->ID,
                    'name' => $studio_admin->display_name,
                    'email' => $studio_admin->user_email,
                    'role' => 'Studio Admin',
                    'status' => 'active',
                    'joined_date' => $studio_admin->user_registered,
                    'avatar' => get_avatar_url($studio_admin->ID),
                    'permissions' => array('all'),
                    'last_active' => $this->get_user_last_active($studio_admin->ID)
                );
            }
            
            // Add collaborators
            foreach ($collaborators as $collaborator) {
                $formatted_members[] = array(
                    'id' => $collaborator->ID,
                    'name' => $collaborator->display_name,
                    'email' => $collaborator->user_email,
                    'role' => 'Collaborator',
                    'status' => $this->get_user_status($collaborator->ID),
                    'joined_date' => $collaborator->user_registered,
                    'avatar' => get_avatar_url($collaborator->ID),
                    'permissions' => get_user_meta($collaborator->ID, '_courscribe_collaborator_permissions', true) ?: array(),
                    'last_active' => $this->get_user_last_active($collaborator->ID)
                );
            }
            
            wp_send_json_success($formatted_members);
            
        } catch (Exception $e) {
            error_log('CourScribe Get Team Members Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load team members');
        }
    }
    
    /**
     * Send invitation to collaborator
     */
    public function send_invitation() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $email = sanitize_email($_POST['invite_email'] ?? '');
        $role = sanitize_text_field($_POST['invite_role'] ?? 'collaborator');
        $message = sanitize_textarea_field($_POST['invite_message'] ?? '');
        
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }
        
        $studio_id = $this->get_current_studio_id();
        $current_user = wp_get_current_user();
        
        try {
            // Check if user already exists
            $existing_user = get_user_by('email', $email);
            if ($existing_user) {
                wp_send_json_error('User already exists in the system');
            }
            
            // Check invitation limits based on tier
            $tier = get_option('courscribe_tier', 'basics');
            $current_collaborators = $this->get_collaborator_count($studio_id);
            $max_collaborators = $this->get_tier_collaborator_limit($tier);
            
            if ($current_collaborators >= $max_collaborators && $max_collaborators !== -1) {
                wp_send_json_error('Collaborator limit reached for your tier');
            }
            
            // Generate invitation
            $invite_code = wp_generate_password(32, false);
            $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            // Store invitation in database
            global $wpdb;
            $table_name = $wpdb->prefix . 'courscribe_invitations';
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'email' => $email,
                    'invite_code' => $invite_code,
                    'studio_id' => $studio_id,
                    'invited_by' => $current_user->ID,
                    'role' => $role,
                    'message' => $message,
                    'status' => 'pending',
                    'created_at' => current_time('mysql'),
                    'expires_at' => $expires_at
                ),
                array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                wp_send_json_error('Failed to create invitation');
            }
            
            // Send invitation email
            $this->send_invitation_email($email, $invite_code, $message, $current_user, $studio_id);
            
            wp_send_json_success(array(
                'message' => 'Invitation sent successfully',
                'email' => $email,
                'expires_at' => $expires_at
            ));
            
        } catch (Exception $e) {
            error_log('CourScribe Send Invitation Error: ' . $e->getMessage());
            wp_send_json_error('Failed to send invitation');
        }
    }
    
    /**
     * Save studio information
     */
    public function save_studio_info() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        
        if (!current_user_can('edit_crscribe_studio', $studio_id)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $studio_name = sanitize_text_field($_POST['studio_name'] ?? '');
            $studio_description = wp_kses_post($_POST['studio_description'] ?? '');
            $studio_email = sanitize_email($_POST['studio_email'] ?? '');
            $studio_website = esc_url_raw($_POST['studio_website'] ?? '');
            $studio_public = isset($_POST['studio_public']) ? 1 : 0;
            
            // Update post
            $post_data = array(
                'ID' => $studio_id,
                'post_title' => $studio_name,
                'post_content' => $studio_description
            );
            
            $result = wp_update_post($post_data, true);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            // Update meta fields
            update_post_meta($studio_id, '_studio_email', $studio_email);
            update_post_meta($studio_id, '_studio_website', $studio_website);
            update_post_meta($studio_id, '_studio_is_public', $studio_public ? 'Yes' : 'No');
            update_post_meta($studio_id, '_studio_updated_at', current_time('mysql'));
            
            // Log the change
            $this->log_studio_activity($studio_id, 'studio_updated', 'Studio information updated');
            
            wp_send_json_success(array(
                'message' => 'Studio information saved successfully',
                'studio_id' => $studio_id
            ));
            
        } catch (Exception $e) {
            error_log('CourScribe Save Studio Info Error: ' . $e->getMessage());
            wp_send_json_error('Failed to save studio information');
        }
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics_data() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        $period = sanitize_text_field($_POST['period'] ?? '30');
        
        try {
            $analytics = array(
                'content_growth' => $this->get_content_growth_data($studio_id, $period),
                'team_activity' => $this->get_team_activity_data($studio_id, $period),
                'completion_rates' => $this->get_completion_rates_data($studio_id, $period),
                'popular_content' => $this->get_popular_content_data($studio_id),
                'engagement_metrics' => $this->get_engagement_metrics($studio_id, $period)
            );
            
            wp_send_json_success($analytics);
            
        } catch (Exception $e) {
            error_log('CourScribe Analytics Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load analytics data');
        }
    }
    
    /**
     * Get content chart data
     */
    public function get_content_chart_data() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        
        try {
            global $wpdb;
            
            // Get last 30 days of content creation
            $data = array();
            $labels = array();
            
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime('-' . $i . ' days'));
                $labels[] = date('M j', strtotime($date));
                
                // Count posts created on this date for this studio
                $count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(DISTINCT p.ID)
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson')
                    AND p.post_status = 'publish'
                    AND DATE(p.post_date) = %s
                    AND (pm.meta_key = '_studio_id' AND pm.meta_value = %s)
                ", $date, $studio_id));
                
                $data[] = intval($count);
            }
            
            wp_send_json_success(array(
                'labels' => $labels,
                'data' => $data
            ));
            
        } catch (Exception $e) {
            error_log('CourScribe Content Chart Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load content chart data');
        }
    }
    
    /**
     * Get activity chart data
     */
    public function get_activity_chart_data() {
        if (!$this->verify_nonce() || !$this->check_permissions()) {
            wp_send_json_error('Unauthorized access');
        }
        
        $studio_id = $this->get_current_studio_id();
        
        try {
            global $wpdb;
            
            // Get last 7 days of team activity
            $data = array();
            $labels = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
            
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime('-' . $i . ' days'));
                $day_of_week = date('N', strtotime($date)) - 1; // 0 = Monday
                
                // Count activity (posts + logins) on this date
                $post_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(DISTINCT p.ID)
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson')
                    AND p.post_status = 'publish'
                    AND DATE(p.post_modified) = %s
                    AND (pm.meta_key = '_studio_id' AND pm.meta_value = %s)
                ", $date, $studio_id));
                
                // Add login activity if available
                $login_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(DISTINCT u.ID)
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
                    WHERE um.meta_key = '_courscribe_studio_id'
                    AND um.meta_value = %s
                    AND um2.meta_key = '_courscribe_last_login'
                    AND DATE(um2.meta_value) = %s
                ", $studio_id, $date));
                
                $data[$day_of_week] = intval($post_count) + intval($login_count);
            }
            
            // Ensure we have data for all 7 days
            for ($i = 0; $i < 7; $i++) {
                if (!isset($data[$i])) {
                    $data[$i] = 0;
                }
            }
            
            // Sort by day of week
            ksort($data);
            
            wp_send_json_success(array(
                'labels' => $labels,
                'data' => array_values($data)
            ));
            
        } catch (Exception $e) {
            error_log('CourScribe Activity Chart Error: ' . $e->getMessage());
            wp_send_json_error('Failed to load activity chart data');
        }
    }
    
    // Helper Methods
    
    private function verify_nonce() {
        return wp_verify_nonce($_POST['nonce'] ?? '', 'courscribe_ajax_nonce');
    }
    
    private function check_permissions() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        return in_array('studio_admin', $user_roles) || 
               in_array('collaborator', $user_roles) || 
               current_user_can('manage_options');
    }
    
    private function get_current_studio_id() {
        // Try to get from POST data first
        if (isset($_POST['studio_id'])) {
            return intval($_POST['studio_id']);
        }
        
        // Try to get from current post
        global $post;
        if ($post && $post->post_type === 'crscribe_studio') {
            return $post->ID;
        }
        
        // Try to get from user meta (for collaborators)
        $current_user = wp_get_current_user();
        $studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
        
        if ($studio_id) {
            return intval($studio_id);
        }
        
        return null;
    }
    
    private function get_curriculum_count($studio_id) {
        $query = new WP_Query(array(
            'post_type' => 'crscribe_curriculum',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_studio_id',
                    'value' => $studio_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return $query->found_posts;
    }
    
    private function get_course_count($studio_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT c.ID)
            FROM {$wpdb->posts} c
            JOIN {$wpdb->posts} curr ON c.post_parent = curr.ID
            JOIN {$wpdb->postmeta} pm ON curr.ID = pm.post_id
            WHERE c.post_type = 'crscribe_course'
            AND c.post_status = 'publish'
            AND curr.post_type = 'crscribe_curriculum'
            AND pm.meta_key = '_studio_id'
            AND pm.meta_value = %s
        ", $studio_id));
    }
    
    private function get_module_count($studio_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT m.ID)
            FROM {$wpdb->posts} m
            JOIN {$wpdb->posts} c ON m.post_parent = c.ID
            JOIN {$wpdb->posts} curr ON c.post_parent = curr.ID
            JOIN {$wpdb->postmeta} pm ON curr.ID = pm.post_id
            WHERE m.post_type = 'crscribe_module'
            AND m.post_status = 'publish'
            AND c.post_type = 'crscribe_course'
            AND curr.post_type = 'crscribe_curriculum'
            AND pm.meta_key = '_studio_id'
            AND pm.meta_value = %s
        ", $studio_id));
    }
    
    private function get_lesson_count($studio_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT l.ID)
            FROM {$wpdb->posts} l
            JOIN {$wpdb->posts} m ON l.post_parent = m.ID
            JOIN {$wpdb->posts} c ON m.post_parent = c.ID
            JOIN {$wpdb->posts} curr ON c.post_parent = curr.ID
            JOIN {$wpdb->postmeta} pm ON curr.ID = pm.post_id
            WHERE l.post_type = 'crscribe_lesson'
            AND l.post_status = 'publish'
            AND m.post_type = 'crscribe_module'
            AND c.post_type = 'crscribe_course'
            AND curr.post_type = 'crscribe_curriculum'
            AND pm.meta_key = '_studio_id'
            AND pm.meta_value = %s
        ", $studio_id));
    }
    
    private function get_collaborator_count($studio_id) {
        $users = get_users(array(
            'role' => 'collaborator',
            'meta_key' => '_courscribe_studio_id',
            'meta_value' => $studio_id,
            'fields' => 'ID'
        ));
        
        return count($users);
    }
    
    private function get_active_users_count($studio_id) {
        $users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => '_courscribe_studio_id',
                    'value' => $studio_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_courscribe_last_login',
                    'value' => date('Y-m-d', strtotime('-7 days')),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'fields' => 'ID'
        ));
        
        return count($users);
    }
    
    private function get_completion_rate($studio_id) {
        // Calculate average completion rate across all curriculums
        $curriculums = get_posts(array(
            'post_type' => 'crscribe_curriculum',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_studio_id',
                    'value' => $studio_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        if (empty($curriculums)) {
            return 0;
        }
        
        $total_completion = 0;
        foreach ($curriculums as $curriculum_id) {
            $total_completion += $this->calculate_curriculum_progress($curriculum_id);
        }
        
        return round($total_completion / count($curriculums));
    }
    
    private function get_monthly_growth($studio_id) {
        global $wpdb;
        
        $current_month = date('Y-m');
        $last_month = date('Y-m', strtotime('-1 month'));
        
        $current_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson')
            AND p.post_status = 'publish'
            AND pm.meta_key = '_studio_id'
            AND pm.meta_value = %s
            AND DATE_FORMAT(p.post_date, '%%Y-%%m') = %s
        ", $studio_id, $current_month));
        
        $last_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson')
            AND p.post_status = 'publish'
            AND pm.meta_key = '_studio_id'
            AND pm.meta_value = %s
            AND DATE_FORMAT(p.post_date, '%%Y-%%m') = %s
        ", $studio_id, $last_month));
        
        if ($last_count == 0) {
            return $current_count > 0 ? 100 : 0;
        }
        
        return round((($current_count - $last_count) / $last_count) * 100);
    }
    
    private function calculate_curriculum_progress($curriculum_id) {
        // Simple progress calculation based on published content
        $courses = get_posts(array(
            'post_type' => 'crscribe_course',
            'post_parent' => $curriculum_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        if (empty($courses)) {
            return 0;
        }
        
        $total_modules = 0;
        $completed_modules = 0;
        
        foreach ($courses as $course_id) {
            $modules = get_posts(array(
                'post_type' => 'crscribe_module',
                'post_parent' => $course_id,
                'post_status' => array('publish', 'draft'),
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            
            $total_modules += count($modules);
            
            foreach ($modules as $module_id) {
                if (get_post_status($module_id) === 'publish') {
                    $completed_modules++;
                }
            }
        }
        
        if ($total_modules == 0) {
            return 100; // Consider empty curriculum as complete
        }
        
        return round(($completed_modules / $total_modules) * 100);
    }
    
    private function get_activity_type($post_type) {
        $types = array(
            'crscribe_curriculum' => 'create',
            'crscribe_course' => 'create',
            'crscribe_module' => 'edit',
            'crscribe_lesson' => 'edit'
        );
        
        return $types[$post_type] ?? 'create';
    }
    
    private function get_activity_description($activity) {
        $actions = array(
            'crscribe_curriculum' => 'created curriculum',
            'crscribe_course' => 'created course',
            'crscribe_module' => 'updated module',
            'crscribe_lesson' => 'updated lesson'
        );
        
        $action = $actions[$activity->post_type] ?? 'updated';
        return sprintf('%s <em>"%s"</em>', $action, $activity->post_title);
    }
    
    private function get_tier_collaborator_limit($tier) {
        $limits = array(
            'basics' => 1,
            'plus' => 3,
            'pro' => -1 // unlimited
        );
        
        return $limits[$tier] ?? 1;
    }
    
    private function send_invitation_email($email, $invite_code, $message, $sender, $studio_id) {
        $studio = get_post($studio_id);
        $site_name = get_bloginfo('name');
        $register_url = home_url('/register/?invite_code=' . $invite_code);
        
        $subject = sprintf('[%s] Invitation to join %s studio', $site_name, $studio->post_title);
        
        $email_content = sprintf(
            "Hello!\n\n" .
            "%s has invited you to join the \"%s\" studio on %s.\n\n" .
            "%s\n\n" .
            "To accept this invitation, please click the link below:\n" .
            "%s\n\n" .
            "This invitation will expire in 7 days.\n\n" .
            "Best regards,\n" .
            "The %s Team",
            $sender->display_name,
            $studio->post_title,
            $site_name,
            $message ? "Personal message: " . $message : "",
            $register_url,
            $site_name
        );
        
        return wp_mail($email, $subject, $email_content);
    }
    
    private function log_studio_activity($studio_id, $action, $description) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'courscribe_studio_log';
        $current_user = wp_get_current_user();
        
        $wpdb->insert(
            $table_name,
            array(
                'studio_id' => $studio_id,
                'user_id' => $current_user->ID,
                'action' => $action,
                'description' => $description,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    private function get_user_last_active($user_id) {
        $last_login = get_user_meta($user_id, '_courscribe_last_login', true);
        return $last_login ?: 'Never';
    }
    
    private function get_user_status($user_id) {
        $last_login = get_user_meta($user_id, '_courscribe_last_login', true);
        
        if (!$last_login) {
            return 'inactive';
        }
        
        $days_since_login = (time() - strtotime($last_login)) / (60 * 60 * 24);
        
        if ($days_since_login <= 7) {
            return 'active';
        } elseif ($days_since_login <= 30) {
            return 'away';
        } else {
            return 'inactive';
        }
    }
    
    private function get_curriculum_status($curriculum_id) {
        $progress = $this->calculate_curriculum_progress($curriculum_id);
        
        if ($progress >= 100) {
            return 'completed';
        } elseif ($progress > 0) {
            return 'in-progress';
        } else {
            return 'not-started';
        }
    }
    
    private function get_curriculum_course_count($curriculum_id) {
        return wp_count_posts('crscribe_course')->publish ?? 0;
    }
    
    private function get_curriculum_module_count($curriculum_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(m.ID)
            FROM {$wpdb->posts} m
            JOIN {$wpdb->posts} c ON m.post_parent = c.ID
            WHERE m.post_type = 'crscribe_module'
            AND m.post_status = 'publish'
            AND c.post_parent = %d
        ", $curriculum_id));
    }
    
    private function get_curriculum_lesson_count($curriculum_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(l.ID)
            FROM {$wpdb->posts} l
            JOIN {$wpdb->posts} m ON l.post_parent = m.ID
            JOIN {$wpdb->posts} c ON m.post_parent = c.ID
            WHERE l.post_type = 'crscribe_lesson'
            AND l.post_status = 'publish'
            AND c.post_parent = %d
        ", $curriculum_id));
    }
    
    /**
     * Update curriculum order via drag and drop
     */
    public function update_curriculum_order() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_studio_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $curriculum_ids = isset($_POST['curriculum_ids']) ? array_map('intval', $_POST['curriculum_ids']) : array();
        
        if (empty($curriculum_ids)) {
            wp_send_json_error(array('message' => 'No curriculum IDs provided'));
        }
        
        global $wpdb;
        
        // Update menu order for each curriculum
        $success = true;
        foreach ($curriculum_ids as $index => $curriculum_id) {
            $result = wp_update_post(array(
                'ID' => $curriculum_id,
                'menu_order' => $index + 1
            ));
            
            if (is_wp_error($result)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            // Log activity
            $this->log_activity_internal('curriculum_reorder', 'Curriculums reordered via drag and drop', array(
                'curriculum_ids' => $curriculum_ids,
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_success(array('message' => 'Curriculum order updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update curriculum order'));
        }
    }
    
    /**
     * Get archived curriculums
     */
    public function get_archived_curriculums() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_studio_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('read')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        // Get current studio ID
        $studio_id = $this->get_current_studio_id();
        if (!$studio_id) {
            wp_send_json_error(array('message' => 'Studio not found'));
        }
        
        // Get archived curriculums for this studio
        $archived_curriculums = get_posts(array(
            'post_type' => 'crscribe_curriculum',
            'post_status' => 'trash',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_studio_id',
                    'value' => $studio_id,
                    'compare' => '='
                )
            )
        ));
        
        $curriculum_data = array();
        foreach ($archived_curriculums as $curriculum) {
            $course_count = $this->get_course_count_for_curriculum($curriculum->ID);
            
            $curriculum_data[] = array(
                'id' => $curriculum->ID,
                'title' => $curriculum->post_title,
                'archived_date' => get_post_meta($curriculum->ID, '_archived_date', true) ?: $curriculum->post_modified,
                'course_count' => $course_count,
                'status' => 'archived'
            );
        }
        
        wp_send_json_success($curriculum_data);
    }
    
    /**
     * Restore archived curriculum
     */
    public function restore_curriculum() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_studio_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $curriculum_id = intval($_POST['curriculum_id']);
        
        if (!$curriculum_id) {
            wp_send_json_error(array('message' => 'Invalid curriculum ID'));
        }
        
        // Restore the curriculum from trash
        $result = wp_untrash_post($curriculum_id);
        
        if ($result) {
            // Remove archived date meta
            delete_post_meta($curriculum_id, '_archived_date');
            
            // Log activity
            $this->log_activity_internal('curriculum_restored', "Curriculum restored from archive", array(
                'curriculum_id' => $curriculum_id,
                'curriculum_title' => get_the_title($curriculum_id),
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_success(array('message' => 'Curriculum restored successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to restore curriculum'));
        }
    }
    
    /**
     * Delete curriculum permanently
     */
    public function delete_curriculum_permanently() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_studio_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $curriculum_id = intval($_POST['curriculum_id']);
        
        if (!$curriculum_id) {
            wp_send_json_error(array('message' => 'Invalid curriculum ID'));
        }
        
        $curriculum_title = get_the_title($curriculum_id);
        
        // Delete the curriculum permanently
        $result = wp_delete_post($curriculum_id, true);
        
        if ($result) {
            // Log activity
            $this->log_activity_internal('curriculum_deleted_permanently', "Curriculum permanently deleted", array(
                'curriculum_id' => $curriculum_id,
                'curriculum_title' => $curriculum_title,
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_success(array('message' => 'Curriculum deleted permanently'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete curriculum'));
        }
    }
    
    /**
     * Log activity (AJAX endpoint)
     */
    public function log_activity() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_studio_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('read')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $metadata = $_POST['metadata'] ?? '{}';
        
        if (empty($action) || empty($description)) {
            wp_send_json_error(array('message' => 'Action and description are required'));
        }
        
        // Decode and validate metadata
        $metadata_array = json_decode($metadata, true);
        if (!is_array($metadata_array)) {
            $metadata_array = array();
        }
        
        $result = $this->log_activity_internal($action, $description, $metadata_array);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Activity logged successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to log activity'));
        }
    }
    
    /**
     * Internal method to log activity
     */
    private function log_activity_internal($action, $description, $metadata = array()) {
        global $wpdb;
        
        $studio_id = $this->get_current_studio_id();
        $user_id = get_current_user_id();
        
        if (!$studio_id || !$user_id) {
            return false;
        }
        
        // Create activity log table if it doesn't exist
        $table_name = $wpdb->prefix . 'courscribe_activity_log';
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            studio_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            action varchar(50) NOT NULL,
            description text NOT NULL,
            metadata longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY studio_id (studio_id),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insert activity log
        $result = $wpdb->insert(
            $table_name,
            array(
                'studio_id' => $studio_id,
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'description' => sanitize_textarea_field($description),
                'metadata' => wp_json_encode($metadata),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get current studio ID from global post or session
     */
    private function get_current_studio_id_ole() {
        global $post;
        
        // Try to get from current post
        if ($post && $post->post_type === 'crscribe_studio') {
            return $post->ID;
        }
        
        // Try to get from POST data
        if (isset($_POST['studio_id'])) {
            return intval($_POST['studio_id']);
        }
        
        // Try to get from referrer URL
        $referer = wp_get_referer();
        if ($referer) {
            $post_id = url_to_postid($referer);
            if ($post_id && get_post_type($post_id) === 'crscribe_studio') {
                return $post_id;
            }
        }
        
        return null;
    }
    
    /**
     * Archive curriculum
     */
    public function archive_curriculum() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'courscribe_archive_curriculum')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $curriculum_id = intval($_POST['curriculum_id']);
        
        if (!$curriculum_id) {
            wp_send_json_error(array('message' => 'Invalid curriculum ID'));
        }
        
        $curriculum_title = get_the_title($curriculum_id);
        
        // Move curriculum to trash (archive)
        $result = wp_trash_post($curriculum_id);
        
        if ($result) {
            // Add archived date meta
            update_post_meta($curriculum_id, '_archived_date', current_time('mysql'));
            
            // Log activity
            $this->log_activity_internal('curriculum_archived', "Curriculum archived", array(
                'curriculum_id' => $curriculum_id,
                'curriculum_title' => $curriculum_title,
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_success(array('message' => 'Curriculum archived successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to archive curriculum'));
        }
    }
}

// Initialize the AJAX handler only once
if (!isset($GLOBALS['courscribe_studio_premium_ajax_initialized'])) {
    new Courscribe_Studio_Premium_Ajax();
    $GLOBALS['courscribe_studio_premium_ajax_initialized'] = true;
}