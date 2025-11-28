<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskUp - User Profile</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
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
                <div class="header-actions">
                    <button class="notification-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.93 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                    </button>
                    <button class="settings-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                        </svg>
                    </button>
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

            <!-- Health Stats Insights -->
            <div class="info-card combined-card">
                <div class="health-section">
                    <div class="card-header">
                        <h2 class="card-title">Health Score</h2>
                    </div>
                    <div class="health-score-container">
                        <div class="score-display">
                            <div class="score-item">
                                <div class="score-circle" style="background: conic-gradient(#57C785 0% 65%, #eaeaea 65% 100%);">
                                    <span class="score-value">65%</span>
                                </div>
                                <span class="score-label">Sitting Time</span>
                            </div>
                            <div class="score-item">
                                <div class="score-circle" style="background: conic-gradient(#2A7B9B 0% 35%, #eaeaea 35% 100%);">
                                    <span class="score-value">35%</span>
                                </div>
                                <span class="score-label">Standing Time</span>
                            </div>
                        </div>
                        <button class="stats-button">View Complete Statistics</button>
                    </div>
                </div>
                
                <div class="divider"></div>
                
                <div class="height-section">
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
                        <button class="height-button">Configure Ideal Height</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer Component -->
    @include('components.footer')
    </div>

    <script src="{{ asset('js/profile.js') }}"></script>
</body>
</html>