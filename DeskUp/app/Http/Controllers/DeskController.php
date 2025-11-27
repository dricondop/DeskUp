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

        $desks = Desk::all();
        
        $isAdmin = false;
        if (Auth::check()) {
            Auth::user()->refresh();
            $isAdmin = Auth::user()->isAdmin();
        }

        return view('desk-control', [
            'desk' => $desk,
            'desks' => $desks,
            'isAdmin' => $isAdmin,
            'isLoggedIn' => Auth::check()
        ]);
    }

    public function updateHeight(Request $request, $id)
    {
        $validated = $request->validate([
            'height' => 'required|integer'
        ]);

        $desk = Desk::findOrFail($id);

        //  $heightMm = $validated['height'] * 10; 

        // $simResponse = \App\Helpers\APIMethods::raiseDesk($heightMm, $desk->id);

        // // Gets the allowed height by the simulator
        // $data = $simResponse->json();
        // $resultInMm = $data['position_mm'] ?? null;

        // if ($resultInMm === null) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Height did not updated successfully',
        //         'response' => $data
        //     ], 500);
        // }

        // $desk->height = $resultInMm;




        $desk->height = $validated['height'];
        $desk->save();

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

    public function addActivity(Request $request, $id)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'scheduled_to' => 'required|date'
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
