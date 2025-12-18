/*!
 * jQuery version of classie-based accordion behavior
 */
jQuery(document).ready(function($) {
  $('.courscribe-xy-acc').on('click', '.accordion-button', function(e) {
    e.preventDefault();

    var $button = $(this);
    var $titleWrapper = $button.closest('.courscribe-xy-acc_title');
    var $item = $titleWrapper.closest('.courscribe-xy-acc-item');
    var $content = $item.find('.courscribe-xy-acc_panel');

    // Toggle active class for styling
    $titleWrapper.toggleClass('courscribe-xy-acc_title_active');

    // Toggle content visibility with class
    if ($content.hasClass('courscribe-xy-acc_panel_col')) {
      $content.removeClass('anim_out').addClass('anim_in');
    } else {
      $content.removeClass('anim_in').addClass('anim_out');
    }

    // Toggle panel open/close state
    $content.toggleClass('courscribe-xy-acc_panel_col');
  });
  $('.lesson-xy-acc').on('click', '.accordion-button', function(e) {
    e.preventDefault();

    var $button = $(this);
    var $titleWrapper = $button.closest('.lesson-xy-acc_title');
    var $item = $titleWrapper.closest('.lesson-xy-acc-item');
    var $content = $item.find('.lesson-xy-acc_panel');

    // Toggle active class for styling
    $titleWrapper.toggleClass('lesson-xy-acc_title_active');

    // Toggle content visibility with class
    if ($content.hasClass('lesson-xy-acc_panel_col')) {
      $content.removeClass('anim_out').addClass('anim_in');
    } else {
      $content.removeClass('anim_in').addClass('anim_out');
    }

    // Toggle panel open/close state
    $content.toggleClass('lesson-xy-acc_panel_col');
  });
});