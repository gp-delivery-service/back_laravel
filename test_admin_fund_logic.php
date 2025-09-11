<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\GpAdmin;
use App\Models\GpCompany;
use App\Models\GpDriver;
use App\Models\GpOperator;
use App\Models\GpPickup;
use App\Models\GpOrder;
use App\Repositories\Balance\AdminFundRepository;
use App\Repositories\Balance\CompanyBalanceRepository;
use App\Repositories\Balance\DriverBalanceRepository;
use App\Repositories\Balance\OperatorBalanceRepository;
use App\Repositories\Balance\DriverTransactionsRepository;
use App\Services\NodeService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class AdminFundTest
{
    private $admin;
    private $company;
    private $driver;
    private $operator;
    private $adminFundRepo;
    private $companyBalanceRepo;
    private $driverBalanceRepo;
    private $operatorBalanceRepo;
    private $driverTransactionsRepo;

    public function __construct()
    {
        // Инициализация Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        // Получаем первые записи
        $this->admin = GpAdmin::first();
        $this->company = GpCompany::first();
        $this->driver = GpDriver::first();
        $this->operator = GpOperator::first();

        if (!$this->admin || !$this->company || !$this->driver || !$this->operator) {
            throw new Exception("Не найдены необходимые записи в базе данных");
        }

        // Инициализируем репозитории
        $this->adminFundRepo = new AdminFundRepository();
        $this->companyBalanceRepo = new CompanyBalanceRepository();
        $this->driverBalanceRepo = new DriverBalanceRepository();
        $this->operatorBalanceRepo = new OperatorBalanceRepository();
        $this->driverTransactionsRepo = new DriverTransactionsRepository($this->driverBalanceRepo);
    }

    public function run()
    {
        echo "=== ТЕСТ ЛОГИКИ ФОНДА АДМИНА ===\n\n";

        try {
            $this->step1_resetAllValues();
            // $this->step2_topUpAdminFund();
            // $this->step3_topUpOperatorCash();
            // $this->step4_adminTopUpCompanyCredit();
            // $this->step5_operatorTopUpCompanyCredit();
            // $this->step6_topUpCompanyBalance();
            // $this->step7_createPickupsWithOrders();
            // $this->step8_driverAcceptsPickups();
            // $this->step9_driverClosesOrders();
            // $this->step10_driverClosesPickups();
            // $this->step11_driverClosesCash();
            // $this->step12_operatorClosesCash();

            echo "\n=== ТЕСТ УСПЕШНО ЗАВЕРШЕН ===\n";
        } catch (Exception $e) {
            echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n";
            echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }

    private function step1_resetAllValues()
    {
        echo "1️⃣ СБРОС ВСЕХ ЗНАЧЕНИЙ\n";
        echo "------------------------\n";

        // Очищаем связи между вызовами и заказами (сначала, так как они ссылаются на основные таблицы)
        DB::table('gp_pickup_order_logs')->delete();
        DB::table('gp_pickup_orders')->delete();
        
        // Очищаем вызовы и заказы
        DB::table('gp_pickups')->delete();
        DB::table('gp_orders')->delete();

        // Очищаем логи
        DB::table('gp_admin_fund_logs')->delete();
        DB::table('gp_company_balance_logs')->delete();
        DB::table('gp_driver_balance_logs')->delete();
        DB::table('gp_operator_balance_logs')->delete();

        // Сбрасываем значения
        $this->admin->update([
            'fund' => 0,
            'fund_dynamic' => 0,
            'total_earn' => 0
        ]);

        $this->company->update([
            'credit_balance' => 0,
            'balance' => 0,
            'agregator_side_balance' => 0
        ]);

        $this->driver->update([
            'cash_service' => 0,
            'earning' => 0,
            'earning_pending' => 0,
            'cash_client' => 0,
            'cash_goods' => 0,
            'cash_company_balance' => 0,
            'cash_wallet' => 0
        ]);

        $this->operator->update([
            'cash' => 0
        ]);

        echo "✅ Все значения сброшены\n";
        echo "   - Фонд админа fund=0, fund_dynamic=0, total_earn=0\n";
        echo "   - Компания credit_balance=0, balance=0, agregator_side_balance=0\n";
        echo "   - Водитель cash_service=0, earning_pending=0\n";
        echo "   - Оператор cash=0\n";
        echo "   - Вызовы и заказы очищены\n";
        echo "   - Связи между вызовами и заказами очищены\n";
        echo "   - Логи очищены\n\n";
    }

    private function step2_topUpAdminFund()
    {
        echo "2️⃣ ПОПОЛНЕНИЕ ФОНДА АДМИНА\n";
        echo "---------------------------\n";

        $amount = 10000;
        $result = $this->adminFundRepo->topUpFund($amount, 'test_admin_top_up_fund');

        $this->admin->refresh();
        
        echo "💰 Пополнение фонда на {$amount} TMT\n";
        echo "   Ожидаем fund={$amount}, fund_dynamic={$amount}\n";
        echo "   Факт fund={$this->admin->fund}, fund_dynamic={$this->admin->fund_dynamic}\n";
        
        if ($this->admin->fund == $amount && $this->admin->fund_dynamic == $amount) {
            echo "   ✅ УСПЕШНО\n";
        } else {
            echo "   ❌ ОШИБКА\n";
        }
        echo "\n";
    }

    private function step3_topUpOperatorCash()
    {
        echo "3️⃣ ПОПОЛНЕНИЕ КАССЫ ОПЕРАТОРА\n";
        echo "-------------------------------\n";

        $amount = 2000;
        $oldFundDynamic = $this->admin->fund_dynamic;
        $oldOperatorCash = $this->operator->cash;

        $result = $this->adminFundRepo->addCashToOperator($this->operator->id, $amount, 'test_admin_add_cash');

        $this->admin->refresh();
        $this->operator->refresh();

        echo "💰 Пополнение кассы оператора на {$amount} TMT\n";
        echo "   Ожидаем fund_dynamic↓{$amount}, operator_cash↑{$amount}\n";
        echo "   Факт fund_dynamic={$this->admin->fund_dynamic} (было {$oldFundDynamic}), operator_cash={$this->operator->cash} (было {$oldOperatorCash})\n";
        
        if ($this->admin->fund_dynamic == $oldFundDynamic - $amount && $this->operator->cash == $oldOperatorCash + $amount) {
            echo "   ✅ УСПЕШНО\n";
        } else {
            echo "   ❌ ОШИБКА\n";
        }
        echo "\n";
    }

    private function step4_adminTopUpCompanyCredit()
    {
        echo "4️⃣ АДМИН ПОПОЛНЯЕТ КРЕДИТ КОМПАНИИ\n";
        echo "-----------------------------------\n";

        $amount = 3000;
        $oldFundDynamic = $this->admin->fund_dynamic;
        $oldCreditBalance = $this->company->credit_balance;

        $result = $this->companyBalanceRepo->addCreditBalance($this->company->id, $amount, 'test_credit_balance_increase');

        $this->admin->refresh();
        $this->company->refresh();

        echo "💰 Админ пополняет кредит компании на {$amount} TMT\n";
        echo "   Ожидаем fund_dynamic↓{$amount}, credit_balance↑{$amount}\n";
        echo "   Факт fund_dynamic={$this->admin->fund_dynamic} (было {$oldFundDynamic}), credit_balance={$this->company->credit_balance} (было {$oldCreditBalance})\n";
        
        if ($this->admin->fund_dynamic == $oldFundDynamic - $amount && $this->company->credit_balance == $oldCreditBalance + $amount) {
            echo "   ✅ УСПЕШНО\n";
        } else {
            echo "   ❌ ОШИБКА\n";
        }
        echo "\n";
    }

    private function step5_operatorTopUpCompanyCredit()
    {
        echo "5️⃣ ОПЕРАТОР ПОПОЛНЯЕТ КРЕДИТ КОМПАНИИ\n";
        echo "---------------------------------------\n";

        $amount = 1500;
        $oldFundDynamic = $this->admin->fund_dynamic;
        $oldCreditBalance = $this->company->credit_balance;
        $oldOperatorCash = $this->operator->cash;

        $result = $this->companyBalanceRepo->addCreditBalanceByOperator($this->company->id, $amount, $this->operator->id, 'test_operator_credit_balance_increase');

        $this->admin->refresh();
        $this->company->refresh();
        $this->operator->refresh();

        echo "💰 Оператор пополняет кредит компании на {$amount} TMT\n";
        echo "   Ожидаем fund_dynamic не изменяется, credit_balance↑{$amount}, operator_cash↓{$amount}\n";
        echo "   Факт fund_dynamic={$this->admin->fund_dynamic} (было {$oldFundDynamic}), credit_balance={$this->company->credit_balance} (было {$oldCreditBalance}), operator_cash={$this->operator->cash} (было {$oldOperatorCash})\n";
        
        if ($this->admin->fund_dynamic == $oldFundDynamic && $this->company->credit_balance == $oldCreditBalance + $amount && $this->operator->cash == $oldOperatorCash - $amount) {
            echo "   ✅ УСПЕШНО\n";
        } else {
            echo "   ❌ ОШИБКА\n";
        }
        echo "\n";
    }

    private function step6_topUpCompanyBalance()
    {
        echo "6️⃣ ПОПОЛНЕНИЕ БАЛАНСА КОМПАНИИ\n";
        echo "--------------------------------\n";

        $amount = 5000;
        $oldBalance = $this->company->balance;

        // Используем метод пополнения баланса компании
        $this->companyBalanceRepo->addBalance($this->company->id, $amount, 'test_balance_increase');

        $this->company->refresh();

        echo "💰 Пополнение баланса компании на {$amount} TMT\n";
        echo "   Ожидаем balance↑{$amount}\n";
        echo "   Факт balance={$this->company->balance} (было {$oldBalance})\n";
        
        if ($this->company->balance == $oldBalance + $amount) {
            echo "   ✅ УСПЕШНО\n";
        } else {
            echo "   ❌ ОШИБКА\n";
        }
        echo "\n";
    }

    private function step7_createPickupsWithOrders()
    {
        echo "7️⃣ СОЗДАНИЕ ВЫЗОВОВ С ЗАКАЗАМИ\n";
        echo "-------------------------------\n";

        // Создаем разные типы заказов
        $pickups = [];

        // Заказ 1: Оплата с кредита компании (достаточно кредита)
        $pickup1 = $this->createPickupWithOrder([
            'delivery_price' => 800,
            'delivery_pay' => 'client',
            'pickup_status' => 'requested'
        ], 'Кредит компании (достаточно)');
        $pickups[] = $pickup1;

        // Заказ 2: Уходит в долг агрегатора (недостаточно кредита)
        $pickup2 = $this->createPickupWithOrder([
            'delivery_price' => 5000,
            'delivery_pay' => 'client',
            'pickup_status' => 'requested'
        ], 'Долг агрегатора');
        $pickups[] = $pickup2;

        // Заказ 3: Оплата с баланса компании
        $pickup3 = $this->createPickupWithOrder([
            'delivery_price' => 1200,
            'delivery_pay' => 'balance',
            'pickup_status' => 'requested'
        ], 'Оплата с баланса');
        $pickups[] = $pickup3;

        // Заказ 4: Оплата наличными в заведении
        $pickup4 = $this->createPickupWithOrder([
            'delivery_price' => 600,
            'delivery_pay' => 'cash',
            'pickup_status' => 'requested'
        ], 'Оплата наличными');
        $pickups[] = $pickup4;

        echo "✅ Создано 4 вызова с заказами\n";
        foreach ($pickups as $i => $pickup) {
            $pickupOrder = \App\Models\GpPickupOrder::where('pickup_id', $pickup->id)->first();
            $order = $pickupOrder ? GpOrder::find($pickupOrder->order_id) : null;
            $num = $i + 1;
            if ($order) {
                echo "   {$num}. ID: {$pickup->id}, Цена {$order->delivery_price}, Тип {$order->delivery_pay}\n";
            } else {
                echo "   {$num}. ID: {$pickup->id}, Заказ не найден\n";
            }
        }
        echo "\n";

        return $pickups;
    }

    private function createPickupWithOrder($orderData, $description)
    {
        // Создаем заказ
        $order = GpOrder::create([
            'number' => 'TEST-' . time() . rand(100, 999),
            'company_id' => $this->company->id,
            'sum' => $orderData['delivery_price'] * 2, // Сумма заказа больше цены доставки
            'delivery_price' => $orderData['delivery_price'],
            'delivery_pay' => $orderData['delivery_pay'],
            'client_phone' => '62 123456',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Создаем вызов
        $pickup = GpPickup::create([
            'company_id' => $this->company->id,
            'driver_id' => null,
            'status' => $orderData['pickup_status'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Создаем связь между вызовом и заказом
        \App\Models\GpPickupOrder::create([
            'pickup_id' => $pickup->id,
            'order_id' => $order->id,
            'status' => 'inherited',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $pickup;
    }

    private function step8_driverAcceptsPickups()
    {
        echo "8️⃣ ВОДИТЕЛЬ ПРИНИМАЕТ ВЫЗОВЫ\n";
        echo "-----------------------------\n";

        $pickups = GpPickup::where('status', 'requested')->get();
        
        foreach ($pickups as $pickup) {
            $oldFundDynamic = $this->admin->fund_dynamic;
            $oldCreditBalance = $this->company->credit_balance;
            $oldAgregatorBalance = $this->company->agregator_side_balance;
            $oldTotalEarn = $this->admin->total_earn;

            echo "🚗 Водитель принимает вызов ID: {$pickup->id}\n";
            $pickupOrder = \App\Models\GpPickupOrder::where('pickup_id', $pickup->id)->first();
            $order = $pickupOrder ? GpOrder::find($pickupOrder->order_id) : null;
            if ($order) {
                echo "   Заказ цена={$order->delivery_price}, оплата={$order->delivery_pay}\n";
            }

            // Вызываем метод принятия вызова
            $this->driverTransactionsRepo->pickup_as_picked_up_price_check($pickup->id, $this->driver->id);

            $this->admin->refresh();
            $this->company->refresh();

            $fundChange = $this->admin->fund_dynamic - $oldFundDynamic;
            $creditChange = $this->company->credit_balance - $oldCreditBalance;
            $agregatorChange = $this->company->agregator_side_balance - $oldAgregatorBalance;
            $totalEarnChange = $this->admin->total_earn - $oldTotalEarn;

            echo "   Изменения\n";
            echo "     - fund_dynamic {$oldFundDynamic} → {$this->admin->fund_dynamic} (" . ($fundChange >= 0 ? '+' : '') . "{$fundChange})\n";
            echo "     - credit_balance {$oldCreditBalance} → {$this->company->credit_balance} (" . ($creditChange >= 0 ? '+' : '') . "{$creditChange})\n";
            echo "     - agregator_side_balance {$oldAgregatorBalance} → {$this->company->agregator_side_balance} (" . ($agregatorChange >= 0 ? '+' : '') . "{$agregatorChange})\n";
            echo "     - total_earn {$oldTotalEarn} → {$this->admin->total_earn} (" . ($totalEarnChange >= 0 ? '+' : '') . "{$totalEarnChange})\n";

            // Проверяем логику в зависимости от типа оплаты
            $expected = $this->getExpectedChangesForPickup($pickup, $oldFundDynamic, $oldCreditBalance, $oldAgregatorBalance, $oldTotalEarn);
            
            if ($this->checkExpectedChanges($expected, $this->admin->fund_dynamic, $this->company->credit_balance, $this->company->agregator_side_balance, $this->admin->total_earn)) {
                echo "   ✅ УСПЕШНО\n";
            } else {
                echo "   ❌ ОШИБКА\n";
            }
            echo "\n";
        }
    }

    private function getExpectedChangesForPickup($pickup, $oldFundDynamic, $oldCreditBalance, $oldAgregatorBalance, $oldTotalEarn)
    {
        $pickupOrder = \App\Models\GpPickupOrder::where('pickup_id', $pickup->id)->first();
        $order = $pickupOrder ? GpOrder::find($pickupOrder->order_id) : null;
        if (!$order) {
            return [
                'fund_dynamic' => $oldFundDynamic,
                'credit_balance' => $oldCreditBalance,
                'agregator_side_balance' => $oldAgregatorBalance,
                'total_earn' => $oldTotalEarn
            ];
        }
        
        $price = $order->delivery_price;
        $paymentType = $order->delivery_pay;

        if ($paymentType === 'balance') {
            // Оплата с баланса - увеличивается total_earn (комиссия)
            $commission = $price * 0.2; // 20% комиссия
            return [
                'fund_dynamic' => $oldFundDynamic,
                'credit_balance' => $oldCreditBalance,
                'agregator_side_balance' => $oldAgregatorBalance,
                'total_earn' => $oldTotalEarn + $commission
            ];
        } elseif ($paymentType === 'client') {
            // Оплата клиентом - проверяем кредит
            if ($oldCreditBalance >= $price) {
                // Списание с кредита - увеличивается fund_dynamic
                return [
                    'fund_dynamic' => $oldFundDynamic + $price,
                    'credit_balance' => $oldCreditBalance - $price,
                    'agregator_side_balance' => $oldAgregatorBalance,
                    'total_earn' => $oldTotalEarn
                ];
            } else {
                // Долг агрегатора - fund_dynamic не изменяется
                return [
                    'fund_dynamic' => $oldFundDynamic,
                    'credit_balance' => $oldCreditBalance,
                    'agregator_side_balance' => $oldAgregatorBalance + $price,
                    'total_earn' => $oldTotalEarn
                ];
            }
        } else {
            // Наличные - ничего не изменяется
            return [
                'fund_dynamic' => $oldFundDynamic,
                'credit_balance' => $oldCreditBalance,
                'agregator_side_balance' => $oldAgregatorBalance,
                'total_earn' => $oldTotalEarn
            ];
        }
    }

    private function checkExpectedChanges($expected, $actualFundDynamic, $actualCreditBalance, $actualAgregatorBalance, $actualTotalEarn)
    {
        return $expected['fund_dynamic'] == $actualFundDynamic &&
               $expected['credit_balance'] == $actualCreditBalance &&
               $expected['agregator_side_balance'] == $actualAgregatorBalance &&
               $expected['total_earn'] == $actualTotalEarn;
    }

    private function step9_driverClosesOrders()
    {
        echo "9️⃣ ВОДИТЕЛЬ ЗАКРЫВАЕТ ЗАКАЗЫ\n";
        echo "-----------------------------\n";

        $orders = GpOrder::all(); // Берем все заказы, так как поле status не используется
        
        foreach ($orders as $order) {
            $oldFundDynamic = $this->admin->fund_dynamic;
            $oldTotalEarn = $this->admin->total_earn;

            echo "📦 Водитель закрывает заказ ID: {$order->id}\n";
            echo "   Цена {$order->delivery_price}, Оплата {$order->delivery_pay}\n";

                    // Вызываем метод закрытия заказа через репозиторий водителя
        $driverPickupRepo = new \App\Repositories\Driver\DriverPickupRepository(new NotificationService(new NodeService()));
        $pickupOrder = \App\Models\GpPickupOrder::where('order_id', $order->id)->first();
        if ($pickupOrder) {
            $driverPickupRepo->makeOrderAsClosed($pickupOrder->id, $this->driver->id);
        }

            $this->admin->refresh();

            $fundChange = $this->admin->fund_dynamic - $oldFundDynamic;
            $totalEarnChange = $this->admin->total_earn - $oldTotalEarn;

            echo "   Изменения\n";
            echo "     - fund_dynamic {$oldFundDynamic} → {$this->admin->fund_dynamic} (" . ($fundChange >= 0 ? '+' : '') . "{$fundChange})\n";
            echo "     - total_earn {$oldTotalEarn} → {$this->admin->total_earn} (" . ($totalEarnChange >= 0 ? '+' : '') . "{$totalEarnChange})\n";

            // При закрытии заказа фонд не должен изменяться
            if ($fundChange == 0) {
                echo "   ✅ УСПЕШНО (фонд не изменился)\n";
            } else {
                echo "   ❌ ОШИБКА (фонд изменился)\n";
            }
            echo "\n";
        }
    }

    private function step10_driverClosesPickups()
    {
        echo "🔟 ВОДИТЕЛЬ ЗАКРЫВАЕТ ВЫЗОВЫ\n";
        echo "-----------------------------\n";

        $pickups = GpPickup::where('status', 'picked_up')->get();
        
        foreach ($pickups as $pickup) {
            $oldFundDynamic = $this->admin->fund_dynamic;
            $oldTotalEarn = $this->admin->total_earn;

            echo "🚗 Водитель закрывает вызов ID: {$pickup->id}\n";

                    // Вызываем метод закрытия вызова через репозиторий водителя
        $driverPickupRepo = new \App\Repositories\Driver\DriverPickupRepository(new NotificationService(new NodeService()));
        $driverPickupRepo->markPickupAsClosed($pickup->id, $this->driver->id);

            $this->admin->refresh();

            $fundChange = $this->admin->fund_dynamic - $oldFundDynamic;
            $totalEarnChange = $this->admin->total_earn - $oldTotalEarn;

            echo "   Изменения\n";
            echo "     - fund_dynamic {$oldFundDynamic} → {$this->admin->fund_dynamic} (" . ($fundChange >= 0 ? '+' : '') . "{$fundChange})\n";
            echo "     - total_earn {$oldTotalEarn} → {$this->admin->total_earn} (" . ($totalEarnChange >= 0 ? '+' : '') . "{$totalEarnChange})\n";

            // При закрытии вызова фонд не должен изменяться
            if ($fundChange == 0) {
                echo "   ✅ УСПЕШНО (фонд не изменился)\n";
            } else {
                echo "   ❌ ОШИБКА (фонд изменился)\n";
            }
            echo "\n";
        }
    }

    private function step11_driverClosesCash()
    {
        echo "1️⃣1️⃣ ВОДИТЕЛЬ ЗАКРЫВАЕТ КАССУ\n";
        echo "-----------------------------\n";

        // Пополняем cash_service водителя и другие кассы для тестирования
        $this->driver->update([
            'cash_service' => 1000,
            'cash_client' => 0,
            'cash_goods' => 0,
            'cash_company_balance' => 0,
            'cash_wallet' => 0
        ]);
        $this->driver->refresh();

        $oldTotalEarn = $this->admin->total_earn;
        $oldCashService = $this->driver->cash_service;
        $closeAmount = 500;

        // Проверяем общий долг водителя
        $totalDebt = $this->driver->cash_client + $this->driver->cash_service + $this->driver->cash_company_balance + $this->driver->cash_wallet;
        if ($totalDebt < $closeAmount) {
            echo "   ❌ ОШИБКА: Недостаточно средств у водителя. Общий долг: {$totalDebt}, требуется: {$closeAmount}\n";
            return;
        }

        echo "💰 Водитель закрывает кассу на сумму {$closeAmount} TMT\n";
        echo "   cash_service до закрытия {$oldCashService}\n";

        // Вызываем метод закрытия кассы водителя
        $this->driverTransactionsRepo->cash_close($this->driver->id, $closeAmount);

        $this->admin->refresh();
        $this->driver->refresh();

        $totalEarnChange = $this->admin->total_earn - $oldTotalEarn;
        $cashServiceChange = $this->driver->cash_service - $oldCashService;

        echo "   Изменения\n";
        echo "     - total_earn {$oldTotalEarn} → {$this->admin->total_earn} (" . ($totalEarnChange >= 0 ? '+' : '') . "{$totalEarnChange})\n";
        echo "     - cash_service {$oldCashService} → {$this->driver->cash_service} (" . ($cashServiceChange >= 0 ? '+' : '') . "{$cashServiceChange})\n";

        // Проверяем, что total_earn увеличился на сумму закрытия cash_service
        $expectedCashServiceChange = -min($closeAmount, $oldCashService);
        if ($totalEarnChange == abs($expectedCashServiceChange) && $cashServiceChange == $expectedCashServiceChange) {
            echo "   ✅ УСПЕШНО\n";
        } else {
            echo "   ❌ ОШИБКА\n";
            echo "   Ожидалось: total_earn +" . abs($expectedCashServiceChange) . ", cash_service " . $expectedCashServiceChange . "\n";
        }
        echo "\n";
    }

    private function step12_operatorClosesCash()
    {
        echo "1️⃣2️⃣ ОПЕРАТОР ЗАКРЫВАЕТ КАССУ\n";
        echo "-----------------------------\n";

        $oldFundDynamic = $this->admin->fund_dynamic;
        $oldOperatorCash = $this->operator->cash;
        $closeAmount = 300; // Уменьшаем сумму, так как у оператора только 500

        echo "💰 Оператор закрывает кассу на сумму {$closeAmount} TMT\n";
        echo "   operator_cash до закрытия {$oldOperatorCash}\n";

        // Вызываем метод закрытия кассы оператора
        $this->adminFundRepo->closeOperatorCash($this->operator->id, $closeAmount, 'test_admin_close_cash');

        $this->admin->refresh();
        $this->operator->refresh();

        $fundChange = $this->admin->fund_dynamic - $oldFundDynamic;
        $operatorCashChange = $this->operator->cash - $oldOperatorCash;

        echo "   Изменения\n";
        echo "     - fund_dynamic {$oldFundDynamic} → {$this->admin->fund_dynamic} (" . ($fundChange >= 0 ? '+' : '') . "{$fundChange})\n";
        echo "     - operator_cash {$oldOperatorCash} → {$this->operator->cash} (" . ($operatorCashChange >= 0 ? '+' : '') . "{$operatorCashChange})\n";

        // При закрытии кассы оператора фонд НЕ должен изменяться
        if ($fundChange == 0 && $operatorCashChange == -$closeAmount) {
            echo "   ✅ УСПЕШНО (фонд не изменился)\n";
        } else {
            echo "   ❌ ОШИБКА\n";
        }
        echo "\n";
    }
}

// Запуск теста
$test = new AdminFundTest();
$test->run();
