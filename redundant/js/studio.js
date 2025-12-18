jQuery(document).ready(function($) {
    $('#add_collaborator').on('click', function() {
        var search = $('#collaborator_search').val();
        var studio_id = $('#studio_id').val();
        $.post( courscribeAjax.ajaxurl, {
            action: 'courscribe_add_collaborator',
            search: search,
            studio_id: studio_id,
            nonce: courscribeAjax.nonce
        }, function(response) {
            if ( response.success ) {
                location.reload(); // Refresh to update collaborator list
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});