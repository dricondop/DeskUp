<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Desk;

class DeskUserAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        // Get users by email
        $regularUser = User::where('email', 'user@deskup.com')->first();
        $adminUser = User::where('email', 'admin@deskup.com')->first();

        if ($regularUser) {
            Desk::where('desk_number', 102)->update(['user_id' => $regularUser->id]);
            $this->command->info("Assigned regular user to desk 102");
        }
        
        if ($adminUser) {
            Desk::where('desk_number', 101)->update(['user_id' => $adminUser->id]);
            $this->command->info("Assigned admin user to desk 101");
        }
    }
}
