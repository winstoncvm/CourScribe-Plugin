jQuery(document).ready(function($) {
    $('#open_custom_editor').on('click', function() {
        var postId = $(this).data('post-id');
        $('#courscribeEditDocumentOffcanvas').attr('data-post-id', postId);
        $('#courscribeEditDocumentOffcanvas').offcanvas('show');
    });
});