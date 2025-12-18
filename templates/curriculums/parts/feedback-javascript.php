<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Feedback JavaScript Component
 * Contains all the feedback-related JavaScript functionality
 */
function courscribe_render_feedback_javascript() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            let feedbackOffcanvas;
            let currentFeedbackButton = null;
            
            function renderFeedback(feedbackData) {
                const feedback = feedbackData.feedback || [];
                const fieldName = feedbackData.field_name || 'Unknown Field';
                const fieldValue = feedbackData.field_value || '';
                const postName = feedbackData.post_name || 'Unknown Post';
                const curriculumTitle = feedbackData.curriculum_title || 'Unknown Curriculum';
                const fieldType = feedbackData.field_type || 'Unknown Type';
                const cPostType = feedbackData.post_type || 'Unknown';

                let fieldValueHtml;
                try {
                    if (fieldName === 'objectives') {
                        const objectives = JSON.parse(fieldValue);
                        fieldValueHtml = objectives.map(obj => 
                            '<div class="courscribe-offcanvas-field-value">Error parsing objective data</div>'
                        ).join('');
                    } else {
                        fieldValueHtml = 
                            `<div class="courscribe-offcanvas-field-value">${postName}</div>`;
                    }
                    fieldValueHtml = 
                        `<div class="courscribe-offcanvas-field-value">${fieldValue || 'N/A'}</div>`;
                } catch (e) {
                    fieldValueHtml = 
                        `<div class="courscribe-offcanvas-field-value">${fieldValue || 'N/A'}</div>`;
                }

                let feedbackListHtml = '';
                let canEdit = feedback.length > 0;
                let canComment = true;

                if (feedback.length > 0) {
                    feedbackListHtml = `
                        <div class="courscribe-offcanvas-header-component">
                            <div class="courscribe-offcanvas-title">Feedback for <span>${postName}</span> <div class="pill">${cPostType}</div></div>
                            <div class="courscribe-offcanvas-subtitle">Curriculum: ${curriculumTitle}</div>
                            <div class="courscribe-offcanvas-field-type">Field: ${fieldType}</div>
                            <div class="courscribe-offcanvas-field-value">Value: ${fieldValueHtml}</div>
                            <div class="courscribe-feedback-radio">
                                <input type="radio" name="feedbackType" value="comment" id="commentRadio" checked>
                                <label for="commentRadio">Comment</label>
                                <input type="radio" name="feedbackType" value="screenshot" id="screenshotRadio">
                                <label for="screenshotRadio">Screenshot</label>
                            </div>
                        </div>
                        <div class="courscribe-feedback-entries">
                            ${feedback.map(entry => `
                                <div class="courscribe-feedback-entry ${entry.role === 'Client' ? 'client' : ''}">
                                    <div class="courscribe-feedback-content">
                                        <div class="courscribe-feedback-user">
                                            <div class="courscribe-feedback-user-info">${entry.user_name}</div>
                                            <div class="courscribe-feedback-role">${entry.role}</div>
                                        </div>
                                        <div class="courscribe-feedback-timestamp">${new Date(entry.timestamp).toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true })}</div>
                                    </div>
                                    <div class="courscribe-feedback-text">${entry.text}</div>
                                    ${entry.screenshot_url ? `<img src="${entry.screenshot_url}" alt="Feedback screenshot" style="max-width: 100%; margin-top: 10px;">` : ''}
                                    <div class="courscribe-feedback-status ${entry.status}">
                                        Status: ${entry.status}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    feedbackListHtml = `
                        <div class="courscribe-feedback-component">
                            <div class="courscribe-feedback-header mt-3 mb-3">
                                <p>No feedback yet. Be the first to provide feedback!</p>
                            </div>
                            <div class="courscribe-feedback-timeline">
                                <!-- Timeline will be populated as feedback is added -->
                            </div>
                            <div class="courscribe-feedback-footer">
                                <!-- Footer content -->
                            </div>
                        </div>
                    `;
                }

                function bindEventHandlers() {
                    $('input[name="feedbackType"]').on('change', function() {
                        const selectedType = $(this).val();
                        if (selectedType === 'screenshot') {
                            $('#courscribe-field-feedback-container').html(`
                                <div class="courscribe-offcanvas-header-component">
                                    <div class="courscribe-offcanvas-title">Screenshot Feedback for <span>${postName}</span></div>
                                    <div class="courscribe-offcanvas-subtitle">Curriculum: ${curriculumTitle}</div>
                                    <div class="courscribe-feedback-radio">
                                        <input type="radio" name="feedbackType" value="comment" id="commentRadio">
                                        <label for="commentRadio">Comment</label>
                                        <input type="radio" name="feedbackType" value="screenshot" id="screenshotRadio" checked>
                                        <label for="screenshotRadio">Screenshot</label>
                                    </div>
                                </div>
                                <div class="courscribe-screenshot-container">
                                    <p>Click "Take Screenshot" to capture the current page and add annotations.</p>
                                    <button id="courscribe-take-screenshot" class="btn btn-primary">Take Screenshot</button>
                                    <div class="courscribe-annotation-controls" style="display: none;">
                                        <div id="courscribe-screenshot-canvas-container"></div>
                                        <div class="annotation-tools">
                                            <button id="courscribe-add-arrow" class="btn btn-secondary">Add Arrow</button>
                                            <button id="courscribe-add-text" class="btn btn-secondary">Add Text</button>
                                            <button id="courscribe-add-highlight" class="btn btn-secondary">Add Highlight</button>
                                            <input type="color" id="courscribe-annotation-color" value="#ff0000">
                                            <button id="courscribe-clear-annotations" class="btn btn-warning">Clear All</button>
                                        </div>
                                        <textarea id="courscribe-screenshot-feedback" placeholder="Add your feedback about the screenshot..." rows="3" style="width: 100%; margin-top: 10px;"></textarea>
                                        <button id="courscribe-submit-screenshot" class="btn btn-success" style="margin-top: 10px;">Submit Screenshot Feedback</button>
                                    </div>
                                </div>
                            `);
                            bindEventHandlers();
                        } else {
                            $('#courscribe-field-feedback-container').html(originalContent);
                            bindEventHandlers();
                        }
                    });

                    $('#courscribe-take-screenshot').on('click', function() {
                        $('.courscribe-annotation-controls').show();
                        if (typeof html2canvas !== 'undefined') {
                            html2canvas(document.body).then(canvas => {
                                $('#courscribe-screenshot-canvas-container').html('').append(canvas);
                                $(canvas).css({
                                    'max-width': '100%',
                                    'height': 'auto',
                                    'border': '1px solid #ccc'
                                });
                            });
                        } else {
                            alert('Screenshot functionality is not available. Please ensure html2canvas is loaded.');
                        }
                    });
                }

                let originalContent = `
                    <div class="courscribe-offcanvas-header-component">
                        <div class="courscribe-offcanvas-title">Feedback for <span>${postName}</span> <div class="pill">${cPostType}</div></div>
                        <div class="courscribe-offcanvas-subtitle">Curriculum: ${curriculumTitle}</div>
                        <div class="courscribe-offcanvas-field-type">Field: ${fieldType}</div>
                        <div class="courscribe-offcanvas-field-value">Value: ${fieldValueHtml}</div>
                        <div class="courscribe-feedback-radio">
                            <input type="radio" name="feedbackType" value="comment" id="commentRadio" checked>
                            <label for="commentRadio">Comment</label>
                            <input type="radio" name="feedbackType" value="screenshot" id="screenshotRadio">
                            <label for="screenshotRadio">Screenshot</label>
                        </div>
                    </div>
                    ${feedbackListHtml}
                    <div class="courscribe-loading-container" style="text-align: center; padding: 20px;">
                        <div class="courscribe-loading-spinner" style="display: none;">Loading...</div>
                    </div>
                    ${canComment ? `
                        <div class="courscribe-feedback-form">
                            <h4>Add Your Feedback</h4>
                            <div class="form-group mb-3">
                                <label for="courscribe-feedback-text">Your Comment:</label>
                                <textarea id="courscribe-feedback-text" rows="4" style="width: 100%;" placeholder="Enter your feedback here..."></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label for="courscribe-feedback-status">Status:</label>
                                <select id="courscribe-feedback-status" style="width: 100%;">
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <button id="courscribe-submit-feedback" class="btn btn-primary">Submit Feedback</button>
                        </div>
                    ` : ''}
                `;

                if (canEdit) {
                    const screenshotSection = feedback.some(entry => entry.screenshot_url) ? `
                        <div class="courscribe-screenshot-container">
                            <p>Previous screenshots and annotations:</p>
                            ${feedback.filter(entry => entry.screenshot_url).map(entry => `
                                <img src="${entry.screenshot_url}" alt="Feedback screenshot" style="max-width: 100%; margin: 10px 0;">
                                <p><strong>${entry.user_name}:</strong> ${entry.text}</p>
                            `).join('')}
                            <div class="courscribe-annotation-controls">
                                <button id="courscribe-take-new-screenshot" class="btn btn-primary">Take New Screenshot</button>
                            </div>
                        </div>
                    ` : '';

                    $('#courscribe-field-feedback-container').html(originalContent + screenshotSection);
                } else {
                    $('#courscribe-field-feedback-container').html(originalContent);
                }

                bindEventHandlers();

                function addResponseHandler() {
                    $('#courscribe-submit-feedback').off('click').on('click', function() {
                        const feedbackText = $('#courscribe-feedback-text').val().trim();
                        const feedbackStatus = $('#courscribe-feedback-status').val();

                        if (!feedbackText) {
                            alert('Please enter some feedback text.');
                            return;
                        }

                        const submitButton = $(this);
                        const originalText = submitButton.text();
                        submitButton.text('Submitting...').prop('disabled', true);

                        const feedbackData = {
                            action: 'courscribe_save_feedback',
                            security: courscribe_single_curriculum_vars.nonce,
                            feedback_text: feedbackText,
                            status: feedbackStatus,
                            field_name: currentFeedbackButton.data('field-name'),
                            field_id: currentFeedbackButton.data('field-id'),
                            post_name: currentFeedbackButton.data('post-name'),
                            current_field_value: currentFeedbackButton.data('current-field-value'),
                            post_type: currentFeedbackButton.data('post-type'),
                            field_type: currentFeedbackButton.data('field-type'),
                            user_id: currentFeedbackButton.data('user-id'),
                            user_name: currentFeedbackButton.data('user-name'),
                            curriculum_id: currentFeedbackButton.data('curriculum-id'),
                            curriculum_title: currentFeedbackButton.data('curriculum-title'),
                            course_id: currentFeedbackButton.data('course-id'),
                            module_id: currentFeedbackButton.data('module-id'),
                            lesson_id: currentFeedbackButton.data('lesson-id')
                        };

                        $.ajax({
                            url: courscribe_single_curriculum_vars.ajax_url,
                            type: 'POST',
                            data: feedbackData,
                            success: function(response) {
                                if (response.success) {
                                    $('#courscribe-feedback-text').val('');
                                    if (feedbackOffcanvas) {
                                        feedbackOffcanvas.hide();
                                    }
                                    alert('Feedback submitted successfully!');
                                    
                                    if (currentFeedbackButton && currentFeedbackButton.find('.feedback-count').length > 0) {
                                        loadFeedbackCounts();
                                    }
                                } else {
                                    alert('Failed to submit feedback: ' + (response.data || 'Unknown error'));
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                alert('Failed to submit feedback. Please try again.');
                            },
                            complete: function() {
                                submitButton.text(originalText).prop('disabled', false);
                            }
                        });
                    });
                }

                addResponseHandler();
            }

            function fetchFeedback() {
                if (!currentFeedbackButton) {
                    console.error('No feedback button context available');
                    return;
                }

                $('.courscribe-loading-spinner').show();

                const feedbackData = {
                    action: 'courscribe_get_feedback',
                    security: courscribe_single_curriculum_vars.nonce,
                    field_name: currentFeedbackButton.data('field-name'),
                    field_id: currentFeedbackButton.data('field-id'),
                    post_name: currentFeedbackButton.data('post-name'),
                    current_field_value: currentFeedbackButton.data('current-field-value'),
                    post_type: currentFeedbackButton.data('post-type'),
                    field_type: currentFeedbackButton.data('field-type'),
                    user_id: currentFeedbackButton.data('user-id'),
                    user_name: currentFeedbackButton.data('user-name'),
                    curriculum_id: currentFeedbackButton.data('curriculum-id'),
                    curriculum_title: currentFeedbackButton.data('curriculum-title'),
                    course_id: currentFeedbackButton.data('course-id'),
                    module_id: currentFeedbackButton.data('module-id'),
                    lesson_id: currentFeedbackButton.data('lesson-id')
                };

                $.ajax({
                    url: courscribe_single_curriculum_vars.ajax_url,
                    type: 'POST',
                    data: feedbackData,
                    success: function(response) {
                        $('.courscribe-loading-spinner').hide();
                        if (response.success && response.data) {
                            renderFeedback(response.data);
                        } else {
                            console.error('Failed to fetch feedback:', response);
                            renderFeedback({ feedback: [] });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('.courscribe-loading-spinner').hide();
                        console.error('AJAX Error fetching feedback:', error);
                        renderFeedback({ feedback: [] });
                    }
                });
            }

            $(document).on('click', '.courscribe-client-review-submit-button', function() {
                currentFeedbackButton = $(this);
                
                if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const offcanvasElement = document.getElementById('courscribeFieldFeedbackOffcanvas');
                    if (offcanvasElement) {
                        feedbackOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
                        feedbackOffcanvas.show();
                        
                        setTimeout(() => {
                            fetchFeedback();
                        }, 300);
                    }
                } else {
                    console.error('Bootstrap not available for offcanvas');
                }
            });

            $('.courscribe-show-feedback').on('click', function() {
                $('.courscribe-client-review-submit-button').each(function() {
                    const $this = $(this);
                    
                    if ($this.find('.feedback-count').length === 0) {
                        $this.append('<span class="feedback-count">Loading...</span>');
                    }
                });
                
                loadFeedbackCounts();
                
                $(this).hide();
                $('.courscribe-hide-feedback').show();
            });

            $('.courscribe-hide-feedback').on('click', function() {
                $('.feedback-count').remove();
                $(this).hide();
                $('.courscribe-show-feedback').show();
            });

            function loadFeedbackCounts() {
                $('.courscribe-client-review-submit-button').each(function() {
                    const $button = $(this);
                    const feedbackData = {
                        action: 'courscribe_get_feedback_count',
                        security: courscribe_single_curriculum_vars.nonce,
                        field_name: $button.data('field-name'),
                        curriculum_id: $button.data('curriculum-id'),
                        course_id: $button.data('course-id'),
                        module_id: $button.data('module-id'),
                        lesson_id: $button.data('lesson-id')
                    };

                    $.ajax({
                        url: courscribe_single_curriculum_vars.ajax_url,
                        type: 'POST',
                        data: feedbackData,
                        success: function(response) {
                            if (response.success) {
                                const count = response.data.count || 0;
                                const status = response.data.status || 'none';
                                let countHtml = '';
                                
                                if (count > 0) {
                                    let statusClass = '';
                                    switch(status) {
                                        case 'open':
                                            statusClass = 'feedback-status-open';
                                            break;
                                        case 'in_progress':
                                            statusClass = 'feedback-status-progress';
                                            break;
                                        case 'resolved':
                                            statusClass = 'feedback-status-resolved';
                                            break;
                                    }
                                    countHtml = `<span class="feedback-count ${statusClass}">${count}</span>`;
                                } else {
                                    countHtml = '<span class="feedback-count feedback-status-none">0</span>';
                                }
                                
                                $button.find('.feedback-count').remove();
                                $button.append(countHtml);
                            }
                        },
                        error: function() {
                            $button.find('.feedback-count').text('Error');
                        }
                    });
                });
            }
        });

        // Debounce function to prevent rapid clicks
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
    <?php
}
?>