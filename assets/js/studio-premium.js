/**
 * CourScribe Premium Studio Interface
 * Interactive JavaScript functionality for the modern studio interface
 */

class CourScribeStudio {
    constructor() {
        this.currentSection = 'dashboard';
        this.isLoading = false;
        this.charts = {};
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeCharts();
        this.loadDashboardData();
        this.setupAnimations();
        
        // Show the studio interface after initialization
        this.hideLoader();
        
        console.log('CourScribe Premium Studio initialized');
    }
    
    bindEvents() {
        // Navigation events
        this.bindNavigationEvents();
        
        // Modal events
        this.bindModalEvents();
        
        // Form events
        this.bindFormEvents();
        
        // Quick action events
        this.bindQuickActionEvents();
        
        // Keyboard shortcuts
        this.bindKeyboardShortcuts();
        
        // Window events
        this.bindWindowEvents();
        
        // Drag and drop events
        this.bindDragDropEvents();
        
        // Archived curriculums events
        this.bindArchivedEvents();
        
        // Premium modal events
        this.bindPremiumModalEvents();
        
        // Activity filter and pagination events
        this.bindActivityEvents();
    }
    
    bindNavigationEvents() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                const section = item.dataset.section;
                if (section && section !== this.currentSection) {
                    this.navigateToSection(section);
                }
            });
        });
        
        // User menu toggle
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleUserMenu();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                this.closeUserMenu();
            });
        }
    }
    
    bindModalEvents() {
        const modalOverlay = document.getElementById('modal-overlay');
        
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    this.closeModal();
                }
            });
        }
        
        // Modal close button
        const modalClose = document.querySelector('.modal-close');
        if (modalClose) {
            modalClose.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }
    
    bindFormEvents() {
        // Studio info form
        const studioInfoForm = document.getElementById('studio-info-form');
        if (studioInfoForm) {
            studioInfoForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveStudioInfo();
            });
        }
        
        // Invite form
        const inviteForm = document.getElementById('invite-form');
        if (inviteForm) {
            inviteForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendInvitation();
            });
        }
    }
    
    bindQuickActionEvents() {
        // Create curriculum button
        const createCurriculumBtn = document.getElementById('create-curriculum-btn');
        if (createCurriculumBtn) {
            createCurriculumBtn.addEventListener('click', () => {
                this.createNewCurriculum();
            });
        }
    }
    
    bindKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Only handle shortcuts when not in form inputs
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            // Cmd/Ctrl + key shortcuts
            if (e.metaKey || e.ctrlKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        this.navigateToSection('dashboard');
                        break;
                    case '2':
                        e.preventDefault();
                        this.navigateToSection('curriculums');
                        break;
                    case '3':
                        e.preventDefault();
                        this.navigateToSection('team');
                        break;
                    case '4':
                        e.preventDefault();
                        this.navigateToSection('analytics');
                        break;
                    case '5':
                        e.preventDefault();
                        this.navigateToSection('settings');
                        break;
                    case 'n':
                        e.preventDefault();
                        this.createNewCurriculum();
                        break;
                    case 'i':
                        e.preventDefault();
                        this.inviteCollaborator();
                        break;
                }
            }
        });
    }
    
    bindWindowEvents() {
        // Handle window resize
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));
        
        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refreshData();
            }
        });
    }
    
    bindDragDropEvents() {
        // Initialize drag and drop functionality for curriculum items
        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('curriculum-item')) {
                e.target.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', e.target.outerHTML);
                e.dataTransfer.setData('text/plain', e.target.getAttribute('data-curriculum-id'));
            }
        });
        
        document.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('curriculum-item')) {
                e.target.classList.remove('dragging');
                document.querySelectorAll('.drag-over').forEach(el => {
                    el.classList.remove('drag-over');
                });
            }
        });
        
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            const dragging = document.querySelector('.dragging');
            const container = e.target.closest('.curriculums-grid, .archived-grid');
            
            if (container && dragging) {
                const afterElement = this.getDragAfterElement(container, e.clientY);
                if (afterElement == null) {
                    container.appendChild(dragging);
                } else {
                    container.insertBefore(dragging, afterElement);
                }
            }
        });
        
        document.addEventListener('drop', (e) => {
            e.preventDefault();
            const curriculumId = e.dataTransfer.getData('text/plain');
            if (curriculumId) {
                this.updateCurriculumOrder();
                this.logActivity('curriculum_reordered', `Curriculum reordered via drag and drop`);
            }
        });
    }
    
    bindArchivedEvents() {
        // Bind to the global function for archived curriculums toggle
        window.toggleArchivedCurriculums = () => {
            this.toggleArchivedCurriculums();
        };
    }
    
    bindPremiumModalEvents() {
        // Archive confirmation
        const archiveBtn = document.getElementById('confirm-archive-btn');
        if (archiveBtn) {
            archiveBtn.addEventListener('click', () => {
                this.confirmArchiveCurriculum();
            });
        }
        
        // Delete confirmation
        const deleteBtn = document.getElementById('confirm-delete-btn');
        const deleteInput = document.getElementById('delete-confirmation-text');
        
        if (deleteBtn && deleteInput) {
            // Enable/disable delete button based on confirmation text
            deleteInput.addEventListener('input', (e) => {
                const isValid = e.target.value.toUpperCase() === 'DELETE';
                deleteBtn.disabled = !isValid;
            });
            
            deleteBtn.addEventListener('click', () => {
                this.confirmDeleteCurriculum();
            });
        }
        
        // Global modal functions for curriculum manager integration
        window.showArchiveModal = (curriculumId, curriculumTitle) => {
            this.showArchiveModal(curriculumId, curriculumTitle);
        };
        
        window.showDeleteModal = (curriculumId, curriculumTitle) => {
            this.showDeleteModal(curriculumId, curriculumTitle);
        };
    }
    
    bindActivityEvents() {
        // Activity filter
        const filterSelect = document.getElementById('activity-filter-select');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.filterActivity(e.target.value);
            });
        }
        
        // Activity pagination
        const prevBtn = document.getElementById('activity-prev-btn');
        const nextBtn = document.getElementById('activity-next-btn');
        const viewAllBtn = document.getElementById('view-all-activity');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.previousActivityPage();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.nextActivityPage();
            });
        }
        
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', () => {
                this.viewAllActivity();
            });
        }
        
        // Initialize activity pagination
        this.currentActivityPage = 1;
        this.activityPerPage = 5;
        this.currentActivityFilter = 'all';
    }
    
    navigateToSection(sectionName) {
        // Update navigation state
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.dataset.section === sectionName) {
                item.classList.add('active');
            }
        });
        
        // Update section visibility
        const sections = document.querySelectorAll('.studio-section');
        sections.forEach(section => {
            section.classList.remove('active');
        });
        
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = sectionName;
            
            // Load section-specific data
            this.loadSectionData(sectionName);
            
            // Update URL without page reload
            this.updateURL(sectionName);
            
            // Track navigation
            this.trackEvent('navigation', sectionName);
        }
    }
    
    loadSectionData(sectionName) {
        switch(sectionName) {
            case 'curriculums':
                this.loadCurriculums();
                break;
            case 'team':
                this.loadTeamMembers();
                break;
            case 'analytics':
                this.loadAnalytics();
                break;
            case 'settings':
                this.loadSettings();
                break;
        }
    }
    
    loadDashboardData() {
        this.showLoader();
        
        // Load statistics
        this.loadStatistics()
            .then(() => this.loadRecentActivity())
            .then(() => this.loadProgressChart())
            .finally(() => this.hideLoader());
    }
    
    async loadStatistics() {
        try {
            const response = await this.makeAjaxRequest('get_studio_stats', {});
            
            if (response.success) {
                this.updateStatistics(response.data);
            }
        } catch (error) {
            console.error('Failed to load statistics:', error);
            this.showNotification('Failed to load statistics', 'error');
        }
    }
    
    updateStatistics(data) {
        const statElements = {
            curriculum: document.querySelector('.stat-card .stat-content .stat-number'),
            course: document.querySelectorAll('.stat-card .stat-content .stat-number')[1],
            module: document.querySelectorAll('.stat-card .stat-content .stat-number')[2],
            lesson: document.querySelectorAll('.stat-card .stat-content .stat-number')[3]
        };
        
        // Animate number changes
        if (statElements.curriculum) {
            this.animateNumber(statElements.curriculum, data.total_curriculums || 0);
        }
        if (statElements.course) {
            this.animateNumber(statElements.course, data.total_courses || 0);
        }
        if (statElements.module) {
            this.animateNumber(statElements.module, data.total_modules || 0);
        }
        if (statElements.lesson) {
            this.animateNumber(statElements.lesson, data.total_lessons || 0);
        }
    }
    
    async loadRecentActivity() {
        try {
            const response = await this.makeAjaxRequest('get_recent_activity', {});
            
            if (response.success) {
                this.updateActivityFeed(response.data);
            }
        } catch (error) {
            console.error('Failed to load recent activity:', error);
        }
    }
    
    updateActivityFeed(activities) {
        const feed = document.querySelector('#activity-feed');
        if (!feed) return;
        
        if (!activities || !activities.length) {
            feed.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h4>No Recent Activity</h4>
                    <p>Activity will appear here as your team creates and updates content.</p>
                </div>
            `;
            return;
        }
        
        feed.innerHTML = activities.map(activity => `
            <div class="activity-item" style="animation: fadeInUp 0.5s ease-out">
                <div class="activity-icon ${activity.type}">
                    <i class="fas fa-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">
                        <strong>${this.escapeHtml(activity.user_name)}</strong> 
                        ${this.escapeHtml(activity.description)}
                    </div>
                    <div class="activity-time">${this.formatTimeAgo(activity.timestamp)}</div>
                </div>
            </div>
        `).join('');
    }
    
    initializeCharts() {
        this.initProgressChart();
        this.initContentChart();
        this.initActivityChart();
    }
    
    initProgressChart() {
        const canvas = document.getElementById('progress-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Create a simple donut chart
        this.charts.progress = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending'],
                datasets: [{
                    data: [75, 20, 5],
                    backgroundColor: [
                        '#10B981',
                        '#F59E0B', 
                        '#6B7280'
                    ],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    async initContentChart() {
        const canvas = document.getElementById('content-chart');
        if (!canvas) return;
        
        try {
            const response = await this.makeAjaxRequest('get_content_chart_data', {});
            
            const ctx = canvas.getContext('2d');
            const chartData = response.success ? response.data : this.getEmptyChartData();
            
            this.charts.content = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels || this.getLast30Days(),
                    datasets: [{
                        label: 'Content Created',
                        data: chartData.data || new Array(30).fill(0),
                        borderColor: '#E4B26F',
                        backgroundColor: 'rgba(228, 178, 111, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#E4B26F',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#94A3B8'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#94A3B8',
                                beginAtZero: true
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to load content chart data:', error);
            // Show empty chart on error
            this.initEmptyContentChart(canvas);
        }
    }
    
    async initActivityChart() {
        const canvas = document.getElementById('activity-chart');
        if (!canvas) return;
        
        try {
            const response = await this.makeAjaxRequest('get_activity_chart_data', {});
            
            const ctx = canvas.getContext('2d');
            const chartData = response.success ? response.data : this.getEmptyActivityData();
            
            this.charts.activity = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Team Activity',
                        data: chartData.data || new Array(7).fill(0),
                        backgroundColor: 'rgba(228, 178, 111, 0.8)',
                        borderColor: '#E4B26F',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94A3B8'
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#94A3B8',
                                beginAtZero: true
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to load activity chart data:', error);
            // Show empty chart on error
            this.initEmptyActivityChart(canvas);
        }
    }
    
    async loadCurriculums() {
        this.showSectionLoader('curriculums');
        
        try {
            const response = await this.makeAjaxRequest('get_curriculums', {});
            
            if (response.success) {
                this.renderCurriculums(response.data);
            }
        } catch (error) {
            console.error('Failed to load curriculums:', error);
            this.showNotification('Failed to load curriculums', 'error');
        } finally {
            this.hideSectionLoader('curriculums');
        }
    }
    
    renderCurriculums(curriculums) {
        const grid = document.querySelector('.curriculums-grid');
        if (!grid) return;
        
        if (!curriculums.length) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No curriculums yet</h3>
                    <p>Create your first curriculum to get started.</p>
                    <button class="btn btn-primary" onclick="studioApp.createNewCurriculum()">
                        <i class="fas fa-plus"></i>
                        Create First Curriculum
                    </button>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = curriculums.map(curriculum => `
            <div class="curriculum-card" data-id="${curriculum.id}">
                <div class="curriculum-header">
                    <h3>${this.escapeHtml(curriculum.title)}</h3>
                    <div class="curriculum-status ${curriculum.status}">
                        ${this.formatStatus(curriculum.status)}
                    </div>
                </div>
                <div class="curriculum-stats">
                    <div class="stat">
                        <i class="fas fa-graduation-cap"></i>
                        <span>${curriculum.course_count} Courses</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-layer-group"></i>
                        <span>${curriculum.module_count} Modules</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-play-circle"></i>
                        <span>${curriculum.lesson_count} Lessons</span>
                    </div>
                </div>
                <div class="curriculum-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${curriculum.progress}%"></div>
                    </div>
                    <span class="progress-text">${curriculum.progress}% Complete</span>
                </div>
                <div class="curriculum-actions">
                    <button class="btn-icon" title="Edit" onclick="studioApp.editCurriculum(${curriculum.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" title="View" onclick="studioApp.viewCurriculum(${curriculum.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" title="Export" onclick="studioApp.exportCurriculum(${curriculum.id})">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    async loadTeamMembers() {
        this.showSectionLoader('team');
        
        try {
            const response = await this.makeAjaxRequest('get_team_members', {});
            
            if (response.success) {
                this.renderTeamMembers(response.data);
            }
        } catch (error) {
            console.error('Failed to load team members:', error);
            this.showNotification('Failed to load team members', 'error');
        } finally {
            this.hideSectionLoader('team');
        }
    }
    
    renderTeamMembers(members) {
        const grid = document.querySelector('.team-grid');
        if (!grid) return;
        
        if (!members.length) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No team members yet</h3>
                    <p>Invite collaborators to start building together.</p>
                    <button class="btn btn-primary" onclick="studioApp.inviteCollaborator()">
                        <i class="fas fa-user-plus"></i>
                        Invite Your First Member
                    </button>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = members.map(member => `
            <div class="team-member-card" data-id="${member.id}">
                <div class="member-avatar">
                    ${member.avatar ? `<img src="${member.avatar}" alt="${member.name}">` : '<i class="fas fa-user"></i>'}
                </div>
                <div class="member-info">
                    <h3 class="member-name">${this.escapeHtml(member.name)}</h3>
                    <div class="member-role">${this.escapeHtml(member.role)}</div>
                    <div class="member-email">${this.escapeHtml(member.email)}</div>
                </div>
                <div class="member-status">
                    <div class="status-dot ${member.status}"></div>
                    <span>${this.formatStatus(member.status)}</span>
                </div>
                <div class="member-actions">
                    <button class="btn-icon" title="Edit Permissions" onclick="studioApp.editMemberPermissions(${member.id})">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn-icon" title="Message" onclick="studioApp.messageMember(${member.id})">
                        <i class="fas fa-envelope"></i>
                    </button>
                    <button class="btn-icon danger" title="Remove" onclick="studioApp.removeMember(${member.id})">
                        <i class="fas fa-user-times"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    // Modal Methods
    showModal(modalId) {
        const overlay = document.getElementById('modal-overlay');
        const modal = document.getElementById(modalId);
        
        if (overlay && modal) {
            overlay.classList.add('show');
            modal.style.display = 'block';
            
            // Focus first input
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
        }
    }
    
    closeModal() {
        const overlay = document.getElementById('modal-overlay');
        
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                const modals = overlay.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
            }, 300);
        }
    }
    
    toggleUserMenu() {
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    }
    
    closeUserMenu() {
        const dropdown = document.getElementById('user-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }
    
    // Quick Actions
    createNewCurriculum() {
        this.trackEvent('action', 'create_curriculum');
        window.location.href = '/wp-admin/post-new.php?post_type=crscribe_curriculum';
    }
    
    inviteCollaborator() {
        this.showModal('invite-modal');
        this.trackEvent('action', 'invite_collaborator_modal');
    }
    
    exportContent() {
        this.showNotification('Export feature coming soon!', 'info');
        this.trackEvent('action', 'export_content');
    }
    
    viewAnalytics() {
        this.navigateToSection('analytics');
        this.trackEvent('action', 'view_analytics');
    }
    
    upgradePlan() {
        this.trackEvent('action', 'upgrade_plan');
        window.open('/pricing', '_blank');
    }
    
    // Form Methods
    async saveStudioInfo() {
        const form = document.getElementById('studio-info-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        this.showLoader();
        
        try {
            const response = await this.makeAjaxRequest('save_studio_info', data);
            
            if (response.success) {
                this.showNotification('Studio information saved successfully!', 'success');
            } else {
                this.showNotification(response.data.message || 'Failed to save studio information', 'error');
            }
        } catch (error) {
            console.error('Failed to save studio info:', error);
            this.showNotification('Failed to save studio information', 'error');
        } finally {
            this.hideLoader();
        }
    }
    
    async sendInvitation() {
        const form = document.getElementById('invite-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        if (!data.invite_email) {
            this.showNotification('Please enter an email address', 'error');
            return;
        }
        
        this.showLoader();
        
        try {
            const response = await this.makeAjaxRequest('send_invitation', data);
            
            if (response.success) {
                this.showNotification('Invitation sent successfully!', 'success');
                this.closeModal();
                form.reset();
                
                // Refresh team section if currently viewing
                if (this.currentSection === 'team') {
                    this.loadTeamMembers();
                }
            } else {
                this.showNotification(response.data.message || 'Failed to send invitation', 'error');
            }
        } catch (error) {
            console.error('Failed to send invitation:', error);
            this.showNotification('Failed to send invitation', 'error');
        } finally {
            this.hideLoader();
        }
    }
    
    // Utility Methods
    async makeAjaxRequest(action, data = {}) {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: `courscribe_${action}`,
                nonce: courscribeNonce,
                ...data
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    showLoader() {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.style.display = 'flex';
        }
    }
    
    hideLoader() {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.style.display = 'none';
        }
    }
    
    showSectionLoader(sectionName) {
        const section = document.getElementById(`${sectionName}-section`);
        if (section) {
            section.style.opacity = '0.5';
            section.style.pointerEvents = 'none';
        }
    }
    
    hideSectionLoader(sectionName) {
        const section = document.getElementById(`${sectionName}-section`);
        if (section) {
            section.style.opacity = '1';
            section.style.pointerEvents = 'auto';
        }
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${this.escapeHtml(message)}</span>
            </div>
            <button class="notification-close">&times;</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => this.hideNotification(notification), 5000);
        
        // Manual close
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.hideNotification(notification));
    }
    
    hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    animateNumber(element, targetValue, duration = 1000) {
        const startValue = parseInt(element.textContent) || 0;
        const increment = (targetValue - startValue) / (duration / 16);
        let currentValue = startValue;
        
        const animate = () => {
            currentValue += increment;
            
            if ((increment > 0 && currentValue >= targetValue) || 
                (increment < 0 && currentValue <= targetValue)) {
                element.textContent = targetValue;
                return;
            }
            
            element.textContent = Math.round(currentValue);
            requestAnimationFrame(animate);
        };
        
        animate();
    }
    
    setupAnimations() {
        // Intersection Observer for scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        // Observe elements for animation
        document.querySelectorAll('.stat-card, .dashboard-card, .curriculum-card, .team-member-card').forEach(el => {
            observer.observe(el);
        });
    }
    
    handleResize() {
        // Resize charts if they exist
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.resize) {
                chart.resize();
            }
        });
    }
    
    refreshData() {
        if (this.currentSection === 'dashboard') {
            this.loadDashboardData();
        } else {
            this.loadSectionData(this.currentSection);
        }
    }
    
    updateURL(section) {
        const url = new URL(window.location);
        url.searchParams.set('section', section);
        window.history.replaceState(null, '', url);
    }
    
    trackEvent(category, action, label = '') {
        // Analytics tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                event_category: category,
                event_label: label
            });
        }
        
        console.log(`Track: ${category}/${action}${label ? '/' + label : ''}`);
    }
    
    // Helper Methods
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        return `${Math.floor(diffInSeconds / 86400)} days ago`;
    }
    
    formatStatus(status) {
        return status.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    getActivityIcon(type) {
        const icons = {
            create: 'plus',
            edit: 'edit',
            delete: 'trash',
            collaborate: 'users',
            comment: 'comment'
        };
        return icons[type] || 'circle';
    }
    
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    getLast30Days() {
        const days = [];
        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }
        return days;
    }
    
    getEmptyChartData() {
        return {
            labels: this.getLast30Days(),
            data: new Array(30).fill(0)
        };
    }
    
    getEmptyActivityData() {
        return {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            data: new Array(7).fill(0)
        };
    }
    
    initEmptyContentChart(canvas) {
        const ctx = canvas.getContext('2d');
        this.charts.content = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.getLast30Days(),
                datasets: [{
                    label: 'Content Created',
                    data: new Array(30).fill(0),
                    borderColor: '#E4B26F',
                    backgroundColor: 'rgba(228, 178, 111, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#94A3B8' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#94A3B8', beginAtZero: true } }
                }
            }
        });
    }
    
    initEmptyActivityChart(canvas) {
        const ctx = canvas.getContext('2d');
        this.charts.activity = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Team Activity',
                    data: new Array(7).fill(0),
                    backgroundColor: 'rgba(228, 178, 111, 0.8)',
                    borderColor: '#E4B26F',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94A3B8' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#94A3B8', beginAtZero: true } }
                }
            }
        });
    }
    
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
    
    // Drag and Drop Helper Methods
    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.curriculum-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    async updateCurriculumOrder() {
        const container = document.querySelector('.curriculums-grid');
        if (!container) return;
        
        const curriculumItems = [...container.querySelectorAll('.curriculum-item')];
        const orderedIds = curriculumItems.map(item => item.getAttribute('data-curriculum-id')).filter(id => id);
        
        if (orderedIds.length === 0) return;
        
        try {
            const response = await this.makeAjaxRequest('update_curriculum_order', {
                curriculum_ids: orderedIds
            });
            
            if (response.success) {
                this.showNotification('Curriculum order updated successfully!', 'success');
                this.logActivity('curriculum_reorder', 'Curriculums reordered via drag and drop');
            } else {
                this.showNotification('Failed to update curriculum order', 'error');
            }
        } catch (error) {
            console.error('Failed to update curriculum order:', error);
            this.showNotification('Failed to update curriculum order', 'error');
        }
    }
    
    async toggleArchivedCurriculums() {
        const content = document.getElementById('archived-curriculums-content');
        const icon = document.getElementById('archived-toggle-icon');
        const grid = document.getElementById('archived-curriculums-grid');
        
        if (!content || !icon) return;
        
        const isVisible = content.style.display !== 'none';
        
        if (isVisible) {
            // Hide archived section
            content.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        } else {
            // Show archived section and load data if not loaded yet
            content.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            
            // Load archived curriculums if not already loaded
            if (grid && grid.innerHTML.includes('Loading archived curriculums')) {
                await this.loadArchivedCurriculums();
            }
        }
    }
    
    async loadArchivedCurriculums() {
        const grid = document.getElementById('archived-curriculums-grid');
        const countElement = document.getElementById('archived-count');
        
        if (!grid) return;
        
        try {
            const response = await this.makeAjaxRequest('get_archived_curriculums', {});
            
            if (response.success && response.data) {
                const archivedCurriculums = response.data;
                
                // Update count
                if (countElement) {
                    countElement.textContent = `(${archivedCurriculums.length})`;
                }
                
                if (archivedCurriculums.length === 0) {
                    grid.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-archive"></i>
                            <h4>No Archived Curriculums</h4>
                            <p>Archived curriculums will appear here.</p>
                        </div>
                    `;
                } else {
                    grid.innerHTML = archivedCurriculums.map(curriculum => `
                        <div class="curriculum-card archived" data-id="${curriculum.id}">
                            <div class="curriculum-header">
                                <h3>${this.escapeHtml(curriculum.title)}</h3>
                                <div class="curriculum-status archived">
                                    <i class="fas fa-archive"></i>
                                    Archived
                                </div>
                            </div>
                            <div class="curriculum-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Archived: ${this.formatDate(curriculum.archived_date)}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>${curriculum.course_count} Courses</span>
                                </div>
                            </div>
                            <div class="curriculum-actions">
                                <button class="btn-icon" title="Restore" onclick="studioApp.restoreCurriculum(${curriculum.id})">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button class="btn-icon" title="View" onclick="studioApp.viewCurriculum(${curriculum.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon danger" title="Delete Permanently" onclick="studioApp.deleteCurriculumPermanently(${curriculum.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                grid.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Failed to Load Archived Curriculums</h4>
                        <p>Please try again later.</p>
                        <button class="btn btn-secondary" onclick="studioApp.loadArchivedCurriculums()">
                            <i class="fas fa-refresh"></i>
                            Retry
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Failed to load archived curriculums:', error);
            grid.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Failed to Load Archived Curriculums</h4>
                    <p>An error occurred while loading archived curriculums.</p>
                </div>
            `;
        }
    }
    
    async logActivity(action, description, metadata = {}) {
        try {
            await this.makeAjaxRequest('log_activity', {
                action: action,
                description: description,
                metadata: JSON.stringify(metadata)
            });
        } catch (error) {
            console.error('Failed to log activity:', error);
        }
    }
    
    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    // Additional curriculum management methods
    async restoreCurriculum(curriculumId) {
        if (!confirm('Are you sure you want to restore this curriculum?')) {
            return;
        }
        
        try {
            const response = await this.makeAjaxRequest('restore_curriculum', {
                curriculum_id: curriculumId
            });
            
            if (response.success) {
                this.showNotification('Curriculum restored successfully!', 'success');
                this.logActivity('curriculum_restored', `Curriculum ${curriculumId} restored from archive`);
                
                // Reload archived section
                await this.loadArchivedCurriculums();
                
                // Reload curriculums section if currently viewing
                if (this.currentSection === 'curriculums') {
                    this.loadCurriculums();
                }
            } else {
                this.showNotification('Failed to restore curriculum', 'error');
            }
        } catch (error) {
            console.error('Failed to restore curriculum:', error);
            this.showNotification('Failed to restore curriculum', 'error');
        }
    }
    
    async deleteCurriculumPermanently(curriculumId) {
        if (!confirm('Are you sure you want to permanently delete this curriculum? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await this.makeAjaxRequest('delete_curriculum_permanently', {
                curriculum_id: curriculumId
            });
            
            if (response.success) {
                this.showNotification('Curriculum deleted permanently', 'success');
                this.logActivity('curriculum_deleted_permanently', `Curriculum ${curriculumId} deleted permanently`);
                
                // Reload archived section
                await this.loadArchivedCurriculums();
            } else {
                this.showNotification('Failed to delete curriculum', 'error');
            }
        } catch (error) {
            console.error('Failed to delete curriculum:', error);
            this.showNotification('Failed to delete curriculum', 'error');
        }
    }
    
    async editCurriculum(curriculumId) {
        this.logActivity('curriculum_edit_started', `Started editing curriculum ${curriculumId}`);
        window.location.href = `/wp-admin/post.php?post=${curriculumId}&action=edit`;
    }
    
    async viewCurriculum(curriculumId) {
        this.logActivity('curriculum_viewed', `Viewed curriculum ${curriculumId}`);
        window.open(`/?post_type=crscribe_curriculum&p=${curriculumId}`, '_blank');
    }
    
    async exportCurriculum(curriculumId) {
        this.logActivity('curriculum_export_started', `Started exporting curriculum ${curriculumId}`);
        this.showNotification('Curriculum export feature coming soon!', 'info');
    }
    
    // Premium Modal Methods
    showArchiveModal(curriculumId, curriculumTitle) {
        const modal = document.getElementById('archive-modal');
        const titleSpan = document.getElementById('archive-curriculum-title');
        
        if (modal && titleSpan) {
            titleSpan.textContent = curriculumTitle;
            modal.dataset.curriculumId = curriculumId;
            this.showModal('archive-modal');
        }
    }
    
    showDeleteModal(curriculumId, curriculumTitle) {
        const modal = document.getElementById('delete-modal');
        const titleSpan = document.getElementById('delete-curriculum-title');
        const confirmInput = document.getElementById('delete-confirmation-text');
        const confirmBtn = document.getElementById('confirm-delete-btn');
        
        if (modal && titleSpan && confirmInput && confirmBtn) {
            titleSpan.textContent = curriculumTitle;
            modal.dataset.curriculumId = curriculumId;
            confirmInput.value = '';
            confirmBtn.disabled = true;
            this.showModal('delete-modal');
            
            // Focus the input after modal animation
            setTimeout(() => confirmInput.focus(), 300);
        }
    }
    
    async confirmArchiveCurriculum() {
        const modal = document.getElementById('archive-modal');
        const curriculumId = modal?.dataset.curriculumId;
        
        if (!curriculumId) {
            this.showNotification('Invalid curriculum ID', 'error');
            return;
        }
        
        try {
            const response = await this.makeAjaxRequest('archive_curriculum', {
                curriculum_id: curriculumId
            });
            
            if (response.success) {
                this.showNotification('Curriculum archived successfully!', 'success');
                this.closeModal();
                
                // Refresh curriculums section if currently viewing
                if (this.currentSection === 'curriculums') {
                    this.loadCurriculums();
                }
                
                // Reload archived section if expanded
                const archivedContent = document.getElementById('archived-curriculums-content');
                if (archivedContent && archivedContent.style.display !== 'none') {
                    this.loadArchivedCurriculums();
                }
            } else {
                this.showNotification('Failed to archive curriculum', 'error');
            }
        } catch (error) {
            console.error('Failed to archive curriculum:', error);
            this.showNotification('Failed to archive curriculum', 'error');
        }
    }
    
    async confirmDeleteCurriculum() {
        const modal = document.getElementById('delete-modal');
        const curriculumId = modal?.dataset.curriculumId;
        
        if (!curriculumId) {
            this.showNotification('Invalid curriculum ID', 'error');
            return;
        }
        
        try {
            const response = await this.makeAjaxRequest('delete_curriculum_permanently', {
                curriculum_id: curriculumId
            });
            
            if (response.success) {
                this.showNotification('Curriculum deleted permanently', 'success');
                this.closeModal();
                
                // Refresh curriculums section if currently viewing
                if (this.currentSection === 'curriculums') {
                    this.loadCurriculums();
                }
                
                // Reload archived section if expanded
                const archivedContent = document.getElementById('archived-curriculums-content');
                if (archivedContent && archivedContent.style.display !== 'none') {
                    this.loadArchivedCurriculums();
                }
            } else {
                this.showNotification('Failed to delete curriculum', 'error');
            }
        } catch (error) {
            console.error('Failed to delete curriculum:', error);
            this.showNotification('Failed to delete curriculum', 'error');
        }
    }
    
    // Activity Methods with Filtering and Pagination
    async loadRecentActivity() {
        try {
            const response = await this.makeAjaxRequest('get_recent_activity', {
                filter: this.currentActivityFilter,
                page: this.currentActivityPage,
                per_page: this.activityPerPage
            });
            
            if (response.success) {
                this.updateActivityFeed(response.data.activities, response.data.pagination);
            }
        } catch (error) {
            console.error('Failed to load recent activity:', error);
        }
    }
    
    updateActivityFeed(activities, pagination) {
        const feed = document.querySelector('#activity-feed');
        const paginationDiv = document.querySelector('#activity-pagination');
        
        if (!feed) return;
        
        if (!activities || !activities.length) {
            feed.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h4>No Recent Activity</h4>
                    <p>Activity will appear here as your team creates and updates content.</p>
                </div>
            `;
            if (paginationDiv) paginationDiv.style.display = 'none';
            return;
        }
        
        feed.innerHTML = activities.map(activity => `
            <div class="activity-item" style="animation: fadeInUp 0.5s ease-out">
                <div class="activity-icon ${activity.type}">
                    <i class="fas fa-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">
                        <strong>${this.escapeHtml(activity.user_name)}</strong> 
                        ${this.escapeHtml(activity.description)}
                    </div>
                    <div class="activity-time">${this.formatTimeAgo(activity.timestamp)}</div>
                </div>
            </div>
        `).join('');
        
        // Update pagination
        if (pagination && paginationDiv) {
            this.updateActivityPagination(pagination);
        }
    }
    
    updateActivityPagination(pagination) {
        const paginationDiv = document.querySelector('#activity-pagination');
        const prevBtn = document.getElementById('activity-prev-btn');
        const nextBtn = document.getElementById('activity-next-btn');
        const pageInfo = document.getElementById('activity-page-info');
        
        if (!paginationDiv) return;
        
        paginationDiv.style.display = pagination.total_pages > 1 ? 'flex' : 'none';
        
        if (prevBtn) {
            prevBtn.disabled = pagination.current_page <= 1;
        }
        
        if (nextBtn) {
            nextBtn.disabled = pagination.current_page >= pagination.total_pages;
        }
        
        if (pageInfo) {
            pageInfo.textContent = `Page ${pagination.current_page} of ${pagination.total_pages}`;
        }
    }
    
    filterActivity(filter) {
        this.currentActivityFilter = filter;
        this.currentActivityPage = 1; // Reset to first page
        this.loadRecentActivity();
    }
    
    previousActivityPage() {
        if (this.currentActivityPage > 1) {
            this.currentActivityPage--;
            this.loadRecentActivity();
        }
    }
    
    nextActivityPage() {
        this.currentActivityPage++;
        this.loadRecentActivity();
    }
    
    viewAllActivity() {
        // Navigate to a dedicated activity page or show expanded modal
        this.showNotification('View all activity feature coming soon!', 'info');
        this.trackEvent('action', 'view_all_activity');
    }
}

// Global functions for button clicks
window.createNewCurriculum = () => studioApp.createNewCurriculum();
window.inviteCollaborator = () => studioApp.inviteCollaborator();
window.exportContent = () => studioApp.exportContent();
window.viewAnalytics = () => studioApp.viewAnalytics();
window.upgradePlan = () => studioApp.upgradePlan();
window.closeModal = () => studioApp.closeModal();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if we're on the studio page
    if (document.querySelector('.courscribe-studio-premium')) {
        window.studioApp = new CourScribeStudio();
    }
});

// Add notification styles dynamically
const notificationStyles = `
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--gradient-card);
    border: 1px solid rgba(228, 178, 111, 0.2);
    border-radius: var(--radius-lg);
    padding: var(--space-md);
    box-shadow: var(--shadow-xl);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: var(--space-md);
    max-width: 400px;
    transform: translateX(420px);
    transition: transform 0.3s ease;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid var(--text-success);
}

.notification-error {
    border-left: 4px solid var(--text-danger);
}

.notification-warning {
    border-left: 4px solid var(--text-warning);
}

.notification-info {
    border-left: 4px solid var(--primary-gold);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    flex: 1;
    color: var(--text-primary);
}

.notification-close {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 1.2rem;
    padding: var(--space-xs);
    border-radius: var(--radius-sm);
    transition: all var(--transition-fast);
}

.notification-close:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', notificationStyles);