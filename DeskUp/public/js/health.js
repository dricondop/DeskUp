'use strict';

const q = s => document.querySelector(s);
const qq = s => Array.from(document.querySelectorAll(s));

let chartInstances = {};
const palette = { primary: '#3A506B', accent: '#00A8A8', alt:'#9FB3C8' };

// Fetch all data at once for instant page load
async function fetchAllData(range = 'today') {
    try {
        const response = await fetch(`/api/health-data?range=${range}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error fetching data:', data.error);
            return null;
        }
        
        // Update live status
        updateLiveStatus(data.liveStatus);
        
        // Update stats
        updateStats(data.stats);
        
        // Update charts
        updateCharts(data.chartData, data.stats);
        
        return data;
    } catch (error) {
        console.error('Error fetching all data:', error);
        return null;
    }
}

// Update live status display
function updateLiveStatus(data) {
    const modeEl = q('#live-mode');
    const heightEl = q('#live-height');
    const lastEl = q('#live-last');
    
    if (modeEl) modeEl.textContent = data.mode;
    if (heightEl) heightEl.textContent = `${data.height_cm} cm`;
    if (lastEl) lastEl.textContent = `Last adjusted: ${data.last_adjusted}`;
}

// Update stats display
function updateStats(data) {
    // Update metric cards
    const activeEl = q('[data-key="periodActiveHours"]');
    if (activeEl) activeEl.textContent = data.active_hours || 0;
    
    const standEl = q('[data-key="periodStanding"]');
    if (standEl) standEl.textContent = `${data.standing_pct || 0}%`;
    
    const breaksEl = q('[data-key="periodBreaks"]');
    if (breaksEl) breaksEl.textContent = data.breaks_per_day || 0;
    
    const calEl = q('[data-key="periodCalories"]');
    if (calEl) calEl.textContent = `${data.calories_per_day || 0} kcal`;
    
    // Update posture score
    const score = Math.max(0, Math.min(100, data.standing_pct || 0));
    const scoreEl = q('#posture-score-value');
    const bar = q('#posture-score-bar');
    if (scoreEl) scoreEl.textContent = `${score} / 100`;
    if (bar) bar.style.width = `${score}%`;
    
    // Generate insights
    generateInsights(data);
}

// Update all charts
function updateCharts(chartData, stats) {
    // Update doughnut chart (time percentage)
    if (chartInstances.timePercentage) {
        chartInstances.timePercentage.data.datasets[0].data = [
            stats.sitting_pct || 50,
            stats.standing_pct || 50
        ];
        chartInstances.timePercentage.update();
    }
    
    // Update bar chart (absolute time)
    if (chartInstances.timeAbsolute && chartData.labels && chartData.labels.length > 0) {
        chartInstances.timeAbsolute.data.datasets[0].data = [
            chartData.sitting_hours.reduce((a, b) => a + b, 0).toFixed(2),
            chartData.standing_hours.reduce((a, b) => a + b, 0).toFixed(2)
        ];
        chartInstances.timeAbsolute.data.labels = ['Sitting', 'Standing'];
        chartInstances.timeAbsolute.update();
    }
    
    // Update posture score line chart
    if (chartInstances.postureScore && chartData.posture_scores && chartData.posture_scores.length > 0) {
        chartInstances.postureScore.data.labels = chartData.labels;
        chartInstances.postureScore.data.datasets[0].data = chartData.posture_scores;
        setYAxisRange(chartInstances.postureScore, chartData.posture_scores, { minClamp: null, maxClamp: 100 });
        chartInstances.postureScore.update();
    }
    
    // Update height average chart
    if (chartInstances.heightAverage && chartData.avg_sit_heights && chartData.avg_sit_heights.length > 0) {
        chartInstances.heightAverage.data.labels = chartData.labels;
        chartInstances.heightAverage.data.datasets[0].data = chartData.avg_sit_heights;
        chartInstances.heightAverage.data.datasets[1].data = chartData.avg_stand_heights;
        setYAxisRange(
            chartInstances.heightAverage,
            chartData.avg_sit_heights.concat(chartData.avg_stand_heights),
            { minClamp: null, maxClamp: null }
        );
        chartInstances.heightAverage.update();
    }
    
    // Update height overview chart
    if (chartInstances.heightOverview && chartData.height_overview && chartData.height_overview.length > 0) {
        chartInstances.heightOverview.data.labels = chartData.labels;
        
        const heights = chartData.height_overview.map(h => h.height);
        const modes = chartData.height_overview.map(h => h.mode);
        
        chartInstances.heightOverview.data.datasets[0].data = heights;
        chartInstances.heightOverview.data.datasets[0].modes = modes;
        
        const validHeights = heights.filter(h => h !== null);
        if (validHeights.length > 0) {
            setYAxisRange(chartInstances.heightOverview, validHeights, { minClamp: null, maxClamp: null });
        }
        
        chartInstances.heightOverview.update();
    }
}

// Fetch live status
async function fetchLiveStatus() {
    try {
        const response = await fetch('/api/health-live-status');
        const data = await response.json();
        
        updateLiveStatus(data);
    } catch (error) {
        console.error('Error fetching live status:', error);
    }
}

// Fetch aggregated stats (kept for backward compatibility or refresh)
async function fetchStats(range = 'today') {
    try {
        const response = await fetch(`/api/health-stats?range=${range}`);
        const data = await response.json();
        
        // Check for insufficient data (if there's only a few entries of data)
        if (data.insufficient_data) {
            displayInsufficientDataInCharts(data.data_quality);
            clearMetrics();
            clearRecommendations();
            return data;
        }
        
        // Clear insufficient data message if data becomes sufficient
        clearInsufficientDataMessage();
        
        // Update metric cards
        const activeEl = q('[data-key="periodActiveHours"]');
        if (activeEl) activeEl.textContent = data.active_hours || 0;
        
        const standEl = q('[data-key="periodStanding"]');
        if (standEl) standEl.textContent = `${data.standing_pct || 0}%`;
        
        const transitionsEl = q('[data-key="periodBreaks"]');
        if (transitionsEl) transitionsEl.textContent = data.position_changes || 0;
        
        const calEl = q('[data-key="periodCalories"]');
        if (calEl) calEl.textContent = `${data.calories_per_day || 0} kcal`;
        
        // Update posture score
        const score = data.posture_score;
        const scoreEl = q('#posture-score-value');
        const bar = q('#posture-score-bar');
        
        if (score === null || score === undefined) {
            if (scoreEl) scoreEl.textContent = '- / 100';
            if (bar) bar.style.width = '0%';
        } else {
            if (scoreEl) scoreEl.textContent = `${score} / 100`;
            if (bar) {
                bar.style.width = `${score}%`;
                if (score >= 80) {
                    bar.style.backgroundColor = '#00A8A8';
                } else if (score >= 60) {
                    bar.style.backgroundColor = '#3A506B';
                } else if (score >= 40) {
                    bar.style.backgroundColor = '#F4A261';
                } else {
                    bar.style.backgroundColor = '#E76F51';
                }
            }
        }
        
        // Update doughnut chart
        if (chartInstances.timePercentage) {
            chartInstances.timePercentage.data.datasets[0].data = [
                data.standing_pct || 50,
                data.sitting_pct || 50
            ];
            chartInstances.timePercentage.update();
        }
        
        // Generate insights
        generateInsights(data);
        
        return data;
    } catch (error) {
        console.error('Error fetching stats:', error);
        return null;
    }
}

// Fetch chart data (kept for backward compatibility or refresh)
async function fetchChartData(range = 'today') {
    try {
        const response = await fetch(`/api/health-chart-data?range=${range}`);
        const data = await response.json();
        
        // Update bar chart (absolute time)
        if (chartInstances.timeAbsolute && data.labels && data.labels.length > 0) {
            chartInstances.timeAbsolute.data.labels = ['Sitting', 'Standing'];
            chartInstances.timeAbsolute.data.datasets[0].data = [
                data.sitting_hours.reduce((a, b) => a + (b || 0), 0).toFixed(2),
                data.standing_hours.reduce((a, b) => a + (b || 0), 0).toFixed(2)
            ];
            chartInstances.timeAbsolute.update();
        }
        
        // Update posture score line chart
        if (chartInstances.postureScore && data.posture_scores && data.posture_scores.length > 0) {
            chartInstances.postureScore.data.labels = data.labels;
            chartInstances.postureScore.data.datasets[0].data = data.posture_scores;
            chartInstances.postureScore.options.scales.y.min = 0;
            chartInstances.postureScore.options.scales.y.max = 100;
            chartInstances.postureScore.update();
        }
        
        // Update height average chart
        if (chartInstances.heightAverage && data.avg_sit_heights && data.avg_sit_heights.length > 0) {
            chartInstances.heightAverage.data.labels = data.labels;
            chartInstances.heightAverage.data.datasets[0].data = data.avg_sit_heights;
            chartInstances.heightAverage.data.datasets[1].data = data.avg_stand_heights;
            
            // Only set range if we have valid data
            const validHeights = [...data.avg_sit_heights, ...data.avg_stand_heights].filter(h => h !== null);
            if (validHeights.length > 0) {
                setYAxisRange(chartInstances.heightAverage, validHeights, { minClamp: null, maxClamp: null });
            }
            chartInstances.heightAverage.update();
        }
        
        // Height overview chart
        if (chartInstances.heightOverview && data.height_overview && data.height_overview.length > 0) {
            chartInstances.heightOverview.data.labels = data.labels;
            
            const sittingHeights = data.height_overview.map(h => h.sitting_height);
            const standingHeights = data.height_overview.map(h => h.standing_height);
            
            chartInstances.heightOverview.data.datasets[0].data = sittingHeights;
            chartInstances.heightOverview.data.datasets[1].data = standingHeights;
            
            const validHeights = [...sittingHeights, ...standingHeights].filter(h => h !== null);
            if (validHeights.length > 0) {
                setYAxisRange(chartInstances.heightOverview, validHeights, { minClamp: null, maxClamp: null });
            }
            
            chartInstances.heightOverview.update();
        }
        
        return data;
    } catch (error) {
        console.error('Error fetching chart data:', error);
        return null;
    }
}

function setYAxisRange(chart, dataArr, opts = { minClamp: 0, maxClamp: 100 }) {
    if (!chart || !Array.isArray(dataArr) || dataArr.length === 0) return;
    const min = Math.min(...dataArr);
    const max = Math.max(...dataArr);
    const pad = Math.max(1, (max - min) * 0.12);
    let yMin = Math.floor(min - pad);
    let yMax = Math.ceil(max + pad);

    if (typeof opts.minClamp === 'number') yMin = Math.max(opts.minClamp, yMin);
    if (typeof opts.maxClamp === 'number') yMax = Math.min(opts.maxClamp, yMax);

    if (chart.options?.scales?.y) {
        chart.options.scales.y.min = yMin;
        chart.options.scales.y.max = yMax;
    }

    chart.update('none');
}

function createCharts() {
    try {
        const pctCtx = q('#timePercentageChart')?.getContext('2d');
        if (pctCtx) {
            chartInstances.timePercentage = new Chart(pctCtx, {
                type: 'doughnut',
                data: { 
                    labels: ['Standing', 'Sitting'], 
                    datasets: [{
                        data: [50, 50],
                        backgroundColor: [palette.accent,palette.primary]
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom', reverse: true} } }
            });
        }

        const absCtx = q('#timeAbsoluteChart')?.getContext('2d');
        if (absCtx) {
            chartInstances.timeAbsolute = new Chart(absCtx, {
                type: 'bar',
                data: { 
                    labels: ['Sitting', 'Standing'], 
                    datasets: [{
                        label: 'Hours',
                        data: [0, 0],
                        backgroundColor: [palette.primary, palette.accent],
                        borderRadius: 8,
                        barThickness: 18
                    }]
                },
                options: { 
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        }

        const postureCtx = q('#postureScoreChart')?.getContext('2d');
        if (postureCtx) {
            chartInstances.postureScore = new Chart(postureCtx, {
                type: 'line',
                data: { 
                    labels: [],
                    datasets: [{
                        label: 'Posture Score',
                        data: [],
                        borderColor: palette.primary,
                        backgroundColor: 'rgba(58,80,107,0.06)',
                        fill: true,
                        tension: 0.25,
                        pointRadius: 3,
                        spanGaps: true
                    }]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.parsed.y !== null ? `Score: ${ctx.parsed.y}` : 'No data'
                            }
                        }
                    },
                    scales: { 
                        y: {
                            min: 0,
                            max: 100
                        }
                    }
                }
            });
        }

        const heightOverviewCtx = q('#heightOverviewChart')?.getContext('2d');
        if (heightOverviewCtx) {
            chartInstances.heightOverview = new Chart(heightOverviewCtx, {
                type: 'line',
                data: { 
                    labels: [],
                    datasets: [
                        {
                            label: 'Sitting Height Average (cm)',
                            data: [],
                            borderColor: palette.primary,
                            backgroundColor: palette.primary,
                            spanGaps: true,
                            tension: 0.3,
                            pointRadius: 5,
                            pointBackgroundColor: palette.primary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Standing Height Average (cm)',
                            data: [],
                            borderColor: palette.accent,
                            backgroundColor: palette.accent,
                            spanGaps: true,
                            tension: 0.3,
                            pointRadius: 5,
                            pointBackgroundColor: palette.accent,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    if (ctx.parsed.y === null) return 'No data';
                                    return `${ctx.dataset.label}: ${ctx.parsed.y} cm`;
                                }
                            }
                        }
                    },
                    scales: { 
                        y: { 
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Height (cm)'
                            }
                        } 
                    }
                }
            });
        }
    } catch (e) {
        console.warn('Chart init error', e);
    }
}

function displayInsufficientDataInCharts(dataQuality) {
    const chartsGrid = q('.chart-grid');
    if (!chartsGrid) return;
    
    // Hide all existing charts
    chartsGrid.innerHTML = '';
    
    // Create centered message container
    const messageContainer = document.createElement('div');
    messageContainer.className = 'insufficient-data-message';
    messageContainer.innerHTML = `
        <div class="insufficient-data-content">
            <div class="insufficient-data-icon">📊</div>
            <h3 class="insufficient-data-title">Insufficient Data</h3>
            <p class="insufficient-data-description">${dataQuality.recommendation || 'Not enough data to calculate health insights.'}</p>
            
            <div class="insufficient-data-details">
                <div class="insufficient-data-detail-icon">ℹ️</div>
                <div>
                    <h4>Details</h4>
                    <p>Reason: ${dataQuality.reason}</p>
                </div>
            </div>
        </div>
    `;
    
    chartsGrid.appendChild(messageContainer);
}

function clearInsufficientDataMessage() {
    const chartsGrid = q('.chart-grid');
    if (!chartsGrid) return;
    
    const insufficientMsg = chartsGrid.querySelector('.insufficient-data-message');
    if (insufficientMsg) {
        chartsGrid.innerHTML = `
            <figure class="card chart-card" data-key="timePercentage">
                <h3 class="chart-title">Time Percentage (sitting vs standing)</h3>
                <div class="chart-container">
                    <canvas id="timePercentageChart"></canvas>
                </div>
            </figure>

            <figure class="card chart-card" data-key="timeAbsolute">
                <h3 class="chart-title">Absolute Time (hours sitting vs standing)</h3>
                <div class="chart-container">
                    <canvas id="timeAbsoluteChart"></canvas>
                </div>
            </figure>

            <figure class="card chart-card" data-key="postureScore">
                <h3 class="chart-title">Posture Score Over Period</h3>
                <div class="chart-container">
                    <canvas id="postureScoreChart"></canvas>
                </div>
            </figure>

            <figure class="card chart-card" data-key="heightOverview">
                <h3 class="chart-title">Height Average Overview</h3>
                <div class="chart-container">
                    <canvas id="heightOverviewChart"></canvas>
                </div>
            </figure>
        `;
        
        // Recreate charts
        createCharts();
    }
}

function clearMetrics() {
    const activeEl = q('[data-key="periodActiveHours"]');
    if (activeEl) activeEl.textContent = '—';
    
    const standEl = q('[data-key="periodStanding"]');
    if (standEl) standEl.textContent = '—%';
    
    const transitionsEl = q('[data-key="periodBreaks"]');
    if (transitionsEl) transitionsEl.textContent = '—';
    
    const calEl = q('[data-key="periodCalories"]');
    if (calEl) calEl.textContent = '— kcal';
}

function clearRecommendations() {
    const container = q('.insights');
    if (container) container.innerHTML = '';
function setupPDFExport() {
    const exportBtn = document.getElementById('export-pdf-btn');
    if (!exportBtn) return;
    
    exportBtn.addEventListener('click', async () => {
        const activeRangeBtn = document.querySelector('.range-btn.active');
        const range = activeRangeBtn ? activeRangeBtn.dataset.range : 'today';
        
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<span>Exporting...</span>';
        exportBtn.disabled = true;
        
        try {
            const url = `/health/export/pdf?range=${range}`;
            window.open(url, '_blank');
        } catch (error) {
            console.error('Export error:', error);
            alert('Failed to generate PDF. Please try again.');
        } finally {
            // Restaurar botón
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        }
    });
}

function generateInsights(data) {
    const container = q('.insights');
    if (!container) return;
    container.innerHTML = '';

    // Skip insights if insufficient data (already handled in charts area)
    if (data.insufficient_data) {
        return;
    }

    const sitting = data.sitting_pct || 65;
    const standing = data.standing_pct || 35;
    const activeHours = data.active_hours || 0;
    const transitions = data.position_changes || 0;
    const postureScore = data.posture_score;

    if (postureScore === null || postureScore === undefined) {
        showTip(
            "Not enough data to calculate posture score. Continue using your desk throughout the day.",
            "⏳ Building Your Profile",
            "info"
        );
        return;
    }

    showTip(
        "This score reflects desk usage patterns based on ergonomic guidelines. It does not constitute clinical posture assessment.",
        "ℹ️ Disclaimer",
        "info"
    );

    if (postureScore >= 85) {
        showTip("Outstanding posture habits! You're maintaining excellent ergonomic balance.", "⭐ Excellent");
    } else if (postureScore >= 70) {
        showTip("Good posture habits overall. Small optimizations could further improve your score.", "✅ Good");
    } else if (postureScore >= 50) {
        showTip("Room for improvement. Focus on regular position changes and ergonomic desk heights.", "⚠️ Fair");
    } else {
        showTip("Your posture habits need attention. Review ergonomic guidelines and consider more frequent breaks.", "❌ Needs Improvement");
    }

    if (standing < 30) {
        showTip("Try standing more! Aim for 40-50% of your work time for optimal health.", "Increase standing");
    } else if (standing > 60) {
        showTip("You're standing a lot. Balance it with more sitting breaks to avoid fatigue.", "Balance needed");
    } else if (standing >= 40 && standing <= 50) {
        showTip("Perfect standing-to-sitting ratio! Keep maintaining this balance.", "Ideal balance");
    }

    const normalizedTransitions = transitions * (8 / Math.max(1, activeHours));
    if (normalizedTransitions < 4) {
        showTip("Increase position changes. Aim for 6-12 sit-stand transitions per workday (every 40-80 minutes).", "More transitions needed");
    } else if (normalizedTransitions > 16) {
        showTip("You're switching positions very frequently. Consider longer intervals between changes.", "Too many transitions");
    } else if (normalizedTransitions >= 6 && normalizedTransitions <= 12) {
        showTip("Excellent transition frequency! You're changing positions at healthy intervals.", "Perfect rhythm");
    }
    
    const totalHours = activeHours || 8;
    const sittingHours = ((sitting / 100) * totalHours).toFixed(1);
    if (sittingHours > 6) {
        showTip("Extended sitting detected. Take 5-10 minute standing/walking breaks every hour.", "Long sitting hours");
    } else if (sittingHours < 3) {
        showTip("Great job limiting sitting time! Ensure your standing setup is comfortable.", "Active workday");
    }
}

function showTip(message, title = 'Suggestion', type = 'default') {
    const container = q('.insights');
    if (!container) return;
    const article = document.createElement('article');
    article.className = `insight insight-${type}`;
    article.innerHTML = `<h4>${title}</h4><p>${message}</p>`;
    container.appendChild(article);
}

async function init() {
    createCharts();
    await fetchLiveStatus();
    await fetchStats('today');
    await fetchChartData('today');
    
    
    // Load all data in parallel for faster page load
    await Promise.all([
        fetchAllData('today'),
        loadNotificationHistory()
    ]);

    qq('.range-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            qq('.range-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            updateRange(btn.dataset.range || 'today');
        });
    });
    
    setupPDFExport();
    
    // Refresh live status every 30 seconds
    setInterval(fetchLiveStatus, 30000);
    
    // Refresh notifications every 2 minutes
    setInterval(loadNotificationHistory, 120000);
}

async function updateRange(range) {
    // Use combined endpoint for faster updates
    await fetchAllData(range);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// Load notification history
async function loadNotificationHistory() {
    const listEl = document.getElementById('notificationHistoryList');
    if (!listEl) return;

    try {
        const response = await fetch('/api/notifications/history');
        const data = await response.json();

        if (!data.notifications || data.notifications.length === 0) {
            listEl.innerHTML = '<p class="empty-text">No notifications yet</p>';
            return;
        }

        listEl.innerHTML = data.notifications.map(notification => {
            const date = new Date(notification.sent_at || notification.created_at);
            const timeAgo = getTimeAgo(date);
            const unreadClass = notification.is_read ? '' : ' unread';
            
            return `
                <div class="notification-item${unreadClass}">
                    <div class="notification-item-header">
                        <h4 class="notification-item-title">${escapeHtml(notification.title)}</h4>
                        <span class="notification-item-badge ${notification.type}">${notification.type}</span>
                    </div>
                    <p class="notification-item-message">${escapeHtml(notification.message)}</p>
                    <span class="notification-item-time">${timeAgo}</span>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading notification history:', error);
        listEl.innerHTML = '<p class="empty-text">Failed to load notifications</p>';
    }
}

function getTimeAgo(date) {
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`;
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

