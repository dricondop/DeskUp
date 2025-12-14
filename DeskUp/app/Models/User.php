<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'assigned_desk_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];
    
    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
    public function deskActivities()
    {
    return $this->hasManyThrough(DeskActivity::class, Desk::class, 'user_id', 'desk_id', 'id', 'id');
    }


    /**
     * Get the desk assigned to the user.
     */
    public function assignedDesk()
    {
        return $this->belongsTo(Desk::class, 'assigned_desk_id');
    }

    /**
     * Get the events created by the user.
     */
    public function eventsCreatedBy()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    // can be used to return all events a user is assigned to, or attach/detach users to events
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_users');
    }

    public function assignedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_users');
    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
