<?php
// /includes/class-courscribe-tooltips.php

if (!defined('ABSPATH')) exit;

class CourScribe_Tooltips {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        // Enqueue Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        // Enqueue tooltip styles
        wp_enqueue_style(
            'courscribe-tooltips',
            plugin_dir_url(__FILE__) . '../assets/css/tooltips.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . '../assets/css/tooltips.css')
        );

        // Enqueue tooltip scripts
        wp_enqueue_script(
            'courscribe-tooltips',
            plugin_dir_url(__FILE__) . '../assets/js/tooltips.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/tooltips.js'),
            true
        );

        // Localize script with user package info
        wp_localize_script('courscribe-tooltips', 'courscribeTooltipData', [
            'userPackage' => $this->get_user_package(),
            'packageLevels' => [
                'CourScribe Basics' => 1,
                'CourScribe + (Plus)' => 2,
                'CourScribe Pro (Agency)' => 3
            ]
        ]);
    }

    public function get_user_package() {
        // Implement your actual package check logic here
        return 'CourScribe Basics'; // Default fallback
    }

    /**
     * Wraps a button with tooltip functionality
     *
     * @param string $button_html The full button HTML to wrap
     * @param array $args {
     *     @type string $description   Tooltip description text
     *     @type string $title         Tooltip title (default: "Package Requirement")
     *     @type string $required_package Required package level
     * }
     * @return string HTML with wrapped tooltip
     */
    public function wrap_button_with_tooltip($button_html, $args = []) {
        $defaults = [
            'description' => '',
            'title' => 'Package Requirement',
            'required_package' => 'CourScribe Basics'
        ];

        $args = wp_parse_args($args, $defaults);

        // Extract the button attributes to preserve them
        preg_match('/<button\s([^>]*)>/', $button_html, $matches);
        $button_attrs = isset($matches[1]) ? $matches[1] : '';

        // Get button content (inner HTML)
        preg_match('/<button[^>]*>(.*?)<\/button>/s', $button_html, $content_matches);
        $button_content = isset($content_matches[1]) ? $content_matches[1] : '';

        // Determine access
        $user_package = $this->get_user_package();
        $has_access = $this->compare_packages($user_package, $args['required_package']);

        ob_start();
        ?>
        <div class="courscribe-tooltip relative inline-block group"
             data-required-package="<?php echo esc_attr($args['required_package']); ?>"
             data-title="<?php echo esc_attr($args['title']); ?>"
             data-description="<?php echo esc_attr($args['description']); ?>">

            <!-- Reconstruct button with original attributes -->
            <button <?php echo $button_attrs; ?>>
                <?php echo $button_content; ?>
            </button>

            <!-- Tooltip template will be populated by JavaScript -->
        </div>
        <?php
        return ob_get_clean();
    }

    private function compare_packages($user_package, $required_package) {
        $levels = [
            'CourScribe Basics' => 1,
            'CourScribe + (Plus)' => 2,
            'CourScribe Pro (Agency)' => 3
        ];

        $user_level = $levels[$user_package] ?? 0;
        $required_level = $levels[$required_package] ?? 0;

        return $user_level >= $required_level;
    }
}