# Схема изменения общего заработка в фонде админа

## Общий заработок (total_earn) пополняется только двумя способами:

### 1. Закрытие кассы водителя
**Когда:** Водитель закрывает кассу и списывается с поля `gp_drivers.cash_service`

**Логика:**
- При закрытии кассы водителя происходит списание по очереди со всех колонок:
  - `cash_client` (наличные от клиентов)
  - `cash_service` (наличные за услугу доставки) ← **ИМЕННО ОТСЮДА**
  - `cash_goods` (сумма товаров на руках)
  - `cash_company_balance` (от заведений)
  - `cash_wallet` (за пополнение кошелька клиента)

**Важно:** У админа увеличивается `total_earn` только суммой списания с `gp_drivers.cash_service`

### 2. Закрытие заказа с оплатой с баланса компании
**Когда:** Водитель закрывает заказ с `delivery_pay = 'balance'`

**Логика:**
- Компания заказывает доставку и ставит оплату своим балансом
- При закрытии заказа с баланса компании `gp_companies.balance` списывается сумма `delivery_price`
- Водителю начисляется его часть: `delivery_price - (delivery_price * driver_fee / 100)`
- Остаток (комиссия агрегатора) записывается в `total_earn` у админа

## Детальная схема работы:

### Сценарий 1: Закрытие кассы водителя
```
Водитель закрывает кассу на 100 TMT:
- cash_client: 30 TMT → 0 TMT
- cash_service: 50 TMT → 0 TMT ← total_earn +50 TMT
- cash_goods: 20 TMT → 0 TMT

Результат: total_earn увеличивается на 50 TMT
```

### Сценарий 2: Закрытие заказа с оплатой с баланса
```
Заказ с delivery_price = 20 TMT, driver_fee = 25%:
- С баланса компании списывается: 20 TMT
- Водителю начисляется: 20 - (20 * 25/100) = 15 TMT в earning_pending
- Комиссия агрегатора: 20 - 15 = 5 TMT

Результат: total_earn увеличивается на 5 TMT
```

## Текущая реализация:

### В `DriverTransactionsRepository::cash_close()`:
```php
// Закрываем cash_service
if ($remaining > 0 && $driver->cash_service > 0) {
    $toClose = min($remaining, $driver->cash_service);
    $this->driverBalanceRepository->addCashService($driverId, -$toClose, 'cash_close');
    // НУЖНО ДОБАВИТЬ: увеличение total_earn у админа на $toClose
    $remaining -= $toClose;
}
```

### В `DriverTransactionsRepository::order_as_closed_transaction()`:
```php
// Если deliveryPay == balance
if ($order->delivery_pay === 'balance') {
    // Вычитаем всю стоимость услуги с balance компании
    $negativeSum = -abs($order->delivery_price);
    $companyBalanceRepository->addBalance($company->id, $negativeSum, 'order_closed');
    
    // Начисляем часть водителя в earning_pending
    $a = abs($order->delivery_price);
    $servicePart = ($a * ($driverFee / 100));
    $driverPart = $a - $servicePart;
    $this->driverBalanceRepository->addEarningPending($driverId, $driverPart, 'order_closed');
    
    // НУЖНО ДОБАВИТЬ: увеличение total_earn у админа на $servicePart
}
```

## Реализованные изменения:

### ✅ 1. Создан метод в `AdminFundRepository`
```php
public function increaseTotalEarn($amount, $tag = 'total_earn_increase')
```
- Увеличивает `total_earn` у админа
- Логирует изменения в `gp_admin_fund_logs`
- Возвращает обновленные данные фонда

### ✅ 2. Добавлен вызов в `cash_close()`
```php
// Увеличиваем общий заработок админа на сумму списания с cash_service
$adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
$adminFundRepository->increaseTotalEarn($toClose, 'driver_cash_service_close');
```

### ✅ 3. Добавлен вызов в `order_as_closed_transaction()`
```php
// Увеличиваем общий заработок админа на комиссию агрегатора
$adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
$adminFundRepository->increaseTotalEarn($servicePart, 'order_balance_commission');
```

### ✅ 4. Добавлено логирование
Все изменения `total_earn` записываются в `gp_admin_fund_logs` с соответствующими тегами:
- `driver_cash_service_close` - при закрытии кассы водителя
- `order_balance_commission` - при закрытии заказа с баланса

## Формула проверки:
```
total_earn = сумма всех списаний с cash_service + сумма всех комиссий с delivery_price
```

## Теги для логирования:
- `driver_cash_service_close` - списание с cash_service водителя
- `order_balance_commission` - комиссия с заказа, оплаченного с баланса компании
- `total_earn_increase` - общий тег для увеличения заработка
