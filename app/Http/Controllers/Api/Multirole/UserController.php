<?php

namespace App\Http\Controllers\Api\Multirole;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function user(Request $request)
    {
        $guardToRole = [
            'api_admin' => 'admin',
            'api_operator' => 'operator',
            'api_manager' => 'manager',
            'api_driver' => 'driver',
        ];
        
        $user = Auth::user();
        $user = $user->toArray();
        $guard = Auth::getDefaultDriver();
        $user['role'] = $guardToRole[$guard] ?? 'unknown';
        return response()->json($user);
    }
}
