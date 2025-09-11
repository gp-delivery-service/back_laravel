<?php

namespace App\Http\Controllers\Api\Driver;

use App\Constants\GpPickupStatus;
use App\Http\Controllers\Controller;
use App\Models\GpPickup;
use App\Repositories\Manager\ManagerPickupRepository;
use App\Services\NodeService;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;

class DriverPickupController extends Controller
{
    public function __construct(protected ManagerPickupRepository $repository) {}


    public function takePickup(Request $request)
    {
        $validated = $request->validate([
            'pickup_id' => 'required|integer|exists:gp_pickups,id',
            'driver_uuid' => 'required|string|exists:gp_drivers,id',
        ]);

        // Устанавливаем пользователя вручную для логирования
        LogHelper::setManualUser($validated['driver_uuid'], 'App\\Models\\GpDriver');

        $pickup = GpPickup::findOrFail($validated['pickup_id']);

        $openForDrivers = GpPickupStatus::openForDrivers();

        if (!in_array($pickup->status->value, $openForDrivers) || $pickup->driver_id !== null) {
            return response()->json([
                'message' => 'Pickup is not available for drivers',
                // 'statuses' => $openForDrivers,
                // 'status' => $pickup->status,
                // 'pickup_driver_id' => $pickup->driver_id
            ], 400);
        }

        $setted = $this->repository->setDriverToPickup(
            $validated['pickup_id'],
            $validated['driver_uuid']
        );

        if (!$setted) {
            return response()->json([
                'message' => 'Failed to assign pickup to driver',
            ], 500);
        }

        NodeService::callPickupsRefresh();
        NodeService::callVisibilityUpdate();

        return response()->json([
            'status' => true,
            'message' => 'Pickup assigned to driver successfully',
            'pickup_id' => $validated['pickup_id'],
            'driver_uuid' => $validated['driver_uuid'],
        ]);
    }
}
