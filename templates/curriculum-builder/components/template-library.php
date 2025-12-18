<?php
/**
 * Template Library Component
 * Professional curriculum templates for quick start
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ccb-templates-content">
    <div class="ccb-templates-header">
        <h2 class="ccb-templates-title">Choose a Template</h2>
        <button class="ccb-close-btn" id="ccbCloseTemplates" title="Close Templates">×</button>
    </div>
    
    <div class="ccb-templates-body">
        <!-- Template Categories -->
        <div class="ccb-template-categories">
            <button class="ccb-template-category active" data-category="all">All Templates</button>
            <button class="ccb-template-category" data-category="technology">Technology</button>
            <button class="ccb-template-category" data-category="business">Business</button>
            <button class="ccb-template-category" data-category="creative">Creative</button>
            <button class="ccb-template-category" data-category="academic">Academic</button>
            <button class="ccb-template-category" data-category="healthcare">Healthcare</button>
        </div>

        <!-- Templates Grid -->
        <div class="ccb-templates-grid" id="ccbTemplatesGrid">
            
            <!-- Technology Templates -->
            <div class="ccb-template-card" data-template-id="full-stack-web-dev" data-category="technology">
                <div class="ccb-template-preview">
                    <i class="fas fa-code"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Full Stack Web Development</h3>
                    <p class="ccb-template-description">Complete frontend and backend development curriculum with modern frameworks</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">12 weeks</span>
                        <span class="ccb-template-level">Intermediate</span>
                        <span class="ccb-template-modules">6 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <div class="ccb-template-card" data-template-id="data-science-fundamentals" data-category="technology">
                <div class="ccb-template-preview">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Data Science Fundamentals</h3>
                    <p class="ccb-template-description">Python programming, statistics, machine learning, and data visualization</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">10 weeks</span>
                        <span class="ccb-template-level">Beginner</span>
                        <span class="ccb-template-modules">5 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <div class="ccb-template-card" data-template-id="mobile-app-development" data-category="technology">
                <div class="ccb-template-preview">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Mobile App Development</h3>
                    <p class="ccb-template-description">React Native and Flutter development for iOS and Android</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">14 weeks</span>
                        <span class="ccb-template-level">Advanced</span>
                        <span class="ccb-template-modules">7 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <div class="ccb-template-card" data-template-id="cybersecurity-essentials" data-category="technology">
                <div class="ccb-template-preview">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Cybersecurity Essentials</h3>
                    <p class="ccb-template-description">Network security, ethical hacking, and digital forensics</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">8 weeks</span>
                        <span class="ccb-template-level">Intermediate</span>
                        <span class="ccb-template-modules">4 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <!-- Business Templates -->
            <div class="ccb-template-card" data-template-id="digital-marketing-strategy" data-category="business">
                <div class="ccb-template-preview">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Digital Marketing Strategy</h3>
                    <p class="ccb-template-description">SEO, social media, content marketing, and analytics</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">6 weeks</span>
                        <span class="ccb-template-level">Beginner</span>
                        <span class="ccb-template-modules">4 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <div class="ccb-template-card" data-template-id="project-management-essentials" data-category="business">
                <div class="ccb-template-preview">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Project Management Essentials</h3>
                    <p class="ccb-template-description">Agile methodologies, team leadership, and project planning</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">8 weeks</span>
                        <span class="ccb-template-level">Intermediate</span>
                        <span class="ccb-template-modules">5 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <!-- Creative Templates -->
            <div class="ccb-template-card" data-template-id="ui-ux-design-fundamentals" data-category="creative">
                <div class="ccb-template-preview">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">UI/UX Design Fundamentals</h3>
                    <p class="ccb-template-description">Design thinking, user research, prototyping, and usability testing</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">10 weeks</span>
                        <span class="ccb-template-level">Beginner</span>
                        <span class="ccb-template-modules">6 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <div class="ccb-template-card" data-template-id="graphic-design-principles" data-category="creative">
                <div class="ccb-template-preview">
                    <i class="fas fa-palette"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Graphic Design Principles</h3>
                    <p class="ccb-template-description">Typography, color theory, layout design, and Adobe Creative Suite</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">12 weeks</span>
                        <span class="ccb-template-level">Beginner</span>
                        <span class="ccb-template-modules">6 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

            <!-- Academic Templates -->
            <div class="ccb-template-card" data-template-id="research-methodology" data-category="academic">
                <div class="ccb-template-preview">
                    <i class="fas fa-microscope"></i>
                </div>
                <div class="ccb-template-info">
                    <h3 class="ccb-template-name">Research Methodology</h3>
                    <p class="ccb-template-description">Qualitative and quantitative research methods for academic studies</p>
                    <div class="ccb-template-meta">
                        <span class="ccb-template-duration">8 weeks</span>
                        <span class="ccb-template-level">Advanced</span>
                        <span class="ccb-template-modules">4 modules</span>
                    </div>
                </div>
                <div class="ccb-template-actions">
                    <button class="ccb-btn ccb-btn-secondary ccb-template-preview-btn">Preview</button>
                    <button class="ccb-btn ccb-btn-primary ccb-template-select-btn">Use Template</button>
                </div>
            </div>

        </div>

        <!-- Custom Template Option -->
        <div class="ccb-custom-template-section">
            <div class="ccb-custom-template-card">
                <div class="ccb-custom-template-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="ccb-custom-template-content">
                    <h3>Create Custom Template</h3>
                    <p>Start from scratch and build your own curriculum structure</p>
                </div>
                <button class="ccb-btn ccb-btn-secondary" id="ccbCreateCustomTemplate">
                    Start Blank
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Templates Modal Content */
.ccb-templates-content {
    background: var(--ccb-bg-card);
    border-radius: var(--ccb-border-radius-lg);
    max-width: 900px;
    width: 90%;
    max-height: 85vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.ccb-templates-header {
    padding: var(--ccb-spacing-xl);
    border-bottom: 1px solid var(--ccb-border-color);
    position: relative;
}

.ccb-templates-title {
    margin: 0;
    color: var(--ccb-text-primary);
    font-size: 24px;
    font-weight: 600;
}

.ccb-templates-body {
    padding: var(--ccb-spacing-xl);
    overflow-y: auto;
    flex: 1;
}

/* Template Categories */
.ccb-template-categories {
    display: flex;
    gap: var(--ccb-spacing-sm);
    margin-bottom: var(--ccb-spacing-xl);
    flex-wrap: wrap;
}

.ccb-template-category {
    background: var(--ccb-bg-elevated);
    color: var(--ccb-text-secondary);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius);
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--ccb-transition);
}

.ccb-template-category:hover,
.ccb-template-category.active {
    background: var(--ccb-gradient-secondary);
    color: white;
    border-color: transparent;
}

/* Templates Grid */
.ccb-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--ccb-spacing-lg);
    margin-bottom: var(--ccb-spacing-xl);
}

.ccb-template-card {
    background: var(--ccb-bg-elevated);
    border: 1px solid var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    overflow: hidden;
    transition: all var(--ccb-transition);
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.ccb-template-card:hover {
    border-color: var(--ccb-primary-gold);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(228, 178, 111, 0.2);
}

.ccb-template-card.hidden {
    display: none;
}

.ccb-template-preview {
    height: 120px;
    background: var(--ccb-gradient-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: var(--ccb-primary-gold);
}

.ccb-template-info {
    padding: var(--ccb-spacing-md);
    flex: 1;
}

.ccb-template-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--ccb-text-primary);
    margin: 0 0 var(--ccb-spacing-sm) 0;
}

.ccb-template-description {
    font-size: 14px;
    color: var(--ccb-text-muted);
    line-height: 1.4;
    margin: 0 0 var(--ccb-spacing-md) 0;
}

.ccb-template-meta {
    display: flex;
    gap: var(--ccb-spacing-sm);
    flex-wrap: wrap;
}

.ccb-template-meta span {
    background: var(--ccb-bg-card);
    color: var(--ccb-text-muted);
    font-size: 11px;
    padding: var(--ccb-spacing-xs) var(--ccb-spacing-sm);
    border-radius: var(--ccb-border-radius-sm);
    font-weight: 500;
}

.ccb-template-duration {
    background: rgba(40, 167, 69, 0.2) !important;
    color: var(--ccb-success) !important;
}

.ccb-template-level {
    background: rgba(23, 162, 184, 0.2) !important;
    color: var(--ccb-info) !important;
}

.ccb-template-modules {
    background: rgba(255, 193, 7, 0.2) !important;
    color: var(--ccb-warning) !important;
}

.ccb-template-actions {
    padding: var(--ccb-spacing-md);
    border-top: 1px solid var(--ccb-border-color);
    display: flex;
    gap: var(--ccb-spacing-sm);
}

.ccb-template-actions .ccb-btn {
    flex: 1;
    font-size: 12px;
    padding: var(--ccb-spacing-sm) var(--ccb-spacing-md);
    justify-content: center;
}

/* Custom Template Section */
.ccb-custom-template-section {
    border-top: 1px solid var(--ccb-border-color);
    padding-top: var(--ccb-spacing-xl);
}

.ccb-custom-template-card {
    background: var(--ccb-bg-elevated);
    border: 2px dashed var(--ccb-border-color);
    border-radius: var(--ccb-border-radius-lg);
    padding: var(--ccb-spacing-xl);
    text-align: center;
    transition: all var(--ccb-transition);
}

.ccb-custom-template-card:hover {
    border-color: var(--ccb-primary-gold);
    background: var(--ccb-hover-bg);
}

.ccb-custom-template-icon {
    font-size: 48px;
    color: var(--ccb-text-muted);
    margin-bottom: var(--ccb-spacing-md);
}

.ccb-custom-template-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--ccb-text-primary);
    margin: 0 0 var(--ccb-spacing-sm) 0;
}

.ccb-custom-template-content p {
    color: var(--ccb-text-muted);
    margin: 0 0 var(--ccb-spacing-lg) 0;
}

/* Template Preview Modal */
.ccb-template-preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 3000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--ccb-spacing-lg);
}

.ccb-template-preview-content {
    background: var(--ccb-bg-card);
    border-radius: var(--ccb-border-radius-lg);
    max-width: 600px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.ccb-template-preview-header {
    padding: var(--ccb-spacing-lg);
    border-bottom: 1px solid var(--ccb-border-color);
    position: sticky;
    top: 0;
    background: var(--ccb-bg-card);
}

.ccb-template-preview-body {
    padding: var(--ccb-spacing-lg);
}

/* Responsive Design */
@media (max-width: 768px) {
    .ccb-templates-content {
        width: 95%;
        height: 95vh;
    }
    
    .ccb-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .ccb-template-categories {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: var(--ccb-spacing-sm);
    }
    
    .ccb-template-category {
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .ccb-template-actions {
        flex-direction: column;
    }
}

/* Loading State */
.ccb-templates-loading {
    text-align: center;
    padding: var(--ccb-spacing-2xl);
    color: var(--ccb-text-muted);
}

.ccb-templates-loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--ccb-border-color);
    border-top: 3px solid var(--ccb-primary-gold);
    border-radius: 50%;
    animation: ccb-spin 1s linear infinite;
    margin: 0 auto var(--ccb-spacing-md) auto;
}

/* Empty State */
.ccb-templates-empty {
    text-align: center;
    padding: var(--ccb-spacing-2xl);
    color: var(--ccb-text-muted);
}

.ccb-templates-empty i {
    font-size: 48px;
    margin-bottom: var(--ccb-spacing-md);
}
</style>