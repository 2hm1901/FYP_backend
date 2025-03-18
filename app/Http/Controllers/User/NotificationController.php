<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $userId = $request->user_id;
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ], 200);
    }
    public function markNotificationAsRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|exists:notifications,id',
        ]);

        $notification = Notification::find($request->notification_id);
        if ($notification && $notification->user_id === $request->user()->id) {
            $notification->update(['is_read' => true]);
            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized or not found'], 403);
    }
}
