<?php

namespace App\Http\Controllers\Api\Manager;

use App\Constants\GpPickupStatus;
use App\Http\Controllers\Controller;
use App\Models\GpOrder;
use App\Repositories\Admin\OrderRepository;
use App\Repositories\Manager\ManagerPickupRepository;
use App\Services\NodeService;
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

    public function getOrderFields($id){
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && !$user->company_id) {
            return response()->json([
                'mesage' => "Компания не указана"
            ], 403);
        }

        $item = $this->itemRepository->getOrderAvailableFields($id);

        return response()->json(['fields' => $item]);
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

    public function show($id)
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && !$user->company_id) {
            return response()->json([
                'mesage' => "Компания не указана"
            ], 403);
        }

        $item = $this->itemRepository->getItemById($id);

        return response()->json($item);
    }



    public function create(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'client_phone' => 'required|string',
            'number' => 'required|string',
            'sum' => 'required|numeric',
            'delivery_price' => 'required|numeric',
            'delivery_pay' => 'required|string|in:balance,cash,client',
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
            'sum' => 'required|numeric',
            'delivery_pay' => 'required|string|in:balance,cash,client',
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


    public function createOrderWithPickupAndSearchDriver(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'client_phone' => 'required|string',
            'number' => 'required|string',
            'sum' => 'required|numeric',
            'delivery_price' => 'required|numeric',
            'delivery_pay' => 'required|string|in:balance,cash,client',
            'company_id' => 'required|string|exists:gp_companies,id',
            'geo_comment' => 'nullable|string',
            'district_id' => 'nullable|integer|exists:gp_map_districts,id',
            'street_id' => 'nullable|integer|exists:gp_map_streets,id',
            'second_street_id' => 'nullable|integer|exists:gp_map_streets,id',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'date' => 'nullable|integer',
        ]);

        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && $user->company_id !== $validated['company_id']) {
            return response()->json([
                'message' => "Вы не можете создавать заказы для другой компании"
            ], 403);
        }

        // ✅ Создание заказа
        $order = $this->itemRepository->create($validated);

        if (!$order) {
            return response()->json(['error' => 'Ошибка при создании заказа'], 500);
        }

        // ✅ Создание пикапа
        $pickupData = [
            'company_id' => $order->company_id,
            'note' => '',
            'preparing_time' => $validated['date'] ?? null,
            'order_ids' => [$order->id],
        ];

        $pickup = app(ManagerPickupRepository::class)->create($pickupData);

        if (!$pickup) {
            return response()->json(['error' => 'Ошибка при создании пикапа'], 500);
        }

        // ✅ Перевод пикапа в статус "поиск водителя"
        $statusUpdated = app(ManagerPickupRepository::class)->switchStatus($pickup->id, GpPickupStatus::REQUESTED->value);

        if (!$statusUpdated) {
            return response()->json(['error' => 'Ошибка при запуске поиска водителя'], 500);
        }

        // Дополнительно: вызвать обновление ноды
        NodeService::callServiceRefresh();
        NodeService::callPickupsRefresh();
        NodeService::callVisibilityUpdate();

        return response()->json([
            'message' => 'Заказ, пикап и поиск водителя успешно запущены',
            'order_id' => $order->id,
            'pickup_id' => $pickup->id,
        ]);
    }


    private $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager',
        'api_driver' => 'driver',
        'api_client' => 'client',
    ];
}
