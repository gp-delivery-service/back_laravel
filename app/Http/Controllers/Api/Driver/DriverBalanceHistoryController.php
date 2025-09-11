<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\GpDriverBalanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverBalanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('api_driver')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
                'status' => false,
            ], 401);
        }

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        $query = DB::table('gp_driver_balance_logs')
            ->select(
                'id',
                'amount',
                'old_amount',
                'new_amount',
                'tag',
                'column',
                'user_id',
                'user_type',
                'created_at'
            )
            ->where('driver_id', $user->id)
            ->orderByDesc('created_at');

        $total = $query->count();
        $items = $query->forPage($page, $perPage)->get();

        // Получаем информацию о пользователях, которые выполняли операции
        $userIds = $items->pluck('user_id')->filter()->unique()->toArray();
        $users = [];
        
        if (!empty($userIds)) {
            $adminUsers = DB::table('gp_admins')
                ->whereIn('id', $userIds)
                ->select('id', 'name', DB::raw("'admin' as type"))
                ->get()
                ->keyBy('id');
            
            $operatorUsers = DB::table('gp_operators')
                ->whereIn('id', $userIds)
                ->select('id', 'name', DB::raw("'operator' as type"))
                ->get()
                ->keyBy('id');
            
            $driverUsers = DB::table('gp_drivers')
                ->whereIn('id', $userIds)
                ->select('id', 'name', DB::raw("'driver' as type"))
                ->get()
                ->keyBy('id');

            $users = $adminUsers->merge($operatorUsers)->merge($driverUsers);
        }

        // Добавляем информацию о пользователях к логам
        $items = $items->map(function ($item) use ($users) {
            $item->user = $users->get($item->user_id);
            return $item;
        });

        return response()->json([
            'items' => $items,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'total' => $total,
            'per_page' => $perPage
        ]);
    }
}
