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
        return view('admin.notifications', compact('settings'));
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
        ]);

        $count = $this->notificationService->sendManualNotification(
            $validated['title'],
            $validated['message']
        );

        return response()->json(['success' => true, 'message' => "Sent to {$count} users"]);
    }

    public function getUnread()
    {
        $notifications = $this->notificationService->getUnreadNotifications(Auth::user());
        return response()->json(['notifications' => $notifications]);
    }

    public function markAsRead($id)
    {
        $this->notificationService->markAsRead($id);
        return response()->json(['success' => true]);
    }
}

