<?php

namespace Database\Seeders;

use App\Models\UserStatsHistory;
use App\Models\User;
use App\Models\Desk;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserStatsHistorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::find(2);
        $desk = $user?->assignedDesk;

        if (!$user || !$desk) {
            $this->command->warn('User ID 2 or their assigned desk not found. Skipping user_stats_history seeding.');
            return;
        }

        $startDate = Carbon::parse('2025-10-27 08:00:00');
        
        for ($day = 0; $day < 7; $day++) {
            $currentDate = $startDate->copy()->addDays($day);
            $activationCount = 40 + $day;
            $sitStandCount = 1 + $day;
            
            for ($hour = 8; $hour <= 17; $hour++) {
                $recordTime = $currentDate->copy()->setHour($hour)->setMinute(0);
                
                $isSitting = ($hour % 2 == 0);
                $height = $isSitting ? 720 : 1100;
                $speed = ($hour == 8 || $hour == 12 || $hour == 16) ? rand(20, 40) : 0;
                $status = $speed > 0 ? 'Moving' : 'Normal';
                
                $isAnomaly = rand(1, 50) == 1;
                
                UserStatsHistory::create([
                    'user_id' => $user->id,
                    'desk_id' => $desk->id,
                    'desk_height_mm' => $height,
                    'desk_speed_mms' => $speed,
                    'desk_status' => $status,
                    'is_position_lost' => $isAnomaly && rand(0, 1) == 1,
                    'is_overload_up' => false,
                    'is_overload_down' => $isAnomaly && rand(0, 1) == 1,
                    'is_anti_collision' => $isAnomaly && rand(0, 1) == 1,
                    'activations_count' => $activationCount + ($hour - 8),
                    'sit_stand_count' => $sitStandCount + floor(($hour - 8) / 2),
                    'recorded_at' => $recordTime,
                ]);
            }
        }

        $this->command->info('Created ' . (7 * 10) . ' user_stats_history records for User ID 2');
    }
}
