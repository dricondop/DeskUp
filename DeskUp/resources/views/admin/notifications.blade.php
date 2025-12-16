<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskUp - Notification Settings</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
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
                        <div class="profile-info">
                            <h1>Notification Management</h1>
                            <p class="position">Configure automatic and manual notifications</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-content">
                <!-- Automatic Notifications Settings -->
                <div class="info-card">
                    <div class="card-header">
                        <h2 class="card-title">Automatic Notifications</h2>
                    </div>
                    <div class="settings-form">
                        <div class="form-field">
                            <label class="field-label">Enable Automatic Notifications</label>
                            <div class="toggle-container">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="auto-enabled" {{ $settings->automatic_notifications_enabled ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="toggle-status">{{ $settings->automatic_notifications_enabled ? 'Enabled' : 'Disabled' }}</span>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="field-label" for="sitting-threshold">Sitting Time Threshold (minutes)</label>
                            <input type="number" id="sitting-threshold" class="field-input" 
                                   value="{{ $settings->sitting_time_threshold_minutes }}" 
                                   min="1" max="300">
                            <span class="field-hint">Users will be notified after sitting for this duration</span>
                        </div>

                        <button class="save-btn" onclick="saveSettings()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </div>

                <!-- Send Manual Notification -->
                <div class="info-card">
                    <div class="card-header">
                        <h2 class="card-title">Send Manual Notification</h2>
                    </div>
                    <div class="notification-form">
                        <div class="form-field">
                            <label class="field-label">Recipients</label>
                            <div class="recipient-options">
                                <label class="radio-option">
                                    <input type="radio" name="recipient-type" value="all" checked onchange="toggleUserSelection()">
                                    <span>All Users</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="recipient-type" value="specific" onchange="toggleUserSelection()">
                                    <span>Specific Users</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-field" id="user-selection" style="display: none;">
                            <label class="field-label">Select Users</label>
                            <div class="user-select-container">
                                <div class="user-select-header">
                                    <input type="text" id="user-search" class="field-input" placeholder="Search users...">
                                    <label class="select-all-label">
                                        <input type="checkbox" id="select-all" onchange="toggleAllUsers()">
                                        <span>Select All</span>
                                    </label>
                                </div>
                                <div class="user-list" id="user-list">
                                    @foreach($users as $user)
                                    <label class="user-item" data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}">
                                        <input type="checkbox" class="user-checkbox" value="{{ $user->id }}">
                                        <div class="user-info">
                                            <span class="user-name">{{ $user->name }}</span>
                                            <span class="user-email">{{ $user->email }}</span>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="field-label" for="notif-title">Title</label>
                            <input type="text" id="notif-title" class="field-input" maxlength="255" placeholder="Enter notification title">
                            <span class="char-count"><span id="title-count">0</span>/255</span>
                        </div>

                        <div class="form-field">
                            <label class="field-label" for="notif-message">Message</label>
                            <textarea id="notif-message" class="field-textarea" maxlength="1000" rows="5" placeholder="Enter notification message"></textarea>
                            <span class="char-count"><span id="message-count">0</span>/1000</span>
                        </div>

                        <button class="send-btn" onclick="sendNotification()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                            <span id="send-btn-text">Send to All Users</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @include('components.footer')
    </div>

    <div id="toast" class="toast"></div>

    <script src="{{ asset('js/admin-notifications.js') }}"></script>
</body>
</html>
