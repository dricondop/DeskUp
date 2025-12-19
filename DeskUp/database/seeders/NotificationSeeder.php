<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all non-admin users
        $users = User::where('is_admin', false)->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No non-admin users found. Please create users first.');
            return;
        }

        foreach ($users as $user) {
            // Create test notifications for each user
            
            // Recent automatic notification (2 hours ago)
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Time to Stand Up!',
                'message' => 'You\'ve been sitting for 45 minutes. Take a 5-minute break to stretch and improve your posture.',
                'type' => 'automatic',
                'is_read' => false,
                'sent_at' => now()->subHours(2),
            ]);

            // Older automatic notification (5 hours ago, read)
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Time to Stand Up!',
                'message' => 'You\'ve been sitting for 60 minutes. Consider switching to standing position.',
                'type' => 'automatic',
                'is_read' => true,
                'read_at' => now()->subHours(4)->subMinutes(30),
                'sent_at' => now()->subHours(5),
            ]);

            // Manual notification from admin (1 day ago)
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Wellness Reminder',
                'message' => 'Remember to take regular breaks throughout the day. Your health is important!',
                'type' => 'manual',
                'is_read' => true,
                'read_at' => now()->subDay()->addHour(),
                'sent_at' => now()->subDay(),
            ]);

            // Recent manual notification (30 minutes ago)
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Meeting Reminder',
                'message' => 'Team standup meeting in 30 minutes. Please ensure your desk is at a comfortable height.',
                'type' => 'manual',
                'is_read' => false,
                'sent_at' => now()->subMinutes(30),
            ]);

            // Older automatic notification (3 days ago, read)
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Posture Check!',
                'message' => 'You\'ve been sitting for 50 minutes. Stand up and move around for better circulation.',
                'type' => 'automatic',
                'is_read' => true,
                'read_at' => now()->subDays(3)->addHour(),
                'sent_at' => now()->subDays(3),
            ]);
        }

        $totalNotifications = $users->count() * 5;
        $this->command->info("Created {$totalNotifications} notifications for {$users->count()} users.");
    }
}
