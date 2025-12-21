<?php  
  
namespace Tests\Unit;  
  
use App\Http\Controllers\EventController;  
use App\Models\Event;  
use App\Models\User;  
use App\Models\Desk;  
use Illuminate\Foundation\Testing\RefreshDatabase;  
use Illuminate\Http\Request;  
use Tests\TestCase;  
use Illuminate\Support\Facades\Auth;
use PhpParser\Lexer\TokenEmulator\VoidCastEmulator;
use Carbon\Carbon;


/**
 * These tests ensure that the Event methods works correctly:
 * - Creates events in database
 * - Add user to events
 * - Return users not assigned to specific event
 * - Approve / Reject event
 * - Create cleaning schedule 
 */
class EventControllerTest extends TestCase  
{  
    use RefreshDatabase;  
  
    private EventController $controller;  
    private User $adminUser;  
    private User $regularUser;  
  
    protected function setUp(): void  
    {  
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 12, 20, 12, 0, 0)); // freezez time, ensures it works if time is just past midnight

        $this->controller = new EventController();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);

        // Set required environment variables for API
        config(['app.env' => 'testing']);
    }

    /**
     * Test: addEvent by administrator creates an event with status 'approved'
     */
    public function test_add_event_creates_approved_event_for_admin(): void
    {
        Auth::shouldReceive('user')->andReturn($this->adminUser);

        $desk = Desk::factory()->create();
        $request = new Request([
            'event_type' => 'meeting',
            'description' => 'Test meeting',
            'scheduled_at' => now()->toDateString(),
            'scheduled_to' => now()->subHour(2)->toDateString(),
            'desk_ids' => [$desk->id],
            'user_ids' => [$this->adminUser->id],      
        ]);

        $response = $this->controller->addEvent($request);

        // assert the response it correct
        $this->assertTrue($response->getData()->success);
        $this->assertEquals('Event added successfully', $response->getData()->message);

        $event = Event::first();
        $this->assertEquals(Event::STATUS_APPROVED, $event->status);
        $this->assertEquals('meeting', $event->event_type);
        $this->assertEquals($this->adminUser->id, $event->created_by);
    }

    /**
     * Test: addEvent by regular user creates an event with status 'pending'
     */
    public function test_add_event_creates_pending_event_for_regular_user(): void
    {
        Auth::shouldReceive('user')->andReturn($this->regularUser);

        $desk = Desk::factory()->create();
        $request = new Request([
            'event_type' => 'meeting',
            'description' => 'Test meeting',
            'scheduled_at' => now()->toDateString(),
            'scheduled_to' => now()->subHour(2)->toDateString(),
            'desk_ids' => [$desk->id],
            'user_ids' => [$this->regularUser->id],      
        ]);

        $response = $this->controller->addEvent($request);

        // assert the response it correct
        $this->assertTrue($response->getData()->success);
        $this->assertEquals('Event added successfully', $response->getData()->message);

        $event = Event::first();
        $this->assertEquals(Event::STATUS_PENDING, $event->status);
        $this->assertEquals('meeting', $event->event_type);
        $this->assertEquals($this->regularUser->id, $event->created_by);
    }

    /**
     * Test: addCleaningSchedule sets previous cleaning schedule as 'completed'
     */
    public function test_add_cleaning_schedule_marks_previous_as_completed(): void  
    {  
        Auth::shouldReceive('user')->andReturn($this->adminUser);  
          
        // Create existing approved cleaning schedule  
        $existingCleaning = Event::create([  
            'event_type' => 'cleaning',  
            'description' => 'Old cleaning',  
            'cleaning_time' => now()->toDateString(),
            'cleaning_days' => ["MON", "TUE"],
            'is_recurring' => true,
            'status' => Event::STATUS_APPROVED,  
            'created_by' => $this->adminUser->id  
        ]);  
  
        $request = new Request([  
            'cleaning_time' => '18:00',  
            'cleaning_days' => ['MON', 'WED', 'FRI']  
        ]);  
        
        // new cleaning schedule
        $response = $this->controller->addCleaningSchedule($request);  
  
        $this->assertTrue($response->getData()->success);  
          
        // previous schedule should be marked as completed  
        $existingCleaning->refresh();  
        $this->assertEquals(Event::STATUS_COMPLETED, $existingCleaning->status);  
          
        // new schedule should be approved  
        $newSchedule = Event::where('status', Event::STATUS_APPROVED)->first();  
        $this->assertEquals('cleaning', $newSchedule->event_type);  
        $this->assertEquals(['MON', 'WED', 'FRI'], $newSchedule->cleaning_days);  
    } 

    /**
     * Test: addCleaningSchedule fails smoothly if logged-in user does not have admin access
     */
    public function test_add_cleaning_schedule_fails_if_user_is_not_admin(): void  
    {  
        Auth::shouldReceive('user')->andReturn($this->regularUser);  
          
        $request = new Request([  
            'cleaning_time' => '19:00',  
            'cleaning_days' => ['TUE', 'THU']  
        ]);  
  
        $response = $this->controller->addCleaningSchedule($request);  
  
        $this->assertFalse($response->getData()->success);  
        $this->assertEquals('Logged in user does not have administrator access', $response->getData()->message);
    }

     /**
     * Test: availableUsers excludes users already assigned to event
     */
    public function test_available_users_excludes_users_already_assigned_to_event(): void  
    {  
        $user1 = User::factory()->create();  
        $user2 = User::factory()->create();  
          
        $event = Event::factory()->create([
            'created_by' => $this->adminUser->id,
        ]);  
        $event->users()->attach([$this->regularUser->id, $this->adminUser->id]);  
  
        $response = $this->controller->availableUsers($event); 
        
        // returns users not attached to the event
        $users = $response->getData(); 

        $this->assertCount(2, $users);  
        $this->assertNotContains($user1->id, $users);
        $this->assertNotContains($user2->id, $users);
    } 

    /**
     * Test: addUser adds a user to an event
     */
    public function test_add_user_to_event(): void  
    {  
        $event = Event::factory()->create([
            'created_by' => $this->adminUser->id,
        ]);  
        $event->users()->attach($this->adminUser->id);

        // assert event only contains one user
        $this->assertEquals(1, $event->users()->count());
        
        // add new user
        $request = new Request(['user' => $this->regularUser->id]);  
        $response = $this->controller->addUserToEvent($request, $event);  
  
        $this->assertTrue($response->getData()->success);  
        $this->assertEquals($this->regularUser->id, $response->getData()->user_id);  
        $this->assertEquals(2, $event->users()->count());
        $this->assertTrue($event->users()->where('users.id', $this->regularUser->id)->exists());  
    } 

    /**
     * Test: addUser cannot add the same user twice.
     */
    public function test_add_user_to_event_does_not_duplicate(): void  
    {  
        $event = Event::factory()->create([
            'created_by' => $this->adminUser->id,
        ]);   
        $event->users()->attach($this->adminUser->id);  

        // assert event only contains one user
        $this->assertEquals(1, $event->users()->count());
        
        // try to add admin user again
        $request = new Request(['user' => $this->adminUser->id]);  
        $response = $this->controller->addUserToEvent($request, $event);  
  
        $this->assertTrue($response->getData()->success);  
        $this->assertEquals(1, $event->users()->count()); // Still only one user  
    }  

    /**d
     * Test: event relationship with pivot tables work correctly
     */
    public function test_event_relationships__with_pivot_tables_work_correctly(): void  
    {  
        $event = Event::factory()->create([   
            'created_by' => $this->adminUser->id  
        ]);

        $desk1 = Desk::factory()->create();
        $desk2 = Desk::factory()->create();
  
        $event->desks()->attach([$desk1->id, $desk2->id]);  
        $event->users()->attach([$this->regularUser->id]);  
  
        // Test relationships  
        $this->assertEquals($this->adminUser->id, $event->creator->id);  
        $this->assertCount(2, $event->desks);  
        $this->assertCount(1, $event->users);  
        $this->assertTrue($event->desks->contains($desk1));  
        $this->assertTrue($event->desks->contains($desk2));  
        $this->assertTrue($event->users->contains($this->regularUser));  
    } 

}