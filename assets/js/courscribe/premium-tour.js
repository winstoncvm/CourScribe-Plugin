/**
 * CourScribe Premium Guided Tour System
 * Provides an interactive tour for new users
 */
class CourScribePremiumTour {
    constructor() {
        this.currentStep = 0;
        this.steps = [];
        this.overlay = null;
        this.tooltip = null;
        this.isActive = false;
        this.userTier = 'basics';
        
        this.init();
    }
    
    init() {
        // Check if tour should be started
        const urlParams = new URLSearchParams(window.location.search);
        const startTour = urlParams.get('tour');
        const createCurriculum = urlParams.get('create_curriculum');
        
        if (startTour === '1') {
            setTimeout(() => this.startTour(), 1000);
        } else if (createCurriculum === '1') {
            setTimeout(() => this.startCurriculumTour(), 1000);
        }
        
        // Initialize tour steps based on user tier
        this.initializeTourSteps();
        
        // Bind events
        this.bindEvents();
    }
    
    initializeTourSteps() {
        this.steps = [
            {
                target: '.courscribe-curriculum-manager',
                title: '🎉 Welcome to Your Studio!',
                content: 'This is your curriculum development headquarters. Here you can create, manage, and collaborate on educational content.',
                position: 'center',
                type: 'welcome'
            },
            {
                target: '.courscribe-stepper-nextBtn',
                title: '📚 Create Your First Curriculum',
                content: this.userTier === 'basics' 
                    ? 'Click here to create your first curriculum. You can create 1 curriculum with the free plan.'
                    : 'Click here to create unlimited curriculums with your premium plan. Use AI generation for faster creation!',
                position: 'bottom',
                type: 'action'
            },
            {
                target: '.recent-changes-section',
                title: '📊 Track Your Progress',
                content: 'This section shows your recent curriculum changes and activities. Stay on top of your content development.',
                position: 'top',
                type: 'info'
            }
        ];
        
        // Add premium-specific steps
        if (this.userTier !== 'basics') {
            this.steps.push(
                {
                    target: '.get-ai-button',
                    title: '🤖 AI-Powered Content Generation',
                    content: 'Generate courses, modules, and lessons automatically with AI. This premium feature saves hours of work!',
                    position: 'top',
                    type: 'premium',
                    highlight: true
                },
                {
                    target: '.courscribe-invite-section',
                    title: '👥 Team Collaboration',
                    content: 'Invite collaborators and clients to work together on your curriculums. Share feedback and co-create content.',
                    position: 'left',
                    type: 'premium'
                }
            );
        }
        
        // Add final step
        this.steps.push({
            target: 'body',
            title: '🚀 You\'re All Set!',
            content: this.userTier === 'basics'
                ? 'Start creating amazing educational content! Upgrade anytime to unlock premium features like AI generation and team collaboration.'
                : 'You have access to all premium features! Create unlimited curriculums, use AI generation, and collaborate with your team.',
            position: 'center',
            type: 'completion'
        });
    }
    
    startTour() {
        if (this.isActive) return;
        
        this.isActive = true;
        this.currentStep = 0;
        this.createOverlay();
        this.showStep(this.currentStep);
        
        // Track tour start
        this.trackEvent('tour_started', { user_tier: this.userTier });
    }
    
    startCurriculumTour() {
        // Focus on curriculum creation
        const createBtn = document.querySelector('.courscribe-stepper-nextBtn');
        if (createBtn) {
            this.highlightElement(createBtn);
            this.showTooltip(createBtn, {
                title: '✨ Create Your First Curriculum',
                content: 'Let\'s start by creating your first curriculum. Click here to begin!',
                position: 'bottom',
                showNext: false,
                showSkip: true
            });
        }
    }
    
    showStep(stepIndex) {
        if (stepIndex >= this.steps.length) {
            this.completeTour();
            return;
        }
        
        const step = this.steps[stepIndex];
        const target = step.target === 'body' ? document.body : document.querySelector(step.target);
        
        if (!target && step.target !== 'body') {
            // Skip this step if target not found
            this.nextStep();
            return;
        }
        
        // Update overlay and highlight
        this.updateOverlay(target, step);
        
        // Show tooltip
        this.showTooltip(target, step);
        
        // Scroll to element if needed
        if (target !== document.body) {
            this.scrollToElement(target);
        }
    }
    
    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'courscribe-tour-overlay';
        this.overlay.innerHTML = `
            <div class="tour-backdrop"></div>
            <div class="tour-highlight"></div>
        `;
        document.body.appendChild(this.overlay);
        
        // Add click handler to close tour
        this.overlay.querySelector('.tour-backdrop').addEventListener('click', () => {
            this.endTour();
        });
    }
    
    updateOverlay(target, step) {
        if (!this.overlay) return;
        
        const highlight = this.overlay.querySelector('.tour-highlight');
        
        if (target === document.body || step.position === 'center') {
            highlight.style.display = 'none';
        } else {
            const rect = target.getBoundingClientRect();
            const padding = step.type === 'premium' ? 12 : 8;
            
            highlight.style.display = 'block';
            highlight.style.left = (rect.left - padding) + 'px';
            highlight.style.top = (rect.top - padding) + 'px';
            highlight.style.width = (rect.width + padding * 2) + 'px';
            highlight.style.height = (rect.height + padding * 2) + 'px';
            
            // Add premium glow effect
            if (step.type === 'premium') {
                highlight.classList.add('premium-highlight');
            } else {
                highlight.classList.remove('premium-highlight');
            }
        }
    }
    
    showTooltip(target, step) {
        // Remove existing tooltip
        if (this.tooltip) {
            this.tooltip.remove();
        }
        
        this.tooltip = document.createElement('div');
        this.tooltip.className = `courscribe-tour-tooltip ${step.type || ''}`;
        
        const isPremium = step.type === 'premium';
        const isCompletion = step.type === 'completion';
        const isCenter = step.position === 'center' || target === document.body;
        
        this.tooltip.innerHTML = `
            <div class="tooltip-content">
                ${isPremium ? '<div class="premium-badge"><i class="fas fa-crown"></i> Premium Feature</div>' : ''}
                <h3 class="tooltip-title">${step.title}</h3>
                <p class="tooltip-text">${step.content}</p>
                <div class="tooltip-actions">
                    <div class="progress-indicator">
                        <span class="step-counter">${this.currentStep + 1} of ${this.steps.length}</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${((this.currentStep + 1) / this.steps.length) * 100}%"></div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        ${this.currentStep > 0 ? '<button class="btn-prev" onclick="courscribeTour.prevStep()"><i class="fas fa-arrow-left"></i> Previous</button>' : ''}
                        ${!isCompletion ? '<button class="btn-skip" onclick="courscribeTour.endTour()">Skip Tour</button>' : ''}
                        <button class="btn-next ${isPremium ? 'premium' : ''}" onclick="courscribeTour.${isCompletion ? 'completeTour' : 'nextStep'}()">
                            ${isCompletion ? '<i class="fas fa-check"></i> Finish' : (step.showNext === false ? 'Got it!' : 'Next <i class="fas fa-arrow-right"></i>')}
                        </button>
                    </div>
                </div>
            </div>
            <div class="tooltip-arrow"></div>
        `;
        
        document.body.appendChild(this.tooltip);
        
        // Position tooltip
        this.positionTooltip(target, step);
        
        // Animate in
        setTimeout(() => {
            this.tooltip.classList.add('show');
        }, 10);
    }
    
    positionTooltip(target, step) {
        const tooltip = this.tooltip;
        const arrow = tooltip.querySelector('.tooltip-arrow');
        
        if (step.position === 'center' || target === document.body) {
            tooltip.style.position = 'fixed';
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
            arrow.style.display = 'none';
            return;
        }
        
        const rect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const arrowSize = 10;
        
        let top, left;
        
        switch (step.position) {
            case 'top':
                top = rect.top - tooltipRect.height - arrowSize;
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                arrow.className = 'tooltip-arrow arrow-bottom';
                break;
            case 'bottom':
                top = rect.bottom + arrowSize;
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                arrow.className = 'tooltip-arrow arrow-top';
                break;
            case 'left':
                top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.left - tooltipRect.width - arrowSize;
                arrow.className = 'tooltip-arrow arrow-right';
                break;
            case 'right':
                top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.right + arrowSize;
                arrow.className = 'tooltip-arrow arrow-left';
                break;
        }
        
        // Keep tooltip within viewport
        const margin = 20;
        if (left < margin) left = margin;
        if (left + tooltipRect.width > window.innerWidth - margin) {
            left = window.innerWidth - tooltipRect.width - margin;
        }
        if (top < margin) top = margin;
        if (top + tooltipRect.height > window.innerHeight - margin) {
            top = window.innerHeight - tooltipRect.height - margin;
        }
        
        tooltip.style.position = 'fixed';
        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
        tooltip.style.transform = 'none';
    }
    
    nextStep() {
        this.currentStep++;
        this.showStep(this.currentStep);
        
        // Track step completion
        this.trackEvent('tour_step_completed', {
            step: this.currentStep,
            step_name: this.steps[this.currentStep - 1]?.title
        });
    }
    
    prevStep() {
        if (this.currentStep > 0) {
            this.currentStep--;
            this.showStep(this.currentStep);
        }
    }
    
    completeTour() {
        this.trackEvent('tour_completed', {
            user_tier: this.userTier,
            total_steps: this.steps.length
        });
        
        // Show completion animation
        this.showCompletionAnimation();
        
        setTimeout(() => {
            this.endTour();
        }, 3000);
    }
    
    showCompletionAnimation() {
        const completion = document.createElement('div');
        completion.className = 'tour-completion-animation';
        completion.innerHTML = `
            <div class="completion-content">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>🎉 Tour Complete!</h2>
                <p>You're ready to create amazing educational content with CourScribe.</p>
            </div>
        `;
        
        document.body.appendChild(completion);
        
        setTimeout(() => {
            completion.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            completion.remove();
        }, 2800);
    }
    
    endTour() {
        this.isActive = false;
        
        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }
        
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
        
        // Remove tour parameter from URL
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('tour');
            url.searchParams.delete('create_curriculum');
            window.history.replaceState({}, document.title, url.toString());
        }
        
        this.trackEvent('tour_ended', {
            completed_steps: this.currentStep,
            total_steps: this.steps.length
        });
    }
    
    highlightElement(element) {
        element.classList.add('courscribe-highlight-element');
        setTimeout(() => {
            element.classList.remove('courscribe-highlight-element');
        }, 3000);
    }
    
    scrollToElement(element) {
        const rect = element.getBoundingClientRect();
        const elementTop = rect.top + window.pageYOffset;
        const elementHeight = rect.height;
        const windowHeight = window.innerHeight;
        const offset = (windowHeight - elementHeight) / 2;
        
        window.scrollTo({
            top: elementTop - offset,
            behavior: 'smooth'
        });
    }
    
    trackEvent(eventName, properties = {}) {
        // Google Analytics tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, properties);
        }
        
        // Custom analytics
        if (typeof courscribeAnalytics !== 'undefined') {
            courscribeAnalytics.track(eventName, properties);
        }
        
        console.log('CourScribe Tour Event:', eventName, properties);
    }
    
    bindEvents() {
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (!this.isActive) return;
            
            switch (e.key) {
                case 'Escape':
                    this.endTour();
                    break;
                case 'ArrowLeft':
                    if (this.currentStep > 0) this.prevStep();
                    break;
                case 'ArrowRight':
                    this.nextStep();
                    break;
            }
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            if (this.isActive && this.tooltip) {
                const step = this.steps[this.currentStep];
                const target = step.target === 'body' ? document.body : document.querySelector(step.target);
                if (target) {
                    this.updateOverlay(target, step);
                    this.positionTooltip(target, step);
                }
            }
        });
    }
    
    // Public API methods
    setUserTier(tier) {
        this.userTier = tier;
        this.initializeTourSteps();
    }
    
    addCustomStep(step) {
        this.steps.push(step);
    }
    
    restart() {
        this.endTour();
        setTimeout(() => this.startTour(), 500);
    }
}

// Global instance
let courscribeTour;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    courscribeTour = new CourScribePremiumTour();
    
    // Make it globally accessible
    window.courscribeTour = courscribeTour;
});

// CSS Styles
const tourStyles = `
<style>
.courscribe-tour-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    pointer-events: none;
}

.tour-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    pointer-events: all;
    cursor: pointer;
}

.tour-highlight {
    position: absolute;
    background: transparent;
    border: 3px solid #E4B26F;
    border-radius: 8px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
    transition: all 0.3s ease;
    pointer-events: none;
}

.tour-highlight.premium-highlight {
    border-color: #FFD700;
    box-shadow: 
        0 0 0 9999px rgba(0, 0, 0, 0.6),
        0 0 20px rgba(255, 215, 0, 0.6),
        inset 0 0 20px rgba(255, 215, 0, 0.1);
    animation: premiumGlow 2s ease-in-out infinite;
}

@keyframes premiumGlow {
    0%, 100% { box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6), 0 0 20px rgba(255, 215, 0, 0.6); }
    50% { box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6), 0 0 30px rgba(255, 215, 0, 0.8); }
}

.courscribe-tour-tooltip {
    position: fixed;
    background: #2a2a2a;
    border: 1px solid #444;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    max-width: 400px;
    z-index: 10001;
    opacity: 0;
    transform: scale(0.9);
    transition: all 0.3s ease;
    pointer-events: all;
    backdrop-filter: blur(10px);
}

.courscribe-tour-tooltip.show {
    opacity: 1;
    transform: scale(1);
}

.courscribe-tour-tooltip.premium {
    border-color: #FFD700;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 20px rgba(255, 215, 0, 0.2);
}

.courscribe-tour-tooltip.completion {
    background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
    border-color: #4CAF50;
}

.tooltip-content {
    padding: 24px;
}

.premium-badge {
    background: linear-gradient(45deg, #FFD700, #FFA500);
    color: #1a1a1a;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.tooltip-title {
    color: #fff;
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.tooltip-text {
    color: #ccc;
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 20px 0;
}

.tooltip-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.progress-indicator {
    flex: 1;
}

.step-counter {
    color: #888;
    font-size: 12px;
    font-weight: 500;
    display: block;
    margin-bottom: 8px;
}

.progress-bar {
    height: 4px;
    background: #333;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(45deg, #E4B26F, #F8923E);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.action-buttons button {
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-prev {
    background: transparent;
    color: #888;
    border: 1px solid #444;
}

.btn-prev:hover {
    background: #333;
    color: #ccc;
}

.btn-skip {
    background: transparent;
    color: #888;
    border: 1px solid #444;
}

.btn-skip:hover {
    background: #333;
    color: #ccc;
}

.btn-next {
    background: linear-gradient(45deg, #E4B26F, #F8923E);
    color: #1a1a1a;
    font-weight: 600;
}

.btn-next:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(228, 178, 111, 0.3);
}

.btn-next.premium {
    background: linear-gradient(45deg, #FFD700, #FFA500);
}

.tooltip-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border: 10px solid transparent;
}

.arrow-top {
    top: -20px;
    left: 50%;
    margin-left: -10px;
    border-bottom-color: #2a2a2a;
}

.arrow-bottom {
    bottom: -20px;
    left: 50%;
    margin-left: -10px;
    border-top-color: #2a2a2a;
}

.arrow-left {
    left: -20px;
    top: 50%;
    margin-top: -10px;
    border-right-color: #2a2a2a;
}

.arrow-right {
    right: -20px;
    top: 50%;
    margin-top: -10px;
    border-left-color: #2a2a2a;
}

.courscribe-highlight-element {
    animation: highlightPulse 2s ease-in-out;
    position: relative;
    z-index: 9999;
}

@keyframes highlightPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(228, 178, 111, 0.8); }
    50% { box-shadow: 0 0 0 20px rgba(228, 178, 111, 0); }
}

.tour-completion-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10002;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.tour-completion-animation.show {
    opacity: 1;
}

.completion-content {
    text-align: center;
    color: #fff;
}

.success-icon {
    font-size: 80px;
    color: #4CAF50;
    margin-bottom: 20px;
    animation: bounceIn 0.8s ease;
}

.completion-content h2 {
    font-size: 2rem;
    margin-bottom: 10px;
    background: linear-gradient(45deg, #E4B26F, #F8923E);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.completion-content p {
    font-size: 1.1rem;
    color: #ccc;
}

@keyframes bounceIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@media (max-width: 768px) {
    .courscribe-tour-tooltip {
        max-width: 320px;
        margin: 10px;
    }
    
    .tooltip-content {
        padding: 20px;
    }
    
    .tooltip-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .action-buttons {
        width: 100%;
        justify-content: center;
    }
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', tourStyles);