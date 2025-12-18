<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Studio management functionality
 */
class Courscribe_Studio_Management {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add studio-specific meta boxes
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        // Save studio meta
        add_action( 'save_post_crscribe_studio', array( $this, 'save_studio_meta' ) );
        // Add custom columns to admin list
        add_filter( 'manage_crscribe_studio_posts_columns', array( $this, 'set_custom_columns' ) );
        add_action( 'manage_crscribe_studio_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
        // AJAX handlers for dashboard
        add_action( 'wp_ajax_courscribe_get_studio_stats', array( $this, 'get_studio_stats' ) );
        add_action( 'wp_ajax_courscribe_get_studio_activity', array( $this, 'get_studio_activity' ) );
        add_action( 'wp_ajax_courscribe_get_studio_logs', array( $this, 'get_studio_logs' ) );
    }
    
    /**
     * Add meta boxes to studio edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'studio_details',
            'Studio Details',
            array( $this, 'render_studio_details_meta_box' ),
            'crscribe_studio',
            'normal',
            'high'
        );
        
        add_meta_box(
            'studio_advanced',
            'Advanced Settings',
            array( $this, 'render_studio_advanced_meta_box' ),
            'crscribe_studio',
            'side',
            'default'
        );
    }
    
    /**
     * Render studio details meta box
     */
    public function render_studio_details_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'courscribe_save_studio_meta', 'courscribe_studio_nonce' );
        
        // Get saved values
        $studio_lead = get_post_meta( $post->ID, '_studio_lead', true );
        $studio_description = get_post_meta( $post->ID, '_studio_description', true );
        $studio_email = get_post_meta( $post->ID, '_studio_email', true );
        $studio_website = get_post_meta( $post->ID, '_studio_website', true );
        $studio_phone = get_post_meta( $post->ID, '_studio_phone', true );
        
        ?>
        <div class="courscribe-meta-field">
            <label for="studio_lead">Studio Lead:</label>
            <input type="text" id="studio_lead" name="studio_lead" value="<?php echo esc_attr( $studio_lead ); ?>" class="widefat">
        </div>
        
        <div class="courscribe-meta-field" style="margin-top: 15px;">
            <label for="studio_email">Studio Email:</label>
            <input type="email" id="studio_email" name="studio_email" value="<?php echo esc_attr( $studio_email ); ?>" class="widefat">
        </div>
        
        <div class="courscribe-meta-field" style="margin-top: 15px;">
            <label for="studio_website">Studio Website:</label>
            <input type="url" id="studio_website" name="studio_website" value="<?php echo esc_attr( $studio_website ); ?>" class="widefat" placeholder="https://">
        </div>
        
        <div class="courscribe-meta-field" style="margin-top: 15px;">
            <label for="studio_phone">Studio Phone:</label>
            <input type="tel" id="studio_phone" name="studio_phone" value="<?php echo esc_attr( $studio_phone ); ?>" class="widefat">
        </div>
        
        <div class="courscribe-meta-field" style="margin-top: 15px;">
            <label for="studio_description">Studio Description:</label>
            <textarea id="studio_description" name="studio_description" class="widefat" rows="4"><?php echo esc_textarea( $studio_description ); ?></textarea>
        </div>
        <?php
    }
    
    /**
     * Save studio meta
     */
    public function save_studio_meta( $post_id ) {
        // Security checks
        if ( ! isset( $_POST['courscribe_studio_nonce'] ) || ! wp_verify_nonce( $_POST['courscribe_studio_nonce'], 'courscribe_save_studio_meta' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_crscribe_studio', $post_id ) ) {
            return;
        }
        
        // Save meta fields
        if ( isset( $_POST['studio_lead'] ) ) {
            update_post_meta( $post_id, '_studio_lead', sanitize_text_field( $_POST['studio_lead'] ) );
        }
        
        if ( isset( $_POST['studio_email'] ) ) {
            update_post_meta( $post_id, '_studio_email', sanitize_email( $_POST['studio_email'] ) );
        }
        
        if ( isset( $_POST['studio_website'] ) ) {
            update_post_meta( $post_id, '_studio_website', esc_url_raw( $_POST['studio_website'] ) );
        }
        
        if ( isset( $_POST['studio_phone'] ) ) {
            update_post_meta( $post_id, '_studio_phone', sanitize_text_field( $_POST['studio_phone'] ) );
        }
        
        if ( isset( $_POST['studio_description'] ) ) {
            update_post_meta( $post_id, '_studio_description', sanitize_textarea_field( $_POST['studio_description'] ) );
        }
        
        if ( isset( $_POST['studio_is_public'] ) ) {
            update_post_meta( $post_id, '_studio_is_public', (bool) $_POST['studio_is_public'] );
        } else {
            update_post_meta( $post_id, '_studio_is_public', false );
        }
        
        if ( isset( $_POST['studio_max_collaborators'] ) ) {
            update_post_meta( $post_id, '_studio_max_collaborators', absint( $_POST['studio_max_collaborators'] ) );
        }
    }
    
    /**
     * Set custom columns for studio list
     */
    public function set_custom_columns( $columns ) {
        $new_columns = array();
        
        // Insert title and checkbox first
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add custom columns
        $new_columns['studio_lead'] = 'Studio Lead';
        $new_columns['studio_email'] = 'Email';
        $new_columns['curriculum_count'] = 'Curriculums';
        $new_columns['collaborator_count'] = 'Collaborators';
        $new_columns['studio_status'] = 'Status';
        
        // Add remaining columns
        foreach ( $columns as $key => $value ) {
            if ( ! isset( $new_columns[$key] ) ) {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public function custom_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'studio_lead':
                $studio_lead = get_post_meta( $post_id, '_studio_lead', true );
                echo esc_html( $studio_lead ?: '—' );
                break;
                
            case 'studio_email':
                $studio_email = get_post_meta( $post_id, '_studio_email', true );
                echo esc_html( $studio_email ?: '—' );
                break;
                
            case 'curriculum_count':
                // Count associated curriculums
                $count = $this->get_curriculum_count( $post_id );
                echo $count > 0 ? '<a href="' . esc_url( admin_url( 'edit.php?post_type=crscribe_curriculum&studio=' . $post_id ) ) . '">' . $count . '</a>' : '0';
                break;
                
            case 'collaborator_count':
                $count = $this->get_collaborator_count( $post_id );
                echo esc_html( $count );
                break;
                
            case 'studio_status':
                $is_public = get_post_meta( $post_id, '_studio_is_public', true );
                echo $is_public ? '<span class="status-public">Public</span>' : '<span class="status-private">Private</span>';
                break;
        }
    }
    
    /**
     * Render advanced studio settings meta box
     */
    public function render_studio_advanced_meta_box( $post ) {
        $is_public = get_post_meta( $post->ID, '_studio_is_public', true );
        $max_collaborators = get_post_meta( $post->ID, '_studio_max_collaborators', true ) ?: 10;
        
        ?>
        <div class="courscribe-meta-field">
            <label>
                <input type="checkbox" name="studio_is_public" value="1" <?php checked( $is_public ); ?> />
                Make studio public
            </label>
            <p class="description">Public studios can be discovered by other users</p>
        </div>
        
        <div class="courscribe-meta-field" style="margin-top: 15px;">
            <label for="studio_max_collaborators">Max Collaborators:</label>
            <input type="number" id="studio_max_collaborators" name="studio_max_collaborators" 
                   value="<?php echo esc_attr( $max_collaborators ); ?>" class="widefat" min="1" max="100">
            <p class="description">Maximum number of collaborators allowed</p>
        </div>
        <?php
    }
    
    /**
     * Get curriculum count for a studio
     */
    private function get_curriculum_count( $studio_id ) {
        $args = array(
            'post_type' => 'crscribe_curriculum',
            'meta_query' => array(
                array(
                    'key' => '_studio_id',
                    'value' => $studio_id,
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $query = new WP_Query( $args );
        return $query->found_posts;
    }
    
    /**
     * Get collaborator count for a studio
     */
    private function get_collaborator_count( $studio_id ) {
        $users = get_users( array(
            'role' => 'collaborator',
            'meta_key' => '_courscribe_studio_id',
            'meta_value' => $studio_id,
            'fields' => 'ID',
        ) );
        
        return count( $users );
    }
    
    /**
     * AJAX handler to get studio statistics
     */
    public function get_studio_stats() {
        // Verify user permissions
        if ( ! current_user_can( 'edit_crscribe_studios' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Get studios based on user role
        $studio_args = array(
            'post_type' => 'crscribe_studio',
            'post_status' => 'publish',
            'numberposts' => -1,
        );
        
        if ( in_array( 'collaborator', $user_roles ) ) {
            $studio_id = get_user_meta( $current_user->ID, '_courscribe_studio_id', true );
            if ( $studio_id ) {
                $studio_args['p'] = $studio_id;
            }
        } else {
            $studio_args['author'] = $current_user->ID;
        }
        
        $studios = get_posts( $studio_args );
        $total_studios = count( $studios );
        
        // Count total curriculums
        $total_curriculums = 0;
        $total_collaborators = 0;
        
        foreach ( $studios as $studio ) {
            $total_curriculums += $this->get_curriculum_count( $studio->ID );
            $total_collaborators += $this->get_collaborator_count( $studio->ID );
        }
        
        // Get active users (last 7 days)
        $active_users = get_users( array(
            'meta_query' => array(
                array(
                    'key' => '_courscribe_last_login',
                    'value' => date( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
                    'compare' => '>=',
                    'type' => 'DATETIME',
                ),
            ),
            'fields' => 'ID',
        ) );
        
        wp_send_json_success( array(
            'total_studios' => $total_studios,
            'total_curriculums' => $total_curriculums,
            'total_collaborators' => $total_collaborators,
            'active_users' => count( $active_users ),
        ) );
    }
    
    /**
     * AJAX handler to get studio activity data for charts
     */
   public function get_studio_activity() {
    if ( ! current_user_can( 'edit_crscribe_studios' ) ) {
        wp_send_json_error( 'Insufficient permissions' );
    }

    global $wpdb;

    // Define the post types to include
    $post_types = array(
        'crscribe_studio',
        'crscribe_curriculum',
        'crscribe_course',
        'crscribe_module',
        'crscribe_lesson',
    );

    // Get today's date and date 30 days ago
    $end_date = date( 'Y-m-d' );
    $start_date = date( 'Y-m-d', strtotime( '-29 days' ) ); // includes today

    // Prepare the placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

    // Query to get post counts grouped by date
    $query = $wpdb->prepare(
        "
        SELECT DATE(post_date) as post_day, COUNT(*) as count
        FROM {$wpdb->posts}
        WHERE post_type IN ($placeholders)
        AND post_status = 'publish'
        AND DATE(post_date) BETWEEN %s AND %s
        GROUP BY post_day
        ",
        array_merge($post_types, array($start_date, $end_date))
    );

    $results = $wpdb->get_results($query, OBJECT_K);

    // Initialize 30-day activity array with zeros
    $activity_data = array();
    for ( $i = 29; $i >= 0; $i-- ) {
        $date = date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
        $activity_data[] = array(
            'date'  => $date,
            'count' => isset($results[$date]) ? (int) $results[$date]->count : 0,
        );
    }

    wp_send_json_success( $activity_data );
}
    
    /**
     * AJAX handler to get studio logs
     */
    public function get_studio_logs() {
    // Permissions check
    if ( ! current_user_can( 'edit_crscribe_studios' ) ) {
        wp_send_json_error( 'Insufficient permissions' );
    }

    // CSRF protection (optional but recommended)
    check_ajax_referer( 'courscribe_admin_nonce', 'nonce' );

    // Get and validate studio ID
    $studio_id = isset( $_POST['studio_id'] ) ? intval( $_POST['studio_id'] ) : 0;
    if ( $studio_id <= 0 ) {
        wp_send_json_error( 'Invalid studio ID' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_studio_log';

    // Fetch last 10 logs for the studio
    $logs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE studio_id = %d 
             ORDER BY timestamp DESC 
             LIMIT 10",
            $studio_id
        )
    );

    // Format logs
    $formatted_logs = array();
    foreach ( $logs as $log ) {
        $user = get_userdata( $log->user_id );
        $formatted_logs[] = array(
            'timestamp'  => wp_date( 'M j, Y g:i A', strtotime( $log->timestamp ) ),
            'user'       => $user ? $user->display_name : 'Unknown User',
            'action'     => ucfirst( str_replace( '_', ' ', $log->action ) ),
            'studio_id'  => $log->studio_id,
            'details'    => $log->changes 
                ? ( strlen( $log->changes ) > 100 
                    ? substr( $log->changes, 0, 100 ) . '...' 
                    : $log->changes ) 
                : 'No details',
        );
    }

    wp_send_json_success( $formatted_logs );
}
}

// Initialize studio management
$courscribe_studio_management = new Courscribe_Studio_Management();