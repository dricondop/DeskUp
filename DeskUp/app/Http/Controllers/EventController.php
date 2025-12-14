<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class EventController extends Controller
{
    public function index(Request $request)
    {
        // get all users for creating an event
        $users = User::select('id', 'name')->orderBy('name')->get();

        $myEventsButton = $request->boolean('mine');

        if ($myEventsButton) {
            $baseQuery = Auth::user()
                ->assignedEvents()
                ->with(['desks', 'users'])                  // eager load relations
                ->withCount('users')                        // make users_count available
                ->where('status', Event::STATUS_APPROVED);
        } else {
            $baseQuery = Event::with(['desks', 'users'])    // same as above
                ->withCount('users')
                ->where('status', Event::STATUS_APPROVED);
        }

        $allEvents = $baseQuery->orderBy('scheduled_at', 'asc')->get();
    
        
        $meetings   = $allEvents->where('event_type', 'meeting');
        $events     = $allEvents->where('event_type', 'event');
        $cleanings     = $allEvents->where('event_type', 'cleaning');
        $maintenances     = $allEvents->where('event_type', 'maintenance');

        return view('events', [
            'upcomingEvents' => $allEvents,
            'meetings' => $meetings,
            'events' => $events,
            'cleanings' => $cleanings,
            'maintenances' => $maintenances,
            'myEventsButton' => $myEventsButton,
            'users' => $users,
        ]);
    }

    public function addEvent(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'scheduled_to' => 'required|date',
            'desk_ids' => 'required|array|min:1',
            'desk_ids.*' => 'exists:desks,id',      // '.*' means it must apply to every element in an array
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id', 
        ]);

        $user = Auth::user();
        $status = $user && $user->isAdmin()
            ? Event::STATUS_APPROVED
            : Event::STATUS_PENDING;
        

        $event = Event::create([
            'event_type' => $validated['event_type'],
            'description' => $validated['description'],
            'scheduled_at' => $validated['scheduled_at'],
            'scheduled_to' => $validated['scheduled_to'],
            'status' => $status,
            'created_by' => $user->id
        ]);

        // attach desks and users to event
        $event->desks()->syncWithoutDetaching($validated['desk_ids']);      // Adds relationship only if it does not already exist
        $event->users()->syncWithoutDetaching($validated['user_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Event added successfully',
            'event' => $event
        ]);
    }

    public function availableUsers(Event $event)
    {
        $users = User::whereDoesntHave('events', function ($q) use ($event) {
            $q->where('events.id', $event->id);         // finds users that do not have a relationship with the event
        })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($users);
    }

    public function addUserToEvent(Request $request, Event $event)
    {
        $validated = $request->validate([
            'user' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($validated['user']);
        $userName = $user->name;

        $event = Event::findOrFail($event->id);
        $event->users()->syncWithoutDetaching($validated['user']);

        return response()->json([
            'success' => true,
            'message' => 'Successfully added user to event',
            'event_id' => $event->id,
            'user_id' => $validated['user'],
            'user_name' => $userName
        ]);

    }
}
