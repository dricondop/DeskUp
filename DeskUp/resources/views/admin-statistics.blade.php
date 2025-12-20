<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Statistics | DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/health.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-stats.css') }}">
    <script src="{{ asset('js/admin-stats.js') }}" defer></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    @include('components.sidebar')

    <div class="main-content">
        <header class="site-header" role="banner">
            <div class="container">
                <div class="header-left">
                    <h1>Admin Statistics</h1>
                    <p class="subtitle">Desk usage analytics</p>
                </div>
                <nav>
                    <span class="badge">DeskUp Admin</span>
                    <div style="display: inline-flex; gap: 8px; margin-left: 15px;">
                        <button id="export-stats-pdf-btn" class="export-btn" title="Export Statistics to PDF">
                            <svg style="width: 16px; height: 16px; margin-right: 5px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                            </svg>
                            Export PDF
                        </button>
                    </div>
                </nav>
            </div>
        </header>

        <main class="container admin-container" role="main">
            <section class="summary" aria-label="Resumen de escritorios">
                <article class="card metric-card" aria-labelledby="occupied-title">
                    <div class="metric">
                        <h3 id="occupied-title" class="metric-title">Occupied Desks</h3>
                        <p id="desks-occupied" class="metric-value">{{ $occupiedDesks }}</p>
                        <p class="muted small">Amount of occupied desks</p>
                    </div>
                </article>

                <article class="card metric-card" aria-labelledby="available-title">
                    <div class="metric">
                        <h3 id="available-title" class="metric-title">Available Desks</h3>
                        <p id="desks-free" class="metric-value">{{ $totalDesks - $occupiedDesks }}</p>
                        <p class="muted small">Available desks</p>
                    </div>
                </article>

                <article class="card metric-card" aria-labelledby="avg-session-title">
                    <div class="metric">
                        <h3 id="avg-session-title" class="metric-title">Average Session Time</h3>
                        <p id="avg-session" class="metric-value">{{ round($avgSession) }} min</p>
                        <p class="muted small">Average session (approx.)</p>
                    </div>
                </article>
            </section>

            <section class="panels" aria-label="Panels">
                <div>
                    <section class="card chart-card" aria-labelledby="usage-title">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <h2 id="usage-title" class="chart-title">User Desk Usage</h2>
                            <div style="display:flex; gap:8px; align-items:center;">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div style="min-height:180px;">
                                <h4 style="margin:6px 0 8px 0; font-size:0.95rem; color:#3A506B;">Top Users (usage records)</h4>
                                <div class="chart-container">
                                    <canvas id="topUsersChart" aria-label="Top users bar chart"></canvas>
                                </div>
                            </div>

                            <div style="min-height:180px;">
                                <h4 style="margin:6px 0 8px 0; font-size:0.95rem; color:#3A506B;">Occupied vs Available</h4>
                                <div class="chart-container">
                                    <canvas id="desksDonut" aria-label="Desks occupancy donut"></canvas>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="heatmap-title" style="margin-top:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 id="heatmap-title" class="chart-title">Occupancy Heatmap (hours × days)</h3>
                            <p class="muted small" style="margin:0">Intensity = Number of usage records</p>
                        </div>

                        <div style="display:flex; gap:16px; margin-top:12px; align-items:flex-start;">
                            <div class="heatmap-row-labels" aria-hidden="true">
                                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                            </div>

                            <div>
                                <div id="heatmap" class="heatmap" role="img" aria-label="Heatmap"></div>

                                <div class="heatmap-legend" aria-hidden="true">
                                    <span style="font-weight:600; color:#333;">Low</span>
                                    <div style="width:18px; height:12px; background:#f1f3f5; border-radius:3px;"></div>
                                    <div style="width:18px; height:12px; background:#9fb3c8; border-radius:3px;"></div>
                                    <div style="width:18px; height:12px; background:#00A8A8; border-radius:3px;"></div>
                                    <div style="width:18px; height:12px; background:#3A506B; border-radius:3px;"></div>
                                    <span style="font-weight:600; color:#333;">High</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside style="display:flex; flex-direction:column; gap:12px;">
                    <section class="card avg-panel" aria-labelledby="desk-list-title">
                        <h3 id="desk-list-title" style="margin:0 0 8px 0;">All Desks — Average session</h3>
                        <div class="desk-list" id="deskList" aria-live="polite">
                            @foreach($desks as $desk)
                                <div class="desk-row">
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <strong style="min-width:90px; color:#3A506B;">{{ $desk->name }}</strong>
                                        <small class="muted small">Status: {{ $desk->status }}</small>
                                    </div>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <span class="avg-badge">{{ $desk->height }} min</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                            <button class="btn-ghost" id="exportBtn">Export</button>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="users-list-title">
                        <h3 id="users-list-title" style="margin:0 0 8px 0;">Users</h3>

                        <ul class="small-list" id="usersList" aria-live="polite">
                            @foreach($users as $u)
                                <li>
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <strong style="color:#3A506B">{{ $u->name }}</strong>
                                            <div style="font-size:0.85rem; color:#666;">{{ $u->email }}</div>
                                        </div>
                                        <div class="user-actions">
                                            {{-- You can add actions here (assign desk, disable) hooked to API endpoints --}}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                </aside>
            </section>
        </main>

        <footer class="site-footer" role="contentinfo">
            <div class="container">
                <small>DeskUp © 2025</small>
            </div>
        </footer>
    </div>

    {{-- Expose server data to JS safely --}}
    <script>
        const totalDesks = {{ $totalDesks }};
        const occupiedDesks = {{ $occupiedDesks }};
        const topUsersData = @json($topUsers);
        const heatmapGrid = @json($heatmapGrid); 
    </script>

    <script>
    (function(){
        const palette = { primary: '#3A506B', accent: '#00A8A8', alt: '#9FB3C8', light: '#f1f3f5' };

        function updateClock() {
            const clockEl = document.getElementById('clock');
            if (!clockEl) return;

            const now = new Date();

            const datePart = now.toLocaleDateString(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });

            const timePart = now.toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            clockEl.textContent = `${datePart} ${timePart}`;
        }

        updateClock();      
        setInterval(updateClock, 1000); 


        
        const ctxTop = document.getElementById('topUsersChart').getContext('2d');
        const topLabels = (Array.isArray(topUsersData) && topUsersData.length) ? topUsersData.map(u => u.name) : [];
        const topValues = (Array.isArray(topUsersData) && topUsersData.length) ? topUsersData.map(u => u.count) : [];

        const topUsersChart = new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{ label: 'Usage records', data: topValues, backgroundColor: palette.primary, borderRadius: 6 }]
            },
            options: { plugins:{legend:{display:false}}, scales:{ y:{ beginAtZero:true } } }
        });

        const ctxDonut = document.getElementById('desksDonut').getContext('2d');
        const desksDonut = new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Occupied','Free'],
                datasets: [{ data: [occupiedDesks, Math.max(0, totalDesks - occupiedDesks)], backgroundColor: [palette.primary, palette.alt] }]
            },
            options: { plugins:{legend:{position:'bottom'}} }
        });

        const heatmapEl = document.getElementById('heatmap');

        function drawHeatmap(grid){
            heatmapEl.innerHTML = '';
            const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            const hours = 24;

            let max = 0;
            for(let d=0; d<7; d++){
                for(let h=0; h<hours; h++){
                    const v = (grid?.[d]?.[h]) ? Number(grid[d][h]) : 0;
                    if(v > max) max = v;
                }
            }

            for(let h=0; h<hours; h++){
                for(let d=0; d<7; d++){
                    const v = (grid?.[d]?.[h]) ? Number(grid[d][h]) : 0;
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    const t = max ? (v / max) : 0;
                    let bg;
                    if(t < 0.25) bg = '#f1f3f5';
                    else if(t < 0.5) bg = '#c9d8e0';
                    else if(t < 0.75) bg = '#00A8A8';
                    else bg = '#3A506B';
                    cell.style.background = bg;
                    cell.title = `${days[d]} ${h}:00 — ${v} usage records`;
                    heatmapEl.appendChild(cell);
                }
            }

            const hoursRow = document.createElement('div');
            hoursRow.style.display = 'grid';
            hoursRow.style.gridAutoFlow = 'column';
            hoursRow.style.gridTemplateColumns = `repeat(${hours}, auto)`;
            hoursRow.style.gap = '6px';
            hoursRow.style.marginTop = '8px';
            hoursRow.style.overflowX = 'auto';
            hoursRow.style.padding = '6px 4px';
            for(let h=0; h<hours; h++){
                const lbl = document.createElement('div');
                lbl.style.fontSize = '10px';
                lbl.style.width = '18px';
                lbl.style.textAlign = 'center';
                lbl.style.color = '#666';
                lbl.textContent = h;
                hoursRow.appendChild(lbl);
            }
            heatmapEl.parentNode.appendChild(hoursRow);
        }

        drawHeatmap(heatmapGrid);

        document.getElementById('exportBtn').addEventListener('click', () => {
            alert('Export — you can implement CSV/Excel export in a controller that returns a downloadable file.');
        });

    })();
    </script>
</body>
</html>
