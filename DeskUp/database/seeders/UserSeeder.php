<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Desk;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $desks = Desk::all();
        
        User::factory()->create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@deskup.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
            'assigned_desk_id' => $desks->get(0)?->id,
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Regular User',
            'email' => 'user@deskup.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'assigned_desk_id' => $desks->get(1)?->id,
        ]);
    }
}
