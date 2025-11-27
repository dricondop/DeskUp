<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Control — DeskUp</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                <h2 style="margin-bottom: 1rem; color:#3A506B;">User Desk Overview</h2>
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
                                                 — Assign Desk —
                                            @endif
                                        </option>
                                    </select>
                                </td>
                                <td> 
                                    @if ($user->assignedDesk !== null)
                                        @if ($user->assignedDesk->is_active === true)
                                            Online
                                        @else
                                            Offline
                                        @endif
                                        
                                    @else
                                        Unassigned
                                    @endif
                                </td> <!-- Needs to be changed -->
                                <td><button class="btn-unassign" data-user-id='{{ $user->id }}'
                                @if($user->assignedDesk === null) disabled @endif>Unassign</button></td>
                                <td><button class="btn-remove" data-user-id='{{ $user->id }}'>Remove</button></td>
                            <tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </div>


        <!-- Approve/Reject Meetings -->
        <div class="admin-container">
            <header class="page-header">
                <h1>Meeting requests</h1>
                <p class="subtitle">Approve or reject meeting requests</p>
            </header>

            <section class="card">
                <h2 style="margin-bottom: 1rem; color:#3A506B;">Meeting Requests Overview</h2>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Time From</th>
                            <th>Time To</th>
                            <th>Description</th>
                            <th>Desks</th>
                            <th>Approve</th>
                            <th>Reject</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{--
                        @foreach ($pendingMeetings as $meeting)
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><span class="status inactive">Approve</span></td>
                                <td><button class="btn-remove">Reject</button></td>
                            </tr>
                        @endforeach
                        --}}
                    
                    
                        
                    
                        
                    
                    
                    
                    
                        <tr>
                            <td>Jane Doe</td>
                            <td>jane@example.com</td>
                            <td>
                                <select class="desk-select" onchange="updateDesk(this)">
                                    <option value="">— Select Desk —</option>
                                </select>
                            </td>
                            <td><span class="status active" onclick="toggleDesk(this)">Active</span></td>
                            <td>102 cm</td>
                            <td><button class="btn-remove" onclick="removeUser(this)">Remove</button></td>
                        </tr>
                        <tr>
                            <td>John Smith</td>
                            <td>john@example.com</td>
                            <td>
                                <select class="desk-select" onchange="updateDesk(this)">
                                    <option value="">— Select Desk —</option>
                                </select>
                            </td>
                            <td><span class="status inactive" onclick="toggleDesk(this)">Disabled</span></td>
                            <td>—</td>
                            <td><button class="btn-remove" onclick="removeUser(this)">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>

    </div>
    <script>
        window.desks = @json($desks);
    </script>

    <script src='{{ asset('js/users-management.js') }}'></script>
</body>
</html>
