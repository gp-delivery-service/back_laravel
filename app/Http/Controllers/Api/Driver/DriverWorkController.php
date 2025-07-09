<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Repositories\Driver\DriverPickupRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverWorkController extends Controller
{
    public function __construct(protected DriverPickupRepository $repository) {}


    public function availableFlow(Request $request) {

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
}
