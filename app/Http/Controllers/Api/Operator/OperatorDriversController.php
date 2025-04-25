<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\DriverRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorDriversController extends Controller
{

    protected $itemRepository;

    public function __construct(DriverRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index()
    {
        $user = Auth::user();

        $items = $this->itemRepository->getItemsWithPagination($user->id, 20);

        return response()->json([
            'items' => $items->items(),
            'current_page' => $items->currentPage(),
            'next_page' => $items->nextPageUrl(),
            'last_page' => $items->lastPage(),
            'total' => $items->total()
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string|unique:gp_drivers,phone'
        ]);

        $created = $this->itemRepository->create($validated);

        if(!$created) {
            return response()->json(['error' => 'Error creating driver'], 500);
        }

        return response()->json(['message' => 'Driver created']);
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string'
        ]);

        $updated = $this->itemRepository->update($id, $validated);

        if(!$updated) {
            return response()->json(['error' => 'Error updating driver'], 500);
        }

        return response()->json(['message' => 'Driver updated']);
    }
}
