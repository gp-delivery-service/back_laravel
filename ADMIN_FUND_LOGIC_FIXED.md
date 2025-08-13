# Исправленная логика фонда админа

## Проблемы, которые были исправлены

### 1. Ошибка внешнего ключа
**Проблема:** При пополнении кассы оператора возникала ошибка:
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`gepard`.`gp_admin_fund_logs`, CONSTRAINT `gp_admin_fund_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `gp_admins` (`id`) ON DELETE SET NULL)
```

**Причина:** В таблице `gp_admin_fund_logs` поле `user_id` имеет внешний ключ на таблицу `gp_admins`, но код пытался записать туда ID оператора.

**Решение:** Изменена логика записи в `user_id` - теперь туда записывается только ID админа, а ID оператора записывается в поле `operator_id`.

### 2. Неправильная логика пополнения кредита компании
**Проблема:** При пополнении кредита компании оператором всегда списывалось с `fund_dynamic`, что противоречило требованиям.

**Решение:** Изменена логика в `CompanyBalanceRepository::addCreditBalance()`:
- Если пополняет **админ** → списывается с `fund_dynamic`
- Если пополняет **оператор** → списывается только с кассы оператора, `fund_dynamic` не трогается

## Текущая логика фонда админа

### 1. Пополнение кассы оператора
- **Источник:** `fund_dynamic` админа
- **Назначение:** Касса оператора
- **Логика:** `fund_dynamic` уменьшается, касса оператора увеличивается
- **Метод:** `AdminFundRepository::addCashToOperator()`

### 2. Пополнение кредита компании оператором
- **Источник:** Касса оператора
- **Назначение:** `credit_balance` компании
- **Логика:** Касса оператора уменьшается, `credit_balance` компании увеличивается, `fund_dynamic` не изменяется
- **Метод:** `CompanyBalanceRepository::addCreditBalanceByOperator()`

### 3. Пополнение кредита компании админом
- **Источник:** `fund_dynamic` админа
- **Назначение:** `credit_balance` компании
- **Логика:** `fund_dynamic` уменьшается, `credit_balance` компании увеличивается
- **Метод:** `CompanyBalanceRepository::addCreditBalance()` (только для админа)

### 4. Закрытие кредита компании
- **Источник:** `credit_balance` компании
- **Назначение:** `fund_dynamic` админа
- **Логика:** `credit_balance` уменьшается, `fund_dynamic` увеличивается
- **Метод:** `CompanyBalanceRepository::addCreditBalance()` (при отрицательном amount)

## Изменения в коде

### CompanyBalanceRepository.php
1. Изменена логика в `addCreditBalance()` - теперь `fund_dynamic` списывается только при пополнении админом
2. Добавлен новый метод `addCreditBalanceByOperator()` для пополнения кредита оператором

### FundManagerRepository.php
1. Исправлена логика записи в `gp_admin_fund_logs` - `user_id` теперь содержит только ID админа
2. Добавлена логика для правильного заполнения `operator_id`

### AdminFundRepository.php
1. Исправлена логика записи в `gp_admin_fund_logs` для консистентности

## Использование

### Для пополнения кредита оператором:
```php
$companyBalanceRepo = new CompanyBalanceRepository();
$companyBalanceRepo->addCreditBalanceByOperator($companyId, $amount, $operatorId);
```

### Для пополнения кредита админом:
```php
$companyBalanceRepo = new CompanyBalanceRepository();
$companyBalanceRepo->addCreditBalance($companyId, $amount, $tag);
```

## Проверка баланса

Фонд админа всегда должен быть сбалансирован:
```
fund = fund_dynamic + сумма всех credit_balance компаний
```

Для проверки баланса используйте:
```php
$admin = GpAdmin::first();
$isBalanced = $admin->isFundBalanced();
$difference = $admin->getFundDifference();
```
