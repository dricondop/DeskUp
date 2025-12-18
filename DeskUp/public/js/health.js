'use strict';

const q = s => document.querySelector(s);
const qq = s => Array.from(document.querySelectorAll(s));

let chartInstances = {};
const palette = { primary: '#3A506B', accent: '#00A8A8', alt:'#9FB3C8' };

// Fetch live status
async function fetchLiveStatus() {
    try {
        const response = await fetch('/api/health-live-status');
        const data = await response.json();
        
        const modeEl = q('#live-mode');
        const heightEl = q('#live-height');
        const lastEl = q('#live-last');
        
        if (modeEl) modeEl.textContent = data.mode;
        if (heightEl) heightEl.textContent = `${data.height_cm} cm`;
        if (lastEl) lastEl.textContent = `Last adjusted: ${data.last_adjusted}`;
    } catch (error) {
        console.error('Error fetching live status:', error);
    }
}

// Fetch aggregated stats
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

// Fetch chart data
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
}

function generateInsights(data) {
    const container = q('.insights');
    if (container) container.innerHTML = '';

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

async function updateRange(range) {
    await fetchStats(range);
    await fetchChartData(range);
}

async function init() {
    createCharts();
    await fetchLiveStatus();
    await fetchStats('today');
    await fetchChartData('today');

    qq('.range-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            qq('.range-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            updateRange(btn.dataset.range || 'today');
        });
    });

    setInterval(fetchLiveStatus, 30000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}