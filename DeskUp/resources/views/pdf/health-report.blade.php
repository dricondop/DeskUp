<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Insights Report - DeskUp</title>
    <!-- External CSS for better maintainability -->
    <link rel="stylesheet" href="{{ asset('css/health-report.css') }}">
</head>
<body>
    <!-- PAGE 1: Header and User Info -->
    <div class="header">
        <h1>Health Insights Report</h1>
        <p class="subtitle">DeskUp - Your desk activity and wellness overview</p>
        <p class="meta-detail">Generated on: {{ $exportDate }}</p>
        <div style="margin-top: 4px; font-size: 7px; color: #9FB3C8;">
            Report ID: {{ substr(md5($user->id . $exportDate), 0, 8) }} | Period: {{ $periodLabel }}
        </div>
    </div>
    
    <!-- User Meta Information -->
    <div class="meta-info">
        <div class="meta-item">
            <span class="meta-label">USER</span>
            <span class="meta-value">{{ $user->name }}</span>
            <span class="meta-detail">{{ $user->email }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">REPORT PERIOD</span>
            <span class="meta-value">{{ $periodLabel }}</span>
            <span class="meta-detail">Range: {{ ucfirst($range) }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">DATA COVERAGE</span>
            <span class="meta-value">{{ $stats['records_count'] ?? 0 }} records</span>
            <span class="meta-detail">
                @if(($stats['records_count'] ?? 0) > 0)
                    Data available
                @else
                    No data collected
                @endif
            </span>
        </div>
    </div>
    
    <!-- Current Status Section -->
    <div class="section keep-together">
        <h2 class="section-title">Current Status</h2>
        <div class="compact-card live-status-card">
            <div class="card-title">LIVE DESK STATUS</div>
            <div class="card-value">{{ $liveStatus['mode'] }} — {{ $liveStatus['height_cm'] }} cm</div>
            <div class="card-subtitle">Last adjusted: {{ $liveStatus['last_adjusted'] }}</div>
            @php
                $height_cm = $liveStatus['height_cm'] ?? 0;
                $heightPercentage = min(100, max(1, ($height_cm / 150) * 100));
            @endphp
        </div>
    </div>
    
    <!-- Key Health Metrics Section -->
    <div class="section keep-together">
        <h2 class="section-title">Key Health Metrics</h2>
        <div class="metrics-grid">
            <!-- Posture Score -->
            @php
                $postureScore = $stats['standing_pct'] ?? 0;
                $postureClass = 'value-neutral';
                $progressClass = 'progress-bar-poor';
                if ($postureScore >= 80) {
                    $postureClass = 'value-excellent';
                    $progressClass = 'progress-bar-excellent';
                } elseif ($postureScore >= 50) {
                    $postureClass = 'value-good';
                    $progressClass = 'progress-bar-good';
                } elseif ($postureScore >= 20) {
                    $postureClass = 'value-fair';
                    $progressClass = 'progress-bar-fair';
                } else {
                    $postureClass = 'value-poor';
                    $progressClass = 'progress-bar-poor';
                }
            @endphp
            <div class="metric-card">
                <div class="card-title">POSTURE SCORE</div>
                <div class="card-value {{ $postureClass }}">{{ $postureScore }}/100</div>
                <div class="card-subtitle">Based on standing percentage</div>
                <div class="progress-container">
                    <div class="progress-bar {{ $progressClass }}" style="width: {{ max(1, $postureScore) }}%"></div>
                </div>
            </div>
            
            <!-- Active Hours -->
            @php
                $activeHours = $stats['active_hours'] ?? 0;
                $activeClass = 'value-neutral';
                if ($activeHours >= 8) {
                    $activeClass = 'value-excellent';
                } elseif ($activeHours >= 6) {
                    $activeClass = 'value-good';
                } elseif ($activeHours >= 4) {
                    $activeClass = 'value-fair';
                } else {
                    $activeClass = 'value-poor';
                }
            @endphp
            <div class="metric-card">
                <div class="card-title">ACTIVE HOURS</div>
                <div class="card-value {{ $activeClass }}">{{ $activeHours }}</div>
                <div class="card-subtitle">Total desk activity time</div>
                <span class="status-indicator optimal">
                    @if($activeHours >= 6)
                        ⭐ Optimal activity level
                    @else
                        ⚡ Could be improved
                    @endif
                </span>
            </div>
            
            <!-- Standing Ratio -->
            @php
                $standingRatio = $stats['standing_pct'] ?? 0;
                $standingClass = 'value-neutral';
                $standingProgressClass = 'progress-bar-poor';
                if ($standingRatio >= 40) {
                    $standingClass = 'value-excellent';
                    $standingProgressClass = 'progress-bar-excellent';
                } elseif ($standingRatio >= 30) {
                    $standingClass = 'value-good';
                    $standingProgressClass = 'progress-bar-good';
                } elseif ($standingRatio >= 15) {
                    $standingClass = 'value-fair';
                    $standingProgressClass = 'progress-bar-fair';
                } else {
                    $standingClass = 'value-poor';
                    $standingProgressClass = 'progress-bar-poor';
                }
            @endphp
            <div class="metric-card">
                <div class="card-title">STANDING RATIO</div>
                <div class="card-value {{ $standingClass }}">{{ $standingRatio }}%</div>
                <div class="card-subtitle">vs {{ $stats['sitting_pct'] ?? 0 }}% sitting</div>
                <span class="status-indicator @if($standingRatio >= 30) good @else improve @endif">
                    @if($standingRatio >= 30)
                        👍 Good balance
                    @else
                        📈 Aim for 30%+
                    @endif
                </span>
                <div class="progress-container">
                    <div class="progress-bar {{ $standingProgressClass }}" style="width: {{ max(1, min(100, ($standingRatio / 40) * 100)) }}%"></div>
                </div>
            </div>
            
            <!-- Breaks -->
            @php
                $breaks = $stats['breaks_per_day'] ?? 0;
                $breaksClass = 'value-neutral';
                $breaksProgressClass = 'progress-bar-poor';
                if ($breaks >= 5) {
                    $breaksClass = 'value-excellent';
                    $breaksProgressClass = 'progress-bar-excellent';
                } elseif ($breaks >= 3) {
                    $breaksClass = 'value-good';
                    $breaksProgressClass = 'progress-bar-good';
                } elseif ($breaks >= 2) {
                    $breaksClass = 'value-fair';
                    $breaksProgressClass = 'progress-bar-fair';
                } else {
                    $breaksClass = 'value-poor';
                    $breaksProgressClass = 'progress-bar-poor';
                }
            @endphp
            <div class="metric-card">
                <div class="card-title">BREAKS</div>
                <div class="card-value {{ $breaksClass }}">{{ $breaks }}</div>
                <div class="card-subtitle">Sit-stand transitions</div>
                <span class="status-indicator @if($breaks >= 3) good @else improve @endif">
                    @if($breaks >= 3)
                        ✅ Good frequency
                    @else
                        🔄 More breaks recommended
                    @endif
                </span>
                <div class="progress-container">
                    <div class="progress-bar {{ $breaksProgressClass }}" style="width: {{ max(1, min(100, ($breaks / 5) * 100)) }}%"></div>
                </div>
            </div>
            
            <!-- Calories -->
            @php
                $calories = $stats['calories_per_day'] ?? 0;
                $caloriesClass = 'value-neutral';
                if ($calories >= 50) {
                    $caloriesClass = 'value-excellent';
                } elseif ($calories >= 30) {
                    $caloriesClass = 'value-good';
                } elseif ($calories >= 15) {
                    $caloriesClass = 'value-fair';
                } else {
                    $caloriesClass = 'value-poor';
                }
            @endphp
            <div class="metric-card">
                <div class="card-title">CALORIES</div>
                <div class="card-value {{ $caloriesClass }}">{{ $calories }} kcal</div>
                <div class="card-subtitle">Estimated from standing time</div>
                <span class="status-indicator">
                    Equivalent to {{ round($calories / 100, 1) }} hours walking
                </span>
            </div>
            
            <!-- Height Average -->
            @php
                $heightAvg = $stats['avg_height_mm'] ?? 0;
                $heightClass = 'value-neutral';
            @endphp
            <div class="metric-card">
                <div class="card-title">HEIGHT AVERAGE</div>
                <div class="card-value {{ $heightClass }}">{{ $heightAvg }} mm</div>
                <div class="card-subtitle">Overall desk height</div>
                <span class="status-indicator">
                    Sit: {{ $stats['avg_sit_height_cm'] ?? 72 }}cm | Stand: {{ $stats['avg_stand_height_cm'] ?? 110 }}cm
                </span>
            </div>
        </div>
    </div>

    <!-- PAGE 2: Visual Analytics -->
    <div class="charts-page">
        <h2 class="section-title">Visual Analytics</h2>
        
        <div class="charts-grid">
            <!-- Chart 1: Time Distribution -->
            @if(isset($charts['timeDistribution']))
                <div class="chart-container">
                    <div class="chart-title">Time Distribution</div>
                    <div class="chart-image-container">
                        <img src="{{ $charts['timeDistribution'] }}" alt="Time Distribution Chart" class="chart-image">
                    </div>
                    <div class="chart-description">
                        Sitting: {{ $stats['sitting_pct'] ?? 0 }}% | Standing: {{ $stats['standing_pct'] ?? 0 }}%
                    </div>
                </div>
            @else
                <div class="chart-container">
                    <div class="chart-title">Time Distribution</div>
                    <div class="chart-image-container">
                        <div class="chart-placeholder">
                            Chart not available<br>
                            <small>Sitting: {{ $stats['sitting_pct'] ?? 0 }}% | Standing: {{ $stats['standing_pct'] ?? 0 }}%</small>
                        </div>
                    </div>
                    <div class="chart-description">
                        Sitting vs Standing time distribution
                    </div>
                </div>
            @endif

            <!-- Chart 2: Absolute Time -->
            @if(isset($charts['timeAbsolute']))
                <div class="chart-container">
                    <div class="chart-title">Absolute Time</div>
                    <div class="chart-image-container">
                        <img src="{{ $charts['timeAbsolute'] }}" alt="Absolute Time Chart" class="chart-image">
                    </div>
                    <div class="chart-description">
                        Total: {{ ($stats['sitting_hours'] ?? 0) + ($stats['standing_hours'] ?? 0) }} hours
                    </div>
                </div>
            @else
                <div class="chart-container">
                    <div class="chart-title">Absolute Time</div>
                    <div class="chart-image-container">
                        <div class="chart-placeholder">
                            Chart not available<br>
                            <small>Total: {{ ($stats['sitting_hours'] ?? 0) + ($stats['standing_hours'] ?? 0) }} hours</small>
                        </div>
                    </div>
                    <div class="chart-description">
                        Absolute time spent sitting and standing
                    </div>
                </div>
            @endif

            <!-- Chart 3: Posture Score Trend -->
            @if(isset($charts['postureScore']))
                <div class="chart-container">
                    <div class="chart-title">Posture Score Trend</div>
                    <div class="chart-image-container">
                        <img src="{{ $charts['postureScore'] }}" alt="Posture Score Chart" class="chart-image">
                    </div>
                    <div class="chart-description">
                        Average score: {{ round(array_sum($chartData['posture_scores'] ?? [0]) / max(1, count($chartData['posture_scores'] ?? [0])), 1) }}
                    </div>
                </div>
            @else
                <div class="chart-container">
                    <div class="chart-title">Posture Score Trend</div>
                    <div class="chart-image-container">
                        <div class="chart-placeholder">
                            Chart not available<br>
                            <small>Average: {{ round(array_sum($chartData['posture_scores'] ?? [0]) / max(1, count($chartData['posture_scores'] ?? [0])), 1) }}</small>
                        </div>
                    </div>
                    <div class="chart-description">
                        Posture score progression over time
                    </div>
                </div>
            @endif

            <!-- Chart 4: Height Overview -->
            @if(isset($charts['heightAverage']))
                <div class="chart-container">
                    <div class="chart-title">Height Overview</div>
                    <div class="chart-image-container">
                        <img src="{{ $charts['heightAverage'] }}" alt="Height Average Chart" class="chart-image">
                    </div>
                    <div class="chart-description">
                        Sitting vs Standing height comparison
                    </div>
                </div>
            @else
                <div class="chart-container">
                    <div class="chart-title">Height Overview</div>
                    <div class="chart-image-container">
                        <div class="chart-placeholder">
                            Chart not available<br>
                            <small>Sitting vs Standing height</small>
                        </div>
                    </div>
                    <div class="chart-description">
                        Desk height comparison between postures
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Data Summary Section -->
        <div class="section">
            <h2 class="section-title">Data Summary for {{ $periodLabel }}</h2>
            <div class="data-summary">
                <div class="data-point">
                    <span class="data-label">Time Periods Analyzed:</span>
                    <span class="data-value">{{ count($chartData['labels'] ?? []) }}</span>
                </div>
                
                <div class="data-point">
                    <span class="data-label">Highest Posture Score:</span>
                    <span class="data-value">{{ max($chartData['posture_scores'] ?? [0]) }}/100</span>
                </div>
                
                <div class="data-point">
                    <span class="data-label">Lowest Posture Score:</span>
                    <span class="data-value">{{ min($chartData['posture_scores'] ?? [0]) }}/100</span>
                </div>
                
                <div class="data-point">
                    <span class="data-label">Average Sitting Height:</span>
                    <span class="data-value">{{ round(array_sum($chartData['avg_sit_heights'] ?? [0]) / max(1, count($chartData['avg_sit_heights'] ?? [0])), 1) }} cm</span>
                </div>
                
                <div class="data-point">
                    <span class="data-label">Average Standing Height:</span>
                    <span class="data-value">{{ round(array_sum($chartData['avg_stand_heights'] ?? [0]) / max(1, count($chartData['avg_stand_heights'] ?? [0])), 1) }} cm</span>
                </div>
            </div>
        </div>
        
        <!-- Detailed Statistics Section -->
        <div class="section">
            <h2 class="section-title">Detailed Statistics</h2>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>METRIC</th>
                        <th>VALUE</th>
                        <th>DESCRIPTION</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total Records</strong></td>
                        <td>{{ $stats['records_count'] ?? 0 }}</td>
                        <td>Number of data points collected</td>
                    </tr>
                    <tr>
                        <td><strong>Sitting Time</strong></td>
                        <td>{{ $stats['sitting_hours'] ?? 0 }} hours</td>
                        <td>{{ $stats['sitting_pct'] ?? 0 }}% of total time</td>
                    </tr>
                    <tr>
                        <td><strong>Standing Time</strong></td>
                        <td>{{ $stats['standing_hours'] ?? 0 }} hours</td>
                        <td>{{ $stats['standing_pct'] ?? 0 }}% of total time</td>
                    </tr>
                    <tr>
                        <td><strong>Average Sitting Height</strong></td>
                        <td>{{ $stats['avg_sit_height_cm'] ?? 72 }} cm</td>
                        <td>Typical desk height when sitting</td>
                    </tr>
                    <tr>
                        <td><strong>Average Standing Height</strong></td>
                        <td>{{ $stats['avg_stand_height_cm'] ?? 110 }} cm</td>
                        <td>Typical desk height when standing</td>
                    </tr>
                    <tr>
                        <td><strong>Total Activations</strong></td>
                        <td>{{ $stats['total_activations'] ?? 0 }}</td>
                        <td>Desk motor activations</td>
                    </tr>
                    <tr>
                        <td><strong>Sit-Stand Cycles</strong></td>
                        <td>{{ $stats['total_sit_stand'] ?? 0 }}</td>
                        <td>Full height adjustment cycles</td>
                    </tr>
                    <tr>
                        <td><strong>Error Count</strong></td>
                        <td>{{ $stats['error_count'] ?? 0 }}</td>
                        <td>Position or overload errors detected</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>DeskUp Health Insights Report</strong> • Generated automatically • User ID: {{ $user->id }}</p>
            <p>This report is based on data collected from your desk usage between {{ $periodLabel }}.</p>
            <p style="margin-top: 4px; font-size: 6px; color: #9FB3C8;">
                Report generated: {{ $exportDate }} | 
                Data points: {{ $stats['records_count'] ?? 0 }} | 
                © {{ date('Y') }} DeskUp. All rights reserved.
            </p>
            <div style="margin-top: 4px; font-size: 6px; color: #D0DAE3; border-top: 1px solid #F0F3F6; padding-top: 4px;">
                Confidential Report • Do not distribute without permission
            </div>
        </div>
    </div>
</body>
</html>