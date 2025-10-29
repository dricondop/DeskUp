<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
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
