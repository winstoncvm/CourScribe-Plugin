/**
 * CourScribe Curriculum Content Builder - Main JavaScript
 * Modern curriculum development interface with drag & drop, AI integration, and templates
 */

(function($) {
    'use strict';

    /**
     * Main Curriculum Builder Application Class
     */
    window.CourScribeCurriculumBuilderApp = class {
        constructor() {
            this.config = {};
            this.curriculum = {};
            this.courses = [];
            this.permissions = {};
            this.isDirty = false;
            this.autoSaveTimer = null;
            
            // Bind methods
            this.init = this.init.bind(this);
            this.setupEventListeners = this.setupEventListeners.bind(this);
            this.handleSave = this.handleSave.bind(this);
            this.handleAutoSave = this.handleAutoSave.bind(this);
            this.toggleAIPanel = this.toggleAIPanel.bind(this);
            this.showTemplates = this.showTemplates.bind(this);
            this.hideTemplates = this.hideTemplates.bind(this);
        }

        /**
         * Initialize the application
         */
        init(config) {
            this.config = config;
            this.curriculum = config.curriculum;
            this.courses = config.courses;
            this.permissions = config.permissions;
            
            console.log('CourScribe Curriculum Builder initialized', config);
            
            this.setupEventListeners();
            this.initializeComponents();
            this.initializeEditors();
            this.setupDebouncedSaves();
            this.startAutoSave();
            
            // Show welcome message or tutorial for first-time users
            this.checkFirstTimeUser();
        }

        /**
         * Setup all event listeners
         */
        setupEventListeners() {
            // Header button actions
            $('#ccbSaveBtn').on('click', this.handleSave);
            $('#ccbPreviewBtn').on('click', this.handlePreview.bind(this));
            $('#ccbTemplatesBtn').on('click', this.showTemplates);
            $('#ccbAIAssistantBtn').on('click', this.toggleAIPanel);
            $('#ccbExportBtn').on('click', this.handleExport.bind(this));

            // AI Panel events
            $('#ccbAIPanel .ccb-close-btn').on('click', this.toggleAIPanel);
            $('#ccbAIPanel').on('click', '.ccb-ai-suggestion', this.handleAISuggestion.bind(this));

            // Templates modal events
            $('#ccbTemplatesModal .ccb-close-btn').on('click', this.hideTemplates);
            $('#ccbTemplatesModal').on('click', '.ccb-template-card', this.handleTemplateSelect.bind(this));

            // Content change tracking
            $(document).on('input change', '.ccb-content-editable, .ccb-form-input', this.markAsDirty.bind(this));

            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));

            // Window events
            $(window).on('beforeunload', this.handleBeforeUnload.bind(this));
            $(window).on('resize', this.handleResize.bind(this));

            // Mobile sidebar toggle
            $('.ccb-mobile-menu-btn').on('click', this.toggleSidebar.bind(this));

            // Course management events
            $(document).on('click', '.ccb-course-edit-btn', this.handleCourseEdit.bind(this));
            $(document).on('click', '.ccb-course-ai-btn', this.handleCourseAI.bind(this));
            $(document).on('click', '.ccb-course-delete-btn', this.handleCourseDelete.bind(this));
            $(document).on('click', '.ccb-add-course-btn, #ccbAddFirstCourse', this.handleAddCourse.bind(this));
            $(document).on('click', '.ccb-course-collapse-btn', this.handleCourseCollapse.bind(this));

            // Module management events
            $(document).on('click', '.ccb-module-edit-btn', this.handleModuleEdit.bind(this));
            $(document).on('click', '.ccb-module-ai-btn', this.handleModuleAI.bind(this));
            $(document).on('click', '.ccb-module-delete-btn', this.handleModuleDelete.bind(this));
            $(document).on('click', '.ccb-add-module-btn, .ccb-add-module-link', this.handleAddModule.bind(this));
            $(document).on('click', '.ccb-module-collapse-btn', this.handleModuleCollapse.bind(this));

            // Lesson management events
            $(document).on('click', '.ccb-lesson-edit-btn', this.handleLessonEdit.bind(this));
            $(document).on('click', '.ccb-lesson-ai-btn', this.handleLessonAI.bind(this));
            $(document).on('click', '.ccb-lesson-delete-btn', this.handleLessonDelete.bind(this));
            $(document).on('click', '.ccb-add-lesson-btn, .ccb-add-lesson-link', this.handleAddLesson.bind(this));
            $(document).on('click', '.ccb-lesson-collapse-btn', this.handleLessonCollapse.bind(this));

            // Objective management events
            $(document).on('click', '.ccb-add-objective-btn', this.handleAddObjective.bind(this));
            $(document).on('click', '.ccb-objective-delete-btn', this.handleDeleteObjective.bind(this));
            $(document).on('change', '.ccb-thinking-skill-select', this.handleObjectiveChange.bind(this));
            $(document).on('input', '.ccb-objective-description', this.handleObjectiveChange.bind(this));

            // Teaching Points management events
            $(document).on('click', '.ccb-add-teaching-point-btn, .ccb-add-teaching-point-link', this.handleAddTeachingPoint.bind(this));
            $(document).on('click', '.ccb-teaching-point-delete-btn', this.handleDeleteTeachingPoint.bind(this));
            $(document).on('input', '.ccb-teaching-point-title, .ccb-teaching-point-description, .ccb-teaching-point-example, .ccb-teaching-point-activity', this.handleTeachingPointChange.bind(this));

            // Methods, Materials, Media management events
            $(document).on('click', '.ccb-add-method-btn, .ccb-add-method-link', this.handleAddMethod.bind(this));
            $(document).on('click', '.ccb-method-delete-btn', this.handleDeleteMethod.bind(this));
            $(document).on('click', '.ccb-add-material-btn, .ccb-add-material-link', this.handleAddMaterial.bind(this));
            $(document).on('click', '.ccb-material-delete-btn', this.handleDeleteMaterial.bind(this));
            $(document).on('click', '.ccb-add-media-btn, .ccb-add-media-link', this.handleAddMedia.bind(this));
            $(document).on('click', '.ccb-media-delete-btn', this.handleDeleteMedia.bind(this));

            // Content editing events
            $(document).on('input', '.ccb-method-input', this.handleMethodChange.bind(this));
            $(document).on('input', '.ccb-material-title, .ccb-material-description', this.handleMaterialChange.bind(this));
            $(document).on('input change', '.ccb-media-type, .ccb-media-title, .ccb-media-url', this.handleMediaChange.bind(this));

            // Inline content editing
            $(document).on('blur', '[contenteditable="true"]', this.handleInlineEdit.bind(this));
            $(document).on('keydown', '[contenteditable="true"]', this.handleInlineEditKeydown.bind(this));

            // Template and AI events
            $(document).on('click', '#ccbBrowseTemplates', this.showTemplates);
            $(document).on('click', '.ccb-drop-zone', this.handleDropZoneClick.bind(this));

            // Field auto-save events
            $(document).on('input change', '.ccb-field-input, .ccb-field-textarea, .ccb-field-select', this.handleFieldChange.bind(this));
        }

        /**
         * Initialize all components
         */
        initializeComponents() {
            this.initializeDragDrop();
            this.initializeRichTextEditor();
            this.initializeTabs();
            this.initializeTooltips();
            
            console.log('All components initialized');
        }
        
        /**
         * Initialize Editor.js instances
         */
        initializeEditors() {
            // Wait for Editor.js manager to be available
            if (window.CourScribeEditorManager) {
                console.log('Editor.js manager available, editors will auto-initialize');
                // Editor.js manager handles initialization automatically via DOMContentLoaded
            } else {
                console.warn('CourScribeEditorManager not available, retrying in 1 second...');
                setTimeout(() => this.initializeEditors(), 1000);
            }
        }

        /**
         * Initialize drag and drop functionality
         */
        initializeDragDrop() {
            if (!window.Sortable) {
                console.warn('Sortable.js not loaded - drag and drop disabled');
                return;
            }

            console.log('Initializing enhanced drag and drop');
            
            // Initialize course-level sorting
            this.initializeCourseSorting();
            
            // Initialize module sorting within courses  
            this.initializeModuleSorting();
            
            // Initialize objective sorting
            this.initializeObjectiveSorting();
            
            // Add visual feedback CSS
            this.addDragAndDropStyles();
            
            console.log('Enhanced drag and drop initialized');
        }

        /**
         * Initialize course-level drag and drop
         */
        initializeCourseSorting() {
            const coursesContainer = document.getElementById('ccbCoursesContainer');
            if (!coursesContainer) {
                console.log('Courses container not found');
                return;
            }

            this.courseSortable = Sortable.create(coursesContainer, {
                handle: '.ccb-course-drag-handle',
                animation: 300,
                ghostClass: 'ccb-course-ghost',
                chosenClass: 'ccb-course-chosen',
                dragClass: 'ccb-course-drag',
                forceFallback: true,
                fallbackClass: 'ccb-course-fallback',
                
                onStart: (evt) => {
                    console.log('Started dragging course:', evt.item.dataset.courseId);
                    evt.item.classList.add('ccb-dragging');
                    coursesContainer.classList.add('ccb-container-dragging');
                    this.showCourseDropZones();
                    this.showNotification('Drag to reorder courses', 'info', 2000);
                },
                
                onEnd: (evt) => {
                    console.log('Finished dragging course');
                    evt.item.classList.remove('ccb-dragging');
                    coursesContainer.classList.remove('ccb-container-dragging');
                    this.hideCourseDropZones();
                    
                    // Update course order if position changed
                    if (evt.oldIndex !== evt.newIndex) {
                        this.updateCourseOrder(evt);
                        this.showNotification('Course order updated!', 'success', 3000);
                    }
                },
                
                onMove: (evt) => {
                    // Provide visual feedback during drag
                    return this.validateCourseDrop(evt);
                }
            });
            
            console.log('Course sorting initialized');
        }

        /**
         * Initialize module sorting within courses
         */
        initializeModuleSorting() {
            // Initialize for existing module containers
            this.refreshModuleSorting();
            
            // Re-initialize when new courses are added
            $(document).on('courseAdded', () => {
                setTimeout(() => this.refreshModuleSorting(), 100);
            });
        }

        /**
         * Refresh module sorting for all module containers
         */
        refreshModuleSorting() {
            const moduleContainers = document.querySelectorAll('.ccb-modules-list');
            
            moduleContainers.forEach((container, index) => {
                // Destroy existing sortable if it exists
                if (container.sortableInstance) {
                    container.sortableInstance.destroy();
                }
                
                const courseId = container.getAttribute('data-course-id');
                
                container.sortableInstance = Sortable.create(container, {
                    handle: '.ccb-module-drag-handle',
                    animation: 250,
                    ghostClass: 'ccb-module-ghost',
                    chosenClass: 'ccb-module-chosen',
                    dragClass: 'ccb-module-drag',
                    
                    onStart: (evt) => {
                        evt.item.classList.add('ccb-dragging');
                        container.classList.add('ccb-container-dragging');
                        this.showNotification('Reordering modules...', 'info', 2000);
                    },
                    
                    onEnd: (evt) => {
                        evt.item.classList.remove('ccb-dragging');
                        container.classList.remove('ccb-container-dragging');
                        
                        if (evt.oldIndex !== evt.newIndex) {
                            this.updateModuleOrder(evt, courseId);
                            this.showNotification('Module order updated!', 'success', 3000);
                        }
                    }
                });
            });
            
            console.log('Module sorting refreshed for', moduleContainers.length, 'containers');
        }

        /**
         * Initialize objective sorting
         */
        initializeObjectiveSorting() {
            // Use delegation for dynamic content
            $(document).on('mouseenter', '.ccb-objectives-list', (e) => {
                const container = e.target;
                if (container.sortableInstance) return; // Already initialized
                
                const courseId = container.getAttribute('data-course-id');
                
                container.sortableInstance = Sortable.create(container, {
                    handle: '.ccb-objective-drag-handle',
                    animation: 200,
                    ghostClass: 'ccb-objective-ghost',
                    chosenClass: 'ccb-objective-chosen',
                    
                    onEnd: (evt) => {
                        if (evt.oldIndex !== evt.newIndex) {
                            this.updateObjectiveOrder(evt, courseId);
                            this.showNotification('Objective order updated!', 'success', 2000);
                        }
                    }
                });
            });
            
            console.log('Objective sorting delegation initialized');
        }

        /**
         * Initialize rich text editor for content blocks
         */
        initializeRichTextEditor() {
            $('.ccb-rich-text-editor').each(function() {
                // TODO: Integrate with WordPress block editor or TinyMCE
                // For now, use contenteditable with basic formatting
                $(this).attr('contenteditable', 'true');
                $(this).on('input', function() {
                    // Handle content changes
                });
            });
        }

        /**
         * Initialize tab system
         */
        initializeTabs() {
            $(document).on('click', '.ccb-tab-btn', function() {
                const tabId = $(this).data('tab');
                const tabGroup = $(this).data('tab-group');
                
                // Update button states
                $(`.ccb-tab-btn[data-tab-group="${tabGroup}"]`).removeClass('active');
                $(this).addClass('active');
                
                // Update content visibility
                $(`.ccb-tab-content[data-tab-group="${tabGroup}"]`).removeClass('active');
                $(`#${tabId}`).addClass('active');
            });
        }

        /**
         * Initialize tooltips
         */
        initializeTooltips() {
            $('[data-tooltip]').each(function() {
                const tooltip = $(this).data('tooltip');
                $(this).attr('title', tooltip);
            });
        }

        /**
         * Start auto-save functionality
         */
        startAutoSave() {
            this.autoSaveTimer = setInterval(() => {
                if (this.isDirty && this.permissions.canEdit) {
                    this.handleAutoSave();
                }
            }, 30000); // Auto-save every 30 seconds
        }

        /**
         * Mark content as dirty (needs saving)
         */
        markAsDirty() {
            this.isDirty = true;
            $('#ccbSaveBtn').removeClass('ccb-btn-secondary').addClass('ccb-btn-primary');
            $('.ccb-save-indicator').text('Unsaved changes');
        }

        /**
         * Handle manual save
         */
        handleSave() {
            if (!this.permissions.canEdit) {
                this.showNotification('You do not have permission to save changes', 'error');
                return;
            }

            this.showLoading('Saving changes...');
            
            const contentData = this.gatherContentData();
            
            $.ajax({
                url: CourScribeCurriculumBuilder.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_save_curriculum_content',
                    curriculum_id: this.curriculum.id,
                    content_data: JSON.stringify(contentData),
                    nonce: CourScribeCurriculumBuilder.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.isDirty = false;
                        $('#ccbSaveBtn').removeClass('ccb-btn-primary').addClass('ccb-btn-secondary');
                        $('.ccb-save-indicator').text('All changes saved');
                        this.showNotification('Changes saved successfully', 'success');
                    } else {
                        this.showNotification('Failed to save changes: ' + response.data.message, 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showNotification('Error saving changes. Please try again.', 'error');
                }
            });
        }

        /**
         * Handle auto-save
         */
        handleAutoSave() {
            const contentData = this.gatherContentData();
            
            $.ajax({
                url: CourScribeCurriculumBuilder.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_save_curriculum_content',
                    curriculum_id: this.curriculum.id,
                    content_data: JSON.stringify(contentData),
                    nonce: CourScribeCurriculumBuilder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.isDirty = false;
                        $('.ccb-save-indicator').text('Auto-saved');
                    }
                }
            });
        }

        /**
         * Gather all content data for saving
         */
        gatherContentData() {
            const data = {
                curriculum: {
                    id: this.curriculum.id,
                    title: $('#ccbCurriculumTitle').val() || this.curriculum.title,
                    goal: $('#ccbCurriculumGoal').val() || this.curriculum.goal,
                    overview: $('#ccbCurriculumOverview').html() || '',
                    objectives: this.gatherObjectives()
                },
                courses: this.gatherCoursesData(),
                metadata: {
                    lastModified: new Date().toISOString(),
                    version: '1.0',
                    format: 'courscribe-curriculum-builder'
                }
            };
            
            return data;
        }

        /**
         * Gather objectives data
         */
        gatherObjectives() {
            const objectives = [];
            $('.ccb-objective-item').each(function() {
                const objective = {
                    id: $(this).data('objective-id'),
                    title: $(this).find('.ccb-objective-title').val(),
                    description: $(this).find('.ccb-objective-description').val(),
                    type: $(this).find('.ccb-objective-type').val(),
                    order: $(this).index()
                };
                objectives.push(objective);
            });
            return objectives;
        }

        /**
         * Gather courses data
         */
        gatherCoursesData() {
            const courses = [];
            $('.ccb-course-item').each(function() {
                const course = {
                    id: $(this).data('course-id'),
                    title: $(this).find('.ccb-course-title').val(),
                    description: $(this).find('.ccb-course-description').html(),
                    goal: $(this).find('.ccb-course-goal').val(),
                    order: $(this).index(),
                    modules: []
                };
                
                // Gather modules for this course
                $(this).find('.ccb-module-item').each(function() {
                    const module = {
                        id: $(this).data('module-id'),
                        title: $(this).find('.ccb-module-title').val(),
                        description: $(this).find('.ccb-module-description').html(),
                        order: $(this).index()
                    };
                    course.modules.push(module);
                });
                
                courses.push(course);
            });
            return courses;
        }

        /**
         * Toggle AI assistant panel
         */
        toggleAIPanel() {
            $('#ccbAIPanel').toggleClass('open');
            
            if ($('#ccbAIPanel').hasClass('open')) {
                this.loadAISuggestions();
            }
        }

        /**
         * Load AI suggestions based on current context
         */
        loadAISuggestions() {
            if (!this.permissions.canUseAI) {
                $('#ccbAIPanel .ccb-ai-content').html('<p class="ccb-text-muted">AI features are not available for your account.</p>');
                return;
            }

            const suggestions = [
                {
                    title: 'Add Learning Objectives',
                    description: 'Generate learning objectives based on your curriculum goals',
                    action: 'generate-objectives'
                },
                {
                    title: 'Create Course Outline',
                    description: 'Generate a structured course outline with modules and lessons',
                    action: 'generate-course-outline'
                },
                {
                    title: 'Improve Content',
                    description: 'Enhance your content with AI-powered suggestions',
                    action: 'improve-content'
                },
                {
                    title: 'Generate Assessment',
                    description: 'Create assessments and quizzes for your curriculum',
                    action: 'generate-assessment'
                }
            ];

            let html = '<div class="ccb-ai-suggestions">';
            suggestions.forEach(suggestion => {
                html += `
                    <div class="ccb-ai-suggestion" data-action="${suggestion.action}">
                        <h4 class="ccb-ai-suggestion-title">${suggestion.title}</h4>
                        <p class="ccb-ai-suggestion-description">${suggestion.description}</p>
                    </div>
                `;
            });
            html += '</div>';

            $('#ccbAIPanel .ccb-ai-content').html(html);
        }

        /**
         * Handle AI suggestion click
         */
        handleAISuggestion(event) {
            const action = $(event.currentTarget).data('action');
            const context = this.gatherContextForAI();
            
            this.generateAIContent(action, context);
        }

        /**
         * Generate AI content
         */
        generateAIContent(contentType, context) {
            this.showLoading('Generating content with AI...');
            
            $.ajax({
                url: CourScribeCurriculumBuilder.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_generate_ai_content',
                    curriculum_id: this.curriculum.id,
                    content_type: contentType,
                    context: context,
                    nonce: CourScribeCurriculumBuilder.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.insertAIGeneratedContent(response.data.content, response.data.type);
                        this.showNotification('AI content generated successfully', 'success');
                        this.markAsDirty();
                    } else {
                        this.showNotification('Failed to generate content: ' + response.data.message, 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showNotification('Error generating AI content. Please try again.', 'error');
                }
            });
        }

        /**
         * Gather context for AI generation
         */
        gatherContextForAI() {
            return {
                curriculum_title: this.curriculum.title,
                curriculum_goal: this.curriculum.goal,
                curriculum_topic: this.curriculum.topic,
                existing_content: $('.ccb-content-section').length,
                course_count: this.courses.length
            };
        }

        /**
         * Insert AI-generated content into the document
         */
        insertAIGeneratedContent(content, type) {
            // TODO: Implement content insertion based on type
            console.log('Inserting AI content:', type, content);
        }

        /**
         * Show templates modal
         */
        showTemplates() {
            $('#ccbTemplatesModal').fadeIn(300);
            this.loadTemplates();
        }

        /**
         * Hide templates modal
         */
        hideTemplates() {
            $('#ccbTemplatesModal').fadeOut(300);
        }

        /**
         * Load available templates
         */
        loadTemplates() {
            // TODO: Load actual templates from server
            const templates = [
                {
                    id: 'web-dev-course',
                    title: 'Web Development Course',
                    description: 'Complete frontend and backend development curriculum',
                    category: 'technology',
                    preview: 'assets/images/templates/web-dev.jpg'
                },
                {
                    id: 'data-science-course',
                    title: 'Data Science Fundamentals',
                    description: 'Python, statistics, and machine learning basics',
                    category: 'technology',
                    preview: 'assets/images/templates/data-science.jpg'
                }
            ];

            let html = '<div class="ccb-templates-grid">';
            templates.forEach(template => {
                html += `
                    <div class="ccb-template-card" data-template-id="${template.id}">
                        <div class="ccb-template-preview">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="ccb-template-info">
                            <h3 class="ccb-template-name">${template.title}</h3>
                            <p class="ccb-template-description">${template.description}</p>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            $('.ccb-templates-content .ccb-templates-body').html(html);
        }

        /**
         * Handle template selection
         */
        handleTemplateSelect(event) {
            const templateId = $(event.currentTarget).data('template-id');
            
            if (confirm('Apply this template? This will replace your current content.')) {
                this.applyTemplate(templateId);
                this.hideTemplates();
            }
        }

        /**
         * Apply selected template
         */
        applyTemplate(templateId) {
            this.showLoading('Applying template...');
            
            // TODO: Implement template application
            setTimeout(() => {
                this.hideLoading();
                this.showNotification('Template applied successfully', 'success');
                this.markAsDirty();
            }, 1500);
        }

        /**
         * Handle preview mode
         */
        handlePreview() {
            const previewUrl = `${window.location.origin}/courscribe-curriculum-preview/?curriculum_id=${this.curriculum.id}&preview=true`;
            window.open(previewUrl, '_blank');
        }

        /**
         * Handle PDF export
         */
        handleExport() {
            this.showLoading('Generating PDF...');
            
            // TODO: Implement PDF export functionality
            setTimeout(() => {
                this.hideLoading();
                this.showNotification('PDF export feature coming soon', 'info');
            }, 1000);
        }

        /**
         * Handle course reorder
         */
        handleCourseReorder(event) {
            const newOrder = [];
            $('.ccb-course-item').each(function(index) {
                newOrder.push({
                    id: $(this).data('course-id'),
                    order: index + 1
                });
            });
            
            // TODO: Save new order to server
            this.markAsDirty();
            console.log('Course order changed:', newOrder);
        }

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts(event) {
            if (event.ctrlKey || event.metaKey) {
                switch(event.key) {
                    case 's':
                        event.preventDefault();
                        this.handleSave();
                        break;
                    case 'k':
                        event.preventDefault();
                        this.toggleAIPanel();
                        break;
                    case 't':
                        event.preventDefault();
                        this.showTemplates();
                        break;
                }
            }
            
            if (event.key === 'Escape') {
                this.hideTemplates();
                if ($('#ccbAIPanel').hasClass('open')) {
                    this.toggleAIPanel();
                }
            }
        }

        /**
         * Handle before unload (warn about unsaved changes)
         */
        handleBeforeUnload(event) {
            if (this.isDirty) {
                const message = 'You have unsaved changes. Are you sure you want to leave?';
                event.returnValue = message;
                return message;
            }
        }

        /**
         * Handle window resize
         */
        handleResize() {
            // Adjust layout for mobile/desktop
            if ($(window).width() <= 768) {
                $('.ccb-sidebar').removeClass('open');
            }
        }

        /**
         * Toggle mobile sidebar
         */
        toggleSidebar() {
            $('.ccb-sidebar').toggleClass('open');
        }

        /**
         * Check if this is a first-time user
         */
        checkFirstTimeUser() {
            const hasSeenTutorial = localStorage.getItem('ccb-tutorial-seen');
            if (!hasSeenTutorial) {
                // TODO: Show welcome tutorial
                this.showWelcomeTutorial();
            }
        }

        /**
         * Show welcome tutorial
         */
        showWelcomeTutorial() {
            // TODO: Implement tutorial system
            this.showNotification('Welcome to the CourScribe Curriculum Builder!', 'info');
            localStorage.setItem('ccb-tutorial-seen', 'true');
        }

        /**
         * Show loading overlay
         */
        showLoading(message = 'Loading...') {
            $('#ccbLoadingOverlay .ccb-loading-text').text(message);
            $('#ccbLoadingOverlay').fadeIn(300);
        }

        /**
         * Hide loading overlay
         */
        hideLoading() {
            $('#ccbLoadingOverlay').fadeOut(300);
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="ccb-notification ccb-notification-${type}">
                    <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                    ${message}
                    <button class="ccb-notification-close">×</button>
                </div>
            `);
            
            $('body').append(notification);
            
            notification.fadeIn(300);
            
            // Auto-hide after 4 seconds
            setTimeout(() => {
                notification.fadeOut(300, () => notification.remove());
            }, 4000);
            
            // Manual close
            notification.find('.ccb-notification-close').on('click', () => {
                notification.fadeOut(300, () => notification.remove());
            });
        }

        /**
         * Get icon for notification type
         */
        getNotificationIcon(type) {
            const icons = {
                'success': 'check-circle',
                'error': 'exclamation-circle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            return icons[type] || 'info-circle';
        }

        // ==========================================
        // DRAG AND DROP SUPPORT METHODS
        // ==========================================

        /**
         * Add drag and drop visual styles
         */
        addDragAndDropStyles() {
            if ($('#ccb-drag-styles').length) return; // Already added
            
            const styles = `
                <style id="ccb-drag-styles">
                /* Course Drag & Drop Styles */
                .ccb-course-ghost {
                    opacity: 0.3;
                    background: rgba(228, 178, 111, 0.1);
                    border: 2px dashed var(--ccb-primary-gold);
                    transform: rotate(1deg);
                }
                
                .ccb-course-chosen {
                    box-shadow: 0 8px 25px rgba(228, 178, 111, 0.3);
                    transform: scale(1.02);
                    z-index: 1000;
                }
                
                .ccb-course-drag {
                    transform: rotate(2deg);
                    opacity: 0.9;
                }
                
                .ccb-course-fallback {
                    background: var(--ccb-bg-elevated);
                    border: 2px solid var(--ccb-primary-gold);
                    border-radius: var(--ccb-border-radius-lg);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                }
                
                .ccb-container-dragging {
                    background: rgba(228, 178, 111, 0.05);
                    border-radius: var(--ccb-border-radius);
                    position: relative;
                }
                
                .ccb-container-dragging::before {
                    content: 'Drop here to reorder';
                    position: absolute;
                    top: -25px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: var(--ccb-primary-gold);
                    color: var(--ccb-bg-primary);
                    padding: 4px 12px;
                    border-radius: var(--ccb-border-radius);
                    font-size: 12px;
                    font-weight: 600;
                    z-index: 1001;
                    animation: pulse 2s infinite;
                }
                
                /* Module Drag & Drop Styles */
                .ccb-module-ghost {
                    opacity: 0.4;
                    background: rgba(248, 146, 62, 0.1);
                    border: 1px dashed var(--ccb-secondary-accent);
                }
                
                .ccb-module-chosen {
                    transform: scale(1.01);
                    box-shadow: 0 4px 15px rgba(248, 146, 62, 0.2);
                }
                
                .ccb-module-drag {
                    opacity: 0.8;
                    transform: rotate(1deg);
                }
                
                /* Objective Drag & Drop Styles */
                .ccb-objective-ghost {
                    opacity: 0.5;
                    background: rgba(228, 178, 111, 0.1);
                }
                
                .ccb-objective-chosen {
                    background: rgba(228, 178, 111, 0.1);
                    transform: translateY(-2px);
                }
                
                /* Drag Handle Enhancements */
                .ccb-course-drag-handle:hover,
                .ccb-module-drag-handle:hover,
                .ccb-objective-drag-handle:hover {
                    color: var(--ccb-primary-gold);
                    cursor: grab;
                    transform: scale(1.1);
                }
                
                .ccb-course-drag-handle:active,
                .ccb-module-drag-handle:active,
                .ccb-objective-drag-handle:active {
                    cursor: grabbing;
                }
                
                /* Drop Zones */
                .ccb-drop-zone-active {
                    border: 2px dashed var(--ccb-primary-gold);
                    background: rgba(228, 178, 111, 0.05);
                    animation: dropZonePulse 1.5s ease-in-out infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
                
                @keyframes dropZonePulse {
                    0%, 100% { 
                        border-color: var(--ccb-primary-gold);
                        background: rgba(228, 178, 111, 0.05);
                    }
                    50% { 
                        border-color: var(--ccb-secondary-accent);
                        background: rgba(248, 146, 62, 0.08);
                    }
                }
                
                /* Smooth transitions */
                .ccb-course-card,
                .ccb-module-item,
                .ccb-objective-item {
                    transition: all 0.2s ease;
                }
                </style>
            `;
            
            $('head').append(styles);
        }

        /**
         * Show course drop zones during drag
         */
        showCourseDropZones() {
            $('.ccb-courses-container').addClass('ccb-drop-zone-active');
            $('.ccb-drop-zone').addClass('ccb-drop-zone-active');
        }

        /**
         * Hide course drop zones
         */
        hideCourseDropZones() {
            $('.ccb-courses-container').removeClass('ccb-drop-zone-active');
            $('.ccb-drop-zone').removeClass('ccb-drop-zone-active');
        }

        /**
         * Validate course drop position
         */
        validateCourseDrop(evt) {
            // Allow all moves for now - could add logic for restrictions
            return true;
        }

        /**
         * Update course order via AJAX
         */
        updateCourseOrder(evt) {
            const courseId = evt.item.dataset.courseId;
            const newOrder = Array.from(evt.to.children).map((el, index) => ({
                id: el.dataset.courseId,
                order: index + 1
            }));
            
            console.log('Updating course order:', newOrder);
            
            $.ajax({
                url: CourScribeCurriculumBuilder.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_course_order',
                    curriculum_id: CourScribeCurriculumBuilder.curriculumId,
                    course_order: newOrder,
                    nonce: CourScribeCurriculumBuilder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Course order updated successfully');
                        this.markAsDirty();
                        
                        // Update course numbers visually
                        this.updateCourseNumbers();
                    } else {
                        console.error('Failed to update course order:', response.data);
                        this.showNotification('Failed to update course order', 'error');
                        
                        // Revert the visual change
                        this.revertCourseOrder(evt);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error updating course order:', error);
                    this.showNotification('Network error updating course order', 'error');
                    this.revertCourseOrder(evt);
                }
            });
        }

        /**
         * Update module order via AJAX
         */
        updateModuleOrder(evt, courseId) {
            const newOrder = Array.from(evt.to.children).map((el, index) => ({
                id: el.dataset.moduleId,
                order: index + 1
            }));
            
            console.log('Updating module order for course', courseId, ':', newOrder);
            
            $.ajax({
                url: CourScribeCurriculumBuilder.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_module_order',
                    course_id: courseId,
                    module_order: newOrder,
                    nonce: CourScribeCurriculumBuilder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Module order updated successfully');
                        this.markAsDirty();
                    } else {
                        console.error('Failed to update module order:', response.data);
                        this.showNotification('Failed to update module order', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error updating module order:', error);
                    this.showNotification('Network error updating module order', 'error');
                }
            });
        }

        /**
         * Update objective order via AJAX
         */
        updateObjectiveOrder(evt, courseId) {
            const newOrder = Array.from(evt.to.children).map((el, index) => ({
                id: el.dataset.objectiveIndex,
                order: index + 1
            }));
            
            console.log('Updating objective order for course', courseId, ':', newOrder);
            
            $.ajax({
                url: CourScribeCurriculumBuilder.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'courscribe_update_objective_order',
                    course_id: courseId,
                    objective_order: newOrder,
                    nonce: CourScribeCurriculumBuilder.nonce
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Objective order updated successfully');
                        this.markAsDirty();
                    } else {
                        console.error('Failed to update objective order:', response.data);
                        this.showNotification('Failed to update objective order', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error updating objective order:', error);
                    this.showNotification('Network error updating objective order', 'error');
                }
            });
        }

        /**
         * Update visual course numbers after reordering
         */
        updateCourseNumbers() {
            $('.ccb-course-card').each(function(index) {
                $(this).find('.ccb-course-number-text').text('Course ' + (index + 1));
                $(this).attr('data-course-order', index + 1);
            });
        }

        /**
         * Revert course order if save fails
         */
        revertCourseOrder(evt) {
            // Move the item back to its original position
            if (evt.from && evt.item) {
                evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);
                this.showNotification('Order reverted due to save error', 'warning');
            }
        }

        /**
         * Mark content as dirty (unsaved changes)
         */
        markAsDirty() {
            this.isDirty = true;
            $('#ccbSaveBtn').addClass('ccb-btn-dirty').find('.ccb-save-status').text('Unsaved changes');
            
            // Update page title to show unsaved changes
            if (!document.title.startsWith('* ')) {
                document.title = '* ' + document.title;
            }
        }

        /**
         * Mark content as clean (saved)
         */
        markAsClean() {
            this.isDirty = false;
            $('#ccbSaveBtn').removeClass('ccb-btn-dirty').find('.ccb-save-status').text('All changes saved');
            
            // Remove unsaved indicator from title
            document.title = document.title.replace(/^\* /, '');
        }

        // ===== COURSE MANAGEMENT METHODS =====
        
        /**
         * Handle course edit button click
         */
        handleCourseEdit(e) {
            e.preventDefault();
            const courseId = $(e.target).closest('[data-course-id]').data('course-id');
            this.showNotification(`Opening course ${courseId} for editing...`, 'info', 3000);
            // TODO: Implement full course editor modal
        }

        /**
         * Handle course AI generation button click
         */
        handleCourseAI(e) {
            e.preventDefault();
            const courseId = $(e.target).closest('[data-course-id]').data('course-id');
            this.showNotification(`Generating AI content for course ${courseId}...`, 'info', 3000);
            this.generateCourseContent(courseId);
        }

        /**
         * Handle course delete button click
         */
        handleCourseDelete(e) {
            e.preventDefault();
            const courseId = $(e.target).closest('[data-course-id]').data('course-id');
            const courseTitle = $(e.target).closest('.ccb-course-card').find('.ccb-course-title').text();
            
            if (confirm(`Are you sure you want to delete the course "${courseTitle}"? This action cannot be undone.`)) {
                this.deleteCourse(courseId);
            }
        }

        /**
         * Handle add course button click
         */
        handleAddCourse(e) {
            e.preventDefault();
            this.showNotification('Creating new course...', 'info', 2000);
            this.createNewCourse();
        }

        /**
         * Handle course collapse/expand
         */
        handleCourseCollapse(e) {
            e.preventDefault();
            const courseCard = $(e.target).closest('.ccb-course-card');
            const courseContent = courseCard.find('.ccb-course-content');
            
            courseContent.toggleClass('show');
            courseCard.toggleClass('ccb-expanded ccb-collapsed');
            
            const icon = $(e.target).find('i');
            icon.toggleClass('fa-chevron-down fa-chevron-up');
        }

        // ===== MODULE MANAGEMENT METHODS =====
        
        /**
         * Handle module edit button click
         */
        handleModuleEdit(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            this.showNotification(`Opening module ${moduleId} for editing...`, 'info', 3000);
            // TODO: Implement module editor modal
        }

        /**
         * Handle module AI generation button click
         */
        handleModuleAI(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            this.showNotification(`Generating AI content for module ${moduleId}...`, 'info', 3000);
            this.generateModuleContent(moduleId);
        }

        /**
         * Handle module delete button click
         */
        handleModuleDelete(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            const moduleTitle = $(e.target).closest('.ccb-module-accordion-item').find('.ccb-module-title').text();
            
            if (confirm(`Are you sure you want to delete the module "${moduleTitle}"? This action cannot be undone.`)) {
                this.deleteModule(moduleId);
            }
        }

        /**
         * Handle add module button click
         */
        handleAddModule(e) {
            e.preventDefault();
            const courseId = $(e.target).closest('[data-course-id]').data('course-id');
            this.showNotification('Creating new module...', 'info', 2000);
            this.createNewModule(courseId);
        }

        /**
         * Handle module collapse/expand
         */
        handleModuleCollapse(e) {
            e.preventDefault();
            const moduleItem = $(e.target).closest('.ccb-module-accordion-item');
            const moduleContent = moduleItem.find('.ccb-module-content');
            
            moduleContent.toggleClass('show');
            
            const icon = $(e.target).find('i');
            icon.toggleClass('fa-chevron-down fa-chevron-up');
        }

        // ===== LESSON MANAGEMENT METHODS =====
        
        /**
         * Handle lesson edit button click
         */
        handleLessonEdit(e) {
            e.preventDefault();
            const lessonId = $(e.target).closest('[data-lesson-id]').data('lesson-id');
            this.showNotification(`Opening lesson ${lessonId} for editing...`, 'info', 3000);
            // TODO: Implement lesson editor modal
        }

        /**
         * Handle lesson AI generation button click
         */
        handleLessonAI(e) {
            e.preventDefault();
            const lessonId = $(e.target).closest('[data-lesson-id]').data('lesson-id');
            this.showNotification(`Generating AI content for lesson ${lessonId}...`, 'info', 3000);
            this.generateLessonContent(lessonId);
        }

        /**
         * Handle lesson delete button click
         */
        handleLessonDelete(e) {
            e.preventDefault();
            const lessonId = $(e.target).closest('[data-lesson-id]').data('lesson-id');
            const lessonTitle = $(e.target).closest('.ccb-lesson-item').find('.ccb-lesson-title').text();
            
            if (confirm(`Are you sure you want to delete the lesson "${lessonTitle}"? This action cannot be undone.`)) {
                this.deleteLesson(lessonId);
            }
        }

        /**
         * Handle add lesson button click
         */
        handleAddLesson(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            this.showNotification('Creating new lesson...', 'info', 2000);
            this.createNewLesson(moduleId);
        }

        /**
         * Handle lesson collapse/expand
         */
        handleLessonCollapse(e) {
            e.preventDefault();
            const lessonItem = $(e.target).closest('.ccb-lesson-item');
            const lessonContent = lessonItem.find('.ccb-lesson-content');
            
            lessonContent.toggleClass('show');
            
            const icon = $(e.target).find('i');
            icon.toggleClass('fa-chevron-down fa-chevron-up');
        }

        // ===== OBJECTIVE MANAGEMENT METHODS =====
        
        /**
         * Handle add objective button click
         */
        handleAddObjective(e) {
            e.preventDefault();
            const container = $(e.target).closest('[data-course-id], [data-module-id]');
            const courseId = container.data('course-id');
            const moduleId = container.data('module-id');
            
            this.addNewObjective(courseId, moduleId);
        }

        /**
         * Handle delete objective button click
         */
        handleDeleteObjective(e) {
            e.preventDefault();
            const objectiveItem = $(e.target).closest('.ccb-objective-item');
            
            if (confirm('Are you sure you want to delete this objective?')) {
                objectiveItem.fadeOut(300, () => {
                    objectiveItem.remove();
                    this.markAsDirty();
                    this.saveObjectives();
                });
            }
        }

        /**
         * Handle objective field changes
         */
        handleObjectiveChange(e) {
            this.markAsDirty();
            this.debouncedSaveObjectives();
        }

        // ===== TEACHING POINT MANAGEMENT METHODS =====
        
        /**
         * Handle add teaching point button click
         */
        handleAddTeachingPoint(e) {
            e.preventDefault();
            const lessonId = $(e.target).closest('[data-lesson-id]').data('lesson-id');
            this.addNewTeachingPoint(lessonId);
        }

        /**
         * Handle delete teaching point button click
         */
        handleDeleteTeachingPoint(e) {
            e.preventDefault();
            const teachingPointItem = $(e.target).closest('.ccb-teaching-point-item');
            
            if (confirm('Are you sure you want to delete this teaching point?')) {
                teachingPointItem.fadeOut(300, () => {
                    teachingPointItem.remove();
                    this.markAsDirty();
                    this.saveTeachingPoints();
                });
            }
        }

        /**
         * Handle teaching point field changes
         */
        handleTeachingPointChange(e) {
            this.markAsDirty();
            this.debouncedSaveTeachingPoints();
        }

        // ===== METHODS, MATERIALS, MEDIA MANAGEMENT =====
        
        /**
         * Handle add method button click
         */
        handleAddMethod(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            this.addNewMethod(moduleId);
        }

        /**
         * Handle delete method button click
         */
        handleDeleteMethod(e) {
            e.preventDefault();
            const methodItem = $(e.target).closest('.ccb-method-item');
            methodItem.fadeOut(300, () => {
                methodItem.remove();
                this.markAsDirty();
                this.saveMethods();
            });
        }

        /**
         * Handle method field changes
         */
        handleMethodChange(e) {
            this.markAsDirty();
            this.debouncedSaveMethods();
        }

        /**
         * Handle add material button click
         */
        handleAddMaterial(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            this.addNewMaterial(moduleId);
        }

        /**
         * Handle delete material button click
         */
        handleDeleteMaterial(e) {
            e.preventDefault();
            const materialItem = $(e.target).closest('.ccb-material-item');
            materialItem.fadeOut(300, () => {
                materialItem.remove();
                this.markAsDirty();
                this.saveMaterials();
            });
        }

        /**
         * Handle material field changes
         */
        handleMaterialChange(e) {
            this.markAsDirty();
            this.debouncedSaveMaterials();
        }

        /**
         * Handle add media button click
         */
        handleAddMedia(e) {
            e.preventDefault();
            const moduleId = $(e.target).closest('[data-module-id]').data('module-id');
            this.addNewMedia(moduleId);
        }

        /**
         * Handle delete media button click
         */
        handleDeleteMedia(e) {
            e.preventDefault();
            const mediaItem = $(e.target).closest('.ccb-media-item');
            mediaItem.fadeOut(300, () => {
                mediaItem.remove();
                this.markAsDirty();
                this.saveMedia();
            });
        }

        /**
         * Handle media field changes
         */
        handleMediaChange(e) {
            this.markAsDirty();
            this.debouncedSaveMedia();
        }

        // ===== INLINE EDITING METHODS =====
        
        /**
         * Handle inline content editing
         */
        handleInlineEdit(e) {
            const $element = $(e.target);
            const field = $element.data('field');
            const value = $element.text().trim();
            
            if (!field) return;
            
            this.markAsDirty();
            this.saveInlineEdit($element, field, value);
        }

        /**
         * Handle keydown in contenteditable elements
         */
        handleInlineEditKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $(e.target).blur();
            }
        }

        // ===== UTILITY METHODS =====
        
        /**
         * Handle drop zone clicks
         */
        handleDropZoneClick(e) {
            e.preventDefault();
            this.handleAddCourse(e);
        }

        /**
         * Handle curriculum field changes with auto-save
         */
        handleFieldChange(e) {
            const $field = $(e.target);
            const fieldName = $field.data('field');
            const fieldValue = $field.val();
            
            if (!fieldName) return;
            
            this.markAsDirty();
            
            // Auto-save after 2 seconds of inactivity
            clearTimeout(this.fieldChangeTimeout);
            this.fieldChangeTimeout = setTimeout(() => {
                this.saveCurriculumField(fieldName, fieldValue);
            }, 2000);
        }

        /**
         * Save individual curriculum field
         */
        async saveCurriculumField(fieldName, fieldValue) {
            if (!this.permissions.canEdit) return;
            
            const payload = {
                action: 'courscribe_save_curriculum_field',
                nonce: this.config.nonce,
                curriculum_id: this.curriculum.id,
                field_name: fieldName,
                field_value: fieldValue
            };

            try {
                const response = await $.post(this.config.ajaxUrl, payload);
                if (response.success) {
                    this.showSaveIndicator(fieldName, 'saved');
                } else {
                    this.showSaveIndicator(fieldName, 'error');
                    console.error('Failed to save field:', response.data);
                }
            } catch (error) {
                this.showSaveIndicator(fieldName, 'error');
                console.error('Network error saving field:', error);
            }
        }

        /**
         * Show save status indicator for a field
         */
        showSaveIndicator(fieldName, status) {
            const $field = $(`[data-field="${fieldName}"]`);
            if ($field.length === 0) return;
            
            let $indicator = $field.siblings('.ccb-save-indicator');
            if ($indicator.length === 0) {
                $indicator = $('<div class="ccb-save-indicator"></div>');
                $field.after($indicator);
            }
            
            $indicator.removeClass('ccb-save-saving ccb-save-saved ccb-save-error')
                     .addClass(`ccb-save-${status}`)
                     .text(status === 'saved' ? 'Saved' : status === 'saving' ? 'Saving...' : 'Error');
            
            if (status === 'saved') {
                setTimeout(() => {
                    $indicator.fadeOut();
                }, 2000);
            }
        }

        /**
         * Create debounced save functions
         */
        setupDebouncedSaves() {
            this.debouncedSaveObjectives = this.debounce(() => this.saveObjectives(), 2000);
            this.debouncedSaveTeachingPoints = this.debounce(() => this.saveTeachingPoints(), 2000);
            this.debouncedSaveMethods = this.debounce(() => this.saveMethods(), 2000);
            this.debouncedSaveMaterials = this.debounce(() => this.saveMaterials(), 2000);
            this.debouncedSaveMedia = this.debounce(() => this.saveMedia(), 2000);
        }

        /**
         * Debounce function
         */
        debounce(func, wait) {
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

        /**
         * Show notification to user
         */
        showNotification(message, type = 'info', duration = 5000) {
            const $notification = $(`
                <div class="ccb-notification ccb-notification-${type}">
                    <div class="ccb-notification-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="ccb-notification-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto-remove after duration
            setTimeout(() => {
                $notification.fadeOut(300, () => $notification.remove());
            }, duration);
            
            // Manual close button
            $notification.find('.ccb-notification-close').on('click', () => {
                $notification.fadeOut(300, () => $notification.remove());
            });
        }

        /**
         * Cleanup when page unloads
         */
        destroy() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
            }
            
            // Destroy all sortable instances
            if (this.courseSortable) {
                this.courseSortable.destroy();
            }
            
            $('.ccb-modules-list').each(function() {
                if (this.sortableInstance) {
                    this.sortableInstance.destroy();
                }
            });
            
            $('.ccb-objectives-list').each(function() {
                if (this.sortableInstance) {
                    this.sortableInstance.destroy();
                }
            });
            
            $(window).off('beforeunload', this.handleBeforeUnload);
        }
    };

})(jQuery);