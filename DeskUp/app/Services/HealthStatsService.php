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
    
    // Ergonomic heights (in mm) - These are DEFAULT values
    // TODO: Personalize based on user height when available
    private const DEFAULT_IDEAL_SIT_HEIGHT_MM = 720;
    private const DEFAULT_IDEAL_STAND_HEIGHT_MM = 1100;
    private const ERGONOMIC_TOLERANCE_MM = 40; // ±40mm tolerance band
    
    // Scoring weights - UPDATED for better balance
    private const WEIGHT_STANDING_RATIO = 0.35;
    private const WEIGHT_BREAKS = 0.30;
    private const WEIGHT_VARIATION = 0.15;
    private const WEIGHT_ERGONOMICS = 0.20;

    // MINIMUM DATA REQUIREMENTS
    private const MIN_RECORDS_FOR_HOURLY_SCORE = 3; // Need 3+ records per hour
    private const MIN_HOURS_FOR_DAILY_SCORE = 4;    // Need 4+ hours of data for daily score
    private const MIN_DAYS_FOR_WEEKLY_SCORE = 3;    // Need 3+ days for weekly score
    private const MIN_WEEKS_FOR_MONTHLY_SCORE = 2;  // Need 2+ weeks for monthly score
    private const MIN_MONTHS_FOR_YEARLY_SCORE = 6;  // Need 6+ months for yearly score

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

        // Preprocess simulator data first
        $processedStats = $this->preprocessSimulatorData($stats);

        // For very small datasets (simulator might have gaps)
        if ($processedStats->count() < 3) {
            return $this->calculateSimplifiedPostureScore($processedStats);
        }

        // Aggregate into time buckets to normalize dense/sparse data
        $bucketKey = $isHourlyData ? 'Y-m-d H' : 'Y-m-d';
        $bucketedData = $this->aggregateIntoBuckets($processedStats, $bucketKey);

        // Calculate scores based on bucketed data
        $standingScore = $this->calculateStandingRatioScoreFromBuckets($bucketedData);
        $breaksScore = $this->calculateBreaksScoreFromBuckets($bucketedData);
        $variationScore = $this->calculateVariationScore($processedStats);
        $ergonomicsScore = $this->calculateErgonomicsScore($processedStats);

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
     * IMPROVED: Bell curve scoring for standing ratio
     * Peak at 45%, gradual decay toward extremes
     */
    private function calculateStandingRatioScore(Collection $stats): float
    {
        $standingRecords = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
        $totalRecords = $stats->count();
        $standingPct = ($standingRecords / max(1, $totalRecords)) * 100;
        
        // IMPROVED: Bell curve with peak at 45% standing
        $optimal = 45.0;
        $deviation = abs($standingPct - $optimal);
        
        if ($deviation == 0) {
            return 100; // Perfect score at 45%
        }
        
        // Gradual decay using quadratic function
        // At ±15% deviation → ~85 points
        // At ±25% deviation → ~65 points
        // At ±35% deviation → ~40 points
        // Beyond ±45% → <20 points
        
        if ($deviation <= 15) {
            // Gentle slope near optimal
            return 100 - ($deviation * $deviation * 0.067); // 100 → 85
        } elseif ($deviation <= 25) {
            // Moderate penalty
            return 85 - (($deviation - 15) * 2); // 85 → 65
        } elseif ($deviation <= 35) {
            // Steeper penalty
            return 65 - (($deviation - 25) * 2.5); // 65 → 40
        } else {
            // Heavy penalty for extremes
            return max(10, 40 - (($deviation - 35) * 1.5));
        }
    }

    /**
     * Score based on sit-stand transitions
     * Optimal: 3-6 transitions per 8-hour workday
     */
    private function calculateBreaksScore(Collection $stats): float
    {
        $transitions = $this->calculateTransitions($stats);
        $uniqueHours = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d H'))->count();
        $hoursTracked = max(1, $uniqueHours);
        
        // Normalize to 8-hour workday
        $transitionsPerEightHours = ($transitions / $hoursTracked) * 8;
        
        // IMPROVED: Optimal range 6-12 (every 40-80 minutes)
        if ($transitionsPerEightHours >= 6 && $transitionsPerEightHours <= 12) {
            return 100; // Optimal
        } elseif ($transitionsPerEightHours >= 4 && $transitionsPerEightHours < 6) {
            return 70 + (($transitionsPerEightHours - 4) * 15); // 70-100
        } elseif ($transitionsPerEightHours > 12 && $transitionsPerEightHours <= 16) {
            return 100 - (($transitionsPerEightHours - 12) * 7.5); // 100-70
        } elseif ($transitionsPerEightHours >= 2 && $transitionsPerEightHours < 4) {
            return 40 + (($transitionsPerEightHours - 2) * 15); // 40-70
        } elseif ($transitionsPerEightHours > 16 && $transitionsPerEightHours <= 20) {
            return 70 - (($transitionsPerEightHours - 16) * 12.5); // 70-20
        } else {
            // Very few (<2) or excessive (>20) transitions
            return max(10, 20);
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
        
        // Calculate per-mode scores
        $sitScore = $this->calculateModeVariationScore($sittingHeights);
        $standScore = $this->calculateModeVariationScore($standingHeights);
        
        // Average both, with weight toward the mode with more data
        $sitWeight = $sittingHeights->count() / max(1, $stats->count());
        $standWeight = $standingHeights->count() / max(1, $stats->count());
        
        return ($sitScore * $sitWeight) + ($standScore * $standWeight);
    }

    /**
     * NEW: Helper to score variation within a single mode
     * Optimal: 20-40mm variation (micro-adjustments)
     */
    private function calculateModeVariationScore(Collection $heights): float
    {
        if ($heights->count() < 3) {
            return 50; // Neutral for insufficient data
        }
        
        $stdDev = $this->calculateStandardDeviation($heights);
        
        // IMPROVED: Reward 20-40mm (intelligent adjustments)
        if ($stdDev >= 20 && $stdDev <= 40) {
            return 100; // Perfect micro-adjustment range
        } elseif ($stdDev >= 10 && $stdDev < 20) {
            return 70 + (($stdDev - 10) / 10) * 30; // 70-100
        } elseif ($stdDev > 40 && $stdDev <= 60) {
            return 100 - (($stdDev - 40) / 20) * 25; // 100-75
        } elseif ($stdDev < 10) {
            return 50 + ($stdDev / 10) * 20; // Too static: 50-70
        } else {
            // Too much variation (>60mm)
            return max(40, 75 - (($stdDev - 60) / 10) * 5);
        }
    }

    /**
     * Score based on how close heights are to ergonomic ideals
     */
    private function calculateErgonomicsScore(Collection $stats): float
    {
        $sittingHeights = $stats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
        $standingHeights = $stats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);
        
        // TODO: Get personalized ideal heights from user profile when available
        // Example: $idealSitHeight = $this->getUserIdealSitHeight($userId) ?? self::DEFAULT_IDEAL_SIT_HEIGHT_MM;
        $idealSitHeight = self::DEFAULT_IDEAL_SIT_HEIGHT_MM;
        $idealStandHeight = self::DEFAULT_IDEAL_STAND_HEIGHT_MM;
        
        $sitScore = $this->calculateHeightScore(
            $sittingHeights, 
            $idealSitHeight
        );
        
        $standScore = $this->calculateHeightScore(
            $standingHeights, 
            $idealStandHeight
        );
        
        // Weighted average based on time spent in each mode
        $sitWeight = $sittingHeights->count() / max(1, $stats->count());
        $standWeight = $standingHeights->count() / max(1, $stats->count());
        
        return ($sitScore * $sitWeight) + ($standScore * $standWeight);
    }

    /**
     * NEW: Helper to score heights against ideal with tolerance bands
     */
    private function calculateHeightScore(Collection $heights, int $idealHeight): float
    {
        if ($heights->isEmpty()) {
            return 50; // Neutral for no data
        }
        
        $avgHeight = $heights->avg('desk_height_mm');
        $deviation = abs($avgHeight - $idealHeight);
        
        // IMPROVED: Tolerance band approach
        if ($deviation <= self::ERGONOMIC_TOLERANCE_MM) {
            // Within ±40mm tolerance → excellent
            return 100;
        } elseif ($deviation <= self::ERGONOMIC_TOLERANCE_MM * 2) {
            // ±40-80mm → acceptable, gradual penalty
            return 100 - ((($deviation - self::ERGONOMIC_TOLERANCE_MM) / self::ERGONOMIC_TOLERANCE_MM) * 15);
        } elseif ($deviation <= self::ERGONOMIC_TOLERANCE_MM * 3) {
            // ±80-120mm → suboptimal
            return 85 - ((($deviation - (self::ERGONOMIC_TOLERANCE_MM * 2)) / self::ERGONOMIC_TOLERANCE_MM) * 20);
        } else {
            // Beyond ±120mm → poor ergonomics
            return max(30, 65 - (($deviation - (self::ERGONOMIC_TOLERANCE_MM * 3)) / 50));
        }
    }

    /**
     * Simplified posture score for small datasets (hourly view)
     * Only considers standing ratio and ergonomic heights
     */
    private function calculateSimplifiedPostureScore(Collection $stats): int
    {
        $standingScore = $this->calculateStandingRatioScore($stats);
        $ergonomicsScore = $this->calculateErgonomicsScore($stats);
        
        // UPDATED weights: 60% standing, 40% ergonomics
        $finalScore = ($standingScore * 0.6) + ($ergonomicsScore * 0.4);
        
        return (int) round(max(0, min(100, $finalScore)));
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
     * Get aggregated stats with minimum data validation
     */
    public function getAggregatedStats(int $userId, string $range = 'today'): array
    {
        $dates = $this->getDateRange($range);
        $stats = $this->getUserHealthStats($userId, $dates['from'], $dates['to']);

        if ($stats->isEmpty()) {
            return $this->getEmptyStats();
        }

        // UPDATED: Preprocess simulator data
        $processedStats = $this->preprocessSimulatorData($stats);

        // NEW: Validate minimum data requirements
        $dataQuality = $this->validateDataQuality($processedStats, $range);
        
        if (!$dataQuality['sufficient']) {
            return $this->getInsufficientDataStats($dataQuality);
        }

        // Calculate sitting vs standing from processed data
        $sittingRecords = $processedStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
        $standingRecords = $processedStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
        $totalRecords = $processedStats->count();

        $sittingPct = $totalRecords > 0 ? round(($sittingRecords / $totalRecords) * 100) : 0;
        $standingPct = $totalRecords > 0 ? round(($standingRecords / $totalRecords) * 100) : 0;

        $postureScore = $this->calculatePostureScore($processedStats);

        $uniqueHours = $processedStats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d H'))->count();
        $totalHours = max(1, $uniqueHours);
        
        $sittingHours = round(($sittingRecords / max(1, $totalRecords)) * $totalHours, 1);
        $standingHours = round(($standingRecords / max(1, $totalRecords)) * $totalHours, 1);

        $avgHeight = round($processedStats->avg('desk_height_mm'));
        $avgSitHeight = $sittingRecords > 0 
            ? round($processedStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10)
            : 72;
        $avgStandHeight = $standingRecords > 0 
            ? round($processedStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10)
            : 110;

        $transitions = $this->calculateTransitions($processedStats);
        
        $caloriesPerHour = 9;
        $calories = round($standingHours * $caloriesPerHour);

        return [
            'total_activations' => $processedStats->sum('activations_count'),
            'total_sit_stand' => $processedStats->sum('sit_stand_count'),
            'avg_height_mm' => $avgHeight,
            'records_count' => $totalRecords,
            'error_count' => $processedStats->filter(function($stat) {
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
            'posture_score' => $postureScore,
            'data_quality' => $dataQuality, // NEW: Include data quality info
        ];
    }

    /**
     * NEW: Validate if we have enough data for reliable scoring
     */
    private function validateDataQuality(Collection $stats, string $range): array
    {
        if ($stats->isEmpty()) {
            return [
                'sufficient' => false,
                'reason' => 'No data available',
                'recommendation' => 'Start using your desk to gather health insights.'
            ];
        }

        $uniqueHours = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d H'))->count();
        $uniqueDays = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d'))->count();
        $uniqueWeeks = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-W'))->count();
        $uniqueMonths = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m'))->count();

        switch ($range) {
            case 'today':
                if ($uniqueHours < self::MIN_HOURS_FOR_DAILY_SCORE) {
                    return [
                        'sufficient' => false,
                        'reason' => "Only {$uniqueHours} hour(s) of data today",
                        'required' => self::MIN_HOURS_FOR_DAILY_SCORE,
                        'recommendation' => 'Use your desk for at least ' . self::MIN_HOURS_FOR_DAILY_SCORE . ' hours to see reliable health insights.'
                    ];
                }
                
                // Check if each hour has minimum records
                $hourlyRecords = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d H'));
                $insufficientHours = $hourlyRecords->filter(fn($h) => $h->count() < self::MIN_RECORDS_FOR_HOURLY_SCORE)->count();
                
                if ($insufficientHours > ($uniqueHours / 2)) {
                    return [
                        'sufficient' => false,
                        'reason' => 'Sparse data - too few readings per hour',
                        'recommendation' => 'Continue using your desk to gather more data points.'
                    ];
                }
                break;

            case 'weekly':
                if ($uniqueDays < self::MIN_DAYS_FOR_WEEKLY_SCORE) {
                    return [
                        'sufficient' => false,
                        'reason' => "Only {$uniqueDays} day(s) of data this week",
                        'required' => self::MIN_DAYS_FOR_WEEKLY_SCORE,
                        'recommendation' => 'Use your desk for at least ' . self::MIN_DAYS_FOR_WEEKLY_SCORE . ' days to see weekly insights.'
                    ];
                }
                break;

            case 'monthly':
                if ($uniqueWeeks < self::MIN_WEEKS_FOR_MONTHLY_SCORE) {
                    return [
                        'sufficient' => false,
                        'reason' => "Only {$uniqueWeeks} week(s) of data this month",
                        'required' => self::MIN_WEEKS_FOR_MONTHLY_SCORE,
                        'recommendation' => 'Use your desk for at least ' . self::MIN_WEEKS_FOR_MONTHLY_SCORE . ' weeks to see monthly insights.'
                    ];
                }
                break;

            case 'yearly':
                if ($uniqueMonths < self::MIN_MONTHS_FOR_YEARLY_SCORE) {
                    return [
                        'sufficient' => false,
                        'reason' => "Only {$uniqueMonths} month(s) of data this year",
                        'required' => self::MIN_MONTHS_FOR_YEARLY_SCORE,
                        'recommendation' => 'Use your desk for at least ' . self::MIN_MONTHS_FOR_YEARLY_SCORE . ' months to see yearly insights.'
                    ];
                }
                break;
        }

        return [
            'sufficient' => true,
            'hours' => $uniqueHours,
            'days' => $uniqueDays,
            'weeks' => $uniqueWeeks,
            'months' => $uniqueMonths
        ];
    }

    /**
     * NEW: Return stats with insufficient data message
     */
    private function getInsufficientDataStats(array $dataQuality): array
    {
        return [
            'total_activations' => 0,
            'total_sit_stand' => 0,
            'avg_height_mm' => 0,
            'records_count' => 0,
            'error_count' => 0,
            'sitting_pct' => 0,
            'standing_pct' => 0,
            'sitting_hours' => 0,
            'standing_hours' => 0,
            'active_hours' => 0,
            'avg_sit_height_cm' => 72,
            'avg_stand_height_cm' => 110,
            'position_changes' => 0,
            'calories_per_day' => 0,
            'posture_score' => null, // NEW: null instead of 50 when insufficient data
            'data_quality' => $dataQuality,
            'insufficient_data' => true, // NEW: Flag for frontend
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

    /**
     * Preprocess data to handle simulator irregularities
     * - Remove duplicate timestamps (keep latest)
     * - Fill gaps with interpolated values
     * - Smooth out unrealistic spikes
     */
    private function preprocessSimulatorData(Collection $stats): Collection
    {
        if ($stats->isEmpty()) {
            return $stats;
        }

        // Step 1: Remove duplicates, keep latest entry per timestamp
        $uniqueStats = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d H:i'))
            ->map(fn($group) => $group->sortByDesc('id')->first())
            ->values();

        // Step 2: Sort by timestamp
        $sortedStats = $uniqueStats->sortBy('recorded_at')->values();

        // Step 3: Detect and smooth unrealistic spikes (>30cm change in <5 min)
        $smoothedStats = collect();
        $previousHeight = null;

        foreach ($sortedStats as $index => $stat) {
            if ($previousHeight !== null) {
                $heightDiff = abs($stat->desk_height_mm - $previousHeight);
                $timeDiff = $sortedStats[$index - 1]->recorded_at->diffInMinutes($stat->recorded_at);

                // If huge spike in short time, use average instead
                if ($timeDiff < 5 && $heightDiff > 300) {
                    $stat->desk_height_mm = round(($stat->desk_height_mm + $previousHeight) / 2);
                }
            }

            $smoothedStats->push($stat);
            $previousHeight = $stat->desk_height_mm;
        }

        return $smoothedStats;
    }

    /**
     * Aggregate data into meaningful time buckets
     * Handles both sparse and dense data appropriately
     */
    private function aggregateIntoBuckets(Collection $stats, string $bucketKey): Collection
    {
        $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format($bucketKey));

        return $grouped->map(function ($bucketStats) {
            // For each bucket, calculate representative values
            $sittingStats = $bucketStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
            $standingStats = $bucketStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);

            return (object) [
                'total_count' => $bucketStats->count(),
                'sitting_count' => $sittingStats->count(),
                'standing_count' => $standingStats->count(),
                'avg_height' => $bucketStats->avg('desk_height_mm'),
                'avg_sitting_height' => $sittingStats->isNotEmpty() ? $sittingStats->avg('desk_height_mm') : null,
                'avg_standing_height' => $standingStats->isNotEmpty() ? $standingStats->avg('desk_height_mm') : null,
                'transitions' => $this->countTransitionsInBucket($bucketStats),
                'duration_minutes' => $bucketStats->count() * 5, // Assume ~5 min per record
            ];
        });
    }

    private function countTransitionsInBucket(Collection $bucketStats): int
    {
        $transitions = 0;
        $prevMode = null;

        foreach ($bucketStats->sortBy('recorded_at') as $stat) {
            $mode = $stat->desk_height_mm < self::SIT_THRESHOLD_MM ? 'sit' : 'stand';
            if ($prevMode && $prevMode !== $mode) {
                $transitions++;
            }
            $prevMode = $mode;
        }

        return $transitions;
    }

    /**
     * Score based on standing vs sitting ratio from bucketed data
     */
    private function calculateStandingRatioScoreFromBuckets(Collection $buckets): float
    {
        $totalSittingTime = $buckets->sum('sitting_count');
        $totalStandingTime = $buckets->sum('standing_count');
        $totalTime = $totalSittingTime + $totalStandingTime;

        if ($totalTime == 0) return 50;

        $standingPct = ($totalStandingTime / $totalTime) * 100;

        // Use the same improved bell curve logic
        $optimal = 45.0;
        $deviation = abs($standingPct - $optimal);
        
        if ($deviation == 0) {
            return 100;
        }
        
        if ($deviation <= 15) {
            return 100 - ($deviation * $deviation * 0.067);
        } elseif ($deviation <= 25) {
            return 85 - (($deviation - 15) * 2);
        } elseif ($deviation <= 35) {
            return 65 - (($deviation - 25) * 2.5);
        } else {
            return max(10, 40 - (($deviation - 35) * 1.5));
        }
    }

    /**
     * Score based on sit-stand transitions from bucketed data
     */
    private function calculateBreaksScoreFromBuckets(Collection $buckets): float
    {
        $totalTransitions = $buckets->sum('transitions');
        $totalBuckets = $buckets->count();

        // Normalize to 8-hour day equivalent
        $transitionsPerEightHours = $totalBuckets > 0 ? ($totalTransitions / $totalBuckets) * 8 : 0;

        // Use the improved transitions scoring
        if ($transitionsPerEightHours >= 6 && $transitionsPerEightHours <= 12) {
            return 100;
        } elseif ($transitionsPerEightHours >= 4 && $transitionsPerEightHours < 6) {
            return 70 + (($transitionsPerEightHours - 4) * 15);
        } elseif ($transitionsPerEightHours > 12 && $transitionsPerEightHours <= 16) {
            return 100 - (($transitionsPerEightHours - 12) * 7.5);
        } elseif ($transitionsPerEightHours >= 2 && $transitionsPerEightHours < 4) {
            return 40 + (($transitionsPerEightHours - 2) * 15);
        } elseif ($transitionsPerEightHours > 16 && $transitionsPerEightHours <= 20) {
            return 70 - (($transitionsPerEightHours - 16) * 12.5);
        } else {
            return max(10, 20);
        }
    }

    /**
     * Get aggregated stats for a time range.
     *
     * @param int $userId
     * @param string $range 'today', 'weekly', 'monthly', 'yearly'
     * @return array
     */
    public function getAggregatedStatsOld(int $userId, string $range = 'today'): array
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
    public function getChartDataOld(int $userId, string $range = 'today'): array
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
    public function getLiveStatusOld(int $userId): array
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

    private function groupByTimeBuckets(Collection $stats, string $range): array
    {
        // UPDATED: Preprocess first
        $processedStats = $this->preprocessSimulatorData($stats);

        $labels = [];
        $sitting_hours = [];
        $standing_hours = [];
        $posture_scores = [];
        $avg_sit_heights = [];
        $avg_stand_heights = [];
        $height_overview = [];

        switch ($range) {
            case 'today':
                // Determine actual hour range from data
                if ($processedStats->isNotEmpty()) {
                    $minHour = (int) $processedStats->min('recorded_at')->format('G');
                    $maxHour = (int) $processedStats->max('recorded_at')->format('G');
                } else {
                    $minHour = 8;
                    $maxHour = 17;
                }

                $grouped = $processedStats->groupBy(fn($s) => $s->recorded_at->format('H:00'));
                
                foreach (range($minHour, $maxHour) as $hour) {
                    $key = sprintf('%02d:00', $hour);
                    $labels[] = $key;
                    $hourStats = $grouped->get($key, collect());
                    
                    // UPDATED: Calculate weighted time, not just counts
                    $sit = $hourStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
                    $stand = $hourStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);
                    
                    // Estimate time based on records (assume each record ~ 5 minutes)
                    $sitMinutes = $sit->count() * 5;
                    $standMinutes = $stand->count() * 5;
                    
                    $sitting_hours[] = round($sitMinutes / 60, 2);
                    $standing_hours[] = round($standMinutes / 60, 2);

                    $posture_scores[] = $hourStats->isNotEmpty() 
                        ? $this->calculatePostureScore($hourStats, true)
                        : null;
                    
                    $avg_sit_heights[] = $sit->isNotEmpty() ? round($sit->avg('desk_height_mm') / 10) : null;
                    $avg_stand_heights[] = $stand->isNotEmpty() ? round($stand->avg('desk_height_mm') / 10) : null;
                
                    $height_overview[] = [
                        'sitting_height' => $sit->isNotEmpty() ? round($sit->avg('desk_height_mm') / 10) : null,
                        'standing_height' => $stand->isNotEmpty() ? round($stand->avg('desk_height_mm') / 10) : null
                    ];
                }
                break;

            case 'weekly':
                $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
                $endDate = $latestRecord ? $latestRecord->recorded_at : now();
                
                $last7Days = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = $endDate->copy()->subDays($i);
                    $last7Days[] = [
                        'date' => $date,
                        'label' => $date->format('D'),
                        'full_date' => $date->format('Y-m-d')
                    ];
                }
                
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d'));
                
                foreach ($last7Days as $dayInfo) {
                    $labels[] = $dayInfo['label'];
                    $dayStats = $grouped->get($dayInfo['full_date'], collect());
                    
                    $sit = $dayStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $dayStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;

                    // UPDATED: Return null instead of 50 when no data
                    $posture_scores[] = $dayStats->isNotEmpty()
                        ? $this->calculatePostureScore($dayStats, false)
                        : null;
                    
                    // UPDATED: Return null instead of default values when no data
                    $avg_sit_heights[] = $sit > 0 ? round($dayStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : null;
                    $avg_stand_heights[] = $stand > 0 ? round($dayStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : null;
                
                    $sittingHeights = $dayStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
                    $standingHeights = $dayStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);
                    
                    $height_overview[] = [
                        'sitting_height' => $sittingHeights->isNotEmpty() 
                            ? round($sittingHeights->avg('desk_height_mm') / 10) 
                            : null,
                        'standing_height' => $standingHeights->isNotEmpty() 
                            ? round($standingHeights->avg('desk_height_mm') / 10) 
                            : null
                    ];
                }
                break;

            case 'monthly':
                $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
                $endDate = $latestRecord ? $latestRecord->recorded_at : now();
                
                $last4Weeks = [];
                for ($i = 3; $i >= 0; $i--) {
                    $weekEnd = $endDate->copy()->subWeeks($i)->endOfWeek();
                    $weekStart = $weekEnd->copy()->startOfWeek();
                    
                    $startDay = $weekStart->day;
                    $endDay = $weekEnd->day;
                    
                    $last4Weeks[] = [
                        'label' => "{$startDay}-{$endDay}",
                        'start_date' => $weekStart->format('Y-m-d'),
                        'end_date' => $weekEnd->format('Y-m-d')
                    ];
                }
                
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m-d'));
                
                foreach ($last4Weeks as $weekInfo) {
                    $labels[] = $weekInfo['label'];
                    
                    $weekStats = $stats->filter(function($stat) use ($weekInfo) {
                        $statDate = $stat->recorded_at->format('Y-m-d');
                        return $statDate >= $weekInfo['start_date'] && $statDate <= $weekInfo['end_date'];
                    });
                    
                    $sit = $weekStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $weekStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;

                    // UPDATED: Return null instead of 50 when no data
                    $posture_scores[] = $weekStats->isNotEmpty()
                        ? $this->calculatePostureScore($weekStats, false)
                        : null;
                    
                    // UPDATED: Return null instead of default values when no data
                    $avg_sit_heights[] = $sit > 0 ? round($weekStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : null;
                    $avg_stand_heights[] = $stand > 0 ? round($weekStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : null;
                
                    $sittingHeights = $weekStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
                    $standingHeights = $weekStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);
                    
                    $height_overview[] = [
                        'sitting_height' => $sittingHeights->isNotEmpty() 
                            ? round($sittingHeights->avg('desk_height_mm') / 10) 
                            : null,
                        'standing_height' => $standingHeights->isNotEmpty() 
                            ? round($standingHeights->avg('desk_height_mm') / 10) 
                            : null
                    ];
                }
                break;

            case 'yearly':
                $latestRecord = UserStatsHistory::orderBy('recorded_at', 'desc')->first();
                $endDate = $latestRecord ? $latestRecord->recorded_at : now();
                
                $last12Months = [];
                for ($i = 11; $i >= 0; $i--) {
                    $monthDate = $endDate->copy()->subMonths($i);
                    $last12Months[] = [
                        'label' => $monthDate->format('M'),
                        'year_month' => $monthDate->format('Y-m')
                    ];
                }
                
                $grouped = $stats->groupBy(fn($s) => $s->recorded_at->format('Y-m'));
                
                foreach ($last12Months as $monthInfo) {
                    $labels[] = $monthInfo['label'];
                    $monthStats = $grouped->get($monthInfo['year_month'], collect());
                    
                    $sit = $monthStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->count();
                    $stand = $monthStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->count();
                    
                    $sitting_hours[] = $sit;
                    $standing_hours[] = $stand;
                    
                    // UPDATED: Return null instead of 50 when no data
                    $posture_scores[] = $monthStats->isNotEmpty()
                        ? $this->calculatePostureScore($monthStats, false)
                        : null;
                    
                    // UPDATED: Return null instead of default values when no data
                    $avg_sit_heights[] = $sit > 0 ? round($monthStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : null;
                    $avg_stand_heights[] = $stand > 0 ? round($monthStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM)->avg('desk_height_mm') / 10) : null;
                
                    $sittingHeights = $monthStats->filter(fn($s) => $s->desk_height_mm < self::SIT_THRESHOLD_MM);
                    $standingHeights = $monthStats->filter(fn($s) => $s->desk_height_mm >= self::SIT_THRESHOLD_MM);
                    
                    $height_overview[] = [
                        'sitting_height' => $sittingHeights->isNotEmpty() 
                            ? round($sittingHeights->avg('desk_height_mm') / 10) 
                            : null,
                        'standing_height' => $standingHeights->isNotEmpty() 
                            ? round($standingHeights->avg('desk_height_mm') / 10) 
                            : null
                    ];
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