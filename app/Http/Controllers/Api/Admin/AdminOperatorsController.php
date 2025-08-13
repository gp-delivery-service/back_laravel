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

    public function index(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        // Получаем параметр статуса из запроса
        $status = $request->get('status');

        $items = $this->itemRepository->getItemsWithPagination($user->id, 20, $status);

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
            'is_active' => 'nullable|boolean',
        ]);

        $validated['cashier'] = (bool) ($validated['cashier'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
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
            'is_active' => 'nullable|boolean',
        ]);

        $validated['cashier'] = (bool) ($validated['cashier'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

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

        // Проверка: нельзя отключить оператора с незакрытой кассой
        if (
            isset($validated['is_active']) &&
            $operator->is_active == true &&
            $validated['is_active'] === false &&
            $operator->cashier == true &&
            $operator->cash != 0
        ) {
            return response()->json([
                'error' => 'Нельзя отключить оператора-кассира с ненулевым кассовым балансом. Сначала закройте кассу.'
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

        if ($operator->cash < $validated['amount']) {
            return response()->json(['error' => 'Insufficient cash in operator account'], 422);
        }

        try {
            $adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
            $result = $adminFundRepository->closeOperatorCash($operator->id, $validated['amount']);
            
            if (!$result) {
                return response()->json(['error' => 'Error clearing operator cash'], 500);
            }
            
            return response()->json(['message' => 'Operator cash cleared']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
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

        try {
            $adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
            $result = $adminFundRepository->addCashToOperator($operator->id, $validated['amount']);
            
            if (!$result) {
                return response()->json(['error' => 'Error adding operator cash'], 500);
            }
            
            return response()->json(['message' => 'Operator cash added']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function getOperatorStatus($operatorId)
    {
        $operator = GpOperator::find($operatorId);

        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        // Проверяем, включена ли смена у оператора
        $hasShift = $operator->is_active;

        // Определяем статус оператора
        if ($hasShift) {
            $status = 'available'; // Зеленый - включена смена
        } else {
            $status = 'offline'; // Желтый - не включена смена
        }

        return response()->json([
            'status' => $status,
            'has_shift' => $hasShift
        ]);
    }

    public function getFundInfo()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
        $fundInfo = $adminFundRepository->getFundInfo();

        if (!$fundInfo) {
            return response()->json(['error' => 'Fund info not found'], 404);
        }

        return response()->json($fundInfo);
    }

    public function checkFundBalance()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
        $balanceInfo = $adminFundRepository->checkFundBalance();

        if (!$balanceInfo) {
            return response()->json(['error' => 'Balance info not found'], 404);
        }

        return response()->json($balanceInfo);
    }

    /**
     * Пополнение общего фонда админа
     */
    public function topUpFund(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'tag' => 'nullable|string|max:255'
        ]);

        try {
            $adminFundRepository = new \App\Repositories\Balance\AdminFundRepository();
            $result = $adminFundRepository->topUpFund($validated['amount'], $validated['tag'] ?? 'admin_top_up_fund');
            
            if (!$result) {
                return response()->json(['error' => 'Error topping up fund'], 500);
            }
            
            return response()->json([
                'message' => 'Fund topped up successfully',
                'new_fund' => $result['fund'],
                'new_fund_dynamic' => $result['fund_dynamic']
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
