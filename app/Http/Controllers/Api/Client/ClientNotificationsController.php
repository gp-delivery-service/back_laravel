<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\GpNotification;
use App\Models\GpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientNotificationsController extends Controller
{
    /**
     * Получение списка уведомлений клиента
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $unreadOnly = $request->input('unread_only', false);

        $query = GpNotification::where('client_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'items' => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'next_page' => $notifications->nextPageUrl(),
            'last_page' => $notifications->lastPage(),
            'total' => $notifications->total(),
            'unread_count' => GpNotification::where('client_id', $user->id)
                ->where('is_read', false)
                ->count()
        ]);
    }

    /**
     * Получение одного уведомления
     */
    public function show($id)
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notification = GpNotification::where('id', $id)
            ->where('client_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        return response()->json($notification);
    }

    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead($id)
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notification = GpNotification::where('id', $id)
            ->where('client_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Отметить все уведомления как прочитанные
     */
    public function markAllAsRead()
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        GpNotification::where('client_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }



    /**
     * Получение количества непрочитанных уведомлений
     */
    public function unreadCount()
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $count = GpNotification::where('client_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }
}
