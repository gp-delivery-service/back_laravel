<?php

namespace App\Http\Controllers\Api\Operator;

use App\Http\Controllers\Controller;
use App\Models\GpOperatorRefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OperatorUserController extends Controller
{
    public function user(Request $request)
    {
        $user = Auth::guard('api_operator')->user();
        $user = $user->toArray();
        $user['role'] = 'operator';
        return response()->json($user);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_id' => 'required|string|max:255',
        ]);

        Auth::guard('api_operator')->factory()->setTTL(1440);

        $credentials = $request->only('email', 'password');
        $access_token = Auth::guard('api_operator')->attempt($credentials);

        if (!$access_token) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $user = Auth::guard('api_operator')->user();

        if (!$user->is_active) {
            Auth::guard('api_operator')->logout();
            throw ValidationException::withMessages([
                'email' => ['Account is deactivated'],
            ]);
        }

        // Генерация refresh_token
        $refresh_token = Str::random(64);
        $expires_at = Carbon::now()->addDays(30);

        GpOperatorRefreshToken::updateOrCreate(
            ['operator_id' => $user->id, 'device_id' => $request->device_id],
            ['token' => $refresh_token, 'expires_at' => $expires_at]
        );

        return response()->json([
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'token_type' => 'bearer'
        ]);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
            'device_id' => 'required|string',
        ]);

        $record = GpOperatorRefreshToken::where('token', $request->refresh_token)
            ->where('device_id', $request->device_id)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $operatorId = $record->operator_id;

        Auth::guard('api_operator')->factory()->setTTL(1440);
        $access_token = Auth::guard('api_operator')->tokenById($operatorId);

        return response()->json([
            'access_token' => $access_token,
            'token_type' => 'bearer'
        ]);
    }

    public function logout(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        $user = Auth::guard('api_operator')->user();

        GpOperatorRefreshToken::where('operator_id', $user->id)
            ->where('device_id', $request->device_id)
            ->delete();

        Auth::guard('api_operator')->logout();

        return response()->json(['message' => 'Logged out']);
    }
}
