<?php
/**
 * AJAX handlers for single curriculum functionality
 * Handles course, module, lesson, and teaching point operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class CourScribe_Single_Curriculum_Ajax {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Course operations
        add_action('wp_ajax_courscribe_update_course_title', array($this, 'update_course_title'));
        add_action('wp_ajax_courscribe_delete_course', array($this, 'delete_course'));
        add_action('wp_ajax_courscribe_update_course_order', array($this, 'update_course_order'));
        
        // Module operations
        add_action('wp_ajax_courscribe_create_module', array($this, 'create_module'));
        add_action('wp_ajax_courscribe_update_module', array($this, 'update_module'));
        add_action('wp_ajax_courscribe_delete_module', array($this, 'delete_module'));
        
        // Lesson operations
        add_action('wp_ajax_courscribe_create_lesson', array($this, 'create_lesson'));
        add_action('wp_ajax_courscribe_update_lesson', array($this, 'update_lesson'));
        add_action('wp_ajax_courscribe_delete_lesson', array($this, 'delete_lesson'));
        
        // Auto-save operations
        add_action('wp_ajax_courscribe_autosave_course_title', array($this, 'autosave_course_title'));
        
        // Slide deck operations
        add_action('wp_ajax_courscribe_generate_slide_deck', array($this, 'generate_slide_deck'));
        
        // Rich text editor operations
        add_action('wp_ajax_courscribe_save_rich_content', array($this, 'save_rich_content'));
        add_action('wp_ajax_courscribe_load_rich_content', array($this, 'load_rich_content'));
        
        // Activity logging
        add_action('wp_ajax_courscribe_log_curriculum_activity', array($this, 'log_curriculum_activity'));
    }
    
    /**
     * Update course title
     */
    public function update_course_title() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $course_id = absint($_POST['course_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$course_id || !$title) {
            wp_send_json_error(array('message' => 'Course ID and title are required.'));
        }
        
        // Verify user can edit this course
        if (!$this->can_user_edit_course($course_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        // Update course
        $result = wp_update_post(array(
            'ID' => $course_id,
            'post_title' => $title
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Failed to update course: ' . $result->get_error_message()));
        }
        
        // Log activity
        $this->log_activity($course_id, 'course', 'update_title', array(
            'old_title' => get_post($course_id)->post_title,
            'new_title' => $title
        ));
        
        wp_send_json_success(array(
            'message' => 'Course title updated successfully',
            'course_id' => $course_id,
            'title' => $title
        ));
    }
    
    /**
     * Delete course
     */
    public function delete_course() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $course_id = absint($_POST['course_id'] ?? 0);
        
        if (!$course_id) {
            wp_send_json_error(array('message' => 'Course ID is required.'));
        }
        
        // Verify user can delete this course
        if (!$this->can_user_delete_course($course_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $course = get_post($course_id);
        if (!$course) {
            wp_send_json_error(array('message' => 'Course not found.'));
        }
        
        // Log activity before deletion
        $this->log_activity($course_id, 'course', 'delete', array(
            'title' => $course->post_title
        ));
        
        // Delete course and associated content
        $result = wp_delete_post($course_id, true);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete course.'));
        }
        
        // Clean up associated modules, lessons, teaching points
        $this->cleanup_course_content($course_id);
        
        wp_send_json_success(array(
            'message' => 'Course deleted successfully',
            'course_id' => $course_id
        ));
    }
    
    /**
     * Update course order
     */
    public function update_course_order() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $course_ids = array_map('absint', $_POST['course_ids'] ?? array());
        
        if (empty($course_ids)) {
            wp_send_json_error(array('message' => 'Course IDs are required.'));
        }
        
        // Update menu order for each course
        $success_count = 0;
        foreach ($course_ids as $index => $course_id) {
            if ($this->can_user_edit_course($course_id)) {
                $result = wp_update_post(array(
                    'ID' => $course_id,
                    'menu_order' => $index + 1
                ));
                
                if (!is_wp_error($result)) {
                    $success_count++;
                }
            }
        }
        
        if ($success_count === 0) {
            wp_send_json_error(array('message' => 'Failed to update course order.'));
        }
        
        // Log activity
        $this->log_activity(0, 'curriculum', 'reorder_courses', array(
            'course_order' => $course_ids
        ));
        
        wp_send_json_success(array(
            'message' => 'Course order updated successfully',
            'updated_count' => $success_count
        ));
    }
    
    /**
     * Create module
     */
    public function create_module() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $course_id = absint($_POST['course_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$course_id || !$title) {
            wp_send_json_error(array('message' => 'Course ID and title are required.'));
        }
        
        // Verify user can edit this course
        if (!$this->can_user_edit_course($course_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        // Get curriculum and studio IDs
        $curriculum_id = get_post_meta($course_id, '_curriculum_id', true);
        $studio_id = get_post_meta($course_id, '_studio_id', true);
        
        // Create module
        $module_data = array(
            'post_title' => $title,
            'post_type' => 'crscribe_module',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                '_course_id' => $course_id,
                '_curriculum_id' => $curriculum_id,
                '_studio_id' => $studio_id,
                '_creator_id' => get_current_user_id()
            )
        );
        
        $module_id = wp_insert_post($module_data, true);
        
        if (is_wp_error($module_id)) {
            wp_send_json_error(array('message' => 'Failed to create module: ' . $module_id->get_error_message()));
        }
        
        // Log activity
        $this->log_activity($module_id, 'module', 'create', array(
            'title' => $title,
            'course_id' => $course_id
        ));
        
        wp_send_json_success(array(
            'message' => 'Module created successfully',
            'module_id' => $module_id,
            'title' => $title
        ));
    }
    
    /**
     * Update module
     */
    public function update_module() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $module_id = absint($_POST['module_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$module_id || !$title) {
            wp_send_json_error(array('message' => 'Module ID and title are required.'));
        }
        
        // Verify user can edit this module
        if (!$this->can_user_edit_module($module_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $old_title = get_post($module_id)->post_title;
        
        // Update module
        $result = wp_update_post(array(
            'ID' => $module_id,
            'post_title' => $title
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Failed to update module: ' . $result->get_error_message()));
        }
        
        // Log activity
        $this->log_activity($module_id, 'module', 'update_title', array(
            'old_title' => $old_title,
            'new_title' => $title
        ));
        
        wp_send_json_success(array(
            'message' => 'Module updated successfully',
            'module_id' => $module_id,
            'title' => $title
        ));
    }
    
    /**
     * Delete module
     */
    public function delete_module() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $module_id = absint($_POST['module_id'] ?? 0);
        
        if (!$module_id) {
            wp_send_json_error(array('message' => 'Module ID is required.'));
        }
        
        // Verify user can delete this module
        if (!$this->can_user_delete_module($module_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $module = get_post($module_id);
        if (!$module) {
            wp_send_json_error(array('message' => 'Module not found.'));
        }
        
        // Log activity before deletion
        $this->log_activity($module_id, 'module', 'delete', array(
            'title' => $module->post_title
        ));
        
        // Delete module and associated content
        $result = wp_delete_post($module_id, true);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete module.'));
        }
        
        // Clean up associated lessons and teaching points
        $this->cleanup_module_content($module_id);
        
        wp_send_json_success(array(
            'message' => 'Module deleted successfully',
            'module_id' => $module_id
        ));
    }
    
    /**
     * Auto-save course title
     */
    public function autosave_course_title() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $course_id = absint($_POST['id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$course_id || !$title) {
            wp_send_json_error(array('message' => 'Course ID and title are required.'));
        }
        
        // Verify user can edit this course
        if (!$this->can_user_edit_course($course_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        // Update course (auto-save)
        $result = wp_update_post(array(
            'ID' => $course_id,
            'post_title' => $title
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Auto-save failed.'));
        }
        
        // Log auto-save activity (less verbose)
        update_post_meta($course_id, '_last_autosave', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => 'Auto-saved',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Generate slide deck
     */
    public function generate_slide_deck() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $course_id = absint($_POST['course_id'] ?? 0);
        
        if (!$course_id) {
            wp_send_json_error(array('message' => 'Course ID is required.'));
        }
        
        // Verify user can edit this course
        if (!$this->can_user_edit_course($course_id)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        // Check tier limitations
        $course = get_post($course_id);
        $studio_id = get_post_meta($course_id, '_studio_id', true);
        $tier = get_post_meta($studio_id, '_studio_tier', true) ?: 'basics';
        
        if ($tier === 'basics') {
            wp_send_json_error(array('message' => 'Slide deck generation is available in Pro plans only.'));
        }
        
        // Get existing slide decks to check limit
        $existing_decks = get_post_meta($course_id, '_courscribe_slide_decks', true) ?: array();
        
        if ($tier === 'plus' && count($existing_decks) >= 2) {
            wp_send_json_error(array('message' => 'Plus plan allows up to 2 slide decks per course. Upgrade to Pro for unlimited.'));
        } elseif ($tier === 'pro' && count($existing_decks) >= 4) {
            wp_send_json_error(array('message' => 'Pro plan allows up to 4 slide decks per course.'));
        }
        
        try {
            // Generate slide deck using AI
            $slide_deck = $this->generate_course_slide_deck($course_id);
            
            // Save slide deck metadata
            $existing_decks[] = array(
                'date' => current_time('mysql'),
                'ppt_url' => $slide_deck['ppt_url'],
                'reveal_url' => $slide_deck['reveal_url'] ?? '',
                'generated_by' => get_current_user_id()
            );
            
            update_post_meta($course_id, '_courscribe_slide_decks', $existing_decks);
            
            // Log activity
            $this->log_activity($course_id, 'course', 'generate_slides', array(
                'slide_count' => count($existing_decks)
            ));
            
            wp_send_json_success(array(
                'message' => 'Slide deck generated successfully',
                'slide_deck' => $slide_deck
            ));
            
        } catch (Exception $e) {
            error_log('CourScribe: Slide deck generation error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to generate slide deck. Please try again.'));
        }
    }
    
    /**
     * Save rich text content
     */
    public function save_rich_content() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $post_id = absint($_POST['post_id'] ?? 0);
        $content = wp_kses_post($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? '');
        
        if (!$post_id || !in_array($post_type, array('crscribe_course', 'crscribe_module', 'crscribe_lesson'))) {
            wp_send_json_error(array('message' => 'Invalid rich content parameters.'));
        }
        
        // Verify permissions
        if (!$this->can_user_edit_post($post_id, $post_type)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        // Update post content
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Failed to save content.'));
        }
        
        // Log activity
        $this->log_activity($post_id, str_replace('crscribe_', '', $post_type), 'update_content', array(
            'content_length' => strlen($content)
        ));
        
        wp_send_json_success(array(
            'message' => 'Content saved successfully',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Load rich text content
     */
    public function load_rich_content() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $post_id = absint($_POST['post_id'] ?? 0);
        $post_type = sanitize_text_field($_POST['post_type'] ?? '');
        
        if (!$post_id || !in_array($post_type, array('crscribe_course', 'crscribe_module', 'crscribe_lesson'))) {
            wp_send_json_error(array('message' => 'Invalid rich content parameters.'));
        }
        
        // Verify permissions
        if (!$this->can_user_view_post($post_id, $post_type)) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => 'Content not found.'));
        }
        
        wp_send_json_success(array(
            'content' => $post->post_content,
            'title' => $post->post_title,
            'last_modified' => $post->post_modified
        ));
    }
    
    /**
     * Log curriculum activity
     */
    public function log_curriculum_activity() {
        check_ajax_referer('courscribe_nonce', 'nonce');
        
        $activity_type = sanitize_text_field($_POST['activity_type'] ?? '');
        $post_id = absint($_POST['post_id'] ?? 0);
        $details = wp_kses_post($_POST['details'] ?? '');
        
        if (!$activity_type || !$post_id) {
            wp_send_json_error(array('message' => 'Activity type and post ID are required.'));
        }
        
        $this->log_activity($post_id, 'custom', $activity_type, array(
            'details' => $details
        ));
        
        wp_send_json_success(array('message' => 'Activity logged'));
    }
    
    // Permission checking methods
    
    private function can_user_edit_course($course_id) {
        if (!is_user_logged_in()) return false;
        
        $current_user = wp_get_current_user();
        $course = get_post($course_id);
        
        if (!$course || $course->post_type !== 'crscribe_course') return false;
        
        // Admin can edit everything
        if (current_user_can('administrator')) return true;
        
        // Check if user is studio admin or collaborator for this course's studio
        $studio_id = get_post_meta($course_id, '_studio_id', true);
        
        if (in_array('studio_admin', $current_user->roles)) {
            $user_studios = get_posts(array(
                'post_type' => 'crscribe_studio',
                'author' => $current_user->ID,
                'fields' => 'ids',
                'posts_per_page' => -1
            ));
            return in_array($studio_id, $user_studios);
        }
        
        if (in_array('collaborator', $current_user->roles)) {
            $user_studio_id = get_user_meta($current_user->ID, '_courscribe_studio_id', true);
            return $studio_id == $user_studio_id;
        }
        
        return false;
    }
    
    private function can_user_delete_course($course_id) {
        // Only studio admins and wp admins can delete courses
        if (!is_user_logged_in()) return false;
        
        $current_user = wp_get_current_user();
        
        if (current_user_can('administrator')) return true;
        
        if (!in_array('studio_admin', $current_user->roles)) return false;
        
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'crscribe_course') return false;
        
        $studio_id = get_post_meta($course_id, '_studio_id', true);
        $user_studios = get_posts(array(
            'post_type' => 'crscribe_studio',
            'author' => $current_user->ID,
            'fields' => 'ids',
            'posts_per_page' => -1
        ));
        
        return in_array($studio_id, $user_studios);
    }
    
    private function can_user_edit_module($module_id) {
        if (!is_user_logged_in()) return false;
        
        $module = get_post($module_id);
        if (!$module || $module->post_type !== 'crscribe_module') return false;
        
        $course_id = get_post_meta($module_id, '_course_id', true);
        return $this->can_user_edit_course($course_id);
    }
    
    private function can_user_delete_module($module_id) {
        if (!is_user_logged_in()) return false;
        
        $module = get_post($module_id);
        if (!$module || $module->post_type !== 'crscribe_module') return false;
        
        $course_id = get_post_meta($module_id, '_course_id', true);
        return $this->can_user_delete_course($course_id);
    }
    
    private function can_user_edit_post($post_id, $post_type) {
        switch ($post_type) {
            case 'crscribe_course':
                return $this->can_user_edit_course($post_id);
            case 'crscribe_module':
                return $this->can_user_edit_module($post_id);
            case 'crscribe_lesson':
                $lesson = get_post($post_id);
                $module_id = get_post_meta($post_id, '_module_id', true);
                return $this->can_user_edit_module($module_id);
            default:
                return false;
        }
    }
    
    private function can_user_view_post($post_id, $post_type) {
        // For now, same as edit permissions
        // Could be expanded for read-only access
        return $this->can_user_edit_post($post_id, $post_type);
    }
    
    // Utility methods
    
    private function cleanup_course_content($course_id) {
        // Delete associated modules
        $modules = get_posts(array(
            'post_type' => 'crscribe_module',
            'meta_query' => array(
                array(
                    'key' => '_course_id',
                    'value' => $course_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($modules as $module_id) {
            wp_delete_post($module_id, true);
            $this->cleanup_module_content($module_id);
        }
    }
    
    private function cleanup_module_content($module_id) {
        // Delete associated lessons
        $lessons = get_posts(array(
            'post_type' => 'crscribe_lesson',
            'meta_query' => array(
                array(
                    'key' => '_module_id',
                    'value' => $module_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($lessons as $lesson_id) {
            wp_delete_post($lesson_id, true);
            // Could add teaching point cleanup here
        }
    }
    
    private function generate_course_slide_deck($course_id) {
        // This would integrate with the existing AI slide generation functionality
        // For now, return a placeholder
        
        $course = get_post($course_id);
        $modules = get_posts(array(
            'post_type' => 'crscribe_module',
            'meta_query' => array(
                array(
                    'key' => '_course_id',
                    'value' => $course_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        // Use existing AI generation logic here
        // This is a simplified placeholder
        
        return array(
            'ppt_url' => wp_upload_dir()['url'] . '/courscribe/slides/course-' . $course_id . '-' . time() . '.pptx',
            'reveal_url' => home_url('/courscribe-slides/course-' . $course_id . '/')
        );
    }
    
    private function log_activity($post_id, $post_type, $action, $details = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'courscribe_activity_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'post_id' => $post_id,
                'post_type' => $post_type,
                'action' => $action,
                'details' => wp_json_encode($details),
                'timestamp' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
}

// Initialize the AJAX handler
new CourScribe_Single_Curriculum_Ajax();