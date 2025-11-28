<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Desk;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
        [
            'event_type'   => 'meeting',
            'description'  => 'Weekly team 12 meeting',
            'scheduled_at' => '2025-12-01 09:00:00',
            'scheduled_to' => '2025-12-01 10:00:00',
            'status'       => 'approved',
            'created_by'   => 2,
        ],
        [
            'event_type'   => 'cleaning',
            'description'  => 'Monthly deep cleaning of the office area',
            'scheduled_at' => '2025-12-02 17:00:00',
            'scheduled_to' => '2025-12-02 19:00:00',
            'status'       => 'approved',
            'created_by'   => 1,
        ],
        [
            'event_type'   => 'maintenance',
            'description'  => 'Height calibration and cable check for adjustable desks',
            'scheduled_at' => '2025-12-03 13:00:00',
            'scheduled_to' => '2025-12-03 15:00:00',
            'status'       => 'approved',
            'created_by'   => 1,
        ],
        [
            'event_type'   => 'event',
            'description'  => 'DeskUp launch presentation',
            'scheduled_at' => '2025-12-05 14:00:00',
            'scheduled_to' => '2025-12-05 16:00:00',
            'status'       => 'pending',
            'created_by'   => 2,
        ],
        [
            'event_type'   => 'meeting',
            'description'  => 'Monthly team 2 meeting',
            'scheduled_at' => '2025-12-06 10:00:00',
            'scheduled_to' => '2025-12-06 12:00:00',
            'status'       => 'pending',
            'created_by'   => 2,
        ],
        [
            'event_type'   => 'meeting',
            'description'  => 'Monthly team 4 meeting',
            'scheduled_at' => '2025-12-08 09:00:00',
            'scheduled_to' => '2025-12-08 11:00:00',
            'status'       => 'pending',
            'created_by'   => 2,
        ],
    ];

    foreach ($events as $eventData) {
        $event = Event::create($eventData);

        // Attach 1-3 random desks to each event
        $deskIds = Desk::inRandomOrder()->limit(rand(1,3))->pluck('id');

        $event->desks()->attach($deskIds);
    }

    }
}
