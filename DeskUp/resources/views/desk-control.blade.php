<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desk Control | DeskUp</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desk-control.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modals.css') }}">

</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
        <main class="desk-view">
            <section class="desk-control">
                <h1>Desk Control</h1>
                <h3>{{ $desk->name }} &vert; Status: <span id="deskStatus">{{ $desk->status }}</span></h3>

                <p id="clock"
                aria-label="Current time"
                style="margin-top:4px; font-size:0.9rem; color:#3A506B; font-weight:500; letter-spacing:0.03em;">
                    --:--:--
                </p>

                <img src="{{ asset('assets/desk.png') }}" alt="desk">
                
                <div class="desk-view-btns-container">
                    <button class="desk-view-btns sitting">Sitting</button>
                    <button class="desk-view-btns add-event">Add Event</button>
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
                        <button data-height="70">Sit</button>
                        <button data-height="90">90</button>
                        <button data-height="110">Stand</button>
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
    @include('components.modals')


    <script>
        const desk = {
            id: {{ $desk->id }},
            height: {{ $desk->height }},
            speed: {{ $desk->speed }},
        };

        const loggedInUser = {{ auth()->user()->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    </script>

    <script src="{{ asset('js/desk-control.js') }}"></script>
    <script src="{{ asset('js/tab-switcher.js') }}"></script>
    <script src="{{ asset('js/modals.js') }}"></script>

    <script>
        
        function updateClock() {
            const clockEl = document.getElementById('clock');
            if (!clockEl) return;

            const now = new Date();

            const datePart = now.toLocaleDateString(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });

            const timePart = now.toLocaleTimeString(undefined, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            clockEl.textContent = `${datePart} ${timePart}`;
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
    <script>
        // add desk to desk array
        addAllDesks([desk.id], desk.height);
        isEvent = false;
    </script>
</body>
</html>





