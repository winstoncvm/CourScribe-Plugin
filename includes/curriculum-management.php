<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * /includes/curriculum-management.php
 * Curriculum management functionality
 */
class Courscribe_Curriculum_Management {

    /**
     * Constructor
     */
    public function __construct() {
        // Add meta boxes
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        // Save meta
        add_action( 'save_post_crscribe_curriculum', array( $this, 'save_curriculum_meta' ) );
        add_action( 'save_post_crscribe_course', array( $this, 'save_course_meta' ) );
        add_action( 'save_post_crscribe_module', array( $this, 'save_module_meta' ) );
        add_action( 'save_post_crscribe_lesson', array( $this, 'save_lesson_meta' ) );
        // Add custom columns
        add_filter( 'manage_crscribe_curriculum_posts_columns', array( $this, 'set_custom_columns' ) );
        add_action( 'manage_crscribe_curriculum_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
        add_filter( 'manage_crscribe_course_posts_columns', array( $this, 'set_course_columns' ) );
        add_action( 'manage_crscribe_course_posts_custom_column', array( $this, 'course_column_content' ), 10, 2 );
        add_filter( 'manage_crscribe_module_posts_columns', array( $this, 'set_module_columns' ) );
        add_action( 'manage_crscribe_module_posts_custom_column', array( $this, 'module_column_content' ), 10, 2 );
        add_filter( 'manage_crscribe_lesson_posts_columns', array( $this, 'set_lesson_columns' ) );
        add_action( 'manage_crscribe_lesson_posts_custom_column', array( $this, 'lesson_column_content' ), 10, 2 );
        // Add frontend scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
        // AJAX handlers
        add_action( 'wp_ajax_courscribe_save_curriculum', array( $this, 'ajax_save_curriculum' ) );
        add_action( 'wp_ajax_courscribe_archive_curriculum', array( $this, 'ajax_archive_curriculum' ) );
        add_action( 'wp_ajax_courscribe_delete_curriculum', array( $this, 'ajax_delete_curriculum' ) );
        // Studio filter
        add_action( 'restrict_manage_posts', array( $this, 'add_studio_filter' ) );
        add_filter( 'parse_query', array( $this, 'filter_curriculum_by_studio' ) );
        // Create change log table
        add_action( 'init', array( $this, 'create_change_log_table' ) );
        // Debug admin access
        add_action( 'admin_init', array( $this, 'debug_admin_access' ) );
        // Grant capabilities
        add_action( 'admin_init', array( $this, 'grant_admin_capabilities' ) );
        add_action( 'init', array( $this, 'grant_studio_admin_capabilities' ) );
    }

    /**
     * Grant capabilities to administrators
     */
    public function grant_admin_capabilities() {
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $capabilities = [
                'edit_crscribe_curriculum',
                'edit_crscribe_curriculums',
                'publish_crscribe_curriculums',
                'create_crscribe_curriculums',
                'delete_crscribe_curriculum',
                'delete_crscribe_curriculums',
                'read_crscribe_curriculum',
                'read_crscribe_curriculums',
                'edit_crscribe_course',
                'edit_crscribe_courses',
                'publish_crscribe_courses',
                'create_crscribe_courses',
                'delete_crscribe_course',
                'delete_crscribe_courses',
                'read_crscribe_course',
                'edit_crscribe_module',
                'edit_crscribe_modules',
                'publish_crscribe_modules',
                'create_crscribe_modules',
                'delete_crscribe_module',
                'delete_crscribe_modules',
                'read_crscribe_module',
                'edit_crscribe_lesson',
                'edit_crscribe_lessons',
                'publish_crscribe_lessons',
                'create_crscribe_lessons',
                'delete_crscribe_lesson',
                'delete_crscribe_lessons',
                'read_crscribe_lesson',
                'edit_crscribe_studio',
                'edit_crscribe_studios',
                'publish_crscribe_studios',
                'create_crscribe_studios',
                'delete_crscribe_studio',
                'delete_crscribe_studios',
                'read_crscribe_studio',
            ];
            foreach ( $capabilities as $cap ) {
                $admin_role->add_cap( $cap );
                error_log( 'Courscribe: Granted capability ' . $cap . ' to administrator' );
            }
        }
    }

    /**
     * Grant capabilities to studio admins
     */
    public function grant_studio_admin_capabilities() {
        $role = get_role( 'studio_admin' );
        if ( $role ) {
            $capabilities = [
                'edit_crscribe_curriculum',
                'edit_crscribe_curriculums',
                'publish_crscribe_curriculums',
                'create_crscribe_curriculums',
                'delete_crscribe_curriculum',
                'delete_crscribe_curriculums',
                'read_crscribe_curriculum',
                'edit_crscribe_course',
                'edit_crscribe_courses',
                'publish_crscribe_courses',
                'create_crscribe_courses',
                'delete_crscribe_course',
                'delete_crscribe_courses',
                'read_crscribe_course',
                'edit_crscribe_module',
                'edit_crscribe_modules',
                'publish_crscribe_modules',
                'create_crscribe_modules',
                'delete_crscribe_module',
                'delete_crscribe_modules',
                'read_crscribe_module',
                'edit_crscribe_lesson',
                'edit_crscribe_lessons',
                'publish_crscribe_lessons',
                'create_crscribe_lessons',
                'delete_crscribe_lesson',
                'delete_crscribe_lessons',
                'read_crscribe_lesson',
                'read_crscribe_studio',
            ];
            foreach ( $capabilities as $cap ) {
                $role->add_cap( $cap );
                error_log( 'Courscribe: Granted capability ' . $cap . ' to studio_admin' );
            }
        }
    }

    /**
     * Debug admin access to curriculums
     */
    public function debug_admin_access() {
        if ( current_user_can( 'administrator' ) ) {
            $caps = [
                'edit_crscribe_curriculum',
                'edit_crscribe_curriculums',
                'publish_crscribe_curriculums',
                'create_crscribe_curriculums',
                'delete_crscribe_curriculum',
                'delete_crscribe_curriculums',
                'read_crscribe_curriculum',
                'edit_crscribe_course',
                'edit_crscribe_courses',
                'publish_crscribe_courses',
                'create_crscribe_courses',
                'delete_crscribe_course',
                'delete_crscribe_courses',
                'read_crscribe_course',
                'edit_crscribe_module',
                'edit_crscribe_modules',
                'publish_crscribe_modules',
                'create_crscribe_modules',
                'delete_crscribe_module',
                'delete_crscribe_modules',
                'read_crscribe_module',
                'edit_crscribe_lesson',
                'edit_crscribe_lessons',
                'publish_crscribe_lessons',
                'create_crscribe_lessons',
                'delete_crscribe_lesson',
                'delete_crscribe_lessons',
                'read_crscribe_lesson',
                'read_crscribe_studio',
            ];
            foreach ( $caps as $cap ) {
                if ( current_user_can( $cap ) ) {
                    error_log( 'Courscribe: Admin user has capability ' . $cap );
                } else {
                    error_log( 'Courscribe: ERROR - Admin user missing capability ' . $cap );
                }
            }
        }
    }

    /**
     * Create change log table
     */
    public function create_change_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_curriculum_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curriculum_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            changes LONGTEXT,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (log_id),
            KEY curriculum_id (curriculum_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        error_log( 'Courscribe: Change log table created or verified' );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Curriculum meta boxes
        add_meta_box(
            'curriculum_details',
            'Curriculum Details',
            array( $this, 'render_curriculum_details_meta_box' ),
            'crscribe_curriculum',
            'normal',
            'high'
        );
        add_meta_box(
            'curriculum_studio',
            'Associated Studio',
            array( $this, 'render_curriculum_studio_meta_box' ),
            'crscribe_curriculum',
            'side',
            'default'
        );
        add_meta_box(
            'curriculum_change_log',
            'Change Log',
            array( $this, 'render_curriculum_change_log_meta_box' ),
            'crscribe_curriculum',
            'normal',
            'default'
        );
        // Course meta boxes
        add_meta_box(
            'course_details',
            'Course Details',
            array( $this, 'render_course_details_meta_box' ),
            'crscribe_course',
            'normal',
            'high'
        );
        add_meta_box(
            'course_curriculum',
            'Associated Curriculum',
            array( $this, 'render_course_curriculum_meta_box' ),
            'crscribe_course',
            'side',
            'default'
        );
        // Module meta boxes
        add_meta_box(
            'module_details',
            'Module Details',
            array( $this, 'render_module_details_meta_box' ),
            'crscribe_module',
            'normal',
            'high'
        );
        add_meta_box(
            'module_course',
            'Associated Course',
            array( $this, 'render_module_course_meta_box' ),
            'crscribe_module',
            'side',
            'default'
        );
        // Lesson meta boxes
        add_meta_box(
            'lesson_details',
            'Lesson Details',
            array( $this, 'render_lesson_details_meta_box' ),
            'crscribe_lesson',
            'normal',
            'high'
        );
        add_meta_box(
            'lesson_module',
            'Associated Module',
            array( $this, 'render_lesson_module_meta_box' ),
            'crscribe_lesson',
            'side',
            'default'
        );
    }

    /**
     * Render curriculum details meta box
     */
    public function render_curriculum_details_meta_box( $post ) {
        wp_nonce_field( 'courscribe_save_curriculum_meta', 'courscribe_curriculum_nonce' );
        $topic = get_post_meta( $post->ID, '_curriculum_topic', true );
        $goal = get_post_meta( $post->ID, '_curriculum_goal', true );
        $notes = get_post_meta( $post->ID, '_curriculum_notes', true );
        $status = get_post_meta( $post->ID, '_curriculum_status', true ) ?: 'draft';
        $creator_id = get_post_meta( $post->ID, '_creator_id', true );
        ?>
        <div class="courscribe-meta-field">
            <label for="curriculum_topic">Topic:</label>
            <input type="text" id="curriculum_topic" name="curriculum_topic" class="widefat" value="<?php echo esc_attr( $topic ); ?>">
        </div>
        <div class="courscribe-meta-field">
            <label for="curriculum_goal">Goal:</label>
            <textarea id="curriculum_goal" name="curriculum_goal" class="widefat" rows="5"><?php echo esc_textarea( $goal ); ?></textarea>
        </div>
        <div class="courscribe-meta-field">
            <label for="curriculum_notes">Notes:</label>
            <?php wp_editor( $notes, 'curriculum_notes', array( 'textarea_name' => 'curriculum_notes', 'rows' => 10 ) ); ?>
        </div>
        <div class="courscribe-meta-field">
            <label for="curriculum_status">Status:</label>
            <select id="curriculum_status" name="curriculum_status">
                <option value="draft" <?php selected( $status, 'draft' ); ?>>Draft</option>
                <option value="review" <?php selected( $status, 'review' ); ?>>Review</option>
                <option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
                <option value="published" <?php selected( $status, 'published' ); ?>>Published</option>
            </select>
        </div>
        <div class="courscribe-meta-field">
            <label>Creator:</label>
            <?php echo $creator_id ? esc_html( get_userdata( $creator_id )->display_name ) : 'N/A'; ?>
        </div>
        <?php
    }

    /**
     * Render curriculum studio meta box
     */
    public function render_curriculum_studio_meta_box( $post ) {
        $studio_id = get_post_meta( $post->ID, '_studio_id', true );
        $studios = get_posts( array(
            'post_type' => 'crscribe_studio',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        ?>
        <div class="courscribe-meta-field">
            <label for="curriculum_studio">Select Studio:</label>
            <select id="curriculum_studio" name="curriculum_studio" class="widefat">
                <option value="">— None —</option>
                <?php foreach ( $studios as $studio ) : ?>
                    <option value="<?php echo $studio->ID; ?>" <?php selected( $studio_id, $studio->ID ); ?>>
                        <?php echo $studio->post_title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render change log meta box
     */
    public function render_curriculum_change_log_meta_box( $post ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'courscribe_curriculum_log';
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE curriculum_id = %d ORDER BY timestamp DESC",
            $post->ID
        ) );
        ?>
        <div class="courscribe-meta-field">
            <h4>Change Log</h4>
            <?php if ( $logs ) : ?>
                <table class="widefat">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Changes</th>
                        <th>Timestamp</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( get_userdata( $log->user_id )->display_name ); ?></td>
                            <td><?php echo esc_html( $log->action ); ?></td>
                            <td><?php echo esc_html( $log->changes ); ?></td>
                            <td><?php echo esc_html( $log->timestamp ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No changes logged.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render course details meta box
     */
    public function render_course_details_meta_box( $post ) {
        wp_nonce_field( 'courscribe_save_course_meta', 'courscribe_course_nonce' );
        $description = get_post_meta( $post->ID, '_course_description', true );
        $order = get_post_meta( $post->ID, '_order', true );
        ?>
        <div class="courscribe-meta-field">
            <label for="course_description">Description:</label>
            <textarea id="course_description" name="course_description" class="widefat" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
        </div>
        <div class="courscribe-meta-field">
            <label for="course_order">Order:</label>
            <input type="number" id="course_order" name="course_order" class="widefat" value="<?php echo esc_attr( $order ); ?>">
        </div>
        <?php
    }

    /**
     * Render course curriculum meta box
     */
    public function render_course_curriculum_meta_box( $post ) {
        $curriculum_id = get_post_meta( $post->ID, '_curriculum_id', true );
        $curriculums = get_posts( array(
            'post_type' => 'crscribe_curriculum',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        ?>
        <div class="courscribe-meta-field">
            <label for="course_curriculum">Select Curriculum:</label>
            <select id="course_curriculum" name="course_curriculum" class="widefat">
                <option value="">— None —</option>
                <?php foreach ( $curriculums as $curriculum ) : ?>
                    <option value="<?php echo $curriculum->ID; ?>" <?php selected( $curriculum_id, $curriculum->ID ); ?>>
                        <?php echo $curriculum->post_title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render module details meta box
     */
    public function render_module_details_meta_box( $post ) {
        wp_nonce_field( 'courscribe_save_module_meta', 'courscribe_module_nonce' );
        $description = get_post_meta( $post->ID, '_module_description', true );
        $order = get_post_meta( $post->ID, '_order', true );
        ?>
        <div class="courscribe-meta-field">
            <label for="module_description">Description:</label>
            <textarea id="module_description" name="module_description" class="widefat" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
        </div>
        <div class="courscribe-meta-field">
            <label for="module_order">Order:</label>
            <input type="number" id="module_order" name="module_order" class="widefat" value="<?php echo esc_attr( $order ); ?>">
        </div>
        <?php
    }

    /**
     * Render module course meta box
     */
    public function render_module_course_meta_box( $post ) {
        $course_id = get_post_meta( $post->ID, '_course_id', true );
        $courses = get_posts( array(
            'post_type' => 'crscribe_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        ?>
        <div class="courscribe-meta-field">
            <label for="module_course">Select Course:</label>
            <select id="module_course" name="module_course" class="widefat">
                <option value="">— None —</option>
                <?php foreach ( $courses as $course ) : ?>
                    <option value="<?php echo $course->ID; ?>" <?php selected( $course_id, $course->ID ); ?>>
                        <?php echo $course->post_title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render lesson details meta box
     */
    public function render_lesson_details_meta_box( $post ) {
        wp_nonce_field( 'courscribe_save_lesson_meta', 'courscribe_lesson_nonce' );
        $content = get_post_meta( $post->ID, '_lesson_content', true );
        $order = get_post_meta( $post->ID, '_order', true );
        ?>
        <div class="courscribe-meta-field">
            <label for="lesson_content">Content:</label>
            <?php wp_editor( $content, 'lesson_content', array( 'textarea_name' => 'lesson_content', 'rows' => 10 ) ); ?>
        </div>
        <div class="courscribe-meta-field">
            <label for="lesson_order">Order:</label>
            <input type="number" id="lesson_order" name="lesson_order" class="widefat" value="<?php echo esc_attr( $order ); ?>">
        </div>
        <?php
    }

    /**
     * Render lesson module meta box
     */
    public function render_lesson_module_meta_box( $post ) {
        $module_id = get_post_meta( $post->ID, '_module_id', true );
        $modules = get_posts( array(
            'post_type' => 'crscribe_module',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        ?>
        <div class="courscribe-meta-field">
            <label for="lesson_module">Select Module:</label>
            <select id="lesson_module" name="lesson_module" class="widefat">
                <option value="">— None —</option>
                <?php foreach ( $modules as $module ) : ?>
                    <option value="<?php echo $module->ID; ?>" <?php selected( $module_id, $module->ID ); ?>>
                        <?php echo $module->post_title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Save curriculum meta
     */
    public function save_curriculum_meta( $post_id ) {
        if ( ! isset( $_POST['courscribe_curriculum_nonce'] ) || ! wp_verify_nonce( $_POST['courscribe_curriculum_nonce'], 'courscribe_save_curriculum_meta' ) ) {
            error_log( 'Courscribe: Invalid nonce for curriculum meta save' );
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            error_log( 'Courscribe: Autosave detected, skipping curriculum meta save' );
            return;
        }

//        if ( ! current_user_can( 'edit_crscribe_curriculum', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
//            error_log( 'Courscribe: User lacks permission to edit curriculum ' . $post_id );
//            return;
//        }

        $old_data = array(
            'topic' => get_post_meta( $post_id, '_curriculum_topic', true ),
            'goal' => get_post_meta( $post_id, '_curriculum_goal', true ),
            'notes' => get_post_meta( $post_id, '_curriculum_notes', true ),
            'status' => get_post_meta( $post_id, '_curriculum_status', true ),
            'studio_id' => get_post_meta( $post_id, '_studio_id', true ),
        );

        // Save meta fields
        if ( isset( $_POST['curriculum_topic'] ) ) {
            update_post_meta( $post_id, '_curriculum_topic', sanitize_text_field( $_POST['curriculum_topic'] ) );
        }
        if ( isset( $_POST['curriculum_goal'] ) ) {
            update_post_meta( $post_id, '_curriculum_goal', wp_kses_post( $_POST['curriculum_goal'] ) );
        }
        if ( isset( $_POST['curriculum_notes'] ) ) {
            update_post_meta( $post_id, '_curriculum_notes', wp_kses_post( $_POST['curriculum_notes'] ) );
        }
        if ( isset( $_POST['curriculum_status'] ) ) {
            update_post_meta( $post_id, '_curriculum_status', sanitize_text_field( $_POST['curriculum_status'] ) );
        }
        if ( isset( $_POST['curriculum_studio'] ) ) {
            update_post_meta( $post_id, '_studio_id', absint( $_POST['curriculum_studio'] ) );
        }
        // Set creator on first save
        if ( ! get_post_meta( $post_id, '_creator_id', true ) ) {
            update_post_meta( $post_id, '_creator_id', get_current_user_id() );
        }

        // Log changes
        $new_data = array(
            'topic' => isset( $_POST['curriculum_topic'] ) ? sanitize_text_field( $_POST['curriculum_topic'] ) : $old_data['topic'],
            'goal' => isset( $_POST['curriculum_goal'] ) ? wp_kses_post( $_POST['curriculum_goal'] ) : $old_data['goal'],
            'notes' => isset( $_POST['curriculum_notes'] ) ? wp_kses_post( $_POST['curriculum_notes'] ) : $old_data['notes'],
            'status' => isset( $_POST['curriculum_status'] ) ? sanitize_text_field( $_POST['curriculum_status'] ) : $old_data['status'],
            'studio_id' => isset( $_POST['curriculum_studio'] ) ? absint( $_POST['curriculum_studio'] ) : $old_data['studio_id'],
        );
        $changes = array();
        foreach ( $new_data as $key => $value ) {
            if ( $value !== $old_data[$key] ) {
                $changes[$key] = array( 'old' => $old_data[$key], 'new' => $value );
            }
        }
        if ( ! get_post_meta( $post_id, '_creator_id', true ) ) {
            $changes['creator'] = array( 'new' => get_current_user_id() );
        }
        if ( $changes ) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'courscribe_curriculum_log',
                array(
                    'curriculum_id' => $post_id,
                    'user_id' => get_current_user_id(),
                    'action' => get_post_meta( $post_id, '_creator_id', true ) ? 'update' : 'create',
                    'changes' => wp_json_encode( $changes ),
                    'timestamp' => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%s' )
            );
            error_log( 'Courscribe: Logged changes for curriculum ' . $post_id );
        }
    }

    /**
     * Save course meta
     */
    public function save_course_meta( $post_id ) {
        if ( ! isset( $_POST['courscribe_course_nonce'] ) || ! wp_verify_nonce( $_POST['courscribe_course_nonce'], 'courscribe_save_course_meta' ) ) {
            error_log( 'Courscribe: Invalid nonce for course meta save' );
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            error_log( 'Courscribe: Autosave detected, skipping course meta save' );
            return;
        }
        if ( ! current_user_can( 'edit_crscribe_course', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
            error_log( 'Courscribe: User lacks permission to edit course ' . $post_id );
            return;
        }

        if ( isset( $_POST['course_description'] ) ) {
            update_post_meta( $post_id, '_course_description', wp_kses_post( $_POST['course_description'] ) );
        }
        if ( isset( $_POST['course_curriculum'] ) ) {
            update_post_meta( $post_id, '_curriculum_id', absint( $_POST['course_curriculum'] ) );
        }
        if ( isset( $_POST['course_order'] ) ) {
            update_post_meta( $post_id, '_order', absint( $_POST['course_order'] ) );
        }
        error_log( 'Courscribe: Saved course meta for post ' . $post_id );
    }

    /**
     * Save module meta
     */
    public function save_module_meta( $post_id ) {
        if ( ! isset( $_POST['courscribe_module_nonce'] ) || ! wp_verify_nonce( $_POST['courscribe_module_nonce'], 'courscribe_save_module_meta' ) ) {
            error_log( 'Courscribe: Invalid nonce for module meta save' );
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            error_log( 'Courscribe: Autosave detected, skipping module meta save' );
            return;
        }
        if ( ! current_user_can( 'edit_crscribe_module', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
            error_log( 'Courscribe: User lacks permission to edit module ' . $post_id );
            return;
        }

        if ( isset( $_POST['module_description'] ) ) {
            update_post_meta( $post_id, '_module_description', wp_kses_post( $_POST['module_description'] ) );
        }
        if ( isset( $_POST['module_course'] ) ) {
            update_post_meta( $post_id, '_course_id', absint( $_POST['module_course'] ) );
        }
        if ( isset( $_POST['module_order'] ) ) {
            update_post_meta( $post_id, '_order', absint( $_POST['module_order'] ) );
        }
        error_log( 'Courscribe: Saved module meta for post ' . $post_id );
    }

    /**
     * Save lesson meta
     */
    public function save_lesson_meta( $post_id ) {
        if ( ! isset( $_POST['courscribe_lesson_nonce'] ) || ! wp_verify_nonce( $_POST['courscribe_lesson_nonce'], 'courscribe_save_lesson_meta' ) ) {
            error_log( 'Courscribe: Invalid nonce for lesson meta save' );
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            error_log( 'Courscribe: Autosave detected, skipping lesson meta save' );
            return;
        }
        if ( ! current_user_can( 'edit_crscribe_lesson', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
            error_log( 'Courscribe: User lacks permission to edit lesson ' . $post_id );
            return;
        }

        if ( isset( $_POST['lesson_content'] ) ) {
            update_post_meta( $post_id, '_lesson_content', wp_kses_post( $_POST['lesson_content'] ) );
        }
        if ( isset( $_POST['lesson_module'] ) ) {
            update_post_meta( $post_id, '_module_id', absint( $_POST['lesson_module'] ) );
        }
        if ( isset( $_POST['lesson_order'] ) ) {
            update_post_meta( $post_id, '_order', absint( $_POST['lesson_order'] ) );
        }
        error_log( 'Courscribe: Saved lesson meta for post ' . $post_id );
    }

    /**
     * AJAX handler for saving curriculum
     */
    public function ajax_save_curriculum() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'courscribe_frontend_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            error_log( 'Courscribe: AJAX save curriculum failed - invalid nonce' );
        }
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! current_user_can( 'edit_crscribe_curriculum', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
            error_log( 'Courscribe: AJAX save curriculum failed - permission denied for post ' . $post_id );
        }

        $old_data = array(
            'topic' => get_post_meta( $post_id, '_curriculum_topic', true ),
            'goal' => get_post_meta( $post_id, '_curriculum_goal', true ),
            'notes' => get_post_meta( $post_id, '_curriculum_notes', true ),
            'status' => get_post_meta( $post_id, '_curriculum_status', true ),
            'studio_id' => get_post_meta( $post_id, '_studio_id', true ),
        );

        // Update meta fields
        $new_data = array();
        if ( isset( $_POST['topic'] ) ) {
            $new_data['topic'] = sanitize_text_field( $_POST['topic'] );
            update_post_meta( $post_id, '_curriculum_topic', $new_data['topic'] );
        } else {
            $new_data['topic'] = $old_data['topic'];
        }
        if ( isset( $_POST['goal'] ) ) {
            $new_data['goal'] = wp_kses_post( $_POST['goal'] );
            update_post_meta( $post_id, '_curriculum_goal', $new_data['goal'] );
        } else {
            $new_data['goal'] = $old_data['goal'];
        }
        if ( isset( $_POST['notes'] ) ) {
            $new_data['notes'] = wp_kses_post( $_POST['notes'] );
            update_post_meta( $post_id, '_curriculum_notes', $new_data['notes'] );
        } else {
            $new_data['notes'] = $old_data['notes'];
        }
        if ( isset( $_POST['status'] ) ) {
            $new_data['status'] = sanitize_text_field( $_POST['status'] );
            update_post_meta( $post_id, '_curriculum_status', $new_data['status'] );
        } else {
            $new_data['status'] = $old_data['status'];
        }
        if ( isset( $_POST['studio_id'] ) ) {
            $new_data['studio_id'] = absint( $_POST['studio_id'] );
            update_post_meta( $post_id, '_studio_id', $new_data['studio_id'] );
        } else {
            $new_data['studio_id'] = $old_data['studio_id'];
        }

        // Log changes
        $changes = array();
        foreach ( $new_data as $key => $value ) {
            if ( $value !== $old_data[$key] ) {
                $changes[$key] = array( 'old' => $old_data[$key], 'new' => $value );
            }
        }
        if ( $changes ) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'courscribe_curriculum_log',
                array(
                    'curriculum_id' => $post_id,
                    'user_id' => get_current_user_id(),
                    'action' => 'update',
                    'changes' => wp_json_encode( $changes ),
                    'timestamp' => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%s' )
            );
            error_log( 'Courscribe: AJAX logged changes for curriculum ' . $post_id );
        }

        wp_send_json_success( array( 'message' => 'Curriculum saved successfully' ) );
        error_log( 'Courscribe: AJAX curriculum ' . $post_id . ' saved successfully' );
    }

    /**
     * AJAX handler for archiving curriculum
     */
    public function ajax_archive_curriculum() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'courscribe_archive_curriculum' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            error_log( 'Courscribe: AJAX archive curriculum failed - invalid nonce' );
        }
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! current_user_can( 'edit_crscribe_curriculum', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
            error_log( 'Courscribe: AJAX archive curriculum failed - permission denied for post ' . $post_id );
        }

        update_post_meta( $post_id, '_curriculum_status', 'archived' );
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'courscribe_curriculum_log',
            array(
                'curriculum_id' => $post_id,
                'user_id' => get_current_user_id(),
                'action' => 'archive',
                'changes' => wp_json_encode( array( 'status' => 'archived' ) ),
                'timestamp' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );

        wp_send_json_success( array( 'message' => 'Curriculum archived successfully' ) );
        error_log( 'Courscribe: AJAX curriculum ' . $post_id . ' archived successfully' );
    }

    /**
     * AJAX handler for deleting curriculum
     */
    public function ajax_delete_curriculum() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'courscribe_delete_curriculum' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            error_log( 'Courscribe: AJAX delete curriculum failed - invalid nonce' );
        }
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! current_user_can( 'delete_crscribe_curriculum', $post_id ) && ! in_array( 'studio_admin', wp_get_current_user()->roles ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
            error_log( 'Courscribe: AJAX delete curriculum failed - permission denied for post ' . $post_id );
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'courscribe_curriculum_log',
            array(
                'curriculum_id' => $post_id,
                'user_id' => get_current_user_id(),
                'action' => 'delete',
                'changes' => wp_json_encode( array( 'status' => 'deleted' ) ),
                'timestamp' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );
        wp_delete_post( $post_id, true );

        wp_send_json_success( array( 'message' => 'Curriculum deleted successfully' ) );
        error_log( 'Courscribe: AJAX curriculum ' . $post_id . ' deleted successfully' );
    }

    /**
     * Add studio filter to admin
     */
    public function add_studio_filter( $post_type ) {
        if ( ! in_array( $post_type, ['crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'] ) ) {
            return;
        }
        $studios = get_posts( array(
            'post_type' => 'crscribe_studio',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        $selected = isset( $_GET['studio_id'] ) ? absint( $_GET['studio_id'] ) : 0;
        ?>
        <select name="studio_id">
            <option value="">All Studios</option>
            <?php foreach ( $studios as $studio ) : ?>
                <option value="<?php echo esc_attr( $studio->ID ); ?>" <?php selected( $selected, $studio->ID ); ?>>
                    <?php echo esc_html( $studio->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Filter curriculums by studio
     */
    public function filter_curriculum_by_studio( $query ) {
        if ( is_admin() && $query->is_main_query() && in_array( $query->get( 'post_type' ), ['crscribe_curriculum', 'crscribe_course', 'crscribe_module', 'crscribe_lesson'] ) ) {
            if ( isset( $_GET['studio_id'] ) && ! empty( $_GET['studio_id'] ) ) {
                $meta_query = array();
                if ( $query->get( 'post_type' ) === 'crscribe_curriculum' ) {
                    $meta_query[] = array(
                        'key' => '_studio_id',
                        'value' => absint( $_GET['studio_id'] ),
                        'compare' => '=',
                    );
                } elseif ( $query->get( 'post_type' ) === 'crscribe_course' ) {
                    $meta_query[] = array(
                        'key' => '_curriculum_id',
                        'value' => get_posts( array(
                            'post_type' => 'crscribe_curriculum',
                            'meta_key' => '_studio_id',
                            'meta_value' => absint( $_GET['studio_id'] ),
                            'fields' => 'ids',
                            'posts_per_page' => -1,
                        ) ),
                        'compare' => 'IN',
                    );
                } elseif ( $query->get( 'post_type' ) === 'crscribe_module' ) {
                    $meta_query[] = array(
                        'key' => '_course_id',
                        'value' => get_posts( array(
                            'post_type' => 'crscribe_course',
                            'meta_key' => '_curriculum_id',
                            'meta_value' => get_posts( array(
                                'post_type' => 'crscribe_curriculum',
                                'meta_key' => '_studio_id',
                                'meta_value' => absint( $_GET['studio_id'] ),
                                'fields' => 'ids',
                                'posts_per_page' => -1,
                            ) ),
                            'fields' => 'ids',
                            'posts_per_page' => -1,
                        ) ),
                        'compare' => 'IN',
                    );
                } elseif ( $query->get( 'post_type' ) === 'crscribe_lesson' ) {
                    $meta_query[] = array(
                        'key' => '_module_id',
                        'value' => get_posts( array(
                            'post_type' => 'crscribe_module',
                            'meta_key' => '_course_id',
                            'meta_value' => get_posts( array(
                                'post_type' => 'crscribe_course',
                                'meta_key' => '_curriculum_id',
                                'meta_value' => get_posts( array(
                                    'post_type' => 'crscribe_curriculum',
                                    'meta_key' => '_studio_id',
                                    'meta_value' => absint( $_GET['studio_id'] ),
                                    'fields' => 'ids',
                                    'posts_per_page' => -1,
                                ) ),
                                'fields' => 'ids',
                                'posts_per_page' => -1,
                            ) ),
                            'fields' => 'ids',
                            'posts_per_page' => -1,
                        ) ),
                        'compare' => 'IN',
                    );
                }
                $query->set( 'meta_query', $meta_query );
            }
        }
    }

    /**
     * Set custom columns for curriculum
     */
    public function set_custom_columns( $columns ) {
        $new_columns = array();
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }
        $new_columns['studio'] = 'Studio';
        $new_columns['creator'] = 'Creator';
        $new_columns['status'] = 'Status';
        foreach ( $columns as $key => $value ) {
            if ( ! isset( $new_columns[$key] ) ) {
                $new_columns[$key] = $value;
            }
        }
        return $new_columns;
    }

    /**
     * Display custom column content for curriculum
     */
    public function custom_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'studio':
                $studio_id = get_post_meta( $post_id, '_studio_id', true );
                if ( $studio_id ) {
                    $studio = get_post( $studio_id );
                    echo $studio ? '<a href="' . get_edit_post_link( $studio_id ) . '">' . esc_html( $studio->post_title ) . '</a>' : '—';
                } else {
                    echo '—';
                }
                break;
            case 'creator':
                $creator_id = get_post_meta( $post_id, '_creator_id', true );
                echo $creator_id ? esc_html( get_userdata( $creator_id )->display_name ) : '—';
                break;
            case 'status':
                $status = get_post_meta( $post_id, '_curriculum_status', true ) ?: 'draft';
                $status_labels = array(
                    'draft' => '<span class="courscribe-status courscribe-status-draft">Draft</span>',
                    'review' => '<span class="courscribe-status courscribe-status-review">Review</span>',
                    'approved' => '<span class="courscribe-status courscribe-status-approved">Approved</span>',
                    'published' => '<span class="courscribe-status courscribe-status-published">Published</span>',
                    'archived' => '<span class="courscribe-status courscribe-status-archived">Archived</span>',
                );
                echo isset( $status_labels[$status] ) ? $status_labels[$status] : esc_html( $status );
                break;
        }
    }

    /**
     * Set custom columns for course
     */
    public function set_course_columns( $columns ) {
        $new_columns = array();
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }
        $new_columns['curriculum'] = 'Curriculum';
        $new_columns['order'] = 'Order';
        foreach ( $columns as $key => $value ) {
            if ( ! isset( $new_columns[$key] ) ) {
                $new_columns[$key] = $value;
            }
        }
        return $new_columns;
    }

    /**
     * Display custom column content for course
     */
    public function course_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'curriculum':
                $curriculum_id = get_post_meta( $post_id, '_curriculum_id', true );
                if ( $curriculum_id ) {
                    $curriculum = get_post( $curriculum_id );
                    echo $curriculum ? '<a href="' . get_edit_post_link( $curriculum_id ) . '">' . esc_html( $curriculum->post_title ) . '</a>' : '—';
                } else {
                    echo '—';
                }
                break;
            case 'order':
                $order = get_post_meta( $post_id, '_order', true );
                echo esc_html( $order ?: '0' );
                break;
        }
    }

    /**
     * Set custom columns for module
     */
    public function set_module_columns( $columns ) {
        $new_columns = array();
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }
        $new_columns['course'] = 'Course';
        $new_columns['order'] = 'Order';
        foreach ( $columns as $key => $value ) {
            if ( ! isset( $new_columns[$key] ) ) {
                $new_columns[$key] = $value;
            }
        }
        return $new_columns;
    }

    /**
     * Display custom column content for module
     */
    public function module_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'course':
                $course_id = get_post_meta( $post_id, '_course_id', true );
                if ( $course_id ) {
                    $course = get_post( $course_id );
                    echo $course ? '<a href="' . get_edit_post_link( $course_id ) . '">' . esc_html( $course->post_title ) . '</a>' : '—';
                } else {
                    echo '—';
                }
                break;
            case 'order':
                $order = get_post_meta( $post_id, '_order', true );
                echo esc_html( $order ?: '0' );
                break;
        }
    }

    /**
     * Set custom columns for lesson
     */
    public function set_lesson_columns( $columns ) {
        $new_columns = array();
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        if ( isset( $columns['title'] ) ) {
            $new_columns['title'] = $columns['title'];
        }
        $new_columns['module'] = 'Module';
        $new_columns['order'] = 'Order';
        foreach ( $columns as $key => $value ) {
            if ( ! isset( $new_columns[$key] ) ) {
                $new_columns[$key] = $value;
            }
        }
        return $new_columns;
    }

    /**
     * Display custom column content for lesson
     */
    public function lesson_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'module':
                $module_id = get_post_meta( $post_id, '_module_id', true );
                if ( $module_id ) {
                    $module = get_post( $module_id );
                    echo $module ? '<a href="' . get_edit_post_link( $module_id ) . '">' . esc_html( $module->post_title ) . '</a>' : '—';
                } else {
                    echo '—';
                }
                break;
            case 'order':
                $order = get_post_meta( $post_id, '_order', true );
                echo esc_html( $order ?: '0' );
                break;
        }
    }

    /**
     * Enqueue frontend scripts
     */
    public function frontend_scripts() {
        global $post;
        $is_curriculum = is_singular( 'crscribe_curriculum' ) || is_singular( 'crscribe_course' ) || is_singular( 'crscribe_module' ) || is_singular( 'crscribe_lesson' );
        $has_shortcode = $post && ( has_shortcode( $post->post_content, 'courscribe_edit' ) || has_shortcode( $post->post_content, 'courscribe_curriculum_manager' ) );

        if ( $is_curriculum || $has_shortcode ) {
            wp_enqueue_style(
                'courscribe-frontend',
                COURScribe_URL . 'assets/css/courscribe-frontend.css',
                array(),
                '1.0.0'
            );
            wp_enqueue_script(
                'courscribe-frontend',
                COURScribe_URL . 'assets/js/courscribe-frontend.js',
                array( 'jquery' ),
                '1.0.0',
                true
            );
            wp_localize_script( 'courscribe-frontend', 'courscribeVars', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'courscribe_frontend_nonce' ),
            ) );
            error_log( 'Courscribe: Enqueued frontend scripts for curriculum manager' );
        }
    }
}