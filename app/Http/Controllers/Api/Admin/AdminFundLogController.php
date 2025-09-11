<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            ->leftJoin('gp_companies', 'gp_admin_fund_logs.company_id', '=', 'gp_companies.id')
            ->leftJoin('gp_drivers', 'gp_admin_fund_logs.driver_id', '=', 'gp_drivers.id')
            ->leftJoin('gp_pickups', 'gp_admin_fund_logs.pickup_id', '=', 'gp_pickups.id')
            ->select([
                'gp_admin_fund_logs.id',
                'gp_admin_fund_logs.amount',
                'gp_admin_fund_logs.old_fund_dynamic',
                'gp_admin_fund_logs.new_fund_dynamic',
                'gp_admin_fund_logs.old_total_earn',
                'gp_admin_fund_logs.new_total_earn',
                'gp_admin_fund_logs.tag',
                'gp_admin_fund_logs.operator_id',
                'gp_admin_fund_logs.company_id',
                'gp_admin_fund_logs.driver_id',
                'gp_admin_fund_logs.pickup_id',
                'gp_operators.name as operator_name',
                'gp_companies.name as company_name',
                'gp_drivers.name as driver_name',
                'gp_pickups.id as pickup_number',
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

        // Фильтрация по компании
        if ($request->has('company_id') && $request->company_id) {
            $query->where('gp_admin_fund_logs.company_id', $request->company_id);
        }

        // Фильтрация по водителю
        if ($request->has('driver_id') && $request->driver_id) {
            $query->where('gp_admin_fund_logs.driver_id', $request->driver_id);
        }

        // Фильтрация по заказу
        if ($request->has('pickup_id') && $request->pickup_id) {
            $query->where('gp_admin_fund_logs.pickup_id', $request->pickup_id);
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

    public function getCompaniesList()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $companies = DB::table('gp_companies')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json($companies);
    }

    public function getDriversList()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $drivers = DB::table('gp_drivers')
            ->select(['id', 'name', 'phone'])
            ->orderBy('name')
            ->get();

        return response()->json($drivers);
    }

    public function getPickupsList()
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pickups = DB::table('gp_pickups')
            ->select(['id', 'id as pickup_number'])
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return response()->json($pickups);
    }

    public function getEarningsChart(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $period = $request->get('period', 'days'); // days, weeks, months
        $dateFrom = $request->get('start_date') ?: $request->get('date_from');
        $dateTo = $request->get('end_date') ?: $request->get('date_to');

        // Логирование для отладки
        Log::info('EarningsChart request params', [
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'all_params' => $request->all()
        ]);

        $query = DB::table('gp_admin_fund_logs')
            ->select([
                'gp_admin_fund_logs.amount',
                'gp_admin_fund_logs.old_total_earn',
                'gp_admin_fund_logs.new_total_earn',
                'gp_admin_fund_logs.driver_id',
                'gp_drivers.name as driver_name',
                'gp_admin_fund_logs.created_at',
            ])
            ->leftJoin('gp_drivers', 'gp_admin_fund_logs.driver_id', '=', 'gp_drivers.id')
            ->whereNotNull('gp_admin_fund_logs.new_total_earn')
            ->where('gp_admin_fund_logs.new_total_earn', '!=', 'gp_admin_fund_logs.old_total_earn');

        // Фильтрация по датам
        if ($dateFrom) {
            $query->whereDate('gp_admin_fund_logs.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('gp_admin_fund_logs.created_at', '<=', $dateTo);
        }

        $logs = $query->orderBy('gp_admin_fund_logs.created_at')->get();

        // Логирование количества найденных записей
        Log::info('EarningsChart found records', [
            'count' => $logs->count(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);

        // Создаем полный диапазон дат
        $chartData = $this->generateDateRange($period, $dateFrom, $dateTo);
        
        // Логирование сгенерированного диапазона
        Log::info('EarningsChart generated date range', [
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generated_periods_count' => count($chartData),
            'generated_periods' => array_keys($chartData)
        ]);

        // Обрабатываем данные из логов
        foreach ($logs as $log) {
            $earningsChange = $log->new_total_earn - $log->old_total_earn;
            
            if ($earningsChange > 0) {
                $date = new \DateTime($log->created_at);
                
                // Группировка по периоду
                if ($period === 'days') {
                    $key = $date->format('Y-m-d');
                } elseif ($period === 'weeks') {
                    $weekStart = clone $date;
                    $weekStart->modify('monday this week');
                    $key = $weekStart->format('Y-m-d');
                } elseif ($period === 'months') {
                    $key = $date->format('Y-m');
                }

                // Добавляем данные к существующему периоду
                if (isset($chartData[$key])) {
                    $chartData[$key]['total_earnings'] += $earningsChange;

                    // Определение водителя
                    $driverName = $log->driver_name ?: 'Другое';

                    // Разбивка по водителям
                    if (!isset($chartData[$key]['drivers'][$driverName])) {
                        $chartData[$key]['drivers'][$driverName] = 0;
                    }
                    $chartData[$key]['drivers'][$driverName] += $earningsChange;
                }
            }
        }

        // Преобразование в массив и сортировка
        $result = array_values($chartData);
        usort($result, function($a, $b) {
            return strcmp($a['period'], $b['period']);
        });

        // Логирование результата
        Log::info('EarningsChart result', [
            'result_count' => count($result),
            'periods' => array_column($result, 'period'),
            'sample_data' => array_slice($result, 0, 3) // Первые 3 записи для отладки
        ]);

        return response()->json($result);
    }

    private function generateDateRange($period, $dateFrom, $dateTo)
    {
        $chartData = [];
        
        if (!$dateFrom || !$dateTo) {
            Log::warning('EarningsChart: Missing date parameters', [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);
            return $chartData;
        }

        $startDate = new \DateTime($dateFrom);
        $endDate = new \DateTime($dateTo);
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            if ($period === 'days') {
                $key = $currentDate->format('Y-m-d');
                $chartData[$key] = [
                    'period' => $key,
                    'total_earnings' => 0,
                    'drivers' => []
                ];
                $currentDate->add(new \DateInterval('P1D'));
            } elseif ($period === 'weeks') {
                // Начинаем с понедельника текущей недели
                $weekStart = clone $currentDate;
                $weekStart->modify('monday this week');
                $key = $weekStart->format('Y-m-d');
                
                if (!isset($chartData[$key])) {
                    $chartData[$key] = [
                        'period' => $key,
                        'total_earnings' => 0,
                        'drivers' => []
                    ];
                }
                // Переходим к следующей неделе
                $currentDate->add(new \DateInterval('P7D'));
            } elseif ($period === 'months') {
                $key = $currentDate->format('Y-m');
                if (!isset($chartData[$key])) {
                    $chartData[$key] = [
                        'period' => $key,
                        'total_earnings' => 0,
                        'drivers' => []
                    ];
                }
                $currentDate->add(new \DateInterval('P1M'));
            }
        }

        return $chartData;
    }

    public function getOrdersByCompaniesChart(Request $request)
    {
        $user = Auth::guard('api_admin')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n')); // 1-12

        // Логирование для отладки
        Log::info('OrdersByCompaniesChart request params', [
            'year' => $year,
            'month' => $month,
            'all_params' => $request->all()
        ]);

        $query = DB::table('gp_pickup_orders')
            ->select([
                'gp_pickup_orders.id',
                'gp_pickup_orders.status',
                'gp_pickup_orders.created_at',
                'gp_companies.name as company_name',
                'gp_companies.id as company_id'
            ])
            ->leftJoin('gp_pickups', 'gp_pickup_orders.pickup_id', '=', 'gp_pickups.id')
            ->leftJoin('gp_companies', 'gp_pickups.company_id', '=', 'gp_companies.id')
            ->where('gp_pickup_orders.status', 'delivered')
            ->whereYear('gp_pickup_orders.created_at', $year)
            ->whereMonth('gp_pickup_orders.created_at', $month);

        $orders = $query->get();

        // Логирование количества найденных заказов
        Log::info('OrdersByCompaniesChart found orders', [
            'count' => $orders->count(),
            'year' => $year,
            'month' => $month
        ]);

        // Группируем по компаниям
        $companyStats = [];
        foreach ($orders as $order) {
            $companyName = $order->company_name ?: 'Без компании';
            if (!isset($companyStats[$companyName])) {
                $companyStats[$companyName] = 0;
            }
            $companyStats[$companyName]++;
        }

        // Преобразуем в формат для графика
        $chartData = [];
        $colors = [
            '#037979', // Основной зеленый
            '#059669', // Более темный зеленый
            '#10B981', // Светло-зеленый
            '#34D399', // Мятный зеленый
            '#6EE7B7', // Очень светлый зеленый
            '#064E3B', // Темно-зеленый
            '#065F46', // Темно-зеленый 2
            '#047857', // Средний зеленый
            '#10A37F', // Зеленый с оттенком синего
            '#0D9488', // Бирюзовый
            '#14B8A6', // Светло-бирюзовый
            '#2DD4BF', // Очень светлый бирюзовый
            '#0F766E', // Темно-бирюзовый
            '#134E4A', // Очень темный зеленый
            '#115E59'  // Темно-бирюзовый 2
        ];

        $colorIndex = 0;
        foreach ($companyStats as $company => $count) {
            $chartData[] = [
                'company' => $company,
                'count' => $count,
                'color' => $colors[$colorIndex % count($colors)]
            ];
            $colorIndex++;
        }

        // Сортируем по количеству заказов (по убыванию)
        usort($chartData, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Логирование результата
        Log::info('OrdersByCompaniesChart result', [
            'result_count' => count($chartData),
            'companies' => array_column($chartData, 'company'),
            'sample_data' => array_slice($chartData, 0, 3)
        ]);

        return response()->json($chartData);
    }
}
