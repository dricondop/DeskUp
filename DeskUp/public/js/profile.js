/**
 * DeskUp User Profile JavaScript
 * Handles frontend functionality with real database data
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeProfilePage();
});

function initializeProfilePage() {
    animateCharts();
    setupEventListeners();
}

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

function setupEventListeners() {
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', handleNotificationClick);
    }
    
    const settingsBtn = document.querySelector('.settings-btn');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', handleSettingsClick);
    }
    
    const statsButton = document.querySelector('.stats-button');
    if (statsButton) {
        statsButton.addEventListener('click', handleStatsClick);
    }
    
    const heightButton = document.querySelector('.height-button');
    if (heightButton) {
        heightButton.addEventListener('click', handleHeightClick);
    }
}

function handleNotificationClick(event) {
    console.log('Notifications clicked');
    showNotification('No new notifications');
}

function handleSettingsClick(event) {
    console.log('Settings clicked');
    window.location.href = '/settings';
}

function handleStatsClick(event) {
    console.log('View complete statistics clicked');
    window.location.href = '/health';
}

function showNotification(message) {
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
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeProfilePage
    };
}

'use strict';

class ProfileHealthInsights {
    constructor() {
        this.chartInstance = null;
        this.userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.init();
    }

    async init() {
        await this.fetchTodayStats();
        await this.createTimePercentageChart(); 
        
        setInterval(() => this.fetchTodayStats(), 1000);
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
            
            console.log('Health stats data:', data);
            
            this.updateStats(data);
            this.updateChartData(data);
            this.updatePercentagePills(data);
            
        } catch (error) {
            console.error('Error fetching today stats:', error);
            this.updateStatsWithDefaults();
        }
    }

    updateStats(data) {
        const postureScore = Math.max(0, Math.min(100, data.standing_pct || 0));
        const scoreEl = document.getElementById('profile-posture-score');
        const bar = document.getElementById('profile-posture-score-bar');
        const postureCard = document.querySelector('.posture-score-card');
        
        const colorClasses = ['value-poor', 'value-fair', 'value-good', 'value-excellent'];
        
        if (scoreEl) {
            scoreEl.textContent = `${postureScore}`;
            
            colorClasses.forEach(cls => {
                scoreEl.classList.remove(cls);
            });
            
            if (postureScore <= 30) {
                scoreEl.classList.add('value-poor');
            } else if (postureScore <= 60) {
                scoreEl.classList.add('value-fair');
            } else if (postureScore <= 80) {
                scoreEl.classList.add('value-good');
            } else {
                scoreEl.classList.add('value-excellent');
            }
        }
        
        if (bar) {
            bar.style.width = `${postureScore}%`;
            
            colorClasses.forEach(cls => {
                bar.classList.remove(cls);
            });
            
            if (postureScore <= 30) {
                bar.classList.add('value-poor');
            } else if (postureScore <= 60) {
                bar.classList.add('value-fair');
            } else if (postureScore <= 80) {
                bar.classList.add('value-good');
            } else {
                bar.classList.add('value-excellent');
            }
        }
        
        if (postureCard) {
            colorClasses.forEach(cls => {
                postureCard.classList.remove(cls);
            });
            
            if (postureScore <= 30) {
                postureCard.classList.add('value-poor');
            } else if (postureScore <= 60) {
                postureCard.classList.add('value-fair');
            } else if (postureScore <= 80) {
                postureCard.classList.add('value-good');
            } else {
                postureCard.classList.add('value-excellent');
            }
        }
        
        console.log('Posture score calculated:', postureScore);
    }

    updateChartData(data) {
        if (!this.chartInstance) {
            this.createTimePercentageChart(data);
            return;
        }
        
        const sittingPct = data.sitting_pct || 0;
        const standingPct = data.standing_pct || 0;
        
        console.log('Updating chart with:', { sittingPct, standingPct });
        
        this.chartInstance.data.datasets[0].data = [sittingPct, standingPct];
        this.chartInstance.update();
    }

    updatePercentagePills(data) {
        const sittingPct = data.sitting_pct || 0;
        const standingPct = data.standing_pct || 0;
        
        const sittingEl = document.getElementById('sitting-percentage');
        const standingEl = document.getElementById('standing-percentage');
        
        if (sittingEl) {
            sittingEl.textContent = `${Math.round(sittingPct)}% Sit`;
        }
        if (standingEl) {
            standingEl.textContent = `${Math.round(standingPct)}% Stand`;
        }
    }

    createTimePercentageChart(data = null) {
        const ctx = document.getElementById('timePercentageChartProfile')?.getContext('2d');
        if (!ctx) {
            console.error('Canvas element not found');
            return;
        }
        
        const palette = { 
            primary: '#3A506B', 
            accent: '#00A8A8' 
        };
        
        const sittingData = data?.sitting_pct || 50;
        const standingData = data?.standing_pct || 50;
        
        console.log('Creating chart with data:', { sittingData, standingData });
        
        if (this.chartInstance) {
            this.chartInstance.destroy();
        }
        
        this.chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Sitting', 'Standing'],
                datasets: [{
                    data: [sittingData, standingData],
                    backgroundColor: [palette.primary, palette.accent],
                    borderWidth: 0,
                    hoverOffset: 8,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw.toFixed(1)}%`;
                            }
                        },
                        backgroundColor: 'rgba(58, 80, 107, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#00A8A8',
                        borderWidth: 1
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    updateStatsWithDefaults() {
        const scoreEl = document.getElementById('profile-posture-score');
        const bar = document.getElementById('profile-posture-score-bar');
        const postureCard = document.querySelector('.posture-score-card');
        
        const colorClasses = ['value-poor', 'value-fair', 'value-good', 'value-excellent'];
        
        if (scoreEl) {
            scoreEl.textContent = '—';
            colorClasses.forEach(cls => {
                scoreEl.classList.remove(cls);
            });
        }
        
        if (bar) {
            bar.style.width = '0%';
            colorClasses.forEach(cls => {
                bar.classList.remove(cls);
            });
        }
        
        if (postureCard) {
            colorClasses.forEach(cls => {
                postureCard.classList.remove(cls);
            });
        }
        
        const sittingEl = document.getElementById('sitting-percentage');
        const standingEl = document.getElementById('standing-percentage');
        
        if (sittingEl) sittingEl.textContent = '—% Sit';
        if (standingEl) standingEl.textContent = '—% Stand';
        
        if (this.chartInstance) {
            this.chartInstance.data.datasets[0].data = [50, 50];
            this.chartInstance.update();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing ProfileHealthInsights...');
    new ProfileHealthInsights();
});