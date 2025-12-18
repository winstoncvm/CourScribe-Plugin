jQuery(document).ready(function ($) {
    // Initialize Whisper model for speech-to-text
    let recorder;
    let audioChunks = [];
    let whisper;
    let transcribedText = '';
    
    // Load the Whisper model for speech-to-text
    $('[id^="status-"]').each(function() {
        const statusElement = $(this);
        statusElement.text('Loading model...');
    });
    
    import('https://cdn.jsdelivr.net/npm/@xenova/transformers@2.6.0').then(({ pipeline, env }) => {
        // Disable local model loading
        env.allowLocalModels = false;
        // Suppress ONNX Runtime warnings
        env.backends.onnx.loglevel = 'error';
        
        return pipeline('automatic-speech-recognition', 'Xenova/whisper-tiny.en', {
            cache_dir: null,
            local_files_only: false
        });
    }).then(function(model) {
        whisper = model;
        $('[id^="status-"]').text('Ready');
    }).catch(function(error) {
        console.error('Error loading model:', error);
        $('[id^="status-"]').text('Error: Unable to load model.');
    });

    // Voice recording functionality
    $(document).on('click', '.voice-button', function() {
        const container = $(this).closest('.AI-Input');
        const statusElement = container.find('[id^="status-"]');
        const chatInput = container.find('textarea');
        
        if (!recorder || recorder.state === 'inactive') {
            // Start recording
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Your browser does not support audio recording.');
                return;
            }

            container.addClass('recording');
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
                recorder = new MediaRecorder(stream);
                audioChunks = [];

                recorder.ondataavailable = function(event) {
                    audioChunks.push(event.data);
                };

                recorder.onstop = function() {
                    container.removeClass('recording');
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);

                    // Process audio with Whisper model
                    statusElement.text('Transcribing...');
                    whisper(audioUrl).then(function(transcription) {
                        transcribedText = transcription.text;
                        const currentText = $.trim(chatInput.val());

                        // Show append/replace prompt if textarea has content
                        if (currentText.length > 0) {
                            container.find('.transcription-prompt').addClass('active');
                            statusElement.text('Choose an option...');
                        } else {
                            // If no existing text, replace directly
                            chatInput.val(transcribedText);
                            statusElement.text('Ready');
                            URL.revokeObjectURL(audioUrl);
                        }
                    }).catch(function(error) {
                        console.error('Error transcribing:', error);
                        statusElement.text('Error: Unable to transcribe.');
                        container.removeClass('recording');
                        URL.revokeObjectURL(audioUrl);
                    });
                };

                recorder.start();
                statusElement.text('Recording...');
            }).catch(function(error) {
                console.error('Error accessing microphone:', error);
                statusElement.text('Error: Unable to access microphone.');
                container.removeClass('recording');
            });
        } else {
            // Stop recording
            if (recorder && recorder.state === 'recording') {
                recorder.stop();
            }
        }
    });

    // Dictation handling
    $(document).on('click', '.mic', function() {
        const container = $(this).closest('.AI-Input');
        const lessonId = container.data('lesson-id');
        const statusElement = container.find('[id^="status-"]');
        const chatInput = container.find(`#chat-input-${lessonId}`);
        if ($(this).is(':checked')) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Your browser does not support audio recording.');
                $(this).prop('checked', false);
                return;
            }
            $('.chat-container').addClass('recording');
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
                recorder = new MediaRecorder(stream);
                audioChunks = [];
                recorder.ondataavailable = function(event) {
                    audioChunks.push(event.data);
                };
                recorder.onstop = function() {
                    $('.chat-container').removeClass('recording');
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    statusElement.text('Transcribing...');
                    whisper(audioUrl).then(function(transcription) {
                        transcribedText = transcription.text;
                        const currentText = $.trim(chatInput.val());
                        if (currentText.length > 0) {
                            $('.transcription-prompt').addClass('active');
                            statusElement.text('Choose an option...');
                        } else {
                            chatInput.val(transcribedText);
                            statusElement.text('Ready');
                            $('#mic').prop('checked', false);
                            URL.revokeObjectURL(audioUrl);
                        }
                    }).catch(function(error) {
                        console.error('Error transcribing:', error);
                        statusElement.text('Error: Unable to transcribe.');
                        $('#mic').prop('checked', false);
                        $('.chat-container').removeClass('recording');
                        URL.revokeObjectURL(audioUrl);
                    });
                };
                recorder.start();
                statusElement.text('Recording...');
            }).catch(function(error) {
                console.error('Error accessing microphone:', error);
                statusElement.text('Error: Unable to access microphone.');
                $(this).prop('checked', false);
                $('.chat-container').removeClass('recording');
            });
        } else {
            if (recorder && recorder.state === 'recording') {
                recorder.stop();
            }
        }
    });

    // Append transcribed text
    $('#append-button').on('click', function() {
         const container = $(this).closest('.AI-Input');
        const lessonId = container.data('lesson-id');
        const statusElement = container.find('[id^="status-"]');
        const chatInput = container.find(`#chat-input-${lessonId}`);
        const currentText = chatInput.val();
        chatInput.val(currentText + (currentText ? ' ' : '') + transcribedText);
        $('.transcription-prompt').removeClass('active');
        statusElement.text('Ready');
        $(`#mic-${lessonId}`).prop('checked', false);
    });

    // Replace with transcribed text
    $('#replace-button').on('click', function() {
        const container = $(this).closest('.AI-Input');
        const lessonId = container.data('lesson-id');
        const statusElement = container.find('[id^="status-"]');
        const chatInput = container.find(`#chat-input-${lessonId}`);
        chatInput.val(transcribedText);
        $('.transcription-prompt').removeClass('active');
        statusElement.text('Ready');
        $(`#mic-${lessonId}`).prop('checked', false);
    });

    // AI Suggestions
    $(document).on('change', '.search-checkbox', function() {
    const container = $(this).closest('.AI-Input');
        if ($(this).is(':checked')) {
            const lessonName = $(this).data('lesson-name');
            const lessonGoal = $(this).data('lesson-goal');
            const lessonId = $(this).data('lesson-id');
            let teachingPoints = [];
        
            container.find('.teaching-points-list .ol-text').each(function() {
                let point = $(this).text().trim();
                if (point) teachingPoints.push(point);
            });
            const prompt = `As an instructional designer, please suggest 3 well-formed lesson teaching points based on this information:
                Lesson name: ${lessonName}
                Lesson goal: ${lessonGoal}
                Current Teaching points: ${teachingPoints.join(', ')}
                
                The teaching points should be specific, measurable, and aligned with the lesson goal and current teaching points. Format each suggestion with a number (1-3) followed by the teaching point, no explanations, just the short teaching point only`;
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: { action: 'get_ai_suggestions', prompt: prompt },
                beforeSend: function() {
                    $(this).text('Fetching AI suggestions...');
                },
                success: function(response) {
                    if (response.success) {
                        const suggestions = response.data.suggestions.map(s => s.replace(/^[\d\s\.\-]+/, '').trim());
                        updateMarquee(suggestions, lessonId);
                        $(this).text('AI suggestions loaded.');
                    } else {
                        $(this).text('Failed to get suggestions.');
                    }
                    $(this).prop('checked', false);
                },
                error: function() {
                    $(this).text('Error fetching suggestions.');
                   $(this).prop('checked', false);
                }
            });
        }
    });

    function updateMarquee(suggestions, lessonId) {
        const marqueeHtml = `
            <ul>
                ${suggestions.map(s => `<li><a href="#" class="suggestion">${s}</a></li>`).join('')}
            </ul>
            <ul>
                ${suggestions.map(s => `<li><a href="#" class="suggestion">${s}</a></li>`).join('')}
            </ul>`;
        $('#chat-marquee-' + lessonId).html(marqueeHtml);
    }

    // Handle append button
    $(document).on('click', '.append-button', function() {
        const container = $(this).closest('.AI-Input');
        const chatInput = container.find('textarea');
        const currentText = chatInput.val();
        chatInput.val(currentText + (currentText ? ' ' : '') + transcribedText);
        container.find('.transcription-prompt').removeClass('active');
        container.find('[id^="status-"]').text('Ready');
    });

    // Handle replace button
    $(document).on('click', '.replace-button', function() {
        const container = $(this).closest('.AI-Input');
        const chatInput = container.find('textarea');
        chatInput.val(transcribedText);
        container.find('.transcription-prompt').removeClass('active');
        container.find('[id^="status-"]').text('Ready');
    });

    // Handle suggestion click
    $(document).on('click', '.suggestion', function(e) {
        e.preventDefault();
        const text = $(this).text();
        const container = $(this).closest('.AI-Input');
        const lessonId = container.data('lesson-id');
        const newPoint = `
            <li>
                <span class="ol-text">${text}</span>
                <div class="ol-actions">
                    <button class="ol-icon-btn edit-teaching-point" title="Edit">✏️</button>
                    <button class="ol-icon-btn delete-teaching-point" title="Delete">🗑️</button>
                </div>
            </li>`;
        $('#teaching-points-list-' + lessonId).append(newPoint);
        $('#status').text('Suggestion added as teaching point.');
    });

    // Add new teaching point
    $(document).on('click', '.add-teaching-point', function() {
        let lessonId = $(this).data('lesson-id');
        if (!lessonId) {
            console.error('Lesson ID not found!');
            return;
        }
        
        // Focus the textarea in the corresponding AI-Input section
        $(`.AI-Input[data-lesson-id="${lessonId}"] textarea`).focus();
    });

    // Save teaching point from input
    $(document).on('click', '.save-teaching-point', function() {
        const container = $(this).closest('.AI-Input');
        const lessonId = container.data('lesson-id');
        const textarea = container.find('textarea');
        const text = textarea.val().trim();
        
        if (text) {
            const uniqueId = 'point-' + lessonId + '-' + Date.now();
            const newPoint = `
                <li data-point-id="${uniqueId}">
                    <span class="ol-text">${text}</span>
                    <div class="ol-actions">
                        <button class="ol-icon-btn edit-teaching-point" title="Edit">✏️</button>
                        <button class="ol-icon-btn delete-teaching-point" title="Delete">🗑️</button>
                    </div>
                </li>`;
            
            // Add to the top of the list
            $('#teaching-points-list-' + lessonId).prepend(newPoint);
            textarea.val('');
        }
    });

    // Edit teaching point
    $(document).on('click', '.edit-teaching-point', function() {
        const item = $(this).closest('li');
        const text = item.find('.ol-text').text();
        const lessonId = item.closest('.teaching-points-section').find('.AI-Input').data('lesson-id');
        
        // Populate the textarea
        $(`.AI-Input[data-lesson-id="${lessonId}"] textarea`).val(text).focus();
        
        // Remove the item (will be re-added if saved)
        item.remove();
    });

    // Delete teaching point
    $(document).on('click', '.delete-teaching-point', function() {
        $(this).closest('li').remove();
    });

    // Get AI suggestions
    $(document).on('click', '.ai-suggest-button', function() {
        const lessonId = $(this).data('lesson-id');
        const container = $(this).closest('.AI-Input');
        const marquee = container.find('.chat-marquee');
        
        // Get course/module/lesson data from the page (you'll need to adjust these selectors)
        const courseName = $('#course-title').text();
        const courseGoal = $('[name="_class_goal"]').val();
        const moduleName = $('.module-title').text();
        const moduleGoal = $('[name="module-goal"]').val();
        const lessonName = $(`#lesson-${lessonId} .lesson-title`).text();
        
        const prompt = `As an instructional designer, please suggest 4 well-formed lesson teaching points based on this information:

            Course Name: ${courseName}
            Course Goal: ${courseGoal}

            Module Name: ${moduleName}
            Module Goal: ${moduleGoal}

            Lesson Name: ${lessonName}

            The teaching points should be specific and aligned with the module goal and lesson objectives. 
            Format each suggestion with a number (1-4) followed by the teaching point.`;

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_ai_suggestions',
                prompt: prompt
            },
            beforeSend: function() {
                container.find('[id^="status-"]').text('Generating suggestions...');
            },
            success: function(response) {
                if (response.success) {
                    const suggestions = response.data.suggestions;
                    const listItems = suggestions.map(suggestion => 
                        `<li>${suggestion.replace(/^\d+\.\s*/, '')}</li>`
                    ).join('');
                    
                    marquee.find('ul').first().html(listItems);
                    marquee.find('ul').last().html(listItems);
                    marquee.show();
                    
                    // Make suggestions clickable
                    marquee.find('li').on('click', function() {
                        container.find('textarea').val($(this).text());
                        marquee.hide();
                    });
                    
                    container.find('[id^="status-"]').text('Ready');
                } else {
                    container.find('[id^="status-"]').text('Error: ' + (response.data.message || 'Failed to get suggestions'));
                }
            },
            error: function(xhr) {
                container.find('[id^="status-"]').text('Error: Request failed');
                console.error('Error getting AI suggestions:', xhr);
            }
        });
    });

    // Save all teaching points
    $(document).on('click', '.save-lesson-teaching-points', function() {
        let lessonId = $(this).data('lesson-id');
        let moduleId = $(this).data('module-id');
        let teachingPoints = [];
        
        $('#teaching-points-list-' + lessonId + ' .ol-text').each(function() {
            let point = $(this).text().trim();
            if (point) teachingPoints.push(point);
        });

        $.ajax({
            url: courscribeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_lesson_teaching_points',
                lesson_id: lessonId,
                module_id: moduleId,
                teaching_points: teachingPoints
            },
            success: function(response) {
                if (response.success) {
                    alert('Teaching points saved successfully!');
                } else {
                    alert('Failed to save teaching points: ' + response.data);
                }
            },
            error: function() {
                alert('Error saving teaching points.');
            }
        });
    });
});