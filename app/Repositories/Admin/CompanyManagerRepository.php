<?php

namespace App\Repositories\Admin;

use App\Models\GpCompanyManager;
use Illuminate\Support\Facades\DB;

class CompanyManagerRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $company_id, $perPage = 20)
    {
        $paginator = GpCompanyManager::select('gp_company_managers.id as id')
            ->where('gp_company_managers.company_id', $company_id)
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
        $data['password'] = bcrypt($data['password']);
        $created = GpCompanyManager::create($data);
        return $created;
    }

    // Обновление
    public function update($id, array $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }else{
            unset($data['password']);
        }
        unset($data['company_id']);
        $item = GpCompanyManager::find($id);
        $updated = $item->update($data);
        return $updated;
    }

    private function getItems(array $ids = [])
    {
        $query = GpCompanyManager::query();
        $query->whereIn('gp_company_managers.id', $ids);
        $query->select(
            'gp_company_managers.id as id',
            'gp_company_managers.name as name',
            'gp_company_managers.email as email',
            'gp_company_managers.company_id as company_id',
            'gp_company_managers.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
}
