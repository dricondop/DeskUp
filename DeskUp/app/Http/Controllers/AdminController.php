<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Desk;
use App\Models\Event;
use App\Models\NotificationSettings;

class AdminController extends Controller
{
    public function index() 
    {
        $users = User::all();
        $assignedDeskIds = User::pluck('assigned_desk_id')->filter();
        $unassignedDesks = Desk::whereNotIn('id', $assignedDeskIds)->pluck('name', 'id');
        $pendingEvents = Event::with(['creator', 'desks'])->where('status', 'pending')->get();
        $settings = NotificationSettings::get();

        return view('users-management', [
            'users' => $users,
            'unassignedDesks' => $unassignedDesks,
            'pendingEvents' => $pendingEvents,
            'settings' => $settings
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

    public function createUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'is_admin' => 'nullable|boolean'
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'is_admin' => $validated['is_admin'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approveEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->status = Event::STATUS_APPROVED;
        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'Event has been approved successfully'
        ]);
    }

    public function rejectEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->status = Event::STATUS_REJECTED;
        $event->save();

        return response()->json([
            'success' => true,
            'message' => 'Event has been rejected successfully'
        ]);
    }
}
