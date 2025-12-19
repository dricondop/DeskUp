<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

class NotificationSettings
{
    private static $settingsFile = 'notification_settings.json';

    /**
     * Get default settings.
     */
    private static function defaults()
    {
        return [
            'automatic_notifications_enabled' => true,
            'sitting_time_threshold_minutes' => 50,
        ];
    }

    /**
     * Get settings from local file.
     */
    public static function get()
    {
        if (!Storage::exists(self::$settingsFile)) {
            self::initializeSettings();
        }

        $settings = json_decode(Storage::get(self::$settingsFile), true);
        
        if (!$settings) {
            self::initializeSettings();
            $settings = self::defaults();
        }

        return (object) $settings;
    }

    /**
     * Update settings in local file.
     */
    public static function update(array $data)
    {
        $currentSettings = (array) self::get();

        if (isset($data['automatic_notifications_enabled'])) {
            $currentSettings['automatic_notifications_enabled'] = (bool) $data['automatic_notifications_enabled'];
        }
        
        if (isset($data['sitting_time_threshold_minutes'])) {
            $currentSettings['sitting_time_threshold_minutes'] = (int) $data['sitting_time_threshold_minutes'];
        }

        Storage::put(self::$settingsFile, json_encode($currentSettings, JSON_PRETTY_PRINT));
    }

    /**
     * Initialize settings file with defaults.
     */
    private static function initializeSettings()
    {
        Storage::put(self::$settingsFile, json_encode(self::defaults(), JSON_PRETTY_PRINT));
    }
}
