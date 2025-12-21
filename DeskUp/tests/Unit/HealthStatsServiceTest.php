<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\HealthStatsService;
use App\Helpers\APIMethods;
use App\Models\Desk;
use App\Models\User;
use App\Models\UserStatsHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * These tests ensure that the HealthStatsService works correctly:
 * - Returns user data within a time range
 * - Calculate sit/stand percentage
 * - Calculates breaks
 * - Calculates burned calories
 * - Format UserStatsHistory records to chart data
 * - Return current/latest desk data
 */
class HealthStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private HealthStatsService $service;
    private User $user;
    private Desk $desk;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 12, 20, 12, 0, 0)); // freezez time, ensures it works if time is just past midnight

        $this->service = new HealthStatsService();
        $this->desk = Desk::factory()->create();
        $this->user = User::factory()->create();

        // Set required environment variables for API
        config(['app.env' => 'testing']);
    }

    /**
     * Test: getUserHealthStats returns user data within specific time frame in ascending order
     */
    public function test_get_user_health_stats_with_date_filtering(): void
    {   
        // create record data
        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 800,
            'recorded_at' => now()->subDays(2)
        ]);

        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1200,
            'recorded_at' => now()->subDays(1)
        ]);

        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1000,
            'recorded_at' => now()->subDays(5)
        ]);

        $fromDate = now()->subDay(3)->toDateString();
        $toDate = now()->toDateString();

        $stats = $this->service->getUserHealthStats(
            $this->user->id,
            $fromDate,
            $toDate
        );

        $this->assertCount(2, $stats);
        $this->assertEquals(800, $stats->last()->desk_height_mm);
    }

    /**
     * Test: getAggregatedStats correctly calculates sit/stand percentages
     */
    public function test_get_aggregated_stats_calculates_percentages_correctly(): void
    {
        // create record data
        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 600,
            'recorded_at' => now(),
        ]);

        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1200,
            'recorded_at' => now()->subHour(1)
        ]);

        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1000,
            'recorded_at' => now()->subHour(2)
        ]);

        $stats = $this->service->getAggregatedStats($this->user->id, 'today');

        $this->assertEquals(round(1/3*100), $stats['sitting_pct']);
        $this->assertEquals(round(2/3*100), $stats['standing_pct']);
        $this->assertEquals(3, $stats['records_count']);
    }

    /**
     * Test: getAggregatedStats calculates breaks (sit/stand position changes)
     */
    public function test_get_aggregated_stats_calculates_breaks(): void
    {
        // create record data
        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 800,    // sit
            'recorded_at' => now()->subHours(3),
        ]);

        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1200,   // Stand (1st break)
            'recorded_at' => now()->subHours(2)
        ]);

        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 900,       // sit (2nd break)
            'recorded_at' => now()->subHours(1),
        ]);

        $stats = $this->service->getAggregatedStats($this->user->id, 'today');

        $this->assertEquals(2, $stats['breaks_per_day']);
    }

    /**
     * Test: getAggregatedStats calculates burned calories (very rough: 0.15 kcal/min standing vs sitting)
     */
    public function test_get_aggregated_stats_calculates_calories(): void  
    {  
        // create record data  
        UserStatsHistory::create([
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1000,       // standing
            'recorded_at' => now()->subHours(1),
        ]);
  
        $stats = $this->service->getAggregatedStats($this->user->id, 'today');  
  
        // should calculate 9 calories per standing hour  
        $this->assertEquals(9, $stats['calories_per_day']);  
    }  
    
    /**
     * Test: getChartData formats UserStatsHistory records for charts 
     */
    public function test_get_chart_data_today_range(): void  
    {  
        // create hourly record data  
        foreach (range(8, 17) as $hour) {  
            UserStatsHistory::create([  
                'user_id' => $this->user->id,  
                'desk_id' => $this->desk->id,
                'desk_height_mm' => $hour % 2 === 0 ? 800 : 1200,  // change between desk height 800 and 1200 every hour
                'recorded_at' => now()->setHour($hour)->setMinute(0)->setSecond(0)  
            ]);  
        }  
  
        $chartData = $this->service->getChartData($this->user->id, 'today');  
  
        $this->assertCount(10, $chartData['labels']); // 8 AM to 5 PM  
        $this->assertEquals('08:00', $chartData['labels'][0]);  
        $this->assertEquals('17:00', $chartData['labels'][9]);  
        $this->assertCount(10, $chartData['sitting_hours']);  
        $this->assertCount(10, $chartData['standing_hours']);  
    }  

    /**
     * Test: getChartData handles no data records smoothly 
     */
    public function test_get_chart_data_without_data(): void  
    {  
        // creates no record data  

        $chartData = $this->service->getChartData($this->user->id, 'today');  
  
        $this->assertCount(0, $chartData['labels']); // 8 AM to 5 PM  
        $this->assertCount(0, $chartData['sitting_hours']);  
        $this->assertCount(0, $chartData['standing_hours']);  
        $this->assertCount(0, $chartData['posture_scores']);  
        $this->assertCount(0, $chartData['avg_sit_heights']);  
        $this->assertCount(0, $chartData['avg_stand_heights']);  
        $this->assertCount(0, $chartData['height_overview']);  
    }  

  
    /**
     * Test: getLiveStatus returns information about the latest UserStatsHistory record
     */
    public function test_get_live_status_returns_current_state(): void  
    {  
        // create latest record data
        UserStatsHistory::create([  
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 800,  
            'recorded_at' => now()->subHours(1)  
        ]);  

        UserStatsHistory::create([  
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 1200,  
            'recorded_at' => now()->subMinutes(30)  
        ]);  
  
        $status = $this->service->getLiveStatus($this->user->id);  
  
        $this->assertEquals('Standing', $status['mode']);  
        $this->assertEquals(120, $status['height_cm']);     // converted to cm  
        $this->assertEquals('30m ago', $status['last_adjusted']);  
    }  
    
    /**
     * Test: getLiveStatus does not fail if there is no UserStatsHistory record
     */
    public function test_get_live_status_no_data(): void  
    {  
        $status = $this->service->getLiveStatus($this->user->id);  
  
        $this->assertEquals('Unknown', $status['mode']);  
        $this->assertEquals(0, $status['height_cm']);  
        $this->assertEquals('Never', $status['last_adjusted']);  
    }  

}
