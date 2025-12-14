<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'auto_notifications_enabled',
        'sitting_time_threshold',
    ];

    protected $casts = [
        'auto_notifications_enabled' => 'boolean',
        'sitting_time_threshold' => 'integer',
    ];

    /**
     * Get the singleton instance.
     */
    public static function getInstance()
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'auto_notifications_enabled' => true,
                'sitting_time_threshold' => 30,
            ]
        );
    }
}
