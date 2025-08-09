<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class MultiRoleMiddleware
{
    // Маппинг ролей к guard-ам
    protected array $roleToGuard = [
        'admin' => 'api_admin',
        'operator' => 'api_operator',
        'manager' => 'api_manager',
        'driver' => 'api_driver',
        'client' => 'api_client',
    ];

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        foreach ($roles as $role) {
            $guard = $this->roleToGuard[$role] ?? null;

            if ($guard && Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                // Определяем роль по типу модели
                $userRole = null;
                if ($user instanceof \App\Models\GpCompanyManager) {
                    $userRole = 'manager';
                } elseif ($user instanceof \App\Models\GpDriver) {
                    $userRole = 'driver';
                } elseif ($user instanceof \App\Models\GpAdmin) {
                    $userRole = 'admin';
                } elseif ($user instanceof \App\Models\GpOperator) {
                    $userRole = 'operator';
                } elseif ($user instanceof \App\Models\GpClient) {
                    $userRole = 'client';
                }

                if ($userRole === $role) {
                    // Проверяем статус is_active для менеджеров и водителей
                    if (($role === 'manager' || $role === 'driver') && !$user->is_active) {
                        Auth::guard($guard)->logout();
                        return response()->json([
                            'error' => 'Account deactivated',
                            'message' => 'Ваш аккаунт был деактивирован'
                        ], 403);
                    }

                    Auth::shouldUse($guard); // Активируем нужный guard
                    return $next($request);
                }
            }
        }

        return response()->json(['error' => 'Unauthorized', 'roles' => $roles], 403);
    }
}
