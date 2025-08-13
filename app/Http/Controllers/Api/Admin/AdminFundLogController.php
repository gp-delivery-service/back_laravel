<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminFundLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = DB::table('gp_admin_fund_logs')
            ->leftJoin('gp_operators', 'gp_admin_fund_logs.operator_id', '=', 'gp_operators.id')
            ->select([
                'gp_admin_fund_logs.id',
                'gp_admin_fund_logs.amount',
                'gp_admin_fund_logs.old_fund_dynamic',
                'gp_admin_fund_logs.new_fund_dynamic',
                'gp_admin_fund_logs.tag',
                'gp_admin_fund_logs.operator_id',
                'gp_operators.name as operator_name',
                'gp_admin_fund_logs.user_id',
                'gp_admin_fund_logs.user_type',
                'gp_admin_fund_logs.created_at',
                'gp_admin_fund_logs.updated_at',
            ])
            ->orderBy('gp_admin_fund_logs.created_at', 'desc');

        // Фильтрация по оператору
        if ($request->has('operator_id') && $request->operator_id) {
            $query->where('gp_admin_fund_logs.operator_id', $request->operator_id);
        }

        // Фильтрация по тегу
        if ($request->has('tag') && $request->tag) {
            $query->where('gp_admin_fund_logs.tag', $request->tag);
        }

        // Фильтрация по дате
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('gp_admin_fund_logs.created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('gp_admin_fund_logs.created_at', '<=', $request->date_to);
        }

        $perPage = $request->get('per_page', 15);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    public function getOperatorsList()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $operators = DB::table('gp_operators')
            ->select(['id', 'name', 'email'])
            ->where('cashier', true)
            ->orderBy('name')
            ->get();

        return response()->json($operators);
    }

    public function getTagsList()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tags = DB::table('gp_admin_fund_logs')
            ->select('tag')
            ->distinct()
            ->orderBy('tag')
            ->pluck('tag');

        return response()->json($tags);
    }
}
