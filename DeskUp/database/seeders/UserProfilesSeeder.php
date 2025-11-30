<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserProfilesSeeder extends Seeder
{
    public function run()
    {
        $profiles = [
            [
                'user_id' => 1,
                'profile_picture' => 'https://example.com/images/1.jpg',
                'phone' => '+45 12340001',
                'date_of_birth' => '1992-01-01',
                'location' => 'Sønderborg',
                'ideal_height' => '70',
                'created_at' => Carbon::now(),
                'updated_at' => '2025-11-24 20:27:55'
            ],
            [
                'user_id' => 2, 
                'profile_picture' => 'https://example.com/images/2.jpg',
                'phone' => '+45 12340002',
                'date_of_birth' => '1991-01-01',
                'location' => 'Sønderborg',
                'ideal_height' => '80',
                'created_at' => Carbon::now(),
                'updated_at' => '2025-11-24 20:27:55'
            ],
        ];

        DB::table('user_profiles')->insert($profiles);
    }
}