<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatsHistory extends Model
{
    use HasFactory;

    protected $table = 'user_stats_history';

    protected $fillable = [
        'user_id',
        'desk_id',
        'desk_height_mm',
        'desk_speed_mms',
        'desk_status',
        'is_position_lost',
        'is_overload_up',
        'is_overload_down',
        'is_anti_collision',
        'activations_count',
        'sit_stand_count',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_position_lost' => 'boolean',
        'is_overload_up' => 'boolean',
        'is_overload_down' => 'boolean',
        'is_anti_collision' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function desk()
    {
        return $this->belongsTo(Desk::class);
    }
}
