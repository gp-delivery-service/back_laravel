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
                $claims = $user->getJWTCustomClaims();

                if (($claims['role'] ?? null) === $role) {
                    Auth::shouldUse($guard); // Активируем нужный guard
                    return $next($request);
                }
            }
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
