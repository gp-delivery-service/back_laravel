<?php

namespace App\Repositories\Balance;

use App\Helpers\LogHelper;
use App\Models\GpOperator;
use Illuminate\Support\Facades\DB;

class OperatorBalanceRepository
{
    public function addCash($operatorId, $amount, $tag)
    {
        $operator = GpOperator::find($operatorId);
        if (!$operator) {
            return null;
        }

        $oldAmount = $operator->cash;
        $tag = $tag ?: 'cash_update';
        $column = 'cash';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_operators')
            ->where('id', $operatorId)
            ->increment('cash', $amount);

        $newAmount = GpOperator::find($operatorId)->cash;

        DB::table('gp_operator_balance_logs')->insert([
            'operator_id' => $operatorId,
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

        return $operator->refresh();
    }
}
