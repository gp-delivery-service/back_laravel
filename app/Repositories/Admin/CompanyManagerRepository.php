<?php

namespace App\Repositories\Admin;

use App\Models\GpCompanyManager;
use Illuminate\Support\Facades\DB;

class CompanyManagerRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $company_id, $perPage = 20, $status = null)
    {
        $query = GpCompanyManager::select('gp_company_managers.id as id')
            ->where('gp_company_managers.company_id', $company_id);

        // Применяем фильтр по статусу
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        // Если $status === 'all' или null, то показываем всех

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

        // Убеждаемся, что is_active передается как boolean
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $item = GpCompanyManager::find($id);
        if (!$item) {
            return false; // Добавить проверку на null
        }
        $updated = $item->update($data);
        return $updated;
    }

    // Удаление
    public function delete($id): bool
    {
        $item = GpCompanyManager::find($id);
        if (!$item) {
            return false;
        }
        return (bool) $item->delete();
    }

    private function getItems(array $ids = [], $status = null)
    {
        $query = GpCompanyManager::query();
        $query->whereIn('gp_company_managers.id', $ids);

        // Применяем фильтр по статусу
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        // Если $status === 'all' или null, то показываем всех
        $query->select(
            'gp_company_managers.id as id',
            'gp_company_managers.name as name',
            'gp_company_managers.email as email',
            'gp_company_managers.company_id as company_id',
            'gp_company_managers.is_active as is_active',
            'gp_company_managers.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
}
