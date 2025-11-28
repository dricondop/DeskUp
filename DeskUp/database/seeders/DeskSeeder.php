<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Desk;
use App\Models\DeskActivity;

class DeskSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('email', 'user@deskup.com')->value('id');
        $adminId = User::where('email', 'admin@deskup.com')->value('id');

        $desks = [
            [
                'id' => 1,
                'name' => 'Admin Desk',
                'desk_number' => 0001,
                'api_desk_id' => null,
                'position_x' => 100.0,
                'position_y' => 100.0,
                'status' => 'OK',
                'user_id' => $adminId,
                'height' => 110,
                'speed' => 36,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Regular Desk',
                'desk_number' => 0002,
                'api_desk_id' => null,
                'position_x' => 300.0,
                'position_y' => 100.0,
                'status' => 'OK',
                'user_id' => $userId,
                'height' => 110,
                'speed' => 36,
                'is_active' => true,
            ],
        ];

        foreach ($desks as $deskData) {
            $desk = Desk::create($deskData);
            
            DeskActivity::create([
                'desk_id' => $desk->id,
                'activity_type' => 'cleaning',
                'description' => 'Cleaning Schedule',
                'scheduled_at' => now()->addHours(2)
            ]);
            
            DeskActivity::create([
                'desk_id' => $desk->id,
                'activity_type' => 'meeting',
                'description' => 'Team Meeting',
                'scheduled_at' => now()->addHours(1)
            ]);
        }
    }
}
