<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        // checks if user is admin
        $isAdmin = false;
        if (Auth::user()->isAdmin()) {
            $isAdmin = true;
        }
        
        // get all users for creating an event
        $users = User::select('id', 'name')->orderBy('name')->get();

        $myEventsButton = $request->boolean('mine');

        $baseQuery = $myEventsButton
            ? Auth::user()->assignedEvents()
            : Event::query();

        $allEvents = $baseQuery
            ->where('event_type', '!=', 'cleaning')
            ->where('status', Event::STATUS_APPROVED)
            ->withCount('users')                                                // make users_count available
            ->with(['desks', 'users'])                                          // eager load relations
            ->orderBy('scheduled_at', 'asc')
            ->get();                                          
        
        $recurringCleaningDays = Event::where('event_type', 'cleaning')
            ->where('status', Event::STATUS_APPROVED)
            ->value('cleaning_days');

        
        $meetings   = $allEvents->where('event_type', 'meeting');
        $events     = $allEvents->where('event_type', 'event');
        $maintenances     = $allEvents->where('event_type', 'maintenance');

        return view('events', [
            'isAdmin' => $isAdmin,
            'upcomingEvents' => $allEvents,
            'meetings' => $meetings,
            'events' => $events,
            'maintenances' => $maintenances,
            'myEventsButton' => $myEventsButton,
            'users' => $users,
            'recurringCleaningDays' => $recurringCleaningDays
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

    public function addCleaningSchedule(Request $request) 
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Logged in user does not have administrator access',
            ]);
        }
        
        $validated =   $request->validate([
            'cleaning_time' => 'required|date_format:H:i',
            'cleaning_days' => 'required|array|min:1',
            'cleaning_days.*' => 'required|string|in:MON,TUE,WED,THU,FRI,SAT,SUN',
        ]);

        

        // change last cleaning schedule to 'completed'
        Event::where('event_type', 'cleaning')
            ->where('status', 'approved')
            ->update(['status' => Event::STATUS_COMPLETED]);
        
        
        
        $event = Event::create([
            'event_type' => 'cleaning',
            'description' => 'recurring office cleaning',
            'cleaning_time' => $validated['cleaning_time'],
            'cleaning_days' => $validated['cleaning_days'],
            'is_recurring' => true,
            'status' => Event::STATUS_APPROVED,
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cleaning Schedule added successfully',
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
