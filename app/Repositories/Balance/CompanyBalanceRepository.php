<?php

namespace App\Repositories\Balance;

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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $company->refresh();
    }
}
