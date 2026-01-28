<?php

namespace App\Repositories\Admin;

use App\Models\GpOperator;
use Illuminate\Support\Facades\DB;

class OperatorRepository
{
    // Получение с пагинацией (поиск по имени и email)
    public function getItemsWithPagination($userUuid, $perPage = 20, $status = null, $search = null)
    {
        $query = GpOperator::select('gp_operators.id as id');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (!empty($search)) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
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

    // Получение одного оператора по ид
    public function getItemById($id)
    {
        $item = GpOperator::find($id);
        if (!$item) {
            return null;
        }
        $item = $this->getItems([$item->id])->first();

        if (!$item) {
            return null;
        }

        return $item;
    }

    // Получение короткого списка операторов
    public function getShortList()
    {
        $all_ids = GpOperator::where('blocked', false)->pluck('id')->toArray();
        $items = $this->getItems($all_ids);
        return $items;
    }

    // Получение короткого списка кассиров
    public function getShortListCashiers()
    {
        $all_ids = GpOperator::where('blocked', false)->where('cashier', true)->pluck('id')->toArray();
        $items = $this->getItems($all_ids);
        return $items;
    }

    // Создание
    public function create(array $data)
    {
        $data['password'] = bcrypt($data['password']);
        $created = GpOperator::create($data);
        return $created;
    }

    // Обновление
    public function update($id, array $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        $operator = GpOperator::find($id);
        $updated = $operator->update($data);
        return $updated;
    }

    public function getItemsByIds(array $ids = [], $status = null)
    {
        return $this->getItems($ids, $status);
    }

    private function getItems(array $ids = [], $status = null)
    {
        $query = GpOperator::query();
        $query->whereIn('gp_operators.id', $ids);

        // Применяем фильтр по статусу
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        // Если $status === 'all' или null, то показываем всех

        $query->select(
            'gp_operators.id as id',
            'gp_operators.name as name',
            'gp_operators.email as email',
            'gp_operators.cashier as cashier',
            'gp_operators.cash as cash',
            'gp_operators.is_active as is_active',
            'gp_operators.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
}
