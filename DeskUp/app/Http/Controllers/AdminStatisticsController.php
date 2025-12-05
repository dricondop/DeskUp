<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Desk;
use App\Models\User;
use App\Models\DeskActivity;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminStatisticsController extends Controller
{
    public function index()
    {

        $desks = Desk::with('latestStats')
            ->orderBy('desk_number')
            ->get();

        $totalDesks = $desks->count();

        $occupiedByStatus = $desks
            ->filter(fn (Desk $desk) => $desk->status !== 'OK')
            ->count();

        $occupiedByRecentActivity = DeskActivity::where('scheduled_at', '>=', now()->subHour())
            ->distinct('desk_id')
            ->count('desk_id');

        
        $occupiedDesks = max($occupiedByStatus, $occupiedByRecentActivity);

        $avgSession = 45;

        $topUsers = [];
        if (Schema::hasColumn('desk_activities', 'user_id')) {
            
            $topUsers = User::select('users.id', 'users.name')
                ->join('desk_activities', 'users.id', '=', 'desk_activities.user_id')
                ->selectRaw('users.name, COUNT(desk_activities.id) as activities_count')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('activities_count')
                ->limit(5)
                ->get()
                ->map(function($r) {
                    return ['name' => $r->name, 'count' => (int)$r->activities_count];
                })->toArray();
        } else {
            
            $topUsers = [];
        }


        $users = User::orderBy('name')->get();
       
        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        
        $activities = DeskActivity::select('scheduled_at')->get();
        foreach ($activities as $act) {
            if (!$act->scheduled_at) continue;
           
            $dt = Carbon::parse($act->scheduled_at);
         
            $dow = $dt->dayOfWeek; 
            
            $mapped = ($dow === 0) ? 6 : ($dow - 1);
            $hour = (int)$dt->format('G'); // 0..23
            $grid[$mapped][$hour] = ($grid[$mapped][$hour] ?? 0) + 1;
        }
  
        $heatmapGrid = array_map(function($row) {
            return array_map('intval', $row);
        }, $grid);

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
