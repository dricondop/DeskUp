<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Desk;
use App\Models\DeskActivity;

class AdminController extends Controller
{
    public function index() 
    {
        $users = User::all();
        $assignedDeskIds = User::pluck('assigned_desk_id')->filter();
        $unassignedDesks = Desk::whereNotIn('id', $assignedDeskIds)->pluck('name', 'id');
        // $pendingMeetings = DeskActivity::where('status', 'pending')->all();

        return view('users-management', [
            'users' => $users,
            'desks' => $unassignedDesks,
            // 'pendingMeetings' => $pendingMeetings
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
        $user = User::findOrFail($id);
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


}
