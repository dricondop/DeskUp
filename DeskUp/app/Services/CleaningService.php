<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Desk;
use App\Helpers\APIMethods;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class CleaningService
{
    public function runCleaningSchedule(): void
    {
        $cleaningSchedule = Event::where('event_type', 'cleaning')
            ->where('status', Event::STATUS_APPROVED)
            ->latest('created_at')
            ->first();

        if (!$cleaningSchedule) {
            return;
        }

        $today = strtoupper(now()->format('D'));             // makes string uppercase: Mon -> MON

        if (!in_array($today, $cleaningSchedule->cleaning_days, true)) {       // return if cleaning is not scheduled today
            return; 
        }

        $now = now()->format('H:i');

        $start = Carbon::parse($cleaningSchedule->cleaning_time)->format('H:i');
        $end = Carbon::parse($cleaningSchedule->cleaning_time)
            ->addHours(2)
            ->format('H:i');

        if ($now === $start) {
            $this->moveAllDesksToMaxHeight();
        }

        if ($now === $end) {
            $this->moveAllDesksToDefaultHeight();
        }
    }

    private function moveAllDesksToMaxHeight(): void
    {
        $desks = Desk::where('api_desk_id','!=',null)->get();
        foreach ($desks as $desk) {
            try {
                APIMethods::raiseDesk(2000, $desk->api_desk_id);
            } catch (\Exception $e) {
                Log::error("Failed to raise desk {$desk->id} during cleaning: " . $e->getMessage());
            }
        }
    }

    private function moveAllDesksToDefaultHeight(): void 
    {
        $desks = Desk::all();
        foreach ($desks as $desk) {
            try {
                APIMethods::raiseDesk(75, $desk->api_desk_id);
            } catch (\Exception $e) {
                Log::error("Failed to lower desk {$desk->id} during cleaning: " . $e->getMessage());
            }
        }
    }

    public function markPastEventsAsComplete(): void 
    {
        Event::where('status', Event::STATUS_APPROVED)
            ->where('event_type', '!=', 'cleaning')
            ->whereNotNull('scheduled_to')
            ->where('scheduled_to', '<', now())
            ->update(['status' => Event::STATUS_COMPLETED]);
    }
}