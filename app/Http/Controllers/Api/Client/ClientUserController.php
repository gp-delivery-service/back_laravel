<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\GpClient;
use App\Models\GpClientSms;
use App\Services\NodeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClientUserController extends Controller
{
    public function user(Request $request)
    {
        $user = Auth::guard('api_client')->user();

        return response()->json($user);
    }

    public function sendCode(Request $request)
    {
        try {

            $test_users_phones = [
                '62345678',
                '62985060',
            ];
            $test_users_sms_code = 123456;

            $this->validate($request, [
                'phone' => 'required|string|min:8|max:8'
            ]);

            $user =  GpClient::where(['phone' => $request->phone])->first();

            if (!$user) {
                // Создаем нового клиента если не найден
                $user = GpClient::create([
                    'name' => $request->phone, // По умолчанию имя = номер телефона
                    'phone' => $request->phone,
                    'wallet' => 0,
                ]);
            }

            $sms = new GpClientSms;
            $sms->user_id = $user->id;
            if (in_array($request->phone, $test_users_phones)) {
                $sms->sms = $test_users_sms_code;
            } else {
                $sms->sms = $sms->generateSms();
            }
            $sms->salt = $sms->generateSalt();

            $expiredAt = Carbon::now()->addMinutes(3);
            $sms->expired_at = $expiredAt;
            $sms->active = 1;
            $sms->save();

            NodeService::sendSmsCode($sms->sms, $user->phone);

            return response()->json([
                'success' => true,
                'message' => 'Смс отправлено',
                'data' => [
                    'salt' => $sms->salt,
                    'sms' => $sms->sms
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Логин и получение JWT токена.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $this->validate($request, [
                'phone' => 'required|string|min:8|max:8',
                'salt' => 'required|string|min:6|max:6',
                'sms' => 'required|string|min:6|max:6',
            ]);

            $user = GpClient::where(['phone' => $request->phone])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            $sms = GpClientSms::where(['user_id' => $user->id, 'sms' => $request->sms, 'salt' => $request->salt, 'active' => 1])->first();

            if (!$sms) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный смс код'
                ], 401);
            }

            if ($sms->isExpired()) {
                $sms->active = 0;
                $sms->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Код просрочен'
                ], 401);
            }

            GpClientSms::where('user_id', $user->id)->delete();

            $profile = $user;
            $access_token = Auth::guard('api_client')->login($user);

            return response()->json([
                'success' => true,
                'message' => 'Регистрация прошла успешно',
                'data' => [
                    'token' => $access_token,
                    'profile' => $profile
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}