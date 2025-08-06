<?php

namespace App\Services;

use App\Models\GpNotification;
use App\Models\GpClient;
use App\Models\GpOrder;
use App\Services\NodeService;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $nodeService;

    public function __construct(NodeService $nodeService)
    {
        $this->nodeService = $nodeService;
    }

    /**
     * Отправка уведомления о статусе заказа
     */
    public function sendOrderStatusNotification(GpOrder $order, string $status, string $title = null, string $message = null)
    {
        try {
            // Получаем клиента по номеру телефона
            $client = GpClient::where('phone', $order->client_phone)->first();

            if (!$client) {
                Log::warning("Client not found for order {$order->id} with phone {$order->client_phone}");
                return false;
            }

            // Формируем заголовок и сообщение если не переданы
            if (!$title) {
                $title = $this->getOrderStatusTitle($status);
            }

            if (!$message) {
                $message = $this->getOrderStatusMessage($order, $status);
            }

            // Создаем запись уведомления в БД
            $notification = GpNotification::create([
                'client_id' => $client->id,
                'order_id' => $order->id,
                'type' => 'order_status',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->number,
                    'status' => $status,
                    'status_text' => $this->getStatusText($status)
                ],
                'fcm_token' => $client->fcm_token ?? null
            ]);

            // Отправляем уведомление через NodeService
            if ($client->fcm_token) {
                $this->nodeService->sendPushNotification(
                    $client->fcm_token,
                    $title,
                    $message,
                    [
                        'type' => 'order_status',
                        'order_id' => $order->id,
                        'status' => $status
                    ]
                );

                $notification->markAsSent();
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error("Error sending notification for order {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправка системного уведомления
     */
    public function sendSystemNotification(GpClient $client, string $title, string $message, array $data = [])
    {
        try {
            // Создаем запись уведомления в БД
            $notification = GpNotification::create([
                'client_id' => $client->id,
                'type' => 'system',
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'fcm_token' => $client->fcm_token ?? null
            ]);

            // Отправляем уведомление через NodeService
            if ($client->fcm_token) {
                $this->nodeService->sendPushNotification(
                    $client->fcm_token,
                    $title,
                    $message,
                    array_merge(['type' => 'system'], $data)
                );

                $notification->markAsSent();
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error("Error sending system notification for client {$client->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение заголовка для статуса заказа
     */
    private function getOrderStatusTitle(string $status): string
    {
        $titles = [
            'accepted' => 'Заказ принят',
            'waiting_client' => 'Водитель прибыл',
            'delivered' => 'Заказ доставлен',
            'client_no_show' => 'Клиент не вышел',
            'cancelled_by_client' => 'Заказ отменен',
            'cancelled_by_operator' => 'Заказ отменен оператором',
            'error' => 'Ошибка с заказом'
        ];

        return $titles[$status] ?? 'Обновление статуса заказа';
    }

    /**
     * Получение сообщения для статуса заказа
     */
    private function getOrderStatusMessage(GpOrder $order, string $status): string
    {
        $messages = [
            'accepted' => "Ваш заказ №{$order->number} принят водителем и находится в пути",
            'waiting_client' => "Водитель прибыл к месту доставки заказа №{$order->number}. Пожалуйста, выйдите для получения",
            'delivered' => "Ваш заказ №{$order->number} успешно доставлен. Спасибо за использование нашего сервиса!",
            'client_no_show' => "Клиент не вышел для получения заказа №{$order->number}",
            'cancelled_by_client' => "Заказ №{$order->number} отменен по вашей просьбе",
            'cancelled_by_operator' => "Заказ №{$order->number} отменен оператором",
            'error' => "Произошла ошибка с заказом №{$order->number}. Обратитесь в службу поддержки"
        ];

        return $messages[$status] ?? "Статус заказа №{$order->number} изменен на: {$this->getStatusText($status)}";
    }

    /**
     * Получение текста статуса
     */
    private function getStatusText(string $status): string
    {
        $statuses = [
            'inherited' => 'Наследуется',
            'accepted' => 'Принят водителем',
            'waiting_client' => 'Ожидает клиента',
            'delivered' => 'Доставлен',
            'client_no_show' => 'Клиент не вышел',
            'cancelled_by_client' => 'Отменен клиентом',
            'cancelled_by_operator' => 'Отменен оператором',
            'error' => 'Ошибка'
        ];

        return $statuses[$status] ?? $status;
    }
}
