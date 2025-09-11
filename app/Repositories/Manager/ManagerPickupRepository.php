<?php

namespace App\Repositories\Manager;

use App\Constants\GpPickupOrderStatus;
use App\Models\GpPickup;
use App\Models\GpOrder;
use App\Models\GpPickupOrder;
use Illuminate\Support\Facades\DB;
use App\Constants\GpPickupStatus;
use App\Models\GpSettings;
use Illuminate\Validation\ValidationException;
use App\Models\GpCompanyManager;

class ManagerPickupRepository
{
    public function getItemsByIds(array $ids = [])
    {
        if (empty($ids)) {
            return collect();
        }
        return $this->getItems($ids);
    }

    public function getItemById($id)
    {
        $items = $this->getItems([$id]);
        if ($items->isEmpty()) {
            return null;
        }
        return $items->first();
    }

    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $company_id, $perPage = 20, $filters = [])
    {
        $query = GpPickup::select('gp_pickups.id as id')
            ->when($company_id !== null, function ($query) use ($company_id) {
                $query->where('gp_pickups.company_id', $company_id);
            })
            ->where('gp_pickups.archived', false);

        // Применяем фильтры
        if (!empty($filters['status'])) {
            $query->where('gp_pickups.status', $filters['status']);
        }

        if (!empty($filters['search_id'])) {
            $query->where('gp_pickups.id', 'like', '%' . $filters['search_id'] . '%');
        }

        if (!empty($filters['search_note'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('gp_pickups.note', 'like', '%' . $filters['search_note'] . '%')
                  ->orWhere('gp_pickups.system_note', 'like', '%' . $filters['search_note'] . '%');
            });
        }

        if (!empty($filters['search_driver'])) {
            $query->whereHas('driver', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search_driver'] . '%')
                  ->orWhere('phone', 'like', '%' . $filters['search_driver'] . '%')
                  ->orWhere('car_number', 'like', '%' . $filters['search_driver'] . '%');
            });
        }

        if (!empty($filters['driver_id'])) {
            $query->where('gp_pickups.driver_id', $filters['driver_id']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('gp_pickups.company_id', $filters['company_id']);
        }

        // Фильтр по дате создания
        if (!empty($filters['date_from'])) {
            $query->where('gp_pickups.created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $query->where('gp_pickups.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $items_ids = $paginator->pluck('id')->toArray();
        $items = $this->getItems($items_ids);
        $ordered_items = $items->sortBy(function ($item) use ($items_ids) {
            return array_search($item->id, $items_ids);
        })->values();
        $paginator->setCollection($ordered_items);
        return $paginator;
    }


    // Получение с пагинацией
    public function getCallItems()
    {
        $paginator = GpPickup::select('gp_pickups.id as id')
            ->where('gp_pickups.archived', false)
            ->whereIn('gp_pickups.status', GpPickupStatus::openForDrivers())
            ->where('gp_pickups.driver_id', null)
            ->get();
        $items_ids = $paginator->pluck('id')->toArray();
        $items = $this->getItems($items_ids);
        return $items;
    }

    // Убираем метод getDriverManagerConnections, так как он больше не нужен
    // public function getDriverManagerConnections()
    // {
    //     // Получаем все активные пикапы с водителями (статус не closed)
    //     $activePickups = GpPickup::select('gp_pickups.driver_id', 'gp_pickups.company_id')
    //         ->whereNotNull('gp_pickups.driver_id')
    //         ->where('gp_pickups.status', '!=', 'closed')
    //         ->where('gp_pickups.archived', false)
    //         ->get();
    //
    //     // Получаем всех активных менеджеров
    //     $managers = GpCompanyManager::select('gp_company_managers.id', 'gp_company_managers.company_id')
    //         ->where('gp_company_managers.is_active', true)
    //         ->get();
    //
    //     // Группируем менеджеров по компаниям
    //     $managersByCompany = $managers->groupBy('company_id');
    //
    //     // Создаем связи водитель -> список менеджеров
    //     $connections = [];
    //     foreach ($activePickups as $pickup) {
    //         $driverId = $pickup->driver_id;
    //         $companyId = $pickup->company_id;
    //
    //         if (!isset($connections[$driverId])) {
    //             $connections[$driverId] = [];
    //         }
    //
    //         // Добавляем менеджеров этой компании к водителю
    //         if (isset($managersByCompany[$companyId])) {
    //             foreach ($managersByCompany[$companyId] as $manager) {
    //                 if (!in_array($manager->id, $connections[$driverId])) {
    //                     $connections[$driverId][] = $manager->id;
    //                 }
    //             }
    //         }
    //     }
    //
    //     return $connections;
    // }


    public function create(array $data): GpPickup
    {
        return DB::transaction(function () use ($data) {

            $createData = [
                'company_id' => $data['company_id'],
                'note' => $data['note'] ?? null,
                'preparing_time' => $data['preparing_time'] ?? null,
                'status' => GpPickupStatus::PREPARING->value,
            ];

            $pickup = GpPickup::create($createData);

            // Проверяем, что поле сохранилось
            $pickup->refresh();

            if (!empty($data['order_ids'])) {
                $orders = GpOrder::whereIn('id', $data['order_ids'])->get();

                foreach ($orders as $order) {
                    if ($order->company_id !== $pickup->company_id) {
                        continue;
                    }

                    GpPickupOrder::firstOrCreate([
                        'pickup_id' => $pickup->id,
                        'order_id' => $order->id,
                    ]);
                }
            }
            $pickup->refresh();
            return $pickup;
        });
    }

    public function update(int $id, array $data): GpPickup
    {
        return DB::transaction(function () use ($id, $data) {
            $pickup = GpPickup::findOrFail($id);

            $pickup->update([
                'note' => $data['note'] ?? null,
                'preparing_time' => $data['preparing_time'] ?? null,
            ]);

            if (isset($data['order_ids'])) {
                GpPickupOrder::where('pickup_id', $pickup->id)->delete();

                $orders = GpOrder::whereIn('id', $data['order_ids'])->get();

                foreach ($orders as $order) {
                    if ($order->company_id !== $pickup->company_id) {
                        continue;
                    }

                    GpPickupOrder::create([
                        'pickup_id' => $pickup->id,
                        'order_id' => $order->id,
                    ]);
                }
            }

            return $pickup;
        });
    }

    public function switchStatus(int $id, $status): bool
    {
        $pickup = GpPickup::findOrFail($id);

        if ($pickup->status === $status) {
            return false;
        }

        $oldStatus = $pickup->status;

        $updateData = ['status' => $status];

        // Если статус REQUESTED, устанавливаем время начала поиска
        if ($status === GpPickupStatus::REQUESTED->value) {
            $updateData['search_started_at'] = now();
        } else {
            // Если статус изменился на что-то другое, очищаем время начала поиска
            $updateData['search_started_at'] = null;
        }

        $pickup->update($updateData);
        return true;
    }

    public function setDriverToPickup(int $pickupId, string $driverId): bool
    {
        $pickup = GpPickup::findOrFail($pickupId);

        logger()->info('🚗 Водитель назначается на заказ', [
            'pickup_id' => $pickupId,
            'driver_id' => $driverId,
            'company_id' => $pickup->company_id,
            'old_status' => $pickup->status->value,
            'new_status' => GpPickupStatus::DRIVER_FOUND->value
        ]);

        $pickup->driver_id = $driverId;
        $pickup->status = GpPickupStatus::DRIVER_FOUND->value;
        $pickup->save();

        logger()->info('✅ Водитель успешно назначен на заказ', [
            'pickup_id' => $pickupId,
            'driver_id' => $driverId,
            'company_id' => $pickup->company_id,
            'status' => $pickup->status->value
        ]);

        return true;
    }

    public function addOrders(int $pickupId, array $orderIds): GpPickup
    {
        $pickup = GpPickup::findOrFail($pickupId);

        $orders = GpOrder::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            if ($order->company_id !== $pickup->company_id) {
                throw ValidationException::withMessages([
                    'order_ids' => ["Заказ {$order->id} не принадлежит компании"],
                ]);
            }

            GpPickupOrder::firstOrCreate([
                'pickup_id' => $pickupId,
                'order_id' => $order->id,
            ]);
        }

        return $pickup->refresh();
    }

    public function removeOrders(int $pickupId, array $orderIds): GpPickup
    {
        // Получаем вызов для проверки его статуса
        $pickup = GpPickup::findOrFail($pickupId);

        // Проверяем, что вызов находится в статусе, когда можно удалять заказы
        $allowedStatuses = [
            GpPickupStatus::PREPARING->value,
            GpPickupStatus::REQUESTED->value,
        ];

        if (!in_array($pickup->status, $allowedStatuses)) {
            throw new \Exception('Нельзя удалять заказы из вызова в текущем статусе');
        }

        // Удаляем заказы
        GpPickupOrder::where('pickup_id', $pickupId)
            ->whereIn('order_id', $orderIds)
            ->whereIn('status', GpPickupOrderStatus::canBeRemovedFromPickup())
            ->delete();

        // Проверяем, остались ли заказы в вызове
        $remainingOrders = GpPickupOrder::where('pickup_id', $pickupId)->count();
        if ($remainingOrders === 0) {
            throw new \Exception('Нельзя удалить все заказы из вызова. Вызов должен содержать хотя бы один заказ.');
        }

        return $pickup->refresh();
    }

    public function changeStatus(int $id, array $data): GpPickup
    {
        $pickup = GpPickup::findOrFail($id);

        $pickup->update([
            'status' => $data['status'],
            'note' => $data['note'] ?? $pickup->note,
        ]);

        return $pickup->refresh();
    }

    private function getItems(array $ids = [])
    {
        $query = GpPickup::query();
        $query->whereIn('gp_pickups.id', $ids);
        $query->leftJoin('gp_companies', 'gp_pickups.company_id', '=', 'gp_companies.id');
        $query->leftJoin('gp_drivers', 'gp_pickups.driver_id', '=', 'gp_drivers.id');
        $query->select(
            'gp_pickups.id as id',
            'gp_pickups.status as status',
            'gp_pickups.note as note',
            'gp_pickups.system_note as system_note',
            'gp_pickups.preparing_time as preparing_time',
            'gp_pickups.closed_time as closed_time',
            'gp_pickups.search_started_at as search_started_at',
            //
            'gp_pickups.company_id as company_id',
            'gp_companies.name as company_name',
            'gp_companies.image as company_image',
            'gp_companies.phone as company_phone',
            'gp_companies.address as company_address',
            'gp_companies.count as company_count',
            'gp_companies.lat as company_lat',
            'gp_companies.lng as company_lng',
            //
            'gp_pickups.driver_id as driver_id',
            'gp_drivers.name as driver_name',
            'gp_drivers.phone as driver_phone',
            'gp_drivers.car_name as driver_car_name',
            'gp_drivers.car_number as driver_car_number',
        );
        $items = $query->get();

        $ids = $items->pluck('id')->toArray();

        $all_orders = $this->getPickupOrdersByIds($ids)->toArray();
        $driverFee = GpSettings::driverFee();
        $items->map(function ($item) use ($all_orders, $driverFee) {
            $item->driver_fee = $driverFee;
            $item->orders = $all_orders[$item->id] ?? null;
            return $item;
        });

        return $items;
    }

    private function getManagerItems(array $ids = [])
    {
        $query = GpCompanyManager::query();
        $query->whereIn('gp_company_managers.id', $ids);
        $query->leftJoin('gp_companies', 'gp_company_managers.company_id', '=', 'gp_companies.id');
        $query->select(
            'gp_company_managers.id as id',
            'gp_company_managers.name as name',
            'gp_company_managers.email as email',
            'gp_company_managers.is_active as is_active',
            'gp_company_managers.created_at as created_at',
            'gp_company_managers.updated_at as updated_at',
            //
            'gp_company_managers.company_id as company_id',
            'gp_companies.name as company_name',
        );
        $items = $query->get();

        return $items;
    }

    public function getPickupOrdersByIds(array $ids = [])
    {
        $query = GpPickupOrder::query();
        $query->whereIn('gp_pickup_orders.pickup_id', $ids);
        $query->leftJoin('gp_orders', 'gp_pickup_orders.order_id', '=', 'gp_orders.id');
        $query->leftJoin('gp_map_districts', 'gp_orders.district_id', '=', 'gp_map_districts.id');
        $query->leftJoin('gp_map_streets as streets', 'gp_orders.street_id', '=', 'streets.id');
        $query->leftJoin('gp_map_streets as second_streets', 'gp_orders.second_street_id', '=', 'second_streets.id');
        $query->select(
            'gp_pickup_orders.id as id',
            'gp_pickup_orders.pickup_id as pickup_id',
            'gp_pickup_orders.order_id as order_id',
            'gp_pickup_orders.status as status',
            'gp_pickup_orders.note as note',
            'gp_pickup_orders.system_note as system_note',
            'gp_pickup_orders.sort_order as sort_order',
            //
            'gp_orders.number as order_number',
            'gp_orders.client_phone as client_phone',
            'gp_orders.sum as sum',
            'gp_orders.lat as lat',
            'gp_orders.lng as lng',
            'gp_orders.delivery_price as delivery_price',
            'gp_orders.delivery_pay as delivery_pay',
            //
            'streets.id as street_id',
            'streets.name as street_name',
            'second_streets.id as second_street_id',
            'second_streets.name as second_street_name',
            'gp_map_districts.id as district_id',
            'gp_map_districts.name as district_name',
            'gp_orders.geo_comment as geo_comment',
        );
        $query->orderBy('gp_pickup_orders.sort_order');
        $items = $query->get();
        $items = $items->groupBy('pickup_id');
        $items = $items->map(function ($item) {
            return $item->map(function ($pickup_order) {
                return [
                    'id' => $pickup_order->id,
                    'pickup_id' => $pickup_order->pickup_id,
                    'order_id' => $pickup_order->order_id,
                    'status' => $pickup_order->status,
                    'order_number' => $pickup_order->order_number,
                    'client_phone' => $pickup_order->client_phone,
                    'sum' => $pickup_order->sum,
                    'delivery_price' => $pickup_order->delivery_price,
                    'delivery_pay' => $pickup_order->delivery_pay,
                    'address' => $this->orderAddressString($pickup_order),
                    'geo' => $this->orderGeopointString($pickup_order),
                    'district_id' => $pickup_order->district_id,
                    'street_id' => $pickup_order->street_id,
                    'second_street_id' => $pickup_order->second_street_id,
                    'note' => $pickup_order->note,
                    'system_note' => $pickup_order->system_note,
                    'sort_order' => $pickup_order->sort_order,
                ];
            });
        });
        return $items;
    }

    private function orderAddressString($pickupOrder)
    {
        $res = '';
        if (!empty($pickupOrder['district_name'])) {
            $res .= $pickupOrder['district_name'] . ', ';
        }
        if (!empty($pickupOrder['street_name'])) {
            $res .= $pickupOrder['street_name'];
        }
        if (!empty($pickupOrder['second_street_name'])) {
            $res .= '-' . $pickupOrder['second_street_name'];
        }
        if (!empty($pickupOrder['geo_comment'])) {
            $res .= ', ' . $pickupOrder['geo_comment'];
        }
        return $res;
    }

    private function orderGeopointString($pickupOrder)
    {
        $res = null;
        if (!empty($pickupOrder['lat']) && !empty($pickupOrder['lng'])) {
            $res = $pickupOrder['lat'] . ',' . $pickupOrder['lng'];
        }
        return $res;
    }
}
