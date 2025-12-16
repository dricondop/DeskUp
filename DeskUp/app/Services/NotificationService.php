<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\DeskActivity;

class NotificationService
{
    /**
     * Send manual notification to all users.
     */
    public function sendManualNotification(string $title, string $message): int
    {
        $users = User::where('is_admin', false)->get();
        
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
     * Check if user should receive notification.
     */
    protected function shouldSendNotification(User $user, int $thresholdMinutes): bool
    {
        $latestActivity = DeskActivity::where('desk_id', $user->assigned_desk_id)
            ->where('status', 'sitting')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestActivity) return false;

        $minutesSitting = $latestActivity->created_at->diffInMinutes(now());
        if ($minutesSitting < $thresholdMinutes) return false;

        $cooldown = config('notifications.notification_cooldown_minutes');
        $hasRecent = Notification::where('user_id', $user->id)
            ->where('type', 'sitting_reminder')
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
            'type' => 'sitting_reminder',
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
