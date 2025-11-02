<?php

namespace App\Http\Controllers;

use App\Models\Desk;
use App\Models\DeskActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeskController extends Controller
{
    public function show($id)
    {
        $desk = Desk::with('activities')->findOrFail($id);
        
        $isAdmin = false;
        if (Auth::check()) {
            Auth::user()->refresh();
            $isAdmin = Auth::user()->isAdmin();
        }

        return view('desk-control', [
            'desk' => $desk,
            'isAdmin' => $isAdmin,
            'isLoggedIn' => Auth::check()
        ]);
    }

    public function updateHeight(Request $request, $id)
    {
        $validated = $request->validate([
            'height' => 'required|integer|min:0|max:150'
        ]);

        $desk = Desk::findOrFail($id);
        $desk->height = $validated['height'];
        $desk->save();

        return response()->json([
            'success' => true,
            'message' => 'Height updated successfully',
            'height' => $desk->height
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50'
        ]);

        $desk = Desk::findOrFail($id);
        $desk->status = $validated['status'];
        $desk->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $desk->status
        ]);
    }

    public function index()
    {
        $desks = Desk::all();
        return response()->json(['desks' => $desks]);
    }

    public function addActivity(Request $request, $id)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date'
        ]);

        $desk = Desk::findOrFail($id);
        
        $activity = $desk->activities()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity added successfully',
            'activity' => $activity
        ]);
    }
}
