<?php
// courscribe-dashboard/templates/stepper.php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stepper template
 *
 * @param array $steps Array of step labels
 * @param int $currentStep Current active step index
 * @param string $site_url Site URL for assets
 * @param array $icons Optional array of icon URLs for each step/state
 */
function courscribe_render_stepper($args = []) {
    // Default values
    $defaults = [
        'steps' => ['Step 1', 'Step 2', 'Step 3'],
        'currentStep' => 0,
        'site_url' => home_url(), // Ensure default is home_url()
        'icons' => [
            'curriculum' => [
                'active' => home_url() . '/wp-content/uploads/2024/12/curriculum-active.png',
                'complete' => home_url() . '/wp-content/uploads/2024/12/curriculum-active.png',
            ],
            'course' => [
                'active' => home_url() . '/wp-content/uploads/2024/12/course-active.png',
                'complete' => home_url() . '/wp-content/uploads/2024/12/course-active.png',
                'inactive' => home_url() . '/wp-content/uploads/2024/12/course-inactive.png',
            ],
            'module' => [
                'active' => home_url() . '/wp-content/uploads/2024/12/module-active.png',
                'complete' => home_url() . '/wp-content/uploads/2024/12/module-active.png',
                'inactive' => home_url() . '/wp-content/uploads/2024/12/module-inactive.png',
            ],
            'lesson' => [
                'active' => home_url() . '/wp-content/uploads/2024/12/lesson-active.png',
                'complete' => home_url() . '/wp-content/uploads/2024/12/lesson-active.png',
                'inactive' => home_url() . '/wp-content/uploads/2024/12/lesson-inactive.png',
            ],
            'teachingPoint' => [
                'active' => home_url() . '/wp-content/uploads/2024/12/teaching-point-active.png',
                'inactive' => home_url() . '/wp-content/uploads/2024/12/teaching-point-inactive.png',
            ],
        ],
    ];

    $args = wp_parse_args($args, $defaults);
    $steps = $args['steps'];
    $currentStep = absint($args['currentStep']); // Sanitize
    $site_url = esc_url_raw($args['site_url']);
    $icons = $args['icons'];
    $icon_keys = array_keys($icons); // For mapping step index to icon key
    ?>

    <div class="stepper">
        <div class="stepper-container">
            <?php foreach ($steps as $index => $label): ?>
                <div class="step <?php echo ($index == $currentStep) ? 'active' : ''; ?> <?php echo ($index < $currentStep) ? 'complete' : ''; ?>" data-step="<?php echo esc_attr($index); ?>">
                    <div class="step-icon">
                        <?php
                        // Determine icon state
                        $state = ($index <= $currentStep) ? 'active' : 'inactive';
                        $icon_key = isset($icon_keys[$index]) ? $icon_keys[$index] : '';
                        $icon_url = !empty($icon_key) && isset($icons[$icon_key][$state])
                            ? esc_url($icons[$icon_key][$state])
                            : esc_url($site_url . '/wp-content/uploads/2024/12/default.png'); // Fallback
                        ?>
                        <img
                            src="<?php echo $icon_url; ?>"
                            alt="<?php echo esc_attr($label); ?>"
                            class="icon-img"
                        />
                    </div>
                    <div class="step-label">
                        <?php echo esc_html($label); ?>
                    </div>
                </div>
                <?php if ($index < count($steps) - 1): ?>
                    <div class="step-connector <?php echo ($index == $currentStep) ? 'active' : ''; ?> <?php echo ($index < $currentStep) ? 'complete' : ''; ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}