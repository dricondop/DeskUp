<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | DeskUp</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/profile.js') }}" defer></script>
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
    <a href="{{ url()->previous() }}" class="back-button">
        <img src="{{ asset('assets/back.png') }}" alt="Back">
    </a>

    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="header-content">
                <div class="profile-main">
                    <img src="{{ $userProfile->profile_picture ?? asset('assets/default-avatar.png') }}" alt="Profile Picture" class="profile-picture">
                    <div class="profile-info">
                        <h1>{{ $user->name ?? 'Guest' }}</h1>
                        <p class="position">
                            @if($isAdmin)
                                Admin
                            @else
                                User
                            @endif
                        </p>
                        <p class="location">
                            <svg class="location-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            {{ $userProfile->location ?? 'Location not set' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <!-- Personal info -->
            <div class="info-card">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                    @if($user)
                    <a href="{{ route('profile.edit') }}" class="edit-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                        Edit
                    </a>
                    @endif
                </div>
                <div class="personal-info-grid">
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $user->email ?? 'Not available' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value">{{ $userProfile->phone ?? 'Not available' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value">
                            @if($userProfile && $userProfile->date_of_birth)
                                {{ \Carbon\Carbon::parse($userProfile->date_of_birth)->format('F j, Y') }}
                            @else
                                Not available
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">User ID</span>
                        <span class="info-value">{{ $user->id ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Assigned Desk</span>
                        <span class="info-value">
                            @if($user && $user->assigned_desk_id)
                                Desk {{ $user->assigned_desk_id }}
                            @else
                                Not assigned
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Since</span>
                        <span class="info-value">
                            @if($user)
                                {{ \Carbon\Carbon::parse($user->created_at)->format('F j, Y') }}
                            @else
                                Not available
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Type</span>
                        <span class="info-value">
                            @if($isAdmin)
                                <span style="color: #4CAF50;">Administrator</span>
                            @else
                                Regular User
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Health Insights Dashboard -->
            <div class="info-card health-insights-card">
                <div class="card-header">
                    <h2 class="card-title">Health Insights</h2>
                    <a href="{{ route('health') }}" class="edit-btn">
                        View Details
                    </a>
                </div>
            <div class="health-insights-horizontal">
                <!-- Horizontal Metrics Container -->
                <div class="health-metrics-row">
                
                <!-- Sit/Stand Ratio -->
                <div class="health-metric-horizontal time-percentage-card">
                    <div class="health-metric">
                    <div class="metric-header">
                        <h3 class="health-metric-title">Sit/Stand Ratio</h3>
                        <span class="health-metric-badge">Today</span>
                    </div>

                    <div class="chart-container-horizontal">
                        <canvas id="timePercentageChartProfile"></canvas>
                    </div>

                    <div class="chart-pills">
                        <span class="pill pill-sit" id="sitting-percentage">65% Sit</span>
                        <span class="pill pill-stand" id="standing-percentage">35% Stand</span>
                    </div>
                    </div>
                </div>

                <div class="health-metric-horizontal posture-score-card">
                    <div class="health-metric">
                    <div class="metric-header">
                        <h3 class="health-metric-title">Posture Score</h3>
                        <span class="health-metric-badge">Today</span>
                    </div>

                    <div class="posture-score-display">
                        <p class="posture-score-value" id="profile-posture-score">65</p>
                    </div>

                    <div class="progress-wrap-horizontal" aria-label="Posture score progression">
                        <div class="progress-bar" id="profile-posture-score-bar" style="width:65%"></div>
                    </div>

                    <div class="score-scale">
                        <span class="scale-min">Poor</span>
                        <span class="scale-max">Excellent</span>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- Ideal Height Card -->
            <div class="info-card ideal-height-card">
                <div class="card-header">
                    <h2 class="card-title">Ideal Height</h2>
                </div>
                <div class="ideal-height-container">
                    <div class="height-display">
                        <span class="height-value">{{ $userProfile->ideal_height ?? 'N/A' }}</span>
                        <span class="height-unit">cm</span>
                    </div>
                    <p class="height-description">
                        Your personalized ideal desk height for optimal ergonomics
                    </p>
                    <a href="{{ route('ideal.height') }}" class="height-button">Configure Ideal Height</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer Component -->
    @include('components.footer')
    </div>

    <!-- Hidden data for API calls -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ Auth::id() }}">
</body>
</html>