<?php
/**
 * AI Assistant Panel Component
 * Contextual AI assistance for curriculum development
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ccb-ai-header">
    <h3 class="ccb-ai-title">
        <i class="fas fa-magic"></i>
        AI Assistant
    </h3>
    <button class="ccb-close-btn" id="ccbCloseAI" title="Close AI Assistant">
        <i class="fas fa-times"></i>
    </button>
</div>

<div class="ccb-ai-content">
    <!-- AI Input Area -->
    <div class="ccb-ai-input-section">
        <textarea class="ccb-ai-input" id="ccbAIInput" 
                  placeholder="Describe what you'd like to generate or improve...

Examples:
• Create a module about project management
• Generate lesson objectives for web development
• Suggest teaching points for data analysis
• Improve this course description"></textarea>
        <button class="ccb-btn ccb-btn-primary ccb-ai-generate-btn" id="ccbAIGenerateBtn">
            <i class="fas fa-magic"></i>
            Generate Content
        </button>
    </div>

    <!-- Quick Suggestions -->
    <div class="ccb-ai-suggestions" id="ccbAISuggestions">
        <h4 class="ccb-ai-suggestions-title">Quick Suggestions</h4>
        
        <div class="ccb-ai-suggestion" data-action="generate-objectives">
            <div class="ccb-ai-suggestion-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="ccb-ai-suggestion-content">
                <h5>Generate Learning Objectives</h5>
                <p>Create SMART learning objectives based on your curriculum goals</p>
            </div>
        </div>
        
        <div class="ccb-ai-suggestion" data-action="create-course-outline">
            <div class="ccb-ai-suggestion-icon">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="ccb-ai-suggestion-content">
                <h5>Create Course Outline</h5>
                <p>Generate a structured course outline with modules and lessons</p>
            </div>
        </div>
        
        <div class="ccb-ai-suggestion" data-action="generate-assessment">
            <div class="ccb-ai-suggestion-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="ccb-ai-suggestion-content">
                <h5>Generate Assessment</h5>
                <p>Create quizzes, assignments, and evaluation criteria</p>
            </div>
        </div>
        
        <div class="ccb-ai-suggestion" data-action="improve-content">
            <div class="ccb-ai-suggestion-icon">
                <i class="fas fa-edit"></i>
            </div>
            <div class="ccb-ai-suggestion-content">
                <h5>Improve Content</h5>
                <p>Enhance your existing content with AI-powered suggestions</p>
            </div>
        </div>
        
        <div class="ccb-ai-suggestion" data-action="create-activities">
            <div class="ccb-ai-suggestion-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="ccb-ai-suggestion-content">
                <h5>Create Learning Activities</h5>
                <p>Design interactive exercises and group activities</p>
            </div>
        </div>
        
        <div class="ccb-ai-suggestion" data-action="generate-resources">
            <div class="ccb-ai-suggestion-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="ccb-ai-suggestion-content">
                <h5>Suggest Resources</h5>
                <p>Find relevant textbooks, articles, and multimedia resources</p>
            </div>
        </div>
    </div>

    <!-- AI History -->
    <div class="ccb-ai-history" id="ccbAIHistory">
        <h4 class="ccb-ai-history-title">Recent Generations</h4>
        <div class="ccb-ai-history-list">
            <!-- History items will be populated by JavaScript -->
        </div>
    </div>

    <!-- AI Tips -->
    <div class="ccb-ai-tips">
        <h4 class="ccb-ai-tips-title">
            <i class="fas fa-lightbulb"></i>
            Pro Tips
        </h4>
        <ul class="ccb-ai-tips-list">
            <li>Be specific about your learning objectives and target audience</li>
            <li>Include context about the subject matter and difficulty level</li>
            <li>Mention any specific teaching methodologies you prefer</li>
            <li>Use the generated content as a starting point and customize as needed</li>
        </ul>
    </div>
</div>

<style>
/* AI Input Section */
.ccb-ai-input-section {
    margin-bottom: var(--ccb-spacing-lg);
}

.ccb-ai-input {
    width: 100%;
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    color: var(--ccb-text-primary);
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
    resize: vertical;
    min-height: 100px;
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-ai-input:focus {
    outline: none;
    border-color: var(--ccb-primary-gold);
    box-shadow: 0 0 0 3px rgba(228, 178, 111, 0.1);
}

.ccb-ai-input::placeholder {
    color: var(--ccb-text-muted);
}

.ccb-ai-generate-btn {
    width: 100%;
    justify-content: center;
}

/* AI Suggestions */
.ccb-ai-suggestions {
    margin-bottom: var(--ccb-spacing-xl);
}

.ccb-ai-suggestions-title {
    color: var(--ccb-text-secondary);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 var(--ccb-spacing-md) 0;
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
}

.ccb-ai-suggestion {
    display: flex;
    align-items: flex-start;
    gap: var(--ccb-spacing-md);
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    margin-bottom: var(--ccb-spacing-sm);
    cursor: pointer;
    transition: all var(--ccb-transition);
}

.ccb-ai-suggestion:hover {
    border-color: var(--ccb-primary-gold);
    background: var(--ccb-hover-bg);
    transform: translateY(-1px);
}

.ccb-ai-suggestion-icon {
    width: 32px;
    height: 32px;
    background: var(--ccb-gradient-secondary);
    border-radius: var(--ccb-border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.ccb-ai-suggestion-content h5 {
    margin: 0 0 var(--ccb-spacing-xs) 0;
    color: var(--ccb-text-primary);
    font-size: 14px;
    font-weight: 600;
}

.ccb-ai-suggestion-content p {
    margin: 0;
    color: var(--ccb-text-muted);
    font-size: 12px;
    line-height: 1.4;
}

/* AI History */
.ccb-ai-history {
    margin-bottom: var(--ccb-spacing-xl);
}

.ccb-ai-history-title {
    color: var(--ccb-text-secondary);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 var(--ccb-spacing-md) 0;
}

.ccb-ai-history-item {
    background: var(--ccb-bg-elevated);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    margin-bottom: var(--ccb-spacing-sm);
    cursor: pointer;
    transition: all var(--ccb-transition);
}

.ccb-ai-history-item:hover {
    background: var(--ccb-hover-bg);
}

.ccb-ai-history-item-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--ccb-text-primary);
    margin-bottom: var(--ccb-spacing-xs);
}

.ccb-ai-history-item-preview {
    font-size: 11px;
    color: var(--ccb-text-muted);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ccb-ai-history-item-meta {
    font-size: 10px;
    color: var(--ccb-text-muted);
    margin-top: var(--ccb-spacing-xs);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* AI Tips */
.ccb-ai-tips {
    background: var(--ccb-bg-elevated);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
}

.ccb-ai-tips-title {
    color: var(--ccb-primary-gold);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 var(--ccb-spacing-md) 0;
    display: flex;
    align-items: center;
    gap: var(--ccb-spacing-sm);
}

.ccb-ai-tips-list {
    margin: 0;
    padding-left: var(--ccb-spacing-lg);
    color: var(--ccb-text-muted);
    font-size: 12px;
    line-height: 1.4;
}

.ccb-ai-tips-list li {
    margin-bottom: var(--ccb-spacing-xs);
}

/* Loading State */
.ccb-ai-loading {
    text-align: center;
    padding: var(--ccb-spacing-lg);
    color: var(--ccb-text-muted);
}

.ccb-ai-loading-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid var(--ccb-border-color);
    border-top: 2px solid var(--ccb-primary-gold);
    border-radius: 50%;
    animation: ccb-spin 1s linear infinite;
    margin: 0 auto var(--ccb-spacing-sm) auto;
}

/* Generated Content Preview */
.ccb-ai-generated-content {
    background: var(--ccb-bg-card);
    border: 1px solid var(--ccb-primary-gold);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-md);
    margin: var(--ccb-spacing-md) 0;
    position: relative;
}

.ccb-ai-generated-content::before {
    content: 'AI Generated';
    position: absolute;
    top: -8px;
    left: var(--ccb-spacing-md);
    background: var(--ccb-primary-gold);
    color: var(--ccb-bg-primary);
    padding: 2px var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ccb-ai-generated-actions {
    display: flex;
    gap: var(--ccb-spacing-sm);
    margin-top: var(--ccb-spacing-md);
    padding-top: var(--ccb-spacing-md);
    border-top: 1px solid var(--ccb-border-color);
}

.ccb-ai-generated-actions .ccb-btn {
    flex: 1;
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    font-size: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ccb-ai-suggestion {
        padding: var(--ccb-spacing-sm);
    }
    
    .ccb-ai-suggestion-icon {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    
    .ccb-ai-generated-actions {
        flex-direction: column;
    }
}
</style>