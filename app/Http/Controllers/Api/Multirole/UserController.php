<?php

namespace App\Http\Controllers\Api\Multirole;

use App\Http\Controllers\Controller;
use App\Models\GpCompanyManager;
use App\Models\GpPickup;
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
            'api_client' => 'client',
        ];

        $user = Auth::user();
        $user = $user ? $user->toArray() : [];
        $guard = Auth::getDefaultDriver();
        $user['role'] = $guardToRole[$guard] ?? 'unknown';
        return response()->json($user);
    }


    public function manager_visible_drivers()
    {
        // Найти все вызовы GpPickups, где driver_id != null и status != closed
        $pickups = GpPickup::whereNotNull('driver_id')
            ->where('status', '!=', 'closed')
            ->get();

        // Сгруппировать все driver_id по company_id
        $grouped = [];
        foreach ($pickups as $pickup) {
            $companyId = $pickup->company_id;
            $driverId = $pickup->driver_id;
            if ($companyId && $driverId) {
                if (!isset($grouped[$companyId])) {
                    $grouped[$companyId] = [];
                }
                // Добавлять только уникальные driver_id
                if (!in_array($driverId, $grouped[$companyId])) {
                    $grouped[$companyId][] = $driverId;
                }
            }
        }

        $managers = GpCompanyManager::all();
        // Для каждого менеджера, относящегося к компании, добавить в $grouped[manager_id] список driver_id этой компании
        $managerDrivers = [];
        foreach ($managers as $manager) {
            $companyId = $manager->company_id;
            $managerId = $manager->id;
            // Если у компании есть водители, присваиваем их менеджеру
            if (isset($grouped[$companyId])) {
                $managerDrivers[$managerId] = $grouped[$companyId];
            } else {
                $managerDrivers[$managerId] = [];
            }
        }
        $grouped = $managerDrivers;


        return response()->json($grouped);
    }
}
