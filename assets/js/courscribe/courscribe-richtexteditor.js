(function ($) {
    let editor = null;
    const editorId = 'courscribe-richtexteditor';
    const errorContainer = $('#courscribe-richtexteditor-error');
    const loadingContainer = $('#courscribe-richtexteditor-loading');

    console.log('courscribe-richtexteditor.js loaded');

    // Debounce utility
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

    // Fetch course data via AJAX
    function fetchCourseData(courseId) {
        console.log('Fetching course data for ID:', courseId);
        return new Promise((resolve, reject) => {
            if (!courscribeAjax || !courscribeAjax.ajaxurl || !courscribeAjax.nonce) {
                console.error('courscribeAjax not properly localized');
                reject(new Error('AJAX configuration missing'));
                return;
            }

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_get_the_course_data',
                    course_id: courseId,
                    nonce: courscribeAjax.nonce
                },
                beforeSend: function() {
                    loadingContainer.show();
                    errorContainer.hide();
                },
                success: function(response) {
                    loadingContainer.hide();
                    if (response.success) {
                        console.log('Course data received:', response.data);
                        resolve(response.data);
                    } else {
                        console.error('Error in response:', response.data?.message);
                        errorContainer.text('Error 45: ' + (response.data?.message || 'Unknown error')).show();
                        reject(new Error(response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    loadingContainer.hide();
                    console.error('AJAX error fetching course data:', status, error, xhr.responseText);
                    errorContainer.text('Error loading course data: ' + (xhr.status === 403 ? 'Access forbidden. Check permissions.' : error)).show();
                    reject(new Error(error));
                }
            });
        });
    }

    // Check existing content for the course
    function checkExistingContent(courseId) {
        console.log('Checking existing content for course ID:', courseId);
        return new Promise((resolve, reject) => {
            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_check_richtexteditor',
                    course_id: courseId,
                    nonce: courscribeAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Check content response:', response.data);
                        resolve({
                            exists: response.data.exists,
                            content: response.data.content
                        });
                    } else {
                        console.error('Error checking content:', response.data?.message);
                        reject(new Error(response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error checking content:', status, error);
                    reject(new Error(error));
                }
            });
        });
    }

    // Format course data into HTML for the editor
    function formatCourseData(courseData) {
        let html = '<div class="courscribe-editor-content">';

        // Course Title Section
        html += `
            <div class="text-center mb-6">
                <h1 class="text-5xl font-bold text-white">${courseData.title || 'Untitled Course'}</h1>
                <p class="text-3xl mt-4 text-gray-300">Course Overview</p>
            </div>
            <hr class="my-4 border-gray-600">
        `;

        // Course Details Section
        html += `
            <div class="ml-12 slide-content mb-6">
                <div class="slide-title text-2xl font-semibold text-white">Course Details</div>
                <div class="slide-line h-1 bg-gray-600 my-2"></div>
                <p class="text-gray-300"><strong>Goal:</strong> ${courseData.goal || 'Not set'}</p>
                <p class="text-gray-300"><strong>Level:</strong> ${courseData.level || 'Not set'}</p>
            </div>
        `;

        // Course Objectives
        if (Array.isArray(courseData.objectives) && courseData.objectives.length > 0) {
            html += `
                <div class="ml-12 slide-content mb-6">
                    <div class="slide-title text-2xl font-semibold text-white">Course Objectives</div>
                    <div class="slide-line h-1 bg-gray-600 my-2"></div>
                    <ul class="list-disc ml-6 text-gray-300">
            `;
            courseData.objectives.forEach(obj => {
                html += `
                    <li>${obj.thinking_skill || 'Not set'} - ${obj.action_verb || 'Not set'}: ${obj.description || 'No description'}</li>
                `;
            });
            html += `
                    </ul>
                </div>
            `;
        }

        // Modules
        if (Array.isArray(courseData.modules) && courseData.modules.length > 0) {
            courseData.modules.forEach((module, moduleIndex) => {
                html += `
                    <div class="ml-12 slide-content mb-6">
                        <div class="slide-title text-2xl font-semibold text-white">Module ${moduleIndex + 1}: ${module.title || 'Untitled Module'}</div>
                        <div class="slide-line h-1 bg-gray-600 my-2"></div>
                        <p class="text-gray-300"><strong>Goal:</strong> ${module.goal || 'Not set'}</p>
                    </div>
                `;

                // Module Objectives
                if (Array.isArray(module.objectives) && module.objectives.length > 0) {
                    html += `
                        <div class="ml-12 slide-content mb-6">
                            <div class="slide-title text-xl font-semibold text-white">Module Objectives</div>
                            <div class="slide-line h-1 bg-gray-600 my-2"></div>
                            <ul class="list-disc ml-6 text-gray-300">
                    `;
                    module.objectives.forEach(obj => {
                        html += `
                            <li>${obj.thinking_skill || 'Not set'} - ${obj.action_verb || 'Not set'}: ${obj.description || 'No description'}</li>
                        `;
                    });
                    html += `
                            </ul>
                        </div>
                    `;
                }

                // Module Methods
                if (Array.isArray(module.methods) && module.methods.length > 0) {
                    html += `
                        <div class="ml-12 slide-content mb-6">
                            <div class="slide-title text-xl font-semibold text-white">Teaching Methods</div>
                            <div class="slide-line h-1 bg-gray-600 my-2"></div>
                            <ul class="list-disc ml-6 text-gray-300">
                    `;
                    module.methods.forEach(method => {
                        html += `
                            <li>
                                ${method.thinking_skill || 'Not set'} - ${method.action_verb || 'Not set'} 
                                (${method.teaching_strategy || 'No strategy'}): 
                                <a href="${method.add_link || '#'}" target="_blank" class="text-blue-400 hover:underline">${method.add_link || 'No link'}</a>
                            </li>
                        `;
                    });
                    html += `
                            </ul>
                        </div>
                    `;
                }

                // Module Materials
                if (Array.isArray(module.materials) && module.materials.length > 0) {
                    html += `
                        <div class="ml-12 slide-content mb-6">
                            <div class="slide-title text-xl font-semibold text-white">Materials</div>
                            <div class="slide-line h-1 bg-gray-600 my-2"></div>
                            <ul class="list-disc ml-6 text-gray-300">
                    `;
                    module.materials.forEach(material => {
                        html += `
                            <li>
                                ${material.thinking_skill || 'Not set'} - ${material.learner_activities || 'Not set'}: 
                                <a href="${material.add_link || '#'}" target="_blank" class="text-blue-400 hover:underline">${material.add_link || 'No link'}</a>
                            </li>
                        `;
                    });
                    html += `
                            </ul>
                        </div>
                    `;
                }

                // Module Activities
                if (Array.isArray(module.activities) && module.activities.length > 0) {
                    html += `
                        <div class="ml-12 slide-content mb-6">
                            <div class="slide-title text-xl font-semibold text-white">Activities</div>
                            <div class="slide-line h-1 bg-gray-600 my-2"></div>
                            <ul class="list-disc ml-6 text-gray-300">
                    `;
                    module.activities.forEach(activity => {
                        html += `
                            <li>${activity || 'No activity'}</li>
                        `;
                    });
                    html += `
                            </ul>
                        </div>
                    `;
                }

                // Lessons
                if (Array.isArray(module.lessons) && module.lessons.length > 0) {
                    module.lessons.forEach((lesson, lessonIndex) => {
                        html += `
                            <div class="ml-12 slide-content mb-6">
                                <div class="slide-title text-xl font-semibold text-white">Lesson ${lessonIndex + 1}: ${lesson.title || 'Untitled Lesson'}</div>
                                <div class="slide-line h-1 bg-gray-600 my-2"></div>
                                <p class="text-gray-300"><strong>Goal:</strong> ${lesson.goal || 'Not set'}</p>
                            </div>
                        `;

                        // Lesson Objectives
                        if (Array.isArray(lesson.objectives) && lesson.objectives.length > 0) {
                            html += `
                                <div class="ml-12 slide-content mb-6">
                                    <div class="slide-title text-lg font-semibold text-white">Lesson Objectives</div>
                                    <div class="slide-line h-1 bg-gray-600 my-2"></div>
                                    <ul class="list-disc ml-6 text-gray-300">
                            `;
                            lesson.objectives.forEach(obj => {
                                html += `
                                    <li>${obj.thinking_skill || 'Not set'} - ${obj.action_verb || 'Not set'}: ${obj.description || 'No description'}</li>
                                `;
                            });
                            html += `
                                    </ul>
                                </div>
                            `;
                        }

                        // Lesson Teaching Points
                        if (Array.isArray(lesson.teaching_points) && lesson.teaching_points.length > 0) {
                            html += `
                                <div class="ml-12 slide-content mb-6">
                                    <div class="slide-title text-lg font-semibold text-white">Teaching Points</div>
                                    <div class="slide-line h-1 bg-gray-600 my-2"></div>
                                    <ul class="list-disc ml-6 text-gray-300">
                            `;
                            lesson.teaching_points.forEach(point => {
                                html += `
                                    <li>${point || 'No teaching point'}</li>
                                `;
                            });
                            html += `
                                    </ul>
                                </div>
                            `;
                        }
                    });
                }

                html += `<hr class="my-4 border-gray-600">`;
            });
        } else {
            html += `
                <div class="ml-12 slide-content mb-6">
                    <div class="slide-title text-2xl font-semibold text-white">No Modules</div>
                    <div class="slide-line h-1 bg-gray-600 my-2"></div>
                    <p class="text-gray-300">No modules available for this course.</p>
                </div>
            `;
        }

        html += '</div>';
        return html;
    }

    // Initialize editor with course data or existing content
    async function initializeEditor(courseId) {
        console.log('Initializing editor for course ID:', courseId);
        try {
            if (typeof RichTextEditor === 'undefined') {
                console.error('RichTextEditor library not loaded');
                errorContainer.text('Editor library failed to load. Please try again later.').show();
                loadingContainer.hide();
                return;
            }

            // Check for existing content first
            const existingContent = await checkExistingContent(courseId);

            if (existingContent.exists && existingContent.content) {
                // Load existing content
                if (document.getElementById(editorId)) {
                    try {
                        editor = new RichTextEditor(`#${editorId}`, {
                            toolbar: 'full',
                            css: ['/wp-content/plugins/courscribe/assets/css/richtexteditor.css']
                        });
                        editor.setHTMLCode(existingContent.content);
                        console.log('Loaded existing content into editor');
                        loadingContainer.hide();
                        $(`#${editorId}`).show();
                    } catch (error) {
                        console.error('Failed to initialize RichTextEditor with existing content:', error);
                        errorContainer.text('Error initializing editor: ' + error.message).show();
                        loadingContainer.hide();
                    }
                } else {
                    console.error(`Editor element #${editorId} not found`);
                    errorContainer.text(`Editor element #${editorId} not found.`).show();
                    loadingContainer.hide();
                }
            } else {
                // Fetch and load course data
                const courseData = await fetchCourseData(courseId);
                if (document.getElementById(editorId)) {
                    try {
                        editor = new RichTextEditor(`#${editorId}`, {
                            toolbar: 'full',
                            css: ['/wp-content/plugins/courscribe/assets/css/richtexteditor.css']
                        });
                        const formattedContent = formatCourseData(courseData);
                        editor.setHTMLCode(formattedContent);
                        console.log('RichTextEditor initialized with course data');
                        loadingContainer.hide();
                        $(`#${editorId}`).show();
                    } catch (error) {
                        console.error('Failed to initialize RichTextEditor with course data:', error);
                        errorContainer.text('Error initializing editor: ' + error.message).show();
                        loadingContainer.hide();
                    }
                } else {
                    console.error(`Editor element #${editorId} not found`);
                    errorContainer.text(`Editor element #${editorId} not found.`).show();
                    loadingContainer.hide();
                }
            }

        } catch (error) {
            console.error('Failed to initialize editor:', error);
            errorContainer.text('Error loading content: ' + error.message).show();
            loadingContainer.hide();
        }
    }

    // Save Content
    $('#courscribe-save-richtexteditor').on('click', debounce(function () {
        if (!editor) {
            console.error('Editor not initialized');
            errorContainer.text('Editor not initialized. Please reload the page.').show();
            return;
        }

        try {
            const content = editor.getHTMLCode();
            console.log('Saving content:', content);
            const courseId = $('#courscribeEditDocumentOffcanvas').attr('data-course-id') || courscribeAjax.course_id;

            const $saveButton = $(this);
            const originalText = $saveButton.find('.text-for-save').text();

            $.ajax({
                url: courscribeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_save_richtexteditor',
                    course_id: courseId,
                    content: content,
                    nonce: courscribeAjax.nonce
                },
                beforeSend: function() {
                    $saveButton.prop('disabled', true);
                    $saveButton.find('.text-for-save').text('Saving...');
                },
                success: function(response) {
                    $saveButton.prop('disabled', false);
                    $saveButton.find('.text-for-save').text(originalText);

                    if (response.success) {
                        console.log('Content saved successfully');
                        alert('Content saved successfully!');
                    } else {
                        console.error('Error saving:', response.data?.message);
                        errorContainer.text('Error saving: ' + (response.data?.message || 'Unknown error')).show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error saving content:', status, error);
                    $saveButton.prop('disabled', false);
                    $saveButton.find('.text-for-save').text(originalText);
                    errorContainer.text('Error saving content: ' + (xhr.status === 403 ? 'Access forbidden. Check permissions.' : error)).show();
                }
            });
        } catch (error) {
            console.error('Error getting content:', error);
            errorContainer.text('Error saving: ' + error.message).show();
        }
    }, 500));

    // Reload Course Data
    $('#courscribe-reload-course-data').on('click', async function () {
        if (!confirm('Are you sure you want to reload the original course data? This will overwrite any unsaved changes.')) {
            return;
        }

        const courseId = $('#courscribeEditDocumentOffcanvas').attr('data-course-id') || courscribeAjax.course_id;
        console.log('Reloading course data for ID:', courseId);

        try {
            const courseData = await fetchCourseData(courseId);
            if (editor) {
                const formattedContent = formatCourseData(courseData);
                editor.setHTMLCode(formattedContent);
                console.log('Reloaded course data into editor');
                alert('Course data reloaded successfully!');
                errorContainer.hide();
            } else {
                console.error('Editor not initialized for reload');
                errorContainer.text('Editor not initialized. Please reload the page.').show();
            }
        } catch (error) {
            console.error('Failed to reload course data:', error);
            errorContainer.text('Error reloading course data: ' + error.message).show();
        }
    });

    // Load course data when offcanvas opens
    $('#courscribeEditDocumentOffcanvas').on('shown.bs.offcanvas', function(e) {
        const button = $(e.relatedTarget);
        const courseId = button.data('course-id') || courscribeAjax.course_id;
        console.log('Offcanvas opened for course ID:', courseId);
        $(this).attr('data-course-id', courseId);

        // Show loading, hide editor and errors
        $(`#${editorId}`).hide();
        loadingContainer.show();
        errorContainer.hide();

        // Initialize editor
        initializeEditor(courseId);
    });

})(jQuery);