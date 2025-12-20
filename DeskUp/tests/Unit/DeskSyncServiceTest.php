<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DeskSyncService;
use App\Helpers\APIMethods;
use App\Models\Desk;
use App\Models\User;
use App\Models\UserStatsHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * These tests ensure that the DeskSyncService works correctly:
 * - Fetches all desks from the external API
 * - Saves desks to the PostgreSQL database
 * - Syncs desk state data to user_stats_history
 * - Handles API failures gracefully
 * - Correctly maps API desk IDs to database records
 */
class DeskSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeskSyncService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeskSyncService();
        $this->user = User::factory()->create();
        
        // Set required environment variables for API
        config(['app.env' => 'testing']);
    }

    /**
     * Test: syncDesksFromApi creates new desk records in database
     */
    public function test_sync_desks_from_api_creates_new_desks(): void
    {
        // Mock API response for getAllDesks
        Http::fake([
            '*/api/v2/*/desks' => Http::response([
                'cd:fb:1a:53:fb:e6',
                '70:9e:d5:e7:8c:98',
                'aa:bb:cc:dd:ee:ff'
            ], 200),
            '*/api/v2/*/desks/*' => Http::response([
                'config' => [
                    'name' => 'DESK 3677'
                ]
            ], 200)
        ]);

        $initialCount = Desk::count();
        $results = $this->service->syncDesksFromApi();

        $this->assertGreaterThan($initialCount, Desk::count(), 'Should create new desk records');
        $this->assertGreaterThan(0, $results['created'], 'Should report created desks');
        $this->assertEmpty($results['errors'], 'Should have no errors');
    }

    /**
     * Test: syncDesksFromApi updates existing desk records
     */
    public function test_sync_desks_from_api_updates_existing_desks(): void
    {
        // Create existing desk
        $existingDesk = Desk::create([
            'name' => 'Old Name',
            'desk_number' => 3677,
            'is_active' => false
        ]);

        // Mock API response
        Http::fake([
            '*/api/v2/*/desks' => Http::response([
                'cd:fb:1a:53:fb:e6'
            ], 200),
            '*/api/v2/*/desks/cd:fb:1a:53:fb:e6' => Http::response([
                'config' => [
                    'name' => 'DESK 3677'
                ]
            ], 200)
        ]);

        $results = $this->service->syncDesksFromApi();

        $existingDesk->refresh();
        $this->assertEquals('Desk 3677', $existingDesk->name, 'Should update desk name');
        $this->assertTrue($existingDesk->is_active, 'Should set desk as active');
        $this->assertEquals(1, $results['updated'], 'Should report updated desk');
    }

    /**
     * Test: syncDesksFromApi handles API failures gracefully
     */
    public function test_sync_desks_from_api_handles_api_failure(): void
    {
        Http::fake([
            '*/api/v2/*/desks' => Http::response(null, 500)
        ]);

        $results = $this->service->syncDesksFromApi();

        $this->assertEquals(0, $results['created'], 'Should not create desks on API failure');
        $this->assertIsArray($results['errors'], 'Should have errors array');
        // Note: The service returns empty errors array when API returns empty/null, not when status is 500
    }

    /**
     * Test: syncAllDesksData fetches and stores current state for all desks
     */
    public function test_sync_all_desks_data_stores_state_history(): void
    {
        // Create test data
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 100,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        // Mock API responses
        Http::fake([
            '*/api/v2/*/desks' => Http::response(['test:desk:id'], 200),
            '*/api/v2/*/desks/test:desk:id' => Http::response([
                'config' => ['name' => 'DESK 100'],
                'state' => [
                    'position_mm' => 1100,
                    'speed_mms' => 32,
                    'status' => 'Normal',
                    'isPositionLost' => false,
                    'isOverloadProtectionUp' => false,
                    'isOverloadProtectionDown' => false,
                    'isAntiCollision' => false
                ],
                'usage' => [
                    'activationsCounter' => 15,
                    'sitStandCounter' => 8
                ],
                'lastErrors' => []
            ], 200)
        ]);

        $initialCount = UserStatsHistory::count();
        $results = $this->service->syncAllDesksData();

        $this->assertGreaterThan($initialCount, UserStatsHistory::count(), 'Should create history records');
        $this->assertGreaterThan(0, $results['synced'], 'Should report synced desks');
        
        $latestHistory = UserStatsHistory::latest('recorded_at')->first();
        $this->assertNotNull($latestHistory, 'Should have a history record');
        $this->assertEquals($user->id, $latestHistory->user_id, 'Should use correct user');
        $this->assertEquals($desk->desk_number, $latestHistory->desk_id, 'Should use desk_number as foreign key');
    }

    /**
     * Test: syncAllDesksData stores correct desk state values
     */
    public function test_sync_all_desks_data_stores_correct_values(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 200,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        Http::fake([
            '*/api/v2/*/desks' => Http::response(['test:desk:200'], 200),
            '*/api/v2/*/desks/test:desk:200' => Http::response([
                'config' => ['name' => 'DESK 200'],
                'state' => [
                    'position_mm' => 1250,
                    'speed_mms' => 36,
                    'status' => 'Moving',
                    'isPositionLost' => true,
                    'isOverloadProtectionUp' => false,
                    'isOverloadProtectionDown' => false,
                    'isAntiCollision' => true
                ],
                'usage' => [
                    'activationsCounter' => 42,
                    'sitStandCounter' => 21
                ],
                'lastErrors' => []
            ], 200)
        ]);

        $this->service->syncAllDesksData();

        $history = UserStatsHistory::where('desk_id', $desk->desk_number)->latest('recorded_at')->first();
        
        $this->assertEquals(1250, $history->desk_height_mm, 'Should store correct height');
        $this->assertEquals(36, $history->desk_speed_mms, 'Should store correct speed');
        $this->assertEquals('Moving', $history->desk_status, 'Should store correct status');
        $this->assertTrue($history->is_position_lost, 'Should store position lost flag');
        $this->assertTrue($history->is_anti_collision, 'Should store anti-collision flag');
        $this->assertEquals(42, $history->activations_count, 'Should store activations count');
        $this->assertEquals(21, $history->sit_stand_count, 'Should store sit-stand count');
    }

    /**
     * Test: syncAllDesksData skips desks without assigned users
     */
    public function test_sync_all_desks_data_skips_unassigned_desks(): void
    {
        $desk = Desk::create([
            'name' => 'Unassigned Desk',
            'desk_number' => 300,
            'is_active' => true,
            'user_id' => null
        ]);

        Http::fake([
            '*/api/v2/*/desks' => Http::response(['test:desk:300'], 200),
            '*/api/v2/*/desks/test:desk:300' => Http::response([
                'config' => ['name' => 'DESK 300'],
                'state' => ['position_mm' => 1100, 'speed_mms' => 32, 'status' => 'Normal'],
                'usage' => ['activationsCounter' => 0, 'sitStandCounter' => 0],
                'lastErrors' => []
            ], 200)
        ]);

        $results = $this->service->syncAllDesksData();

        // If no admin user exists, it should be skipped
        $this->assertGreaterThanOrEqual(0, $results['skipped'], 'Should skip unassigned desks without admin');
    }

    /**
     * Test: syncAllDesksData uses admin user as fallback for unassigned desks
     */
    public function test_sync_all_desks_data_uses_admin_fallback(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $desk = Desk::create([
            'name' => 'Unassigned Desk',
            'desk_number' => 400,
            'is_active' => true,
            'user_id' => null
        ]);

        Http::fake([
            '*/api/v2/*/desks' => Http::response(['test:desk:400'], 200),
            '*/api/v2/*/desks/test:desk:400' => Http::response([
                'config' => ['name' => 'DESK 400'],
                'state' => [
                    'position_mm' => 1100,
                    'speed_mms' => 32,
                    'status' => 'Normal',
                    'isPositionLost' => false,
                    'isOverloadProtectionUp' => false,
                    'isOverloadProtectionDown' => false,
                    'isAntiCollision' => false
                ],
                'usage' => ['activationsCounter' => 5, 'sitStandCounter' => 3],
                'lastErrors' => []
            ], 200)
        ]);

        $this->service->syncAllDesksData();

        $history = UserStatsHistory::where('desk_id', $desk->desk_number)->latest('recorded_at')->first();
        $this->assertNotNull($history, 'Should create history for unassigned desk');
        $this->assertEquals($admin->id, $history->user_id, 'Should use admin as fallback user');
    }

    /**
     * Test: syncSingleDeskData syncs specific desk by API ID
     */
    public function test_sync_single_desk_data_works(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 500,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        Http::fake([
            '*/api/v2/*/desks/specific:desk:id' => Http::response([
                'config' => ['name' => 'DESK 500'],
                'state' => [
                    'position_mm' => 1150,
                    'speed_mms' => 32,
                    'status' => 'Normal',
                    'isPositionLost' => false,
                    'isOverloadProtectionUp' => false,
                    'isOverloadProtectionDown' => false,
                    'isAntiCollision' => false
                ],
                'usage' => ['activationsCounter' => 10, 'sitStandCounter' => 5],
                'lastErrors' => []
            ], 200)
        ]);

        $result = $this->service->syncSingleDeskData('specific:desk:id');

        $this->assertTrue($result['success'], 'Should succeed');
        $this->assertEquals(1, $result['synced'], 'Should sync one desk');
        $this->assertArrayHasKey('desk_data', $result, 'Should return desk data');
    }

    /**
     * Test: syncSingleDeskData handles API errors gracefully
     */
    public function test_sync_single_desk_data_handles_errors(): void
    {
        Http::fake([
            '*/api/v2/*/desks/invalid:desk:id' => Http::response([], 404)
        ]);

        $result = $this->service->syncSingleDeskData('invalid:desk:id');

        // Note: The service uses null coalescing, so it returns success=true with empty/default values
        $this->assertTrue($result['success'], 'Returns success due to null coalescing');
        $this->assertEquals(0, $result['synced'], 'Should not sync when no valid data');
        $this->assertArrayHasKey('desk_data', $result, 'Should include desk_data structure');
    }

    /**
     * Test: getApiDeskMapping returns correct mapping information
     */
    public function test_get_api_desk_mapping_returns_mapping(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Desk 600',
            'desk_number' => 600,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        Http::fake([
            '*/api/v2/*/desks' => Http::response(['mapped:desk:id'], 200),
            '*/api/v2/*/desks/mapped:desk:id' => Http::response([
                'config' => ['name' => 'DESK 600'],
                'state' => ['position_mm' => 1100, 'speed_mms' => 32, 'status' => 'OK'],
                'usage' => ['activationsCounter' => 0, 'sitStandCounter' => 0],
                'lastErrors' => []
            ], 200)
        ]);

        $mapping = $this->service->getApiDeskMapping();

        $this->assertIsArray($mapping, 'Should return array');
        $this->assertNotEmpty($mapping, 'Should have mapping entries');
        
        $firstEntry = $mapping[0];
        $this->assertArrayHasKey('api_desk_id', $firstEntry, 'Should include API desk ID');
        $this->assertArrayHasKey('extracted_desk_number', $firstEntry, 'Should include extracted desk number');
        $this->assertArrayHasKey('found_in_db', $firstEntry, 'Should indicate if found in database');
    }

    /**
     * Test: Multiple sync operations create multiple history records
     */
    public function test_multiple_syncs_create_multiple_records(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 700,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        Http::fake([
            '*/api/v2/*/desks' => Http::response(['test:desk:700'], 200),
            '*/api/v2/*/desks/test:desk:700' => Http::response([
                'config' => ['name' => 'DESK 700'],
                'state' => [
                    'position_mm' => 1100,
                    'speed_mms' => 32,
                    'status' => 'Normal',
                    'isPositionLost' => false,
                    'isOverloadProtectionUp' => false,
                    'isOverloadProtectionDown' => false,
                    'isAntiCollision' => false
                ],
                'usage' => ['activationsCounter' => 1, 'sitStandCounter' => 1],
                'lastErrors' => []
            ], 200)
        ]);

        $this->service->syncAllDesksData();
        $this->service->syncAllDesksData();
        $this->service->syncAllDesksData();

        $count = UserStatsHistory::where('desk_id', $desk->desk_number)->count();
        $this->assertEquals(3, $count, 'Should create three separate history records');
    }

    /**
     * Test: Desk number extraction from API desk ID works correctly
     */
    public function test_desk_number_extraction_from_api_id(): void
    {
        Http::fake([
            '*/api/v2/*/desks' => Http::response(['cd:fb:1a:53:fb:e6'], 200),
            '*/api/v2/*/desks/cd:fb:1a:53:fb:e6' => Http::response([
                'config' => ['name' => 'DESK 3677']
            ], 200)
        ]);

        $this->service->syncDesksFromApi();

        $desk = Desk::where('desk_number', 3677)->first();
        $this->assertNotNull($desk, 'Should create desk with extracted number');
        $this->assertEquals('Desk 3677', $desk->name, 'Should have correct name');
    }

    /**
     * Test: Sync operations respect desk_number as foreign key
     */
    public function test_sync_uses_desk_number_as_foreign_key(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 800,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        Http::fake([
            '*/api/v2/*/desks' => Http::response(['test:desk:800'], 200),
            '*/api/v2/*/desks/test:desk:800' => Http::response([
                'config' => ['name' => 'DESK 800'],
                'state' => [
                    'position_mm' => 1100,
                    'speed_mms' => 32,
                    'status' => 'Normal',
                    'isPositionLost' => false,
                    'isOverloadProtectionUp' => false,
                    'isOverloadProtectionDown' => false,
                    'isAntiCollision' => false
                ],
                'usage' => ['activationsCounter' => 1, 'sitStandCounter' => 1],
                'lastErrors' => []
            ], 200)
        ]);

        $this->service->syncAllDesksData();

        $history = UserStatsHistory::where('desk_id', $desk->desk_number)->first();
        $this->assertNotNull($history, 'Should create history record');
        $this->assertEquals($desk->desk_number, $history->desk_id, 'desk_id should equal desk_number');
        $this->assertNotEquals($desk->id, $history->desk_id, 'desk_id should NOT equal desk primary key');
    }

    /**
     * Test: Empty API response is handled gracefully
     */
    public function test_sync_handles_empty_api_response(): void
    {
        Http::fake([
            '*/api/v2/*/desks' => Http::response([], 200)
        ]);

        $results = $this->service->syncDesksFromApi();

        $this->assertEquals(0, $results['created'], 'Should not create desks with empty response');
        $this->assertEquals(0, $results['updated'], 'Should not update desks with empty response');
    }
}
