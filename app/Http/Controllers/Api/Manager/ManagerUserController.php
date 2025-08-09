<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\GpCompany;
use App\Models\GpCompanyManagerRefreshToken;
use App\Models\GpDriver;
use App\Models\GpPickup;
use App\Constants\GpPickupStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ManagerUserController extends Controller
{
    public function user(Request $request)
    {
        $user = Auth::guard('api_manager')->user();
        $user = $user->toArray();
        $user['role'] = 'manager';
        if(isset($user['company_id'])) {
            $company_name = GpCompany::find($user['company_id'])->name ?? null;
            $user['company_name'] = $company_name;
        }
        return response()->json($user);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_id' => 'required|string|max:255',
        ]);

        Auth::guard('api_manager')->factory()->setTTL(1440);

        $credentials = $request->only('email', 'password');
        $access_token = Auth::guard('api_manager')->attempt($credentials);

        if (!$access_token) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $user = Auth::guard('api_manager')->user();

        if (!$user->is_active) {
            Auth::guard('api_manager')->logout();
            throw ValidationException::withMessages([
                'email' => ['Account is deactivated'],
            ]);
        }

        // Генерация refresh_token
        $refresh_token = Str::random(64);
        $expires_at = Carbon::now()->addDays(30);

        GpCompanyManagerRefreshToken::updateOrCreate(
            ['manager_id' => $user->id, 'device_id' => $request->device_id],
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

        $record = GpCompanyManagerRefreshToken::where('token', $request->refresh_token)
            ->where('device_id', $request->device_id)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $managerId = $record->manager_id;

        Auth::guard('api_manager')->factory()->setTTL(1440);
        $access_token = Auth::guard('api_manager')->tokenById($managerId);

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

        $user = Auth::guard('api_manager')->user();

        GpCompanyManagerRefreshToken::where('manager_id', $user->id)
            ->where('device_id', $request->device_id)
            ->delete();

        Auth::guard('api_manager')->logout();

        return response()->json(['message' => 'Logged out']);
    }
}
