<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desk Control | DeskUp</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desk-control.css') }}">
    
    <!-- Three.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
        <main class="desk-view">
            <!-- Left Column: Desk Control -->
            <section class="desk-control">
                <div class="desk-control-header">
                    <h1>Desk Control</h1>
                    <h3>{{ $desk->name }} &vert; Status: <span id="deskStatus">{{ $desk->status }}</span></h3>
                </div>

                <!-- 3D Canvas -->
                <div class="desk-3d-container">
                    <div id="desk-3d-viewer"></div>
                    <div class="height-indicator-3d">
                        <span id="current-height-3d">{{ $desk->height }} cm</span>
                    </div>
                </div>
                
                <div class="desk-view-btns-container">
                    <button class="desk-view-btns sitting">Sitting</button>
                    <button class="desk-view-btns add-event">Add Event</button>
                </div>
            </section>

            <!-- Right Column: Desk Management -->
            <section class="desk-management">
                <div class="desk-management-header">
                    <h1>Desk Management</h1>
                </div>

                <div class="desk-management-content">
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
                </div>
            </section>
        </main>
    </div>

    <!-- Event Modal -->
    @include('components.modals', ['recurringCleaningDays' => []])

    <script>
        // 3D viewer data
        const deskData = {
            id: {{ $desk->id }},
            currentHeight: {{ $desk->height }},
            minHeight: 68,
            maxHeight: 132,
            modelPath: "{{ asset('models/adjustable-desk/desk.glb') }}",
            apiUrl: "/api/desks/{{ $desk->id }}/height" 
        };

        const loggedInUser = {{ auth()->user()->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Desk control
        const desk = {
            id: {{ $desk->id }},
            height: {{ $desk->height }},
            speed: {{ $desk->speed }},
        };
    </script>

    <!-- Scripts -->
    <script src="{{ asset('js/desk-3d-viewer.js') }}"></script>
    <script src="{{ asset('js/desk-control.js') }}"></script>
    <script src="{{ asset('js/tab-switcher.js') }}"></script>
    <script src="{{ asset('js/modals.js') }}"></script>

    <script>
        // Initialize the 3D viewer after the page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (window.Desk3DViewer) {
                window.desk3DViewer = new Desk3DViewer(deskData);
            }
            
            // Initialize desk controls
            if (typeof addAllDesks === 'function') {
                addAllDesks([desk.id], desk.height);
                isEvent = false;
            }
            
            // Update clock
            updateClock();
            setInterval(updateClock, 1000);
        });
        
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
    </script>
</body>
</html>
