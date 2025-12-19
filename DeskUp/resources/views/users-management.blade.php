<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Control | DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/user-management.css') }}">
</head>
<body>
    @include('components.sidebar')

    <div class="main-content">

        <!-- Admin User Control  -->
        <div class="admin-container">
            <header class="page-header">
                <h1>Admin User Control</h1>
                <p class="subtitle">Manage users and assign desks</p>
            </header>

            <section class="card">
                <div class="card-header">
                    <h2>User Desk Overview</h2>
                    <button id="createUserBtn" class="btn-create">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" y1="8" x2="19" y2="14"></line>
                            <line x1="22" y1="11" x2="16" y2="11"></line>
                        </svg>
                        Create New User
                    </button>
                </div>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Desk</th>
                            <th>Status</th>
                            <th>Unassign Desk</th>
                            <th>Remove User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{$user->name}}</td>
                                <td>{{$user->email}}</td>
                                <td>
                                    <select class="desk-select" data-user-id='{{ $user->id }}'>
                                        <option value="">
                                            @if ($user->assigned_desk_id !== null)
                                                {{ $user->assignedDesk->name}}
                                            @else
                                                 <p>— Assign Desk —</p>
                                            @endif
                                        </option>
                                        @foreach ($unassignedDesks as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td> 
                                    @if ($user->assignedDesk !== null)
                                        @if ($user->assignedDesk->is_active === true)
                                            <p>Online</p>
                                        @else
                                            <p>Offline</p>
                                        @endif
                                        
                                    @else
                                        <p>Unassigned</p>
                                    @endif
                                </td> <!-- Needs to be changed -->
                                <td><button class="btn-unassign" data-user-id='{{ $user->id }}'
                                @if($user->assignedDesk === null) disabled @endif>Unassign</button></td>
                                <td><button class="btn-remove remove" data-user-id='{{ $user->id }}'>Remove</button></td>
                            <tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>


        <!-- Approve/Reject Events -->
        <div class="admin-container">
            <header class="page-header">
                <h1>Admin Events Control</h1>
                <p class="subtitle">Approve or reject events requests</p>
            </header>

            <section class="card">
                <h2 style="margin-bottom: 1rem; color:#3A506B;">Events Requests Overview</h2>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>Requestor</th>
                            <th>Time From</th>
                            <th>Time To</th>
                            <th>Description</th>
                            <th>Desks</th>
                            <th>Approve</th>
                            <th>Reject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingEvents as $event)
                            <tr>
                                <td>
                                    {{ optional($event->creator)->name ?? 'Unknown' }}
                                </td>
                                <td>
                                    {{ $event->scheduled_at 
                                    ? $event->scheduled_at->format('F jS, g:i a, Y')
                                    : 'Unknown' }}    
                                </td>
                                <td>
                                    {{ $event->scheduled_to 
                                    ? $event->scheduled_to->format('F jS, g:i a, Y')
                                    : 'Unknown' }}  
                                </td>
                                <td>
                                    @if($event->description)
                                            <button type="button" class="btn-description" onclick="showMessage('{{ addslashes($event->description) }}')">
                                                <p>Read</p>
                                            </button>
                                    @else
                                            <p>No Description</p>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($event->desks as $desk)
                                        <span class="desk-tag">{{ $desk->name }}</span>
                                    @empty
                                        <span>No desks</span>
                                    @endforelse
                                    
                                    
                                    {{--<button type="button" class="btn-desks" onclick="showDesks('{{ addslashes(string: $deskNames) }}')">
                                        <p>View</p>
                                    </button>--}}
                                </td>
                                <td><span class="btn-approve" data-event-id='{{ $event->id }}'>Approve</span></td>
                                <td><button class="btn-reject remove" data-event-id='{{ $event->id }}'>Reject</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>

        <!-- Notification Management Section -->
        <div class="admin-container">
            <header class="page-header">
                <h1>Notification Management</h1>
                <p class="subtitle">Configure automatic notifications and send manual alerts</p>
            </header>

            <!-- Automatic Notification Settings Card -->
            <section class="card">
                <h2 style="margin-bottom: 1rem; color:#3A506B;">Automatic Notification Settings</h2>
                
                <div class="settings-group">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Enable Automatic Notifications</h3>
                            <p>Automatically remind users to stand up after prolonged sitting</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="autoNotificationsToggle" 
                                {{ isset($settings) && $settings->automatic_notifications_enabled ? 'checked' : '' }}>
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
                                value="{{ isset($settings) ? $settings->sitting_time_threshold_minutes : 30 }}" 
                                min="5" max="120" step="5">
                            <span class="threshold-label">minutes</span>
                        </div>
                    </div>

                    <button id="saveSettingsBtn" class="btn-action btn-save">Save Settings</button>
                </div>

                <div id="settingsMessage" class="message" style="display: none;"></div>
            </section>

            <!-- Manual Notification Card -->
            <section class="card">
                <h2 style="margin-bottom: 1rem; color:#3A506B;">Send Manual Notification</h2>
                
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
                                <label class="user-checkbox-item">
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}">
                                    <span>{{ $user->name }} ({{ $user->email }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" id="sendNotificationBtn" class="btn-action btn-send">
                        Send Notification
                    </button>
                </form>

                <div id="notificationMessage" class="message" style="display: none;"></div>
            </section>
        </div>



    <!-- Description Modal -->
    <div id="descriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i data-lucide="message-circle"></i> Description</h3>
                <span class="close closeModal" >&times;</span>
            </div>
            <div class="modal-body">
                <p id="descriptionText"></p>
            </div>
            <div class="modal-footer">
                <button class="btn-closeModal closeModal">Close</button>
            </div>
        </div>
    </div>

    <!-- Desks Modal -->
    <div id="desksModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i data-lucide="message-circle"></i> Desks</h3>
                <span class="close closeModal" >&times;</span>
            </div>
            <div class="modal-body">
                <input type="radio">
                <p id="desksText"></p>
            </div>
            <div class="modal-footer">
                <button class="btn-closeModal closeModal">Close</button>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-content create-user-modal">
            <div class="modal-header create-user-header">
                <div class="modal-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    <h3 style="color: white;">Create New User</h3>
                </div>
                <span class="close closeModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createUserForm">
                    <div class="form-group">
                        <label for="userName">Full Name</label>
                        <input type="text" id="userName" name="name" placeholder="Enter user's full name" required>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email Address</label>
                        <input type="email" id="userEmail" name="email" placeholder="user@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="userPassword">Password</label>
                        <input type="password" id="userPassword" name="password" placeholder="Minimum 8 characters" required minlength="8">
                        <small class="input-hint">Must be at least 8 characters long</small>
                    </div>
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label-custom">
                            <input type="checkbox" id="isAdmin" name="is_admin">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Grant administrator privileges</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer create-user-footer">
                <button class="btn-modal-cancel closeModal">Cancel</button>
                <button class="btn-modal-submit" id="submitCreateUser">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Create User
                </button>
            </div>
        </div>
    </div>

    </div>

    <script src='{{ asset('js/users-management.js') }}'></script>
</body>
</html>
