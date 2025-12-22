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

        // Get all users and available desk IDs
        $users = User::all();
        $deskIds = Desk::pluck('id')->toArray(); // Get only existing desk IDs
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping user_stats_history seeding.');
            return;
        }

        if (empty($deskIds)) {
            $this->command->warn('No desks found in database. Skipping user_stats_history seeding.');
            return;
        }

        $this->command->info('Available desk IDs: ' . implode(', ', $deskIds));

        // Generate data for each user
        foreach ($users as $user) {
            $this->command->info("Generating data for user: {$user->name} (ID: {$user->id})");
            
            // Assign a random desk ID that actually exists
            $deskId = $deskIds[array_rand($deskIds)];
            
            $this->generateUserData($user, $deskId);
        }
    }

    /**
     * Generate data for a specific user
     */
    private function generateUserData($user, $deskId)
    {

        $currentDate = Carbon::now();
        $recordsCreated = 0;
        $baseActivationCount = 10;
        $transitionCount = 0;
        $previousMode = null;

        // ===== PART 1: Yearly data (10 months, excluding July & August) =====
        $yearStart = $currentDate->copy()->startOfYear();
        
        for ($month = 1; $month <= 12; $month++) {
            // Skip July (7) and August (8)
            if ($month === 7 || $month === 8) {
                continue;
            }
            
            $monthDate = $yearStart->copy()->month($month);
            
            // Skip future months
            if ($monthDate->isAfter($currentDate)) {
                continue;
            }
            
            // Create 5-7 records per month for historical data
            $recordsPerMonth = rand(5, 7);
            $daysInMonth = $monthDate->daysInMonth;
            
            for ($i = 0; $i < $recordsPerMonth; $i++) {
                $dayOfMonth = rand(1, min($daysInMonth, 28)); // Avoid edge dates
                $recordDate = $monthDate->copy()->day($dayOfMonth);
                
                // Skip weekends
                if ($recordDate->isWeekend()) {
                    continue;
                }
                
                // Skip dates in the last 4 weeks (covered by weekly data)
                if ($recordDate->isAfter($currentDate->copy()->subWeeks(4))) {
                    continue;
                }
                
                // Skip today
                if ($recordDate->isToday()) {
                    continue;
                }
                
                $hour = rand(8, 17);
                $recordTime = $recordDate->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(0);
                
                $this->createRecord($user, $deskId, $recordTime, $baseActivationCount, $recordsCreated, $transitionCount, $previousMode);
                $recordsCreated++;
            }
        }

        $yearlyRecords = $recordsCreated;

        // ===== PART 2: Past 4 weeks (at least 3 weekdays per week) =====
        for ($weekOffset = 0; $weekOffset < 4; $weekOffset++) {
            $weekStart = $currentDate->copy()->subWeeks($weekOffset)->startOfWeek(); // Monday
            
            // Collect all weekdays for this week
            $weekdays = [];
            for ($dayOffset = 0; $dayOffset < 5; $dayOffset++) { // Monday to Friday
                $date = $weekStart->copy()->addDays($dayOffset);
                
                // Skip today (covered by hourly data)
                if ($date->isToday()) {
                    continue;
                }
                
                // Skip future dates
                if ($date->isAfter($currentDate)) {
                    continue;
                }
                
                $weekdays[] = $date;
            }
            
            // If we have no valid weekdays, skip this week
            if (empty($weekdays)) {
                continue;
            }
            
            // Ensure at least 3 weekdays have data (or all available if less than 3)
            $daysToUse = min(count($weekdays), max(3, count($weekdays)));
            
            // Shuffle and select days to ensure variety
            shuffle($weekdays);
            $selectedDays = array_slice($weekdays, 0, $daysToUse);
            
            // Create 2-3 records per selected day
            foreach ($selectedDays as $recordDate) {
                $recordsPerDay = rand(2, 3);
                
                for ($i = 0; $i < $recordsPerDay; $i++) {
                    $hour = rand(8, 17);
                    $recordTime = $recordDate->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(0);
                    
                    $this->createRecord($user, $deskId, $recordTime, $baseActivationCount, $recordsCreated, $transitionCount, $previousMode);
                    $recordsCreated++;
                }
            }
        }

        $weeklyRecords = $recordsCreated - $yearlyRecords;

        // ===== PART 3: Today's hourly data (8 AM - 3 PM, 3 entries per hour) =====
        $todayStart = 8;
        $todayEnd = 15;
        
        for ($hour = $todayStart; $hour <= $todayEnd; $hour++) {
            // Only create entries for hours that have passed
            $hourTime = $currentDate->copy()->setHour($hour)->setMinute(0)->setSecond(0);
            if ($hourTime->isFuture()) {
                continue;
            }
            
            // Create 3 records per hour, distributed throughout the hour
            for ($entryNum = 0; $entryNum < 3; $entryNum++) {
                $minute = rand(0 + ($entryNum * 20), 19 + ($entryNum * 20)); // Distribute: 0-19, 20-39, 40-59
                $recordTime = $currentDate->copy()->setHour($hour)->setMinute($minute)->setSecond(rand(0, 59));
                
                // Only create if not in the future
                if ($recordTime->isFuture()) {
                    continue;
                }
                
                $this->createRecord($user, $deskId, $recordTime, $baseActivationCount, $recordsCreated, $transitionCount, $previousMode);
                $recordsCreated++;
            }
        }

        $todayRecords = $recordsCreated - $weeklyRecords - $yearlyRecords;

        $this->command->info("Created total of {$recordsCreated} user_stats_history records for User: {$user->name}");
        $this->command->info("  - Total position transitions: {$transitionCount}");
        $this->command->info("  - {$todayRecords} records for today (8 AM - 3 PM, 3/hour)");
        $this->command->info("  - {$weeklyRecords} records for the past 4 weeks (at least 3 weekdays each)");
        $this->command->info("  - {$yearlyRecords} records for the year (excluding July & August)");
    }

    /**
     * Helper method to create a single record with realistic data
     */
    private function createRecord($user, $deskId, $recordTime, $baseActivationCount, &$recordsCreated, &$transitionCount, &$previousMode)
    {
        // Randomized sitting vs standing (60% sitting, 40% standing)
        // Desk height constraint: 680-1320mm
        $isSitting = rand(1, 100) <= 60;
        $height = $isSitting ? rand(680, 900) : rand(1000, 1320); // Direct range within constraint bounds
        
        // Determine current mode and check for transition
        $currentMode = $height < 1000 ? 'sit' : 'stand';
        if ($previousMode !== null && $previousMode !== $currentMode) {
            $transitionCount++;
        }
        $previousMode = $currentMode;
        
        // Occasional movement (10% chance)
        $isMoving = rand(1, 10) == 1;
        $speed = $isMoving ? (rand(0, 1) ? rand(28, 36) : rand(-36, -28)) : 0;
        
        // Rare anomaly (3% chance)
        $isAnomaly = rand(1, 33) == 1;
        $status = $isAnomaly ? 'Collision' : 'Normal';
        
        // Incrementally increase counts
        $activationCount = $baseActivationCount + $recordsCreated;
        
        UserStatsHistory::create([
            'user_id' => $user->id,
            'desk_id' => $deskId, // Use the actual existing desk ID
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
    }
}