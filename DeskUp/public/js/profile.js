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

// Profile page health insights integration
'use strict';

// Health insights integration for profile page
class ProfileHealthInsights {
    constructor() {
        this.chartInstance = null;
        this.userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.init();
    }

    async init() {
        await this.fetchLiveStatus();
        await this.fetchTodayStats();
        this.createTimePercentageChart();
        
        // Refresh live status every 30 seconds
        setInterval(() => this.fetchLiveStatus(), 30000);
    }

    async fetchLiveStatus() {
        try {
            const response = await fetch('/api/health-live-status', {
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch live status');
            
            const data = await response.json();
            
            // Update live status elements
            const modeEl = document.getElementById('profile-live-mode');
            const heightEl = document.getElementById('profile-live-height');
            const lastEl = document.getElementById('profile-last-adjusted');
            
            if (modeEl) modeEl.textContent = data.mode || 'Unknown';
            if (heightEl) heightEl.textContent = `${data.height_cm || 0} cm`;
            if (lastEl) lastEl.textContent = `Last adjusted: ${data.last_adjusted || 'Never'}`;
            
        } catch (error) {
            console.error('Error fetching live status:', error);
            this.updateStatusError();
        }
    }

    async fetchTodayStats() {
        try {
            const response = await fetch('/api/health-stats?range=today', {
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch stats');
            
            const data = await response.json();
            this.updateStats(data);
            this.updateChartData(data);
            
        } catch (error) {
            console.error('Error fetching today stats:', error);
            this.updateStatsWithDefaults();
        }
    }

    updateStats(data) {
        // Update posture score
        const postureScore = Math.max(0, Math.min(100, data.standing_pct || 0));
        const scoreEl = document.getElementById('profile-posture-score');
        const bar = document.getElementById('profile-posture-score-bar');
        
        if (scoreEl) scoreEl.textContent = `${postureScore} / 100`;
        if (bar) bar.style.width = `${postureScore}%`;
        
        // Update quick stats
        const activeEl = document.getElementById('profile-active-hours');
        const breaksEl = document.getElementById('profile-breaks');
        const caloriesEl = document.getElementById('profile-calories');
        
        if (activeEl) activeEl.textContent = data.active_hours?.toFixed(1) || '0';
        if (breaksEl) breaksEl.textContent = data.breaks_per_day || '0';
        if (caloriesEl) caloriesEl.textContent = `${data.calories_per_day || '0'} kcal`;
    }

    updateChartData(data) {
        if (this.chartInstance) {
            this.chartInstance.data.datasets[0].data = [
                data.sitting_pct || 65,
                data.standing_pct || 35
            ];
            this.chartInstance.update();
        }
    }

    createTimePercentageChart() {
        const ctx = document.getElementById('timePercentageChartProfile')?.getContext('2d');
        if (!ctx) return;
        
        const palette = { 
            primary: '#3A506B', 
            accent: '#00A8A8' 
        };
        
        this.chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Sitting', 'Standing'],
                datasets: [{
                    data: [65, 35], // Default values
                    backgroundColor: [palette.primary, palette.accent],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    }

    updateStatusError() {
        const modeEl = document.getElementById('profile-live-mode');
        const heightEl = document.getElementById('profile-live-height');
        const lastEl = document.getElementById('profile-last-adjusted');
        
        if (modeEl) modeEl.textContent = 'Error';
        if (heightEl) heightEl.textContent = '— cm';
        if (lastEl) lastEl.textContent = 'Failed to load';
    }

    updateStatsWithDefaults() {
        const scoreEl = document.getElementById('profile-posture-score');
        const bar = document.getElementById('profile-posture-score-bar');
        
        if (scoreEl) scoreEl.textContent = '— / 100';
        if (bar) bar.style.width = '0%';
        
        const activeEl = document.getElementById('profile-active-hours');
        const breaksEl = document.getElementById('profile-breaks');
        const caloriesEl = document.getElementById('profile-calories');
        
        if (activeEl) activeEl.textContent = '—';
        if (breaksEl) breaksEl.textContent = '—';
        if (caloriesEl) caloriesEl.textContent = '— kcal';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProfileHealthInsights();
});