<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\UserProfile;
use App\Models\Desk;

class HeightDetectionController extends Controller
{
    /**
     * Show posture analysis view (route: posture-analysis)
     */
    public function showAnalysis()
    {
        // Get user's assigned desk
        $user = auth()->user();
        $desk = $user->assignedDesk; // Use relationship defined in User.php
        
        // Get current desk height from latestStats
        $currentHeight = $desk ? $desk->height : 0; // height is an accessor that already converts mm to cm
        
        return view('posture-analysis', [
            'currentHeight' => $currentHeight
        ]);
    }

    /**
     * Process posture analysis (route: height-detection/analyze)
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'current_height' => 'required|numeric|min:0|max:200'
        ]);

        try {
            // 1. Get data
            $imageData = $request->input('image');
            $currentHeight = $request->input('current_height');
            
            // 2. Clean base64 if it has a prefix
            $base64Image = $imageData;
            if (strpos($imageData, 'base64,') !== false) {
                $base64Image = explode('base64,', $imageData)[1];
            }
            
            // 3. Send to Python service
            $response = Http::post('http://localhost:5001/analyze', [
                'image' => $base64Image,
                'current_height' => (float)$currentHeight
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Python service error: ' . $response->status());
            }
            
            $pythonResult = $response->json();
            
            // 4. Validate Python service response
            if (!isset($pythonResult['ideal_height'])) {
                throw new \Exception('Invalid response from Python service');
            }
            
            $idealHeight = $pythonResult['ideal_height'];
            
            // 5. Save to user_profiles
            $userProfile = UserProfile::firstOrCreate(
                ['user_id' => auth()->id()],
                ['ideal_height' => null] // Default values
            );
            
            $userProfile->ideal_height = $idealHeight;
            $userProfile->save();
            
            // 6. Return response
            return response()->json([
                'success' => true,
                'ideal_height' => $idealHeight,
                'current_height' => $currentHeight,
                'message' => 'Ideal height calculated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show analysis result (route: height-detection/result/{id})
     */
    public function result($id)
    {
        // This function could show a specific result if you decide to save history
        // For now, redirect to profile
        return redirect()->route('profile')->with('success', 'Ideal height saved successfully');
    }

    /**
     * View analysis history (route: height-detection/history)
     */
    public function history()
    {
        // If you decide to implement history in the future
        return view('height-detection-history', [
            'analyses' => []
        ]);
    }

    /**
     * Check service health (route: height-detection/health)
     */
    public function healthCheck()
    {
        try {
            // Check if Python service is running
            $response = Http::timeout(3)->get('http://localhost:5001/health');
            
            return response()->json([
                'python_service' => $response->successful() ? 'online' : 'offline',
                'message' => $response->successful() ? 'All services are running' : 'Python service unavailable'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'python_service' => 'offline',
                'message' => 'Python service not responding: ' . $e->getMessage()
            ], 503);
        }
    }

    /**
     * Get current desk height (new API function)
     */
    public function getCurrentHeight()
    {
        try {
            $user = auth()->user();
            $desk = $user->assignedDesk;
            
            if (!$desk) {
                return response()->json([
                    'current_height' => 0,
                    'message' => 'No desk assigned to user'
                ]);
            }
            
            return response()->json([
                'current_height' => $desk->height, // Already in cm due to accessor
                'desk_name' => $desk->name,
                'desk_id' => $desk->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'current_height' => 0,
                'error' => 'Failed to get current height: ' . $e->getMessage()
            ], 500);
        }
    }
}