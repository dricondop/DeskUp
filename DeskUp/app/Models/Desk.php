<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Event;

class Desk extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'desk_number',
        'api_desk_id',
        'position_x',
        'position_y',
        'is_active',
        'user_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position_x' => 'float',
        'position_y' => 'float'
    ];

    // Append these attributes to JSON serialization for API responses
    protected $appends = ['height', 'status', 'speed'];

    // Relationship to desk events (cleaning, meetings, etc.)
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_desks');
    }

    // Relationship to user stats history using desk_number as foreign key
    public function userStatsHistory()
    {
        return $this->hasMany(UserStatsHistory::class, 'desk_id', 'desk_number');
    }

    // Get the most recent stats record for this desk, desks table no longer has height, status nor speed so it must retrieve it from user_stats_history
    public function latestStats()
    {
        return $this->hasOne(UserStatsHistory::class, 'desk_id', 'desk_number')->latestOfMany('recorded_at');
    }

    // Get height from latest stats (converts mm to cm for display)
    public function getHeightAttribute()
    {
        $latestStats = $this->latestStats;
        if ($latestStats && $latestStats->desk_height_mm) {
            return round($latestStats->desk_height_mm / 10); // Convert mm to cm
        }
        return 110; // Default height in cm
    }

    // Get status from latest stats (determines if desk has errors)
    public function getStatusAttribute()
    {
        $latestStats = $this->latestStats;
        if ($latestStats) {
            return $this->determineStatus($latestStats);
        }
        return 'OK'; // Default status
    }

    // Get speed from latest stats (in mm/s for API compatibility)
    public function getSpeedAttribute()
    {
        $latestStats = $this->latestStats;
        if ($latestStats) {
            return $latestStats->desk_speed_mms; // Already in mm/s
        }
        return 36; // Default speed
    }

    // Determine status from stats data (checks for collision, overload, etc.)
    private function determineStatus($stats): string
    {
        if ($stats->desk_status === 'Collision' || 
            $stats->is_anti_collision || 
            $stats->is_overload_up || 
            $stats->is_overload_down || 
            $stats->is_position_lost) {
            return 'Error';
        }
        return 'OK';
    }

    // Update desk height by creating new UserStatsHistory record
    public function newUserStatsHistoryRecord(int $height): void
    {
        // Find the user associated with this desk
        $user = \App\Models\User::where('assigned_desk_id', $this->id)->first();
        
        if ($user) {
            \App\Models\UserStatsHistory::create([
                'user_id' => $user->id,
                'desk_id' => $this->desk_number,
                'desk_height_mm' => $height,
                'desk_speed_mms' => $this->speed ?? 36,
                'desk_status' => $this->status ?? 'OK',
                'is_position_lost' => false,
                'is_overload_up' => false,
                'is_overload_down' => false,
                'is_anti_collision' => false,
                'activations_count' => $this->latestStats->activations_count ?? 0,
                'sit_stand_count' => $this->latestStats->sit_stand_count ?? 0,
                'recorded_at' => now(),
            ]);
        }
    }

    // Update desk status by creating new UserStatsHistory record with collision flags
    public function updateStatus(string $status): void
    {
        $user = \App\Models\User::where('assigned_desk_id', $this->id)->first();
        
        if ($user) {
            \App\Models\UserStatsHistory::create([
                'user_id' => $user->id,
                'desk_id' => $this->desk_number,
                'desk_height_mm' => ($this->height ?? 110) * 10, // Convert cm to mm
                'desk_speed_mms' => $this->speed ?? 36,
                'desk_status' => $status,
                'is_position_lost' => $status === 'collision', // Set collision flags based on status
                'is_overload_up' => false,
                'is_overload_down' => false,
                'is_anti_collision' => $status === 'collision',
                'activations_count' => $this->latestStats->activations_count ?? 0,
                'sit_stand_count' => $this->latestStats->sit_stand_count ?? 0,
                'recorded_at' => now(),
            ]);
        }
    }

    // Get the user assigned to this desk via user_id foreign key
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
