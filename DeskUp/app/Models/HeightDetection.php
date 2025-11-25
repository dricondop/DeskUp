<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeightDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'detected_height', 
        'recommended_height',
        'posture_data',
        'image_path'
    ];

    protected $casts = [
        'posture_data' => 'array',
        'detected_height' => 'decimal:2',
        'recommended_height' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userProfile()
    {
        return $this->hasOneThrough(
            UserProfile::class,
            User::class,
            'id',
            'user_id', 
            'user_id',
            'id'
        );
    }
    
    /**
     * Get current ideal_height
     */
    public function getCurrentIdealHeightAttribute()
    {
        return $this->user->profile->ideal_height ?? null;
    }
}