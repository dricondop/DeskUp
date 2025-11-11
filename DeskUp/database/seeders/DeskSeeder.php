<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Desk;
use App\Models\User;
use App\Models\DeskActivity;

class DeskSeeder extends Seeder
{
    public function run(): void
    {
        $desks = [
            ['name' => 'Desk 101', 'desk_number' => 101, 'position_x' => 100, 'position_y' => 100],
            ['name' => 'Desk 102', 'desk_number' => 102, 'position_x' => 300, 'position_y' => 100],
            ['name' => 'Desk 103', 'desk_number' => 103, 'position_x' => 500, 'position_y' => 100],
            ['name' => 'Desk 201', 'desk_number' => 201, 'position_x' => 100, 'position_y' => 300],
            ['name' => 'Desk 202', 'desk_number' => 202, 'position_x' => 300, 'position_y' => 300],
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

        // Added later
        $userId = User::where('email', 'user@deskup.com')->value('id');
        $adminId = User::where('email', 'admin@deskup.com')->value('id');

        Desk::where('desk_number', 201)->update(['user_id' => $userId]);
        Desk::where('desk_number', 202)->update(['user_id' => $adminId]);

    }
}
