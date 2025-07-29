<?php

namespace App\Repositories\Admin;

use App\Models\GpClient;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientRepository
{
    public function getItemsWithPagination($perPage = 20): LengthAwarePaginator
    {
        $paginator = GpClient::select('gp_clients.id as id')
            ->orderBy('gp_clients.created_at', 'desc')
            ->paginate($perPage);

        $ids = $paginator->items();
        $ids = collect($ids)->pluck('id')->toArray();

        $items = $this->getItems($ids);
        $paginator->setCollection($items);

        return $paginator;
    }

    public function getItemById($clientId)
    {
        $items = $this->getItems([$clientId]);
        return $items->first();
    }

    public function getItemsByIds(array $ids = [])
    {
        return $this->getItems($ids);
    }

    private function getItems(array $ids = [])
    {
        $query = GpClient::query();
        $query->whereIn('gp_clients.id', $ids);
        $query->select(
            'gp_clients.id as id',
            'gp_clients.name as name',
            'gp_clients.phone as phone',
            'gp_clients.wallet as wallet',
            'gp_clients.created_at as created_at'
        );
        $items = $query->get();
        return $items;
    }
} 