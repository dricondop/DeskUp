<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Desk;

class DeskUserAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        // This seeder is no longer needed as we use users.assigned_desk_id instead of desks.user_id
        // Desk assignments are now managed through the User model's assigned_desk_id column
        $this->command->info("Skipping DeskUserAssignmentSeeder - using users.assigned_desk_id instead");
    }
}
