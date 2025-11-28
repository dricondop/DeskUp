<?php

namespace App\Http\Controllers;

use App\Models\Desk;
use App\Models\Event;
use App\Models\User;
use App\Models\DeskActivity;
use App\Services\DeskSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeskController extends Controller
{
    protected $syncService;

    public function __construct(DeskSyncService $syncService)
    {
        $this->syncService = $syncService;
    }
    public function show($id)
    {
        $desk = Desk::with('events')->findOrFail($id);

        $desks = Desk::all();
    
        $user = Auth::user();

        $pendingEvents = $user->events()
            ->pendingEvents()
            ->orderBy('scheduled_at', 'desc')
            ->get();
        
        // Sync desk state from API if connected
        if ($desk->isConnectedToAPI()) {
            try {
                $this->syncService->syncDeskState($desk);
                $desk->refresh();
            } catch (\Exception $e) {
                \Log::warning("Failed to sync desk state for desk {$id}", ['error' => $e->getMessage()]);
            }
        }
        
        $isAdmin = false;
        if (Auth::check()) {
            Auth::user()->refresh();
            $isAdmin = Auth::user()->isAdmin();
        }

        return view('desk-control', [
            'desk' => $desk,
            'desks' => $desks,
            'isAdmin' => $isAdmin,
            'isLoggedIn' => Auth::check(),
            'pendingEvents' => $pendingEvents
        ]);
    }

    public function updateHeight(Request $request, $id)
    {
        $validated = $request->validate([
            'height' => 'required|integer'
        ]);

        $desk = Desk::findOrFail($id);

        $desk->updateHeight($validated['height']);

        return response()->json([
            'success' => true,
            'message' => 'Height updated successfully',
            'height' => $desk->height // This will now get the value from latest stats
        ]);
        
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50'
        ]);

        $desk = Desk::findOrFail($id);
        $desk->updateStatus($validated['status']); // Use new method

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $desk->status // This will now get the value from latest stats
        ]);
    }

    public function index()
    {
        $desks = Desk::all();
        return response()->json(['desks' => $desks]);
    }

    public function addEvent(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'scheduled_to' => 'required|date',
            'desk_ids' => 'required|array|min:1',
            'desk_ids.*' => 'exists:desks,id', // '.*' means it must apply to every element in an array
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

        $event->desks()->attach($validated['desk_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Event added successfully',
            'event' => $event
        ]);
    }
}
