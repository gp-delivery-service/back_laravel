<?php

namespace App\Repositories\Driver;

use App\Constants\GpPickupOrderStatus;
use App\Models\GpPickup;
use App\Models\GpOrder;
use App\Models\GpPickupOrder;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use App\Constants\GpPickupStatus;
use App\Repositories\Balance\DriverBalanceRepository;
use App\Repositories\Balance\DriverTransactionsRepository;
use App\Repositories\Manager\ManagerPickupRepository;
use Illuminate\Validation\ValidationException;

class DriverPickupRepository
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function availablePickups()
    {
        $query = GpPickup::query();
        $query->where('driver_id', null);
        $query->whereIn('status', GpPickupStatus::openForDrivers());
        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function driverActivePickups($driverId)
    {
        $query = GpPickup::query();
        $query->where('driver_id', $driverId);
        $query->whereIn('status', GpPickupStatus::activeStatuses());
        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function driverClosedPickups($driverId)
    {
        $query = GpPickup::query();
        $query->where('driver_id', $driverId);
        $query->whereIn('status', array(GpPickupStatus::CLOSED));
        // $query->orderBy('updated_at', 'desc');
        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function closedPaginated($driverId)
    {
        $query = GpPickup::query();
        $query->where('driver_id', $driverId);
        $query->whereIn('status', GpPickupStatus::closedStatuses());

        $pickupIds = $query->pluck('id')->toArray();
        $pickupRepository = new ManagerPickupRepository();
        $pickups = $pickupRepository->getItemsByIds($pickupIds);
        return $pickups;
    }

    public function getPickupById($pickupId, $driverId)
    {
        $query = GpPickup::query();
        $query->where('id', $pickupId);
        $query->where('driver_id', $driverId);
        $pickup = $query->first();

        if (!$pickup) {
            return null;
        }

        $pickupRepository = new ManagerPickupRepository();
        $pickup = $pickupRepository->getItemById($pickupId);
        if (!$pickup) {
            return null;
        }

        return $pickup;
    }

    public function markPickupAsPickedUp($pickupId, $driverId)
    {
        $pickup = GpPickup::query()
            ->where('id', $pickupId)
            ->where('driver_id', $driverId)
            ->first();
        if (!$pickup) {
            throw ValidationException::withMessages(['pickup' => 'Pickup not found or unauthorized']);
        }

        DB::transaction(function () use ($pickup, $driverId) {
            $driverTransactionRepository = new DriverTransactionsRepository(new DriverBalanceRepository());
            $r = $driverTransactionRepository->pickup_as_picked_up_price_check($pickup->id, $driverId);
            if (!$r) {
                throw ValidationException::withMessages(['pickup' => 'Error checking pickup price']);
            }
            $pickup->status = GpPickupStatus::PICKED_UP;
            $pickup->picked_up_at = now();
            $pickup->save();
        });

        return $this->getPickupById($pickupId, $driverId);
    }

    public function markPickupAsClosed($pickupId, $driverId)
    {
        $pickup = GpPickup::query()
            ->where('id', $pickupId)
            ->where('driver_id', $driverId)
            ->first();
        if (!$pickup) {
            throw ValidationException::withMessages(['pickup' => 'Pickup not found or unauthorized']);
        }

        DB::transaction(function () use ($pickup) {
            $pickup->status = GpPickupStatus::CLOSED;
            $pickup->closed_at = now();
            $pickup->save();
        });

        return $this->getPickupById($pickupId, $driverId);
    }

    public function makeOrderAsClosed($pickupOrderId, $driverId)
    {
        $order = GpPickupOrder::query()
            ->where('id', $pickupOrderId)
            ->first();
        if (!$order) {
            throw ValidationException::withMessages(['order' => 'Order not found']);
        }

        DB::transaction(function () use ($order, $driverId) {
            $driverTransactionRepository = new DriverTransactionsRepository(new DriverBalanceRepository());
            $r = $driverTransactionRepository->order_as_closed_transaction($order->order_id, $driverId, $order->pickup_id);
            if ($r !== true) {
                throw ValidationException::withMessages(['order' => 'Error during closing order: ' . $r]);
            }
            $order->status = GpPickupOrderStatus::DELIVERED;
            $order->save();
        });

        // Получаем заказ для отправки уведомления
        $orderModel = GpOrder::find($order->order_id);
        if ($orderModel) {
            // Отправляем уведомление клиенту о том, что заказ доставлен
            $this->notificationService->sendOrderStatusNotification(
                $orderModel,
                'delivered'
            );
        }

        return $this->getPickupById($order->pickup_id, $order->driver_id);
    }

    /**
     * Установить статус заказа как "ожидает клиента" и отправить уведомление
     */
    public function setOrderWaitingClient($pickupOrderId, $driverId)
    {
        $order = GpPickupOrder::query()
            ->where('id', $pickupOrderId)
            ->first();
        if (!$order) {
            throw ValidationException::withMessages(['order' => 'Order not found']);
        }

        // Проверяем, что водитель имеет доступ к этому заказу
        $pickup = GpPickup::where('id', $order->pickup_id)
            ->where('driver_id', $driverId)
            ->first();
        if (!$pickup) {
            throw ValidationException::withMessages(['order' => 'Order not found or unauthorized']);
        }

        DB::transaction(function () use ($order) {
            $order->status = GpPickupOrderStatus::WAITING_CLIENT;
            $order->save();
        });

        // Получаем заказ для отправки уведомления
        $orderModel = GpOrder::find($order->order_id);
        if ($orderModel) {
            // Отправляем уведомление клиенту о том, что водитель прибыл
            $this->notificationService->sendOrderStatusNotification(
                $orderModel,
                'waiting_client'
            );
        }

        return $this->getPickupById($order->pickup_id, $order->driver_id);
    }
}
