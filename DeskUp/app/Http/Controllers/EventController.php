<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;


class EventController extends Controller
{
    public function index()
    {
        $upcomingEvents = Event::where('status', Event::STATUS_APPROVED)
            ->orderBy('scheduled_at', 'asc')
            ->get();
        
        $meetings   = $upcomingEvents->where('event_type', 'meeting');
        $events     = $upcomingEvents->where('event_type', 'event');
        $cleanings     = $upcomingEvents->where('event_type', 'cleaning');
        $maintenances     = $upcomingEvents->where('event_type', 'maintenance');

        return view('events', [
            'upcomingEvents' => $upcomingEvents,
            'meetings' => $meetings,
            'events' => $events,
            'cleanings' => $cleanings,
            'maintenances' => $maintenances,
        ]);
    }
}
