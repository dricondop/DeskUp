<?php

namespace App\Services;

use App\Models\UserStatsHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HealthStatsService
{
    // Threshold in mm to differentiate sitting vs standing
    private const SIT_THRESHOLD_MM = 1000;
    
    // Ideal ergonomic heights (in mm)
    private const IDEAL_SIT_HEIGHT_MM = 720; // ~72cm
    private const IDEAL_STAND_HEIGHT_MM = 1100; // ~110cm
    
    // Scoring weights
    private const WEIGHT_STANDING_RATIO = 0.40;
    private const WEIGHT_BREAKS = 0.20;
    private const WEIGHT_VARIATION = 0.20;
    private const WEIGHT_ERGONOMICS = 0.20;

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
     * Calculate comprehensive posture score (0-100)
     * Takes into account multiple health factors
     */
    private function calculatePostureScore(Collection $stats, bool $isHourlyData = false): int
    {
        if ($stats->isEmpty()) {
            return 50; // Neutral score for no data
        }

        // For hourly/small datasets, use simplified scoring
        if ($isHourlyData || $stats->count() <= 3) {
            return $this->calculateSimplifiedPostureScore($stats);
        }

        // 1. Standing ratio score (40% weight)
        $standingScore = $this->calculateStandingRatioScore($stats);
        
        // 2. Break frequency score (20% weight)
        $breaksScore = $this->calculateBreaksScore($stats);
        
        // 3. Posture variation score (20% weight)
        $variationScore = $this->calculateVariationScore($stats);
        
        // 4. Ergonomic height usage score (20% weight)
        $ergonomicsScore = $this->calculateErgonomicsScore($stats);
        
        // Weighted average
        $finalScore = (
            ($standingScore * self::WEIGHT_STANDING_RATIO) +
            ($breaksScore * self::WEIGHT_BREAKS) +
            ($variationScore * self::WEIGHT_VARIATION) +
            ($ergonomicsScore * self::WEIGHT_ERGONOMICS)
        );
        
        return (int) round(max(0, min(100, $finalScore)));
    }

    /**
     * Simplified posture score for small datasets (hourly view)
     * Only considers standing ratio and ergonomic heights
     */
    private function calculateSimplifiedPostureScore(Collection $stats): int
    {
        $standingRecords = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
        $totalRecords = $stats->count();
        $standingPct = ($standingRecords / max(1, $totalRecords)) * 100;
        
        // 70% weight on standing ratio (simplified)
        $standingScore = $this->calculateStandingRatioScore($stats);
        
        // 30% weight on ergonomics
        $ergonomicsScore = $this->calculateErgonomicsScore($stats);
        
        $finalScore = ($standingScore * 0.7) + ($ergonomicsScore * 0.3);
        
        return (int) round(max(0, min(100, $finalScore)));
    }

    /**
     * Score based on standing vs sitting ratio
     * Optimal: 40-50% standing
     */
    private function calculateStandingRatioScore(Collection $stats): float
    {
        $standingRecords = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
        $totalRecords = $stats->count();
        $standingPct = ($standingRecords / max(1, $totalRecords)) * 100;
        
        // Optimal range: 40-50% standing
        if ($standingPct >= 40 && $standingPct <= 50) {
            return 100;
        } elseif ($standingPct >= 30 && $standingPct < 40) {
            return 80 + (($standingPct - 30) * 2); // Linear 80-100
        } elseif ($standingPct > 50 && $standingPct <= 60) {
            return 100 - (($standingPct - 50) * 2); // Linear 100-80
        } elseif ($standingPct >= 20 && $standingPct < 30) {
            return 60 + (($standingPct - 20) * 2); // Linear 60-80
        } elseif ($standingPct > 60 && $standingPct <= 70) {
            return 80 - (($standingPct - 60) * 2); // Linear 80-60
        } else {
            // Below 20% or above 70% is suboptimal
            return max(30, 60 - abs($standingPct - 45));
        }
    }

    /**
     * Score based on sit-stand transitions
     * Optimal: 3-6 transitions per 8-hour workday
     */
    private function calculateBreaksScore(Collection $stats): float
    {
        $transitions = $this->calculateTransitions($stats);
        $hoursTracked = $stats->count(); // Assuming 1 record per hour
        $transitionsPerEightHours = $hoursTracked > 0 ? ($transitions / $hoursTracked) * 8 : 0;
        
        // Optimal: 3-6 transitions per 8 hours
        if ($transitionsPerEightHours >= 3 && $transitionsPerEightHours <= 6) {
            return 100;
        } elseif ($transitionsPerEightHours >= 2 && $transitionsPerEightHours < 3) {
            return 70 + (($transitionsPerEightHours - 2) * 30);
        } elseif ($transitionsPerEightHours > 6 && $transitionsPerEightHours <= 8) {
            return 100 - (($transitionsPerEightHours - 6) * 15);
        } elseif ($transitionsPerEightHours >= 1 && $transitionsPerEightHours < 2) {
            return 40 + (($transitionsPerEightHours - 1) * 30);
        } else {
            return max(20, 40 - abs($transitionsPerEightHours - 4.5) * 5);
        }
    }

    /**
     * Score based on height variation within sitting/standing modes
     * Rewards micro-adjustments for comfort
     */
    private function calculateVariationScore(Collection $stats): float
    {
        $sittingHeights = $stats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)
            ->pluck('desk_height_mm');
        $standingHeights = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)
            ->pluck('desk_height_mm');
        
        $sitVariation = $sittingHeights->count() > 1 
            ? $this->calculateStandardDeviation($sittingHeights) 
            : 0;
        $standVariation = $standingHeights->count() > 1 
            ? $this->calculateStandardDeviation($standingHeights) 
            : 0;
        
        // Optimal variation: 30-80mm (micro-adjustments without extreme changes)
        $avgVariation = ($sitVariation + $standVariation) / 2;
        
        if ($avgVariation >= 30 && $avgVariation <= 80) {
            return 100;
        } elseif ($avgVariation >= 15 && $avgVariation < 30) {
            return 70 + (($avgVariation - 15) / 15) * 30;
        } elseif ($avgVariation > 80 && $avgVariation <= 120) {
            return 100 - (($avgVariation - 80) / 40) * 30;
        } elseif ($avgVariation < 15) {
            return 50 + ($avgVariation / 15) * 20; // Too static
        } else {
            return max(40, 70 - ($avgVariation - 120) / 10); // Too much variation
        }
    }

    /**
     * Score based on how close heights are to ergonomic ideals
     */
    private function calculateErgonomicsScore(Collection $stats): float
    {
        $sittingHeights = $stats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
        $standingHeights = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);
        
        $sitScore = 50;
        $standScore = 50;
        
        if ($sittingHeights->count() > 0) {
            $avgSitHeight = $sittingHeights->avg('desk_height_mm');
            $sitDeviation = abs($avgSitHeight - self::IDEAL_SIT_HEIGHT_MM);
            // Perfect at ideal, decreases with deviation
            $sitScore = max(30, 100 - ($sitDeviation / 5));
        }
        
        if ($standingHeights->count() > 0) {
            $avgStandHeight = $standingHeights->avg('desk_height_mm');
            $standDeviation = abs($avgStandHeight - self::IDEAL_STAND_HEIGHT_MM);
            // Perfect at ideal, decreases with deviation
            $standScore = max(30, 100 - ($standDeviation / 5));
        }
        
        // Average both scores
        return ($sitScore + $standScore) / 2;
    }

    /**
     * Calculate standard deviation for height variations
     */
    private function calculateStandardDeviation(Collection $values): float
    {
        if ($values->count() < 2) {
            return 0;
        }
        
        $mean = $values->avg();
        $variance = $values->map(fn($val) => pow($val - $mean, 2))->avg();
        
        return sqrt($variance);
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
        $sittingRecords = $stats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
        $standingRecords = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
        $totalRecords = $stats->count();

        $sittingPct = $totalRecords > 0 ? round(($sittingRecords / $totalRecords) * 100) : 0;
        $standingPct = $totalRecords > 0 ? round(($standingRecords / $totalRecords) * 100) : 0;

        // Calculate comprehensive posture score
        $postureScore = $this->calculatePostureScore($stats);

        // Calculate time-based metrics (assuming each record = 1 hour)
        $totalHours = $totalRecords;
        $sittingHours = round(($sittingRecords / max(1, $totalRecords)) * $totalHours, 1);
        $standingHours = round(($standingRecords / max(1, $totalRecords)) * $totalHours, 1);

        // Calculate averages
        $avgHeight = round($stats->avg('desk_height_mm'));
        $avgSitHeight = $sittingRecords > 0 
            ? round($stats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) // convert to cm
            : 72;
        $avgStandHeight = $standingRecords > 0 
            ? round($stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) // convert to cm
            : 110;

        // Estimate breaks (changes from sitting to standing or vice versa)
        $transitions = $this->calculateTransitions($stats);
        
        // Estimate calories (very rough: 0.15 kcal/min standing vs sitting)
        $caloriesPerHour = 9; // approximate difference
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
            'position_changes' => $transitions,
            'calories_per_day' => $calories,
            'posture_score' => $postureScore, // NEW: Comprehensive score
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

        // Group by time buckets
        $buckets = $this->groupByTimeBuckets($stats, $range);

        return [
            'labels' => $buckets['labels'],
            'sitting_hours' => $buckets['sitting_hours'],
            'standing_hours' => $buckets['standing_hours'],
            'posture_scores' => $buckets['posture_scores'],
            'avg_sit_heights' => $buckets['avg_sit_heights'],
            'avg_stand_heights' => $buckets['avg_stand_heights'],
            'height_overview' => $buckets['height_overview'],
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
        $mode = $latestStat->desk_height_mm < self::SIT_THRESHOLD_MM ? 'Sitting' : 'Standing';
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

    private function calculateTransitions(Collection $stats): int
    {
        $transitions = 0;
        $prevMode = null;

        foreach ($stats->sortBy('recorded_at') as $stat) {
            $currentMode = $stat->desk_height_mm < self::SIT_THRESHOLD_MM ? 'sit' : 'stand';
            if ($prevMode && $prevMode !== $currentMode) {
                $transitions++;
            }
            $prevMode = $currentMode;
        }

        return $transitions;
    }

    
    private function groupByTimeBuckets(Collection $stats, string $range): array
    {
        $labels = [];
        $sitting_hours = [];
        $standing_hours = [];
        $posture_scores = [];
        $avg_sit_heights = [];
        $avg_stand_heights = [];
        $height_overview = [];

        switch ($range) {
            case 'today':
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('H:00'));
                
                // Determine the actual hour range from available data
                if ($stats->isNotEmpty()) {
                    $minHour = (int) $stats->min('recorded_at')->format('G');
                    $maxHour = (int) $stats->max('recorded_at')->format('G');
                } else {
                    // Fallback to 8-17 if no data
                    $minHour = 8;
                    $maxHour = 17;
                }
                
                foreach (range($minHour, $maxHour) as $hour) {
                    $key = sprintf('%02d:00', $hour);
                    $labels[] = $key;
                    $hourStats = $grouped->get($key, collect());
                    
                    $sit = $hourStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $hourStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;

                    // Use simplified score for hourly data
                    $posture_scores[] = $hourStats->isNotEmpty() 
                        ? $this->calculatePostureScore($hourStats, true)
                        : 50;
                    
                    $avg_sit_heights[] = $sit > 0 ? round($hourStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($hourStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 110;
                
                    // HEIGHT OVERVIEW LOGIC
                    if ($hourStats->isEmpty()) {
                        $height_overview[] = [
                            'height' => null,
                            'mode' => 'unknown'
                        ];
                    } else {
                        $avgHeight = round($hourStats->avg('desk_height_mm') / 10);
                        $mode = $avgHeight > self::SIT_THRESHOLD_MM / 10 ? 'standing' : 'sitting';
                        $height_overview[] = [
                            'height' => $avgHeight,
                            'mode' => $mode
                        ];
                    }
                }
                break;

            case 'weekly':
                // Get the latest record date or use now
                $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
                $endDate = $latestRecord ? $latestRecord->recorded_at : now();
                
                // Create array of last 7 days ending with today
                $last7Days = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = $endDate->copy()->subDays($i);
                    $last7Days[] = [
                        'date' => $date,
                        'label' => $date->format('D'), // Mon, Tue, Wed, etc.
                        'full_date' => $date->format('Y-m-d')
                    ];
                }
                
                // Group stats by date
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d'));
                
                foreach ($last7Days as $dayInfo) {
                    $labels[] = $dayInfo['label'];
                    $dayStats = $grouped->get($dayInfo['full_date'], collect());
                    
                    $sit = $dayStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $dayStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;

                    // Use full comprehensive score for daily+ aggregations
                    $posture_scores[] = $dayStats->isNotEmpty()
                        ? $this->calculatePostureScore($dayStats, false)
                        : 50;
                    
                    $avg_sit_heights[] = $sit > 0 ? round($dayStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($dayStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 110;
                
                    if ($dayStats->isEmpty()) {
                        $height_overview[] = ['height' => null, 'mode' => 'unknown'];
                    } else {
                        $avgHeight = round($dayStats->avg('desk_height_mm') / 10);
                        $mode = $avgHeight > self::SIT_THRESHOLD_MM / 10 ? 'standing' : 'sitting';
                        $height_overview[] = ['height' => $avgHeight, 'mode' => $mode];
                    }
                }
                break;

            case 'monthly':
                // Get the latest record date or use now
                $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
                $endDate = $latestRecord ? $latestRecord->recorded_at : now();
                
                // Create array of last 4 weeks with date ranges
                $last4Weeks = [];
                for ($i = 3; $i >= 0; $i--) {
                    $weekEnd = $endDate->copy()->subWeeks($i)->endOfWeek();
                    $weekStart = $weekEnd->copy()->startOfWeek();
                    
                    // Format label as "1-7", "8-14", etc.
                    $startDay = $weekStart->day;
                    $endDay = $weekEnd->day;
                    
                    $last4Weeks[] = [
                        'label' => "{$startDay}-{$endDay}",
                        'start_date' => $weekStart->format('Y-m-d'),
                        'end_date' => $weekEnd->format('Y-m-d')
                    ];
                }
                
                // Group stats by date
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d'));
                
                foreach ($last4Weeks as $weekInfo) {
                    $labels[] = $weekInfo['label'];
                    
                    // Get all stats within this week's date range
                    $weekStats = $stats->filter(function($stat) use ($weekInfo) {
                        $statDate = $stat->recorded_at->format('Y-m-d');
                        return $statDate >= $weekInfo['start_date'] && $statDate <= $weekInfo['end_date'];
                    });
                    
                    $sit = $weekStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $weekStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;

                    $posture_scores[] = $weekStats->isNotEmpty()
                        ? $this->calculatePostureScore($weekStats, false)
                        : 50;
                    $avg_sit_heights[] = $sit > 0 ? round($weekStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($weekStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 110;
                
                    if ($weekStats->isEmpty()) {
                        $height_overview[] = ['height' => null, 'mode' => 'unknown'];
                    } else {
                        $avgHeight = round($weekStats->avg('desk_height_mm') / 10);
                        $mode = $avgHeight > self::SIT_THRESHOLD_MM / 10 ? 'standing' : 'sitting';
                        $height_overview[] = ['height' => $avgHeight, 'mode' => $mode];
                    }
                }
                break;

            case 'yearly':
                // Get the latest record date or use now
                $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
                $endDate = $latestRecord ? $latestRecord->recorded_at : now();
                
                // Create array of last 12 months ending with current month
                $last12Months = [];
                for ($i = 11; $i >= 0; $i--) {
                    $monthDate = $endDate->copy()->subMonths($i);
                    $last12Months[] = [
                        'label' => $monthDate->format('M'), // Jan, Feb, Mar, etc.
                        'year_month' => $monthDate->format('Y-m') // 2025-01, 2025-02, etc.
                    ];
                }
                
                // Group stats by year-month
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m'));
                
                foreach ($last12Months as $monthInfo) {
                    $labels[] = $monthInfo['label'];
                    $monthStats = $grouped->get($monthInfo['year_month'], collect());
                    
                    $sit = $monthStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $monthStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;
                    
                    $posture_scores[] = $monthStats->isNotEmpty()
                        ? $this->calculatePostureScore($monthStats, false)
                        : 50;
                    $avg_sit_heights[] = $sit > 0 ? round($monthStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 72;
                    $avg_stand_heights[] = $stand > 0 ? round($monthStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : 110;
                
                    if ($monthStats->isEmpty()) {
                        $height_overview[] = ['height' => null, 'mode' => 'unknown'];
                    } else {
                        $avgHeight = round($monthStats->avg('desk_height_mm') / 10);
                        $mode = $avgHeight > self::SIT_THRESHOLD_MM / 10 ? 'standing' : 'sitting';
                        $height_overview[] = ['height' => $avgHeight, 'mode' => $mode];
                    }
                }
                break;
        }

        return compact('labels', 'sitting_hours', 'standing_hours', 'posture_scores', 'avg_sit_heights', 'avg_stand_heights', 'height_overview');
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
            'height_overview' => [],
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
            'position_changes' => 0,
            'calories_per_day' => 0,
        ];
    }
}