<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic Notifications
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic notifications for users when they have been
    | sitting for too long.
    |
    */

    'automatic_notifications_enabled' => env('NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Sitting Time Threshold
    |--------------------------------------------------------------------------
    |
    | The number of minutes a user can sit continuously before receiving
    | an automatic notification to stand up.
    |
    */

    'sitting_time_threshold_minutes' => env('SITTING_THRESHOLD_MINUTES', 50),

];
