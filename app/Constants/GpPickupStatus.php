<?php

namespace App\Constants;

/**
 * Enum GpPickupStatus
 *
 * Статусы вызова водителя для забора заказов.
 */
enum GpPickupStatus: string
{
    /**
     * Вызов готовится оператором — добавляются заказы.
     */
    case PREPARING = 'preparing';

    /**
     * Вызов запрошен — передан в систему.
     */
    case REQUESTED = 'requested';

    /**
     * Система ищет водителя.
     */
    case SEARCHING_DRIVER = 'searching_driver';

    /**
     * Водитель найден и назначен.
     */
    case DRIVER_FOUND = 'driver_found';

    /**
     * Водитель прибыл и забрал заказы.
     */
    case PICKED_UP = 'picked_up';

    /**
     * Водитель в процессе выполнения (развозит, хотя бы один заказ завершён).
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * Водитель завершил доставку всех заказов. Ожидается подтверждение оператором.
     */
    case WAITING_CONFIRMATION = 'waiting_confirmation';

    /**
     * Вызов закрыт.
     */
    case CLOSED = 'closed';

    /**
     * Один или несколько заказов не были завершены. Требуется модерация.
     */
    case NEEDS_MODERATION = 'needs_moderation';

    /**
     * Вызов отменён оператором.
     */
    case CANCELLED_BY_OPERATOR = 'cancelled_by_operator';

    /**
     * Вызов отменён водителем.
     */
    case CANCELLED_BY_DRIVER = 'cancelled_by_driver';

    /**
     * Система ищет нового водителя повторно.
     */
    case RETRY_SEARCH = 'retry_search';

    /**
     * Водитель не найден.
     */
    case DRIVER_NOT_FOUND = 'driver_not_found';

    /**
     * Ошибка или неопределённый статус.
     */
    case ERROR = 'error';

     /**
     * Статусы, при которых можно назначить водителя.
     *
     * @return string[]
     */
    public static function openForDrivers(): array
    {
        return [
            self::REQUESTED->value,
            self::SEARCHING_DRIVER->value,
            self::RETRY_SEARCH->value,
            self::DRIVER_NOT_FOUND->value,
        ];
    }

    /**
     * Статусы, при которых вызов активный
     * 
     * @return string[]
     */
    public static function activeStatuses(): array
    {
        return [
            self::DRIVER_FOUND->value,
            self::PICKED_UP->value,
            self::IN_PROGRESS->value,
            self::WAITING_CONFIRMATION->value,
            self::NEEDS_MODERATION->value,
        ];
    }

    /**
     * Статусы, при которых вызов закрыт
     * 
     * @return string[]
     */
    public static function closedStatuses(): array
    {
        return [
            self::CLOSED->value,
        ];
    }
}