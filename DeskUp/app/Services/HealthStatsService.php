<?php

namespace App\Services;

use App\Models\UserStatsHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HealthStatsService
{
    /**
     * Fetch user health stats with optional date filtering.
     *
     * @param int $userId
     * @param string|null $fromDate ISO date string (e.g., '2025-01-01')
     * @param string|null $toDate ISO date string
     * @return Collection
     */
    public function getUserHealthStats(int $userId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = UserStatsHistory::where('user_id', $userId)
            ->orderBy('recorded_at', 'desc');

        if ($fromDate) {
            $query->where('recorded_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('recorded_at', '<=', $toDate);
        }

        return $query->get();
    }

    /**
     * Get aggregated stats for a time range.
     *
     * @param int $userId
     * @param string $range 'today', 'weekly', 'monthly', 'yearly'
     * @return array
     */
    public function getAggregatedStats(int $userId, string $range = 'today'): array
    {
        $dates = $this->getDateRange($range);
        $stats = $this->getUserHealthStats($userId, $dates['from'], $dates['to']);

        if ($stats->isEmpty()) {
            return $this->getEmptyStats();
        }

        // Calculate sitting vs standing percentages
        $sitThreshold = 1000; // mm - adjust based on your desk specs
        $sittingRecords = $stats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->count();
        $standingRecords = $stats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->count();
        $totalRecords = $stats->count();

        $sittingPct = $totalRecords > 0 ? round(($sittingRecords / $totalRecords) * 100) : 0;
        $standingPct = $totalRecords > 0 ? round(($standingRecords / $totalRecords) * 100) : 0;

        // Calculate time-based metrics (assuming each record = 1 hour)
        $totalHours = $totalRecords;
        $sittingHours = round(($sittingRecords / max(1, $totalRecords)) * $totalHours, 1);
        $standingHours = round(($standingRecords / max(1, $totalRecords)) * $totalHours, 1);

        // Calculate averages
        $avgHeight = round($stats->avg('desk_height_mm'));
        $avgSitHeight = $sittingRecords > 0 
            ? round($stats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->avg('desk_height_mm') / 10) // convert to cm
            : 72;
        $avgStandHeight = $standingRecords > 0 
            ? round($stats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->avg('desk_height_mm') / 10) // convert to cm
            : 110;

        // Estimate breaks (changes from sitting to standing or vice versa)
        $breaks = $this->calculateBreaks($stats, $sitThreshold);
        
        // Estimate calories (very rough: 0.15 kcal/min standing vs sitting)
        $caloriesPerHour = 10; // approximate difference
        $calories = round($standingHours * $caloriesPerHour);

        return [
            'total_activations' => $stats->sum('activations_count'),
            'total_sit_stand' => $stats->sum('sit_stand_count'),
            'avg_height_mm' => $avgHeight,
            'records_count' => $totalRecords,
            'error_count' => $stats->filter(function($stat) {
                return $stat->is_position_lost || $stat->is_overload_up || $stat->is_overload_down;
            })->count(),
            
            'sitting_pct' => $sittingPct,
            'standing_pct' => $standingPct,
            'sitting_hours' => $sittingHours,
            'standing_hours' => $standingHours,
            'active_hours' => round($totalHours, 1),
            'avg_sit_height_cm' => $avgSitHeight,
            'avg_stand_height_cm' => $avgStandHeight,
            'breaks_per_day' => $breaks,
            'calories_per_day' => $calories,
        ];
    }

    /**
     * Get chart data formatted for frontend consumption.
     */
    public function getChartData(int $userId, string $range = 'today'): array
    {
        $dates = $this->getDateRange($range);
        $stats = $this->getUserHealthStats($userId, $dates['from'], $dates['to']);

        if ($stats->isEmpty()) {
            return $this->getEmptyChartData($range);
        }

        $sitThreshold = 1000;

        // Group by time buckets
        $buckets = $this->groupByTimeBuckets($stats, $range);

        return [
            'labels' => $buckets['labels'],
            'sitting_hours' => $buckets['sitting_hours'],
            'standing_hours' => $buckets['standing_hours'],
            'posture_scores' => $buckets['posture_scores'],
            'avg_sit_heights' => $buckets['avg_sit_heights'],
            'avg_stand_heights' => $buckets['avg_stand_heights'],
        ];
    }

    /**
     * Get live desk status.
     */
    public function getLiveStatus(int $userId): array
    {
        $latestStat = UserStatsHistory::where('user_id', $userId)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latestStat) {
            return [
                'mode' => 'Unknown',
                'height_cm' => 0,
                'last_adjusted' => 'Never',
            ];
        }

        $heightCm = round($latestStat->desk_height_mm / 10);
        $mode = $latestStat->desk_height_mm < 850 ? 'Sitting' : 'Standing';
        $minutesAgo = round((now()->diffInMinutes($latestStat->recorded_at))*-1);
        
        $lastAdjusted = $minutesAgo < 60 
            ? "{$minutesAgo}m ago" 
            : round($minutesAgo / 60) . 'h ago';

        return [
            'mode' => $mode,
            'height_cm' => $heightCm,
            'last_adjusted' => $lastAdjusted,
            'status' => $latestStat->desk_status,
        ];
    }

    private function calculateBreaks(Collection $stats, int $sitThreshold): int
    {
        $breaks = 0;
        $prevMode = null;

        foreach ($stats->sortBy('recorded_at') as $stat) {
            $currentMode = $stat->desk_height_mm < $sitThreshold ? 'sit' : 'stand';
            if ($prevMode && $prevMode !== $currentMode) {
                $breaks++;
            }
            $prevMode = $currentMode;
        }

        return $breaks;
    }

    
    private function groupByTimeBuckets(Collection $stats, string $range): array
    {
        $sitThreshold = 1000;
        $labels = [];
        $sitting_hours = [];  // Changed from $sittingHours
        $standing_hours = []; // Changed from $standingHours
        $posture_scores = []; // Changed from $postureScores
        $avg_sit_heights = []; // Changed from $avgSitHeights
        $avg_stand_heights = []; // Changed from $avgStandHeights

        switch ($range) {
            case 'today':
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('H:00'));
                foreach (range(8, 17) as $hour) {
                    $key = sprintf('%02d:00', $hour);
                    $labels[] = $key;
                    $hourStats = $grouped->get($key, collect());
                    
                    $sit = $hourStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->count();
                    $stand = $hourStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->count();
                    $total = max(1, $sit + $stand);
                    
                    $sitting_hours[] = round($sit / $total, 2);
                    $standing_hours[] = round($stand / $total, 2);
                    $posture_scores[] = round((($stand / $total) * 100), 1);
                    $avg_sit_heights[] = $sit > 0 ? round($hourStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($hourStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->avg('desk_height_mm') / 10) : 110;
                }
                break;

            case 'weekly':
                $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('D'));
                foreach ($daysOfWeek as $day) {
                    $labels[] = $day;
                    $dayStats = $grouped->get($day, collect());
                    
                    $sit = $dayStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->count();
                    $stand = $dayStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->count();
                    $total = max(1, $sit + $stand);
                    
                    $sitting_hours[] = round($sit / $total, 2);
                    $standing_hours[] = round($stand / $total, 2);
                    $posture_scores[] = round((($stand / $total) * 100), 1);
                    $avg_sit_heights[] = $sit > 0 ? round($dayStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($dayStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->avg('desk_height_mm') / 10) : 110;
                }
                break;

            case 'monthly':
                for ($i = 1; $i <= 4; $i++) {
                    $labels[] = "Week {$i}";
                    $weekStats = $stats->filter(fn($s) => ceil($s->recorded_at->day / 7) == $i);
                    
                    $sit = $weekStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->count();
                    $stand = $weekStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->count();
                    $total = max(1, $sit + $stand);
                    
                    $sitting_hours[] = round($sit / $total, 2);
                    $standing_hours[] = round($stand / $total, 2);
                    $posture_scores[] = round((($stand / $total) * 100), 1);
                    $avg_sit_heights[] = $sit > 0 ? round($weekStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($weekStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->avg('desk_height_mm') / 10) : 110;
                }
                break;

            case 'yearly':
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('M'));
                foreach ($months as $month) {
                    $labels[] = $month;
                    $monthStats = $grouped->get($month, collect());
                    
                    $sit = $monthStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->count();
                    $stand = $monthStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->count();
                    $total = max(1, $sit + $stand);
                    
                    $sitting_hours[] = round($sit / $total, 2);
                    $standing_hours[] = round($stand / $total, 2);
                    $posture_scores[] = round((($stand / $total) * 100), 1);
                    $avg_sit_heights[] = $sit > 0 ? round($monthStats->filter(fn($s) => $s->desk_height_mm < $sitThreshold)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($monthStats->filter(fn($s) => $s->desk_height_mm >= $sitThreshold)->avg('desk_height_mm') / 10) : 110;
                }
                break;
    }

    return compact('labels', 'sitting_hours', 'standing_hours', 'posture_scores', 'avg_sit_heights', 'avg_stand_heights');
    }

    private function getEmptyChartData(string $range): array
    {
        return [
            'labels' => [],
            'sitting_hours' => [],
            'standing_hours' => [],
            'posture_scores' => [],
            'avg_sit_heights' => [],
            'avg_stand_heights' => [],
        ];
    }

    private function getDateRange(string $range): array
    {
        $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
        $now = $latestRecord ? $latestRecord->recorded_at : now();
        
        return match($range) {
            'today' => [
                'from' => $now->copy()->startOfDay()->toDateTimeString(),
                'to' => $now->copy()->endOfDay()->toDateTimeString(),
            ],
            'weekly' => [
                'from' => $now->copy()->subWeek()->toDateTimeString(),
                'to' => $now->copy()->toDateTimeString(),
            ],
            'monthly' => [
                'from' => $now->copy()->subMonth()->toDateTimeString(),
                'to' => $now->copy()->toDateTimeString(),
            ],
            'yearly' => [
                'from' => $now->copy()->subYear()->toDateTimeString(),
                'to' => $now->copy()->toDateTimeString(),
            ],
            default => [
                'from' => null,
                'to' => null,
            ],
        };
    }

    private function getEmptyStats(): array
    {
        return [
            'total_activations' => 0,
            'total_sit_stand' => 0,
            'avg_height_mm' => 0,
            'records_count' => 0,
            'error_count' => 0,
            'sitting_pct' => 65,
            'standing_pct' => 35,
            'sitting_hours' => 0,
            'standing_hours' => 0,
            'active_hours' => 0,
            'avg_sit_height_cm' => 72,
            'avg_stand_height_cm' => 110,
            'breaks_per_day' => 0,
            'calories_per_day' => 0,
        ];
    }
}