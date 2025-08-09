<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\DriverRepository;
use App\Models\GpDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperatorDriversController extends Controller
{

    protected $itemRepository;

    public function __construct(DriverRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Получаем параметр статуса из запроса
        $status = $request->get('status');

        Log::info('Drivers API called with status: ' . $status);

        $items = $this->itemRepository->getItemsWithPagination($user->id, 20, $status);

        Log::info('Drivers API returned ' . count($items->items()) . ' items');

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function getInfo($driverId)
    {
        $driver = $this->itemRepository->getItemById($driverId);

        if (!$driver) {
            return response()->json(['error' => 'Driver not found'], 404);
        }

        return response()->json($driver);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:gp_drivers,phone',
            'car_name' => 'required|string',
            'car_number' => 'required|string'
        ]);

        $created = $this->itemRepository->create($validated);

        if (!$created) {
            return response()->json(['error' => 'Error creating driver'], 500);
        }

        return response()->json(['message' => 'Driver created']);
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => [
                'required',
                'string',
                'unique:gp_drivers,phone,' . $id,
            ],
            'car_name' => 'required|string',
            'car_number' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        // Проверка: нельзя отключить водителя с ненулевыми балансами
        if (isset($validated['is_active']) && $validated['is_active'] === false) {
            $driver = $this->itemRepository->getItemById($id);
            
            if ($driver && (
                $driver->cash_client != 0 ||
                $driver->cash_service != 0 ||
                $driver->cash_goods != 0 ||
                $driver->cash_company_balance != 0 ||
                $driver->cash_wallet != 0
            )) {
                return response()->json([
                    'error' => 'Нельзя отключить водителя с ненулевыми балансами. Сначала закройте все кассы.'
                ], 422);
            }
        }

        $updated = $this->itemRepository->update($id, $validated);

        if (!$updated) {
            return response()->json(['error' => 'Error updating driver'], 500);
        }

        return response()->json(['message' => 'Driver updated']);
    }

    public function delete($id)
    {
        $deleted = $this->itemRepository->delete($id);
        if (!$deleted) {
            return response()->json(['error' => 'Driver not found'], 404);
        }
        return response()->json(['message' => 'Driver deleted']);
    }
}
