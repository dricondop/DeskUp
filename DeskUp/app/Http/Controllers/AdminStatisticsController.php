<?php

namespace App\Http\Controllers;

use App\Models\Desk;
use App\Models\User;
use App\Models\UserStatsHistory;
use Carbon\Carbon;

class AdminStatisticsController extends Controller
{
    public function index()
    {
        // Desks list (keep your existing relation)
        $desks = Desk::with('latestStats')
            ->orderBy('desk_number')
            ->get();

        $totalDesks = $desks->count();

        // Improved occupied desks calculation:
        // 1. Count desks with assigned users
        // 2. Count desks with recent activity (last 10 minutes)
        // 3. Count desks with error status
        
        $desksWithUsers = User::whereNotNull('assigned_desk_id')->count();
        
        $desksWithRecentActivity = UserStatsHistory::where('recorded_at', '>=', now()->subMinutes(10))
            ->distinct('desk_id')
            ->count('desk_id');
        
        $desksWithErrors = $desks->filter(fn (Desk $desk) => $desk->status !== 'OK')->count();

        // Use the maximum to ensure we don't undercount
        $occupiedDesks = max($desksWithUsers, $desksWithRecentActivity, $desksWithErrors);

        // Average active time (approx.) from real records
        // Definition: average minutes per user per day over last 7 days
        $days = 7;
        $since = now()->subDays($days);

        $totalRecords = UserStatsHistory::where('recorded_at', '>=', $since)->count();
        $distinctUsers = UserStatsHistory::where('recorded_at', '>=', $since)->distinct('user_id')->count('user_id');

        $avgSession = $distinctUsers > 0
            ? round(($totalRecords * 60.0) / $distinctUsers / $days)
            : 0;

        // Top users by number of usage records (real)
        $topUsers = UserStatsHistory::select('users.name')
            ->join('users', 'users.id', '=', 'user_stats_history.user_id')
            ->where('user_stats_history.recorded_at', '>=', $since)
            ->selectRaw('users.name, COUNT(*) as usage_count')
            ->groupBy('users.name')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->usage_count])
            ->toArray();

        // Users list (keep)
        $users = User::orderBy('name')->get();

        // Heatmap grid: 7 days × 24 hours, based on recorded_at (real)
        // Your UI expects Mon..Sun order: 0=Mon ... 6=Sun
        $grid = array_fill(0, 7, array_fill(0, 24, 0));

        $heatRows = UserStatsHistory::where('recorded_at', '>=', $since)
            ->selectRaw('EXTRACT(DOW FROM recorded_at) as dow, EXTRACT(HOUR FROM recorded_at) as hr, COUNT(*) as c')
            ->groupBy('dow', 'hr')
            ->get();

        foreach ($heatRows as $row) {
            $dow = (int) $row->dow;   // Postgres: 0=Sunday ... 6=Saturday
            $hr  = (int) $row->hr;    // 0..23

            // Map to Mon..Sun: Mon=0 ... Sun=6
            $mapped = ($dow === 0) ? 6 : ($dow - 1);

            $grid[$mapped][$hr] = (int) $row->c;
        }

        $heatmapGrid = $grid;

        return view('admin-statistics', compact(
            'totalDesks',
            'occupiedDesks',
            'avgSession',
            'topUsers',
            'desks',
            'users',
            'heatmapGrid'
        ));
    }

    public function getLiveData()
    {
        // Optimized: Use single query with eager loading
        $totalDesks = Desk::count();

        // Optimized: Combined queries for occupied desk calculation
        $desksWithUsers = User::whereNotNull('assigned_desk_id')->count();
        
        $desksWithRecentActivity = UserStatsHistory::where('recorded_at', '>=', now()->subMinutes(10))
            ->distinct('desk_id')
            ->count('desk_id');
        
        // Only load desks with stats when needed for error check
        $desksWithErrors = Desk::with('latestStats')
            ->get()
            ->filter(fn ($desk) => $desk->status !== 'OK')
            ->count();

        $occupiedDesks = max($desksWithUsers, $desksWithRecentActivity, $desksWithErrors);
        $availableDesks = max(0, $totalDesks - $occupiedDesks);

        // Optimized: Use single aggregate query for session stats
        $days = 7;
        $since = now()->subDays($days);

        $sessionStats = UserStatsHistory::where('recorded_at', '>=', $since)
            ->selectRaw('COUNT(*) as total_records, COUNT(DISTINCT user_id) as distinct_users')
            ->first();

        $avgSession = $sessionStats && $sessionStats->distinct_users > 0
            ? round(($sessionStats->total_records * 60.0) / $sessionStats->distinct_users / $days)
            : 0;

        // Optimized: Top users with single query
        $topUsers = UserStatsHistory::select('users.name')
            ->join('users', 'users.id', '=', 'user_stats_history.user_id')
            ->where('user_stats_history.recorded_at', '>=', $since)
            ->selectRaw('users.name, COUNT(*) as usage_count')
            ->groupBy('users.name')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->usage_count])
            ->toArray();

        // Optimized: Get desk usage counts in single query
        $deskUsageCounts = UserStatsHistory::where('recorded_at', '>=', $since)
            ->selectRaw('desk_id, COUNT(*) as usage_count')
            ->groupBy('desk_id')
            ->pluck('usage_count', 'desk_id');

        $deskList = Desk::with('latestStats')
            ->orderBy('desk_number')
            ->get()
            ->map(function ($desk) use ($deskUsageCounts) {
                return [
                    'name' => $desk->name,
                    'status' => $desk->status,
                    'avgTime' => $deskUsageCounts[$desk->desk_number] ?? 0
                ];
            });

        return response()->json([
            'totalDesks' => $totalDesks,
            'occupiedDesks' => $occupiedDesks,
            'availableDesks' => $availableDesks,
            'avgSession' => $avgSession,
            'topUsers' => $topUsers,
            'deskList' => $deskList
        ]);
    }
}
