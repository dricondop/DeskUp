<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'event_type',
        'description',
        'scheduled_at',
        'scheduled_to',
        'status',
        'created_by'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'scheduled_to' => 'datetime'
    ];

    // can be used to return all desks from an event, or attach/detach desks to events
    public function desks()
    {
        return $this->belongsToMany(Desk::class, 'event_desks')->withTimestamps();
    }

    // can be used to return all users from an event, or attach/detach users to events
    public function users()
    {
        return $this->belongsToMany(User::class, 'event_users')->withTimestamps();
    }

    // returns the creator of an event
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // returns events that are pending based on a query
    public function scopePendingEvents($query)
    {
        return $query->where('status', Event::STATUS_PENDING);
    }
}
