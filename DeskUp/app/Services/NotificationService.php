<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationSettings;
use App\Models\User;
use App\Models\DeskActivity;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send a manual notification to specific users or all users.
     */
    public function sendManualNotification(string $title, string $message, ?array $userIds = null): int
    {
        $users = $userIds ? User::whereIn('id', $userIds)->get() : User::all();
        $count = 0;

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => 'manual',
            ]);
            $count++;
        }

        Log::info('Manual notifications sent', [
            'count' => $count,
            'title' => $title,
            'userIds' => $userIds,
        ]);

        return $count;
    }

    /**
     * Check users' sitting time and send automatic notifications.
     */
    public function checkAndSendAutoNotifications(): int
    {
        $settings = NotificationSettings::getInstance();

        if (!$settings->auto_notifications_enabled) {
            return 0;
        }

        $thresholdMinutes = $settings->sitting_time_threshold;
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            if ($this->shouldSendNotification($user, $thresholdMinutes)) {
                $this->sendAutoNotification($user, $thresholdMinutes);
                $count++;
            }
        }

        if ($count > 0) {
            Log::info('Auto notifications sent', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Check if user should receive a notification based on sitting time.
     */
    protected function shouldSendNotification(User $user, int $thresholdMinutes): bool
    {
        if (!$user->assigned_desk_id) {
            return false;
        }

        // Get the most recent desk activity
        $recentActivity = DeskActivity::where('desk_id', $user->assigned_desk_id)
            ->where('created_at', '>=', Carbon::now()->subHours(2))
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$recentActivity) {
            return false;
        }

        // Check if user is currently sitting
        if ($recentActivity->height > 80) { // Assuming >80cm is standing
            return false;
        }

        // Calculate sitting duration
        $sittingStart = DeskActivity::where('desk_id', $user->assigned_desk_id)
            ->where('height', '<=', 80)
            ->where('created_at', '<=', $recentActivity->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$sittingStart) {
            return false;
        }

        $sittingMinutes = Carbon::parse($sittingStart->created_at)->diffInMinutes(now());

        // Check if we already sent a notification recently
        $lastNotification = Notification::where('user_id', $user->id)
            ->where('type', 'automatic')
            ->where('created_at', '>=', Carbon::now()->subMinutes($thresholdMinutes))
            ->latest()
            ->first();

        if ($lastNotification) {
            return false;
        }

        return $sittingMinutes >= $thresholdMinutes;
    }

    /**
     * Send an automatic notification to a user.
     */
    protected function sendAutoNotification(User $user, int $minutes): void
    {
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Time to Stand Up! 🧍',
            'message' => "You've been sitting for {$minutes} minutes. Take a 5-minute standing break to improve your posture and circulation.",
            'type' => 'automatic',
        ]);
    }

    /**
     * Get notifications for a user.
     */
    public function getUserNotifications(int $userId, int $limit = 50)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(array $notificationIds): void
    {
        Notification::whereIn('id', $notificationIds)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get current notification settings.
     */
    public function getSettings(): NotificationSettings
    {
        return NotificationSettings::getInstance();
    }

    /**
     * Update notification settings.
     */
    public function updateSettings(array $data): NotificationSettings
    {
        $settings = NotificationSettings::getInstance();
        $settings->update($data);

        Log::info('Notification settings updated', $data);

        return $settings;
    }
}
