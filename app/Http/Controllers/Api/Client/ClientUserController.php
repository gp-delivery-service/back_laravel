<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\GpClient;
use App\Models\GpClientSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientUserController extends Controller
{
    public function sendCode(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $validated['phone'];
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Удаляем старые коды для этого номера
        GpClientSms::where('phone', $phone)->delete();

        // Создаем новый код
        GpClientSms::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        // TODO: Здесь должна быть отправка SMS
        // Пока возвращаем код в ответе для тестирования
        return response()->json([
            'message' => 'SMS code sent successfully',
            'code' => $code, // Убрать в продакшене
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:4',
        ]);

        $phone = $validated['phone'];
        $code = $validated['code'];

        // Проверяем код
        $sms = GpClientSms::where('phone', $phone)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$sms) {
            return response()->json([
                'message' => 'Invalid or expired code',
                'status' => false,
            ], 400);
        }

        // Помечаем код как использованный
        $sms->update(['used' => true]);

        // Находим или создаем клиента
        $client = GpClient::where('phone', $phone)->first();

        if (!$client) {
            $client = GpClient::create([
                'name' => $phone, // По умолчанию имя = номер телефона
                'phone' => $phone,
                'wallet' => 0,
            ]);
        }

        // Генерируем токен
        $token = Auth::guard('api_client')->login($client);

        return response()->json([
            'message' => 'Login successful',
            'status' => true,
            'token' => $token,
            'user' => $client,
        ]);
    }

    public function user()
    {
        $user = Auth::guard('api_client')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        return response()->json([
            'user' => $user,
            'status' => true,
        ]);
    }
}