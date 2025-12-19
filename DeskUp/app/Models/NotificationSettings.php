<?php

namespace App\Models;

class NotificationSettings
{
    /**
     * Get settings from config file.
     */
    public static function get()
    {
        return (object) [
            'automatic_notifications_enabled' => config('notifications.automatic_notifications_enabled', true),
            'sitting_time_threshold_minutes' => config('notifications.sitting_time_threshold_minutes', 50),
        ];
    }

    /**
     * Update settings in config cache.
     */
    public static function update(array $data)
    {
        if (isset($data['automatic_notifications_enabled'])) {
            config(['notifications.automatic_notifications_enabled' => $data['automatic_notifications_enabled']]);
        }
        
        if (isset($data['sitting_time_threshold_minutes'])) {
            config(['notifications.sitting_time_threshold_minutes' => $data['sitting_time_threshold_minutes']]);
        }
    }
}
