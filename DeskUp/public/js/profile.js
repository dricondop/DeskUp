/**
 * DeskUp User Profile JavaScript
 * Handles frontend functionality with real database data
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeProfilePage();
});

/**
 * Initialize the profile page with animations
 */
function initializeProfilePage() {
    animateCharts();
    setupEventListeners();
}

/**
 * Animate charts on page load
 */
function animateCharts() {
    const charts = document.querySelectorAll('.score-circle');
    
    setTimeout(() => {
        charts.forEach(chart => {
            chart.style.transform = 'scale(1.05)';
            setTimeout(() => {
                chart.style.transform = 'scale(1)';
            }, 300);
        });
    }, 300);
}

/**
 * Setup event listeners for interactive elements
 */
function setupEventListeners() {
    // Notification button
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', handleNotificationClick);
    }
    
    // Settings button
    const settingsBtn = document.querySelector('.settings-btn');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', handleSettingsClick);
    }
    
    // Stats button
    const statsButton = document.querySelector('.stats-button');
    if (statsButton) {
        statsButton.addEventListener('click', handleStatsClick);
    }
    
    // Height button
    const heightButton = document.querySelector('.height-button');
    if (heightButton) {
        heightButton.addEventListener('click', handleHeightClick);
    }
}

/**
 * Handle notification button click
 */
function handleNotificationClick(event) {
    console.log('Notifications clicked');
    // Logic for notifications (to do)
    showNotification('No new notifications');
}

/**
 * Handle settings button click
 */
function handleSettingsClick(event) {
    console.log('Settings clicked');
    window.location.href = '/settings'; // there is settings?
}

/**
 * Handle stats button click
 */
function handleStatsClick(event) {
    console.log('View complete statistics clicked');
    // Health page
    window.location.href = '/health';
}

/**
 * Handle height button click
 */
function handleHeightClick(event) {
    console.log('Configure ideal height clicked');
    // Ideal height cofig (will be done soon)
    showNotification('Height configuration coming soon');
}

/**
 * Show temporary notification
 */
function showNotification(message) {
    // Notifications (basic, to be polished)
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #07325F;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: 'Inter', sans-serif;
        font-size: 14px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // 3 sec delay
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Export functions for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeProfilePage
    };
}