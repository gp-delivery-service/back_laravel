<?php

namespace Database\Seeders;

use App\Models\GpClient;
use App\Models\GpCompany;
use App\Models\GpOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GpOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем или создаем тестовых клиентов
        $clients = GpClient::all();
        if ($clients->isEmpty()) {
            $clients = collect([
                GpClient::create([
                    'id' => Str::uuid(),
                    'name' => 'Иван Петров',
                    'phone' => '+99312345678',
                    'wallet' => 5000,
                    'fcm_token' => 'test_fcm_token_ivan'
                ]),
                GpClient::create([
                    'id' => Str::uuid(),
                    'name' => 'Мария Сидорова',
                    'phone' => '+99387654321',
                    'wallet' => 3000,
                    'fcm_token' => 'test_fcm_token_maria'
                ]),
                GpClient::create([
                    'id' => Str::uuid(),
                    'name' => 'Алексей Козлов',
                    'phone' => '+99355555555',
                    'wallet' => 7500,
                    'fcm_token' => 'test_fcm_token_alex'
                ])
            ]);
        }

        // Получаем или создаем тестовые компании
        $companies = GpCompany::all();
        if ($companies->isEmpty()) {
            $companies = collect([
                GpCompany::create([
                    'id' => Str::uuid(),
                    'name' => 'Ресторан "У Повара"',
                    'address' => 'ул. Туркменбаши, 15'
                ]),
                GpCompany::create([
                    'id' => Str::uuid(),
                    'name' => 'Кафе "Солнышко"',
                    'address' => 'пр. Махтумкули, 25'
                ]),
                GpCompany::create([
                    'id' => Str::uuid(),
                    'name' => 'Пиццерия "Италия"',
                    'address' => 'ул. Азади, 8'
                ])
            ]);
        }

        // Статусы заказов
        $statuses = ['pending', 'accepted', 'in_progress', 'waiting_client', 'closed', 'cancelled'];

        // Типы оплаты доставки
        $deliveryPays = ['balance', 'cash'];

        // Создаем 50 тестовых заказов
        for ($i = 1; $i <= 50; $i++) {
            $client = $clients->random();
            $company = $companies->random();
            $deliveryPay = $deliveryPays[array_rand($deliveryPays)];

            // Генерируем случайную сумму заказа (от 500 до 5000)
            $orderSum = rand(500, 5000);
            $deliveryPrice = rand(200, 800);

            // Создаем заказ
            GpOrder::create([
                'company_id' => $company->id,
                'number' => 'ORDER-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'client_phone' => $client->phone,
                'sum' => $orderSum,
                'delivery_price' => $deliveryPrice,
                'delivery_pay' => $deliveryPay,
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                'updated_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59))
            ]);
        }

        $this->command->info('✅ Создано 50 тестовых заказов');
        $this->command->info('📊 Статистика:');
        $this->command->info('   - Клиентов: ' . $clients->count());
        $this->command->info('   - Компаний: ' . $companies->count());
        $this->command->info('   - Заказов: ' . GpOrder::count());
    }
}
