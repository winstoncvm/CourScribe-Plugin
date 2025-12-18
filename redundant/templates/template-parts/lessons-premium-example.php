<?php
/**
 * Example Usage of Premium Lessons Template
 * 
 * This file shows how to integrate and use the new premium lessons template
 * Replace your existing lessons.php calls with lessons-premium.php
 */

// Example: In your main curriculum template or wherever lessons are displayed
if (!defined('ABSPATH')) {
    exit;
}

// Example usage in your main template file (e.g., curriculums/shortcodes/courscribe_curriculum_manager_shortcode.php)
function example_usage_premium_lessons() {
    // Include the premium lessons template
    require_once COURSCRIBE_PLUGIN_PATH . 'templates/template-parts/lessons-premium.php';
    
    // Your existing course and curriculum data
    $course_id = 123; // Your course ID
    $course_title = 'Introduction to Web Development'; // Your course title
    $curriculum_id = 456; // Your curriculum ID
    $tooltips = new CourScribe_Tooltips(); // Your tooltips instance
    $site_url = home_url(); // Your site URL
    
    // Call the premium lessons renderer
    courscribe_render_lessons([
        'course_id' => $course_id,
        'course_title' => $course_title,
        'curriculum_id' => $curriculum_id,
        'tooltips' => $tooltips,
        'site_url' => $site_url,
    ]);
}

// Example: How to enqueue the premium assets in your main plugin file or assets class
function enqueue_premium_lessons_assets() {
    // Enqueue the premium lessons CSS
    wp_enqueue_style(
        'courscribe-lessons-premium',
        COURSCRIBE_PLUGIN_URL . 'assets/css/lessons-premium.css',
        ['courscribe-main-styles'], // Depend on your main styles
        '1.0.0'
    );
    
    // Enqueue the premium lessons JavaScript
    wp_enqueue_script(
        'courscribe-lessons-premium',
        COURSCRIBE_PLUGIN_URL . 'assets/js/courscribe/lessons-premium.js',
        ['jquery', 'courscribe-main-js'], // Dependencies
        '1.0.0',
        true
    );
    
    // Localize script with necessary data
    wp_localize_script('courscribe-lessons-premium', 'courscribeAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'lesson_nonce' => wp_create_nonce('courscribe_lesson_premium_nonce'),
        'site_url' => home_url(),
    ]);
}

// Hook the assets enqueuing (add this to your main plugin file)
// add_action('wp_enqueue_scripts', 'enqueue_premium_lessons_assets');

// Example: Migration from old lessons template to premium
function migrate_to_premium_lessons() {
    /*
    BEFORE (old lessons.php):
    
    function c($args = []) {
        // Old implementation
    }
    
    AFTER (lessons-premium.php):
    
    function courscribe_render_lessons($args = []) {
        // Premium implementation with enhanced features
    }
    
    CHANGES NEEDED IN YOUR EXISTING CODE:
    
    1. Replace function calls:
       OLD: c($args)
       NEW: courscribe_render_lessons($args)
    
    2. Update CSS classes in your templates:
       OLD: .courscribe-lessons
       NEW: .cs-lessons-premium
    
    3. Include premium action handlers:
       require_once COURSCRIBE_PLUGIN_PATH . 'actions/courscribe-lessons-premium-actions.php';
    
    4. Update stepper.js (already done in this implementation)
    */
}

// Example: How to customize the premium lessons appearance
function customize_premium_lessons_styles() {
    /*
    You can add custom CSS to override the premium styles:
    
    .cs-lessons-premium {
        // Your custom styling
    }
    
    .cs-premium-input {
        // Customize input appearance
    }
    
    .cs-btn-primary {
        // Customize button colors to match your brand
    }
    */
}

// Example: How to extend premium lessons functionality
function extend_premium_lessons_functionality() {
    /*
    You can extend the JavaScript functionality by listening to custom events:
    
    jQuery(document).on('courscribe:stepChanged', function(event, data) {
        if (data.stepName === 'lesson') {
            // Custom logic when lessons step is activated
        }
    });
    
    // Or access the global instance:
    if (window.courscribleLessonsPremium) {
        // Access premium lessons methods
        window.courscribleLessonsPremium.showNotification('info', 'Custom message');
    }
    */
}

/**
 * INTEGRATION CHECKLIST:
 * 
 * 1. ✅ Replace lessons.php with lessons-premium.php
 * 2. ✅ Update function calls from c() to courscribe_render_lessons()
 * 3. ✅ Include premium action handlers file
 * 4. ✅ Enqueue premium CSS and JavaScript files
 * 5. ✅ Update stepper.js to handle premium lessons
 * 6. ✅ Test auto-save functionality
 * 7. ✅ Test archive/restore functionality
 * 8. ✅ Test activity logs viewing
 * 9. ✅ Test form validation
 * 10. ✅ Test responsive design on mobile devices
 * 
 * FEATURES INCLUDED:
 * 
 * ✅ Real-time auto-save with visual indicators
 * ✅ Advanced form validation with live feedback
 * ✅ Archive/restore lesson functionality
 * ✅ Comprehensive activity log viewer with restore capabilities
 * ✅ Premium UI with smooth animations
 * ✅ Proper nonce security and error handling
 * ✅ Modular component structure following development guidelines
 * ✅ Character counters and input limits
 * ✅ Notification system for user feedback
 * ✅ Responsive design for all devices
 * ✅ Accessibility considerations
 * ✅ Integration with existing CourScribe ecosystem
 */

/**
 * USAGE NOTES:
 * 
 * 1. The premium lessons template maintains backward compatibility
 * 2. All existing data structures are preserved
 * 3. Enhanced UI provides better user experience
 * 4. Auto-save reduces data loss risk
 * 5. Archive functionality allows for better content management
 * 6. Activity logs provide audit trail for changes
 * 7. Form validation prevents invalid data entry
 * 8. Responsive design works on all devices
 */
?>