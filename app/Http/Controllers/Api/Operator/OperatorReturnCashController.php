<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Models\ReturnCashVerificationCode;

class OperatorReturnCashController extends Controller
{

    public function getReturnCashCode($id){
        $user = auth()->guard('api_operator')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $verificationCode = ReturnCashVerificationCode::find($id)->load('driver');

        if (!$verificationCode) {
            return response()->json([
                'message' => 'Verification code not found',
                'status' => false,
            ], 404);
        }
        if ($verificationCode->operator_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to access this code',
                'status' => false,
            ], 403);
        }
        return response()->json($verificationCode, 200);
    }
}