<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Для API роутов всегда возвращаем null
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Для веб роутов пытаемся перенаправить на login
        try {
            return route('login');
        } catch (\Exception $e) {
            return null;
        }
    }
}
