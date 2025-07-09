<?php

namespace App\Repositories\Admin;

use App\Models\GpOrder;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $company_id, $perPage = 20)
    {
        $paginator = GpOrder::select('gp_orders.id as id')
            ->when($company_id !== null, function ($query) use ($company_id) {
                $query->where('gp_orders.company_id', $company_id);
            })
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

    // Получение всех заказов без записи в gp_pickup_orders
    public function getOpenOrders($company_id)
    {
        $orders = GpOrder::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('gp_pickup_orders')
                ->whereRaw('gp_pickup_orders.order_id = gp_orders.id');
        })->when($company_id !== null, function ($query) use ($company_id) {
            $query->where('gp_orders.company_id', $company_id);
        })
            ->orderBy('created_at', 'desc')
            ->pluck('id')
            ->toArray();

        $orders = $this->getItems($orders);

        return $orders;
    }

    // Создание
    public function create(array $data)
    {
        $created = GpOrder::create($data);
        return $created;
    }

    // Обновление
    public function update($id, array $data)
    {
        $item = GpOrder::find($id);
        $updated = $item->update($data);
        return $updated;
    }


    private function getItems(array $ids = [])
    {
        $query = GpOrder::query();
        $query->whereIn('gp_orders.id', $ids);
        $query->leftJoin('gp_companies', 'gp_orders.company_id', '=', 'gp_companies.id');
        $query->leftJoin('gp_map_districts', 'gp_orders.district_id', '=', 'gp_map_districts.id');
        $query->leftJoin('gp_map_streets as streets', 'gp_orders.street_id', '=', 'streets.id');
        $query->leftJoin('gp_map_streets as second_streets', 'gp_orders.second_street_id', '=', 'second_streets.id');
        $query->select(
            'gp_orders.id as id',
            //
            'gp_orders.company_id as company_id',
            'gp_companies.name as company_name',
            //
            'gp_orders.number as number',
            'gp_orders.client_phone as client_phone',
            'gp_orders.sum as sum',
            'gp_orders.delivery_price as delivery_price',
            //
            'gp_map_districts.id as district_id',
            'gp_map_districts.name as district_name',
            'streets.id as street_id',
            'streets.name as street_name',
            'second_streets.id as second_street_id',
            'second_streets.name as second_street_name',
            'gp_orders.geo_comment as geo_comment',
            'gp_orders.lat as lat',
            'gp_orders.lng as lng',
            //
            'gp_orders.created_at as created_at',
            'gp_orders.updated_at as updated_at'
        );
        $items = $query->get();
        return $items;
    }
}
