<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode for profile page
add_shortcode('courscribe_studio_settings', 'courscribe_studio_settings_shortcode');
function courscribe_studio_settings_shortcode() {
    global $wpdb;
    $site_url = home_url();
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $cache_key = 'courscribe_studio_new_new' . $current_user->ID;
    $query = wp_cache_get($cache_key);
    // Optimized WP_Query for studio
    $query_args = [
        'post_type'      => 'crscribe_studio',
        'author' => $current_user->ID,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'cache_results'  => true,
    ];
    if ($query === false) {
        $query = new WP_Query($query_args);
        wp_cache_set($cache_key, $query, '', 21); // Cache for 5 minutes
    }
    $studios = get_posts(array(
        'post_type' => 'crscribe_studio',
        'author' => $current_user->ID,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'cache_results'  => true,
    ));
    $post_id = $user_studio_id = $studios[0];
    $email = get_post_meta($post_id, '_studio_email', true);
    $website = get_post_meta($post_id, '_studio_website', true);
    $address = get_post_meta($post_id, '_studio_address', true);
    $is_public = get_post_meta($post_id, '_studio_is_public', true);

 

    if ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $email = get_post_meta($post_id, '_studio_email', true);
        $website = get_post_meta($post_id, '_studio_website', true);
        $address = get_post_meta($post_id, '_studio_address', true);
        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
        $is_public = get_post_meta($post_id, '_studio_is_public', true) ? 'Yes' : 'No';


    wp_enqueue_style(
        'courscribe_studio_settings_b_styles',
        plugin_dir_url(__FILE__) . '../../../assets/css/studio-settings.css',
        [],
        '2.1.0'
    );
    wp_enqueue_style(
        'courscribe_studio_b_styles',
        plugin_dir_url(__FILE__) . '../../../assets/css/studio.css',
        [],
        '2.1.0'
    );
    wp_enqueue_style(
        'courscribe_wrapper_b_styles',
        plugin_dir_url(__FILE__) . '../../../assets/css/wrapper.css',
        [],
        '2.1.0'
    );
    wp_enqueue_script(
        'courscribe_studio_b_js',
        plugin_dir_url(__FILE__) . '../../../assets/js/ui/sidebar.js',
        [],
        '1.0.10'
    );

    ob_start();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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
                    <a href="<?php echo esc_url($site_url); ?>/studio" >
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
                    <a href="<?php echo esc_url($site_url); ?>/profile">
                        <i class="fas fa-user"></i>
                        <span class="menu-text">Profile</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="active">
                        <i class="fas fa-cog"></i>
                        <span class="menu-text">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="content">
            <div class="courscribe_main_container">
                <?php if (in_array('studio_admin', $user_roles)) : ?>
                    <div class="courscribe_container">
                        <div class="courscribe_floating_elements">
                            <div class="courscribe_floating_element"></div>
                            <div class="courscribe_floating_element"></div>
                            <div class="courscribe_floating_element"></div>
                        </div>

                        <div class="courscribe_header">
                            <h1>Studio Settings</h1>
                            <p>Manage your curriculum development studio</p>
                        </div>

                        <form id="courscribe_studioForm" class="courscribe_settings_grid" method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('courscribe_edit_studio', 'courscribe_edit_studio_nonce'); ?>
                            <!-- Basic Information -->
                            <div class="courscribe_card">
                                <div class="courscribe_card_header">
                                    <div class="courscribe_card_icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <h3 class="courscribe_card_title">Basic Information</h3>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Studio Title</label>
                                    <input  name="courscribe_studio_title" class="courscribe_form_input" id="courscribe_studioTitle" value="<?php echo esc_attr(get_post_field('post_title', $post_id)); ?>" required>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Description</label>
                                    <textarea name="courscribe_studio_description" class="courscribe_form_textarea" id="courscribe_studioDescription"><?php echo esc_textarea(get_post_field('post_content', $post_id)); ?></textarea>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="courscribe_card">
                                <div class="courscribe_card_header">
                                    <div class="courscribe_card_icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <h3 class="courscribe_card_title">Contact Information</h3>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Contact Email</label>
                                    <input type="email" name="courscribe_studio_email" class="courscribe_form_input" id="courscribe_studioEmail" value="<?php echo esc_attr($email); ?>" required>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Website URL</label>
                                    <input type="url" name="courscribe_studio_website" class="courscribe_form_input" id="courscribe_studioWebsite" value="<?php echo esc_attr($website); ?>">
                                </div>
                            </div>

                            <!-- Location & Visibility -->
                            <div class="courscribe_card">
                                <div class="courscribe_card_header">
                                    <div class="courscribe_card_icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <h3 class="courscribe_card_title">Location & Visibility</h3>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Address</label>
                                    <textarea name="courscribe_studio_address" class="courscribe_form_textarea" id="courscribe_studioAddress"><?php echo esc_textarea($address); ?></textarea>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_checkbox_group" for="courscribe_isPublic">
                                        <div class="courscribe_form_checkbox" id="courscribe_publicCheckbox">
                                            <i class="fas fa-check" style="display: <?php echo $is_public === 'Yes' ? 'block' : 'none'; ?>;"></i>
                                        </div>
                                        <span>Make Studio Public</span>
                                    </label>
                                    <input type="checkbox" name="courscribe_studio_is_public" id="courscribe_isPublic" style="display: none;" value="1" <?php checked($is_public, 'Yes'); ?>>
                                </div>
                            </div>

                            <!-- Studio Preferences -->
                            <div class="courscribe_card">
                                <div class="courscribe_card_header">
                                    <div class="courscribe_card_icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <h3 class="courscribe_card_title">Studio Preferences</h3>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Default Curriculum Template</label>
                                    <select class="courscribe_form_select" id="courscribe_defaultTemplate" name="courscribe_default_template">
                                        <option value="standard">Standard Template</option>
                                        <option value="academic">Academic Template</option>
                                        <option value="corporate">Corporate Template</option>
                                        <option value="creative">Creative Template</option>
                                    </select>
                                </div>
                                <div class="courscribe_form_group">
                                    <label class="courscribe_form_label">Collaboration Level</label>
                                    <select class="courscribe_form_select" id="courscribe_collaborationLevel" name="courscribe_collaboration_level">
                                        <option value="invite-only">Invite Only</option>
                                        <option value="request-access">Request Access</option>
                                        <option value="open">Open Collaboration</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Subscription History -->
                            <div class="courscribe_card courscribe_subscription_card">
                                <div class="courscribe_card_header">
                                    <div class="courscribe_card_icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <h3 class="courscribe_card_title">Subscription History</h3>
                                </div>
                                <?php
                                $all_subscriptions = [];
                                $subscription_orders = wc_get_orders([
                                    'limit'  => -1,
                                    'status' => ['completed', 'processing', 'on-hold', 'wc-wps_renewal', 'cancelled', 'expired'],
                                    'type'   => 'shop_order',
                                    'customer_id' => $current_user->ID,
                                ]);

                                foreach ($subscription_orders as $order) {
                                    foreach ($order->get_items() as $item) {
                                        $product = $item->get_product();
                                        if ($product && get_post_meta($product->get_id(), '_wps_sfw_product', true) === 'yes') {
                                            $all_subscriptions[] = [
                                                'product_name' => $product->get_name(),
                                                'status' => $order->get_meta('wps_subscription_status') ?: $order->get_status(),
                                                'price' => $item->get_total(),
                                                'start_date' => $order->get_meta('wps_schedule_start') ?: $order->get_date_created()->date('Y-m-d'),
                                                'next_payment' => $order->get_meta('wps_next_payment_date') ?: 'N/A',
                                                'end_date' => $order->get_meta('wps_susbcription_end') && $order->get_meta('wps_susbcription_end') != '0' ? $order->get_meta('wps_susbcription_end') : 'Indefinite',
                                            ];
                                        }
                                    }
                                }
                                ?>
                                <?php if (empty($all_subscriptions)) : ?>
                                    <p>No subscriptions found.</p>
                                <?php else : ?>
                                    <table class="courscribe_subscription_table">
                                        <thead>
                                            <tr>
                                                <th>Plan</th>
                                                <th>Status</th>
                                                <th>Price</th>
                                                <th>Start Date</th>
                                                <th>Next Renewal</th>
                                                <th>End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_subscriptions as $sub) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($sub['product_name']); ?></td>
                                                    <td><span class="courscribe_status_badge courscribe_status_<?php echo $sub['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo esc_html(ucfirst($sub['status'])); ?></span></td>
                                                    <td><?php echo wc_price($sub['price']); ?></td>
                                                    <td><?php echo esc_html($sub['start_date']); ?></td>
                                                    <td><?php echo esc_html($sub['next_payment']); ?></td>
                                                    <td><?php echo esc_html($sub['end_date']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <div class="courscribe_action_buttons">
                                <button class="courscribe_btn courscribe_btn_secondary" type="button" onclick="courscribe_resetForm()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <button class="courscribe_btn courscribe_btn_primary" type="submit" name="courscribe_submit_edit_studio">
                                    <i class="fas fa-save"></i> Update Studio
                                </button>
                            </div>
                        </form>

                        <div class="courscribe_notification" id="courscribe_notification">
                            <i class="fas fa-check-circle"></i> Studio settings updated successfully!
                        </div>

                        <?php
                        // Studio Settings form submission
                        if (isset($_POST['courscribe_submit_edit_studio']) && wp_verify_nonce($_POST['courscribe_edit_studio_nonce'], 'courscribe_edit_studio')) {
                            if (current_user_can('edit_crscribe_studio', $post_id)) {
                                $title = sanitize_text_field($_POST['courscribe_studio_title']);
                                $description = wp_kses_post($_POST['courscribe_studio_description']);
                                $email = sanitize_email($_POST['courscribe_studio_email']);
                                $website = esc_url_raw($_POST['courscribe_studio_website']);
                                $address = sanitize_textarea_field($_POST['courscribe_studio_address']);
                                $is_public = isset($_POST['courscribe_studio_is_public']) ? 'Yes' : 'No';
                                $default_template = sanitize_text_field($_POST['courscribe_default_template']);
                                $collaboration_level = sanitize_text_field($_POST['courscribe_collaboration_level']);

                                $update_result = wp_update_post([
                                    'ID'           => $post_id,
                                    'post_title'   => $title,
                                    'post_content' => $description,
                                    'post_type'    => 'crscribe_studio',
                                ], true);

                                if (is_wp_error($update_result)) {
                                    echo '<p class="courscribe_error">Error updating studio: ' . esc_html($update_result->get_error_message()) . '</p>';
                                    error_log('Courscribe: Failed to update studio ' . $post_id . ': ' . $update_result->get_error_message());
                                } else {
                                    update_post_meta($post_id, '_studio_email', $email);
                                    update_post_meta($post_id, '_studio_website', $website);
                                    update_post_meta($post_id, '_studio_address', $address);
                                    update_post_meta($post_id, '_studio_is_public', $is_public);
                                    update_post_meta($post_id, '_studio_default_template', $default_template);
                                    update_post_meta($post_id, '_studio_collaboration_level', $collaboration_level);

                                    wp_cache_delete('studio_' . $post_id);
                                    wp_cache_flush();

                                    echo '<script>document.getElementById("courscribe_notification").classList.add("show"); setTimeout(() => { document.getElementById("courscribe_notification").classList.remove("show"); }, 3000);</script>';
                                    error_log('Courscribe: Studio ' . $post_id . ' updated successfully by user ' . $current_user->ID);
                                }
                            } else {
                                echo '<p class="courscribe_error">You do not have permission to edit this studio.</p>';
                                error_log('Courscribe: User ' . $current_user->ID . ' attempted to edit studio ' . $post_id . ' without permission');
                            }
                        }
                        ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Checkbox animation
        const courscribe_checkbox = document.getElementById('courscribe_isPublic');
        const courscribe_checkboxVisual = document.getElementById('courscribe_publicCheckbox');
        const courscribe_checkIcon = courscribe_checkboxVisual.querySelector('i');

        document.querySelector('.courscribe_form_checkbox_group').addEventListener('click', function() {
            courscribe_checkbox.checked = !courscribe_checkbox.checked;
            
            if (courscribe_checkbox.checked) {
                courscribe_checkboxVisual.classList.add('checked');
                courscribe_checkIcon.style.display = 'block';
            } else {
                courscribe_checkboxVisual.classList.remove('checked');
                courscribe_checkIcon.style.display = 'none';
            }
        });

        // Form submission
        document.getElementById('courscribe_studioForm').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.courscribe_btn_primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });

        // Reset form
        function courscribe_resetForm() {
            document.getElementById('courscribe_studioForm').reset();
            courscribe_checkbox.checked = false;
            courscribe_checkboxVisual.classList.remove('checked');
            courscribe_checkIcon.style.display = 'none';
        }

        // Input focus animations
        document.querySelectorAll('.courscribe_form_input, .courscribe_form_textarea, .courscribe_form_select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
    <?php
    } else {
        return ob_get_clean() . courscribe_retro_error("No studio found.");
    }
    wp_reset_postdata();
    return ob_get_clean();
}
?>