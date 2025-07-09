<?php

namespace App\Constants;

/**
 * Enum GpPickupOrderStatus
 *
 * Статусы заказов внутри одного вызова водителя.
 */
enum GpPickupOrderStatus: string
{
    /**
     * Наследуется от общего статуса вызова.
     * Не отображается как отдельный статус.
     */
    case INHERITED = 'inherited';

    /**
     * Заказ принят водителем и находится у него.
     */
    case ACCEPTED = 'accepted';

    /**
     * Водитель прибыл к клиенту, ожидает получения.
     */
    case WAITING_CLIENT = 'waiting_client';

    /**
     * Заказ успешно передан клиенту. Завершён без инцидентов.
     */
    case DELIVERED = 'delivered';

    /**
     * Клиент не отвечает, не вышел.
     */
    case CLIENT_NO_SHOW = 'client_no_show';

    /**
     * Клиент отменил заказ.
     */
    case CANCELLED_BY_CLIENT = 'cancelled_by_client';

    /**
     * Оператор отменил заказ.
     */
    case CANCELLED_BY_OPERATOR = 'cancelled_by_operator';

    /**
     * Неопределённый статус — ошибка или системный сбой.
     */
    case ERROR = 'error';
}
