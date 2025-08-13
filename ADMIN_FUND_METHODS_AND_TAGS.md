# Методы и теги изменения фонда и заработка админа

## Класс: AdminFundRepository

### 1. Пополнение кассы оператора
**Метод:** `addCashToOperator($operatorId, $amount, $tag = 'admin_add_cash')`

**Описание:** Пополняет кассу оператора из фонда админа
- **Источник:** `fund_dynamic` админа
- **Назначение:** Касса оператора
- **Логика:** `fund_dynamic` уменьшается, касса оператора увеличивается

**Тег:** `admin_add_cash`
**Логирование:** `gp_admin_fund_logs` и `gp_operator_balance_logs`

---

### 2. Закрытие кассы оператора
**Метод:** `closeOperatorCash($operatorId, $amount, $tag = 'admin_close_cash')`

**Описание:** Закрывает кассу оператора (возврат в фонд админа)
- **Источник:** Касса оператора
- **Назначение:** `fund_dynamic` админа
- **Логика:** Касса оператора уменьшается, `fund_dynamic` увеличивается

**Тег:** `admin_close_cash`
**Логирование:** `gp_admin_fund_logs` и `gp_operator_balance_logs`

---

### 3. Пополнение общего фонда
**Метод:** `topUpFund($amount, $tag = 'admin_top_up_fund')`

**Описание:** Пополняет общий фонд админа
- **Источник:** Внешнее пополнение
- **Назначение:** `fund` и `fund_dynamic` админа
- **Логика:** Оба фонда увеличиваются на указанную сумму

**Тег:** `admin_top_up_fund`
**Логирование:** `gp_admin_fund_logs`

---

### 4. Увеличение общего заработка
**Метод:** `increaseTotalEarn($amount, $tag = 'total_earn_increase')`

**Описание:** Увеличивает общий заработок админа
- **Источник:** Комиссии агрегатора
- **Назначение:** `total_earn` админа
- **Логика:** `total_earn` увеличивается на указанную сумму

**Тег:** `total_earn_increase`
**Логирование:** `gp_admin_fund_logs`

---

## Класс: FundManagerRepository

### 1. Увеличение динамичного фонда
**Метод:** `increaseFundDynamic($amount, $tag = 'credit_balance_close')`

**Описание:** Увеличивает `fund_dynamic` админа
- **Источник:** Закрытие кредита компании
- **Назначение:** `fund_dynamic` админа
- **Логика:** `fund_dynamic` увеличивается при списании `credit_balance`

**Тег:** `credit_balance_close`
**Логирование:** `gp_admin_fund_logs`

---

### 2. Уменьшение динамичного фонда
**Метод:** `decreaseFundDynamic($amount, $tag = 'credit_balance_increase')`

**Описание:** Уменьшает `fund_dynamic` админа
- **Источник:** Пополнение кредита компании админом
- **Назначение:** `credit_balance` компании
- **Логика:** `fund_dynamic` уменьшается при пополнении `credit_balance` админом

**Тег:** `credit_balance_increase`
**Логирование:** `gp_admin_fund_logs`

---

## Класс: CompanyBalanceRepository

### 1. Пополнение кредита компании (общий метод)
**Метод:** `addCreditBalance($companyId, $amount, $tag)`

**Описание:** Пополняет кредит компании с учетом типа пользователя
- **Для админа:** Списывается с `fund_dynamic`
- **Для оператора:** Списывается только с кассы оператора (через отдельный метод)

**Тег:** `credit_balance_update` (по умолчанию)
**Логирование:** `gp_company_balance_logs`

---

### 2. Пополнение кредита компании оператором
**Метод:** `addCreditBalanceByOperator($companyId, $amount, $operatorId, $tag = 'operator_credit_balance_increase')`

**Описание:** Пополняет кредит компании оператором
- **Источник:** Касса оператора
- **Назначение:** `credit_balance` компании
- **Логика:** Касса оператора уменьшается, `credit_balance` увеличивается, `fund_dynamic` не изменяется

**Тег:** `operator_credit_balance_increase`
**Логирование:** `gp_company_balance_logs` и `gp_operator_balance_logs`

---

## Класс: DriverTransactionsRepository

### 1. Закрытие кассы водителя
**Метод:** `cash_close($driverId, $amount)`

**Описание:** Закрывает кассу водителя с увеличением заработка админа
- **Источник:** Касса водителя (по очереди: cash_client, cash_service, cash_goods, cash_company_balance, cash_wallet)
- **Назначение:** Касса оператора + заработок админа
- **Логика:** При списании с `cash_service` увеличивается `total_earn` админа

**Тег:** `driver_cash_service_close` (для заработка админа)
**Логирование:** `gp_driver_balance_logs` и `gp_admin_fund_logs`

---

### 2. Закрытие заказа
**Метод:** `order_as_closed_transaction($orderId, $driverId)`

**Описание:** Обрабатывает закрытие заказа водителем
- **Для `delivery_pay = 'balance'`:** Увеличивает `total_earn` админа на комиссию
- **Для `delivery_pay = 'client'`:** Начисляет комиссию в `cash_service` водителя
- **Для `delivery_pay = 'cash'`:** Уже обработано при принятии вызова

**Тег:** `order_balance_commission` (для заработка админа при балансе)
**Логирование:** `gp_company_balance_logs`, `gp_driver_balance_logs` и `gp_admin_fund_logs`

---

## Полный список тегов

### Теги для gp_admin_fund_logs:
- `admin_add_cash` - пополнение кассы оператора
- `admin_close_cash` - закрытие кассы оператора
- `admin_top_up_fund` - пополнение общего фонда
- `total_earn_increase` - общий заработок (общий тег)
- `driver_cash_service_close` - заработок с кассы водителя
- `order_balance_commission` - комиссия с заказа с баланса
- `credit_balance_close` - закрытие кредита компании
- `credit_balance_increase` - пополнение кредита админом

### Теги для gp_operator_balance_logs:
- `admin_add_cash` - пополнение кассы оператора
- `admin_close_cash` - закрытие кассы оператора
- `operator_credit_balance_increase` - пополнение кредита оператором

### Теги для gp_company_balance_logs:
- `credit_balance_update` - обновление кредита
- `operator_credit_balance_increase` - пополнение кредита оператором
- `order_closed` - закрытие заказа
- `balance_update` - обновление баланса
- `agregator_side_balance_update` - обновление долга агрегатора

### Теги для gp_driver_balance_logs:
- `cash_close` - закрытие кассы
- `order_closed` - закрытие заказа
- `picked_up_cash` - принятие вызова с наличными
- `earning_increase` - увеличение заработка
- `earning_pending_increase` - увеличение отложенного заработка

## Схема движения денег

### 1. Пополнение кассы оператора:
```
fund_dynamic → касса оператора
Тег: admin_add_cash
```

### 2. Пополнение кредита оператором:
```
касса оператора → credit_balance компании
Тег: operator_credit_balance_increase
```

### 3. Пополнение кредита админом:
```
fund_dynamic → credit_balance компании
Тег: credit_balance_increase
```

### 4. Закрытие кассы водителя:
```
cash_service водителя → total_earn админа
Тег: driver_cash_service_close
```

### 5. Закрытие заказа с баланса:
```
balance компании → earning_pending водителя + total_earn админа
Тег: order_balance_commission
```

## Формулы проверки

### Баланс фонда:
```
fund = fund_dynamic + сумма всех credit_balance компаний
```

### Общий заработок:
```
total_earn = сумма всех списаний с cash_service + сумма всех комиссий с delivery_price
```
