<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\GpCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerCompanyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        // Проверяем права доступа
        if ($role === 'operator') {
            return response()->json([
                'message' => "Доступ запрещен"
            ], 403);
        }

        // Получаем компании в зависимости от роли
        if ($role === 'manager') {
            // Менеджер видит только свою компанию
            $companies = GpCompany::where('id', $user->company_id)
                ->select('id', 'name')
                ->get();
        } else {
            // Администратор видит все компании
            $companies = GpCompany::select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return response()->json($companies);
    }

    public function show($id)
    {
        $user = Auth::user();
        $guard = Auth::getDefaultDriver();
        $role = $this->guardToRole[$guard] ?? 'unknown';

        // Проверяем права доступа
        if ($role === 'operator') {
            return response()->json([
                'message' => "Доступ запрещен"
            ], 403);
        }

        // Проверяем доступ к компании
        if ($role === 'manager' && $user->company_id !== $id) {
            return response()->json([
                'message' => "Доступ запрещен"
            ], 403);
        }

        $company = GpCompany::select('id', 'name', 'phone', 'address', 'created_at')
            ->find($id);

        if (!$company) {
            return response()->json([
                'message' => "Компания не найдена"
            ], 404);
        }

        return response()->json($company);
    }
}
