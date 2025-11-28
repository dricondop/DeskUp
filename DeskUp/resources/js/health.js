/* Health insights — Chart rendering, timeframe and UI interactions */
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
        
        // Update doughnut chart
        if (chartInstances.timePercentage) {
            chartInstances.timePercentage.data.datasets[0].data = [
                data.sitting_pct || 65,
                data.standing_pct || 35
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
            chartInstances.timeAbsolute.data.labels = data.labels;
            chartInstances.timeAbsolute.data.datasets[0].data = [
                data.sitting_hours.reduce((a, b) => a + b, 0).toFixed(2),
                data.standing_hours.reduce((a, b) => a + b, 0).toFixed(2)
            ];
            chartInstances.timeAbsolute.data.labels = ['Sitting', 'Standing'];
            chartInstances.timeAbsolute.update();
        }
        
        // Update posture score line chart
        if (chartInstances.postureScore && data.posture_scores && data.posture_scores.length > 0) {
            chartInstances.postureScore.data.labels = data.labels;
            chartInstances.postureScore.data.datasets[0].data = data.posture_scores;
            setYAxisRange(chartInstances.postureScore, data.posture_scores, { minClamp: null, maxClamp: 100 });
            chartInstances.postureScore.update();
        }
        
        // Update height average chart
        if (chartInstances.heightAverage && data.avg_sit_heights && data.avg_sit_heights.length > 0) {
            chartInstances.heightAverage.data.labels = data.labels;
            chartInstances.heightAverage.data.datasets[0].data = data.avg_sit_heights;
            chartInstances.heightAverage.data.datasets[1].data = data.avg_stand_heights;
            setYAxisRange(
                chartInstances.heightAverage,
                data.avg_sit_heights.concat(data.avg_stand_heights),
                { minClamp: null, maxClamp: null }
            );
            chartInstances.heightAverage.update();
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
                    labels: ['Sitting', 'Standing'], 
                    datasets: [{
                        data: [65, 35],
                        backgroundColor: [palette.primary, palette.accent]
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
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
                        pointRadius: 3
                    }]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: {} }
                }
            });
        }

        const heightCtx = q('#heightAverageChart')?.getContext('2d');
        if (heightCtx) {
            chartInstances.heightAverage = new Chart(heightCtx, {
                type: 'line',
                data: { 
                    labels: [],
                    datasets: [
                        {
                            label: 'Avg Sit (cm)',
                            data: [],
                            borderColor: palette.alt,
                            backgroundColor: 'rgba(159,179,200,0.06)',
                            tension: 0.25,
                            pointRadius: 2
                        },
                        {
                            label: 'Avg Stand (cm)',
                            data: [],
                            borderColor: palette.accent,
                            backgroundColor: 'rgba(0,168,168,0.06)',
                            tension: 0.25,
                            pointRadius: 2
                        }
                    ]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: false } }
                }
            });
        }
    } catch (e) {
        console.warn('Chart init error', e);
    }
}

function generateInsights(data) {
    const container = q('.insights');
    if (container) container.innerHTML = '';

    const sitting = data.sitting_pct || 65;
    const activeHours = data.active_hours || 0;
    const breaks = data.breaks_per_day || 0;

    if (sitting > 60) {
        showTip("Try standing a bit more to reach a balanced posture! Aim for short standing breaks every hour.", "Posture balance");
    } else {
        showTip("Nice balance between sitting and standing — keep it up!", "Great posture");
    }

    if (activeHours < 6) {
        showTip("Your active hours are below 6h. Consider micro-activity breaks to raise daily activity.", "Increase activity");
    } else {
        showTip("You have a good amount of active desk time. Maintain regular breaks.", "Active time");
    }

    if (breaks < 2) {
        showTip("You might benefit from more short breaks — try 3–5 minute breaks each hour.", "Take breaks");
    }
}

function showTip(message, title = 'Suggestion') {
    const container = q('.insights');
    if (!container) return;
    const article = document.createElement('article');
    article.className = 'insight';
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

    // Refresh live status every 30 seconds
    setInterval(fetchLiveStatus, 30000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}