<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\GpTransaction;

class CompanyTransactionRepository
{
    protected string $table = 'gp_transactions';
    protected string $companyTable = 'gp_companies';

    public function create(string $type, float $amount, string $companyId, ?string $operatorId = null, ?string $adminId = null): string
    {
        $id = (string) Str::uuid();
        $createdAt = Carbon::now();

        $hash = $this->generateHash([
            'id' => $id,
            'type' => $type,
            'amount' => $amount,
            'company_id' => $companyId,
            'operator_id' => $operatorId,
            'admin_id' => $adminId,
            'created_at' => $createdAt->toDateTimeString(),
        ]);

        DB::transaction(function () use ($id, $type, $amount, $companyId, $operatorId, $adminId, $createdAt, $hash) {
            DB::table($this->table)->insert([
                'id' => $id,
                'type' => $type,
                'amount' => $amount,
                'company_id' => $companyId,
                'operator_id' => $operatorId,
                'admin_id' => $adminId,
                'created_at' => $createdAt,
                'hash' => $hash,
            ]);

            $this->applyToCompany($type, $amount, $companyId);
        });

        return $id;
    }

    protected function applyToCompany(string $type, float $amount, string $companyId): void
    {
        match ($type) {
            'credit_increase' =>
                DB::table($this->companyTable)->where('id', $companyId)->increment('credit_balance', $amount),

            'credit_close_cash',
            'credit_close_order' =>
                DB::table($this->companyTable)->where('id', $companyId)->decrement('credit_balance', $amount),

            'credit_close_balance' => function () use ($companyId, $amount) {
                DB::table($this->companyTable)->where('id', $companyId)->decrement('credit_balance', $amount);
                DB::table($this->companyTable)->where('id', $companyId)->decrement('balance', $amount);
            },

            'aggregator_debt_increase_order' =>
                DB::table($this->companyTable)->where('id', $companyId)->increment('agregator_side_balance', $amount),

            'aggregator_debt_decrease_cash' =>
                DB::table($this->companyTable)->where('id', $companyId)->decrement('agregator_side_balance', $amount),

            'aggregator_debt_decrease_credit' => function () use ($companyId, $amount) {
                DB::table($this->companyTable)->where('id', $companyId)->decrement('agregator_side_balance', $amount);
                DB::table($this->companyTable)->where('id', $companyId)->decrement('credit_balance', $amount);
            },

            'balance_increase_cash' =>
                DB::table($this->companyTable)->where('id', $companyId)->increment('balance', $amount),

            'balance_decrease_order' =>
                DB::table($this->companyTable)->where('id', $companyId)->decrement('balance', $amount),

            default => throw new \InvalidArgumentException("Unknown transaction type: $type")
        };
    }

    protected function generateHash(array $data): string
    {
        $serialized = implode('|', [
            $data['id'],
            $data['type'],
            $data['amount'],
            $data['company_id'],
            $data['operator_id'] ?? '',
            $data['admin_id'] ?? '',
            $data['created_at'],
        ]);

        return hash('sha256', $serialized . env('APP_KEY'));
    }
}
