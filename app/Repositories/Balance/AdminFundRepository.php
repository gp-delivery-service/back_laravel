<?php

namespace App\Repositories\Balance;

use App\Helpers\LogHelper;
use App\Models\GpAdmin;
use App\Models\GpOperator;
use Illuminate\Support\Facades\DB;

class AdminFundRepository
{
    /**
     * Пополнение кассы оператора из фонда админа
     */
    public function addCashToOperator($operatorId, $amount, $tag = 'admin_add_cash')
    {
        $operator = GpOperator::find($operatorId);
        $admin = GpAdmin::first(); // Админ всегда один

        if (!$operator || !$admin) {
            return null;
        }

        // Проверяем, достаточно ли средств в fund_dynamic
        if ($admin->fund_dynamic < $amount) {
            throw new \RuntimeException("Недостаточно средств в фонде. Доступно: {$admin->fund_dynamic}, требуется: {$amount}");
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($operator, $admin, $amount, $tag, $userData) {
            // Уменьшаем fund_dynamic админа
            $oldFundDynamic = $admin->fund_dynamic;
            $admin->fund_dynamic -= $amount;
            $admin->save();

            // Увеличиваем кассу оператора
            $oldOperatorCash = $operator->cash;
            $operator->cash += $amount;
            $operator->save();

            // Логируем изменение фонда админа
            // user_id должен быть ID админа, так как внешний ключ ссылается на gp_admins
            $logUserId = ($userData['user_type'] === 'App\Models\GpAdmin') ? $userData['user_id'] : null;

            DB::table('gp_admin_fund_logs')->insert([
                'admin_id' => $admin->id,
                'amount' => -$amount,
                'old_fund_dynamic' => $oldFundDynamic,
                'new_fund_dynamic' => $admin->fund_dynamic,
                'tag' => $tag,
                'operator_id' => $operator->id,
                'user_id' => $logUserId,
                'user_type' => $userData['user_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Логируем изменение кассы оператора
            DB::table('gp_operator_balance_logs')->insert([
                'operator_id' => $operator->id,
                'amount' => $amount,
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

        return $operator->refresh();
    }

    /**
     * Закрытие кассы оператора (возврат в фонд админа)
     */
    public function closeOperatorCash($operatorId, $amount, $tag = 'admin_close_cash')
    {
        $operator = GpOperator::find($operatorId);
        $admin = GpAdmin::first(); // Админ всегда один

        if (!$operator || !$admin) {
            return null;
        }

        // Проверяем, достаточно ли средств в кассе оператора
        if ($operator->cash < $amount) {
            throw new \RuntimeException("Недостаточно средств в кассе оператора. Доступно: {$operator->cash}, требуется: {$amount}");
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($operator, $admin, $amount, $tag, $userData) {
            // Увеличиваем fund_dynamic админа
            $oldFundDynamic = $admin->fund_dynamic;
            $admin->fund_dynamic += $amount;
            $admin->save();

            // Уменьшаем кассу оператора
            $oldOperatorCash = $operator->cash;
            $operator->cash -= $amount;
            $operator->save();

            // Логируем изменение фонда админа
            // user_id должен быть ID админа, так как внешний ключ ссылается на gp_admins
            $logUserId = ($userData['user_type'] === 'App\Models\GpAdmin') ? $userData['user_id'] : null;

            DB::table('gp_admin_fund_logs')->insert([
                'admin_id' => $admin->id,
                'amount' => $amount,
                'old_fund_dynamic' => $oldFundDynamic,
                'new_fund_dynamic' => $admin->fund_dynamic,
                'tag' => $tag,
                'operator_id' => $operator->id,
                'user_id' => $logUserId,
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

        return $operator->refresh();
    }

    /**
     * Получение информации о фонде админа
     */
    public function getFundInfo()
    {
        $fundManager = new \App\Repositories\Balance\FundManagerRepository();
        return $fundManager->getFundInfo();
    }

    /**
     * Проверка баланса фонда
     */
    public function checkFundBalance()
    {
        $admin = GpAdmin::first();
        
        if (!$admin) {
            return null;
        }

        return [
            'is_balanced' => $admin->isFundBalanced(),
            'fund' => $admin->fund,
            'fund_current' => $admin->fund_current,
            'difference' => $admin->getFundDifference(),
            'fund_dynamic' => $admin->fund_dynamic,
        ];
    }

    /**
     * Пополнение общего фонда админа
     * Увеличивает как статичный фонд (fund), так и динамичный (fund_dynamic)
     */
    public function topUpFund($amount, $tag = 'admin_top_up_fund')
    {
        $admin = GpAdmin::first();

        if (!$admin) {
            return null;
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($admin, $amount, $tag, $userData) {
            // Сохраняем старые значения для логов
            $oldFund = $admin->fund;
            $oldFundDynamic = $admin->fund_dynamic;

            // Увеличиваем статичный фонд
            $admin->fund += $amount;
            
            // Увеличиваем динамичный фонд
            $admin->fund_dynamic += $amount;
            
            $admin->save();

            // Логируем изменение фонда админа
            // user_id должен быть ID админа, так как внешний ключ ссылается на gp_admins
            $logUserId = ($userData['user_type'] === 'App\Models\GpAdmin') ? $userData['user_id'] : null;

            DB::table('gp_admin_fund_logs')->insert([
                'admin_id' => $admin->id,
                'amount' => $amount,
                'old_fund_dynamic' => $oldFundDynamic,
                'new_fund_dynamic' => $admin->fund_dynamic,
                'tag' => $tag,
                'operator_id' => null,
                'user_id' => $logUserId,
                'user_type' => $userData['user_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return [
            'fund' => $admin->fund,
            'fund_dynamic' => $admin->fund_dynamic
        ];
    }

    /**
     * Увеличивает общий заработок админа
     * Вызывается при закрытии кассы водителя (списание с cash_service) 
     * и при закрытии заказа с оплатой с баланса (комиссия агрегатора)
     */
    public function increaseTotalEarn($amount, $tag = 'total_earn_increase')
    {
        $admin = GpAdmin::first();

        if (!$admin) {
            return null;
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($admin, $amount, $tag, $userData) {
            // Сохраняем старое значение для логов
            $oldTotalEarn = $admin->total_earn;

            // Увеличиваем общий заработок админа
            $admin->total_earn += $amount;
            $admin->save();

            // Логируем изменение общего заработка админа
            // user_id должен быть ID админа, так как внешний ключ ссылается на gp_admins
            $logUserId = ($userData['user_type'] === 'App\Models\GpAdmin') ? $userData['user_id'] : null;

            DB::table('gp_admin_fund_logs')->insert([
                'admin_id' => $admin->id,
                'amount' => $amount,
                'old_fund_dynamic' => $admin->fund_dynamic, // Используем fund_dynamic для совместимости
                'new_fund_dynamic' => $admin->fund_dynamic, // Не изменяется
                'tag' => $tag,
                'operator_id' => ($userData['user_type'] === 'App\Models\GpOperator') ? $userData['user_id'] : null,
                'user_id' => $logUserId,
                'user_type' => $userData['user_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return [
            'fund' => $admin->fund,
            'fund_dynamic' => $admin->fund_dynamic,
            'total_earn' => $admin->total_earn
        ];
    }
}
