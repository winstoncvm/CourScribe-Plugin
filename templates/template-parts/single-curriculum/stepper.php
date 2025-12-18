<?php
// Path: templates/template-parts/single-curriculum/stepper.php
if (!defined('ABSPATH')) {
    exit;
}

courscribe_render_stepper([
    'steps' => $steps,
    'currentStep' => $currentStep,
    'site_url' => $site_url,
    'icons' => [
        'curriculum' => [
            'active' => $site_url . '/wp-content/uploads/2024/12/curriculum-active.png',
            'complete' => $site_url . '/wp-content/uploads/2024/12/curriculum-active.png',
        ],
        'course' => [
            'active' => $site_url . '/wp-content/uploads/2024/12/course-active.png',
            'complete' => $site_url . '/wp-content/uploads/2024/12/course-active.png',
            'inactive' => $site_url . '/wp-content/uploads/2024/12/course-inactive.png',
        ],
        'module' => [
            'active' => $site_url . '/wp-content/uploads/2024/12/module-active.png',
            'complete' => $site_url . '/wp-content/uploads/2024/12/module-active.png',
            'inactive' => $site_url . '/wp-content/uploads/2024/12/module-inactive.png',
        ],
        'lesson' => [
            'active' => $site_url . '/wp-content/uploads/2024/12/lesson-active.png',
            'complete' => $site_url . '/wp-content/uploads/2024/12/lesson-active.png',
            'inactive' => $site_url . '/wp-content/uploads/2024/12/lesson-inactive.png',
        ],
        'teachingPoint' => [
            'active' => $site_url . '/wp-content/uploads/2024/12/teaching-point-active.png',
            'inactive' => $site_url . '/wp-content/uploads/2024/12/teaching-point-inactive.png',
        ],
    ],
]);
?>