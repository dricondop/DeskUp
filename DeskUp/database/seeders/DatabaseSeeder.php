<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DeskSeeder::class,              // Create desks first (without user_id)
            UserSeeder::class,              // Create users and assign desks
            DeskUserAssignmentSeeder::class, // NEW: Assign users to desks
            UserStatsHistorySeeder::class,   // Create history data
            UserProfilesSeeder::class,      // NEW: Create user profiles
        ]);
    }
}