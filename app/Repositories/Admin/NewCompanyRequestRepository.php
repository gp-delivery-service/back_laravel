<?php

namespace App\Repositories\Admin;

use App\Models\NewCompanyRequest;
use Illuminate\Support\Facades\DB;

class NewCompanyRequestRepository
{
    // Получение с пагинацией
    public function getItemsWithPagination($perPage = 20)
    {
        $paginator = NewCompanyRequest::select('new_company_requests.id as id')
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

    // Получение всех заявок
    public function getAllItems()
    {
        $items = $this->getShortItems();
        return $items;
    }

    // Получение одной заявки
    public function getItemById($id)
    {
        $item = NewCompanyRequest::find($id);
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
        $created = NewCompanyRequest::create($data);
        return $created;
    }

    // Удаление
    public function delete($id)
    {
        $item = NewCompanyRequest::find($id);
        if (!$item) {
            return false;
        }
        return $item->delete();
    }

    // Обновление статуса
    public function updateStatus($id, $status)
    {
        $item = NewCompanyRequest::find($id);
        if (!$item) {
            return false;
        }
        $item->status = $status;
        return $item->save();
    }
    // Получение количества заявок со статусом pending
    public function getPendingCount()
    {
        return NewCompanyRequest::where('status', NewCompanyRequest::STATUS_PENDING)->count();
    }

    private function getItems(array $ids = [])
    {
        $query = NewCompanyRequest::query();
        $query->whereIn('new_company_requests.id', $ids);
        $query->select(
            'new_company_requests.id as id',
            'new_company_requests.company_name as company_name',
            'new_company_requests.phone as phone',
            'new_company_requests.status as status',
            'new_company_requests.created_at as created_at',
            'new_company_requests.updated_at as updated_at',
        );
        $items = $query->get();
        return $items;
    }

    private function getShortItems()
    {
        $query = NewCompanyRequest::query();
        $query->select(
            'new_company_requests.id as id',
            'new_company_requests.company_name as company_name'
        );
        $items = $query->get();
        return $items;
    }
}
