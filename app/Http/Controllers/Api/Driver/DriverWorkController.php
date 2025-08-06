<?php

namespace App\Http\Controllers\Api\Driver;

use App\Constants\GpPickupOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\GpPickup;
use App\Models\GpPickupOrder;
use App\Repositories\Admin\OrderRepository;
use App\Repositories\Driver\DriverPickupRepository;
use App\Services\NodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverWorkController extends Controller
{
    public function __construct(
        protected DriverPickupRepository $repository,
        protected OrderRepository $orderRepository
    ) {}


    public function availableFlow(Request $request)
    {

        $availablePickups = $this->repository->availablePickups();

        return response()->json([
            'items' => $availablePickups,
        ]);
    }

    public function workflow(Request $request)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }
        $pickupIds = $this->repository->driverActivePickups($user->id);

        return response()->json([
            'items' => $pickupIds,
        ]);
    }

    public function closed(Request $request)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }
        $pickupIds = $this->repository->driverClosedPickups($user->id);

        return response()->json([
            'items' => $pickupIds,
        ]);
    }

    public function pickup($pickupId)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $pickup = $this->repository->getPickupById($pickupId, $user->id);

        if (!$pickup) {
            return response()->json([
                'message' => 'Pickup not found or unauthorized',
                'status' => false,
            ], 404);
        }

        return response()->json($pickup);
    }

    // Водитель забрал заказы с заведения
    public function pickupPickedUp($pickupId)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $pickup = $this->repository->getPickupById($pickupId, $user->id);

        if (!$pickup) {
            return response()->json([
                'message' => 'Pickup not found or unauthorized',
                'status' => false,
            ], 404);
        }
        try {
            $updated_pickup =  $this->repository->markPickupAsPickedUp($pickupId, $user->id);
            $this->orderRepository->setPickupOrdersAccepted($pickupId);
            NodeService::callPickupsRefresh();
            return response()->json($updated_pickup);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error marking pickup as picked up: ' . $e->getMessage(),
                'status' => false,
            ], 500);
        }
    }

    public function pickupClose($pickupId)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }


        $pickup = $this->repository->getPickupById($pickupId, $user->id);

        if (!$pickup) {
            return response()->json([
                'message' => 'Pickup not found or unauthorized',
                'status' => false,
            ], 404);
        }

        try {
            $updated_pickup =  $this->repository->markPickupAsClosed($pickup->id, $user->id);
            NodeService::callPickupsRefresh();
            return response()->json($updated_pickup);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error marking pickup as picked up: ' . $e->getMessage(),
                'status' => false,
            ], 500);
        }

        return response()->json($pickup);
    }


    public function orderClose(Request $request)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $validatedData = $request->validate([
            'pickup_order_id' => 'required|integer|exists:gp_pickup_orders,id',
        ]);

        $pickupOrder = GpPickupOrder::find($validatedData['pickup_order_id']);

        $driverHasPickups = GpPickup::where('driver_id', $user->id)
            ->where('id', $pickupOrder->pickup_id)
            ->exists();

        $orderCanBeClosed = in_array($pickupOrder->status, [
            GpPickupOrderStatus::INHERITED,
            GpPickupOrderStatus::ACCEPTED,
            GpPickupOrderStatus::WAITING_CLIENT,
        ]);

        if ($pickupOrder == null || !$driverHasPickups || !$orderCanBeClosed) {
            return response()->json([
                'message' => 'Pickup or order not found or unauthorized',
                'status' => false,
            ], 404);
        }

        $pickup = $this->repository->makeOrderAsClosed($pickupOrder->id, $user->id);
        NodeService::callPickupsRefresh();

        return response()->json($pickup);
    }

    /**
     * Водитель прибыл к клиенту - установить статус "ожидает клиента"
     */
    public function orderWaitingClient(Request $request)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $validatedData = $request->validate([
            'pickup_order_id' => 'required|integer|exists:gp_pickup_orders,id',
        ]);

        $pickupOrder = GpPickupOrder::find($validatedData['pickup_order_id']);

        $driverHasPickups = GpPickup::where('driver_id', $user->id)
            ->where('id', $pickupOrder->pickup_id)
            ->exists();

        $orderCanBeWaiting = in_array($pickupOrder->status, [
            GpPickupOrderStatus::INHERITED,
            GpPickupOrderStatus::ACCEPTED,
        ]);

        if ($pickupOrder == null || !$driverHasPickups || !$orderCanBeWaiting) {
            return response()->json([
                'message' => 'Pickup or order not found or unauthorized',
                'status' => false,
            ], 404);
        }

        try {
            $pickup = $this->repository->setOrderWaitingClient($pickupOrder->id, $user->id);
            NodeService::callPickupsRefresh();
            return response()->json($pickup);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error setting order as waiting client: ' . $e->getMessage(),
                'status' => false,
            ], 500);
        }
    }
}
