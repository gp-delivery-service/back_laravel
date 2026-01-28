<?php

namespace App\Repositories\Admin;

use App\Models\GpDriver;
use Illuminate\Support\Facades\DB;

class DriverRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $perPage = 20, $status = null, $search = null)
    {
        $query = GpDriver::select('gp_drivers.id as id');

        // Применяем фильтр по статусу
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        // Если $status === 'all' или null, то показываем всех
        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('gp_drivers.name', 'like', $term)
                  ->orWhere('gp_drivers.phone', 'like', $term);
            });
        }
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $items_ids = $paginator->pluck('id')->toArray();
        $items = $this->getItems($items_ids, $status);
        $ordered_items = $items->sortBy(function ($item) use ($items_ids) {
            return array_search($item->id, $items_ids);
        })->values();
        $paginator->setCollection($ordered_items);
        return $paginator;
    }

    // Создание
    public function create(array $data)
    {
        $created = GpDriver::create($data);
        return $created;
    }

    // Обновление
    public function update($id, array $data)
    {
        $driver = GpDriver::find($id);

        // Убеждаемся, что is_active передается как boolean
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $updated = $driver->update($data);
        return $updated;
    }

    // Удаление
    public function delete($id): bool
    {
        $driver = GpDriver::find($id);
        if (!$driver) {
            return false;
        }
        return (bool) $driver->delete();
    }

    public function getItemById($driverId)
    {
        $item = GpDriver::find($driverId);
        if (!$item) {
            return null;
        }
        $item = $this->getItems([$item->id])->first();
        if (!$item) {
            return null;
        }
        return $item;
    }

    public function getItemsByIds(array $ids = [], $status = null)
    {
        return $this->getItems($ids, $status);
    }

    private function getItems(array $ids = [], $status = null)
    {
        $query = GpDriver::query();
        $query->whereIn('gp_drivers.id', $ids);

        // Применяем фильтр по статусу
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        // Если $status === 'all' или null, то показываем всех
        $query->select(
            'gp_drivers.id as id',
            'gp_drivers.name as name',
            'gp_drivers.phone as phone',
            'gp_drivers.car_name as car_name',
            'gp_drivers.car_number as car_number',
            'gp_drivers.image as image',
            'gp_drivers.balance as balance',
            'gp_drivers.cash_client as cash_client',
            'gp_drivers.cash_service as cash_service',
            'gp_drivers.cash_goods as cash_goods',
            'gp_drivers.cash_company_balance as cash_company_balance',
            'gp_drivers.earning as earning',
            'gp_drivers.earning_pending as earning_pending',
            'gp_drivers.cash_wallet as cash_wallet',
            'gp_drivers.is_active as is_active',
            'gp_drivers.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
}
