<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\ReturnCashVerificationCode;
use App\Repositories\Admin\OperatorRepository;
use App\Services\NodeService;
use PHPUnit\Framework\Constraint\Operator;

class DriverReturnCashController extends Controller
{
    public function getOperatorsList()
    {
        $user = auth()->guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $operatorsRepository = new OperatorRepository();
        $operators = $operatorsRepository->getShortList();

        return response()->json($operators);
    }

    public function getReturnCashAmountWithCode()
    {
        $user = auth()->guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $validated = request()->validate([
            'operator_id' => 'required|exists:gp_operators,id',
        ]);

        $operator_id = $validated['operator_id'];

        if ($user->total_cash <= 0) {
            return response()->json([
                'message' => 'No cash available for return',
                'status' => false,
            ], 400);
        }
        $totalCash = $user->total_cash;
        $verificationCode = $this->generateVerificationCode();

        $verification = ReturnCashVerificationCode::create([
            'code' => $verificationCode,
            'driver_id' => $user->id,
            'operator_id' => $operator_id,
            'amount' => $totalCash,
            'created_at' => now(),
        ]);

        if (!$verification) {
            return response()->json([
                'message' => 'Error creating verification code',
                'status' => false,
            ], 500);
        }

        NodeService::callShowVerificationCode($verification->id, $verification->operator_id);

        return response()->json([
            'id' => $verification->id,
            'amount' => $totalCash,
        ]);
    }

    public function confirmReturnCash()
    {
        $user = auth()->guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $validated = request()->validate([
            'id' => 'required|exists:return_cash_verification_codes,id',
            'operator_id' => 'required|exists:gp_operators,id',
            'verification_code' => 'required|string|size:6',
            'amount' => 'required|numeric|min:0',
        ]);

        $id = $validated['id'];
        $operator_id = $validated['operator_id'];
        $verificationCode = $validated['verification_code'];
        $amount = $validated['amount'];

        $verification = ReturnCashVerificationCode::where('id', $id)
            ->where('driver_id', $user->id)
            ->where('operator_id', $operator_id)
            ->where('code', $verificationCode)
            ->first();

        if (!$verification) {
            return response()->json([
                'message' => 'Invalid verification code or operator',
                'status' => false,
            ], 404);
        }
        NodeService::callHideVerificationCode($verification->id, $verification->operator_id);
        $verification->delete();

        return response()->json([
            'message' => 'Return cash confirmed successfully',
            'status' => true,
        ]);
    }

    private function generateVerificationCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
