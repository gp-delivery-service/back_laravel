<?php

namespace App\Http\Controllers\Api\Operator;


use App\Http\Controllers\Controller;
use App\Repositories\Admin\CompanyRepository;
use App\Repositories\Balance\CompanyTransactionsRepository;
use App\Repositories\Balance\OperatorBalanceRepository;
use App\Repositories\Balance\OperatorTransactionsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorCompanyBalanceController extends Controller
{
    protected $repository;

    public function __construct(CompanyTransactionsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getInfo($companyId)
    {
        $companyRepostory = new CompanyRepository();

        $company = $companyRepostory->getItemById($companyId);

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json($company);
    }

    public function creditIncrease(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:gp_companies,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $this->repository->credit_increase($validated['company_id'], $validated['amount']);

        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        $operatorTransactionRepository = new OperatorTransactionsRepository(new OperatorBalanceRepository());
        if ($role === 'operator') {
            $operatorTransactionRepository->cash_decrease($user->id, $validated['amount']);
        }

        return response()->json([
            'message' => 'Credit increased successfully',
        ]);
    }

    public function balanceIncrease(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|string|exists:gp_companies,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        $this->repository->balance_increase_cash($validated['company_id'], $validated['amount']);
        $operatorTransactionRepository = new OperatorTransactionsRepository(new OperatorBalanceRepository());
        if ($role === 'operator') {
            $operatorTransactionRepository->cash_increase($user->id, $validated['amount']);
        }

        return response()->json([
            'message' => 'Balance increased successfully',
        ]);
    }

    public function show($id)
    {
        // Logic to retrieve and return specific company balance information by ID
        return response()->json([
            'message' => "Company balance for ID {$id} will be implemented here."
        ]);
    }

    private $guardToRole = [
        'api_admin' => 'admin',
        'api_operator' => 'operator',
        'api_manager' => 'manager',
        'api_driver' => 'driver',
    ];
}
