<?php

namespace App\Models;

class NotificationSettings
{
    private static $settingsFile = 'notification-settings.json';

    /**
     * Get settings from JSON file or config defaults.
     */
    public static function get()
    {
        $filePath = public_path(self::$settingsFile);
        
        // Try to read from JSON file first
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $data = json_decode($json, true);
            
            if ($data !== null) {
                return (object) [
                    'automatic_notifications_enabled' => $data['automatic_notifications_enabled'] ?? true,
                    'sitting_time_threshold_minutes' => $data['sitting_time_threshold_minutes'] ?? 50,
                ];
            }
        }
        
        // Fall back to config defaults
        return (object) [
            'automatic_notifications_enabled' => config('notifications.automatic_notifications_enabled', true),
            'sitting_time_threshold_minutes' => config('notifications.sitting_time_threshold_minutes', 50),
        ];
    }

    /**
     * Update settings and save to JSON file.
     */
    public static function update(array $data)
    {
        $filePath = public_path(self::$settingsFile);
        
        // Get current settings
        $current = (array) self::get();
        
        // Merge with new data
        $settings = [
            'automatic_notifications_enabled' => $data['automatic_notifications_enabled'] ?? $current['automatic_notifications_enabled'],
            'sitting_time_threshold_minutes' => $data['sitting_time_threshold_minutes'] ?? $current['sitting_time_threshold_minutes'],
            'updated_at' => now()->toDateTimeString(),
        ];
        
        // Save to JSON file
        $json = json_encode($settings, JSON_PRETTY_PRINT);
        file_put_contents($filePath, $json);
        
        return true;
    }
}
