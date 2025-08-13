<?php

namespace App\Repositories\Balance;

use App\Helpers\LogHelper;
use App\Models\GpAdmin;
use Illuminate\Support\Facades\DB;

class AdminBalanceRepository
{
    public function addFund($adminId, $amount, $tag)
    {
        $admin = GpAdmin::find($adminId);
        if (!$admin) {
            return null;
        }

        $oldAmount = $admin->fund;
        $tag = $tag ?: 'fund_update';
        $column = 'fund';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_admins')
            ->where('id', $adminId)
            ->increment('fund', $amount);

        $newAmount = GpAdmin::find($adminId)->fund;

        DB::table('gp_admin_balance_logs')->insert([
            'admin_id' => $adminId,
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

        return $admin->refresh();
    }

    public function addFundDynamic($adminId, $amount, $tag)
    {
        $admin = GpAdmin::find($adminId);
        if (!$admin) {
            return null;
        }

        $oldAmount = $admin->fund_dynamic;
        $tag = $tag ?: 'fund_dynamic_update';
        $column = 'fund_dynamic';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_admins')
            ->where('id', $adminId)
            ->increment('fund_dynamic', $amount);

        $newAmount = GpAdmin::find($adminId)->fund_dynamic;

        DB::table('gp_admin_balance_logs')->insert([
            'admin_id' => $adminId,
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

        return $admin->refresh();
    }

    public function addTotalEarn($adminId, $amount, $tag)
    {
        $admin = GpAdmin::find($adminId);
        if (!$admin) {
            return null;
        }

        $oldAmount = $admin->total_earn;
        $tag = $tag ?: 'total_earn_update';
        $column = 'total_earn';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_admins')
            ->where('id', $adminId)
            ->increment('total_earn', $amount);

        $newAmount = GpAdmin::find($adminId)->total_earn;

        DB::table('gp_admin_balance_logs')->insert([
            'admin_id' => $adminId,
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

        return $admin->refresh();
    }

    public function addTotalDriverPay($adminId, $amount, $tag)
    {
        $admin = GpAdmin::find($adminId);
        if (!$admin) {
            return null;
        }

        $oldAmount = $admin->total_driver_pay;
        $tag = $tag ?: 'total_driver_pay_update';
        $column = 'total_driver_pay';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_admins')
            ->where('id', $adminId)
            ->increment('total_driver_pay', $amount);

        $newAmount = GpAdmin::find($adminId)->total_driver_pay;

        DB::table('gp_admin_balance_logs')->insert([
            'admin_id' => $adminId,
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

        return $admin->refresh();
    }
}
