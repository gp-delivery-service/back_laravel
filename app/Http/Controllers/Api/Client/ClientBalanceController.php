<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Repositories\Balance\ClientTransactionsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientBalanceController extends Controller
{
    protected $repository;

    public function __construct(ClientTransactionsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getInfo()
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'client' => $user,
            'wallet' => $user->wallet,
        ]);
    }

    public function walletIncrease(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $this->repository->wallet_increase($user->id, $validated['amount']);

        return response()->json([
            'message' => 'Wallet increased successfully',
            'new_balance' => $user->refresh()->wallet,
        ]);
    }

    public function walletDecrease(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->wallet < $validated['amount']) {
            return response()->json([
                'error' => 'Insufficient funds',
            ], 422);
        }

        $this->repository->wallet_decrease($user->id, $validated['amount']);

        return response()->json([
            'message' => 'Wallet decreased successfully',
            'new_balance' => $user->refresh()->wallet,
        ]);
    }
}