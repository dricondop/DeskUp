<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display the admin notification management view.
     */
    public function adminIndex()
    {
        $settings = $this->notificationService->getSettings();
        $users = User::where('is_admin', false)->get(['id', 'name', 'email']);

        return view('admin-notifications', compact('settings', 'users'));
    }

    /**
     * Send manual notification to users.
     */
    public function sendManual(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'send_to_all' => 'boolean',
        ]);

        $userIds = $validated['send_to_all'] ?? false 
            ? null 
            : ($validated['user_ids'] ?? null);

        $count = $this->notificationService->sendManualNotification(
            $validated['title'],
            $validated['message'],
            $userIds
        );

        return response()->json([
            'success' => true,
            'message' => "Notification sent to {$count} user(s)",
            'count' => $count,
        ]);
    }

    /**
     * Update notification settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_notifications_enabled' => 'required|boolean',
            'sitting_time_threshold' => 'required|integer|min:5|max:120',
        ]);

        $settings = $this->notificationService->updateSettings($validated);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'settings' => $settings,
        ]);
    }

    /**
     * Get user's notifications.
     */
    public function getUserNotifications(Request $request)
    {
        $userId = Auth::id();
        $notifications = $this->notificationService->getUserNotifications($userId);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($userId),
        ]);
    }

    /**
     * Get pending notifications for current user (for popup display).
     */
    public function getPending()
    {
        $userId = Auth::id();
        $notifications = $this->notificationService->getUserNotifications($userId, 5);
        
        return response()->json($notifications);
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        $this->notificationService->markAsRead($validated['notification_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read',
        ]);
    }
}
