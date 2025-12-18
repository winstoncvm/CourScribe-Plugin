<?php
// Path: includes/helpers/class-courscribe-permissions.php

if (!defined('ABSPATH')) {
    exit;
}

class CourScribe_Permissions {
    private $current_user;
    private $is_collaborator;
    private $is_studio_admin;
    private $is_client;

    public function __construct($user) {
        $this->current_user = $user;
        $this->is_collaborator = in_array('collaborator', $user->roles);
        $this->is_studio_admin = in_array('studio_admin', $user->roles);
        $this->is_client = in_array('client', $user->roles);
    }

    public function can_view_curriculum($curriculum_id, $studio_id) {
        if ($this->is_client) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'courscribe_client_invites';
            $invite = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE email = %s AND curriculum_id = %d AND status = 'Accepted'",
                $this->current_user->user_email,
                $curriculum_id
            ));
            return $invite ? true : false;
        }

        if (current_user_can('edit_crscribe_curriculums') || $this->is_studio_admin) {
            return true;
        }

        if ($this->is_collaborator) {
            $permissions = get_user_meta($this->current_user->ID, '_courscribe_collaborator_permissions', true) ?: [];
            $user_studio_id = get_user_meta($this->current_user->ID, '_courscribe_studio_id', true);
            return in_array('edit_crscribe_curriculums', $permissions) && $studio_id == $user_studio_id;
        }

        $admin_studios = get_posts([
            'post_type' => 'crscribe_studio',
            'post_status' => 'publish',
            'author' => $this->current_user->ID,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        return in_array($studio_id, $admin_studios);
    }

    public function can_edit_curriculum($view_mode, $target_user_id, $curriculum_id) {
        if ($view_mode !== 'edit') {
            return false;
        }

        if ($this->is_studio_admin) {
            return true;
        }

        if ($this->is_collaborator && $this->can_view_curriculum($curriculum_id, get_post_meta($curriculum_id, '_studio_id', true))) {
            return $target_user_id ? $target_user_id === $this->current_user->ID : true;
        }

        return false;
    }

    public function is_client() {
        return $this->is_client;
    }

    public function is_studio_admin() {
        return $this->is_studio_admin;
    }

    public function is_collaborator() {
        return $this->is_collaborator;
    }

    public function get_current_user_id() {
        return $this->current_user->ID;
    }
}