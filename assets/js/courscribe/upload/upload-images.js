// assets/js/courscribe/upload/upload-images.js
jQuery(document).ready(function ($) {
    function handleFileUpload(files, previewContainer) {
        $.each(files, function (index, file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var filePreview = $('<div class="col-md-3 media-item"></div>');

                if (file.type.startsWith('image/')) {
                    filePreview.append('<img src="' + e.target.result + '" class="media-preview img-fluid" alt="Media Image" />');
                } else if (file.type.startsWith('video/')) {
                    filePreview.append('<video controls class="media-preview"><source src="' + e.target.result + '" type="' + file.type + '">Your browser does not support the video tag.</video>');
                } else if (file.type === 'application/pdf') {
                    filePreview.append('<embed src="' + e.target.result + '" type="application/pdf" class="media-preview" />');
                } else if (file.type.includes('word') || file.type.includes('powerpoint')) {
                    filePreview.append('<div class="file-icon doc-preview"><i class="fas fa-file-word"></i> ' + file.name + '</div>');
                } else {
                    filePreview.append('<div class="file-icon"><i class="fas fa-file"></i> ' + file.name + '</div>');
                }

                previewContainer.append(filePreview);
            };
            reader.readAsDataURL(file);
        });
    }

    $(document).on('change', 'input[type="file"]', function (e) {
        var files = e.target.files;
        var previewContainer = $(this).closest('.method-group').find('#media-preview-grid .row');
        handleFileUpload(files, previewContainer);
    });

    $('.media-upload-wrapper').on('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    }).on('dragleave', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    }).on('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        var files = e.originalEvent.dataTransfer.files;
        var previewContainer = $(this).closest('.method-group').find('#media-preview-grid .row');
        handleFileUpload(files, previewContainer);
        $(this).find('input[type="file"]').prop('files', files);
    });

    $('.save-teachingPoint').on('click', function () {
        var teachingPointId = $(this).data('teachingpoint-id');
        if (!teachingPointId) {
            alert('Error: Teaching Point ID is missing');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'update_teaching_point');
        formData.append('teaching_point_id', teachingPointId);

        formData.append('method_thinking_skill', $('#thinking-skill-method-' + teachingPointId).val() || '');
        formData.append('method_teaching_strategy', $('#teaching-strategy-' + teachingPointId).val() || '');
        formData.append('method_add_link', $('#add-link-method-' + teachingPointId).val() || '');

        formData.append('materials_thinking_skill', $('#thinking-skill-materials-' + teachingPointId).val() || '');
        formData.append('materials_learner_activities', $('#learner-activities-materials-' + teachingPointId).val() || '');
        formData.append('materials_add_link', $('#add-link-materials-' + teachingPointId).val() || '');

        var mediaInput = $('#media-' + teachingPointId)[0];
        if (mediaInput && mediaInput.files.length > 0) {
            for (var i = 0; i < mediaInput.files.length; i++) {
                formData.append('media[]', mediaInput.files[i]);
            }
        }

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    alert('Teaching point updated successfully!');
                    location.reload();
                } else {
                    alert('Failed to update teaching point: ' + response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('An error occurred while updating the teaching point.');
            }
        });
    });
});