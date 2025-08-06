<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GpClient;
use App\Models\GpNotification;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Находим клиента по ID
        $client = GpClient::find('50a81f62-8d80-4b30-968e-d1e76ecde7d5');

        if (!$client) {
            $this->command->error('Клиент с ID 50a81f62-8d80-4b30-968e-d1e76ecde7d5 не найден!');
            return;
        }

        $this->command->info("Найден клиент: {$client->name} (Телефон: {$client->phone})");

        // Создаем несколько уведомлений для тестирования
        $notifications = [
            [
                'type' => 'system',
                'title' => 'Добро пожаловать!',
                'message' => 'Спасибо за регистрацию в нашем сервисе. Мы рады приветствовать вас!',
                'data' => ['welcome_message' => true, 'timestamp' => now()->toISOString()],
                'is_read' => false
            ],
            [
                'type' => 'order_status',
                'title' => 'Заказ принят',
                'message' => 'Ваш заказ №12345 принят водителем и находится в пути',
                'data' => [
                    'order_number' => '12345',
                    'status' => 'accepted',
                    'status_text' => 'Принят водителем'
                ],
                'is_read' => false
            ],
            [
                'type' => 'system',
                'title' => 'Акция!',
                'message' => 'Скидка 10% на все заказы в течение недели!',
                'data' => ['promo' => true, 'discount' => 10, 'timestamp' => now()->toISOString()],
                'is_read' => true
            ],
            [
                'type' => 'order_status',
                'title' => 'Водитель прибыл',
                'message' => 'Водитель прибыл к месту доставки заказа №12345. Пожалуйста, выйдите для получения',
                'data' => [
                    'order_number' => '12345',
                    'status' => 'waiting_client',
                    'status_text' => 'Ожидает клиента'
                ],
                'is_read' => false
            ],
            [
                'type' => 'system',
                'title' => 'Новое обновление',
                'message' => 'В приложении доступно новое обновление с улучшенным интерфейсом',
                'data' => ['update' => true, 'version' => '2.1.0', 'timestamp' => now()->toISOString()],
                'is_read' => false
            ]
        ];

        $createdCount = 0;
        foreach ($notifications as $notificationData) {
            $notification = GpNotification::create([
                'client_id' => $client->id,
                'order_id' => $notificationData['data']['order_id'] ?? null,
                'type' => $notificationData['type'],
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => $notificationData['data'],
                'is_read' => $notificationData['is_read'],
                'is_sent' => false,
                'fcm_token' => $client->fcm_token ?? null
            ]);

            $createdCount++;
            $this->command->info("Создано уведомление #{$createdCount}: {$notification->title} (ID: {$notification->id})");
        }

        $this->command->info("Всего создано уведомлений: {$createdCount}");

        // Показываем статистику
        $totalNotifications = GpNotification::where('client_id', $client->id)->count();
        $unreadNotifications = GpNotification::where('client_id', $client->id)->where('is_read', false)->count();

        $this->command->info("Статистика для клиента {$client->name}:");
        $this->command->info("- Всего уведомлений: {$totalNotifications}");
        $this->command->info("- Непрочитанных: {$unreadNotifications}");
    }
}
