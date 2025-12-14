<?php

namespace App\Http\Controllers;

use App\Helpers\APIMethods;
use App\Models\Desk;
use App\Models\Event;
use App\Models\User;
use App\Models\DeskActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeskController extends Controller
{
    public function show($id)
    {
        $desk = Desk::with('events')->findOrFail($id);

        $desks = Desk::all();
    
        $user = Auth::user();
        
        // get all users for creating an event
        $users = User::select('id', 'name')->orderBy('name')->get();

        $pendingEvents = $user->eventsCreatedBy()
            ->pendingEvents()
            ->orderBy('scheduled_at', 'desc')
            ->get();
        
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
            'pendingEvents' => $pendingEvents,
            'users' => $users,
        ]);
    }

    public function updateHeight(Request $request, $id)
    {
        $validated = $request->validate([
            'height' => 'required|integer'
        ]);

        // converts height from cm to mm
        $height = $validated['height'] * 10;

        $desk = Desk::findOrFail($id);
        
        try {
            APIMethods::raiseDesk($height, $desk->api_desk_id);
            $desk->newUserStatsHistoryRecord($height);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update height',
                'error' => $e->getMessage(),
            ]);
        }

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

}
