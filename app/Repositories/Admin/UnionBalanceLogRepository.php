<?php

namespace App\Repositories\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Repositories\Admin\OperatorRepository;
use App\Repositories\Admin\ClientRepository;

class UnionBalanceLogRepository
{
    protected CompanyRepository $companyRepository;
    protected DriverRepository $driverRepository;
    protected OperatorRepository $operatorRepository;
    protected ClientRepository $clientRepository;

    public function __construct(CompanyRepository $companyRepository, DriverRepository $driverRepository, OperatorRepository $operatorRepository, ClientRepository $clientRepository)
    {
        $this->companyRepository = $companyRepository;
        $this->driverRepository = $driverRepository;
        $this->operatorRepository = $operatorRepository;
        $this->clientRepository = $clientRepository;
    }

    public function getPage(int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        // Подзапрос для компании
        $companyLogs = DB::table('gp_company_balance_logs')
            ->select(
                DB::raw("'company' as type"),
                'id',
                'company_id',
                DB::raw('NULL as driver_id'),
                DB::raw('NULL as operator_id'),
                DB::raw('NULL as client_id'),
                'amount',
                'old_amount',
                'new_amount',
                'tag',
                'column',
                'user_id',
                'user_type',
                'created_at'
            );

        // Подзапрос для водителя
        $driverLogs = DB::table('gp_driver_balance_logs')
            ->select(
                DB::raw("'driver' as type"),
                'id',
                DB::raw('NULL as company_id'),
                'driver_id',
                DB::raw('NULL as operator_id'),
                DB::raw('NULL as client_id'),
                'amount',
                'old_amount',
                'new_amount',
                'tag',
                'column',
                'user_id',
                'user_type',
                'created_at'
            );

        // Подзапрос для оператора
        $operatorLogs = DB::table('gp_operator_balance_logs')
            ->select(
                DB::raw("'operator' as type"),
                'id',
                DB::raw('NULL as company_id'),
                DB::raw('NULL as driver_id'),
                'operator_id',
                DB::raw('NULL as client_id'),
                'amount',
                'old_amount',
                'new_amount',
                'tag',
                'column',
                'user_id',
                'user_type',
                'created_at'
            );

        // Подзапрос для клиента
        $clientLogs = DB::table('gp_client_balance_logs')
            ->select(
                DB::raw("'client' as type"),
                'id',
                DB::raw('NULL as company_id'),
                DB::raw('NULL as driver_id'),
                DB::raw('NULL as operator_id'),
                'client_id',
                'amount',
                'old_amount',
                'new_amount',
                'tag',
                'column',
                'user_id',
                'user_type',
                'created_at'
            );

        // Объединение
        $union = $companyLogs->unionAll($driverLogs)->unionAll($operatorLogs)->unionAll($clientLogs);

        // Итоговый запрос с сортировкой
        $query = DB::table(DB::raw("({$union->toSql()}) as balance_logs"))
            ->mergeBindings($union)
            ->orderByDesc('created_at');

        // Получение текущей страницы
        $items = $query->forPage($page, $perPage)->get();

        // Сбор ID
        $companyIds = [];
        $driverIds = [];
        $operatorIds = [];
        $clientIds = [];
        $userIds = [];

        foreach ($items as $item) {
            if ($item->type === 'company' && $item->company_id) {
                $companyIds[] = $item->company_id;
            } elseif ($item->type === 'driver' && $item->driver_id) {
                $driverIds[] = $item->driver_id;
            } elseif ($item->type === 'operator' && $item->operator_id) {
                $operatorIds[] = $item->operator_id;
            } elseif ($item->type === 'client' && $item->client_id) {
                $clientIds[] = $item->client_id;
            }
            
            if ($item->user_id && $item->user_type) {
                $userIds[] = $item->user_id;
            }
        }

        // Получение связанных объектов
        $companies = collect($this->companyRepository->getItemsByIds($companyIds))->keyBy('id');
        $drivers = collect($this->driverRepository->getItemsByIds($driverIds))->keyBy('id');
        $operators = collect($this->operatorRepository->getItemsByIds($operatorIds))->keyBy('id');
        $clients = collect($this->clientRepository->getItemsByIds($clientIds))->keyBy('id');

        // Получение пользователей
        $users = [];
        if (!empty($userIds)) {
            $userTypes = ['App\Models\GpAdmin', 'App\Models\GpOperator', 'App\Models\GpDriver', 'App\Models\GpCompanyManager', 'App\Models\GpClient'];
            foreach ($userTypes as $userType) {
                $modelUsers = $userType::whereIn('id', $userIds)->get()->keyBy('id');
                foreach ($modelUsers as $user) {
                    $users[$user->id] = $user;
                }
            }
        }

        // Привязка связанных сущностей
        $items->transform(function ($item) use ($companies, $drivers, $operators, $clients, $users) {
            $item->company = $item->type === 'company'
                ? ($companies[$item->company_id] ?? null)
                : null;

            $item->driver = $item->type === 'driver'
                ? ($drivers[$item->driver_id] ?? null)
                : null;

            $item->operator = $item->type === 'operator'
                ? ($operators[$item->operator_id] ?? null)
                : null;

            $item->client = $item->type === 'client'
                ? ($clients[$item->client_id] ?? null)
                : null;

            $item->user = isset($users[$item->user_id]) ? $users[$item->user_id] : null;

            return $item;
        });

        // Подсчёт общего количества
        $total = DB::table(DB::raw("({$union->toSql()}) as balance_logs"))
            ->mergeBindings($union)
            ->count();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => url()->current()]
        );
    }
}