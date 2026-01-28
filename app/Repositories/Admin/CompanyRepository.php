<?php

namespace App\Repositories\Admin;

use App\Models\GpCompany;
use Illuminate\Support\Facades\DB;

class CompanyRepository
{
    // Получение с пагинацией (поиск по названию и адресу)
    public function getItemsWithPagination($userUuid, $perPage = 20, $search = null)
    {
        $query = GpCompany::select('gp_companies.id as id');

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('address', 'like', $term);
            });
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

    // Получение всех компаний, короткий список
    public function getAllItems()
    {
        $items = $this->getShortItems();
        return $items;
    }

    // Получение одной компании
    public function getItemById($id)
    {
        $item = GpCompany::find($id);
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
            'gp_companies.phone as phone',
            'gp_companies.count as count',
            'gp_companies.balance as balance',
            'gp_companies.agregator_side_balance as agregator_side_balance',
            'gp_companies.credit_balance as credit_balance',
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
