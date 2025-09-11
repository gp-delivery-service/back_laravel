<?php

namespace App\Console\Commands;

use App\Constants\GpPickupStatus;
use App\Models\GpPickup;
use App\Services\NodeService;
use Illuminate\Console\Command;

class SyncActivePickupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickups:sync-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизирует активные заказы с Node.js сервисом';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начинаем синхронизацию активных заказов...');

        try {
            // Получаем активные заказы с назначенными водителями
            $activePickups = GpPickup::whereNotNull('driver_id')
                ->whereIn('status', [
                    GpPickupStatus::DRIVER_FOUND->value,
                    GpPickupStatus::PICKED_UP->value,
                    GpPickupStatus::IN_PROGRESS->value,
                    GpPickupStatus::WAITING_CONFIRMATION->value
                ])
                ->where('archived', false)
                ->get();

            $pickupsData = $activePickups->map(function ($pickup) {
                return [
                    'id' => $pickup->id,
                    'manager_id' => $pickup->company_id, // Используем company_id как manager_id
                    'driver_id' => $pickup->driver_id,
                    'status' => $pickup->status->value
                ];
            })->toArray();

            $this->info("Найдено {$activePickups->count()} активных заказов");

            if (!empty($pickupsData)) {
                $result = NodeService::syncActivePickups($pickupsData);
                
                if ($result['status'] === 'ok') {
                    $this->info('✅ Синхронизация завершена успешно');
                    $this->info("Синхронизировано заказов: " . count($pickupsData));
                } else {
                    $this->error('❌ Ошибка синхронизации: ' . ($result['message'] ?? 'Неизвестная ошибка'));
                    return 1;
                }
            } else {
                $this->info('Нет активных заказов для синхронизации');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при синхронизации: ' . $e->getMessage());
            return 1;
        }
    }
}
