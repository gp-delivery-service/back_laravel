<?php

namespace App\Http\Controllers\Api\Manager;

use App\Constants\GpPickupStatus;
use App\Http\Controllers\Controller;
use App\Models\GpOrder;
use App\Repositories\Manager\ManagerPickupRepository;
use App\Services\NodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ManagerPickupController extends Controller
{
    public function __construct(protected ManagerPickupRepository $repository) {}


    public function index(Request $request)
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && !$user->company_id) {
            return response()->json([
                'mesage' => "Компания не указана"
            ], 403);
        }

        $filters = $request->only([
            'status',
            'search_id',
            'search_note',
            'search_driver',
            'driver_id',
            'company_id',
            'date_from',
            'date_to'
        ]);

        // Обрабатываем фильтр по водителю
        if (!empty($filters['driver_id'])) {
            // Если передан ID водителя, используем его для точного поиска
            $filters['driver_id'] = $filters['driver_id'];
        }

        $request->validate([
            'status' => 'nullable|string|in:' . implode(',', array_column(GpPickupStatus::cases(), 'value')),
            'search_id' => 'nullable|string|max:50',
            'search_note' => 'nullable|string|max:255',
            'search_driver' => 'nullable|string|max:255',
            'driver_id' => 'nullable|string|exists:gp_drivers,id',
            'company_id' => 'nullable|string|exists:gp_companies,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        $items = $this->repository->getItemsWithPagination($user->id, $user->company_id, 20, $filters);

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'company_id' => 'required|string|exists:gp_companies,id',
            'note' => 'nullable|string',
            'preparing_time' => 'nullable|integer',
            'order_ids' => 'nullable|array',
            'order_ids.*' => 'integer|exists:gp_orders,id'
        ]);

        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && $user->company_id !== $data['company_id']) {
            return response()->json([
                'mesage' => "Вы не можете создавать заказы для другой компании"
            ], 403);
        }

        $created = $this->repository->create($data);
        if (!$created) {
            return response()->json([
                'message' => 'An error occurred while creating the pickup.',
            ], 500);
        }
        return response()->json(['message' => 'Pickup created']);
    }

    public function quickStore(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'order_id' => 'required|integer|exists:gp_orders,id'
        ]);

        $order = GpOrder::find($data['order_id']);
        if (!$order || $order->archived) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        $data['company_id'] = $order->company_id;
        $data['note'] = '';
        $data['preparing_time'] = null;
        $data['order_ids'] = [$data['order_id']];

        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && $user->company_id !== $data['company_id']) {
            return response()->json([
                'mesage' => "Вы не можете создавать заказы для другой компании"
            ], 403);
        }

        $created = $this->repository->create($data);
        if (!$created) {
            return response()->json([
                'message' => 'An error occurred while creating the pickup.',
            ], 500);
        }

        return response()->json(['message' => 'Pickup created']);
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();

        $data = $request->validate([
            'company_id' => 'required|string|exists:gp_companies,id',
            'note' => 'nullable|string',
            'preparing_time' => 'nullable|integer',
            'order_ids' => 'nullable|array',
            'order_ids.*' => 'integer|exists:gp_orders,id',
        ]);

        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'manager' && $user->company_id !== $data['company_id']) {
            return response()->json([
                'mesage' => "Вы не можете создавать заказы для другой компании"
            ], 403);
        }

        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            return response()->json([
                'message' => 'An error occurred while updating the pickup.',
            ], 500);
        }

        return response()->json(['message' => 'Pickup updated']);
    }

    public function addOrders(Request $request, int $id)
    {
        $data = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:gp_orders,id',
        ]);

        try {
            return $this->repository->addOrders($id, $data['order_ids']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while adding orders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeOrders(Request $request, int $id)
    {
        $data = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:gp_orders,id',
        ]);

        return $this->repository->removeOrders($id, $data['order_ids']);
    }

    public function changeStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::enum(GpPickupStatus::class)],
            'note' => 'nullable|string',
        ]);

        $updated = $this->repository->switchStatus($id, $data['status']);


        if (!$updated) {
            return response()->json([
                'message' => 'An error occurred while updating the pickup.',
            ], 500);
        }

        NodeService::callServiceRefresh();
        NodeService::callVisibilityUpdate();

        return response()->json(['message' => 'Pickup updated']);
    }


    public function calls()
    {
        $items = $this->repository->getCallItems();
        try {
            logger()->info('XNode calls payload built', [
                'count' => is_iterable($items) ? count($items) : null,
                'type' => is_object($items) ? get_class($items) : gettype($items),
            ]);
        } catch (\Throwable $e) {}
        return response()->json($items);
    }

    private $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager'
    ];
}
