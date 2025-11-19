<?php

namespace App\Http\Controllers;

use App\Models\Desk;
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
        $desk = Desk::with('activities')->findOrFail($id);
        
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
            'isAdmin' => $isAdmin,
            'isLoggedIn' => Auth::check()
        ]);
    }

    public function showAssignedDesk()
    {
        $user = Auth::user();
        $desk = Desk::where('user_id', $user->id)->with('activities')->firstOrFail();
        
        return view('desk-control', [
            'desk' => $desk,
            'isAdmin' => $user->isAdmin(),
            'isLoggedIn' => true,
        ]);
    }

    public function updateHeight(Request $request, $id)
    {
        $validated = $request->validate([
            'height' => 'required|integer|min=0|max:150'
        ]);

        $desk = Desk::findOrFail($id);
        
        // If desk is connected to API, update via API
        if ($desk->isConnectedToAPI()) {
            try {
                $result = $this->syncService->updateDeskPosition($desk, $validated['height']);
                return response()->json($result);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update desk via API: ' . $e->getMessage()
                ], 500);
            }
        }
        
        // Otherwise update database only
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
