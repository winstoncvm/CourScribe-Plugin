<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Curriculum JavaScript Components
 * Handles all JavaScript functionality for the curriculum page
 */
function courscribe_render_curriculum_scripts($localized_data) {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Localized data from PHP
        const courseScribeData = <?php echo json_encode($localized_data); ?>;
        
        // Initialize main functionality
        initializeCurriculumPage();
        initializeFeedbackSystem();
        initializeSortableElements();
        initializeTourGuide();
        
        /**
         * Initialize main curriculum page functionality
         */
        function initializeCurriculumPage() {
            // Accordion functionality
            initializeAccordion();
            
            // Course management
            initializeCourseManagement();
            
            // Module management
            initializeModuleManagement();
            
            // Lesson management
            initializeLessonManagement();
            
            // Auto-save functionality
            initializeAutoSave();
        }
        
        /**
         * Initialize accordion behavior
         */
        function initializeAccordion() {
            $('.accordion-button').on('click', function() {
                const $button = $(this);
                const targetId = $button.closest('.accordion-header').attr('id').replace('heading-', 'collapse-');
                const $target = $('#' + targetId);
                const $icon = $button.find('.custom-icon');
                
                if ($target.hasClass('show')) {
                    $target.removeClass('show');
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                } else {
                    $target.addClass('show');
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                }
            });
        }
        
        /**
         * Initialize course management functionality
         */
        function initializeCourseManagement() {
            // Course title editing
            $(document).on('blur', '.course-title-field', function() {
                const $input = $(this);
                const courseId = $input.attr('id').replace('course-name-', '');
                const newTitle = $input.val().trim();
                
                if (newTitle) {
                    saveCourseTitle(courseId, newTitle);
                }
            });
            
            // Course content editing
            $(document).on('blur', '.course-content-field', function() {
                const $textarea = $(this);
                const courseId = $textarea.attr('id').replace('course-content-', '');
                const newContent = $textarea.val();
                
                saveCourseContent(courseId, newContent);
            });
            
            // Course deletion
            $(document).on('click', '.delete-course', function() {
                const $button = $(this);
                const courseId = $button.data('course-id');
                
                if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                    deleteCourse(courseId);
                }
            });
        }
        
        /**
         * Initialize module management functionality
         */
        function initializeModuleManagement() {
            // Add module
            $(document).on('click', '.add-module-btn', function() {
                const $button = $(this);
                const courseId = $button.data('course-id');
                const curriculumId = $button.data('curriculum-id');
                
                addModule(courseId, curriculumId);
            });
            
            // Module title editing
            $(document).on('blur', '.module-title-field', function() {
                const $input = $(this);
                const moduleId = $input.attr('id').replace('module-name-', '');
                const newTitle = $input.val().trim();
                
                if (newTitle) {
                    saveModuleTitle(moduleId, newTitle);
                }
            });
            
            // Module content editing
            $(document).on('blur', '.module-content-field', function() {
                const $textarea = $(this);
                const moduleId = $textarea.attr('id').replace('module-content-', '');
                const newContent = $textarea.val();
                
                saveModuleContent(moduleId, newContent);
            });
            
            // Module deletion
            $(document).on('click', '.delete-module', function() {
                const $button = $(this);
                const moduleId = $button.data('module-id');
                
                if (confirm('Are you sure you want to delete this module? This action cannot be undone.')) {
                    deleteModule(moduleId);
                }
            });
        }
        
        /**
         * Initialize lesson management functionality
         */
        function initializeLessonManagement() {
            // Add lesson
            $(document).on('click', '.add-lesson-btn', function() {
                const $button = $(this);
                const moduleId = $button.data('module-id');
                const courseId = $button.data('course-id');
                const curriculumId = $button.data('curriculum-id');
                
                addLesson(moduleId, courseId, curriculumId);
            });
            
            // Lesson title editing
            $(document).on('blur', '.lesson-title-field', function() {
                const $input = $(this);
                const lessonId = $input.attr('id').replace('lesson-name-', '');
                const newTitle = $input.val().trim();
                
                if (newTitle) {
                    saveLessonTitle(lessonId, newTitle);
                }
            });
            
            // Lesson content editing
            $(document).on('blur', '.lesson-content-field', function() {
                const $textarea = $(this);
                const lessonId = $textarea.attr('id').replace('lesson-content-', '');
                const newContent = $textarea.val();
                
                saveLessonContent(lessonId, newContent);
            });
            
            // Lesson deletion
            $(document).on('click', '.delete-lesson', function() {
                const $button = $(this);
                const lessonId = $button.data('lesson-id');
                
                if (confirm('Are you sure you want to delete this lesson? This action cannot be undone.')) {
                    deleteLesson(lessonId);
                }
            });
        }
        
        /**
         * Initialize auto-save functionality
         */
        function initializeAutoSave() {
            let autoSaveTimeout;
            
            $(document).on('input', '.the-input-field, .course-content-field, .module-content-field, .lesson-content-field', function() {
                clearTimeout(autoSaveTimeout);
                const $field = $(this);
                
                autoSaveTimeout = setTimeout(function() {
                    if ($field.hasClass('course-content-field')) {
                        const courseId = $field.attr('id').replace('course-content-', '');
                        saveCourseContent(courseId, $field.val());
                    } else if ($field.hasClass('module-content-field')) {
                        const moduleId = $field.attr('id').replace('module-content-', '');
                        saveModuleContent(moduleId, $field.val());
                    } else if ($field.hasClass('lesson-content-field')) {
                        const lessonId = $field.attr('id').replace('lesson-content-', '');
                        saveLessonContent(lessonId, $field.val());
                    }
                }, 2000);
            });
        }
        
        /**
         * AJAX Functions for Course Management
         */
        function saveCourseTitle(courseId, title) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_course_title',
                    course_id: courseId,
                    title: title,
                    nonce: courseScribeData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#course-header-' + courseId + ' .course-title-span').text(title);
                    }
                }
            });
        }
        
        function saveCourseContent(courseId, content) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_course_content',
                    course_id: courseId,
                    content: content,
                    nonce: courseScribeData.nonce
                }
            });
        }
        
        function deleteCourse(courseId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_delete_course',
                    course_id: courseId,
                    nonce: courseScribeData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('[data-course-id="' + courseId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }
            });
        }
        
        /**
         * AJAX Functions for Module Management
         */
        function addModule(courseId, curriculumId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_add_module',
                    course_id: courseId,
                    curriculum_id: curriculumId,
                    nonce: courseScribeData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#modules-container-' + courseId).append(response.data.html);
                    }
                }
            });
        }
        
        function saveModuleTitle(moduleId, title) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_module_title',
                    module_id: moduleId,
                    title: title,
                    nonce: courseScribeData.nonce
                }
            });
        }
        
        function saveModuleContent(moduleId, content) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_module_content',
                    module_id: moduleId,
                    content: content,
                    nonce: courseScribeData.nonce
                }
            });
        }
        
        function deleteModule(moduleId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_delete_module',
                    module_id: moduleId,
                    nonce: courseScribeData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('[data-module-id="' + moduleId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }
            });
        }
        
        /**
         * AJAX Functions for Lesson Management
         */
        function addLesson(moduleId, courseId, curriculumId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_add_lesson',
                    module_id: moduleId,
                    course_id: courseId,
                    curriculum_id: curriculumId,
                    nonce: courseScribeData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#lessons-container-' + moduleId).append(response.data.html);
                    }
                }
            });
        }
        
        function saveLessonTitle(lessonId, title) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_lesson_title',
                    lesson_id: lessonId,
                    title: title,
                    nonce: courseScribeData.nonce
                }
            });
        }
        
        function saveLessonContent(lessonId, content) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_lesson_content',
                    lesson_id: lessonId,
                    content: content,
                    nonce: courseScribeData.nonce
                }
            });
        }
        
        function deleteLesson(lessonId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_delete_lesson',
                    lesson_id: lessonId,
                    nonce: courseScribeData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('[data-lesson-id="' + lessonId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }
            });
        }
        
        /**
         * Initialize sortable functionality
         */
        function initializeSortableElements() {
            // Make courses sortable
            if (typeof Sortable !== 'undefined') {
                const coursesAccordion = document.getElementById('coursesAccordion');
                if (coursesAccordion) {
                    new Sortable(coursesAccordion, {
                        handle: '.drag-handle',
                        animation: 150,
                        onEnd: function(evt) {
                            saveCourseOrder();
                        }
                    });
                }
            }
        }
        
        function saveCourseOrder() {
            const courseIds = [];
            $('#coursesAccordion .as-course').each(function() {
                courseIds.push($(this).data('course-id'));
            });
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_course_order',
                    course_ids: courseIds,
                    nonce: courseScribeData.nonce
                }
            });
        }
        
        /**
         * Initialize feedback system
         */
        function initializeFeedbackSystem() {
            // Feedback button handlers are included in the main feedback.js file
            console.log('Feedback system initialized');
        }
        
        /**
         * Initialize tour guide
         */
        function initializeTourGuide() {
            $(document).on('click', '.courscribe-help-toggle-single', function() {
                if (typeof TourGuideClient !== 'undefined') {
                    startCurriculumTour();
                }
            });
        }
        
        function startCurriculumTour() {
            const tourSteps = [
                {
                    title: "Welcome to Course Management",
                    content: "This is where you can manage your curriculum courses, modules, and lessons.",
                    target: ".courscribe-single-curriculum"
                },
                {
                    title: "Course Accordion",
                    content: "Click on course headers to expand and view modules and lessons.",
                    target: ".courscribe-xy-acc"
                },
                {
                    title: "Course Actions",
                    content: "Edit course titles, add content, and manage course structure here.",
                    target: ".courscribe-courses-header"
                }
            ];
            
            // Initialize tour guide if available
            if (typeof TourGuideClient !== 'undefined') {
                const tour = new TourGuideClient({
                    steps: tourSteps
                });
                tour.start();
            }
        }
        
        /**
         * Utility functions
         */
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
        
        // Global error handler
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
            // Optionally show user-friendly error message
        });
    });
    </script>
    <?php
}