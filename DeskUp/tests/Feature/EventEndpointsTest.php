<?php  
  
namespace Tests\Feature;  
  
use App\Models\Event;  
use App\Models\User;  
use App\Models\Desk;  
use Illuminate\Foundation\Testing\RefreshDatabase;  
use Tests\TestCase;  
  
class EventEndpointsTest extends TestCase  
{  
    use RefreshDatabase;  
  
    private User $adminUser;  
    private User $regularUser;  
    private Desk $desk1;  
    private Desk $desk2;  
  
    protected function setUp(): void  
    {  
        parent::setUp();  
        $this->adminUser = User::factory()->create(['is_admin' => true]);  
        $this->regularUser = User::factory()->create(['is_admin' => false]);  
        $this->desk1 = Desk::factory()->create();  
        $this->desk2 = Desk::factory()->create();  
    } 

    /**
     * Test: /api/addEvent endpoint add events marked as approved by admin
     */
    public function test_add_event_endpoint_admin_creates_approved_event(): void  
    {  
        $response = $this->actingAs($this->adminUser)  
            ->postJson('/api/addEvent', [  
                'event_type' => 'meeting',  
                'description' => 'Team meeting',  
                'scheduled_at' => '2026-1-1 10:00:00',  
                'scheduled_to' => '2026-1-1 11:00:00',  
                'desk_ids' => [$this->desk1->id],  
                'user_ids' => [$this->adminUser->id]  
            ]);  
  
        $response->assertStatus(200)  
            ->assertJson([  
                'success' => true,  
                'message' => 'Event added successfully'  
            ]);  
  
        $this->assertDatabaseHas('events', [  
            'event_type' => 'meeting',  
            'status' => Event::STATUS_APPROVED,  
            'created_by' => $this->adminUser->id  
        ]);  
  
        $this->assertDatabaseHas('event_desks', [  
            'event_id' => 1,  
            'desk_id' => $this->desk1->id  
        ]);  
  
        $this->assertDatabaseHas('event_users', [  
            'event_id' => 1,  
            'user_id' => $this->adminUser->id  
        ]);  
    }  

    /**
     * Test: /api/addEvent endpoint add events marked as pending by regular user
     */
    public function test_add_event_endpoint_regular_user_creates_pending_event(): void  
    {  
        $response = $this->actingAs($this->regularUser)  
            ->postJson('/api/addEvent', [  
                'event_type' => 'meeting',  
                'description' => 'Team meeting',  
                'scheduled_at' => '2026-1-1 10:00:00',  
                'scheduled_to' => '2026-1-1 11:00:00',  
                'desk_ids' => [$this->desk1->id],  
                'user_ids' => [$this->regularUser->id]  
            ]);  
  
        $response->assertStatus(200)  
            ->assertJson([  
                'success' => true,  
                'message' => 'Event added successfully'  
            ]);  
  
        $this->assertDatabaseHas('events', [  
            'event_type' => 'meeting',  
            'status' => Event::STATUS_PENDING,  
            'created_by' => $this->regularUser->id  
        ]);  
  
        $this->assertDatabaseHas('event_desks', [  
            'event_id' => 1,  
            'desk_id' => $this->desk1->id  
        ]);  
  
        $this->assertDatabaseHas('event_users', [  
            'event_id' => 1,  
            'user_id' => $this->regularUser->id  
        ]);  
    }  

    /**
     * Test: /api/addCleaningSchedule endpoint adds cleaning schedule
     */
    public function test_add_cleaning_schedule_endpoint_as_admin(): void  
    {  
        $response = $this->actingAs($this->adminUser)  
            ->postJson('/api/addCleaningSchedule', [  
                'cleaning_time' => '18:30',  
                'cleaning_days' => ['MON', 'WED', 'FRI']  
            ]);  
  
        $response->assertStatus(200)  
            ->assertJson([  
                'success' => true,  
                'message' => 'Cleaning Schedule added successfully'  
            ]);  
  
        $this->assertDatabaseHas('events', [  
            'event_type' => 'cleaning',  
            'status' => Event::STATUS_APPROVED,  
            'cleaning_time' => '18:30'  
        ]);  
    } 

    /**
     * Test: /api/addCleaningSchedule endpoint fails if logged in user is not admin
     */
    public function test_add_cleaning_schedule_endpoint_as_regular_user_fails(): void  
    {  
        $response = $this->actingAs($this->regularUser)  
            ->postJson('/api/addCleaningSchedule', [  
                'cleaning_time' => '18:30',  
                'cleaning_days' => ['MON', 'WED', 'FRI']  
            ]);  
  
        $response->assertStatus(200)  
            ->assertJson([  
                'success' => false,  
                'message' => 'Logged in user does not have administrator access'  
            ]);  
  
        $this->assertDatabaseEmpty('events');  
    } 

    /**
     * Test: /event/{$event->id}/availableUsers endpoint returns available users
     */
    public function test_available_users_endpoint(): void  
    {  
        $user1 = User::factory()->create();  
        $user2 = User::factory()->create();   
          
        $event = Event::factory()->create([
            'created_by' => $this->adminUser->id,
        ]);  
        $event->users()->attach([$this->adminUser->id, $this->regularUser->id]);  
        
        // get available users 
        $response = $this->actingAs($this->adminUser)  
            ->getJson("/event/{$event->id}/availableUsers");  
  
        $response->assertStatus(200);  
        $users = $response->json();  
        $userIds = array_column($users, 'id');
          
        $this->assertCount(2, $users);   
        $this->assertContains($user1->id, $userIds);  
        $this->assertContains($user2->id, $userIds);   
    }  

    /**
     * Test: /event/{$event->id}/addUser endpoint adds a user to an existing event
     */
    public function test_add_user_to_event_endpoint(): void  
    {  
        $event = Event::factory()->create([
            'created_by' => $this->adminUser->id,
        ]);  

        $this->assertDatabaseMissing('event_users', [
            'event_id' => $event->id,
            'user_id' => $this->regularUser->id,
        ]);
  
        $response = $this->actingAs($this->adminUser)  
            ->postJson("/event/{$event->id}/addUser", [  
                'user' => $this->regularUser->id  
            ]);  
  
        $response->assertStatus(200)  
            ->assertJson([  
                'success' => true,  
                'user_id' => $this->regularUser->id  
            ]);  
  
        $this->assertDatabaseHas('event_users', [  
            'event_id' => $event->id,  
            'user_id' => $this->regularUser->id  
        ]);  
    } 

    /**
     * Test: /event/{$event->id}/approve endpoint marks an event as 'approved' or 'rejected'
     */
    public function test_event_approval_endpoint(): void  
    {  
        $event1 = Event::factory()->create(['status' => Event::STATUS_PENDING]);
        $event2 = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        // approve event
        $approveEvent = $this->actingAs($this->adminUser)
            ->postJson("/event/{$event1->id}/approve");

        $approveEvent->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event has been approved successfully'
            ]);

        // reject event
        $rejectEvent = $this->actingAs($this->adminUser)
            ->postJson("/event/{$event2->id}/reject");

        $rejectEvent->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event has been rejected successfully'
            ]);
        
        $event1->refresh();
        $event2->refresh();
        $this->assertEquals(Event::STATUS_APPROVED, $event1->status);
        $this->assertEquals(Event::STATUS_REJECTED, $event2->status);
        
    } 
}