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
                                                 <p>— Assign Desk —</p>
                                            @endif
                                        </option>
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

    </div>
    <script>
        window.desks = @json($desks);
    </script>

    <script src='{{ asset('js/users-management.js') }}'></script>
</body>
</html>
