<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\GpOrder;
use App\Repositories\Admin\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerOrderController extends Controller
{
    protected $itemRepository;

    public function __construct(OrderRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }


    public function index()
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && !$user->company_id) {
            return response()->json([
                'mesage' => "Компания не указана"
            ], 403);
        }


        $items = $this->itemRepository->getItemsWithPagination($user->id, $user->company_id, 20);

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function allOpen()
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && !$user->company_id) {
            return response()->json([
                'mesage' => "Компания не указана"
            ], 403);
        }


        $items = $this->itemRepository->getOpenOrders($user->company_id);

        return response()->json($items);
    }


    public function create(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'client_phone' => 'required|string',
            'number' => 'required|string',
            'sum' => 'required|numeric',
            'delivery_price' => 'required|numeric',
            'company_id' => 'required|string|exists:gp_companies,id',
            'geo_comment' => 'nullable|string',
            'district_id' => 'nullable|integer|exists:gp_map_districts,id',
            'street_id' => 'nullable|integer|exists:gp_map_streets,id',
            'second_street_id' => 'nullable|integer|exists:gp_map_streets,id',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string'
        ]);

        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && $user->company_id !== $validated['company_id']) {
            return response()->json([
                'mesage' => "Вы не можете создавать заказы для другой компании"
            ], 403);
        }


        $created = $this->itemRepository->create($validated);

        if (!$created) {
            return response()->json(['error' => 'Error creating order'], 500);
        }

        return response()->json(['message' => 'Order created']);
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'number' => 'required|string',
            'sum' => 'required|numeric'
        ]);

        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        $updating_item = GpOrder::find($id);
        if ($role === 'manager' && $user->company_id !== $updating_item->company_id) {
            return response()->json([
                'mesage' => "Вы не можете редактировать заказы другой компании"
            ], 403);
        }


        $updated = $this->itemRepository->update($id, $validated);

        if (!$updated) {
            return response()->json(['error' => 'Error updating order'], 500);
        }

        return response()->json(['message' => 'Order updated']);
    }


    private $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager',
        'api_driver' => 'driver',
    ];
}
