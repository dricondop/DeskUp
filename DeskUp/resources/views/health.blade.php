<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Insights — DeskUp</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/health.css') }}">
    <script src="{{ asset('js/health.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
    <header class="site-header" role="banner">
        <div class="container">
            <div class="header-left">
                <h1>Health Insights</h1>
                <p class="subtitle">Your desk activity and wellness overview</p>
            </div>

            <nav>
                <span class="badge">DeskUp</span>
                <div style="display: inline-flex; gap: 8px; margin-left: 15px;">
                    <button id="export-pdf-btn" class="export-btn" title="Export to PDF">
                        <svg style="width: 16px; height: 16px; margin-right: 5px;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        Export PDF
                    </button>
                </div>
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

                <figure class="card chart-card" data-key="heightOverview" aria-label="Height overview">
                    <h3 class="chart-title">Height Overview</h3>
                    <div class="chart-container">
                        <canvas id="heightOverviewChart" role="img" aria-label="Line chart showing desk height with color-coded sitting and standing"></canvas>
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
    </div>
</body>
</html>