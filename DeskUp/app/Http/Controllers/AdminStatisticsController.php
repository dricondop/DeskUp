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

        // Occupied by current desk status (your existing logic)
        $occupiedByStatus = $desks
            ->filter(fn (Desk $desk) => $desk->status !== 'OK')
            ->count();

        // Occupied by recent usage records (last hour) from user_stats_history
        // NOTE: in your seeder, user_stats_history.desk_id stores the desk_number (not desks.id)
        $occupiedByRecentActivity = UserStatsHistory::where('recorded_at', '>=', now()->subHour())
            ->distinct('desk_id')
            ->count('desk_id');

        // Keep your max logic
        $occupiedDesks = max($occupiedByStatus, $occupiedByRecentActivity);

        // Average active time (approx.) from real records
        // Definition: average minutes per user per day over last 7 days
        $days = 7;
        $since = now()->subDays($days);

        $totalRecords = UserStatsHistory::where('recorded_at', '>=', $since)->count();
        $distinctUsers = UserStatsHistory::where('recorded_at', '>=', $since)->distinct('user_id')->count('user_id');

        $avgSession = $distinctUsers > 0
            ? ($totalRecords * 60.0) / $distinctUsers / $days
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
}
