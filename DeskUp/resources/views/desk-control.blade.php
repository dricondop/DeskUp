<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Desk Control | DeskUp</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
        <link rel="stylesheet" href="{{ asset('css/desk-control.css') }}">
        <link rel="stylesheet" href="{{ asset('css/sync-status.css') }}">
    </head>
    <body>
        @include('components.sidebar')
        
        <div class="main-content">
            <!-- Real-time indicator -->
            @if($desk->isConnectedToAPI())
                <div class="realtime-indicator" id="realtimeIndicator" title="Real-time updates active"></div>
            @else
                <div class="realtime-indicator inactive" title="Not connected to API"></div>
            @endif

            <main class="desk-view">
                <section class="desk-control">
                    <h1>Desk Control</h1>
                    <h3>
                        {{ $desk->name }} 
                        &vert; Status: 
                        @if($desk->isConnectedToAPI())
                            <span class="desk-badge connected" id="deskStatus">Connected</span>
                        @else
                            <span class="desk-badge disconnected" id="deskStatus">Offline</span>
                        @endif
                    </h3>

                <img src="{{ asset('assets/desk.png') }}" alt="desk">
                    
                    <div class="desk-view-btns-container">
                        <button class="desk-view-btns sitting">Sitting</button>
                        <button class="desk-view-btns error-log">Error log</button>
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
                                <h3>Speed: 36</h3>
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

                        <h3 class="activity">Activity</h3>
                        <div id="activityList">
                            @forelse($desk->activities()->orderBy('scheduled_at', 'desc')->limit(5)->get() as $activity)
                                <div class="temp-activity-box">
                                    <p>{{ $activity->scheduled_at->format('H:i') }} {{ $activity->description }}</p>
                                </div>
                            @empty
                                <div class="temp-activity-box">
                                    <p>No scheduled activities</p>
                                </div>
                            @endforelse
                        </div>

                    
                    </section>
                </div>
            </main>
        </div>

        <script>
            const desk = {
                id: {{ $desk->id }},
                height: {{ $desk->height }},
                speed: {{ $desk->speed }},
                url: "/api/desks/{{ $desk->id }}",
            };
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        </script>

        <script src="{{ asset('js/desk-control.js') }}"></script>
        <script src="{{ asset('js/background-sync.js') }}"></script>
    </body>
</html>


