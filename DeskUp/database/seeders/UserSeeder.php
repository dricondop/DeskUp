<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@deskup.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Regular User',
            'email' => 'user@deskup.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);
    }
}
