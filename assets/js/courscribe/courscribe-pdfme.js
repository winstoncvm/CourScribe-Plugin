jQuery(document).ready(function ($) {
    // Initialize pdfme Designer
    let designer = null;
    const editorContainer = document.getElementById('courscribe-pdfme-editor');
    const courseId = $('#courscribe-edit-pdf-template').data('course-id');

    $('#courscribe-edit-pdf-template').on('click', function (e) {
        e.preventDefault();

        // Load existing template from post meta
        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'courscribe_get_pdf_template',
                course_id: courseId,
                nonce: courscribeAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    const template = response.data.template || {
                        basePdf: pdfme.BLANK_PDF,
                        schemas: [
                            {
                                title: {
                                    type: 'text',
                                    position: { x: 20, y: 20 },
                                    width: 170,
                                    height: 20,
                                    fontSize: 24,
                                    color: '#231F20'
                                },
                                content: {
                                    type: 'text',
                                    position: { x: 20, y: 50 },
                                    width: 170,
                                    height: 200,
                                    fontSize: 16,
                                    color: '#231F20'
                                }
                            }
                        ]
                    };

                    if (editorContainer && !designer) {
                        designer = new pdfme.Designer({
                            domContainer: editorContainer,
                            template: template
                        });
                    }

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('courscribePdfEditorModal'));
                    modal.show();
                } else {
                    alertbox.render({
                        alertIcon: 'error',
                        title: 'Error',
                        message: response.data.message || 'Failed to load PDF template.',
                        btnTitle: 'Ok',
                        themeColor: '#000000',
                        btnColor: '#665442',
                        border: true
                    });
                }
            }
        });
    });

    // Save PDF Template
    $('#courscribe-save-pdf-template').on('click', function () {
        if (designer) {
            const template = designer.saveTemplate();

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_save_pdf_template',
                    course_id: courseId,
                    template: JSON.stringify(template),
                    nonce: courscribeAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alertbox.render({
                            alertIcon: 'success',
                            title: 'Template Saved',
                            message: 'PDF template saved successfully.',
                            btnTitle: 'Ok',
                            themeColor: '#000000',
                            btnColor: '#665442',
                            border: true
                        });
                    } else {
                        alertbox.render({
                            alertIcon: 'error',
                            title: 'Error',
                            message: response.data.message || 'Failed to save PDF template.',
                            btnTitle: 'Ok',
                            themeColor: '#000000',
                            btnColor: '#665442',
                            border: true
                        });
                    }
                }
            });
        }
    });



    // Download Slide Deck (Handle PDF)
    $(document).on('change', '#courscribe-download-deck', function () {
        const url = $(this).val();
        if (url) {
            window.location.href = url;
            $(this).val('');
        }
    });
});