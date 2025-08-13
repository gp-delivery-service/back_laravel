# Обновление тегов для логов фонда админа

## Что было сделано

### 1. Обновлен файл `gepard_vue/src/constants/adminFundLogTags.js`

Добавлены новые теги для логов фонда админа и заработка:

#### Новые теги для заработка админа:
- `total_earn_increase` - "Увеличение общего заработка"
- `driver_cash_service_close` - "Закрытие кассы водителя (cash_service)"
- `order_balance_commission` - "Комиссия с заказа (баланс компании)"

#### Новые теги для операторов:
- `operator_credit_balance_increase` - "Пополнение кредита оператором"

### 2. Обновлен файл `gepard_vue/src/dto/admin/UnionLogDto.js`

Метод `formatTag()` теперь использует словарь тегов из `adminFundLogTags.js`:

```javascript
formatTag() {
    // Сначала пытаемся найти тег в словаре админских логов
    const adminTagName = getTagDisplayName(this.tag);
    if (adminTagName !== this.tag) {
        return adminTagName;
    }
    
    // Если не найден в админских логах, используем старую логику
    switch (this.tag) {
        // ... существующие теги
    }
}
```

### 3. Добавлена группировка тегов по категориям

Создана функция `getTagCategories()` для группировки тегов:

```javascript
export function getTagCategories() {
    return {
        'Фонд админа': [
            'admin_top_up_fund',
            'admin_add_cash',
            'admin_close_cash',
            // ...
        ],
        'Заработок админа': [
            'total_earn_increase',
            'driver_cash_service_close',
            'order_balance_commission',
            // ...
        ],
        // ...
    };
}
```

## Результат

### В странице логов фонда админа (`AdminFundPage.vue`):
- Теги отображаются в понятном виде вместо технических названий
- Добавлены новые теги для заработка админа
- Улучшена читаемость логов

### В странице общих логов (`UnionBalanceLog.vue`):
- Все теги из админских логов теперь отображаются понятно
- Сохранена обратная совместимость со старыми тегами
- Унифицировано отображение тегов во всей системе

## Примеры отображения

### До обновления:
- `admin_add_cash` → `admin_add_cash`
- `driver_cash_service_close` → `driver_cash_service_close`
- `order_balance_commission` → `order_balance_commission`

### После обновления:
- `admin_add_cash` → "Выдача кассы оператору"
- `driver_cash_service_close` → "Закрытие кассы водителя (cash_service)"
- `order_balance_commission` → "Комиссия с заказа (баланс компании)"

## Функции для работы с тегами

### `getTagDisplayName(tag)`
Возвращает человекочитаемое название тега

### `getAllTags()`
Возвращает все доступные теги с их отображаемыми названиями

### `getTagCategories()`
Возвращает группировку тегов по категориям

## Добавление новых тегов

Для добавления новых тегов:

1. Добавить константу в `ADMIN_FUND_LOG_TAGS`
2. Добавить отображаемое название в `getTagDisplayName()`
3. Добавить в `getAllTags()`
4. Добавить в соответствующую категорию в `getTagCategories()`

Пример:
```javascript
// 1. Добавить константу
NEW_TAG: 'new_tag',

// 2. Добавить отображение
[NEW_TAG]: 'Понятное название тега',

// 3. Добавить в getAllTags()
[NEW_TAG]: getTagDisplayName(NEW_TAG),

// 4. Добавить в категорию
'Категория': [
    // ... существующие теги
    NEW_TAG,
],
```
