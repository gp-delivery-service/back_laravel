<?php

namespace App\Repositories\Admin;

use App\Models\GpCompany;
use Illuminate\Support\Facades\DB;

class CompanyRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $perPage = 20)
    {
        $paginator = GpCompany::select('gp_companies.id as id')
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

    // Получение всех компаний, короткий список
    public function getAllItems()
    {
        $items = $this->getShortItems();
        return $items;
    }

    // Создание
    public function create(array $data)
    {
        $created = GpCompany::create($data);
        return $created;
    }

    // Обновление
    public function update($id, array $data)
    {
        $item = GpCompany::find($id);
        $updated = $item->update($data);
        return $updated;
    }

    private function getItems(array $ids = [])
    {
        $query = GpCompany::query();
        $query->whereIn('gp_companies.id', $ids);
        $query->select(
            'gp_companies.id as id',
            'gp_companies.name as name',
            'gp_companies.address as address',
            'gp_companies.lat as lat',
            'gp_companies.lng as lng',
            'gp_companies.image as image',
            'gp_companies.created_at as created_at',
            'gp_companies.updated_at as updated_at',
        );
        $items = $query->get();
        return $items;
    }
    private function getShortItems()
    {
        $query = GpCompany::query();
        $query->select(
            'gp_companies.id as id',
            'gp_companies.name as name'
        );
        $items = $query->get();
        return $items;
    }
}
