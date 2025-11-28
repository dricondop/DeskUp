<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Desk;
use App\Models\Event;

class AdminController extends Controller
{
    public function index() 
    {
        $users = User::all();
        $assignedDeskIds = User::pluck('assigned_desk_id')->filter();
        $unassignedDesks = Desk::whereNotIn('id', $assignedDeskIds)->pluck('name', 'id');
        $pendingEvents = Event::with(['creator', 'desks'])->where('status', 'pending')->get();

        return view('users-management', [
            'users' => $users,
            'desks' => $unassignedDesks,
            'pendingEvents' => $pendingEvents
        ]);
    }

    public function assignDesk(Request $request, $id)
    {
        $validated = $request->validate([
            'assigned_desk_id' => 'required|integer|exists:desks,id'
        ]);
        
        $user = User::findOrFail($id);
        $user->assigned_desk_id = $validated['assigned_desk_id'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Desk assigned successfully',
            'assigned_desk_id' => $user->assigned_desk_id
        ]);
    }

    public function unassignDesk($id) 
    {
        $user = USer::findOrFail($id);
        $user->assigned_desk_id = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Desk unassigned successfully',
            'assigned_desk_id' => $user->assigned_desk_id
        ]);
    }

    public function removeUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User has been removed successfully'
        ]);
    }

    public function approveEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->status = Event::STATUS_ACTIVE;
        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'DRY-RUN: Event has been approved successfully'
        ]);
    }

    public function rejectEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->status = Event::STATUS_REJECTED;
        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'DRY-RUN: Event has been rejected successfully'
        ]);
    }
}
