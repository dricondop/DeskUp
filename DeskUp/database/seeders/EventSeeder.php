<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Desk;
use App\Models\User;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void  
    {  
        // Get users for event creation  
        $adminUser = User::where('is_admin', true)->first();  
        $regularUser = User::where('is_admin', false)->first();  
  
        if (!$adminUser || !$regularUser) {  
            $this->command->warn('Users not found. Please run UserSeeder first.');  
            return;  
        }  
  
        // Define events to create  
        $events = [  
            [  
                'event_type' => 'meeting',  
                'description' => 'Weekly team standup meeting',  
                'scheduled_at' => Carbon::now()->addDays(7)->setTime(9, 0),  
                'scheduled_to' => Carbon::now()->addDays(7)->setTime(10, 0),  
                'status' => Event::STATUS_APPROVED,  
                'created_by' => $adminUser->id,  
            ],  
            [  
                'event_type' => 'meeting',  
                'description' => 'Monthly planning session',  
                'scheduled_at' => Carbon::now()->addDays(9)->setTime(14, 0),  
                'scheduled_to' => Carbon::now()->addDays(9)->setTime(16, 0),  
                'status' => Event::STATUS_PENDING,  
                'created_by' => $regularUser->id,  
            ],  
            [  
                'event_type' => 'meeting',  
                'description' => 'Weekly scrum meeting',  
                'scheduled_at' => Carbon::now()->addDays(8)->setTime(14, 0),  
                'scheduled_to' => Carbon::now()->addDays(8)->setTime(16, 0),  
                'status' => Event::STATUS_PENDING,  
                'created_by' => $regularUser->id,  
            ],  
            [  
                'event_type' => 'meeting',  
                'description' => 'Bi-weekly planning session',  
                'scheduled_at' => Carbon::now()->addDays(10)->setTime(14, 0),  
                'scheduled_to' => Carbon::now()->addDays(10)->setTime(16, 0),  
                'status' => Event::STATUS_APPROVED,  
                'created_by' => $regularUser->id,  
            ],  
            [  
                'event_type' => 'event',  
                'description' => 'Company all-hands presentation',  
                'scheduled_at' => Carbon::now()->addDays(11)->setTime(10, 0),  
                'scheduled_to' => Carbon::now()->addDays(11)->setTime(13, 0),  
                'status' => Event::STATUS_APPROVED,  
                'created_by' => $adminUser->id,  
            ],  
            [  
                'event_type' => 'event',  
                'description' => 'Company product showcase',  
                'scheduled_at' => Carbon::now()->addDays(23)->setTime(9, 0),  
                'scheduled_to' => Carbon::now()->addDays(23)->setTime(12, 0),  
                'status' => Event::STATUS_APPROVED,  
                'created_by' => $adminUser->id,  
            ],  
            [  
                'event_type' => 'maintenance',  
                'description' => 'Desk height calibration and inspection',  
                'scheduled_at' => Carbon::now()->addDays(5)->setTime(17, 0),  
                'scheduled_to' => Carbon::now()->addDays(5)->setTime(19, 0),  
                'status' => Event::STATUS_APPROVED,  
                'created_by' => $adminUser->id,  
            ],  
            [  
                'event_type' => 'cleaning',  
                'description' => 'Weekly office deep cleaning',  
                'scheduled_at' => null, 
                'scheduled_to' => null, 
                'status' => Event::STATUS_APPROVED,  
                'created_by' => $adminUser->id,  
                'cleaning_time' => '18:00',  
                'cleaning_days' => ['MON', 'WED', 'FRI'],  
                'is_recurring' => true,  
            ],  
        ];  
  
        foreach ($events as $eventData) {  
            $event = Event::create($eventData);  

            // Attach random desks (1-3 desks per event)  
            $deskIds = Desk::inRandomOrder()->limit(rand(1,3))->pluck('id')->all();
            $event->desks()->syncWithoutDetaching($deskIds); 
  
            //Attach users based on event type  
            if (in_array($event->event_type, ['meeting', 'event'])) {  
                // Meetings and events include both users  
                $event->users()->attach([$adminUser->id, $regularUser->id]);  
            } else {  
                // Maintenance and cleaning only include admin  
                $event->users()->attach([$adminUser->id]);  
            }  
        }  
    }
}