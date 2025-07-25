<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpOperator;
use App\Repositories\Admin\OperatorRepository;
use App\Repositories\Balance\OperatorBalanceRepository;
use App\Repositories\Balance\OperatorTransactionsRepository;
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

    public function getInfo($operatorId)
    {
        $operator = $this->itemRepository->getItemById($operatorId);

        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        return response()->json($operator);
    }

    public function create(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:gp_operators,email',
            'password' => 'required|string',
            'cashier' => 'nullable|boolean',
        ]);

        $validated['cashier'] = (bool) ($validated['cashier'] ?? false);
        $created = $this->itemRepository->create($validated);

        if (!$created) {
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
            'cashier' => 'nullable|boolean',
        ]);

        $validated['cashier'] = (bool) ($validated['cashier'] ?? false);

        $operator = GpOperator::find($id);

        if (
            isset($validated['cashier']) &&
            $operator->cashier == true &&
            $validated['cashier'] === false &&
            $operator->cash != 0
        ) {
            return response()->json([
                'error' => 'Нельзя снять роль кассира у оператора с ненулевым кассовым балансом.'
            ], 422);
        }

        $updated = $this->itemRepository->update($id, $validated);

        if (!$updated) {
            return response()->json(['error' => 'Error updating operator'], 500);
        }

        return response()->json(['message' => 'Operator updated']);
    }

    public function clearCash(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'operator_id' => 'required|string|exists:gp_operators,id'
        ]);

        $operator = GpOperator::find($validated['operator_id']);

        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        if ($operator->cashier == false) {
            return response()->json(['error' => 'Operator is not a cashier'], 422);
        }

        if ($operator->cash == 0) {
            return response()->json(['error' => 'Operator cash is already zero'], 422);
        }

        $operatorTransactionRepository = new OperatorTransactionsRepository(new OperatorBalanceRepository);


        $result = $operatorTransactionRepository->cash_decrease($operator->id, $validated['amount']);
        if (!$result) {
            return response()->json(['error' => 'Error clearing operator cash'], 500);
        }
        return response()->json(['message' => 'Operator cash cleared']);
    }

    public function addCash(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'operator_id' => 'required|string|exists:gp_operators,id'
        ]);

        $operator = GpOperator::find($validated['operator_id']);

        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        if ($operator->cashier == false) {
            return response()->json(['error' => 'Operator is not a cashier'], 422);
        }

        if ($operator->cash == 0) {
            return response()->json(['error' => 'Operator cash is already zero'], 422);
        }

        $operatorTransactionRepository = new OperatorTransactionsRepository(new OperatorBalanceRepository);


        $result = $operatorTransactionRepository->cash_increase($operator->id, $validated['amount']);
        if (!$result) {
            return response()->json(['error' => 'Error adding operator cash'], 500);
        }
        return response()->json(['message' => 'Operator cash added']);
    }
}
