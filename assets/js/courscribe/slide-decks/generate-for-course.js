jQuery(document).ready(function ($) {
    // Generate Slide Deck
    $('.courscribe-generate-slide-deck-old').on('click', function (e) {
        e.preventDefault();

        $('#courscribe-loader').removeClass('d-none');

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_test_slide',
                course_id: $(this).data('course-id')
            },
            success: function (response) {
                $('#courscribe-loader').addClass('d-none');

                if (response.success) {
                    // Update remaining generations (not strictly necessary with max 4 limit)
                    $('#courscribe-remaining-count').text(response.data.remaining_generations);

                    // Add new slide deck to dropdown
                    var date = new Date().toLocaleString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true
                    });
                    var newOption = $('<option>', {
                        value: response.data.ppt_url,
                        'data-reveal-url': response.data.reveal_url,
                        text: date
                    });

                    var $dropdown = $('#courscribe-download-deck');
                    if ($dropdown.length) {
                        // Remove oldest option if at max limit (4)
                        if ($dropdown.find('option').length > 4) {
                            $dropdown.find('option:last').remove();
                        }
                        $dropdown.find('option:first').after(newOption);
                    } else {
                        // Create new dropdown if none exists
                        var $newDropdown = $('<select>', {
                            id: 'courscribe-download-deck',
                            class: 'form-select d-inline-block w-auto ms-2 mb-2'
                        }).append(
                            $('<option>', {
                                value: '',
                                text: 'Select a slide deck to download'
                            }),
                            newOption
                        );
                        $('#courscribe-generate-slide-deck').after($newDropdown);
                    }

                    alertbox.render({
                        alertIcon: 'success',
                        title: 'Course Slide Deck Generated!',
                        message: 'Slide deck generated successfully. You can download or preview it now.',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        btnColor: true,
                        border: true
                    });

                    // Optionally trigger download
                    // window.location.href = response.data.ppt_url;
                } else {
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Slide Deck Generation Failed',
                        message: response.data.message || 'An error occurred',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        btnColor: true,
                        border: true
                    });
                }
            },
            error: function (xhr, status, error) {
                $('#courscribe-loader').addClass('d-none');
                alertbox.render({
                    alertIcon: 'error',
                    title: 'Slide Deck Generation Error',
                    message: 'An error occurred while generating the slide deck: ' + error,
                    btnTitle: 'Ok',
                    themeColor: '#000000',
                    btnColor: '#665442',
                    btnColor: true,
                    border: true
                });
            }
        });
    });

    // Update Generate Slide Deck to Include PDF
    $('.courscribe-generate-slide-deck').on('click', function (e) {
        e.preventDefault();
        $('#courscribe-loader').removeClass('d-none');

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_test_slide',
                course_id: $(this).data('course-id')
            },
            success: function (response) {
                $('#courscribe-loader').addClass('d-none');

                if (response.success) {
                    // Update dropdown with new slide deck
                    const date = new Date().toLocaleString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true
                    });
                    const newOption = $('<option>', {
                        value: response.data.ppt_url,
                        'data-reveal-url': response.data.reveal_url,
                        'data-pdf-url': response.data.pdf_url,
                        text: date
                    });

                    const $dropdown = $('#courscribe-download-deck');
                    if ($dropdown.find('option').length > 4) {
                        $dropdown.find('option:last').remove();
                    }
                    $dropdown.find('option:first').after(newOption);

                    alertbox.render({
                        alertIcon: 'success',
                        title: 'Course Slide Deck Generated!',
                        message: 'Slide deck generated successfully. You can download or preview it now.',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                } else {
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Slide Deck Generation Failed',
                        message: response.data.message || 'An error occurred',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                }
            }
        });  
    });

    // Preview Slide Deck
    $('.courscribe-preview-slide-deck').on('click', function (e) {
        e.preventDefault();
        // Get the course ID from the button's data-course-id attribute
        var courseId = $(this).data('course-id');
        
        // Find the corresponding dropdown for this course
        var $dropdown = $('#courscribe-download-deck-' + courseId);
        
        // Get the latest slide deck's reveal_url from the first option with a reveal_url
        var revealUrl = $dropdown.find('option[data-reveal-url]').first().data('reveal-url');
        if (revealUrl) {
            // Set iframe source and show offcanvas
            $('#courscribe-preview-iframe').attr('src', revealUrl);
            var offcanvas = new bootstrap.Offcanvas(document.getElementById('courscribePreviewOffcanvas'));
            offcanvas.show();
        } else {
            alertbox.render({
                alertIcon: 'warning',
                title: 'No Preview Available',
                message: 'Please generate a slide deck first.',
                btnTitle: 'Ok',
                themeColor: '#000000',
                btnColor: '#665442',
                btnColor: true,
                border: true
            });
        }
    });

    // Download Slide Deck
    $(document).on('change', '#courscribe-download-deck', function () {
        var url = $(this).val();
        if (url) {
            window.location.href = url;
            // Reset dropdown to default
            $(this).val('');
        }
    });
});