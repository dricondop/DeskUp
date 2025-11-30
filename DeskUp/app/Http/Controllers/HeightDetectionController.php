<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\HeightDetection;
use App\Models\UserProfile;


class HeightDetectionController extends Controller
{
    private $pythonServiceUrl;

    public function __construct()
    {
        $this->pythonServiceUrl = config('services.python_service.url');
    }

    /**
     * Posture analysis page
     */
    public function showAnalysis()
    {
        return view('posture-analysis');
    }

    /**
     * Process analysis
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'user_height' => 'nullable|numeric|min:100|max:250'
        ]);

        try {
            Log::info('Starting height analysis for user: ' . Auth::id());

            // Send to python
            $response = Http::timeout(30)->post($this->pythonServiceUrl . '/detect', [
                'image' => $request->image,
                'user_height' => $request->user_height,
                'user_id' => Auth::id()
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // Save in database (IMPLEMENT THIS)
                    $heightDetection = HeightDetection::create([
                        'user_id' => Auth::id(),
                        'detected_height' => $data['user_height'],
                        'recommended_height' => $data['recommended_desk_height'],
                        'posture_data' => $data['posture_analysis'],
                    ]);

                    // Update ideal_height in user_profiles (create new table)
                    $this->updateUserProfileIdealHeight($data['recommended_desk_height']);

                    Log::info('Analysis completed for user: ' . Auth::id() . ', Recommended height: ' . $data['recommended_desk_height']);

                    return response()->json([
                        'success' => true,
                        'detection_id' => $heightDetection->id,
                        'user_height' => $data['user_height'],
                        'recommended_height' => $data['recommended_desk_height'],
                        'posture_score' => $data['posture_analysis']['posture_score'] ?? 0,
                        'posture_issues' => $data['posture_analysis']['issues'] ?? [],
                        'message' => 'Analysis completed successfully'
                    ]);

                } else {
                    Log::error('Error in Python service: ' . ($data['error'] ?? 'Unknown error'));
                    return response()->json([
                        'success' => false,
                        'error' => $data['error'] ?? 'Error in image analysis'
                    ], 400);
                }
            }

            Log::error('Could not connect to Python service: ' . $response->status());
            return response()->json([
                'success' => false,
                'error' => 'Could not connect to the analysis service. Code: ' . $response->status()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Height detection error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ideal_height in user_profiles
     */
    private function updateUserProfileIdealHeight($recommendedHeight)
    {
        try {
            $userProfile = UserProfile::where('user_id', Auth::id())->first();
            
            if ($userProfile) {
                $userProfile->update([
                    'ideal_height' => (int) round($recommendedHeight)
                ]);
                Log::info('Updated ideal_height for user ' . Auth::id() . ': ' . $recommendedHeight);
            } else {
                // Create profile (maybe we implement this)
                UserProfile::create([
                    'user_id' => Auth::id(),
                    'ideal_height' => (int) round($recommendedHeight),
                    'location' => 'Unknown', 
                    'phone' => '+45 00000000' 
                ]);
                Log::info('Created new user_profile for user ' . Auth::id() . ' with ideal_height: ' . $recommendedHeight);
            }
        } catch (\Exception $e) {
            Log::error('Error updating user profile ideal_height: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Show results
     */
    public function result($id)
    {
        $detection = HeightDetection::with('user')->findOrFail($id);
        
        if ($detection->user_id !== Auth::id()) {
            abort(403);
        }

        return view('height-detection.result', compact('detection'));
    }

    /**
     * Get user history
     */
    public function history()
    {
        $detections = HeightDetection::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $detections
        ]);
    }
    
    /**
     * Verify python service health
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout(5)->get($this->pythonServiceUrl . '/health');
            
            return response()->json([
                'success' => $response->successful(),
                'status' => $response->successful() ? 'connected' : 'disconnected',
                'python_service' => $response->json() ?? ['error' => 'No response']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'disconnected',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
