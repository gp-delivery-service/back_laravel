<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\DriverRepository;
use App\Repositories\Balance\DriverTransactionsRepository;
use App\Repositories\Balance\OperatorTransactionsRepository;
use App\Services\NodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorDriverBalanceController extends Controller
{
    protected $repository;
    protected $operatorTransactionRepository;

    public function __construct(DriverTransactionsRepository $repository, OperatorTransactionsRepository $operatorTransactionRepository)
    {
        $this->repository = $repository;
        $this->operatorTransactionRepository = $operatorTransactionRepository;
    }

    public function getInfo($driverId)
    {
        $driverRepository = new DriverRepository();

        $driver = $driverRepository->getItemById($driverId);

        if (!$driver) {
            return response()->json(['error' => 'Driver not found'], 404);
        }

        return response()->json($driver);
    }

    public function balanceIncrease(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|string|exists:gp_drivers,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $this->repository->balance_increase($validated['driver_id'], $validated['amount']);

        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        if ($role === 'operator') {
            $this->operatorTransactionRepository->cash_increase($user->id, $validated['amount']);
        }


        return response()->json([
            'message' => 'Balance increased successfully',
        ]);
    }

    public function returnEarning(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|string|exists:gp_drivers,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';
        $this->repository->earning_pending_decrease($validated['driver_id'], $validated['amount']);

        if ($role === 'operator') {
            $this->operatorTransactionRepository->cash_decrease($user->id, $validated['amount']);
        }

        return response()->json([
            'message' => 'Earning returned successfully',
        ]);
    }

    public function closeCash(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'driver_id' => 'required|string|exists:gp_drivers,id',
            'amount' => 'required|numeric|min:0',
        ]);
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        try {
            $result = $this->repository->cash_close($validated['driver_id'], $validated['amount']);

            if (!$result) {
                return response()->json(['error' => 'Error closing cash'], 500);
            }

            $guard = Auth::getDefaultDriver();
            $role = $this->guardToRole[$guard] ?? 'unknown';

            if ($role === 'operator') {
                $this->operatorTransactionRepository->cash_increase($user->id, $validated['amount']);
            }
            return response()->json([
                'message' => 'Cash closed successfully',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422); // 422 Unprocessable Entity — логическая ошибка

        } catch (\Throwable $e) {
            // На случай других исключений, например, SQL или логики
            return response()->json([
                'error' => 'Internal server error',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager',
        'api_driver' => 'driver',
        'api_client' => 'client',
    ];
}
