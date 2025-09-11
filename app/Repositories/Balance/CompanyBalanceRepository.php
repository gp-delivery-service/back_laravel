<?php

namespace App\Repositories\Balance;

use App\Helpers\LogHelper;
use App\Models\GpCompany;
use Illuminate\Support\Facades\DB;

class CompanyBalanceRepository
{
    public function addBalance($companyId, $amount, $tag)
    {
        $company = GpCompany::find($companyId);
        if (!$company) {
            return null;
        }

        $oldAmount = $company->balance;
        $tag = $tag ?: 'balance_update';
        $column = 'balance';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_companies')
            ->where('id', $companyId)
            ->increment('balance', $amount);

        $newAmount = GpCompany::find($companyId)->balance;

        DB::table('gp_company_balance_logs')->insert([
            'company_id' => $companyId,
            'amount' => $amount,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'tag' => $tag,
            'column' => $column,
            'user_id' => $userData['user_id'],
            'user_type' => $userData['user_type'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $company->refresh();
    }

    public function addAgregatorSideBalance($companyId, $amount, $tag)
    {
        $company = GpCompany::find($companyId);
        if (!$company) {
            return null;
        }

        $oldAmount = $company->agregator_side_balance;
        $tag = $tag ?: 'agregator_side_balance_update';
        $column = 'agregator_side_balance';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_companies')
            ->where('id', $companyId)
            ->increment('agregator_side_balance', $amount);

        $newAmount = GpCompany::find($companyId)->agregator_side_balance;

        DB::table('gp_company_balance_logs')->insert([
            'company_id' => $companyId,
            'amount' => $amount,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'tag' => $tag,
            'column' => $column,
            'user_id' => $userData['user_id'],
            'user_type' => $userData['user_type'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $company->refresh();
    }

    public function addCreditBalance($companyId, $amount, $tag)
    {
        $company = GpCompany::find($companyId);
        if (!$company) {
            return null;
        }

        $oldAmount = $company->credit_balance;
        $tag = $tag ?: 'credit_balance_update';
        $column = 'credit_balance';
        $userData = LogHelper::getUserLogData();

        // Управление фондом админа согласно новой логике
        // Только если пополняет админ - проверяем и списываем с fund_dynamic ДО записи кредита
        if ($amount > 0 && $userData['user_type'] === 'App\Models\GpAdmin') {
            $fundManager = new FundManagerRepository();
            // Проверяем достаточность средств в фонде ДО записи кредита
            $fundManager->decreaseFundDynamic($amount, 'credit_balance_increase', $companyId, null, null);
        }

        DB::transaction(function () use ($companyId, $amount, $tag, $oldAmount, $column, $userData) {
            DB::table('gp_companies')
                ->where('id', $companyId)
                ->increment('credit_balance', $amount);

            $newAmount = GpCompany::find($companyId)->credit_balance;

            DB::table('gp_company_balance_logs')->insert([
                'company_id' => $companyId,
                'amount' => $amount,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'tag' => $tag,
                'column' => $column,
                'user_id' => $userData['user_id'],
                'user_type' => $userData['user_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // При закрытии кредита всегда увеличиваем fund_dynamic (после записи)
        if ($amount < 0) {
            $fundManager = new FundManagerRepository();
            $fundManager->increaseFundDynamic(abs($amount), 'credit_balance_close', $companyId, null, null);
        }

        return $company->refresh();
    }

    /**
     * Пополнение кредита компании оператором (списывается только с кассы оператора)
     */
    public function addCreditBalanceByOperator($companyId, $amount, $operatorId, $tag = 'operator_credit_balance_increase')
    {
        $company = GpCompany::find($companyId);
        $operator = \App\Models\GpOperator::find($operatorId);
        
        if (!$company || !$operator) {
            return null;
        }

        // Проверяем, достаточно ли средств в кассе оператора
        if ($operator->cash < $amount) {
            throw new \RuntimeException("Недостаточно средств в кассе оператора. Доступно: {$operator->cash}, требуется: {$amount}");
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($company, $operator, $amount, $tag, $userData) {
            // Сохраняем старые значения для логов
            $oldCompanyCreditBalance = $company->credit_balance;
            $oldOperatorCash = $operator->cash;

            // Увеличиваем credit_balance компании
            $company->credit_balance += $amount;
            $company->save();

            // Уменьшаем кассу оператора
            $operator->cash -= $amount;
            $operator->save();

            // Логируем изменение credit_balance компании
            DB::table('gp_company_balance_logs')->insert([
                'company_id' => $company->id,
                'amount' => $amount,
                'old_amount' => $oldCompanyCreditBalance,
                'new_amount' => $company->credit_balance,
                'tag' => $tag,
                'column' => 'credit_balance',
                'user_id' => $userData['user_id'],
                'user_type' => $userData['user_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Логируем изменение кассы оператора
            DB::table('gp_operator_balance_logs')->insert([
                'operator_id' => $operator->id,
                'amount' => -$amount,
                'old_amount' => $oldOperatorCash,
                'new_amount' => $operator->cash,
                'tag' => $tag,
                'column' => 'cash',
                'user_id' => $userData['user_id'],
                'user_type' => $userData['user_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return $company->refresh();
    }
}
