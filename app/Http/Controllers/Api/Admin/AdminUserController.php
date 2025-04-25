<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpAdminRefreshToken;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function user(Request $request)
    {
        $user = Auth::guard('api_admin')->user();
        $user = $user->toArray();
        $user['role'] = 'admin';
        return response()->json($user);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_id' => 'required|string|max:255',
        ]);

        $credentials = $request->only('email', 'password');

        Auth::guard('api_admin')->factory()->setTTL(1440);
        $access_token = Auth::guard('api_admin')->attempt($credentials);

        if (!$access_token) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials']]);
        }

        $user = Auth::guard('api_admin')->user();

        // Создаём refresh_token
        $refresh_token = Str::random(64);
        $expires_at = Carbon::now()->addDays(30);

        GpAdminRefreshToken::updateOrCreate(
            ['admin_id' => $user->id, 'device_id' => $request->device_id],
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

        $record = GpAdminRefreshToken::where('token', $request->refresh_token)
            ->where('device_id', $request->device_id)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $adminId = $record->admin_id;

        // Выдаём новый access_token
        Auth::guard('api_admin')->factory()->setTTL(1440);
        $access_token = Auth::guard('api_admin')->tokenById($adminId);

        return response()->json([
            'access_token' => $access_token,
            'token_type' => 'bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        $user = Auth::guard('api_admin')->user();

        GpAdminRefreshToken::where('admin_id', $user->id)
            ->where('device_id', $request->device_id)
            ->delete();

        Auth::guard('api_admin')->logout();

        return response()->json(['message' => 'Logged out']);
    }
}
