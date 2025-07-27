<?php

namespace App\Repositories\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EntityLogRepository
{
    protected CompanyRepository $companyRepository;
    protected DriverRepository $driverRepository;
    protected OperatorRepository $operatorRepository;

    public function __construct(CompanyRepository $companyRepository, DriverRepository $driverRepository, OperatorRepository $operatorRepository)
    {
        $this->companyRepository = $companyRepository;
        $this->driverRepository = $driverRepository;
        $this->operatorRepository = $operatorRepository;
    }

    public function getEntityLogs(string $entityType, string $entityId, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        $query = null;

        if ($entityType === 'company') {
            $query = DB::table('gp_company_balance_logs')
                ->select(
                    DB::raw("'company' as type"),
                    'id',
                    'company_id',
                    DB::raw('NULL as driver_id'),
                    DB::raw('NULL as operator_id'),
                    'amount',
                    'old_amount',
                    'new_amount',
                    'tag',
                    'column',
                    'user_id',
                    'user_type',
                    'created_at'
                )
                ->where('company_id', $entityId);
        } elseif ($entityType === 'driver') {
            $query = DB::table('gp_driver_balance_logs')
                ->select(
                    DB::raw("'driver' as type"),
                    'id',
                    DB::raw('NULL as company_id'),
                    'driver_id',
                    DB::raw('NULL as operator_id'),
                    'amount',
                    'old_amount',
                    'new_amount',
                    'tag',
                    'column',
                    'user_id',
                    'user_type',
                    'created_at'
                )
                ->where('driver_id', $entityId);
        } elseif ($entityType === 'operator') {
            $query = DB::table('gp_operator_balance_logs')
                ->select(
                    DB::raw("'operator' as type"),
                    'id',
                    DB::raw('NULL as company_id'),
                    DB::raw('NULL as driver_id'),
                    'operator_id',
                    'amount',
                    'old_amount',
                    'new_amount',
                    'tag',
                    'column',
                    'user_id',
                    'user_type',
                    'created_at'
                )
                ->where('operator_id', $entityId);
        } else {
            throw new \InvalidArgumentException('Unsupported entity type: ' . $entityType);
        }

        // Получение данных с пагинацией
        $items = $query->orderByDesc('created_at')->forPage($page, $perPage)->get();

        // Сбор ID для связанных объектов
        $companyIds = [];
        $driverIds = [];
        $operatorIds = [];
        $userIds = [];

        foreach ($items as $item) {
            if ($item->type === 'company' && $item->company_id) {
                $companyIds[] = $item->company_id;
            } elseif ($item->type === 'driver' && $item->driver_id) {
                $driverIds[] = $item->driver_id;
            } elseif ($item->type === 'operator' && $item->operator_id) {
                $operatorIds[] = $item->operator_id;
            }
            
            if ($item->user_id && $item->user_type) {
                $userIds[] = $item->user_id;
            }
        }

        // Получение связанных объектов
        $companies = collect($this->companyRepository->getItemsByIds($companyIds))->keyBy('id');
        $drivers = collect($this->driverRepository->getItemsByIds($driverIds))->keyBy('id');
        $operators = collect($this->operatorRepository->getItemsByIds($operatorIds))->keyBy('id');

        // Получение пользователей
        $users = [];
        if (!empty($userIds)) {
            $userTypes = ['App\Models\GpAdmin', 'App\Models\GpOperator', 'App\Models\GpDriver', 'App\Models\GpCompanyManager'];
            foreach ($userTypes as $userType) {
                $modelUsers = $userType::whereIn('id', $userIds)->get()->keyBy('id');
                foreach ($modelUsers as $user) {
                    $users[$user->id] = $user;
                }
            }
        }

        // Добавление связанных объектов к логам
        foreach ($items as $item) {
            if ($item->type === 'company' && isset($companies[$item->company_id])) {
                $item->company = $companies[$item->company_id];
            } elseif ($item->type === 'driver' && isset($drivers[$item->driver_id])) {
                $item->driver = $drivers[$item->driver_id];
            } elseif ($item->type === 'operator' && isset($operators[$item->operator_id])) {
                $item->operator = $operators[$item->operator_id];
            }
            
            $item->user = isset($users[$item->user_id]) ? $users[$item->user_id] : null;
        }

        // Получение общего количества записей
        $total = $query->count();

        // Создание пагинатора
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
} 