<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserStatsHistory;

class NotificationService
{
    /**
     * Send manual notification to specific users or all users.
     */
    public function sendManualNotification(string $title, string $message, ?array $userIds = null): int
    {
        $query = User::where('is_admin', false);
        
        if ($userIds !== null) {
            $query->whereIn('id', $userIds);
        }
        
        $users = $query->get();
        
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => 'manual',
                'is_read' => false,
                'sent_at' => now(),
            ]);
        }

        return $users->count();
    }

    /**
     * Check sitting time and send notifications.
     */
    public function checkAndSendAutoNotifications(): int
    {
        if (!config('notifications.automatic_notifications_enabled')) {
            return 0;
        }

        $thresholdMinutes = $settings->sitting_time_threshold;
        $users = User::all();
        $count = 0;

        $thresholdMinutes = config('notifications.sitting_time_threshold_minutes');
        $users = User::where('is_admin', false)->whereNotNull('assigned_desk_id')->get();
        $count = 0;

        foreach ($users as $user) {
            if ($this->shouldSendNotification($user, $thresholdMinutes)) {
                $this->sendAutoNotification($user);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if user should receive notification based on sitting time from user_stats_history.
     */
    protected function shouldSendNotification(User $user, int $thresholdMinutes): bool
    {
        // Sitting threshold: 750mm (75cm) - desks below this are considered sitting
        $sitThresholdMm = 750;
        
        // Get recent stats for this user ordered by time
        $recentStats = \App\Models\UserStatsHistory::where('user_id', $user->id)
            ->where('desk_id', $user->assignedDesk->desk_number ?? 0)
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        if ($recentStats->isEmpty()) return false;

        // Check if user is currently sitting
        $latestStat = $recentStats->first();
        if ($latestStat->desk_height_mm >= $sitThresholdMm) {
            return false; // User is standing, no notification needed
        }

        // Calculate continuous sitting duration from stored data
        $sittingStartTime = null;
        
        foreach ($recentStats->reverse() as $stat) {
            if ($stat->desk_height_mm < $sitThresholdMm) {
                // User is sitting
                if ($sittingStartTime === null) {
                    $sittingStartTime = $stat->recorded_at;
                }
            } else {
                // User stood up, reset the sitting session
                $sittingStartTime = null;
            }
        }

        // If no continuous sitting session found, return false
        if ($sittingStartTime === null) return false;

        // Calculate sitting duration in minutes
        $minutesSitting = $sittingStartTime->diffInMinutes(now());
        
        if ($minutesSitting < $thresholdMinutes) return false;

        // Check cooldown period to avoid spamming notifications
        $cooldown = config('notifications.notification_cooldown_minutes');
        $hasRecent = Notification::where('user_id', $user->id)
            ->where('type', 'automatic')
            ->where('sent_at', '>', now()->subMinutes($cooldown))
            ->exists();

        return !$hasRecent;
    }

    /**
     * Send automatic notification.
     */
    protected function sendAutoNotification(User $user): void
    {
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Time to Stand Up!',
            'message' => "Take a 5-minute break to stretch and improve your posture.",
            'type' => 'automatic',
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }

    /**
     * Get user notifications.
     */
    public function getUserNotifications(int $userId, int $limit = 50)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread notifications.
     */
    public function getUnreadNotifications(User $user)
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->orderBy('sent_at', 'desc')
            ->get();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(int $notificationId): void
    {
        Notification::where('id', $notificationId)->update(['is_read' => true]);
    }
}
