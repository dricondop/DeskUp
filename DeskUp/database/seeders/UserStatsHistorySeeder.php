<?php

namespace Database\Seeders;

use App\Models\UserStatsHistory;
use App\Models\User;
use App\Models\Desk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserStatsHistorySeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('user_stats_history')->truncate();
        $this->command->info('Cleared existing user_stats_history records');

        $user = User::find(2);
        
        if (!$user) {
            $this->command->warn('User ID 2 not found. Skipping user_stats_history seeding.');
            return;
        }

        // Try multiple ways to get the desk
        $desk = null;
        
        // Method 1: Try assignedDesk relationship if it exists
        try {
            $desk = $user->assignedDesk;
        } catch (\Exception $e) {
            $this->command->warn('assignedDesk relationship not found, trying alternative methods...');
        }
        
        // Method 3: Use any available desk as fallback
        if (!$desk) {
            $desk = Desk::first();
            if ($desk) {
                $this->command->warn('No desk assigned to User ID 2, using first available desk: ' . $desk->desk_number);
            }
        }

        if (!$desk) {
            $this->command->warn('No desks found in database. Skipping user_stats_history seeding.');
            return;
        }

        $currentDate = Carbon::now();
        $recordsCreated = 0;
        $baseActivationCount = 10;
        $transitionCount = 0; // Track actual transitions
        $previousMode = null; // Track previous sitting/standing mode

        // ===== PART 1: Historical data - 11 months (7 records per month) =====
        $startDate = $currentDate->copy()->subMonths(12);
        
        for ($month = 0; $month < 11; $month++) {
            $monthStart = $startDate->copy()->addMonths($month);
            $daysInMonth = $monthStart->daysInMonth;
            
            // Distribute 7 records evenly across the month
            $dayStep = floor($daysInMonth / 7);
            
            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $dayOfMonth = min(($dayIndex * $dayStep) + 1, $daysInMonth);
                $recordDate = $monthStart->copy()->day($dayOfMonth);
                
                // Skip future dates, past 4 weeks, and today
                if ($recordDate->isAfter($currentDate) || 
                    $recordDate->isAfter($currentDate->copy()->subWeeks(4)) ||
                    $recordDate->isToday()) {
                    continue;
                }
                
                // Random hour between 8 AM - 5 PM
                $hour = rand(8, 17);
                $recordTime = $recordDate->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(0);
                
                // Randomized sitting vs standing with some variation
                $sitProbability = rand(55, 70);
                $isSitting = rand(1, 100) <= $sitProbability;
                $baseHeight = $isSitting ? rand(600, 750) : rand(1200, 1400);
                $height = $baseHeight + rand(-100, 100);
                
                // Determine current mode and check for transition
                $currentMode = $height < 1000 ? 'sit' : 'stand';
                if ($previousMode !== null && $previousMode !== $currentMode) {
                    $transitionCount++;
                }
                $previousMode = $currentMode;
                
                // Occasional movement
                $isMoving = rand(1, 12) == 1;
                $speed = $isMoving ? (rand(0, 1) ? rand(28, 36) : rand(-36, -28)) : 0;
                
                // Occasional anomaly (1 in 50)
                $isAnomaly = rand(1, 50) == 1;
                $status = $isAnomaly ? 'Collision' : 'Normal';
                
                // Incrementally increase counts over time for realism
                $activationCount = $baseActivationCount + $recordsCreated;
                
                UserStatsHistory::create([
                    'user_id' => $user->id,
                    'desk_id' => $desk->desk_number,
                    'desk_height_mm' => $height,
                    'desk_speed_mms' => $speed,
                    'desk_status' => $status,
                    'is_position_lost' => $isAnomaly && rand(0, 1) == 1,
                    'is_overload_up' => false,
                    'is_overload_down' => $isAnomaly && rand(0, 1) == 1,
                    'is_anti_collision' => $isAnomaly && rand(0, 1) == 1,
                    'activations_count' => $activationCount,
                    'sit_stand_count' => $transitionCount,
                    'recorded_at' => $recordTime,
                ]);
                
                $recordsCreated++;
            }
        }

        $historicalRecords = $recordsCreated;

        // ===== PART 2: Create 7 entries for each of the past 4 weeks (excluding today) =====
        for ($weeksAgo = 3; $weeksAgo >= 0; $weeksAgo--) {
            $weekStart = $currentDate->copy()->subWeeks($weeksAgo)->startOfWeek();
            
            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $recordDate = $weekStart->copy()->addDays($dayIndex);
                
                // Skip future dates and today
                if ($recordDate->isAfter($currentDate) || $recordDate->isToday()) {
                    continue;
                }
                
                // Random hour between 8 AM - 5 PM
                $hour = rand(8, 17);
                $recordTime = $recordDate->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(0);
                
                // Randomized sitting vs standing (60% sitting, 40% standing)
                $isSitting = rand(1, 100) <= 60;
                $baseHeight = $isSitting ? rand(600, 750) : rand(1200, 1400);
                $height = $baseHeight + rand(-50, 50);
                
                // Determine current mode and check for transition
                $currentMode = $height < 1000 ? 'sit' : 'stand';
                if ($previousMode !== null && $previousMode !== $currentMode) {
                    $transitionCount++;
                }
                $previousMode = $currentMode;
                
                // Occasional movement
                $isMoving = rand(1, 10) == 1;
                $speed = $isMoving ? (rand(0, 1) ? rand(28, 36) : rand(-36, -28)) : 0;
                
                // Rare anomaly (1 in 30)
                $isAnomaly = rand(1, 30) == 1;
                $status = $isAnomaly ? 'Collision' : 'Normal';
                
                // Incrementally increase counts
                $activationCount = $baseActivationCount + $recordsCreated;
                
                UserStatsHistory::create([
                    'user_id' => $user->id,
                    'desk_id' => $desk->desk_number,
                    'desk_height_mm' => $height,
                    'desk_speed_mms' => $speed,
                    'desk_status' => $status,
                    'is_position_lost' => $isAnomaly && rand(0, 1) == 1,
                    'is_overload_up' => false,
                    'is_overload_down' => $isAnomaly && rand(0, 1) == 1,
                    'is_anti_collision' => $isAnomaly && rand(0, 1) == 1,
                    'activations_count' => $activationCount,
                    'sit_stand_count' => $transitionCount,
                    'recorded_at' => $recordTime,
                ]);
                
                $recordsCreated++;
            }
        }

        $weeklyRecords = $recordsCreated - $historicalRecords;

        // ===== PART 3: Create hourly entries for today (8 AM - 5 PM) =====
        for ($hour = 8; $hour <= 17; $hour++) {
            $recordTime = $currentDate->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(0);
            
            // Only create entries for hours that have already passed or current hour
            if ($recordTime->isFuture()) {
                continue;
            }
            
            // Randomized sitting vs standing (55% sitting, 45% standing)
            $isSitting = rand(1, 100) <= 55;
            $baseHeight = $isSitting ? rand(600, 750) : rand(1200, 1400);
            $height = $baseHeight + rand(-30, 30);
            
            // Determine current mode and check for transition
            $currentMode = $height < 1000 ? 'sit' : 'stand';
            if ($previousMode !== null && $previousMode !== $currentMode) {
                $transitionCount++;
            }
            $previousMode = $currentMode;
            
            // Occasional movement
            $isMoving = rand(1, 8) == 1;
            $speed = $isMoving ? (rand(0, 1) ? rand(28, 36) : rand(-36, -28)) : 0;
            
            // Very rare anomaly (1 in 20 for today's data)
            $isAnomaly = rand(1, 20) == 1;
            $status = $isAnomaly ? 'Collision' : 'Normal';
            
            // Incrementally increase counts
            $activationCount = $baseActivationCount + $recordsCreated;
            
            UserStatsHistory::create([
                'user_id' => $user->id,
                'desk_id' => $desk->desk_number,
                'desk_height_mm' => $height,
                'desk_speed_mms' => $speed,
                'desk_status' => $status,
                'is_position_lost' => $isAnomaly && rand(0, 1) == 1,
                'is_overload_up' => false,
                'is_overload_down' => $isAnomaly && rand(0, 1) == 1,
                'is_anti_collision' => $isAnomaly && rand(0, 1) == 1,
                'activations_count' => $activationCount,
                'sit_stand_count' => $transitionCount,
                'recorded_at' => $recordTime,
            ]);
            
            $recordsCreated++;
        }

        $todayRecords = $recordsCreated - $weeklyRecords - $historicalRecords;

        $this->command->info("Created total of {$recordsCreated} user_stats_history records for User ID 2");
        $this->command->info("  - Total position transitions: {$transitionCount}");
        $this->command->info("  - {$todayRecords} hourly records for today (8 AM - 5 PM)");
        $this->command->info("  - {$weeklyRecords} records for the past 4 weeks");
        $this->command->info("  - {$historicalRecords} historical records spanning 11 months");
    }
}