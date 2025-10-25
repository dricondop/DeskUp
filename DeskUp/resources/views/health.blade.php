<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Health Insights — DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset ('css/health.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="site-header" role="banner">
        <div class="container">
            <div class="header-left">
                <h1>Health Insights</h1>
                <p class="subtitle">Your desk activity and wellness overview</p>
            </div>

            <nav>
                <span class="badge">DeskUp</span>
            </nav>
        </div>
    </header>

    <main class="container dashboard" role="main">
        <section class="summary" aria-labelledby="summary-heading">

            <article class="card metric-card" id="live-status-card" aria-labelledby="live-status-label">
                <div class="metric">
                    <h3 id="live-status-label" class="metric-title">Live Status</h3>
                    <p class="metric-value"><span id="live-mode">Standing</span> — <span id="live-height">102 cm</span></p>
                    <p class="muted small" id="live-last">Last adjusted: 12m ago</p>
                </div>
                <span class="metric-badge">Real-time</span>
            </article>

            <article class="card metric-card" id="posture-score-card" aria-labelledby="posture-score-label">
                <div class="metric">
                    <h3 id="posture-score-label" class="metric-title">Posture Score</h3>
                    <p class="metric-value" id="posture-score-value">— / 100</p>
                    <div class="progress-wrap" aria-hidden="true">
                        <div class="progress-bar" id="posture-score-bar" style="width:0%"></div>
                    </div>
                </div>
                <span class="metric-badge">Period</span>
            </article>

            <article class="card metric-card" aria-labelledby="active-hours-label">
                <div class="metric">
                    <h3 id="active-hours-label" class="metric-title">Active Hours</h3>
                    <p class="metric-value" data-key="periodActiveHours">—</p>
                </div>
                <span class="metric-badge">Period</span>
            </article>

            <article class="card metric-card" aria-labelledby="standing-percent-label">
                <div class="metric">
                    <h3 id="standing-percent-label" class="metric-title">Standing</h3>
                    <p class="metric-value" data-key="periodStanding">—%</p>
                </div>
                <span class="metric-badge">Period</span>
            </article>

            <article class="card metric-card" aria-labelledby="breaks-label">
                <div class="metric">
                    <h3 id="breaks-label" class="metric-title">Breaks</h3>
                    <p class="metric-value" data-key="periodBreaks">—</p>
                </div>
                <span class="metric-badge">Period</span>
            </article>

            <article class="card metric-card" aria-labelledby="calories-label">
                <div class="metric">
                    <h3 id="calories-label" class="metric-title">Calories</h3>
                    <p class="metric-value" data-key="periodCalories">— kcal</p>
                </div>
                <span class="metric-badge">Period</span>
            </article>
        </section>

        <section class="charts" aria-labelledby="charts-heading">
            <div class="charts-header">
                <h2 id="charts-heading" class="section-title">Activity Charts</h2>
                <div class="controls" role="tablist" aria-label="Time range">
                    <button class="range-btn active" data-range="today">Today</button>
                    <button class="range-btn" data-range="weekly">Weekly</button>
                    <button class="range-btn" data-range="monthly">Monthly</button>
                    <button class="range-btn" data-range="yearly">Yearly</button>
                </div>
            </div>

            <div class="chart-grid" id="charts-grid">
                <figure class="card chart-card" data-key="timePercentage" aria-label="Time percentage">
                    <h3 class="chart-title">Time Percentage (sitting vs standing)</h3>
                    <div class="chart-container">
                        <canvas id="timePercentageChart" role="img" aria-label="Doughnut chart showing sitting vs standing percentage"></canvas>
                    </div>
                </figure>

                <figure class="card chart-card" data-key="timeAbsolute" aria-label="Absolute time">
                    <h3 class="chart-title">Absolute Time (hours sitting vs standing)</h3>
                    <div class="chart-container">
                        <canvas id="timeAbsoluteChart" role="img" aria-label="Horizontal bars showing sitting and standing durations"></canvas>
                    </div>
                </figure>

                <figure class="card chart-card" data-key="postureScore" aria-label="Posture score">
                    <h3 class="chart-title">Posture Score Over Period</h3>
                    <div class="chart-container">
                        <canvas id="postureScoreChart" role="img" aria-label="Line chart of posture score"></canvas>
                    </div>
                </figure>

                <figure class="card chart-card" data-key="heightAverage" aria-label="Sitting and standing height average">
                    <h3 class="chart-title">Height Average — Sit vs Stand</h3>
                    <div class="chart-container">
                        <canvas id="heightAverageChart" role="img" aria-label="Two-line chart of sitting and standing heights"></canvas>
                    </div>
                </figure>
            </div>
        </section>

        <section id="recommendations" class="recommendations" aria-labelledby="recommendations-heading">
            <h2 id="recommendations-heading" class="section-title">Recommendations</h2>

            <div class="insights" role="region" aria-live="polite" aria-label="Health recommendations"></div>

            <aside class="aside-tips" aria-label="Quick tips">
                <div class="card">
                    <h3>Quick Tips</h3>
                    <ul>
                        <li>Try the 50/10 rule: 50 minutes sitting, 10 standing or moving.</li>
                        <li>Set gentle reminders to stand or stretch every hour.</li>
                    </ul>
                </div>
            </aside>
        </section>
    </main>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <small>DeskUp © 2025</small>
        </div>
    </footer>

    <script src="health.js"></script>
</body>
</html>
<script>
    /* Health insights — Chart rendering, timeframe and UI interactions */
    'use strict';

    /* dataset */
    const sessionData = {
    sittingPct: 65,
    standingPct: 35,
    activeHours: 6.5,
    breaksPerDay: 3,
    caloriesPerDay: 120,
    weeklyHours: [5, 6, 7, 5.5, 6.8, 7.2, 4.9],
    monthlyHours: [130, 140, 135, 150, 160, 155, 170, 165],
    avgSitHeightCm: 72,
    avgStandHeightCm: 102
    };

    const q = s => document.querySelector(s);
    const qq = s => Array.from(document.querySelectorAll(s));

    function populateMetrics(data){
    qq('.metric-value').forEach(node=>{
        const key = node.dataset.key;
        if(!key) return;
        let val;
        switch(key){
        case 'periodStanding': val = `${data.standingPct}%`; break;
        case 'periodCalories': val = `${data.caloriesPerDay} kcal`; break;
        case 'periodBreaks': val = data.breaksPerDay ?? data.breaksPerDay;
        case 'periodActiveHours': val = data.activeHours; break;
        default: val = node.textContent;
        }
        if(val !== undefined) node.textContent = val;
    });

    const baseScore = Math.max(0, Math.min(100, Math.round(100 - (data.sittingPct - data.standingPct))));
    const scoreEl = q('#posture-score-value');
    const bar = q('#posture-score-bar');
    if(scoreEl) scoreEl.textContent = `${baseScore} / 100`;
    if(bar) bar.style.width = `${baseScore}%`;
    }

    /* build period-specific values for charts */
    function buildPeriodData(range){
    const timePct = { labels: ['Sitting','Standing'], data: [0,0] };
    const posture = { labels: [], data: [] };
    const heightAvg = { labels: [], sit: [], stand: [] };

    if(range === 'today'){
        const sitH = +(sessionData.activeHours * (sessionData.sittingPct/100)).toFixed(2);
        const standH = +(sessionData.activeHours * (sessionData.standingPct/100)).toFixed(2);
        timePct.data = [sitH, standH];

        const start = 8;
        const hours = Math.max(6, Math.ceil(sessionData.activeHours));
        posture.labels = Array.from({length: hours}, (_,i)=> `${start + i}:00`);
        posture.data = posture.labels.map((_,i)=>{
        const base = Math.max(30, Math.min(95, 100 - (sessionData.sittingPct - sessionData.standingPct)));
        return +(base + (Math.sin(i/2)*3) + (Math.random()*2-1)).toFixed(1);
        });

        heightAvg.labels = posture.labels;
        heightAvg.sit = heightAvg.labels.map(()=> +(sessionData.avgSitHeightCm + (Math.random()*3-1.5)).toFixed(1));
        heightAvg.stand = heightAvg.labels.map(()=> +(sessionData.avgStandHeightCm + (Math.random()*4-2)).toFixed(1));
    } else if(range === 'weekly'){
        const total = sessionData.weeklyHours.reduce((s,v)=>s+v,0);
        const sitH = +(total * (sessionData.sittingPct/100)).toFixed(2);
        const standH = +(total * (sessionData.standingPct/100)).toFixed(2);
        timePct.data = [sitH, standH];

        const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        posture.labels = days;
        posture.data = sessionData.weeklyHours.map((h,i)=>{
        const base = Math.max(30, Math.min(95, 100 - (sessionData.sittingPct - sessionData.standingPct)));
        return +(base + (i-3)*0.6 + (Math.random()*3-1.5)).toFixed(1);
        });

        heightAvg.labels = days;
        heightAvg.sit = sessionData.weeklyHours.map(()=> +(sessionData.avgSitHeightCm + (Math.random()*3-1.5)).toFixed(1));
        heightAvg.stand = sessionData.weeklyHours.map(()=> +(sessionData.avgStandHeightCm + (Math.random()*4-2)).toFixed(1));
    } else if(range === 'monthly'){
        const totalMonths = sessionData.monthlyHours.reduce((s,v)=>s+v,0);
        const avgMonth = totalMonths / Math.max(1, sessionData.monthlyHours.length);
        const sitH = +(avgMonth * (sessionData.sittingPct/100)).toFixed(2);
        const standH = +(avgMonth * (sessionData.standingPct/100)).toFixed(2);
        timePct.data = [sitH, standH];

        const buckets = ['1-5','6-10','11-15','16-20','21-25','26-30'];
        posture.labels = buckets;
        posture.data = buckets.map((_,i)=>{
        const base = Math.max(30, Math.min(95, 100 - (sessionData.sittingPct - sessionData.standingPct)));
        return +(base + (i - 2.5) * 0.8 + (Math.random()*3-1.5)).toFixed(1);
        });

        heightAvg.labels = buckets;
        heightAvg.sit = buckets.map((_,i)=> +(sessionData.avgSitHeightCm + (i*0.4) + (Math.random()*2-1)).toFixed(1));
        heightAvg.stand = buckets.map((_,i)=> +(sessionData.avgStandHeightCm + (i*0.6) + (Math.random()*3-1.5)).toFixed(1));
    } else { // yearly
        const totalYear = sessionData.monthlyHours.reduce((s,v)=>s+v,0);
        const sitH = +(totalYear * (sessionData.sittingPct/100)).toFixed(2);
        const standH = +(totalYear * (sessionData.standingPct/100)).toFixed(2);
        timePct.data = [sitH, standH];

        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        posture.labels = months;
        posture.data = months.map((_,i)=>{
        const base = Math.max(30, Math.min(95, 100 - (sessionData.sittingPct - sessionData.standingPct)));
        return +(base + Math.sin(i/2)*2 + (Math.random()*4-2)).toFixed(1);
        });

        heightAvg.labels = months;
        heightAvg.sit = months.map((_,i)=> +(sessionData.avgSitHeightCm + Math.cos(i/3)*1.5 + (Math.random()*2-1)).toFixed(1));
        heightAvg.stand = months.map((_,i)=> +(sessionData.avgStandHeightCm + Math.cos(i/2)*2 + (Math.random()*3-1.5)).toFixed(1));
    }

    return { timePct, posture, heightAvg };
    }

    /* chart instances */
    let chartInstances = {};
    const palette = { primary: '#3A506B', accent: '#00A8A8', alt:'#9FB3C8' };

    function setYAxisRange(chart, dataArr, opts = { minClamp: 0, maxClamp: 100 }) {
    if (!chart || !Array.isArray(dataArr) || dataArr.length === 0) return;
    const min = Math.min(...dataArr);
    const max = Math.max(...dataArr);
    const pad = Math.max(1, (max - min) * 0.12);
    let yMin = Math.floor(min - pad);
    let yMax = Math.ceil(max + pad);

    if (typeof opts.minClamp === 'number') yMin = Math.max(opts.minClamp, yMin);
    if (typeof opts.maxClamp === 'number') yMax = Math.min(opts.maxClamp, yMax);

    // safer way (no reassignments of chart.options)
    if (chart.options?.scales?.y) {
        chart.options.scales.y.min = yMin;
        chart.options.scales.y.max = yMax;
    }

    chart.update('none'); // use 'none' to avoid reanimation
    }


    function createCharts(data){
    try {
        const pctCtx = q('#timePercentageChart').getContext('2d');
        chartInstances.timePercentage = new Chart(pctCtx, {
        type: 'doughnut',
        data: { labels:['Sitting','Standing'], datasets:[{data:[data.sittingPct,data.standingPct], backgroundColor:[palette.primary,palette.accent]}] },
        options: { responsive:true, plugins:{legend:{position:'bottom'}} }
        });

        const absCtx = q('#timeAbsoluteChart').getContext('2d');
        const initAbs = buildPeriodData('today').timePct;
        chartInstances.timeAbsolute = new Chart(absCtx, {
        type:'bar',
        data:{ labels: initAbs.labels, datasets:[{ label: 'Hours', data: initAbs.data, backgroundColor: [palette.primary, palette.accent], borderRadius: 8, barThickness: 18 }]},
        options:{ indexAxis: 'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{ beginAtZero:true } } }
        });

        const postureCtx = q('#postureScoreChart').getContext('2d');
        const initPosture = buildPeriodData('today').posture;
        chartInstances.postureScore = new Chart(postureCtx, {
        type:'line',
        data:{ labels: initPosture.labels, datasets:[{ label:'Posture Score', data: initPosture.data, borderColor: palette.primary, backgroundColor: 'rgba(58,80,107,0.06)', fill: true, tension: 0.25, pointRadius: 3 }]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ } } }
        });
        setYAxisRange(chartInstances.postureScore, initPosture.data, { minClamp: null, maxClamp: 100 });

        const heightCtx = q('#heightAverageChart').getContext('2d');
        const initHeights = buildPeriodData('today').heightAvg;
        chartInstances.heightAverage = new Chart(heightCtx, {
        type:'line',
        data:{ labels: initHeights.labels, datasets:[
            { label:'Avg Sit (cm)', data: initHeights.sit, borderColor: palette.alt, backgroundColor: 'rgba(159,179,200,0.06)', tension:0.25, pointRadius:2 },
            { label:'Avg Stand (cm)', data: initHeights.stand, borderColor: palette.accent, backgroundColor: 'rgba(0,168,168,0.06)', tension:0.25, pointRadius:2 }
        ]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{ y:{ beginAtZero:false } } }
        });
        setYAxisRange(chartInstances.heightAverage, initHeights.sit.concat(initHeights.stand), { minClamp: null, maxClamp: null });

    } catch(e) {
        console.warn('Chart init error', e);
    }
    }

    function updateRange(range){
    const period = buildPeriodData(range);

    if(chartInstances.timeAbsolute){
        chartInstances.timeAbsolute.data.labels = period.timePct.labels;
        chartInstances.timeAbsolute.data.datasets[0].data = period.timePct.data;
        chartInstances.timeAbsolute.update();
    }

    if(chartInstances.postureScore){
        chartInstances.postureScore.data.labels = period.posture.labels;
        chartInstances.postureScore.data.datasets[0].data = period.posture.data;
        setYAxisRange(chartInstances.postureScore, period.posture.data, { minClamp: null, maxClamp: 100 });
        chartInstances.postureScore.update();
    }

    if(chartInstances.heightAverage){
        chartInstances.heightAverage.data.labels = period.heightAvg.labels;
        chartInstances.heightAverage.data.datasets[0].data = period.heightAvg.sit;
        chartInstances.heightAverage.data.datasets[1].data = period.heightAvg.stand;
        setYAxisRange(chartInstances.heightAverage, period.heightAvg.sit.concat(period.heightAvg.stand), { minClamp: null, maxClamp: null });
        chartInstances.heightAverage.update();
    }

    if(chartInstances.timePercentage){
        const delta = (range==='today'?0: Math.floor(Math.random()*5)-2);
        const sit = Math.max(30, Math.min(80, sessionData.sittingPct + delta));
        const stand = 100 - sit;
        chartInstances.timePercentage.data.datasets[0].data = [sit, stand];
        chartInstances.timePercentage.update();
    }

    updateMetricsForRange(range);
    }

    function updateMetricsForRange(range){
    const period = buildPeriodData(range);
    const sit = period.timePct.data[0] || 0;
    const stand = period.timePct.data[1] || 0;
    const total = +(sit + stand).toFixed(2);

    const activeHours = Number(total.toFixed(2));
    const standingPercent = total > 0 ? Math.round((stand / total) * 100) : sessionData.standingPct;
    const multipliers = { today:1, weekly:7, monthly:30, yearly:365 };
    const m = multipliers[range] || 1;
    const breaks = Math.round(sessionData.breaksPerDay * m) || Math.round(sessionData.breaksPerDay ?? sessionData.breaksPerDay);
    const calories = Math.round(sessionData.caloriesPerDay * m);

    const activeEl = q('[data-key="periodActiveHours"]'); if(activeEl) activeEl.textContent = activeHours;
    const standEl = q('[data-key="periodStanding"]'); if(standEl) standEl.textContent = `${standingPercent}%`;
    const breaksEl = q('[data-key="periodBreaks"]'); if(breaksEl) breaksEl.textContent = breaks;
    const calEl = q('[data-key="periodCalories"]'); if(calEl) calEl.textContent = `${calories} kcal`;

    const score = Math.max(0, Math.min(100, Math.round(standingPercent)));
    const scoreEl = q('#posture-score-value'); const bar = q('#posture-score-bar');
    if(scoreEl) scoreEl.textContent = `${score} / 100`;
    if(bar) bar.style.width = `${score}%`;
    }

    function showTip(message, title = 'Suggestion'){
    const container = q('.insights');
    if(!container) return;
    const article = document.createElement('article');
    article.className = 'insight';
    article.innerHTML = `<h4>${title}</h4><p>${message}</p>`;
    container.appendChild(article);
    }

    function generateInsights(data){
    const container = q('.insights'); if(container) container.innerHTML = '';

    if(data.sittingPct > 60) showTip("Try standing a bit more tomorrow to reach a balanced posture! Aim for short standing breaks every hour.", "Posture balance");
    else showTip("Nice balance between sitting and standing — keep it up!", "Great posture");

    if(data.activeHours < 6) showTip("Your active hours are below 6h. Consider micro-activity breaks to raise daily activity.", "Increase activity");
    else showTip("You have a good amount of active desk time today. Maintain regular breaks.", "Active time");

    if(data.breaksPerDay < 2) showTip("You might benefit from more short breaks — try 3–5 minute breaks each hour.", "Take breaks");

    const recent = data.monthlyHours.slice(-3);
    const rising = recent.length >= 3 && recent[2] >= recent[0];
    if(rising) showTip("Monthly standing trend is improving — continue increasing standing minutes gradually.", "Trend");
    else showTip("Monthly standing has flattened or dipped; consider setting gentle standing goals.", "Trend");
    }

    function init(){
    populateMetrics(sessionData);
    createCharts(sessionData);
    generateInsights(sessionData);

    qq('.range-btn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
        qq('.range-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        updateRange(btn.dataset.range || 'today');
        });
    });

    updateMetricsForRange('today');
    }

    if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
    } else {
    init();
    }
</script>