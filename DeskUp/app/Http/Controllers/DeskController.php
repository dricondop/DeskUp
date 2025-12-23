<?php

namespace App\Http\Controllers;

use App\Helpers\APIMethods;
use App\Models\Desk;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeskController extends Controller
{
    public function show($id)
    {
        $desk = Desk::with(['events', 'latestStats'])->findOrFail($id);
        
        \Log::info("Desk control page loaded for desk {$desk->id}, current height: {$desk->height}cm");

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
        
        \Log::info("Height update request for desk {$desk->id}: {$height}mm ({$validated['height']}cm)");
        
        try {
            // Send command to physical desk
            APIMethods::raiseDesk($height, $desk->api_desk_id);
            \Log::info("API command sent to physical desk {$desk->api_desk_id}");
            
            // Update database immediately with target height (don't wait for physical movement)
            $desk->newUserStatsHistoryRecord($height);
            \Log::info("Database update completed for desk {$desk->id}");
            
            // Refresh the desk model to load the new stats record
            $desk->refresh();
            $desk->load('latestStats');
            
            $newHeight = $desk->height;
            \Log::info("Desk {$desk->id} height after refresh: {$newHeight}cm");
            
        } catch (\Exception $e) {
            \Log::error("Height update failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update height',
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Height updated successfully',
            'height' => $desk->height // Returns the target height from database
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

    // Get current desk state from API in real-time
    public function getCurrentState($id)
    {
        try {
            $desk = Desk::findOrFail($id);
            
            if (!$desk->api_desk_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desk has no API ID'
                ]);
            }
            
            // Fetch current data from API
            $apiData = APIMethods::getDeskData($desk->api_desk_id);
            
            // Extract current height and status
            $currentHeightMm = $apiData['state']['position_mm'] ?? null;
            $currentStatus = $apiData['state']['status'] ?? 'Unknown';
            $currentSpeedMms = $apiData['state']['speed_mms'] ?? 0;
            
            if ($currentHeightMm === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not retrieve height from API'
                ]);
            }
            
            // Convert mm to cm
            $currentHeightCm = round($currentHeightMm / 10);
            
            return response()->json([
                'success' => true,
                'height' => $currentHeightCm,
                'heightMm' => $currentHeightMm,
                'status' => $currentStatus,
                'speed' => $currentSpeedMms,
                'timestamp' => now()->toIso8601String()
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Failed to get current desk state: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch desk state',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
