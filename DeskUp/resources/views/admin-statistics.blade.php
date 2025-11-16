<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Statistics — DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/health.css') }}"> {{-- reutilizamos health.css --}}
    <style>
        
        .admin-container { display: flex; flex-direction: column; gap: 28px; padding: 24px 28px; max-width: 1200px; margin: 0 auto; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; align-items: stretch; }
        .metric-card .metric-title { font-size: 1.05rem; color: #3A506B; font-weight: 700; } /* dark blue title */
        .metric-card .metric-value { font-size: 1.6rem; font-weight: 700; color: #3A506B; }
        .metric-badge { display: inline-block; padding: 6px 10px; border-radius: 10px; font-size: 0.8rem; background: rgba(58,80,107,0.08); color: #3A506B; }

        .panels { display: grid; grid-template-columns: 1fr 520px; gap: 20px; align-items: start; }
        .card.chart-card { min-height: 260px; display: flex; flex-direction: column; padding: 14px; }
        .chart-container { flex: 1; min-height: 160px; display:flex; align-items:center; justify-content:center; }
        .small-list { list-style: none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
        .desk-list { max-height: 260px; overflow:auto; padding-right:6px; }

       
        .heatmap-wrap { display: flex; gap: 12px; align-items:flex-start; }
        .heatmap-legend { display:flex; gap:8px; align-items:center; margin-top:6px; font-size:0.85rem; color:#555; }
        .heatmap {
            --cell-size: 18px;
            display: grid;
            grid-template-rows: repeat(7, var(--cell-size));
            grid-auto-flow: column;
            gap: 6px;
            padding: 8px;
            background:#fff;
            border-radius:8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .heatmap .cell {
            width: var(--cell-size);
            height: var(--cell-size);
            border-radius: 3px;
            background: #f1f3f5;
        }
        .heatmap-row-labels { display:flex; flex-direction:column; gap:6px; margin-right:8px; font-size:0.85rem; color:#333; }

        
        .avg-panel .desk-row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 0; border-bottom:1px dashed #eee; }
        .avg-panel .desk-row:last-child { border-bottom: none; }
        .avg-badge { font-size:0.85rem; padding:6px 8px; border-radius:6px; background: rgba(0,168,168,0.08); color:#00A8A8; font-weight:600; }

        
        .user-actions { display:flex; gap:8px; align-items:center; }
        .btn-ghost { background:transparent; border:1px solid rgba(0,0,0,0.08); padding:6px 10px; border-radius:8px; cursor:pointer; }
        .btn-danger { background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:8px; cursor:pointer; }

        
        @media (max-width: 960px) {
            .panels { grid-template-columns: 1fr; }
            .chart-grid { grid-template-columns: 1fr; }
        }

    </style>

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
                <nav><span class="badge">DeskUp Admin</span></nav>
            </div>
        </header>

        <main class="container admin-container" role="main">
            
            <section class="summary" aria-label="Resumen de escritorios">
                <article class="card metric-card" aria-labelledby="occupied-title">
                    <div class="metric">
                        <h3 id="occupied-title" class="metric-title">Occupied Desks</h3>
                        <p id="desks-occupied" class="metric-value">—</p>
                        <p class="muted small">Ammount of occupied desks</p>
                    </div>
                </article>

                <article class="card metric-card" aria-labelledby="available-title">
                    <div class="metric">
                        <h3 id="available-title" class="metric-title">Available Desks</h3>
                        <p id="desks-free" class="metric-value">—</p>
                        <p class="muted small">Available desks</p>
                    </div>
                </article>

                <article class="card metric-card" aria-labelledby="avg-session-title">
                    <div class="metric">
                        <h3 id="avg-session-title" class="metric-title">Average Session Time</h3>
                        <p id="avg-session" class="metric-value">— min</p>
                        <p class="muted small">Average time per session (placeholder)</p>
                    </div>
                </article>
            </section>

            
            <section class="panels" aria-label="Panels">
                <div>
                    
                    <section class="card chart-card" aria-labelledby="usage-title">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <h2 id="usage-title" class="chart-title">User Desk Usage</h2>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <button class="btn-ghost" id="refreshDataBtn" title="Refresh placeholders">Refresh</button>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div style="min-height:180px;">
                                <h4 style="margin:6px 0 8px 0; font-size:0.95rem; color:#3A506B;">Top Users (hours)</h4>
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
                            <p class="muted small" style="margin:0">Intensity = Number of sessions</p>
                        </div>

                        <div style="display:flex; gap:16px; margin-top:12px; align-items:flex-start;">
                            <div class="heatmap-row-labels" aria-hidden="true">
                                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                            </div>

                            <div>
                                <div id="heatmap" class="heatmap" role="img" aria-label="Heatmap placeholder"></div>

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
                          
                        </div>
                        <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                            <button class="btn-ghost" id="exportBtn">Export (placeholder)</button>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="users-list-title">
                        <h3 id="users-list-title" style="margin:0 0 8px 0;">Users</h3>

                        <ul class="small-list" id="usersList" aria-live="polite">
                            
                        </ul>

                        <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                            <button class="btn-ghost" id="addUserPlaceholder">Add user (placeholder)</button>
                        </div>
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

    
    <script>
    (function(){
        
        const palette = { primary: '#3A506B', accent: '#00A8A8', alt: '#9FB3C8', light: '#f1f3f5' };

        
        let placeholder = {
            totalDesks: 20,
            occupied: 14,
            avgSessionMins: 52,
            usersTop: [
                { name: 'Ricondo', hours: 42 },
                { name: 'Carlos', hours: 38 },
                { name: 'Manuel', hours: 30 },
                { name: 'Bjarne', hours: 28 }
            ],
            usersLow: [
                { name: 'Elena', hours: 5 }, { name: 'Tom', hours: 3 }, { name: 'Laura', hours: 2 }
            ],
            desks: Array.from({length:20}, (_,i) => ({
                id: i+1,
                name: 'Desk ' + (i+1),
                avgSession: Math.round(30 + Math.random()*60), 
                status: (i < 14) ? 'occupied' : 'free'
            })),
            
            heatmap: (function(){
                const days = 7, hours = 24;
                const grid = Array.from({length:days}, ()=> Array.from({length:hours}, ()=> Math.round(Math.random()*6)));
                
                for(let d=0; d<days; d++){
                    for(let h=8; h<=18; h++){
                        grid[d][h] = Math.round(Math.random()*8);
                    }
                }
                return grid;
            })()
        };

        
        document.getElementById('desks-occupied').textContent = placeholder.occupied;
        document.getElementById('desks-free').textContent = (placeholder.totalDesks - placeholder.occupied);
        document.getElementById('avg-session').textContent = placeholder.avgSessionMins + ' min';

        
        const deskListEl = document.getElementById('deskList');
        deskListEl.innerHTML = '';
        placeholder.desks.forEach(d => {
            const row = document.createElement('div');
            row.className = 'desk-row';
            row.innerHTML = `
                <div style="display:flex; gap:10px; align-items:center;">
                    <strong style="min-width:90px; color:#3A506B;">${d.name}</strong>
                    <small class="muted small">Status: ${d.status}</small>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <span class="avg-badge">${d.avgSession} min</span>
                </div>
            `;
            deskListEl.appendChild(row);
        });

        
        const usersListEl = document.getElementById('usersList');
        const sampleUsers = [
            { name: 'Carlos Herrero', desk: 'Desk 2' },
            { name: 'Manuel Magaña', desk: 'Desk 5' },
            { name: 'Ricondo', desk: 'Desk 1' }
        ];
        function renderUsers(){
            usersListEl.innerHTML = '';
            sampleUsers.forEach((u, idx) => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#3A506B">${u.name}</strong>
                            <div style="font-size:0.85rem; color:#666;">${u.desk}</div>
                        </div>
                        <div class="user-actions">
                            <select aria-label="Assign desk" class="desk-select" data-idx="${idx}">
                                ${placeholder.desks.map(d => `<option value="${d.name}" ${d.name===u.desk ? 'selected' : ''}>${d.name}</option>`).join('')}
                            </select>
                            <label style="display:flex; align-items:center; gap:6px;">
                                <input type="checkbox" class="disable-toggle" data-idx="${idx}" /> Disable
                            </label>
                            <button class="btn-danger remove-user" data-idx="${idx}">Remove</button>
                        </div>
                    </div>
                `;
                usersListEl.appendChild(li);
            });

            
            document.querySelectorAll('.desk-select').forEach(sel => {
                sel.addEventListener('change', (e) => {
                    const i = e.target.dataset.idx;
                    sampleUsers[i].desk = e.target.value;
                    
                });
            });
            document.querySelectorAll('.remove-user').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const i = Number(e.target.dataset.idx);
                    sampleUsers.splice(i,1);
                    renderUsers();
                });
            });
            document.querySelectorAll('.disable-toggle').forEach(cb => {
                cb.addEventListener('change', (e) => {
                    const i = Number(e.target.dataset.idx);
                    const checked = e.target.checked;
                    
                    e.target.closest('li').style.opacity = checked ? 0.5 : 1;
                });
            });
        }
        renderUsers();

        
        const ctxTop = document.getElementById('topUsersChart').getContext('2d');
        const topUsersChart = new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: placeholder.usersTop.map(u => u.name),
                datasets: [{ label: 'Hours', data: placeholder.usersTop.map(u => u.hours), backgroundColor: palette.primary, borderRadius:6 }]
            },
            options: { plugins:{legend:{display:false}}, scales:{ y:{ beginAtZero:true } } }
        });

        const ctxDonut = document.getElementById('desksDonut').getContext('2d');
        const desksDonut = new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Occupied','Free'],
                datasets: [{ data: [placeholder.occupied, placeholder.totalDesks - placeholder.occupied], backgroundColor: [palette.primary, palette.alt] }]
            },
            options: { plugins:{legend:{position:'bottom'}} }
        });

        
        const heatmapEl = document.getElementById('heatmap');
        function drawHeatmap(grid){
            heatmapEl.innerHTML = ''; 
            const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            const hours = 24;
            
            
            let max = 0;
            grid.forEach(row => row.forEach(v => max = Math.max(max, v)));

            
            for(let h=0; h<hours; h++){
                
                for(let d=0; d<days.length; d++){
                    const v = grid[d][h];
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    
                    const t = max ? (v / max) : 0;
                    
                    let bg;
                    if(t < 0.25) bg = '#f1f3f5';
                    else if(t < 0.5) bg = '#c9d8e0';
                    else if(t < 0.75) bg = '#00A8A8';
                    else bg = '#3A506B';
                    cell.style.background = bg;
                    cell.title = `Day ${d+1} - ${h}:00 — ${v} sessions`;
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
        drawHeatmap(placeholder.heatmap);

        
        document.getElementById('refreshDataBtn').addEventListener('click', ()=> {
            
            placeholder.occupied = Math.max(2, Math.round(placeholder.totalDesks * (0.4 + Math.random()*0.4)));
            placeholder.avgSessionMins = Math.round(30 + Math.random()*60);
            
            document.getElementById('desks-occupied').textContent = placeholder.occupied;
            document.getElementById('desks-free').textContent = placeholder.totalDesks - placeholder.occupied;
            document.getElementById('avg-session').textContent = placeholder.avgSessionMins + ' min';
            
            desksDonut.data.datasets[0].data = [placeholder.occupied, placeholder.totalDesks - placeholder.occupied];
            desksDonut.update();
            
            placeholder.heatmap = placeholder.heatmap.map(row => row.map(() => Math.round(Math.random()*8)));
            drawHeatmap(placeholder.heatmap);
        });

     
        document.getElementById('exportBtn').addEventListener('click', ()=> alert('Export placeholder — integrar con backend para CSV/Excel.'));

        
        document.getElementById('addUserPlaceholder').addEventListener('click', () => {
            sampleUsers.push({ name: 'New User ' + (sampleUsers.length+1), desk: 'Desk 1' });
            renderUsers();
        });

    })();
    </script>
</body>
</html>
