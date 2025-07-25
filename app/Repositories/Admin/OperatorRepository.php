<?php

namespace App\Repositories\Admin;

use App\Models\GpOperator;
use Illuminate\Support\Facades\DB;

class OperatorRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($userUuid, $perPage = 20)
    {
        $paginator = GpOperator::select('gp_operators.id as id')
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

    public function getItemsByIds(array $ids = [])
    {
        return $this->getItems($ids);
    }

    private function getItems(array $ids = [])
    {
        $query = GpOperator::query();
        $query->whereIn('gp_operators.id', $ids);
        $query->select(
            'gp_operators.id as id',
            'gp_operators.name as name',
            'gp_operators.email as email',
            'gp_operators.cashier as cashier',
            'gp_operators.cash as cash',
            'gp_operators.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
}
