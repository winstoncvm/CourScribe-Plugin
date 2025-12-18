/**
 * Show a styled notification
 * @param {string} message - The message to display
 * @param {string} type - 'success' or 'error'
 * @param {number} [duration=5000] - Duration in ms (0 = persistent)
 */
function showNotification(message, type = 'success', duration = 5000) {
    const container = document.getElementById('courscribe-notifications');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `courscribe-notification ${type}`;
    
    // Icons based on type
    const icon = type === 'success' ? 
        '<svg class="icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' :
        '<svg class="icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
    
    notification.innerHTML = `
        ${icon}
        <div class="content">${message}</div>
        <div class="close">&times;</div>
        ${duration > 0 ? '<div class="progress-bar"></div>' : ''}
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Auto-remove if duration is set
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.add('hide');
            notification.addEventListener('animationend', () => notification.remove());
        }, duration);
    }
    
    // Manual close
    notification.querySelector('.close').addEventListener('click', () => {
        notification.classList.add('hide');
        notification.addEventListener('animationend', () => notification.remove());
    });
}

// Make available globally
window.CourScribe = window.CourScribe || {};
window.CourScribe.Notifications = { show: showNotification };