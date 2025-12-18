(function($) {
    // Prevent accordion from toggling when selecting text
    $('.courscribe-xy-acc_panel .accordion-body, .lesson-xy-acc_panel .accordion-body').on('mousedown', function(e) {
        // Allow default text selection behavior
        e.stopPropagation();
    });

    // Fix issue with accordion toggle preventing text selection
    $('.courscribe-xy-acc_title, .lesson-xy-acc_title').on('mousedown', function(e) {
        // Don't prevent propagation on the accordion header itself
        if (!$(e.target).closest('.accordion-button').length) {
            e.stopPropagation();
        }
    });

    // Ensure text selection works in input fields
    $('.courscribe-xy-acc input, .courscribe-xy-acc textarea').on('click', function(e) {
        e.stopPropagation();
    });

    // Accordion toggle
    $('.courscribe-xy-acc').on('click', '.accordion-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $titleWrapper = $button.closest('.courscribe-xy-acc_title');
        var $item = $titleWrapper.closest('.courscribe-xy-acc-item');
        var $content = $item.find('.courscribe-xy-acc_panel');

        $titleWrapper.toggleClass('courscribe-xy-acc_title_active');
        if ($content.hasClass('courscribe-xy-acc_panel_col')) {
            $content.removeClass('anim_out').addClass('anim_in');
        } else {
            $content.removeClass('anim_in').addClass('anim_out');
        }
        $content.toggleClass('courscribe-xy-acc_panel_col');
    });

    // Lesson accordion toggle
    $('.lesson-xy-acc').on('click', '.accordion-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $titleWrapper = $button.closest('.lesson-xy-acc_title');
        var $item = $titleWrapper.closest('.lesson-xy-acc-item');
        var $content = $item.find('.lesson-xy-acc_panel');

        $titleWrapper.toggleClass('lesson-xy-acc_title_active');
        if ($content.hasClass('lesson-xy-acc_panel_col')) {
            $content.removeClass('anim_out').addClass('anim_in');
        } else {
            $content.removeClass('anim_in').addClass('anim_out');
        }
        $content.toggleClass('lesson-xy-acc_panel_col');
    });

    // Client-specific event handling
    if (courscribeFeedback.isClient) {
        // Override client-side prevention of clicks on form elements
        $('.courscribe-single-curriculum').off('click', 'input, textarea, select');

        // Prevent form submission and button actions while allowing text selection
        $('.courscribe-single-curriculum').on('click', 'button:not(.accordion-button):not(.courscribe-close-button)', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    }
})(jQuery);