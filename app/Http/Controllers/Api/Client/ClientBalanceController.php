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
}