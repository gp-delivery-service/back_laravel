<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class LogHelper
{
    protected static ?array $manualUser = null;

    public static function setManualUser(?string $id, ?string $type)
    {
        self::$manualUser = $id && $type ? ['id' => $id, 'type' => $type] : null;
    }

    /**
     * Получить текущего пользователя и его тип
     */
    public static function getCurrentUser(): ?array
    {
        if (self::$manualUser) {
            return self::$manualUser;
        }
        $guard = Auth::getDefaultDriver();
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $guardToType = [
            'api_admin' => 'App\Models\GpAdmin',
            'api_operator' => 'App\Models\GpOperator',
            'api_driver' => 'App\Models\GpDriver',
            'api_manager' => 'App\Models\GpCompanyManager',
            'api_client' => 'App\Models\GpClient',
        ];

        return [
            'id' => $user->id,
            'type' => $guardToType[$guard] ?? null,
        ];
    }

    /**
     * Получить данные пользователя для логирования
     */
    public static function getUserLogData(): array
    {
        $userData = self::getCurrentUser();

        if (!$userData) {
            return [
                'user_id' => null,
                'user_type' => null,
            ];
        }

        return [
            'user_id' => $userData['id'],
            'user_type' => $userData['type'],
        ];
    }
}
