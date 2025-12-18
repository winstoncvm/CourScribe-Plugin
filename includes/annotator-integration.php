<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Require JWT library
require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;



// Register REST API endpoints for Annotator Store and Token
add_action('rest_api_init', function () {
    // Token endpoint
    register_rest_route('courscribe/v1', '/token', [
        'methods' => 'GET',
        'callback' => 'courscribe_generate_token',
        'permission_callback' => 'is_user_logged_in', // Allow all logged-in users for testing
    ]);

    // Create annotation
    register_rest_route('courscribe/v1', '/annotations', [
        'methods' => 'POST',
        'callback' => 'courscribe_create_annotation',
        'permission_callback' => '__return_true', // Handled by JWT verification
    ]);

    // Update annotation
    register_rest_route('courscribe/v1', '/annotations/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'courscribe_update_annotation',
        'permission_callback' => '__return_true',
    ]);

    // Delete annotation
    register_rest_route('courscribe/v1', '/annotations/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'courscribe_delete_annotation',
        'permission_callback' => '__return_true',
    ]);

    // Search annotations
    register_rest_route('courscribe/v1', '/annotations/search', [
        'methods' => 'GET',
        'callback' => 'courscribe_search_annotations',
        'permission_callback' => 'is_user_logged_in', // Allow all logged-in users for testing
    ]);
});

// Generate JWT token
function courscribe_generate_token(WP_REST_Request $request) {
    $user = wp_get_current_user();
    if (!$user->ID) {
        error_log('Courscribe: Token generation failed: User not logged in');
        return new WP_Error('not_logged_in', 'User not logged in', ['status' => 401]);
    }

    $consumer_key = get_option('courscribe_consumer_key');
    $consumer_secret = get_option('courscribe_consumer_secret');

    // Validate or regenerate consumer secret
    if (empty($consumer_secret) || !is_string($consumer_secret)) {
        error_log('Courscribe: Invalid consumer secret, regenerating...');
        $consumer_secret = bin2hex(random_bytes(32)); // 64 characters
        update_option('courscribe_consumer_secret', $consumer_secret);
        error_log('Courscribe: Regenerated consumer secret: ' . substr($consumer_secret, 0, 8) . '...');
    }

    $ttl = 86400; // 24 hours
    $payload = [
        'consumerKey' => $consumer_key,
        'userId' => strval($user->ID),
        'issuedAt' => gmdate('c'),
        'ttl' => $ttl,
    ];

    try {
        $token = JWT::encode($payload, $consumer_secret, 'HS256');
        error_log('Courscribe: Generated JWT for user ' . $user->ID);
        return rest_ensure_response(['token' => $token]);
    } catch (Exception $e) {
        error_log('Courscribe: Failed to generate JWT: ' . $e->getMessage());
        return new WP_Error('jwt_error', 'Failed to generate token', ['status' => 500]);
    }
}

// Verify JWT token
function courscribe_verify_jwt($request) {
    $auth_header = $request->get_header('authorization');
    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        error_log('Courscribe: No authorization token provided');
        return new WP_Error('no_token', 'No authorization token provided', ['status' => 401]);
    }

    $token = $matches[1];
    $consumer_secret = get_option('courscribe_consumer_secret');

    // Validate consumer secret
    if (empty($consumer_secret) || !is_string($consumer_secret)) {
        error_log('Courscribe: Invalid consumer secret during JWT verification');
        return new WP_Error('invalid_token', 'Server configuration error', ['status' => 500]);
    }

    try {
        $decoded = JWT::decode($token, new Key($consumer_secret, 'HS256'));
        $now = time();
        $issued_at = strtotime($decoded->issuedAt);
        if ($issued_at + $decoded->ttl < $now) {
            error_log('Courscribe: Token expired for user ' . $decoded->userId);
            return new WP_Error('token_expired', 'Token has expired', ['status' => 401]);
        }

        // Verify user exists
        $user = get_user_by('ID', $decoded->userId);
        if (!$user) {
            error_log('Courscribe: Invalid user ID ' . $decoded->userId);
            return new WP_Error('invalid_user', 'Invalid user', ['status' => 403]);
        }

        // Role check disabled for testing
        /*
        $allowed_roles = ['client', 'studio_admin', 'collaborator'];
        if (!array_intersect($allowed_roles, (array) $user->roles)) {
            error_log('Courscribe: Unauthorized role for user ' . $user->ID);
            return new WP_Error('invalid_user', 'Invalid or unauthorized user', ['status' => 403]);
        }
        */

        error_log('Courscribe: JWT verified for user ' . $user->ID);
        return $user;
    } catch (Exception $e) {
        error_log('Courscribe: JWT verification failed: ' . $e->getMessage());
        return new WP_Error('invalid_token', 'Invalid token: ' . $e->getMessage(), ['status' => 401]);
    }
}

// Create annotation
function courscribe_create_annotation(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $params = $request->get_json_params();

    // Verify JWT
    $user = courscribe_verify_jwt($request);
    if (is_wp_error($user)) {
        return $user;
    }

    // Validate required fields
    if (empty($params['post_id']) || empty($params['post_type'])) {
        error_log('Courscribe: Missing required fields in annotation request');
        return new WP_Error('invalid_data', 'Missing required fields', ['status' => 400]);
    }

    // Set default field_id if not provided
    $field_id = !empty($params['field_id']) ? sanitize_text_field($params['field_id']) : 'body';

    // Curriculum access check disabled for testing
    /*
    $curriculum_id = ($params['post_type'] === 'crscribe_curriculum') ? $params['post_id'] : get_post_meta($params['post_id'], '_curriculum_id', true);
    if ($curriculum_id) {
        $invite = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}courscribe_client_invites WHERE curriculum_id = %d AND email = %s AND status = 'Accepted' AND expires_at > %s",
            $curriculum_id,
            $user->user_email,
            current_time('mysql')
        ));
        if (!$invite && in_array('client', (array) $user->roles)) {
            error_log('Courscribe: No access to curriculum ' . $curriculum_id . ' for user ' . $user->ID);
            return new WP_Error('no_access', 'No access to this content', ['status' => 403]);
        }
    } else {
        error_log('Courscribe: No associated curriculum for post ' . $params['post_id']);
        return new WP_Error('invalid_curriculum', 'No associated curriculum found', ['status' => 400]);
    }
    */

    $data = [
        'post_id' => intval($params['post_id']),
        'post_type' => sanitize_text_field($params['post_type']),
        'field_id' => $field_id,
        'annotation_data' => wp_json_encode($params['annotation']),
        'user_id' => $user->ID,
        'status' => 'pending',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ];

    $result = $wpdb->insert($table_name, $data);
    if ($result === false) {
        error_log('Courscribe: Failed to create annotation: ' . $wpdb->last_error);
        return new WP_Error('db_error', 'Failed to create annotation', ['status' => 500]);
    }

    $data['id'] = $wpdb->insert_id;
    error_log('Courscribe: Annotation created with ID ' . $data['id'] . ' for user ' . $user->ID);
    return rest_ensure_response($data);
}

// Update annotation
function courscribe_update_annotation(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $id = intval($request['id']);

    // Verify JWT
    $user = courscribe_verify_jwt($request);
    if (is_wp_error($user)) {
        return $user;
    }

    // Verify annotation ownership
    $annotation = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table_name WHERE id = %d", $id));
    if (!$annotation || $annotation->user_id != $user->ID) {
        error_log('Courscribe: User ' . $user->ID . ' not authorized to update annotation ' . $id);
        return new WP_Error('no_access', 'Not authorized to update this annotation', ['status' => 403]);
    }

    $params = $request->get_json_params();
    $data = [
        'annotation_data' => wp_json_encode($params['annotation']),
        'updated_at' => current_time('mysql'),
    ];

    $result = $wpdb->update($table_name, $data, ['id' => $id]);
    if ($result === false) {
        error_log('Courscribe: Failed to update annotation: ' . $wpdb->last_error);
        return new WP_Error('db_error', 'Failed to update annotation', ['status' => 500]);
    }

    $params['id'] = $id;
    error_log('Courscribe: Annotation ' . $id . ' updated by user ' . $user->ID);
    return rest_ensure_response($params);
}

// Delete annotation
function courscribe_delete_annotation(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $id = intval($request['id']);

    // Verify JWT
    $user = courscribe_verify_jwt($request);
    if (is_wp_error($user)) {
        return $user;
    }

    // Verify annotation ownership
    $annotation = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table_name WHERE id = %d", $id));
    if (!$annotation || $annotation->user_id != $user->ID) {
        error_log('Courscribe: User ' . $user->ID . ' not authorized to delete annotation ' . $id);
        return new WP_Error('no_access', 'Not authorized to delete this annotation', ['status' => 403]);
    }

    $result = $wpdb->delete($table_name, ['id' => $id]);
    if ($result === false) {
        error_log('Courscribe: Failed to delete annotation: ' . $wpdb->last_error);
        return new WP_Error('db_error', 'Failed to delete annotation', ['status' => 500]);
    }

    error_log('Courscribe: Annotation ' . $id . ' deleted by user ' . $user->ID);
    return rest_ensure_response(['success' => true]);
}

// Search annotations
function courscribe_search_annotations(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'courscribe_annotations';
    $post_id = intval($request->get_param('post_id'));
    $post_type = sanitize_text_field($request->get_param('post_type'));

    $query = "SELECT * FROM $table_name WHERE 1=1";
    $params = [];

    if ($post_id) {
        $query .= " AND post_id = %d";
        $params[] = $post_id;
    }
    if ($post_type) {
        $query .= " AND post_type = %s";
        $params[] = $post_type;
    }

    $results = $wpdb->get_results($wpdb->prepare($query, $params));
    $annotations = [];
    foreach ($results as $row) {
        $row->annotation_data = json_decode($row->annotation_data, true);
        $annotations[] = $row;
    }

    error_log('Courscribe: Searched annotations for post_id ' . $post_id . ', post_type ' . $post_type);
    return rest_ensure_response(['rows' => $annotations]);
}

// Enqueue Annotator scripts and initialize
add_action('wp_enqueue_scripts', function () {
    if ((is_singular(['crscribe_curriculum', 'crscribe_course']) || is_page()) && is_user_logged_in()) {
        wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js', [], '1.7', true);
        wp_enqueue_script('annotator', plugin_dir_url(__FILE__) . '../assets/js/annotator-full.min.js', ['jquery'], '1.2.10', true);
        wp_enqueue_style('annotator', plugin_dir_url(__FILE__) . '../assets/css/annotator.min.css', [], '1.2.10');

        // Localize script with REST API endpoint and user data
        $user = wp_get_current_user();
        $user_data = $user->ID ? [
            'id' => $user->ID,
            'name' => $user->display_name,
        ] : null;

        wp_localize_script('annotator', 'CourscribeAnnotator', [
            'apiUrl' => rest_url('courscribe/v1/annotations'),
            'searchUrl' => rest_url('courscribe/v1/annotations/search'),
            'tokenUrl' => rest_url('courscribe/v1/token'),
            'postId' => get_the_ID(),
            'postType' => get_post_type(),
            'user' => $user_data,
            'nonce' => wp_create_nonce('wp_rest'),
            'isClient' => in_array('client', (array) $user->roles),
            'loadAnnotations' => in_array('client', (array) $user->roles), // Load annotations automatically for clients
        ]);

        // Define ReadOnly plugin
        $read_only_script = <<<JS
(function($) {
    $.fn.annotator.Constructor.Plugin.ReadOnly = function(element, options) {
        var plugin = this;
        plugin.readOnly = options.readOnly || false;
        plugin.on('annotationViewerShown', function(viewer, annotations) {
            if (plugin.readOnly) {
                viewer.element.find('.annotator-controls').hide();
            }
        });
    };
    $.fn.annotator.Constructor.Plugin.ReadOnly.prototype = new $.fn.annotator.Constructor.Plugin();
})(jQuery);
JS;
        wp_add_inline_script('annotator', $read_only_script, 'before');

        // Inline script to initialize Annotator
        $inline_script = <<<JS
jQuery(function($) {
    console.log('Courscribe: Initializing Annotator, nonce:', CourscribeAnnotator.nonce, 'postId:', CourscribeAnnotator.postId, 'postType:', CourscribeAnnotator.postType);
    if (typeof $.fn.annotator === 'undefined') {
        console.error('Courscribe: Annotator not loaded. Ensure annotator-full.min.js is included.');
        return;
    }

    try {
        var app = $('body').annotator();
        console.log('Courscribe: Annotator initialized on body');

        app.annotator('addPlugin', 'Auth', {
            tokenUrl: CourscribeAnnotator.tokenUrl,
            autoFetch: true,
            headers: {
                'X-WP-Nonce': CourscribeAnnotator.nonce
            }
        });

        var storeConfig = {
            prefix: CourscribeAnnotator.apiUrl,
            annotationData: {
                post_id: CourscribeAnnotator.postId,
                post_type: CourscribeAnnotator.postType,
                field_id: 'body'
            },
            urls: {
                create: '',
                update: '/:id',
                destroy: '/:id',
                search: CourscribeAnnotator.searchUrl
            }
        };

        // Load annotations only for clients initially
        if (CourscribeAnnotator.loadAnnotations) {
            storeConfig.loadFromSearch = {
                post_id: CourscribeAnnotator.postId,
                post_type: CourscribeAnnotator.postType
            };
        }

        app.annotator('setupPlugins', {}, {
            Tags: true,
            Filter: {
                addAnnotationFilter: false,
                filters: [{ label: 'Comment', property: 'text' }]
            },
            Store: storeConfig,
            Permissions: {
                user: CourscribeAnnotator.user,
                permissions: {
                    read: [],
                    update: [CourscribeAnnotator.user ? CourscribeAnnotator.user.id : 0],
                    delete: [CourscribeAnnotator.user ? CourscribeAnnotator.user.id : 0],
                    admin: [CourscribeAnnotator.user ? CourscribeAnnotator.user.id : 0]
                },
                showViewPermissionsCheckbox: false,
                showEditPermissionsCheckbox: false,
                userId: function(user) {
                    return user && user.id ? user.id : user;
                },
                userString: function(user) {
                    return user && user.name ? user.name : user;
                }
            }
        });

        if (!CourscribeAnnotator.isClient) {
            app.annotator('addPlugin', 'ReadOnly', { readOnly: true });
            // Add Show Feedback button for collaborators and studio admins
            $('body').prepend('<button id="courscribe-show-feedback" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">Show Feedback</button>');
            $('#courscribe-show-feedback').on('click', function() {
                app.annotator('loadAnnotations', {
                    post_id: CourscribeAnnotator.postId,
                    post_type: CourscribeAnnotator.postType
                });
                $(this).hide();
                console.log('Courscribe: Loading annotations for post_id:', CourscribeAnnotator.postId);
            });
        }

        console.log('Courscribe: Annotator plugins loaded', app.data('annotator').plugins);

        // Debug token fetch with retry
        function fetchToken(attempt) {
            attempt = attempt || 1;
            $.ajax({
                url: CourscribeAnnotator.tokenUrl,
                headers: { 'X-WP-Nonce': CourscribeAnnotator.nonce },
                success: function(data) {
                    console.log('Courscribe: Token fetched successfully', data);
                },
                error: function(xhr, status, error) {
                    console.error('Courscribe: Token fetch failed (attempt ' + attempt + ')', xhr.status, xhr.responseText);
                    if (attempt < 3) {
                        setTimeout(function() { fetchToken(attempt + 1); }, 1000);
                    }
                }
            });
        }
        fetchToken();
    } catch (e) {
        console.error('Courscribe: Annotator initialization failed', e);
    }
});
JS;
        wp_add_inline_script('annotator', $inline_script);
    }
});

// Filter curriculum shortcode output to add annotatable fields
add_filter('courscribe_curriculum_shortcode_output', function ($output, $atts, $curriculum) {
    if (!is_user_logged_in()) {
        return $output;
    }

    // Wrap entire output in annotatable div
    $new_output = '<div class="annotatable-field" id="curriculum-content" data-field-id="post_content">' . $output . '</div>';

    return $new_output;
}, 10, 3);

// Filter course fields output to add annotatable fields
add_filter('courscribe_course_fields_output', function ($output, $atts, $course, $field_id) {
    if (!is_user_logged_in()) {
        return $output;
    }

    // Generate unique ID based on field and course
    $field_slug = str_replace('_', '-', $field_id);
    $unique_id = 'course-' . $field_slug . '-' . esc_attr($course->ID);

    // Wrap output in annotatable div
    $new_output = '<div class="annotatable-field" id="' . $unique_id . '" data-field-id="' . esc_attr($field_id) . '">' . $output . '</div>';

    return $new_output;
}, 10, 4);
?>