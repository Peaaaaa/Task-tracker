<?php

namespace App\Http\Controllers;

use App\Models\TaskNotificationLog;
use Illuminate\Http\Request;

class TaskNotificationController extends Controller
{
    /**
     * Get notification history and unread count.
     */
    public function index(Request $request)
{
    $query = TaskNotificationLog::with('task')
        ->whereNotNull('sent_at');

    // Filter by notification type
    if ($request->filled('type') && in_array($request->type, ['due_today', 'overdue'])) {
        $query->where('notification_type', $request->type);
    }

    // Filter by read status
    if ($request->status === 'unread') {
        $query->whereNull('read_at');
    } elseif ($request->status === 'read') {
        $query->whereNotNull('read_at');
    }

    $notifications = $query
        ->latest('sent_at')
        ->paginate(15);

    $unreadCount = TaskNotificationLog::unread()
        ->whereNotNull('sent_at')
        ->count();

    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);
}

    /**
     * Mark one notification as read.
     */
    public function markAsRead($id)
    {
        $notification = TaskNotificationLog::findOrFail($id);

        if ($notification->sent_at === null) {
            return response()->json([
                'message' => 'Notification has not been sent.',
            ], 404);
        }

        $notification->update([
            'read_at' => $notification->read_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read.',
            'unread_count' => TaskNotificationLog::unread()
                ->whereNotNull('sent_at')
                ->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        TaskNotificationLog::unread()
            ->whereNotNull('sent_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }
}
