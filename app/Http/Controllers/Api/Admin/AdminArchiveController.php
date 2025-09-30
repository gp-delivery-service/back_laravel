<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpOrder;
use App\Models\GpPickup;
use Illuminate\Http\Request;

class AdminArchiveController extends Controller
{
    public function archiveOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:gp_orders,id',
        ]);

        try {
            $order = GpOrder::findOrFail($validated['order_id']);
            $order->update(['archive' => true]);

            return response()->json([
                'message' => 'Order archived successfully',
                'order_id' => $validated['order_id']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while archiving order',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function archivePickup(Request $request)
    {
        $validated = $request->validate([
            'pickup_id' => 'required|integer|exists:gp_pickups,id',
        ]);

        try {
            $pickup = GpPickup::findOrFail($validated['pickup_id']);
            $pickup->update(['archive' => true]);

            return response()->json([
                'message' => 'Pickup archived successfully',
                'pickup_id' => $validated['pickup_id']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while archiving pickup',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager',
        'api_driver' => 'driver',
        'api_client' => 'client',
    ];
}
