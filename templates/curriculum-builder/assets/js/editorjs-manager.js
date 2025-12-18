/**
 * CourScribe Editor.js Manager
 * Manages all Editor.js instances for curriculum content development
 * Maps: Sections → Modules, Pages → Lessons, Blocks → Teaching Points
 */

class CourScribeEditorManager {
    constructor() {
        this.editors = new Map();
        this.configs = {
            simple: this.getSimpleConfig(),
            full: this.getFullConfig(),
            module: this.getModuleConfig(),
            lesson: this.getLessonConfig(),
            overview: this.getOverviewConfig(),
            objectives: this.getObjectivesConfig(),
            assessment: this.getAssessmentConfig()
        };
    }

    /**
     * Initialize all Editor.js instances on the page
     */
    initializeAll() {
        console.log('CourScribeEditorManager: Initializing all editors...');
        
        // Find all editor containers
        const containers = document.querySelectorAll('.ccb-editor-container');
        console.log(`Found ${containers.length} editor containers`);
        
        if (containers.length === 0) {
            console.warn('No editor containers found on page');
            return;
        }
        
        containers.forEach((container, index) => {
            console.log(`Processing container ${index + 1}: ${container.id}`);
            if (!container.id) {
                console.error(`Container ${index + 1} missing ID, skipping`);
                return;
            }
            
            setTimeout(() => {
                this.initializeEditor(container);
            }, index * 100); // Stagger initialization
        });
    }
    
    /**
     * Manual re-initialization (for debugging)
     */
    reinitializeAll() {
        console.log('Manual reinitialization triggered');
        this.destroyAll();
        setTimeout(() => {
            this.initializeAll();
        }, 500);
    }

    /**
     * Initialize a single Editor.js instance
     */
    initializeEditor(container) {
        const editorId = container.id;
        const editorType = container.dataset.editorType || 'simple';
        const fieldName = container.dataset.field;
        const savedContent = this.parseContent(container.dataset.content);

        console.log(`Initializing editor: ${editorId}, type: ${editorType}`);

        // Don't initialize if already exists
        if (this.editors.has(editorId)) {
            console.log(`Editor ${editorId} already exists, skipping`);
            return;
        }

        // Ensure container is visible and has dimensions
        if (container.offsetWidth === 0 || container.offsetHeight === 0) {
            console.warn(`Container ${editorId} has zero dimensions, setting minimum height`);
            container.style.minHeight = '100px';
            container.style.display = 'block';
        }

        const baseConfig = this.configs[editorType] || this.configs.simple;
        const config = {
            ...baseConfig,
            holder: editorId,
            data: savedContent,
            minHeight: 50,
            placeholder: baseConfig.placeholder || 'Start writing...',
            onChange: (api, event) => {
                console.log(`Content changed in ${editorId}`);
                this.handleContentChange(editorId, fieldName, container.dataset);
            },
            onReady: () => {
                console.log(`CourScribe Editor.js ready: ${editorId}`);
                container.classList.add('ccb-editor-ready');
            }
        };

        try {
            console.log('Creating EditorJS instance with config:', config);
            const editor = new EditorJS(config);
            
            this.editors.set(editorId, {
                instance: editor,
                fieldName: fieldName,
                container: container,
                type: editorType
            });
            
            console.log(`Successfully created editor: ${editorId}`);
        } catch (error) {
            console.error(`Failed to initialize Editor.js for ${editorId}:`, error);
            // Fallback to simple textarea
            this.createFallbackEditor(container, savedContent);
        }
    }

    /**
     * Simple configuration for basic text editing
     */
    getSimpleConfig() {
        const tools = {};
        
        // Only add tools that are available
        if (typeof Header !== 'undefined') {
            tools.header = {
                class: Header,
                config: {
                    levels: [2, 3, 4],
                    defaultLevel: 3
                }
            };
        }
        
        if (typeof Paragraph !== 'undefined') {
            tools.paragraph = {
                class: Paragraph,
                inlineToolbar: true
            };
        }
        
        if (typeof List !== 'undefined') {
            tools.list = {
                class: List,
                inlineToolbar: true
            };
        }
        
        if (typeof Quote !== 'undefined') {
            tools.quote = Quote;
        }
        
        if (typeof Marker !== 'undefined') {
            tools.marker = {
                class: Marker,
                shortcut: 'CMD+SHIFT+M'
            };
        }
        
        if (typeof InlineCode !== 'undefined') {
            tools.inlineCode = {
                class: InlineCode,
                shortcut: 'CMD+SHIFT+C'
            };
        }
        
        return {
            tools: tools,
            placeholder: 'Start writing your content...'
        };
    }

    /**
     * Full configuration for rich content editing
     */
    getFullConfig() {
        return {
            tools: {
                header: {
                    class: Header,
                    config: {
                        levels: [1, 2, 3, 4, 5, 6],
                        defaultLevel: 2
                    }
                },
                paragraph: {
                    class: Paragraph,
                    inlineToolbar: true
                },
                list: {
                    class: List,
                    inlineToolbar: true,
                    config: {
                        defaultStyle: 'unordered'
                    }
                },
                checklist: {
                    class: Checklist,
                    inlineToolbar: true
                },
                quote: Quote,
                warning: Warning,
                marker: {
                    class: Marker,
                    shortcut: 'CMD+SHIFT+M'
                },
                code: Code,
                delimiter: Delimiter,
                inlineCode: {
                    class: InlineCode,
                    shortcut: 'CMD+SHIFT+C'
                },
                table: Table,
                embed: Embed,
                image: {
                    class: ImageTool,
                    config: {
                        endpoints: {
                            byFile: CourScribeCurriculumBuilder.ajaxUrl + '?action=courscribe_upload_image',
                        },
                        additionalRequestHeaders: {
                            'X-WP-Nonce': CourScribeCurriculumBuilder.nonce
                        }
                    }
                }
            },
            placeholder: 'Tell your story...'
        };
    }

    /**
     * Module configuration (Sections in Editor.js context)
     */
    getModuleConfig() {
        return {
            tools: {
                header: {
                    class: Header,
                    config: {
                        levels: [2, 3, 4],
                        defaultLevel: 2
                    }
                },
                paragraph: {
                    class: Paragraph,
                    inlineToolbar: true
                },
                list: {
                    class: List,
                    inlineToolbar: true
                },
                checklist: {
                    class: Checklist,
                    inlineToolbar: true
                },
                quote: Quote,
                warning: Warning,
                marker: {
                    class: Marker,
                    shortcut: 'CMD+SHIFT+M'
                },
                delimiter: Delimiter,
                inlineCode: {
                    class: InlineCode,
                    shortcut: 'CMD+SHIFT+C'
                },
                table: Table
            },
            placeholder: 'Describe this module section...'
        };
    }

    /**
     * Lesson configuration (Pages in Editor.js context)
     */
    getLessonConfig() {
        return {
            tools: {
                header: {
                    class: Header,
                    config: {
                        levels: [3, 4, 5],
                        defaultLevel: 3
                    }
                },
                paragraph: {
                    class: Paragraph,
                    inlineToolbar: true
                },
                list: {
                    class: List,
                    inlineToolbar: true
                },
                checklist: {
                    class: Checklist,
                    inlineToolbar: true
                },
                quote: Quote,
                warning: Warning,
                marker: {
                    class: Marker,
                    shortcut: 'CMD+SHIFT+M'
                },
                code: Code,
                delimiter: Delimiter,
                inlineCode: {
                    class: InlineCode,
                    shortcut: 'CMD+SHIFT+C'
                },
                table: Table,
                embed: Embed
            },
            placeholder: 'Create your lesson content...'
        };
    }

    /**
     * Overview configuration for curriculum overview
     */
    getOverviewConfig() {
        return {
            ...this.getFullConfig(),
            placeholder: 'Provide a comprehensive overview of this curriculum...',
            data: {
                blocks: [
                    {
                        type: "paragraph",
                        data: {
                            text: "This comprehensive curriculum introduces students to advanced educational concepts, covering both theoretical foundations and practical applications. Students will learn to develop effective learning strategies using industry-standard tools and best practices."
                        }
                    },
                    {
                        type: "header",
                        data: {
                            text: "Curriculum Goals",
                            level: 3
                        }
                    },
                    {
                        type: "list",
                        data: {
                            style: "unordered",
                            items: [
                                "Master modern educational frameworks and methodologies",
                                "Understand learning design principles", 
                                "Implement assessment strategies and evaluation methods",
                                "Deploy learning solutions across diverse platforms"
                            ]
                        }
                    },
                    {
                        type: "header",
                        data: {
                            text: "Target Audience",
                            level: 3
                        }
                    },
                    {
                        type: "paragraph",
                        data: {
                            text: "This curriculum is designed for educators, instructional designers, and learning professionals who want to enhance their skills in curriculum development and educational technology."
                        }
                    }
                ]
            }
        };
    }

    /**
     * Objectives configuration
     */
    getObjectivesConfig() {
        return {
            tools: {
                header: {
                    class: Header,
                    config: {
                        levels: [3, 4, 5],
                        defaultLevel: 3
                    }
                },
                paragraph: {
                    class: Paragraph,
                    inlineToolbar: true
                },
                list: {
                    class: List,
                    inlineToolbar: true,
                    config: {
                        defaultStyle: 'ordered'
                    }
                },
                marker: {
                    class: Marker,
                    shortcut: 'CMD+SHIFT+M'
                },
                inlineCode: {
                    class: InlineCode,
                    shortcut: 'CMD+SHIFT+C'
                }
            },
            placeholder: 'Define learning objectives using Bloom\'s Taxonomy...',
            data: {
                blocks: [
                    {
                        type: "paragraph",
                        data: {
                            text: "<strong>By the end of this curriculum, students will be able to:</strong>"
                        }
                    },
                    {
                        type: "list",
                        data: {
                            style: "ordered",
                            items: [
                                "<strong>Create</strong> comprehensive curriculum plans using modern educational frameworks",
                                "<strong>Implement</strong> interactive learning experiences using technology and multimedia",
                                "<strong>Develop</strong> assessment strategies that measure student learning effectively",
                                "<strong>Design</strong> inclusive learning environments for diverse student populations",
                                "<strong>Deploy</strong> curriculum materials across multiple learning management systems",
                                "<strong>Apply</strong> best practices for instructional design and learning analytics"
                            ]
                        }
                    }
                ]
            }
        };
    }

    /**
     * Assessment configuration
     */
    getAssessmentConfig() {
        return {
            ...this.getFullConfig(),
            placeholder: 'Define assessment strategies and methods...',
            data: {
                blocks: [
                    {
                        type: "paragraph",
                        data: {
                            text: "This curriculum employs a multi-faceted assessment approach designed to evaluate student learning at various stages and through different methods:"
                        }
                    },
                    {
                        type: "header",
                        data: {
                            text: "Formative Assessments",
                            level: 3
                        }
                    },
                    {
                        type: "list",
                        data: {
                            style: "unordered",
                            items: [
                                "<strong>Weekly Reflection Papers</strong> - Students analyze key concepts and their applications",
                                "<strong>Peer Review Activities</strong> - Collaborative evaluation of project work",
                                "<strong>Interactive Quizzes</strong> - Regular knowledge checks with immediate feedback"
                            ]
                        }
                    },
                    {
                        type: "header",
                        data: {
                            text: "Summative Assessments",
                            level: 3
                        }
                    },
                    {
                        type: "list",
                        data: {
                            style: "unordered",
                            items: [
                                "<strong>Capstone Project</strong> - Comprehensive curriculum design project",
                                "<strong>Portfolio Submission</strong> - Collection of best work throughout the course",
                                "<strong>Final Presentation</strong> - Public presentation of learning outcomes"
                            ]
                        }
                    }
                ]
            }
        };
    }

    /**
     * Parse content from JSON string
     */
    parseContent(contentString) {
        if (!contentString || contentString === '') {
            return {};
        }

        try {
            const parsed = JSON.parse(contentString);
            
            // If it's already Editor.js format, return it
            if (parsed && typeof parsed === 'object' && parsed.blocks) {
                return parsed;
            }
            
            // If it's plain text or HTML, convert to Editor.js format
            if (typeof parsed === 'string') {
                return {
                    blocks: [
                        {
                            type: "paragraph",
                            data: {
                                text: parsed
                            }
                        }
                    ]
                };
            }
            
            return {};
        } catch (error) {
            // If parsing fails, treat as plain text
            return {
                blocks: [
                    {
                        type: "paragraph",
                        data: {
                            text: contentString
                        }
                    }
                ]
            };
        }
    }

    /**
     * Handle content changes and auto-save
     */
    async handleContentChange(editorId, fieldName, dataSet) {
        const editorData = this.editors.get(editorId);
        if (!editorData) return;

        try {
            // Debounce saves
            clearTimeout(editorData.saveTimeout);
            editorData.saveTimeout = setTimeout(async () => {
                const outputData = await editorData.instance.save();
                await this.saveContent(outputData, fieldName, dataSet);
            }, 2000); // Save after 2 seconds of inactivity
        } catch (error) {
            console.error('Error handling content change:', error);
        }
    }

    /**
     * Save content to server
     */
    async saveContent(data, fieldName, dataSet) {
        const payload = {
            action: 'courscribe_save_editor_content',
            nonce: CourScribeCurriculumBuilder.nonce,
            field: fieldName,
            content: JSON.stringify(data),
            ...dataSet
        };

        try {
            const response = await fetch(CourScribeCurriculumBuilder.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(payload)
            });

            const result = await response.json();
            
            if (result.success) {
                console.log('Content saved successfully:', fieldName);
                this.showSaveIndicator(fieldName, 'saved');
            } else {
                console.error('Failed to save content:', result.data);
                this.showSaveIndicator(fieldName, 'error');
            }
        } catch (error) {
            console.error('Network error saving content:', error);
            this.showSaveIndicator(fieldName, 'error');
        }
    }

    /**
     * Show save status indicator
     */
    showSaveIndicator(fieldName, status) {
        // Create or update save indicator
        const indicator = document.getElementById(`save-indicator-${fieldName}`) || 
                         this.createSaveIndicator(fieldName);
        
        indicator.className = `ccb-save-indicator ccb-save-${status}`;
        indicator.textContent = status === 'saved' ? 'Saved' : 
                               status === 'saving' ? 'Saving...' : 'Error saving';
        
        if (status === 'saved') {
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        }
    }

    /**
     * Create save indicator element
     */
    createSaveIndicator(fieldName) {
        const indicator = document.createElement('div');
        indicator.id = `save-indicator-${fieldName}`;
        indicator.className = 'ccb-save-indicator';
        
        // Try to find the editor container and append the indicator
        const container = document.querySelector(`[data-field="${fieldName}"]`);
        if (container && container.parentNode) {
            container.parentNode.insertBefore(indicator, container.nextSibling);
        } else {
            document.body.appendChild(indicator);
        }
        
        return indicator;
    }

    /**
     * Create fallback editor for when Editor.js fails
     */
    createFallbackEditor(container, savedContent) {
        console.log(`Creating fallback editor for ${container.id}`);
        
        // Clear container first
        container.innerHTML = '';
        
        const textarea = document.createElement('textarea');
        textarea.className = 'ccb-fallback-editor';
        textarea.placeholder = container.dataset.editorType === 'simple' ? 
            'Enter your content here...' : 
            'Enter your content here. Editor.js failed to load.';
        textarea.style.width = '100%';
        textarea.style.minHeight = '100px';
        textarea.style.resize = 'vertical';
        
        // Extract text from Editor.js blocks if present
        let textContent = '';
        if (savedContent && savedContent.blocks) {
            textContent = savedContent.blocks
                .map(block => {
                    if (block.data.text) return block.data.text;
                    if (block.data.items) return block.data.items.join('\n');
                    return '';
                })
                .filter(text => text.trim())
                .join('\n\n');
        } else if (typeof savedContent === 'string' && savedContent.trim()) {
            textContent = savedContent;
        }
        
        textarea.value = textContent;
        container.appendChild(textarea);
        container.classList.add('ccb-fallback-active');
        
        // Add change handler for fallback
        let saveTimeout;
        textarea.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                this.handleFallbackChange(textarea.value, container.dataset.field, container.dataset);
            }, 1000);
        });
        
        console.log(`Fallback editor created for ${container.id}`);
    }

    /**
     * Handle fallback editor changes
     */
    async handleFallbackChange(content, fieldName, dataSet) {
        const data = {
            blocks: [
                {
                    type: "paragraph",
                    data: {
                        text: content
                    }
                }
            ]
        };
        
        await this.saveContent(data, fieldName, dataSet);
    }

    /**
     * Get editor instance by ID
     */
    getEditor(editorId) {
        return this.editors.get(editorId);
    }

    /**
     * Destroy all editors
     */
    destroyAll() {
        this.editors.forEach((editorData, editorId) => {
            if (editorData.instance && typeof editorData.instance.destroy === 'function') {
                editorData.instance.destroy();
            }
        });
        this.editors.clear();
    }

    /**
     * Export all editor content
     */
    async exportAll() {
        const allContent = {};
        
        for (const [editorId, editorData] of this.editors) {
            try {
                const content = await editorData.instance.save();
                allContent[editorData.fieldName] = content;
            } catch (error) {
                console.error(`Failed to export content for ${editorId}:`, error);
            }
        }
        
        return allContent;
    }
}

// Initialize when DOM is ready and all scripts loaded
function initializeCourScribeEditors() {
    console.log('Attempting to initialize CourScribe editors...');
    
    // Check if all required dependencies are loaded
    if (typeof EditorJS === 'undefined') {
        console.warn('Editor.js not loaded, retrying in 1 second...');
        setTimeout(initializeCourScribeEditors, 1000);
        return;
    }
    
    if (typeof Header === 'undefined' || typeof Paragraph === 'undefined') {
        console.warn('Editor.js tools not loaded, retrying in 1 second...');
        setTimeout(initializeCourScribeEditors, 1000);
        return;
    }
    
    console.log('All Editor.js dependencies loaded, initializing manager...');
    window.CourScribeEditorManager = new CourScribeEditorManager();
    
    // Initialize all editors
    setTimeout(() => {
        console.log('Initializing all editors...');
        window.CourScribeEditorManager.initializeAll();
    }, 100);
}

// Multiple initialization triggers to ensure editors load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCourScribeEditors);
} else {
    // DOM is already loaded
    setTimeout(initializeCourScribeEditors, 100);
}

// Fallback initialization after window load
window.addEventListener('load', () => {
    if (!window.CourScribeEditorManager) {
        console.log('Fallback editor initialization triggered');
        setTimeout(initializeCourScribeEditors, 500);
    }
});

// Add global debugging functions
window.debugCourScribeEditors = function() {
    console.log('=== CourScribe Editors Debug ===');
    console.log('EditorJS available:', typeof EditorJS !== 'undefined');
    console.log('Header available:', typeof Header !== 'undefined');
    console.log('Paragraph available:', typeof Paragraph !== 'undefined');
    console.log('List available:', typeof List !== 'undefined');
    console.log('Manager available:', !!window.CourScribeEditorManager);
    console.log('Editor containers:', document.querySelectorAll('.ccb-editor-container').length);
    
    if (window.CourScribeEditorManager) {
        console.log('Active editors:', window.CourScribeEditorManager.editors.size);
        window.CourScribeEditorManager.editors.forEach((data, id) => {
            console.log(`  - ${id}: ${data.type}`);
        });
    }
};

window.reinitializeCourScribeEditors = function() {
    if (window.CourScribeEditorManager) {
        window.CourScribeEditorManager.reinitializeAll();
    } else {
        initializeCourScribeEditors();
    }
};

// Add manual trigger button for debugging (temporary)
setTimeout(() => {
    if (document.querySelector('.ccb-curriculum-builder-container') && !document.getElementById('debug-editors-btn')) {
        const debugBtn = document.createElement('button');
        debugBtn.id = 'debug-editors-btn';
        debugBtn.textContent = '🔧 Debug Editors';
        debugBtn.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; background: #ff4444; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-size: 12px;';
        debugBtn.onclick = () => {
            debugCourScribeEditors();
            reinitializeCourScribeEditors();
        };
        document.body.appendChild(debugBtn);
    }
}, 2000);