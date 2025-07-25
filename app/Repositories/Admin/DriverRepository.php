<?php

namespace App\Repositories\Admin;

use App\Models\GpDriver;
use Illuminate\Support\Facades\DB;

class DriverRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $perPage = 20)
    {
        $paginator = GpDriver::select('gp_drivers.id as id')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        $items_ids = $paginator->pluck('id')->toArray();
        $items = $this->getItems($items_ids);
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
        $operator = GpDriver::find($id);
        $updated = $operator->update($data);
        return $updated;
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

    public function getItemsByIds(array $ids = [])
    {
        return $this->getItems($ids);
    }

    private function getItems(array $ids = [])
    {
        $query = GpDriver::query();
        $query->whereIn('gp_drivers.id', $ids);
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
            'gp_drivers.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
}
