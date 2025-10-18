/**
 * DeskUp User Profile JavaScript
 * Handles frontend functionality and prepares for database integration
 */

// Database integration: This script will be replaced with dynamic data from backend
document.addEventListener('DOMContentLoaded', function() {
    initializeProfilePage();
});

/**
 * Initialize the profile page with animations and placeholder data
 */
function initializeProfilePage() {
    animateCharts();
    setupEventListeners();
    // loadUserData(); // Uncomment when database is ready
}

/**
 * Animate charts on page load
 */
function animateCharts() {
    const charts = document.querySelectorAll('.chart-fill');
    
    setTimeout(() => {
        charts.forEach(chart => {
            chart.style.transform = 'rotate(360deg)';
        });
    }, 300);
}

/**
 * Setup event listeners for interactive elements
 */
function setupEventListeners() {
    // Add click handlers for notification icon
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', handleNotificationClick);
    }
    
    // Add click handlers for action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', handleActionButtonClick);
    });
}

/**
 * Handle notification icon click
 */
function handleNotificationClick(event) {
    console.log('Notifications clicked - will show notification panel');
    // Database integration: Fetch and display notifications
    // fetchNotifications().then(displayNotifications);
}

/**
 * Handle action button clicks
 */
function handleActionButtonClick(event) {
    const button = event.currentTarget;
    const buttonText = button.textContent;
    
    console.log(`Action button clicked: ${buttonText}`);
    
    // Add loading state to button
    button.classList.add('loading');
    setTimeout(() => {
        button.classList.remove('loading');
    }, 1000);
}

/**
 * Load user data from backend API (for future database integration)
 */
async function loadUserData() {
    try {
        // Database integration: Uncomment when backend API is ready
        /*
        const response = await fetch('/api/user-profile');
        const data = await response.json();
        
        populateUserData(data);
        updateCharts(data);
        */
    } catch (error) {
        console.error('Error loading user data:', error);
        // Fallback to placeholder data
        usePlaceholderData();
    }
}

/**
 * Populate user data from API response
 */
function populateUserData(data) {
    // User information
    setElementContent('[data-user-name]', data.user.name);
    setElementContent('[data-user-email]', data.user.email);
    setElementContent('[data-user-initials]', data.user.initials);
    setElementContent('[data-user-title]', data.user.job_title);
    
    // User stats
    setElementContent('[data-streak-days]', data.stats.consecutive_days);
    setElementContent('[data-avg-health]', `${data.stats.avg_health}%`);
    setElementContent('[data-standing-time]', `${data.stats.standing_time}h`);
    
    // Notification count
    const notificationBadge = document.querySelector('.notification-badge');
    if (notificationBadge && data.notifications) {
        notificationBadge.textContent = data.notifications.unread_count;
    }
}

/**
 * Update charts with real data
 */
function updateCharts(data) {
    // Health chart
    const healthChart = document.querySelector('[data-health-percentage]');
    const healthScore = document.querySelector('[data-health-score]');
    if (healthChart && healthScore) {
        const healthPercentage = data.health.score;
        healthChart.style.background = 
            `conic-gradient(var(--success) 0%, var(--success) ${healthPercentage}%, var(--light-gray) ${healthPercentage}%, var(--light-gray) 100%)`;
        healthScore.textContent = `${healthPercentage}%`;
    }
    
    // Position chart
    const positionChart = document.querySelector('[data-standing-percentage]');
    const standingPercentage = document.querySelector('[data-standing-percentage]');
    const positionBreakdown = document.querySelector('[data-position-breakdown]');
    if (positionChart && standingPercentage && positionBreakdown) {
        const standingPercent = data.position.standing_percentage;
        positionChart.style.background = 
            `conic-gradient(var(--primary) 0%, var(--primary) ${standingPercent}%, var(--light-gray) ${standingPercent}%, var(--light-gray) 100%)`;
        standingPercentage.textContent = `${standingPercent}%`;
        positionBreakdown.textContent = 
            `${100 - standingPercent}% sitting / ${standingPercent}% standing`;
    }
}

/**
 * Use placeholder data (fallback)
 */
function usePlaceholderData() {
    console.log('Using placeholder data - backend not connected');
    // All data attributes are already populated with placeholder values in HTML
}

/**
 * Helper function to safely set element content
 */
function setElementContent(selector, content) {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = content;
    }
}

/**
 * Fetch notifications from backend (for future implementation)
 */
async function fetchNotifications() {
    // Database integration: Implement when notifications API is ready
    /*
    const response = await fetch('/api/notifications');
    return await response.json();
    */
}

/**
 * Display notifications in UI (for future implementation)
 */
function displayNotifications(notifications) {
    // Database integration: Implement notification display logic
    console.log('Displaying notifications:', notifications);
}

// Export functions for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeProfilePage,
        loadUserData,
        populateUserData
    };
}