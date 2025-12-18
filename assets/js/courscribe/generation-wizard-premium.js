/**
 * Premium Course Generation Wizard JavaScript
 * Handles the complete course generation flow with AI integration
 */

(function($) {
    'use strict';

    class CourScribePremiumGenerator {
        constructor() {
            this.currentStep = 1;
            this.totalSteps = 4;
            this.generatedContent = [];
            this.wizardData = {};
            this.isGenerating = false;
            this.curriculumContext = {};
            
            this.init();
        }

        init() {
            this.loadCurriculumContext();
            this.bindEvents();
            this.initializeComponents();
        }

        loadCurriculumContext() {
            const contextElement = document.getElementById('cs-curriculum-context');
            if (contextElement) {
                try {
                    this.curriculumContext = JSON.parse(contextElement.textContent);
                } catch (e) {
                    console.warn('Failed to parse curriculum context:', e);
                    this.curriculumContext = {};
                }
            }
        }

        bindEvents() {
            // Modal events
            $(document).on('show.bs.modal', '#cs-course-generation-modal', this.onModalShow.bind(this));
            $(document).on('hidden.bs.modal', '#cs-course-generation-modal', this.onModalHide.bind(this));

            // Wizard navigation
            $(document).on('click', '.cs-next-step', this.handleNextStep.bind(this));
            $(document).on('click', '.cs-prev-step', this.handlePrevStep.bind(this));

            // Step 1: Configuration
            $(document).on('click', '.cs-count-option', this.handleCountSelection.bind(this));
            $(document).on('click', '.cs-template-option', this.handleTemplateSelection.bind(this));

            // Step 2: Content Focus
            $(document).on('input', '#cs-generation-wizard-course-instructions', this.handleInstructionInput.bind(this));
            $(document).on('click', '.cs-show-suggestions', this.toggleSuggestions.bind(this));
            $(document).on('click', '.cs-suggestion-tag', this.applySuggestion.bind(this));
            $(document).on('keypress', '#cs-generation-wizard-course-topic-input', this.handleTopicInput.bind(this));
            $(document).on('click', '.cs-topic-remove', this.removeTopic.bind(this));

            // Step 3: Generation
            $(document).on('click', '#cs-start-generation', this.startGeneration.bind(this));

            // Step 4: Review & Customize
            $(document).on('click', '.cs-item-checkbox', this.handleItemSelection.bind(this));
            $(document).on('click', '.cs-select-all', this.selectAllItems.bind(this));
            $(document).on('click', '.cs-deselect-all', this.deselectAllItems.bind(this));
            $(document).on('click', '.cs-edit-item', this.editItem.bind(this));
            $(document).on('click', '.cs-duplicate-item', this.duplicateItem.bind(this));
            $(document).on('click', '.cs-regenerate-item', this.regenerateItem.bind(this));
            $(document).on('click', '.cs-remove-item', this.removeItem.bind(this));
            $(document).on('click', '.cs-enhance-selected', this.enhanceSelected.bind(this));
            $(document).on('click', '.cs-regenerate-selected', this.regenerateSelected.bind(this));
            $(document).on('click', '#cs-save-generated', this.saveGeneratedContent.bind(this));

            // Advanced settings
            $(document).on('change', '#cs-creativity', this.updateCreativityLevel.bind(this));
            $(document).on('click', '.cs-complexity-option', this.handleComplexitySelection.bind(this));
            $(document).on('input change', '.cs-premium-textarea, .cs-premium-input', this.updateCharacterCount.bind(this));

            // Real-time validation
            $(document).on('blur', '.cs-field-title, .cs-field-description', this.validateField.bind(this));

            // Objective management
            $(document).on('click', '.cs-add-objective', this.addObjective.bind(this));
            $(document).on('click', '.cs-remove-objective', this.removeObjective.bind(this));
            $(document).on('change', '.cs-thinking-skill', this.updateActionVerbs.bind(this));

            // Auto-save draft
            setInterval(this.autoSaveDraft.bind(this), 30000); // Auto-save every 30 seconds
        }

        initializeComponents() {
            this.updateStepProgress();
            this.initializeSliders();
            this.updateCostEstimate();
            
            // Initialize tooltips for any new content
            // Using setTimeout to ensure DOM is ready
            setTimeout(() => {
                this.initializeTooltips();
            }, 100);
        }

        onModalShow() {
            this.resetWizard();
            this.updateStepProgress();
        }

        onModalHide() {
            if (this.isGenerating) {
                if (confirm('Generation is in progress. Are you sure you want to close?')) {
                    this.cancelGeneration();
                } else {
                    return false;
                }
            }
        }

        resetWizard() {
            console.log('Resetting wizard');
            this.currentStep = 1;
            this.generatedContent = [];
            this.wizardData = {};
            this.isGenerating = false;
            
            $('.cs-wizard-step').removeClass('active');
            $('.cs-wizard-step[data-step="1"]').addClass('active');
            this.updateStepProgress();
            
            // Reset form elements
            $('#cs-generation-wizard-course-count').val('1');
            $('#cs-generation-wizard-course-difficulty').val('intermediate');
            $('#cs-generation-wizard-course-audience').val('professionals');
            $('#cs-generation-wizard-course-tone').val('professional');
            $('#cs-generation-wizard-course-depth').val('detailed');
            $('#cs-generation-wizard-course-duration').val('1-hour');
        }

        handleNextStep(event) {
            const $btn = $(event.currentTarget);
            const nextStep = parseInt($btn.data('next'));
            
            console.log('Next step clicked:', nextStep, 'Current step:', this.currentStep);
            
            if (isNaN(nextStep)) {
                console.error('Invalid next step data:', $btn.data('next'));
                return;
            }
            
            if (this.validateCurrentStep()) {
                this.collectStepData();
                this.goToStep(nextStep);
            }
        }

        handlePrevStep(event) {
            const $btn = $(event.currentTarget);
            const prevStep = parseInt($btn.data('prev'));
            
            console.log('Previous step clicked:', prevStep, 'Current step:', this.currentStep);
            
            if (isNaN(prevStep)) {
                console.error('Invalid prev step data:', $btn.data('prev'));
                return;
            }
            
            this.goToStep(prevStep);
        }

        goToStep(stepNumber) {
            if (isNaN(stepNumber) || stepNumber < 1 || stepNumber > this.totalSteps) {
                console.error('Invalid step number:', stepNumber);
                return;
            }
            
            console.log('Going to step:', stepNumber);
            
            $('.cs-wizard-step').removeClass('active');
            $(`.cs-wizard-step[data-step="${stepNumber}"]`).addClass('active');
            
            this.currentStep = stepNumber;
            this.updateStepProgress();
            
            // Step-specific actions
            switch (stepNumber) {
                case 3:
                    this.populateGenerationSummary();
                    break;
                case 4:
                    // Will be populated after generation
                    break;
            }
        }

        updateStepProgress() {
            if (isNaN(this.currentStep) || isNaN(this.totalSteps)) {
                console.error('Invalid step values:', this.currentStep, this.totalSteps);
                return;
            }
            
            const percentage = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
            $('.cs-progress-fill').css('width', percentage + '%');
            $('.cs-progress-text').text(`Step ${this.currentStep} of ${this.totalSteps}`);
        }

        validateCurrentStep() {
            switch (this.currentStep) {
                case 1:
                    return this.validateStep1();
                case 2:
                    return this.validateStep2();
                case 3:
                    return true; // No validation needed
                case 4:
                    return true; // No validation needed
                default:
                    return false;
            }
        }

        validateStep1() {
            const count = parseInt($('#cs-generation-wizard-course-count').val()) || 0;
            if (count < 1) {
                this.showError('Please select the number of courses to generate.');
                return false;
            }
            
            // Check tier limits
            if (this.curriculumContext.limits && 
                this.curriculumContext.limits.max_courses !== -1 && 
                count > this.curriculumContext.limits.max_courses) {
                this.showError(`Your ${this.curriculumContext.tier} plan allows only ${this.curriculumContext.limits.max_courses} course(s). Please upgrade to generate more.`);
                return false;
            }
            
            return true;
        }

        validateStep2() {
            // Optional validation for content focus
            return true;
        }

        collectStepData() {
            this.wizardData = {
                // Step 1 data
                count: parseInt($('#cs-generation-wizard-course-count').val()) || 1,
                difficulty: $('#cs-generation-wizard-course-difficulty').val(),
                audience: $('#cs-generation-wizard-course-audience').val(),
                tone: $('#cs-generation-wizard-course-tone').val(),
                depth: $('#cs-generation-wizard-course-depth').val(),
                duration: $('#cs-generation-wizard-course-duration').val(),
                template: $('.cs-template-option.active').data('template') || '',
                
                // Step 2 data
                instructions: $('#cs-generation-wizard-course-instructions').val().trim(),
                topics: this.getSelectedTopics(),
                objectives: $('#cs-generation-wizard-course-objectives').val().trim(),
                
                // Advanced settings
                aiModel: $('#cs-ai-model').val(),
                creativity: parseInt($('#cs-creativity').val()),
                complexity: $('.cs-complexity-option.active').data('level') || 2,
                language: $('#cs-language').val(),
                industry: $('#cs-industry').val(),
                factCheck: $('#cs-enable-fact-check').is(':checked'),
                grammarCheck: $('#cs-grammar-check').is(':checked'),
                plagiarismCheck: $('#cs-plagiarism-check').is(':checked'),
                
                // Context
                curriculum: this.curriculumContext
            };
        }

        getSelectedTopics() {
            const topics = [];
            $('.cs-topic-item').each(function() {
                topics.push($(this).find('.cs-topic-text').text().trim());
            });
            return topics;
        }

        handleCountSelection(event) {
            const $option = $(event.currentTarget);
            const count = $option.data('count');
            
            $('.cs-count-option').removeClass('active');
            $option.addClass('active');
            $('#cs-generation-wizard-course-count').val(count);
            
            this.updateCostEstimate();
        }

        handleTemplateSelection(event) {
            const $option = $(event.currentTarget);
            
            $('.cs-template-option').removeClass('active');
            $option.addClass('active');
        }

        handleInstructionInput(event) {
            const $textarea = $(event.target);
            const value = $textarea.val();
            
            // Update character count
            this.updateCharacterCount(event);
            
            // Show/hide suggestions based on content
            if (value.length > 10) {
                $('.cs-suggestions-btn').show();
            } else {
                $('.cs-suggestions-btn').hide();
            }
        }

        toggleSuggestions(event) {
            const $panel = $('.cs-suggestions-panel');
            $panel.slideToggle(300);
            
            const $btn = $(event.target);
            const isVisible = $panel.is(':visible');
            $btn.find('i').toggleClass('fa-lightbulb fa-times');
            $btn.find('.cs-btn-text').text(isVisible ? 'Hide' : 'Suggestions');
        }

        applySuggestion(event) {
            const $tag = $(event.target);
            const suggestion = $tag.text();
            const $textarea = $('#cs-generation-wizard-course-instructions');
            
            let currentValue = $textarea.val();
            if (currentValue && !currentValue.endsWith('\n')) {
                currentValue += '\n';
            }
            currentValue += '• ' + suggestion;
            
            $textarea.val(currentValue);
            this.updateCharacterCount({ target: $textarea[0] });
            
            // Visual feedback
            $tag.addClass('applied');
            setTimeout(() => $tag.removeClass('applied'), 1000);
        }

        handleTopicInput(event) {
            if (event.which === 13) { // Enter key
                event.preventDefault();
                const $input = $(event.target);
                const topic = $input.val().trim();
                
                if (topic && topic.length >= 2) {
                    this.addTopic(topic);
                    $input.val('');
                }
            }
        }

        addTopic(topic) {
            const $container = $('#cs-generation-wizard-course-topics-list');
            const topicHtml = `
                <div class="cs-topic-item">
                    <span class="cs-topic-text">${this.escapeHtml(topic)}</span>
                    <button type="button" class="cs-topic-remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            $container.append(topicHtml);
            // Reinitialize tooltips for new content
            this.initializeTooltips();
        }

        removeTopic(event) {
            $(event.target).closest('.cs-topic-item').fadeOut(300, function() {
                $(this).remove();
            });
        }

        populateGenerationSummary() {
            $('#cs-summary-type').text('Courses');
            $('#cs-summary-count').text(this.wizardData.count || 1);
            $('#cs-summary-difficulty').text(this.wizardData.difficulty || 'Intermediate');
            $('#cs-summary-audience').text(this.wizardData.audience || 'Professionals');
        }

        async startGeneration() {
            console.log('generating..')
            if (this.isGenerating) return;
            
            this.isGenerating = true;
            this.collectStepData();
            
            
            // Update UI
            $('#cs-start-generation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Generating...');
            $('#cs-generation-progress').show();
            
            try {
                // Start generation process
                await this.performGeneration();
                
                // Move to review step
                this.goToStep(4);
                
            } catch (error) {
                this.showError('Generation failed: ' + error.message);
                console.error('Generation error:', error);
            } finally {
                this.isGenerating = false;
                $('#cs-start-generation').prop('disabled', false).html('<i class="fas fa-magic me-2"></i>Generate with AI');
                $('#cs-generation-progress').hide();
            }
        }

        async performGeneration() {
            // Simulate generation steps
            await this.updateGenerationStep('analyze', 'Analyzing requirements...', 'Understanding your curriculum context and goals');
            await this.delay(1500);
            
            await this.updateGenerationStep('generate', 'Generating content...', 'Creating courses with AI based on your specifications');
            
            // Make actual API call
            const response = await this.callGenerationAPI();
            
            await this.updateGenerationStep('optimize', 'Optimizing results...', 'Enhancing and validating generated content');
            await this.delay(1000);
            
            this.generatedContent = response.data.courses || [];
            this.renderGeneratedContent();
        }

        async updateGenerationStep(step, title, description) {
            $('.cs-progress-step').removeClass('active');
            $(`.cs-progress-step[data-step="${step}"]`).addClass('active');
            
            $('#cs-progress-title').text(title);
            $('#cs-progress-description').text(description);
        }

        async callGenerationAPI() {
            console.log('generation data', this.wizardData)
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: courscribeAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'courscribe_generate_courses_premium',
                        wizard_data: JSON.stringify(this.wizardData),
                        nonce: courscribeAjax.generation_nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.data?.message || 'Generation failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(new Error('Network error: ' + error));
                    }
                });
            });
        }

        renderGeneratedContent() {
            const $container = $('#cs-generated-content');
            
            // Use the content preview component
            $container.html(`
                <div class="cs-content-preview" id="cs-content-preview-course">
                    <!-- Content will be populated by courscribe_render_content_preview -->
                </div>
            `);
            
            // Populate with generated content using PHP function
            // This would typically be done server-side, but for demo purposes:
            this.populateContentPreview();
            
            // Update statistics
            this.updateContentStatistics();
        }

        populateContentPreview() {
            // This is a simplified version - in real implementation, 
            // the content would be rendered server-side
            const $container = $('#cs-generated-content');
            let html = `
                <div class="cs-preview-header">
                    <h5>
                        <i class="fas fa-eye me-2"></i>
                        Generated Courses
                        <span class="cs-count-badge">${this.generatedContent.length}</span>
                    </h5>
                    <div class="cs-preview-actions">
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-select-all">
                            <i class="fas fa-check-square me-1"></i>Select All
                        </button>
                        <button type="button" class="cs-btn cs-btn-sm cs-btn-success cs-enhance-selected">
                            <i class="fas fa-star me-1"></i>Enhance Selected
                        </button>
                    </div>
                </div>
                <div class="cs-preview-content">
            `;
            
            this.generatedContent.forEach((course, index) => {
                html += this.renderCourseItem(course, index);
            });
            
            html += '</div>';
            $container.html(html);
        }

        renderCourseItem(course, index) {
            const objectives = course.objectives || [];
            const topics = course.topics || [];
            
            return `
                <div class="cs-content-item editable" data-index="${index}">
                    <div class="cs-item-header">
                        <div class="cs-item-selector">
                            <input type="checkbox" class="cs-item-checkbox" id="item-${index}" checked>
                            <label for="item-${index}" class="cs-checkbox-label"></label>
                        </div>
                        <div class="cs-item-info">
                            <div class="cs-item-number">${index + 1}</div>
                            <div class="cs-item-type">Course</div>
                        </div>
                        <div class="cs-item-status">
                            <div class="cs-quality-indicator" data-quality="${course.quality || 'good'}">
                                <div class="cs-quality-stars">
                                    ${this.renderQualityStars(course.quality || 'good')}
                                </div>
                                <span class="cs-quality-text">${this.capitalize(course.quality || 'good')}</span>
                            </div>
                        </div>
                        <div class="cs-item-actions">
                            <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-edit-item" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-regenerate-item" title="Regenerate">
                                <i class="fas fa-redo"></i>
                            </button>
                            <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-item" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="cs-item-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="cs-content-field">
                                    <label class="cs-field-label">
                                        <i class="fas fa-heading me-2"></i>Course Title
                                    </label>
                                    <div class="cs-editable-field">
                                        <input type="text" class="cs-premium-input cs-field-title" 
                                               value="${this.escapeHtml(course.title || '')}" 
                                               placeholder="Enter course title..." maxlength="100">
                                        <div class="cs-field-feedback">
                                            <div class="cs-char-count">
                                                <span class="cs-current">${(course.title || '').length}</span>/<span class="cs-max">100</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="cs-content-field">
                                    <label class="cs-field-label">
                                        <i class="fas fa-layer-group me-2"></i>Level of Learning
                                    </label>
                                    <div class="cs-editable-field">
                                        <select class="cs-premium-select cs-field-level">
                                            <option value="remember" ${course.level === 'remember' ? 'selected' : ''}>Remember</option>
                                            <option value="understand" ${course.level === 'understand' ? 'selected' : ''}>Understand</option>
                                            <option value="apply" ${course.level === 'apply' || !course.level ? 'selected' : ''}>Apply</option>
                                            <option value="analyze" ${course.level === 'analyze' ? 'selected' : ''}>Analyze</option>
                                            <option value="evaluate" ${course.level === 'evaluate' ? 'selected' : ''}>Evaluate</option>
                                            <option value="create" ${course.level === 'create' ? 'selected' : ''}>Create</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cs-content-field">
                            <label class="cs-field-label">
                                <i class="fas fa-target me-2"></i>Course Goal
                            </label>
                            <div class="cs-editable-field">
                                <textarea class="cs-premium-textarea cs-field-description" 
                                          rows="3" placeholder="Enter course goal..." maxlength="500">${this.escapeHtml(course.goal || '')}</textarea>
                                <div class="cs-field-feedback">
                                    <div class="cs-char-count">
                                        <span class="cs-current">${(course.goal || '').length}</span>/<span class="cs-max">500</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${topics.length > 0 ? `
                        <div class="cs-content-field">
                            <label class="cs-field-label">
                                <i class="fas fa-list-ul me-2"></i>Key Topics
                            </label>
                            <div class="cs-topics-list">
                                ${topics.map(topic => `<span class="cs-topic-tag">${this.escapeHtml(topic)}</span>`).join('')}
                            </div>
                        </div>
                        ` : ''}

                        ${objectives.length > 0 ? `
                        <div class="cs-content-field">
                            <label class="cs-field-label">
                                <i class="fas fa-list-ol me-2"></i>Learning Objectives
                            </label>
                            <div class="cs-objectives-container" data-course-index="${index}">
                                ${objectives.map((obj, objIndex) => this.renderObjectiveItem(obj, index, objIndex)).join('')}
                            </div>
                            <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-add-objective" data-course-index="${index}">
                                <i class="fas fa-plus me-1"></i>Add Objective
                            </button>
                        </div>
                        ` : `
                        <div class="cs-content-field">
                            <label class="cs-field-label">
                                <i class="fas fa-list-ol me-2"></i>Learning Objectives
                            </label>
                            <div class="cs-objectives-container" data-course-index="${index}">
                                ${this.renderObjectiveItem({}, index, 0)}
                            </div>
                            <button type="button" class="cs-btn cs-btn-sm cs-btn-outline cs-add-objective" data-course-index="${index}">
                                <i class="fas fa-plus me-1"></i>Add Objective
                            </button>
                        </div>
                        `}
                    </div>
                </div>
            `;
        }

        renderQualityStars(quality) {
            const stars = quality === 'excellent' ? 5 : (quality === 'good' ? 4 : 3);
            let html = '';
            for (let i = 1; i <= 5; i++) {
                html += `<i class="fas fa-star ${i <= stars ? 'active' : ''}"></i>`;
            }
            return html;
        }

        renderObjectiveItem(objective, courseIndex, objectiveIndex) {
            const actionVerbs = this.getActionVerbsForThinkingSkill(objective.thinking_skill || 'Apply');
            
            return `
                <div class="cs-objective-item" data-objective-index="${objectiveIndex}">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="cs-field-label">Thinking Skill</label>
                            <select class="cs-premium-select cs-thinking-skill" name="objectives[${courseIndex}][${objectiveIndex}][thinking_skill]">
                                <option value="Know" ${objective.thinking_skill === 'Know' ? 'selected' : ''}>Know</option>
                                <option value="Comprehend" ${objective.thinking_skill === 'Comprehend' ? 'selected' : ''}>Comprehend</option>
                                <option value="Apply" ${objective.thinking_skill === 'Apply' || !objective.thinking_skill ? 'selected' : ''}>Apply</option>
                                <option value="Analyze" ${objective.thinking_skill === 'Analyze' ? 'selected' : ''}>Analyze</option>
                                <option value="Evaluate" ${objective.thinking_skill === 'Evaluate' ? 'selected' : ''}>Evaluate</option>
                                <option value="Create" ${objective.thinking_skill === 'Create' ? 'selected' : ''}>Create</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="cs-field-label">Action Verb</label>
                            <select class="cs-premium-select cs-action-verb" name="objectives[${courseIndex}][${objectiveIndex}][action_verb]">
                                ${actionVerbs.map(verb => `<option value="${verb}" ${objective.action_verb === verb ? 'selected' : ''}>${verb}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="cs-field-label">Description</label>
                            <input type="text" class="cs-premium-input cs-objective-description" 
                                   name="objectives[${courseIndex}][${objectiveIndex}][description]"
                                   value="${this.escapeHtml(objective.description || '')}"
                                   placeholder="Enter description...">
                        </div>
                        <div class="col-md-1">
                            <label class="cs-field-label">&nbsp;</label>
                            <button type="button" class="cs-btn cs-btn-sm cs-btn-danger cs-remove-objective" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        getActionVerbsForThinkingSkill(thinkingSkill) {
            const actionVerbs = {
                'Know': ['Choose', 'Cite', 'Define', 'Describe', 'Identify', 'Label', 'List', 'Match', 'Name', 'Recall', 'Recognize'],
                'Comprehend': ['Classify', 'Compare', 'Explain', 'Interpret', 'Paraphrase', 'Summarize', 'Translate', 'Describe'],
                'Apply': ['Apply', 'Demonstrate', 'Illustrate', 'Solve', 'Use', 'Execute'],
                'Analyze': ['Analyze', 'Compare', 'Contrast', 'Differentiate', 'Distinguish', 'Examine', 'Question', 'Test'],
                'Evaluate': ['Appraise', 'Argue', 'Assess', 'Critique', 'Defend', 'Judge', 'Select', 'Support', 'Value'],
                'Create': ['Assemble', 'Construct', 'Design', 'Develop', 'Formulate', 'Generate', 'Plan', 'Produce', 'Invent']
            };
            return actionVerbs[thinkingSkill] || actionVerbs['Apply'];
        }

        addObjective(event) {
            const courseIndex = $(event.target).data('course-index');
            const $container = $(`.cs-objectives-container[data-course-index="${courseIndex}"]`);
            const objectiveIndex = $container.find('.cs-objective-item').length;
            
            const newObjective = this.renderObjectiveItem({}, courseIndex, objectiveIndex);
            $container.append(newObjective);
            // Reinitialize tooltips for new content
            this.initializeTooltips();
        }

        removeObjective(event) {
            const $objectiveItem = $(event.target).closest('.cs-objective-item');
            const $container = $objectiveItem.closest('.cs-objectives-container');
            
            if ($container.find('.cs-objective-item').length > 1) {
                $objectiveItem.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                this.showWarning('At least one objective is required.');
            }
        }

        updateActionVerbs(event) {
            const $select = $(event.target);
            const thinkingSkill = $select.val();
            const $actionVerbSelect = $select.closest('.cs-objective-item').find('.cs-action-verb');
            
            const actionVerbs = this.getActionVerbsForThinkingSkill(thinkingSkill);
            $actionVerbSelect.empty();
            
            actionVerbs.forEach(verb => {
                $actionVerbSelect.append(new Option(verb, verb));
            });
        }

        handleItemSelection(event) {
            this.updateContentStatistics();
        }

        selectAllItems() {
            $('.cs-item-checkbox').prop('checked', true);
            this.updateContentStatistics();
        }

        deselectAllItems() {
            $('.cs-item-checkbox').prop('checked', false);
            this.updateContentStatistics();
        }

        editItem(event) {
            const $item = $(event.target).closest('.cs-content-item');
            $item.find('.cs-premium-input, .cs-premium-textarea').first().focus();
        }

        duplicateItem(event) {
            const $item = $(event.target).closest('.cs-content-item');
            const $clone = $item.clone();
            
            // Update IDs and indices
            const newIndex = $('.cs-content-item').length;
            $clone.attr('data-index', newIndex);
            $clone.find('.cs-item-number').text(newIndex + 1);
            
            // Insert after current item
            $item.after($clone);
            this.updateContentStatistics();
        }

        regenerateItem(event) {
            const $item = $(event.target).closest('.cs-content-item');
            const index = $item.data('index');
            
            if (confirm('Regenerate this course? This will replace the current content.')) {
                // Show loading state
                $item.addClass('regenerating');
                
                // Simulate regeneration
                setTimeout(() => {
                    $item.removeClass('regenerating');
                    this.showSuccess('Course regenerated successfully');
                }, 2000);
            }
        }

        removeItem(event) {
            const $item = $(event.target).closest('.cs-content-item');
            
            if (confirm('Remove this course from the list?')) {
                $item.fadeOut(300, function() {
                    $(this).remove();
                    // Renumber items
                    $('.cs-content-item').each(function(index) {
                        $(this).attr('data-index', index);
                        $(this).find('.cs-item-number').text(index + 1);
                    });
                });
                
                this.updateContentStatistics();
            }
        }

        enhanceSelected() {
            const selectedItems = $('.cs-item-checkbox:checked').length;
            if (selectedItems === 0) {
                this.showWarning('Please select at least one course to enhance.');
                return;
            }
            
            if (confirm(`Enhance ${selectedItems} selected course(s)? This will improve content quality using AI.`)) {
                this.showInfo('Enhancement started. This may take a few moments...');
                
                // Simulate enhancement
                setTimeout(() => {
                    this.showSuccess(`Successfully enhanced ${selectedItems} course(s)`);
                }, 3000);
            }
        }

        regenerateSelected() {
            const selectedItems = $('.cs-item-checkbox:checked').length;
            if (selectedItems === 0) {
                this.showWarning('Please select at least one course to regenerate.');
                return;
            }
            
            if (confirm(`Regenerate ${selectedItems} selected course(s)? This will replace current content.`)) {
                this.showInfo('Regeneration started. This may take a few moments...');
                
                // Simulate regeneration
                setTimeout(() => {
                    this.showSuccess(`Successfully regenerated ${selectedItems} course(s)`);
                }, 3000);
            }
        }

        async saveGeneratedContent() {
            const selectedItems = this.getSelectedItems();
            
            if (selectedItems.length === 0) {
                this.showWarning('Please select at least one course to save.');
                return;
            }
            
            // Show loading state
            const $btn = $('#cs-save-generated');
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            
            try {
                const response = await this.saveCourses(selectedItems);
                
                if (response.success) {
                    this.showSuccess(`Successfully saved ${selectedItems.length} course(s) to curriculum!`);
                    
                    // Close modal and refresh curriculum view
                    setTimeout(() => {
                        $('#cs-course-generation-modal').modal('hide');
                        window.location.reload(); // Or use more sophisticated refresh
                    }, 1500);
                } else {
                    throw new Error(response.data?.message || 'Failed to save courses');
                }
                
            } catch (error) {
                this.showError('Failed to save courses: ' + error.message);
            } finally {
                $btn.prop('disabled', false).html(originalText);
            }
        }

        getSelectedItems() {
            const items = [];
            $('.cs-item-checkbox:checked').each(function() {
                const $item = $(this).closest('.cs-content-item');
                const index = $item.data('index');
                
                // Collect objectives
                const objectives = [];
                $item.find('.cs-objective-item').each(function() {
                    const $obj = $(this);
                    const thinkingSkill = $obj.find('.cs-thinking-skill').val();
                    const actionVerb = $obj.find('.cs-action-verb').val();
                    const description = $obj.find('.cs-objective-description').val();
                    
                    if (thinkingSkill && actionVerb && description) {
                        objectives.push({
                            thinking_skill: thinkingSkill,
                            action_verb: actionVerb,
                            description: description
                        });
                    }
                });
                
                items.push({
                    title: $item.find('.cs-field-title').val(),
                    goal: $item.find('.cs-field-description').val(),
                    level: $item.find('.cs-field-level').val() || 'apply',
                    objectives: objectives
                });
            });
            return items;
        }

        async saveCourses(courses) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: courscribeAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'courscribe_save_generated_courses',
                        curriculum_id: this.curriculumContext.curriculum_id,
                        courses: JSON.stringify(courses),
                        nonce: courscribeAjax.generation_nonce
                    },
                    success: resolve,
                    error: (xhr, status, error) => reject(new Error(error))
                });
            });
        }

        updateContentStatistics() {
            const totalItems = $('.cs-content-item').length;
            const selectedItems = $('.cs-item-checkbox:checked').length;
            
            $('#cs-total-items').text(totalItems);
            $('#cs-selected-items').text(selectedItems);
        }

        updateCreativityLevel(event) {
            const value = parseInt($(event.target).val());
            let label = 'Balanced';
            
            if (value <= 25) label = 'Conservative';
            else if (value <= 50) label = 'Balanced';
            else if (value <= 75) label = 'Creative';
            else label = 'Innovative';
            
            $(event.target).next('.cs-slider-labels').find('.active').removeClass('active');
            // Update active label based on value range
        }

        handleComplexitySelection(event) {
            $('.cs-complexity-option').removeClass('active');
            $(event.target).closest('.cs-complexity-option').addClass('active');
        }

        updateCharacterCount(event) {
            const $field = $(event.target);
            const maxLength = parseInt($field.attr('maxlength')) || 0;
            const currentLength = $field.val().length;
            
            const $counter = $field.closest('.cs-editable-field, .cs-textarea-enhanced').find('.cs-char-count');
            if ($counter.length) {
                $counter.find('.cs-current').text(currentLength);
                
                // Color coding
                $counter.removeClass('text-warning text-danger');
                if (maxLength > 0) {
                    if (currentLength > maxLength * 0.8) {
                        $counter.addClass(currentLength > maxLength * 0.95 ? 'text-danger' : 'text-warning');
                    }
                }
            }
        }

        validateField(event) {
            const $field = $(event.target);
            const value = $field.val().trim();
            
            // Basic validation
            let isValid = true;
            let message = '';
            
            if ($field.hasClass('cs-field-title')) {
                if (value.length < 3) {
                    isValid = false;
                    message = 'Title must be at least 3 characters long';
                } else if (value.length > 100) {
                    isValid = false;
                    message = 'Title must be less than 100 characters';
                }
            }
            
            // Update validation UI
            const $feedback = $field.closest('.cs-editable-field').find('.cs-field-feedback');
            if (!isValid) {
                $field.addClass('is-invalid');
                $feedback.find('.cs-validation-message').text(message).removeClass('d-none');
            } else {
                $field.removeClass('is-invalid');
                $feedback.find('.cs-validation-message').addClass('d-none');
            }
        }

        updateCostEstimate() {
            const count = parseInt($('#cs-generation-wizard-course-count').val()) || 1;
            $('#cs-cost-estimate').text(count);
        }

        initializeSliders() {
            // Initialize range sliders with better UX
            $('.cs-range-slider').each(function() {
                const $slider = $(this);
                
                $slider.on('input', function() {
                    const value = parseInt(this.value);
                    const $labels = $slider.next('.cs-slider-labels');
                    const $spans = $labels.find('span');
                    const spanCount = $spans.length;
                    
                    // Calculate which label should be active based on value
                    let labelIndex = 0;
                    if (spanCount > 1) {
                        const percentage = value / 100;
                        labelIndex = Math.floor(percentage * (spanCount - 1));
                        // Ensure we don't exceed the available labels
                        labelIndex = Math.min(labelIndex, spanCount - 1);
                    }
                    
                    // Update visual indicator
                    $spans.removeClass('active');
                    $spans.eq(labelIndex).addClass('active');
                });
                
                // Trigger initial update
                $slider.trigger('input');
            });
        }

        initializeTooltips() {
            try {
                // Initialize tooltips for newly added elements only
                // The global soft-ui-dashboard.js already initializes tooltips on page load
                // So we only need to initialize tooltips for dynamically added content
                if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
                    // Only initialize tooltips that haven't been initialized yet
                    const uninitializedTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]:not([data-bs-original-title])');
                    [].slice.call(uninitializedTooltips).map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }
            } catch (error) {
                console.warn('CourScribe: Error initializing tooltips:', error.message);
            }
        }

        autoSaveDraft() {
            if (this.currentStep > 1 && !this.isGenerating) {
                this.collectStepData();
                
                // Save to localStorage as draft
                localStorage.setItem('courscribe_generation_draft', JSON.stringify({
                    timestamp: Date.now(),
                    step: this.currentStep,
                    data: this.wizardData
                }));
            }
        }

        loadDraft() {
            const draft = localStorage.getItem('courscribe_generation_draft');
            if (draft) {
                try {
                    const parsedDraft = JSON.parse(draft);
                    const age = Date.now() - parsedDraft.timestamp;
                    
                    // Only load drafts less than 24 hours old
                    if (age < 24 * 60 * 60 * 1000) {
                        this.wizardData = parsedDraft.data;
                        // Restore form values
                        // ... implementation details
                        
                        this.showInfo('Draft loaded from previous session');
                        $('.cs-save-draft').prop('disabled', false);
                    }
                } catch (e) {
                    console.warn('Failed to load draft:', e);
                }
            }
        }

        // Utility methods
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Notification methods
        showSuccess(message) {
            this.showNotification('success', message);
        }

        showError(message) {
            this.showNotification('error', message);
        }

        showWarning(message) {
            this.showNotification('warning', message);
        }

        showInfo(message) {
            this.showNotification('info', message);
        }

        showNotification(type, message) {
            // Create notification element
            const notification = $(`
                <div class="cs-notification cs-notification-${type}" role="alert">
                    <div class="cs-notification-content">
                        <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                        <span class="cs-notification-message">${message}</span>
                    </div>
                    <button type="button" class="cs-notification-close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            
            // Add to container
            let $container = $('.cs-notifications-container');
            if (!$container.length) {
                $container = $('<div class="cs-notifications-container"></div>');
                $('.cs-modal-body').prepend($container);
                // Reinitialize tooltips for new content
                this.initializeTooltips();
            }
            
            $container.append(notification);
            
            // Auto-remove after delay
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, type === 'error' ? 8000 : 5000);
            
            // Manual close
            notification.find('.cs-notification-close').on('click', function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }

        getNotificationIcon(type) {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-triangle',
                warning: 'exclamation-circle',
                info: 'info-circle'
            };
            return icons[type] || 'info-circle';
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.cs-generation-wizard, #cs-course-generation-modal').length > 0) {
            window.courscribePremiumGenerator = new CourScribePremiumGenerator();
        }
    });

    // Export for external access
    window.CourScribePremiumGenerator = CourScribePremiumGenerator;

})(jQuery);