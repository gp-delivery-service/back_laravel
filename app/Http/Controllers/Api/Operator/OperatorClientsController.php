<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\ClientRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorClientsController extends Controller
{
    protected $itemRepository;

    public function __construct(ClientRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->get('search');

        $items = $this->itemRepository->getItemsWithPagination($perPage, $search);

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function getInfo($clientId)
    {
        $client = $this->itemRepository->getItemById($clientId);

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        return response()->json($client);
    }
} 