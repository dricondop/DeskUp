<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Desk;
use App\Models\User;
use App\Models\UserStatsHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Firewall Tests for DeskController Endpoints
 * 
 * These tests ensure the refactored controller endpoints work correctly:
 * - POST /api/desks/{id}/height
 * - POST /api/desks/{id}/status
 * - GET /api/desks
 */
class DeskControllerRefactoringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: updateHeight endpoint works and returns virtual attribute
     */
    public function test_update_height_endpoint_works(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 200,
            'is_active' => true,
            'user_id' => null
        ]);

        $user->assigned_desk_id = $desk->id;
        $user->save();

        $response = $this->actingAs($user)->postJson("/api/desks/{$desk->id}/height", [
            'height' => 115
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Height updated successfully'
            ])
            ->assertJsonStructure(['height']);

        // Verify history was created
        $this->assertDatabaseHas('user_stats_history', [
            'desk_id' => $desk->desk_number,
            'desk_height_mm' => 1150
        ]);
    }

    /**
     * Test: updateHeight validates input
     */
    public function test_update_height_validates_input(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 201,
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->postJson("/api/desks/{$desk->id}/height", [
            'height' => 'invalid'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['height']);
    }

    /**
     * Test: updateStatus endpoint works and returns virtual attribute
     */
    public function test_update_status_endpoint_works(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 202,
            'is_active' => true,
            'user_id' => null
        ]);

        $user->assigned_desk_id = $desk->id;
        $user->save();

        $response = $this->actingAs($user)->postJson("/api/desks/{$desk->id}/status", [
            'status' => 'Normal'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Status updated successfully'
            ])
            ->assertJsonStructure(['status']);

        // Verify history was created
        $this->assertDatabaseHas('user_stats_history', [
            'desk_id' => $desk->desk_number,
            'desk_status' => 'Normal'
        ]);
    }

    /**
     * Test: updateStatus validates input
     */
    public function test_update_status_validates_input(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 203,
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->postJson("/api/desks/{$desk->id}/status", [
            'status' => str_repeat('a', 51) // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test: Desk index returns virtual attributes in JSON
     */
    public function test_index_returns_desks_with_virtual_attributes(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 204,
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

        $response = $this->actingAs($user)->getJson('/api/desks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'desks' => [
                    '*' => [
                        'id',
                        'name',
                        'desk_number',
                        'height',
                        'status',
                        'speed',
                        'is_active'
                    ]
                ]
            ]);
    }

    /**
     * Test: Multiple height updates create multiple history records
     */
    public function test_multiple_updates_create_multiple_records(): void
    {
        $user = User::factory()->create();
        $desk = Desk::create([
            'name' => 'Test Desk',
            'desk_number' => 205,
            'is_active' => true,
            'user_id' => null
        ]);

        $user->assigned_desk_id = $desk->id;
        $user->save();

        $this->actingAs($user)->postJson("/api/desks/{$desk->id}/height", ['height' => 85]);
        $this->actingAs($user)->postJson("/api/desks/{$desk->id}/height", ['height' => 110]);
        $this->actingAs($user)->postJson("/api/desks/{$desk->id}/height", ['height' => 120]);

        $count = UserStatsHistory::where('desk_id', $desk->desk_number)->count();
        $this->assertEquals(3, $count, 'Should have 3 history records');
    }
}
