<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Desk;
use App\Models\User;
use App\Models\UserStatsHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;


/**
 * These tests ensure that the HTTP endpoints for health analytics work correctly:
 * - GET /api/health-stats - Get aggregated health stats
 * - GET /api/health-live-status - Return current/latest desk data
 * - GET /api/health-chart-data - Return chart formatted data
 */
class HealthStatsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Desk $desk;

    protected function setUp(): void 
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 12, 20, 12, 0, 0)); // freezez time, ensures it works if time is just past midnight

        $this->user = User::factory()->create();
        $this->desk = Desk::factory()->create();
    }
    
    /**
     * Test: '/api/health-stats?range=today' returns correct stats
     */
    public function test_health_stats_endpoint_returns_aggregated_data(): void
    {
        // create latest record data
        UserStatsHistory::create([  
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 800,  
            'recorded_at' => now() 
        ]);  

        $response = $this->actingAs($this->user)
            ->getJson('api/health-stats?range=today');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_activations',
                'total_sit_stand',
                'avg_height_mm',
                'records_count',
                'error_count',
                'sitting_pct',
                'standing_pct',
                'sitting_hours',
                'standing_hours',
                'active_hours',
                'avg_sit_height_cm',
                'avg_stand_height_cm',
                'breaks_per_day',
                'calories_per_day',
            ]);
        
    }

    /**
     * Test: /api/health-stats?range={ranges}' time ranges work properly
     */
    public function test_health_stats_endpoint_with_different_ranges(): void 
    {
        $now = now();

        // create records at different dates
        UserStatsHistory::create([          // daily
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 800,  
            'recorded_at' => now() 
        ]); 

        UserStatsHistory::create([          // weekly
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 800,  
            'recorded_at' => now()->subDays(2),
        ]); 

        UserStatsHistory::create([  
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 800,  
            'recorded_at' => now()->subDays(20),    // monthly
        ]); 

        UserStatsHistory::create([  
            'user_id' => $this->user->id,
            'desk_id' => $this->desk->id,  
            'desk_height_mm' => 800,  
            'recorded_at' => now()->subDays(200),   // yearly
        ]); 

        // test each range returns correct count
        $todayResponse = $this->actingAs($this->user)
            ->getJson('api/health-stats?range=today');
        $todayResponse->assertStatus(200);
        $this->assertEquals(1, $todayResponse->json('records_count'));  //  todays record
        
        $weeklyResponse = $this->actingAs($this->user)
            ->getJson('api/health-stats?range=weekly');
        $weeklyResponse->assertStatus(200);
        $this->assertEquals(2, $weeklyResponse->json('records_count'));  // weekly records

        $monthlyResponse = $this->actingAs($this->user)
            ->getJson('api/health-stats?range=monthly');
        $monthlyResponse->assertStatus(200);
        $this->assertEquals(3, $monthlyResponse->json('records_count'));  // monthly records

        $yearlyResponse = $this->actingAs($this->user)
            ->getJson('api/health-stats?range=yearly');
        $yearlyResponse->assertStatus(200);
        $this->assertEquals(4, $yearlyResponse->json('records_count'));  // All records
    }

    /**
     * Test: '/api/health-stats' unauthenticated returns a 401 status
     */
    public function test_health_stats_endpoint_unauthenticated(): void
    {
        $response = $this->getJson('api/health-stats');
        
        $response->assertStatus(401);
    }

    /**
     * Test: '/api/health-chart-data' returns data for chart format
     */
    public function test_health_chart_data_endpoint_returns_chart_formatted_data(): void  
    {  
        // Create hourly data for today  
        foreach (range(8, 12) as $hour) {  
            UserStatsHistory::create([  
                'user_id' => $this->user->id,  
                'desk_id' => $this->desk->id, 
                'desk_height_mm' => 800,  
                'recorded_at' => now()->setHour($hour)->setMinute(0)  
            ]);  
        }  
  
        $response = $this->actingAs($this->user)  
            ->getJson('/api/health-chart-data?range=today');  
  
        $response->assertStatus(200)  
            ->assertJsonStructure([  
                'labels',
                'sitting_hours',
                'standing_hours',
                'posture_scores',
                'avg_sit_heights',
                'avg_stand_heights',
                'height_overview',
            ]);  
  
        $data = $response->json();  
        $this->assertNotEmpty($data['labels']);  
        $this->assertNotEmpty($data['sitting_hours']);  
        $this->assertNotEmpty($data['height_overview']);  
    }  
    
    /**
     * Test: '/api/health-live-status' returns latest desk data for user
     */
    public function test_health_live_status_endpoint(): void  
    {  
        // Create recent record  
        UserStatsHistory::create([  
            'user_id' => $this->user->id,  
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1200,  
            'recorded_at' => now()->subMinutes(15)  
        ]);  

        UserStatsHistory::create([  
            'user_id' => $this->user->id,  
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 800,  
            'recorded_at' => now()->subMinutes(55)  
        ]);  
  
        $response = $this->actingAs($this->user)  
            ->getJson('/api/health-live-status');  
  
        $response->assertStatus(200)  
            ->assertJsonStructure([  
                'mode',  
                'height_cm',  
                'last_adjusted',  
                'status'  
            ]);  
  
        $data = $response->json();  
        $this->assertEquals('Standing', $data['mode']);  
        $this->assertEquals(120, $data['height_cm']);  
    }  

    /**
     * Test: health endpoints handles no data smoothly
     */
     public function test_health_endpoints_handle_empty_data_smoothly(): void  
    {  
        // Test with no UserStatsHistory records  
        $responses = [  
            $this->actingAs($this->user)->getJson('/api/health-stats'),  
            $this->actingAs($this->user)->getJson('/api/health-chart-data'),  
            $this->actingAs($this->user)->getJson('/api/health-live-status')  
        ];  
  
        foreach ($responses as $response) {  
            $response->assertStatus(200);  
        }  
    } 


    /**
     * Test: '/api/health-stats' only shows logged-in users data
     */
    public function test_health_stats_user_data_seperation(): void  
    {  
        // create a second user 
        $user2 = User::factory()->create();  
        
        // create data only for second user
        UserStatsHistory::create([  
            'user_id' => $user2->id,  
            'desk_id' => $this->desk->id,
            'desk_height_mm' => 1200,  
            'recorded_at' => now()  
        ]);  
  
        // first user should only see their own data (which is empty)  
        $response = $this->actingAs($this->user)  
            ->getJson('/api/health-stats');  
  
        $response->assertStatus(200);  
        $this->assertEquals(0, $response->json('records_count')); 
        
        // second user should also only see their own data 
        $response2 = $this->actingAs($user2)  
            ->getJson('/api/health-stats');  
  
        $response2->assertStatus(200);  
        $this->assertEquals(1, $response2->json('records_count')); 
    }  

}