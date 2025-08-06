<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpClient;
use App\Models\GpOrder;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTestController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Создать тестовые уведомления для проверки
     */
    public function createTestNotifications(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        try {
            // Получаем первого клиента для тестирования
            $client = GpClient::first();
            if (!$client) {
                return response()->json([
                    'message' => 'No clients found in database',
                    'status' => false,
                ], 404);
            }

            // Получаем первый заказ для тестирования
            $order = GpOrder::first();
            if (!$order) {
                return response()->json([
                    'message' => 'No orders found in database',
                    'status' => false,
                ], 404);
            }

            $results = [];

            // Тестовое уведомление 1: Заказ принят
            $notification1 = $this->notificationService->sendOrderStatusNotification(
                $order,
                'accepted'
            );
            $results[] = [
                'type' => 'accepted',
                'notification_id' => $notification1 ? $notification1->id : null,
                'success' => $notification1 !== false
            ];

            // Тестовое уведомление 2: Водитель прибыл
            $notification2 = $this->notificationService->sendOrderStatusNotification(
                $order,
                'waiting_client'
            );
            $results[] = [
                'type' => 'waiting_client',
                'notification_id' => $notification2 ? $notification2->id : null,
                'success' => $notification2 !== false
            ];

            // Тестовое уведомление 3: Заказ доставлен
            $notification3 = $this->notificationService->sendOrderStatusNotification(
                $order,
                'delivered'
            );
            $results[] = [
                'type' => 'delivered',
                'notification_id' => $notification3 ? $notification3->id : null,
                'success' => $notification3 !== false
            ];

            // Тестовое уведомление 4: Системное уведомление
            $notification4 = $this->notificationService->sendSystemNotification(
                $client,
                'Тестовое системное уведомление',
                'Это тестовое системное уведомление для проверки функциональности',
                [
                    'test_type' => 'system_notification',
                    'timestamp' => now()->toISOString(),
                    'admin_id' => $user->id
                ]
            );
            $results[] = [
                'type' => 'system',
                'notification_id' => $notification4 ? $notification4->id : null,
                'success' => $notification4 !== false
            ];

            return response()->json([
                'message' => 'Test notifications created successfully',
                'status' => true,
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'fcm_token' => $client->fcm_token ? 'present' : 'missing'
                ],
                'order' => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'sum' => $order->sum,
                    'delivery_price' => $order->delivery_price
                ],
                'notifications' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating test notifications: ' . $e->getMessage(),
                'status' => false,
            ], 500);
        }
    }

    /**
     * Создать тестовое уведомление с конкретными параметрами
     */
    public function createCustomTestNotification(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $validated = $request->validate([
            'client_id' => 'required|string|exists:gp_clients,id',
            'order_id' => 'required|string|exists:gp_orders,id',
            'status' => 'required|string|in:accepted,waiting_client,delivered,client_no_show,cancelled_by_client,cancelled_by_operator,error'
        ]);

        try {
            $client = GpClient::find($validated['client_id']);
            $order = GpOrder::find($validated['order_id']);

            $notification = $this->notificationService->sendOrderStatusNotification(
                $order,
                $validated['status']
            );

            return response()->json([
                'message' => 'Custom test notification created successfully',
                'status' => true,
                'notification' => [
                    'id' => $notification ? $notification->id : null,
                    'success' => $notification !== false,
                    'data' => $notification ? $notification->data : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating custom test notification: ' . $e->getMessage(),
                'status' => false,
            ], 500);
        }
    }

    /**
     * Получить список всех уведомлений клиента
     */
    public function getClientNotifications(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $validated = $request->validate([
            'client_id' => 'required|string|exists:gp_clients,id'
        ]);

        try {
            $notifications = \App\Models\GpNotification::where('client_id', $validated['client_id'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Client notifications retrieved successfully',
                'status' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving client notifications: ' . $e->getMessage(),
                'status' => false,
            ], 500);
        }
    }
}
