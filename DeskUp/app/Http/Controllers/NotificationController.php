<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use App\Models\NotificationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function adminIndex()
    {
        $settings = NotificationSettings::get();
        $users = \App\Models\User::where('is_admin', false)->get(['id', 'name', 'email']);
        return view('admin.notifications', compact('settings', 'users'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'automatic_notifications_enabled' => 'required|boolean',
            'sitting_time_threshold_minutes' => 'required|integer|min:1|max:300',
        ]);

        NotificationSettings::update($validated);

        return response()->json(['success' => true, 'message' => 'Settings updated']);
    }

    public function sendManual(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'send_to_all' => 'boolean',
        ]);

        $userIds = ($validated['send_to_all'] ?? false) ? null : ($validated['user_ids'] ?? null);

        $count = $this->notificationService->sendManualNotification(
            $validated['title'],
            $validated['message'],
            $userIds
        );

        return response()->json(['success' => true, 'message' => "Sent to {$count} users"]);
    }

    public function getUnread()
    {
        $notifications = $this->notificationService->getUnreadNotifications(Auth::user());
        return response()->json(['notifications' => $notifications]);
    }

    public function getUserNotifications(Request $request)
    {
        $limit = $request->query('limit', 50);
        $notifications = $this->notificationService->getUserNotifications(Auth::id(), $limit);
        return response()->json(['notifications' => $notifications]);
    }

    public function getPending()
    {
        $notifications = $this->notificationService->getUnreadNotifications(Auth::user());
        return response()->json(['notifications' => $notifications]);
    }

    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'sometimes|integer|exists:notifications,id',
            'notification_ids' => 'sometimes|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);
        
        if (isset($validated['notification_ids'])) {
            foreach ($validated['notification_ids'] as $id) {
                $this->notificationService->markAsRead($id, Auth::id());
            }
        } elseif (isset($validated['notification_id'])) {
            $this->notificationService->markAsRead($validated['notification_id'], Auth::id());
        }
        
        return response()->json(['success' => true]);
    }
}

