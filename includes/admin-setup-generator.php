<?php
/**
 * Admin Setup URL Generator
 * Simple page to generate Toni's admin setup URL
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for generating setup URL
add_action('admin_menu', 'courscribe_add_setup_generator_menu');
function courscribe_add_setup_generator_menu() {
    // Only show this menu if admin setup hasn't been completed
    if (!get_option('courscribe_admin_setup_completed', false)) {
        add_management_page(
            'Generate Toni Setup URL',
            'Toni Admin Setup',
            'manage_options',
            'courscribe-generate-setup',
            'courscribe_setup_generator_page'
        );
    }
}

function courscribe_setup_generator_page() {
    // Handle URL generation
    $setup_url = '';
    $message = '';
    $error = '';
    
    if (isset($_POST['generate_setup_url']) && wp_verify_nonce($_POST['_wpnonce'], 'generate_setup_url')) {
        if (get_option('courscribe_admin_setup_completed', false)) {
            $error = 'Admin setup has already been completed.';
        } else {
            $setup_url = courscribe_enable_admin_setup();
            $message = 'Setup URL generated successfully!';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Generate Toni's Admin Setup URL</h1>
        
        <div class="card" style="max-width: 800px;">
            <h2>CourScribe Admin Setup</h2>
            <p>This tool generates a secure, one-time URL for Toni to set up her admin account on the CourScribe platform.</p>
            
            <?php if ($error): ?>
                <div class="notice notice-error">
                    <p><strong>Error:</strong> <?php echo esc_html($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="notice notice-success">
                    <p><strong><?php echo esc_html($message); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($setup_url): ?>
                <div style="background: #f0f6fc; border: 1px solid #0969da; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #0969da;">
                        <span class="dashicons dashicons-admin-links"></span> 
                        Toni's Setup URL
                    </h3>
                    <div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid #d1d9e0; font-family: monospace; word-break: break-all; margin-bottom: 15px;">
                        <?php echo esc_url($setup_url); ?>
                    </div>
                    <button class="button button-secondary" onclick="copyToClipboard('<?php echo esc_js($setup_url); ?>')">
                        <span class="dashicons dashicons-admin-page"></span> 
                        Copy URL
                    </button>
                    <a href="<?php echo esc_url($setup_url); ?>" class="button button-primary" target="_blank" style="margin-left: 10px;">
                        <span class="dashicons dashicons-external"></span>
                        Open Setup Page
                    </a>
                </div>
                
                <div class="notice notice-info">
                    <h4>Important Instructions:</h4>
                    <ul>
                        <li><strong>Security:</strong> This URL contains a secret key. Share it securely with Toni only.</li>
                        <li><strong>One-time use:</strong> The URL becomes inactive after Toni completes the setup.</li>
                        <li><strong>Clean up:</strong> Delete the page containing the shortcode after Toni uses it.</li>
                        <li><strong>Premium experience:</strong> The setup page is personalized for Toni with premium styling.</li>
                    </ul>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <?php wp_nonce_field('generate_setup_url'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Admin Setup Status</th>
                            <td>
                                <?php if (get_option('courscribe_admin_setup_completed', false)): ?>
                                    <span style="color: #00a32a; font-weight: bold;">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        Completed
                                    </span>
                                    <p class="description">Admin setup has been completed. No further action needed.</p>
                                <?php else: ?>
                                    <span style="color: #d63638; font-weight: bold;">
                                        <span class="dashicons dashicons-clock"></span>
                                        Pending
                                    </span>
                                    <p class="description">Ready to generate Toni's setup URL.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Setup Page</th>
                            <td>
                                <p>Create a WordPress page with the shortcode: <code>[courscribe_admin_setup]</code></p>
                                <p class="description">The page should be private/password protected or use a hard-to-guess URL.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (!get_option('courscribe_admin_setup_completed', false)): ?>
                        <p class="submit">
                            <input type="submit" name="generate_setup_url" class="button-primary" value="Generate Setup URL for Toni" />
                        </p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="dashicons dashicons-yes"></span> Copied!';
            button.style.backgroundColor = '#00a32a';
            button.style.color = 'white';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.backgroundColor = '';
                button.style.color = '';
            }, 2000);
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            alert('Copy failed. Please select and copy the URL manually.');
        });
    }
    </script>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
    }
    </style>
    <?php
}
?>