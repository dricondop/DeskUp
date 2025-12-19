<?php

namespace App\Http\Controllers;

use App\Services\HealthStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    protected HealthStatsService $healthService;

    public function __construct(HealthStatsService $healthService)
    {
        $this->healthService = $healthService;
    }

    public function index()
    {
        return view('health');
    }

    /**
     * API endpoint to fetch aggregated health stats.
     */
    public function getStats(Request $request)
    {
        $userId = Auth::id();
        $range = $request->query('range', 'today');

        try {
            $aggregated = $this->healthService->getAggregatedStats($userId, $range);
            return response()->json($aggregated);
        } catch (\Exception $e) {
            Log::error('Health stats error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'range' => $range,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch health stats', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to fetch detailed time-series data for charts.
     */
    public function getChartData(Request $request)
    {
        $userId = Auth::id();
        $range = $request->query('range', 'today');

        try {
            $chartData = $this->healthService->getChartData($userId, $range);
            return response()->json($chartData);
        } catch (\Exception $e) {
            Log::error('Chart data error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'range' => $range,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch chart data', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to fetch live desk status.
     */
    public function getLiveStatus(Request $request)
    {
        $userId = Auth::id();

        try {
            $liveStatus = $this->healthService->getLiveStatus($userId);
            return response()->json($liveStatus);
        } catch (\Exception $e) {
            Log::error('Live status error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch live status', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Combined API endpoint to fetch all health data at once for instant page load.
     * Returns stats, chart data, and live status in a single response.
     */
    public function getAllData(Request $request)
    {
        $userId = Auth::id();
        $range = $request->query('range', 'today');

        try {
            $data = [
                'stats' => $this->healthService->getAggregatedStats($userId, $range),
                'chartData' => $this->healthService->getChartData($userId, $range),
                'liveStatus' => $this->healthService->getLiveStatus($userId),
            ];
            
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Health all data error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'range' => $range,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch health data', 'message' => $e->getMessage()], 500);
        }
    }
}