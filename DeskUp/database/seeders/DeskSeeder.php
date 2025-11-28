<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Desk;

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
            Desk::create($deskData);
        }
    }
}
