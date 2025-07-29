<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\GpOrder;
use App\Repositories\Admin\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientOrdersController extends Controller
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Получение всех заказов клиента с пагинацией
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = $request->input('per_page', 20);
        
        // Получаем заказы клиента по номеру телефона
        $paginator = GpOrder::select('gp_orders.id as id')
            ->where('gp_orders.client_phone', $user->phone)
            ->orderBy('gp_orders.created_at', 'desc')
            ->paginate($perPage);

        $orderIds = $paginator->pluck('id')->toArray();
        
        if (empty($orderIds)) {
            return response()->json([
                'items' => [],
                'current_page' => $paginator->currentPage(),
                'next_page' => null,
                'last_page' => $paginator->lastPage(),
                'total' => 0
            ]);
        }

        // Получаем полную информацию о заказах используя OrderRepository
        $orders = $this->orderRepository->getItemsByIds($orderIds);
        
        // Сортируем заказы в том же порядке, что и в пагинации
        $orderedItems = $orders->sortBy(function ($item) use ($orderIds) {
            return array_search($item->id, $orderIds);
        })->values();

        $paginator->setCollection($orderedItems);

        return response()->json([
            'items' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'next_page' => $paginator->nextPageUrl(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total()
        ]);
    }

    /**
     * Получение информации о конкретном заказе клиента
     */
    public function getInfo($orderId)
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Проверяем, что заказ принадлежит клиенту
        $order = GpOrder::where('id', $orderId)
            ->where('client_phone', $user->phone)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or access denied'], 404);
        }

        // Получаем полную информацию о заказе используя OrderRepository
        $orderInfo = $this->orderRepository->getItemById($orderId);

        if (!$orderInfo) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($orderInfo);
    }
} 