<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpOperator;
use App\Repositories\Admin\OperatorRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOperatorsController extends Controller
{

    protected $itemRepository;

    public function __construct(OperatorRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function index()
    {
        $user = Auth::guard('api_admin')->user();

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
        $user = Auth::guard('api_admin')->user();

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:gp_operators,email',
            'password' => 'required|string',
        ]);

        $created = $this->itemRepository->create($validated);

        if(!$created) {
            return response()->json(['error' => 'Error creating operator'], 500);
        }

        return response()->json(['message' => 'Operator created']);
    }

    public function update($id, Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        $validated = $request->validate([
            'name' => 'required|string',
            'password' => 'nullable|sometimes|string',
        ]);

        $updated = $this->itemRepository->update($id, $validated);

        if(!$updated) {
            return response()->json(['error' => 'Error updating operator'], 500);
        }

        return response()->json(['message' => 'Operator updated']);
    }
}
