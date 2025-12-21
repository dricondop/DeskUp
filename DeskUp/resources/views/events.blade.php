<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Events | DeskUp</title>

    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/events.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modals.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    @include('components.sidebar')

    <div class="main-content">
        <div class="all-content">
            <header>
                <div class="header-content">
                    <h1>Upcoming Events</h1>
                </div>
                <div id="event-tabs" class="horizontal-alignment">
                    <div>
                    <button class="event-tab active" data-target="upcoming">Upcoming</button>
                    <button class="event-tab" data-target="meetings">Meetings</button>
                    <button class="event-tab" data-target="events">Events</button>
                    <button class="event-tab" data-target="maintenance">Maintenance</button>
                    </div>
                    <div class="horizontal-alignment">
                         <!-- My events / all events button -->
                        
                        @if($isAdmin)
                            <span class="btn scheduleCleaning">Cleaning</span>
                        @endif

                        <!-- My events / all events button -->
                        <a href="{{ $myEventsButton 
                                    ? route('events.index') 
                                    : route('events.index', ['mine' => 1]) }}"
                            class="my-event {{ $myEventsButton ? 'active' : '' }}">
                            My events
                        </a>
                    
                        <!-- Add event button -->
                        <span class="btn add-event">&#43;</span>
                    </div>
                </div>
                <div class="horizontal-line"></div>
            </header>
            <main class="main-content-view">
                <div id="event-content">
                    
                    <!-- Show all events together, ordered by first -->
                    <div id="upcoming" class="event-panel">
                        @forelse ($upcomingEvents as $event)
                            @include('components.event-card', ['event' => $event])
                        @empty
                            <p>There are no events scheduled</p>
                        @endforelse
                    </div>

                    <!-- Show all meetings only together -->
                    <div id="meetings" class="event-panel hidden">
                        @forelse ($meetings as $event)
                        @include('components.event-card', ['event' => $event])
                        @empty
                            <p>There are no meetings scheduled</p>
                        @endforelse
                    </div>
                    
                    <!-- Show all events only together -->
                    <div id="events" class="event-panel hidden">
                        @forelse ($events as $event)
                            @include('components.event-card', ['event' => $event])
                        @empty
                            <p>There are no events scheduled</p>
                        @endforelse
                    </div>

                    <!-- Show all maintenance only together -->
                    <div id="maintenance" class="event-panel hidden">
                        @forelse ($maintenances as $event)
                            @include('components.event-card', ['event' => $event])
                        @empty
                            <p>There are no maintenances scheduled</p>
                        @endforelse
                    </div>
                </div>

                <!-- Desk control  -->
                <div class="meeting-management hidden">
                    <div class="meeting-management-header">
                        <span class="btn close-panel"><i data-lucide="chevron-right"></i></span>
                    </div>

                    <section class="desk-adjustment">
                        <h2>Desk Management</h2>

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
                            <button data-height="60">Sit</button>
                            <button data-height="75">75</button>
                            <button data-height="90">Stand</button>
                        </div>

                        <div class="meeting-attendees">
                            <div class="horizontal-alignment">
                                <h3>Attendees</h3>
                                <div class="attendee-btn">
                                    <select id="availableUsers" class="hidden-select">
                                        <option value="">Select user</option>
                                        <!-- Options added in javascript -->
                                    </select>
                                
                                    <i data-lucide="user-round-plus"></i>
                                </div>
                            </div>
                            
                            <div id="included-users"></div>
                        </div>

                        <div class="meeting-desks">
                            <h3>Included desks</h3>
                            <div id="included-desks"></div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Event and Cleaning modals -->
    @include('components.modals', ['recurringCleaningDays' => $recurringCleaningDays ?? collect()])

    <script>
        const loggedInUser = {{ auth()->user()->id }};      // used for adding user to event
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    </script>

    <script src="{{ asset('js/tab-switcher.js') }}"></script>
    <script src="{{ asset('js/desk-control.js') }}"></script>
    <script src="{{ asset('js/events.js') }}"></script>
    <script src="{{ asset('js/modals.js') }}"></script>
</body>
</html>
