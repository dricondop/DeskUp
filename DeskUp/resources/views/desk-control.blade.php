<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desk Control | DeskUp</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desk-control.css') }}">

</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
        <main class="desk-view">
            <section class="desk-control">
                <h1>Desk Control</h1>
                <h3>{{ $desk->name }} &vert; Status: <span id="deskStatus">{{ $desk->status }}</span></h3>

            <img src="{{ asset('assets/desk.png') }}" alt="desk">
                
                <div class="desk-view-btns-container">
                    <button class="desk-view-btns sitting">Sitting</button>
                    <button class="desk-view-btns error-log" onclick="openModal()">Add Event</button>
                </div>
            </section>

            <div class="desk-management">
                <div class="profile">
                    @if($isLoggedIn)
                        <div class="profile-pic"></div>
                        <h2>{{ auth()->user()->name }}</h2>
                    @else
                        <div class="profile-pic"></div>
                        <h2>Guest</h2>
                    @endif
                </div>

                <section class="desk-adjustment">
                    <h1>Desk Management</h1>

                    <h3 class="height-adjustment-line">
                        Height Adjustment
                        <span>
                            <h3>Speed: 36 mm/s</h3>
                        </span>
                    </h3>

                    <div class="input-group">
                        <button id="decrement">-</button>
                        <output id="height-display" class="value"></output>
                        <button id="increment">+</button>
                    </div>

                    <div class="height-preset-btns">
                        <button data-height="75">Sit</button>
                        <button data-height="100">75</button>
                        <button data-height="120">Stand</button>
                    </div>

                    <div class="event-header">
                        <button class="event-tab active" data-target="eventList">Events</button>
                        @if(!$isAdmin)
                        <button class="event-tab" data-target="pendingList">Pending</button>
                        @endif
                    </div>

                    <div id="eventList" class="event-panel">
                        @forelse($desk->events()->orderBy('scheduled_at', 'desc')->limit(5)->get() as $event)
                            <div class="temp-event-box">
                                <p>{{ $event->scheduled_at->format('H:i') }} {{ $event->description }}</p>
                            </div>
                        @empty
                            <div class="temp-event-box">
                                <p>No scheduled events</p>
                            </div>
                        @endforelse
                    </div>

                    <div id="pendingList" class="event-panel hidden">
                        @forelse($pendingEvents as $event)
                            <div class="temp-event-box">
                                <p>{{ $event->scheduled_at->format('H:i') }} {{ $event->description }}</p>
                            </div>
                        @empty
                            <div class="temp-event-box">
                                <p>No pending events</p>
                            </div>
                        @endforelse

                    </div>

                
                </section>
            </div>
        </main>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Event</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    @csrf
                    
                     <!-- Choose date -->
                    <div class="form-group">
                        <div>
                            <label for="meeting-date">Meeting date *</label>
                            <input type="date" id="meeting-date" name="meeting-date" required>
                        </div>
                    </div>

                    <!-- Choose time -->
                    <div class="form-group date-time ">
                        <div>
                            <label for="meeting-time-from">Time from *</label>
                            <input type="time" id="meeting-time-from" name="meeting-time-from" required>
                        </div>
                        <div>
                            <label for="meeting-time-to">Time to *</label>
                            <input type="time" id="meeting-time-to" name="meeting-time-to" required>
                        </div>
                    </div>

                    <!-- Choose desks -->
                    <div class="form-group">
                        <label for="miniLayout">Select desks for the meeting</label>
                        <div id="miniLayout" class="mini-layout"></div>
                        <div id="selectedDesksInputs"></div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="eventFormDescription">Description *</label>
                        <textarea id="eventFormDescription" name="eventFormDescription" rows="5" placeholder="Describe the purpose of the meeting" required></textarea>
                    </div>

                    <button type="submit">Send request</button>
                </form>
            </div>
        </div>
    </div>



    <script>
        const desk = {
            id: {{ $desk->id }},
            height: {{ $desk->height }},
            speed: {{ $desk->speed }},
            url: "/api/desks",
        };

        const userId = {{ auth()->user()->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    </script>

    <script src="{{ asset('js/desk-control.js') }}"></script>
    <script src="{{ asset('js/tab-switcher.js') }}"></script>
</body>
</html>




