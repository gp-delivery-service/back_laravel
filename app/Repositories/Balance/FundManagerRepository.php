<?php

namespace App\Repositories\Balance;

use App\Models\GpAdmin;
use App\Models\GpCompany;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\DB;

class FundManagerRepository
{
    /**
     * Увеличивает fund_dynamic админа (когда списывается credit_balance компании)
     * Вызывается при закрытии заказа, когда списывается credit_balance
     */
    public function increaseFundDynamic($amount, $tag = 'credit_balance_close', $companyId = null, $driverId = null, $pickupId = null)
    {
        $admin = GpAdmin::first();
        
        if (!$admin) {
            return null;
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($admin, $amount, $tag, $userData, $companyId, $driverId, $pickupId) {
            // Сохраняем старое значение для логов
            $oldFundDynamic = $admin->fund_dynamic;

            // Увеличиваем fund_dynamic админа
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
                'operator_id' => ($userData['user_type'] === 'App\Models\GpOperator') ? $userData['user_id'] : null,
                'company_id' => $companyId,
                'driver_id' => $driverId,
                'pickup_id' => $pickupId,
                'old_total_earn' => null,
                'new_total_earn' => null,
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
     * Уменьшает fund_dynamic админа (когда пополняется credit_balance компании)
     * Вызывается при пополнении credit_balance админом
     */
    public function decreaseFundDynamic($amount, $tag = 'credit_balance_increase', $companyId = null, $driverId = null, $pickupId = null)
    {
        $admin = GpAdmin::first();
        
        if (!$admin) {
            return null;
        }

        // Проверяем, достаточно ли средств в fund_dynamic
        if ($admin->fund_dynamic < $amount) {
            throw new \RuntimeException("Недостаточно средств в фонде. Доступно: {$admin->fund_dynamic}, требуется: {$amount}");
        }

        $userData = LogHelper::getUserLogData();

        DB::transaction(function () use ($admin, $amount, $tag, $userData, $companyId, $driverId, $pickupId) {
            // Сохраняем старое значение для логов
            $oldFundDynamic = $admin->fund_dynamic;

            // Уменьшаем fund_dynamic админа
            $admin->fund_dynamic -= $amount;
            $admin->save();

            // Логируем изменение фонда админа
            // user_id должен быть ID админа, так как внешний ключ ссылается на gp_admins
            $logUserId = ($userData['user_type'] === 'App\Models\GpAdmin') ? $userData['user_id'] : null;

            DB::table('gp_admin_fund_logs')->insert([
                'admin_id' => $admin->id,
                'amount' => -$amount,
                'old_fund_dynamic' => $oldFundDynamic,
                'new_fund_dynamic' => $admin->fund_dynamic,
                'tag' => $tag,
                'operator_id' => ($userData['user_type'] === 'App\Models\GpOperator') ? $userData['user_id'] : null,
                'company_id' => $companyId,
                'driver_id' => $driverId,
                'pickup_id' => $pickupId,
                'old_total_earn' => null,
                'new_total_earn' => null,
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
     * Получает информацию о текущем состоянии фонда
     */
    public function getFundInfo()
    {
        $admin = GpAdmin::first();
        
        if (!$admin) {
            return null;
        }

        // Получаем сумму всех credit_balance компаний
        $companiesCreditBalance = GpCompany::sum('credit_balance');

        return [
            'fund' => $admin->fund,
            'fund_dynamic' => $admin->fund_dynamic,
            'fund_current' => $admin->fund_current,
            'total_earn' => $admin->total_earn,
            'is_balanced' => $admin->isFundBalanced(),
            'difference' => $admin->getFundDifference(),
            'companies_credit_balance' => $companiesCreditBalance,
        ];
    }
}
