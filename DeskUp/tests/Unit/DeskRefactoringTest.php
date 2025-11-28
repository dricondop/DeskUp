<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Desk;
use App\Models\User;
use App\Models\UserStatsHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Firewall Tests for Database Refactoring
 * 
 * These tests ensure that the refactored Desk model works correctly:
 * - Removed: height, status, speed columns from desks table
 * - Added: Virtual attributes that fetch from user_stats_history
 * - Uses: desk_number as foreign key instead of desk->id
 */
class DeskRefactoringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Desk model has virtual attributes (height, status, speed)
     */
    public function test_desk_has_virtual_attributes_in_appends(): void
    {
        $desk = new Desk();
        $appends = $desk->getAppends();
        
        $this->assertContains('height', $appends, 'Height should be in appended attributes');
        $this->assertContains('status', $appends, 'Status should be in appended attributes');
        $this->assertContains('speed', $appends, 'Speed should be in appended attributes');
    }

    /**
     * Test: Default height is returned when no stats history exists
     */
    public function test_desk_height_returns_default_when_no_history(): void
    {
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 100,
            'is_active' => true
        ]);

        $this->assertEquals(110, $desk->height, 'Default height should be 110 cm');
    }

    /**
     * Test: Default status is returned when no stats history exists
     */
    public function test_desk_status_returns_default_when_no_history(): void
    {
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 101,
            'is_active' => true
        ]);

        $this->assertEquals('OK', $desk->status, 'Default status should be OK');
    }

    /**
     * Test: Default speed is returned when no stats history exists
     */
    public function test_desk_speed_returns_default_when_no_history(): void
    {
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 102,
            'is_active' => true
        ]);

        $this->assertEquals(36, $desk->speed, 'Default speed should be 36 mm/s');
    }

    /**
     * Test: Height is fetched from user_stats_history and converted from mm to cm
     */
    public function test_desk_height_fetched_from_stats_history(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 103,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        // Create stats history with height in mm
        UserStatsHistory::create([
            'user_id' => $user->id,
            'desk_id' => $desk->desk_number, // Uses desk_number, not desk->id
            'desk_height_mm' => 1200, // 120 cm
            'desk_speed_mms' => 32,
            'desk_status' => 'Normal',
            'recorded_at' => now()
        ]);

        $desk->refresh();
        $this->assertEquals(120, $desk->height, 'Height should be converted from 1200mm to 120cm');
    }

    /**
     * Test: Status 'Error' is returned for collision condition
     */
    public function test_desk_status_error_for_collision(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 104,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        UserStatsHistory::create([
            'user_id' => $user->id,
            'desk_id' => $desk->desk_number,
            'desk_height_mm' => 1100,
            'desk_speed_mms' => 0,
            'desk_status' => 'Collision',
            'is_anti_collision' => true,
            'recorded_at' => now()
        ]);

        $desk->refresh();
        $this->assertEquals('Error', $desk->status, 'Status should be Error for collision');
    }

    /**
     * Test: Foreign key uses desk_number not desk->id
     */
    public function test_user_stats_history_uses_desk_number_foreign_key(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 105,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        $history = UserStatsHistory::create([
            'user_id' => $user->id,
            'desk_id' => $desk->desk_number, // This should work (foreign key)
            'desk_height_mm' => 1100,
            'desk_speed_mms' => 32,
            'desk_status' => 'Normal',
            'recorded_at' => now()
        ]);

        $this->assertEquals($desk->desk_number, $history->desk_id, 'desk_id should equal desk_number');
        $this->assertNotNull($history->desk, 'Relationship should work via desk_number');
        $this->assertEquals($desk->id, $history->desk->id, 'Should retrieve correct desk');
    }

    /**
     * Test: updateHeight creates new history record
     */
    public function test_update_height_creates_stats_history_record(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 106,
            'is_active' => true,
            'user_id' => null
        ]);

        $user->assigned_desk_id = $desk->id;
        $user->save();

        $initialCount = UserStatsHistory::count();
        
        $desk->updateHeight(115);

        $this->assertEquals($initialCount + 1, UserStatsHistory::count(), 'Should create one new history record');
        
        $latestHistory = UserStatsHistory::latest('recorded_at')->first();
        $this->assertEquals(1150, $latestHistory->desk_height_mm, 'Height should be stored as 1150mm');
        $this->assertEquals($desk->desk_number, $latestHistory->desk_id, 'Should use desk_number');
    }

    /**
     * Test: updateStatus creates new history record
     */
    public function test_update_status_creates_stats_history_record(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 107,
            'is_active' => true,
            'user_id' => null
        ]);

        $user->assigned_desk_id = $desk->id;
        $user->save();

        $initialCount = UserStatsHistory::count();
        
        $desk->updateStatus('collision');

        $this->assertEquals($initialCount + 1, UserStatsHistory::count(), 'Should create one new history record');
        
        $latestHistory = UserStatsHistory::latest('recorded_at')->first();
        $this->assertEquals('collision', $latestHistory->desk_status, 'Status should be stored as collision');
        $this->assertTrue($latestHistory->is_anti_collision, 'Should set collision flag');
    }

    /**
     * Test: JSON serialization includes virtual attributes
     */
    public function test_desk_json_includes_virtual_attributes(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 108,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        UserStatsHistory::create([
            'user_id' => $user->id,
            'desk_id' => $desk->desk_number,
            'desk_height_mm' => 1100,
            'desk_speed_mms' => 32,
            'desk_status' => 'Normal',
            'recorded_at' => now()
        ]);

        $json = $desk->fresh()->toArray();

        $this->assertArrayHasKey('height', $json, 'JSON should include height');
        $this->assertArrayHasKey('status', $json, 'JSON should include status');
        $this->assertArrayHasKey('speed', $json, 'JSON should include speed');
    }
}
