<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Statistics Report - DeskUp</title>
    <!-- External CSS for better maintainability -->
    <link rel="stylesheet" href="{{ asset('css/admin-stats-report.css') }}">

</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Admin Statistics Report</h1>
        <p class="subtitle">DeskUp - System-wide desk usage analytics</p>
        <p class="meta-detail">Generated on: {{ $exportDate }}</p>
        <div style="margin-top: 4px; font-size: 7px; color: #9FB3C8;">
            Report ID: {{ substr(md5($exportDate), 0, 8) }} | Period: Last {{ $daysAnalyzed }} days
        </div>
    </div>

    <!-- Meta Information -->
    <div class="meta-info">
        <div class="meta-item">
            <span class="meta-label">PERIOD</span>
            <span class="meta-value">{{ $sinceDate }} to {{ now()->format('d/m/Y') }}</span>
            <span class="meta-detail">{{ $daysAnalyzed }} days analyzed</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">DATA COVERAGE</span>
            <span class="meta-value">{{ $totalRecords }} records</span>
            <span class="meta-detail">{{ $distinctUsers }} distinct users</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">SYSTEM STATUS</span>
            <span class="meta-value">{{ $totalDesks }} total desks</span>
            <span class="meta-detail">{{ $users->count() }} registered users</span>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="section">
        <h2 class="section-title">System Overview</h2>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="card-title">TOTAL DESKS</div>
                <div class="card-value">{{ $totalDesks }}</div>
                <div class="card-subtitle">Available in system</div>
            </div>
            
            <div class="metric-card">
                <div class="card-title">OCCUPIED DESKS</div>
                <div class="card-value value-{{ $occupiedDesks > $totalDesks/2 ? 'excellent' : 'good' }}">{{ $occupiedDesks }}</div>
                <div class="card-subtitle">{{ round(($occupiedDesks/$totalDesks)*100) }}% occupancy rate</div>
            </div>
            
            <div class="metric-card">
                <div class="card-title">AVAILABLE DESKS</div>
                <div class="card-value">{{ $availableDesks }}</div>
                <div class="card-subtitle">Ready for assignment</div>
            </div>
            
            <div class="metric-card">
                <div class="card-title">AVG SESSION TIME</div>
                <div class="card-value value-{{ $avgSession > 30 ? 'excellent' : ($avgSession > 15 ? 'good' : 'fair') }}">{{ $avgSession }} min</div>
                <div class="card-subtitle">Per user per day</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="section">
        <h2 class="section-title">Usage Analytics</h2>
        
        <div class="charts-grid">
            <!-- Top Users Chart -->
            @if(isset($charts['topUsers']))
                <div class="chart-container">
                    <div class="chart-title">Top Users by Usage</div>
                    <div>
                        <img src="{{ $charts['topUsers'] }}" alt="Top Users Chart" class="chart-image">
                    </div>
                </div>
            @else
                <div class="chart-container">
                    <div class="chart-title">Top Users by Usage</div>
                    <div class="chart-placeholder">
                        @if(!empty($topUsers))
                            <table style="width:100%; font-size:8px;">
                                @foreach($topUsers as $user)
                                <tr>
                                    <td>{{ $user['name'] }}</td>
                                    <td style="text-align:right">{{ $user['count'] }} records</td>
                                </tr>
                                @endforeach
                            </table>
                        @else
                            No user data available
                        @endif
                    </div>
                </div>
            @endif

            <!-- Desk Occupancy Chart -->
            @if(isset($charts['deskOccupancy']))
                <div class="chart-container">
                    <div class="chart-title">Desk Occupancy</div>
                    <div>
                        <img src="{{ $charts['deskOccupancy'] }}" alt="Desk Occupancy Chart" class="chart-image">
                    </div>
                </div>
            @else
                <div class="chart-container">
                    <div class="chart-title">Desk Occupancy</div>
                    <div class="chart-placeholder">
                        Occupied: {{ $occupiedDesks }}<br>
                        Available: {{ $availableDesks }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Desks List -->
    <div class="section">
        <h2 class="section-title">Desk Inventory</h2>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>DESK NAME</th>
                    <th>STATUS</th>
                    <th>HEIGHT</th>
                    <th>LAST UPDATED</th>
                </tr>
            </thead>
            <tbody>
                @foreach($desks as $desk)
                <tr>
                    <td><strong>{{ $desk->name }}</strong></td>
                    <td>
                        @if($desk->status === 'OK')
                            <span style="color:#4CAF50;">●</span> OK
                        @else
                            <span style="color:#FF4444;">●</span> {{ $desk->status }}
                        @endif
                    </td>
                    <td>{{ $desk->height }} cm</td>
                    <td>{{ $desk->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Heatmap Visualization - Tabular Version -->
    <div class="section page-break">
        <h2 class="section-title">Usage Heatmap (Last {{ $daysAnalyzed }} Days)</h2>
        
        <div style="margin: 15px 0; overflow-x: auto;">
            <table style="border-collapse: collapse; font-size: 8px; width: 100%;">
                <thead>
                    <tr>
                        <th style="background: #F4F7F8; padding: 6px; text-align: left; border: 1px solid #E0E6EB;">Day/Hour</th>
                        @for($hour = 0; $hour < 24; $hour++)
                            <th style="background: #F4F7F8; padding: 4px; text-align: center; border: 1px solid #E0E6EB; font-weight: 600;">
                                {{ $hour }}:00
                            </th>
                        @endfor
                        <th style="background: #F4F7F8; padding: 6px; text-align: center; border: 1px solid #E0E6EB; font-weight: 600;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $maxValue = 0;
                        foreach($heatmapGrid as $day) {
                            foreach($day as $hour) {
                                if($hour > $maxValue) $maxValue = $hour;
                            }
                        }
                    @endphp
                    
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                        @php $dayIndex = $loop->index; @endphp
                        <tr>
                            <td style="padding: 6px; border: 1px solid #E0E6EB; font-weight: 600; background: #F8F9FA;">
                                {{ $dayName }}
                            </td>
                            
                            @for($hour = 0; $hour < 24; $hour++)
                                @php
                                    $value = $heatmapGrid[$dayIndex][$hour] ?? 0;
                                    $intensity = $maxValue > 0 ? ($value / $maxValue) : 0;
                                    
                                    if($intensity == 0) $bgColor = '#f8f9fa';
                                    elseif($intensity < 0.25) $bgColor = '#e9ecef';
                                    elseif($intensity < 0.5) $bgColor = '#9fb3c8';
                                    elseif($intensity < 0.75) $bgColor = '#00A8A8';
                                    else $bgColor = '#3A506B';
                                    
                                    $textColor = $intensity > 0.5 ? 'white' : '#333';
                                @endphp
                                <td style="
                                    padding: 6px 2px;
                                    border: 1px solid #E0E6EB;
                                    text-align: center;
                                    background: {{ $bgColor }};
                                    color: {{ $textColor }};
                                    font-weight: {{ $value > 0 ? '600' : '400' }};
                                    min-width: 24px;
                                ">
                                    {{ $value > 0 ? $value : '-' }}
                                </td>
                            @endfor
                            
                            @php
                                $dayTotal = array_sum($heatmapGrid[$dayIndex] ?? []);
                            @endphp
                            <td style="
                                padding: 6px;
                                border: 1px solid #E0E6EB;
                                text-align: center;
                                background: {{ $dayTotal > 0 ? 'rgba(0,168,168,0.1)' : '#f8f9fa' }};
                                font-weight: 600;
                            ">
                                {{ $dayTotal }}
                            </td>
                        </tr>
                    @endforeach
                    
                    <!-- Hourly totals row -->
                    <tr>
                        <td style="padding: 6px; border: 1px solid #E0E6EB; font-weight: 600; background: #F8F9FA;">
                            Hour Total
                        </td>
                        @for($hour = 0; $hour < 24; $hour++)
                            @php
                                $hourTotal = 0;
                                for($d = 0; $d < 7; $d++) {
                                    $hourTotal += $heatmapGrid[$d][$hour] ?? 0;
                                }
                            @endphp
                            <td style="
                                padding: 6px 2px;
                                border: 1px solid #E0E6EB;
                                text-align: center;
                                background: {{ $hourTotal > 0 ? 'rgba(58,80,107,0.1)' : '#f8f9fa' }};
                                font-weight: 600;
                            ">
                                {{ $hourTotal }}
                            </td>
                        @endfor
                        <td style="
                            padding: 6px;
                            border: 1px solid #E0E6EB;
                            text-align: center;
                            background: rgba(58,80,107,0.15);
                            font-weight: 700;
                            font-size: 9px;
                        ">
                            {{ $totalRecords }}
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Legend -->
            <div style="display: flex; align-items: center; justify-content: center; margin-top: 15px; font-size: 7px;">
                <span style="margin-right: 10px; color: #666;">Intensity:</span>
                <div style="display: flex; gap: 4px; align-items: center;">
                    <div style="width: 16px; height: 16px; background: #f8f9fa; border: 1px solid #ddd;"></div>
                    <span>0</span>
                    
                    <div style="width: 16px; height: 16px; background: #e9ecef; border: 1px solid #ddd;"></div>
                    <span>Low</span>
                    
                    <div style="width: 16px; height: 16px; background: #9fb3c8; border: 1px solid #ddd;"></div>
                    <span>Medium</span>
                    
                    <div style="width: 16px; height: 16px; background: #00A8A8; border: 1px solid #ddd;"></div>
                    <span>High</span>
                    
                    <div style="width: 16px; height: 16px; background: #3A506B; border: 1px solid #ddd;"></div>
                    <span>Peak</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Summary -->
    <div class="section">
        <h2 class="section-title">Users Summary</h2>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>USER</th>
                    <th>EMAIL</th>
                    <th>DESK ASSIGNED</th>
                    <th>LAST ACTIVITY</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->assigned_desk_id)
                            Desk #{{ $user->assigned_desk_id }}
                        @else
                            <span style="color: #9FB3C8;">Not assigned</span>
                        @endif
                    </td>
                    <td>{{ $user->updated_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>DeskUp Admin Statistics Report</strong> • Generated automatically</p>
        <p>This report shows system-wide desk usage analytics for the period {{ $sinceDate }} to {{ now()->format('d/m/Y') }}</p>
        <p style="margin-top: 4px; font-size: 6px; color: #9FB3C8;">
            Report generated: {{ $exportDate }} | 
            Total desks: {{ $totalDesks }} | 
            Total users: {{ $users->count() }} | 
            © {{ date('Y') }} DeskUp. All rights reserved.
        </p>
        <div style="margin-top: 4px; font-size: 6px; color: #D0DAE3; border-top: 1px solid #F0F3F6; padding-top: 4px;">
            Confidential Admin Report • Internal use only
        </div>
    </div>
</body>
</html>