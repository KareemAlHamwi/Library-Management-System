<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**

     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must loggin first'
            ], 401);
        }

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'type' => $notification->type,
                ];
            }),
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $notifications->count(),
        ]);
    }

    /**

     * @return JsonResponse
     */
    public function unread(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must loggin first'
            ], 401);
        }

        $notifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                    'type' => $notification->type,
                ];
            }),
            'count' => $notifications->count(),
        ]);
    }
    /**

     * @param string $notificationId
     * @return JsonResponse
     */
    public function markAsRead(string $notificationId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must loggin first'
            ], 401);
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return response()->json([
                'message' => 'There is no notifications'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'The notification has been marked as read.',
            'notification_id' => $notificationId,
        ]);
    }

    /**

     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must loggin first'
            ], 401);
        }


        $unreadCount = $user->unreadNotifications()->count();


        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All Notifications have been marked as read',
            'marked_count' => $unreadCount,
        ]);
    }
    /**

     * @param string $notificationId
     * @return JsonResponse
     */
    public function delete(string $notificationId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must loggin first'
            ], 401);
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return response()->json([
                'message' => 'therr are no notifications'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification has been deleted',
            'notification_id' => $notificationId,
        ]);
    }
    /**

     * @return JsonResponse
     */
    public function deleteRead(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'You must loggin first'
            ], 401);
        }

        $deletedCount = $user->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'message' => 'All Notifications have been marked as read',
            'deleted_count' => $deletedCount,
        ]);
    }
}
