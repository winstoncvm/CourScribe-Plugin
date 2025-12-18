/**
 * Courscribe Frontend Script
 */
(function($) {
    'use strict';
    
    // Initialize the frontend editor
    function initFrontendEditor() {
        $('.courscribe-save-button').on('click', function() {
            var $button = $(this);
            var $editor = $button.closest('.courscribe-frontend-editor');
            var $status = $editor.find('.courscribe-save-status');
            var $content = $editor.find('.courscribe-editor-content');
            var postId = $editor.data('post-id');
            
            // Disable button during save
            $button.prop('disabled', true).text('Saving...');
            $status.html('');
            
            // Save the content via AJAX
            $.ajax({
                url: courscribeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_save_curriculum',
                    nonce: courscribeVars.nonce,
                    post_id: postId,
                    content: $content.val()
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="success">' + response.data.message + '</span>');
                    } else {
                        $status.html('<span class="error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span class="error">An error occurred. Please try again.</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save Changes');
                    
                    // Clear status message after 3 seconds
                    setTimeout(function() {
                        $status.fadeOut(300, function() {
                            $(this).html('').show();
                        });
                    }, 3000);
                }
            });
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initFrontendEditor();
    });
    
})(jQuery);