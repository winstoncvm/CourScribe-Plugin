<?php
// Path: templates/template-parts/single-curriculum/feedback-controls.php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="courscribe-slide-deck-controls">
    <?php if (!$permissions->is_client()): ?>
        <?php
        $generate_slide_button = '
            <button
                id="courscribe-generate-slide-deck-' . esc_attr($course->ID) . '"
                class="btn btn-primary generate-test-slide courscribe-generate-slide-deck"
                style="background: linear-gradient(90deg, rgba(251, 175, 63, 0.2) 0%, rgba(239, 67, 57, 0.2) 100%); border-bottom-left-radius: 0px; padding: 1rem 2rem;"
                data-course-id="' . esc_attr($course->ID) . '"
            >
                Generate Slide Deck
            </button>';
        echo $tooltips->wrap_button_with_tooltip($generate_slide_button, [
            'title' => 'Generate Slide Deck',
            'description' => 'Automatically generate a full slide deck for this course using AI. Stores up to 4 slide decks.',
            'required_package' => 'CourScribe Pro'
        ]);

        $preview_slide_button = '
            <button
                id="courscribe-preview-slide-deck-' . esc_attr($course->ID) . '"
                class="txt-button-one preview-test-slide courscribe-preview-slide-deck"
                data-text="Awesome"
                data-course-id="' . esc_attr($course_id) . '"
            >
                <span class="actual-text"> Preview Slide Deck </span>
                <span aria-hidden="true" class="hover-text"> Preview Slide Deck </span>
            </button>';
        echo $tooltips->wrap_button_with_tooltip($preview_slide_button, [
            'title' => 'Preview Slide Deck',
            'description' => 'View the latest slide deck in a full-screen preview.',
            'required_package' => 'CourScribe Pro'
        ]);

        $edit_pdf_button = '
            <button
                id="courscribe-edit-richtexteditor-' . esc_attr($course->ID) . '"
                data-bs-toggle="offcanvas"
                data-bs-target="#courscribeEditDocumentOffcanvas"
                aria-controls="courscribeEditDocumentOffcanvas"
                data-course-id="' . esc_attr($course->ID) . '"
                class="txt-button-two"
                data-text="Awesome"
            >
                <span class="actual-text"> edit doc </span>
                <span aria-hidden="true" class="hover-text-two"> edit doc </span>
            </button>';
        echo $tooltips->wrap_button_with_tooltip($edit_pdf_button, [
            'title' => 'Edit PDF Template',
            'description' => 'Customize the PDF slide deck layout using a visual editor.',
            'required_package' => 'CourScribe Pro'
        ]);

        $slide_decks = get_post_meta($course_id, '_courscribe_slide_decks', true);
        if (!empty($slide_decks) && is_array($slide_decks)) {
            ?>
            <select id="courscribe-download-deck-<?php echo $course_id ?>"
                    class="form-select d-inline-block w-auto ms-2 mb-2">
                <option value="">Select a slide deck to download</option>
                <?php
                usort($slide_decks, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                foreach ($slide_decks as $index => $deck) {
                    if (!empty($deck['ppt_url']) && !empty($deck['date'])) {
                        ?>
                        <option value="<?php echo esc_url($deck['ppt_url']); ?>"
                                data-reveal-url="<?php echo isset($deck['reveal_url']) ? esc_url($deck['reveal_url']) : ''; ?>">
                            <?php echo esc_html(date_i18n('F j, Y, g:i a', strtotime($deck['date']))); ?>
                        </option>
                        <?php
                    }
                }
                ?>
            </select>
            <?php
        }
        ?>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    Feedback({
        h2cPath: '<?php echo esc_url($site_url); ?>/wp-content/plugins/courscribe/assets/js/courscribe/html2canvas.js'
    });

    let feedbackComponentHtml = '';
    let currentAnnotorious = null;
    let feedbackLoaded = <?php echo $permissions->is_client() ? 'true' : 'false'; ?>;
    let hasFeedback = false;

    <?php if ($permissions->is_client()): ?>
        $('.courscribe-feedback-adornment').removeClass('feedback-hidden');
        feedbackLoaded = true;
    <?php endif; ?>

    // Delegate click events for feedback buttons
    $(document).on('click', '.courscribe-feedback-adornment, .courscribe-client-review-submit-button', function() {
        if (!$(this).data('bs-toggle') || !$(this).data('bs-target')) {
            console.warn('Courscribe: Feedback button missing data attributes', this);
            return;
        }
        $('#courscribeFieldFeedbackOffcanvas').trigger('show.bs.offcanvas', this);
    });

    $('#courscribeFieldFeedbackOffcanvas').off('show.bs.offcanvas').on('show.bs.offcanvas', function(event, relatedTarget) {
        var button = relatedTarget ? $(relatedTarget) : $(event.relatedTarget);
        var courseId = button.data('course-id');
        var curriculumId = button.data('curriculum-id');
        var curriculumTitle = button.data('curriculum-title');
        var postName = button.data('post-name');
        var fieldName = button.data('field-name');
        var fieldId = button.data('field-id');
        var fieldType = button.data('field-type');
        var fieldValue = button.data('current-field-value');
        var postType = button.data('post-type');
        var isClient = <?php echo $permissions->is_client() ? 'true' : 'false'; ?>;
        var userId = button.data('user-id');
        var userName = button.data('user-name');

        $(this).attr('data-course-id', courseId)
            .attr('data-curriculum-id', curriculumId)
            .attr('data-post-type', postType)
            .attr('data-field-id', fieldId);

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            method: 'POST',
            data: {
                action: 'courscribe_get_feedback',
                nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                post_id: courseId,
                post_type: postType,
                field_id: fieldId
            },
            success: function(response) {
                if (response.success) {
                    renderFeedback(response.data);
                } else {
                    console.error('Courscribe: Failed to fetch feedback', response.data.message);
                    $(this).find('.offcanvas-body').html('<p>Error loading feedback. Please try again.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Courscribe: AJAX error fetching feedback', xhr.responseText);
                $(this).find('.offcanvas-body').html('<p>Error loading feedback. Please try again.</p>');
            }
        });

        let renderTimeout;

        function renderFeedback(feedbackData) {
            clearTimeout(renderTimeout);
            renderTimeout = setTimeout(() => {
                var fieldValueHtml = '';
                if (fieldType === 'objective') {
                    try {
                        var obj = JSON.parse(fieldValue);
                        fieldValueHtml = `
                            <ul class="courscribe-offcanvas-objective-list">
                                <li><strong>Thinking Skill:</strong> ${obj.thinking_skill || 'N/A'}</li>
                                <li><strong>Action Verb:</strong> ${obj.action_verb || 'N/A'}</li>
                                <li><strong>Description:</strong> ${obj.description || 'N/A'}</li>
                            </ul>
                        `;
                    } catch (e) {
                        fieldValueHtml = '<div class="courscribe-offcanvas-field-value">Error parsing objective data</div>';
                    }
                } else if (fieldType === 'post') {
                    fieldValueHtml = `<div class="courscribe-offcanvas-field-value">${postName}</div>`;
                } else {
                    fieldValueHtml = `<div class="courscribe-offcanvas-field-value">${fieldValue || 'N/A'}</div>`;
                }

                var cPostType = {
                    'crscribe_course': 'Course',
                    'crscribe_curriculum': 'Curriculum',
                    'crscribe_module': 'Module',
                    'crscribe_lesson': 'Lesson'
                }[postType] || 'Course';

                var headerComponent = `
                    <div class="courscribe-offcanvas-header-component">
                        <div class="courscribe-offcanvas-title">Feedback for <span>${postName}</span> <div class="pill">${cPostType}</div></div>
                        <div class="courscribe-offcanvas-subtitle">Curriculum: ${curriculumTitle}</div>
                        <div class="courscribe-offcanvas-field-type">Field: ${fieldType}</div>
                        <div class="courscribe-offcanvas-field-value">Value: ${fieldValueHtml}</div>
                        <div class="courscribe-feedback-radio">
                            <input type="radio" id="status-open" name="feedback-status" value="Open" label="Open" checked>
                            <input type="radio" id="status-in-progress" name="feedback-status" value="In Progress" label="Mark As In-Progress">
                            <input type="radio" id="status-resolved" name="feedback-status" value="Resolved" label="Mark As Resolved">
                        </div>
                    </div>
                `;

                var feedbackEntries = feedbackData.map(entry => `
                    <div class="courscribe-feedback-entry ${entry.role === 'Client' ? 'client' : ''}">
                        <img src="<?php echo esc_url(home_url('/wp-content/plugins/courscribe/assets/images/profile.png')); ?>" alt="${entry.user_name} avatar" class="courscribe-feedback-avatar">
                        <div class="courscribe-feedback-content">
                            <div class="courscribe-feedback-user">
                                <div>
                                    <div class="courscribe-feedback-user-info">${entry.user_name}</div>
                                    <div class="courscribe-feedback-role">${entry.role}</div>
                                </div>
                                <div class="courscribe-feedback-timestamp">${new Date(entry.timestamp).toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true })}</div>
                            </div>
                            <div class="courscribe-feedback-text">${entry.text}</div>
                            ${entry.screenshot_url ? `<img src="${entry.screenshot_url}" class="courscribe-feedback-screenshot" alt="Feedback screenshot" data-screenshot-url="${entry.screenshot_url}" data-annotations='${JSON.stringify(entry.annotations)}'>` : ''}
                            <div class="courscribe-feedback-status ${entry.status}">
                                ${entry.status.toUpperCase().replace('-', ' ')}
                            </div>
                        </div>
                    </div>
                `).join('');

                feedbackComponentHtml = `
                    <div class="courscribe-feedback-component">
                        ${headerComponent}
                        <div class="courscribe-feedback-header mt-3 mb-3">
                            <h6>Feedback Timeline</h6>
                        </div>
                        <div class="courscribe-feedback-timeline">
                            ${feedbackEntries}
                        </div>
                        <div class="courscribe-feedback-footer">
                            <button class="courscribe-add-response-btn"><span>Add Open Response</span></button>
                            ${fieldType === 'post' ? '<button class="courscribe-take-screenshot-btn"><span>Take Screenshot</span></button>' : ''}
                        </div>
                    </div>
                `;

                var $offcanvasBody = $('#courscribeFieldFeedbackOffcanvas').find('.offcanvas-body');
                $offcanvasBody.html(feedbackComponentHtml);

                function bindEventHandlers() {
                    var $offcanvasBody = $('#courscribeFieldFeedbackOffcanvas').find('.offcanvas-body');

                    $('.courscribe-feedback-radio input[name="feedback-status"]').on('change', function() {
                        var selectedStatus = $(this).val();
                        $('.courscribe-add-response-btn span').text(`Add ${selectedStatus} Response`);
                    });

                    $offcanvasBody.on('click', '.courscribe-feedback-screenshot', function() {
                        var screenshotUrl = $(this).data('screenshot-url');
                        var annotations = $(this).data('annotations') || [];

                        $offcanvasBody.html(`
                            <div class="courscribe-screenshot-container">
                                <div id="courscribe-screenshot-wrapper" style="position: relative; width: 100%; overflow: auto;">
                                    <img src="${screenshotUrl}" class="courscribe-screenshot-img" id="courscribe-screenshot-img" style="max-width: 100%; display: block;">
                                </div>
                                <div class="courscribe-annotation-controls">
                                    <button class="courscribe-cancel-annotation-btn courscribe-cancel-view-btn"><span>Close</span></button>
                                </div>
                            </div>
                        `);

                        var screenshotImg = $('#courscribe-screenshot-img')[0];
                        currentAnnotorious = Annotorious.init({
                            image: screenshotImg,
                            readOnly: true,
                        });
                        currentAnnotorious.setAnnotations(annotations);
                    });

                    $('.courscribe-take-screenshot-btn').on('click', function() {
                        var $courseElement = $(`.courscribe-xy-acc-item[data-course-id="${courseId}"]`);
                        if (!$courseElement.length) {
                            alert('Course content not found.');
                            return;
                        }

                        $offcanvasBody.html(`
                            <div class="courscribe-loading-container" style="text-align: center; padding: 20px;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Generating screenshot...</span>
                                </div>
                                <p>Generating screenshot, please wait...</p>
                            </div>
                        `);

                        html2canvas($courseElement[0], {
                            scale: 2
                        }).then(canvas => {
                            var dataUrl = canvas.toDataURL('image/png');
                            $offcanvasBody.html(`
                                <div class="courscribe-screenshot-container">
                                    <div id="courscribe-screenshot-wrapper" style="position: relative; width: 100%; overflow: auto;">
                                        <img src="${dataUrl}" class="courscribe-screenshot-img" id="courscribe-screenshot-img" style="max-width: 100%; display: block;">
                                    </div>
                                    <div class="courscribe-annotation-controls">
                                        <button class="courscribe-save-annotation-btn"><span>Save Annotation</span></button>
                                        <button class="courscribe-cancel-annotation-btn courscribe-cancel-take-btn"><span>Cancel</span></button>
                                    </div>
                                </div>
                            `);

                            var screenshotImg = $('#courscribe-screenshot-img')[0];
                            currentAnnotorious = Annotorious.init({
                                image: screenshotImg,
                                drawingEnabled: true,
                                defaultInteraction: 'edit',
                                style: {
                                    fill: '#ff0000',
                                    fillOpacity: 0.25
                                },
                                disableEditor: false,
                                allowEmpty: false,
                                drawOnSingleClick: true,
                                disableSelect: false,
                                readOnly: false,
                            });
                            currentAnnotorious.setAuthInfo({
                                id: userId,
                                displayName: userName
                            });

                            currentAnnotorious.on('createAnnotation', function(annotation) {
                                console.log('Annotation created:', annotation);
                                const annoElement = document.querySelector(`[data-id="${annotation.id}"]`);
                                if (annoElement) {
                                    annoElement.dispatchEvent(new Event('click'));
                                }
                            });
                        }).catch(error => {
                            console.error('Error generating screenshot:', error);
                            $offcanvasBody.html(feedbackComponentHtml);
                            alert('Failed to generate screenshot. Please try again.');
                        });
                    });

                    $offcanvasBody.on('click', '.courscribe-cancel-view-btn, .courscribe-cancel-take-btn', function() {
                        if (currentAnnotorious) {
                            currentAnnotorious.destroy();
                            currentAnnotorious = null;
                        }
                        $offcanvasBody.html(feedbackComponentHtml);
                        bindEventHandlers();
                    });

                    $offcanvasBody.on('click', '.courscribe-save-annotation-btn', function() {
                        if (!currentAnnotorious) return;

                        var annotations = currentAnnotorious.getAnnotations();
                        var screenshotImg = $('#courscribe-screenshot-img')[0];
                        var dataUrl = screenshotImg.src;

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            method: 'POST',
                            data: {
                                action: 'courscribe_save_feedback',
                                nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                                post_id: courseId,
                                post_type: postType,
                                field_id: fieldId,
                                type: 'feedback',
                                text: 'Screenshot feedback',
                                status: "open",
                                screenshot: dataUrl,
                                annotations: JSON.stringify(annotations)
                            },
                            success: function(response) {
                                if (response.success) {
                                    $offcanvasBody.html(feedbackComponentHtml);
                                    bindEventHandlers();
                                    fetchFeedback();
                                } else {
                                    alert('Failed to save feedback: ' + response.data.message);
                                    console.error('Courscribe: Failed to save feedback', response.data.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                alert('Failed to save feedback. Please try again.');
                                console.error('Courscribe: AJAX error saving feedback', xhr.responseText);
                            }
                        });

                        currentAnnotorious.destroy();
                        currentAnnotorious = null;
                    });

                    function addResponseHandler() {
                        var $timeline = $(this).closest('.courscribe-feedback-component').find('.courscribe-feedback-timeline');
                        var selectedStatus = $('.courscribe-feedback-radio input[name="feedback-status"]:checked').val().toLowerCase().replace(' ', '-');

                        var textField = `
                            <div class="ai-input-container mb-3 mt-3">
                                <div class="courscribe-feedback-status ${selectedStatus}" style="margin-bottom: 5px;">
                                    ${selectedStatus.replace('-', ' ').toUpperCase()}
                                </div>
                                <textarea class="ai-input-field" id="client-review-feedback-textbox" placeholder="Type your feedback..."></textarea>
                                <div class="ai-input-buttons">
                                    <button class="ai-send-button" id="client-review-feedback-save">
                                        <div class="ai-send-icon"></div>
                                    </button>
                                    <button class="ai-cancel-button" id="client-review-feedback-cancel">
                                        <div class="ai-cancel-button-box">
                                            <span class="ai-cancel-button-elem">
                                                <svg viewBox="0 0 46 40" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M46 20.038c0-.7-.3-1.5-.8-2.1l-16-17c-1.1-1-3.2-1.4-4.4-.3-1.2 1.1-1.2 3.3 0 4.4l11.3 11.9H3c-1.7 0-3 1.3-3 3s1.3 3 3 3h33.1l-11.3 11.9c-1 1-1.2 3.3 0 4.4 1.2 1.1 3.3.8 4.4-.3l16-17c.5-.5.8-1.1.8-1.9z"></path>
                                                </svg>
                                            </span>
                                            <span class="ai-cancel-button-elem">
                                                <svg viewBox="0 0 46 40">
                                                    <path d="M46 20.038c0-.7-.3-1.5-.8-2.1l-16-17c-1.1-1-3.2-1.4-4.4-.3-1.2 1.1-1.2 3.3 0 4.4l11.3 11.9H3c-1.7 0-3 1.3-3 3s1.3 3 3 3h33.1l-11.3 11.9c-1 1-1.2 3.3 0 4.4 1.2 1.1 3.3.8 4.4-.3l16-17c.5-.5.8-1.1.8-1.9z"></path>
                                                </svg>
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <div class="ai-input-info">
                                    <span class="ai-input-hint">Type your feedback here.</span>
                                </div>
                            </div>
                        `;
                        $timeline.append(textField);
                        $('.courscribe-add-response-btn').hide();

                        $('#client-review-feedback-cancel').on('click', function() {
                            $('.ai-input-container').remove();
                            $('.courscribe-add-response-btn').show();
                        });

                        $('#client-review-feedback-save').on('click', function() {
                            var feedbackText = $('#client-review-feedback-textbox').val();
                            if (!feedbackText.trim()) return;

                            var selectedStatus = $('.courscribe-feedback-radio input[name="feedback-status"]:checked').val().toLowerCase().replace(' ', '-');
                            $.ajax({
                                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                                method: 'POST',
                                data: {
                                    action: 'courscribe_save_feedback',
                                    nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                                    post_id: courseId,
                                    post_type: postType,
                                    field_id: fieldId,
                                    type: 'response',
                                    text: feedbackText,
                                    status: selectedStatus,
                                    parent_id: 0
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('.ai-input-container').remove();
                                        $('.courscribe-add-response-btn').show();
                                        fetchFeedback();
                                    } else {
                                        alert('Failed to save feedback: ' + response.data.message);
                                        console.error('Courscribe: Failed to save feedback', response.data.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    alert('Failed to save feedback. Please try again.');
                                    console.error('Courscribe: AJAX error saving feedback', xhr.responseText);
                                }
                            });
                        });
                    }

                    $('.courscribe-add-response-btn').on('click', addResponseHandler);

                    function fetchFeedback() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            method: 'POST',
                            data: {
                                action: 'courscribe_get_feedback',
                                nonce: '<?php echo wp_create_nonce('courscribe_nonce'); ?>',
                                post_id: courseId,
                                post_type: postType,
                                field_id: fieldId
                            },
                            success: function(response) {
                                if (response.success) {
                                    renderFeedback(response.data);
                                } else {
                                    console.error('Courscribe: Failed to fetch feedback', response.data.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Courscribe: AJAX error fetching feedback', xhr.responseText);
                            }
                        });
                    }
                }

                bindEventHandlers();
            }, 100);
        });
    });
});
</script>