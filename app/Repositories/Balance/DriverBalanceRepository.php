<?php

namespace App\Repositories\Balance;

use App\Helpers\LogHelper;
use App\Models\GpDriver;
use Illuminate\Support\Facades\DB;

class DriverBalanceRepository
{
    public function addBalance($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->balance;
        $tag = $tag ?: 'balance_update';
        $column = 'balance';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('balance', $amount);

        $newAmount = GpDriver::find($driverId)->balance;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }

    public function addCashClient($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->cash_client;
        $tag = $tag ?: 'cash_client_update';
        $column = 'cash_client';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('cash_client', $amount);

        $newAmount = GpDriver::find($driverId)->cash_client;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }

    public function addCashService($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->cash_service;
        $tag = $tag ?: 'cash_service_update';
        $column = 'cash_service';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('cash_service', $amount);

        $newAmount = GpDriver::find($driverId)->cash_service;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }

    public function addCashGoods($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->cash_goods;
        $tag = $tag ?: 'cash_goods_update';
        $column = 'cash_goods';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('cash_goods', $amount);

        $newAmount = GpDriver::find($driverId)->cash_goods;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }

    public function addCashCompanyBalance($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->cash_company_balance;
        $tag = $tag ?: 'cash_company_balance_update';
        $column = 'cash_company_balance';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('cash_company_balance', $amount);

        $newAmount = GpDriver::find($driverId)->cash_company_balance;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }

    public function addEarning($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->earning;
        $tag = $tag ?: 'earning_update';
        $column = 'earning';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('earning', $amount);

        $newAmount = GpDriver::find($driverId)->earning;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }

    public function addEarningPending($driverId, $amount, $tag)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $oldAmount = $driver->earning_pending;
        $tag = $tag ?: 'earning_pending_update';
        $column = 'earning_pending';
        $userData = LogHelper::getUserLogData();

        DB::table('gp_drivers')
            ->where('id', $driverId)
            ->increment('earning_pending', $amount);

        $newAmount = GpDriver::find($driverId)->earning_pending;

        DB::table('gp_driver_balance_logs')->insert([
            'driver_id' => $driverId,
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

        return $driver->refresh();
    }
}
