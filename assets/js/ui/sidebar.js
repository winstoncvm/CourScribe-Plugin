const sidebar = document.getElementById('sidebar');
const toggleSidebar = document.getElementById('toggleSidebar');
const mobileMenuToggle = document.getElementById('mobileMenuToggle');

// Toggle sidebar
toggleSidebar.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    const icon = this.querySelector('i');
    if (sidebar.classList.contains('collapsed')) {
        toggleSidebar.classList.add('collapsed');
        
    } else {
        toggleSidebar.classList.remove('collapsed');
    }
});
// Check saved state on load
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        toggleSidebar.classList.add('collapsed');
    }
});

// Mobile menu toggle
mobileMenuToggle.addEventListener('click', function() {
    sidebar.classList.toggle('active');
});