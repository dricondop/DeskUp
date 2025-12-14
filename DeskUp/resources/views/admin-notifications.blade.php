<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notification Management — DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-notifications.css') }}">
</head>
<body>
    @include('components.sidebar')

    <div class="main-content">
        <div class="admin-container">
            <header class="page-header">
                <h1>Notification Management</h1>
                <p class="subtitle">Configure automatic notifications and send manual alerts</p>
            </header>

            <!-- Notification Settings Card -->
            <section class="card settings-card">
                <h2>Automatic Notification Settings</h2>
                
                <div class="settings-group">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Enable Automatic Notifications</h3>
                            <p>Automatically remind users to stand up after prolonged sitting</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="autoNotificationsToggle" 
                                {{ $settings->auto_notifications_enabled ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Sitting Time Threshold</h3>
                            <p>Minutes of continuous sitting before sending a notification</p>
                        </div>
                        <div class="threshold-control">
                            <input type="number" id="sittingThreshold" 
                                value="{{ $settings->sitting_time_threshold }}" 
                                min="5" max="120" step="5">
                            <span class="threshold-label">minutes</span>
                        </div>
                    </div>

                    <button id="saveSettingsBtn" class="btn btn-primary">Save Settings</button>
                </div>

                <div id="settingsMessage" class="message" style="display: none;"></div>
            </section>

            <!-- Manual Notification Card -->
            <section class="card manual-notification-card">
                <h2>Send Manual Notification</h2>
                
                <form id="manualNotificationForm">
                    <div class="form-group">
                        <label for="notificationTitle">Notification Title</label>
                        <input type="text" id="notificationTitle" name="title" 
                            placeholder="e.g., Reminder: Stand Up!" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label for="notificationMessage">Message</label>
                        <textarea id="notificationMessage" name="message" rows="4" 
                            placeholder="Enter your notification message here..." 
                            maxlength="1000" required></textarea>
                        <span class="char-count"><span id="charCount">0</span> / 1000</span>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="sendToAll" name="send_to_all">
                            <span>Send to all users</span>
                        </label>
                    </div>

                    <div class="form-group" id="userSelectGroup">
                        <label for="userSelect">Select Users</label>
                        <div class="user-select-container">
                            @foreach($users as $user)
                                <label class="user-checkbox">
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}">
                                    <span>{{ $user->name }} ({{ $user->email }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" id="sendNotificationBtn" class="btn btn-primary">
                        Send Notification
                    </button>
                </form>

                <div id="notificationMessage" class="message" style="display: none;"></div>
            </section>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Settings Management
        const saveSettingsBtn = document.getElementById('saveSettingsBtn');
        const autoNotificationsToggle = document.getElementById('autoNotificationsToggle');
        const sittingThreshold = document.getElementById('sittingThreshold');
        const settingsMessage = document.getElementById('settingsMessage');

        saveSettingsBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/notifications/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        auto_notifications_enabled: autoNotificationsToggle.checked,
                        sitting_time_threshold: parseInt(sittingThreshold.value),
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(settingsMessage, 'Settings saved successfully!', 'success');
                } else {
                    showMessage(settingsMessage, 'Failed to save settings', 'error');
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                showMessage(settingsMessage, 'An error occurred', 'error');
            }
        });

        // Manual Notification Form
        const manualNotificationForm = document.getElementById('manualNotificationForm');
        const sendToAll = document.getElementById('sendToAll');
        const userSelectGroup = document.getElementById('userSelectGroup');
        const notificationMessage = document.getElementById('notificationMessage');
        const charCount = document.getElementById('charCount');

        // Character counter
        document.getElementById('notificationMessage').addEventListener('input', (e) => {
            charCount.textContent = e.target.value.length;
        });

        // Toggle user selection
        sendToAll.addEventListener('change', () => {
            userSelectGroup.style.display = sendToAll.checked ? 'none' : 'block';
            
            // Uncheck all user checkboxes when "send to all" is checked
            if (sendToAll.checked) {
                document.querySelectorAll('input[name="user_ids[]"]').forEach(cb => {
                    cb.checked = false;
                });
            }
        });

        // Submit manual notification
        manualNotificationForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(manualNotificationForm);
            const data = {
                title: formData.get('title'),
                message: formData.get('message'),
                send_to_all: sendToAll.checked,
            };

            if (!sendToAll.checked) {
                const selectedUsers = Array.from(document.querySelectorAll('input[name="user_ids[]"]:checked'))
                    .map(cb => parseInt(cb.value));
                
                if (selectedUsers.length === 0) {
                    showMessage(notificationMessage, 'Please select at least one user or check "Send to all users"', 'error');
                    return;
                }
                
                data.user_ids = selectedUsers;
            }

            try {
                const response = await fetch('/api/notifications/send-manual', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(notificationMessage, `Notification sent to ${result.count} user(s)!`, 'success');
                    manualNotificationForm.reset();
                    charCount.textContent = '0';
                    userSelectGroup.style.display = 'block';
                } else {
                    showMessage(notificationMessage, 'Failed to send notification', 'error');
                }
            } catch (error) {
                console.error('Error sending notification:', error);
                showMessage(notificationMessage, 'An error occurred', 'error');
            }
        });

        function showMessage(element, text, type) {
            element.textContent = text;
            element.className = `message ${type}`;
            element.style.display = 'block';
            
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>
