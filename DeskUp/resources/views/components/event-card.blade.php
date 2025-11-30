<div class="event-container" data-included-desks='@json($event->desks->pluck("name"))' data-included-users='@json($event->users->pluck("name"))'>
    <div class="event-date">
        <p>{{ $event->scheduled_at->format('j M') }}</p>
        <p>{{ $event->scheduled_at->format('g:i a') }}</p>
    </div>
    <div class="event-body">
        <div>
            <h2>{{ $event->event_type }}</h2>
            <span class="btn options"><i data-lucide="more-horizontal"></i></span>
        </div>
        <p class="btn"><i data-lucide="users" class="lucide-users"></i> {{ $event->users()->count() }} attendees</p>
    </div>
</div>