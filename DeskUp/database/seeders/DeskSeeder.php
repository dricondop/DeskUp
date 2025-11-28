<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Desk;
use App\Models\User;

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
                'desk_number' => 1,
                'api_desk_id' => null,
                'position_x' => 100.0,
                'position_y' => 100.0,
                'user_id' => $adminId,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Regular Desk',
                'desk_number' => 2,
                'api_desk_id' => null,
                'position_x' => 300.0,
                'position_y' => 100.0,
                'user_id' => $userId,
                'is_active' => true,
            ],
        ];

        foreach ($desks as $deskData) {
            Desk::create($deskData);
        }

        // Now update users with their assigned desks
        if ($adminId) {
            User::find($adminId)->update(['assigned_desk_id' => 1]);
        }
        if ($userId) {
            User::find($userId)->update(['assigned_desk_id' => 2]);
        }
    }
}
