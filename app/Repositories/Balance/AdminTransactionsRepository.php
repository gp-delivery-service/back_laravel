<?php

namespace App\Repositories\Balance;

use App\Models\GpAdmin;
use App\Services\NodeService;

class AdminTransactionsRepository
{
    protected $adminBalanceRepository;

    public function __construct(AdminBalanceRepository $adminBalanceRepository)
    {
        $this->adminBalanceRepository = $adminBalanceRepository;
        NodeService::callLogsRefresh();
    }

    // Пополнение фонда администратора
    public function fund_increase($adminId, $amount)
    {
        $sum = abs($amount);
        $this->adminBalanceRepository->addFund($adminId, $sum, 'fund_increase');
        NodeService::callLogsRefresh();
    }

    // Списание с фонда администратора
    public function fund_decrease($adminId, $amount)
    {
        $sum = -abs($amount);
        $this->adminBalanceRepository->addFund($adminId, $sum, 'fund_decrease');
        NodeService::callLogsRefresh();
    }

    // Пополнение динамического фонда администратора
    public function fund_dynamic_increase($adminId, $amount)
    {
        $sum = abs($amount);
        $this->adminBalanceRepository->addFundDynamic($adminId, $sum, 'fund_dynamic_increase');
        NodeService::callLogsRefresh();
    }

    // Списание с динамического фонда администратора
    public function fund_dynamic_decrease($adminId, $amount)
    {
        $sum = -abs($amount);
        $this->adminBalanceRepository->addFundDynamic($adminId, $sum, 'fund_dynamic_decrease');
        NodeService::callLogsRefresh();
    }

    // Начисление общего заработка администратора
    public function total_earn_increase($adminId, $amount)
    {
        $sum = abs($amount);
        $this->adminBalanceRepository->addTotalEarn($adminId, $sum, 'total_earn_increase');
        NodeService::callLogsRefresh();
    }

    // Списание с общего заработка администратора
    public function total_earn_decrease($adminId, $amount)
    {
        $sum = -abs($amount);
        $this->adminBalanceRepository->addTotalEarn($adminId, $sum, 'total_earn_decrease');
        NodeService::callLogsRefresh();
    }

    // Начисление общей выплаты водителям
    public function total_driver_pay_increase($adminId, $amount)
    {
        $sum = abs($amount);
        $this->adminBalanceRepository->addTotalDriverPay($adminId, $sum, 'total_driver_pay_increase');
        NodeService::callLogsRefresh();
    }

    // Списание с общей выплаты водителям
    public function total_driver_pay_decrease($adminId, $amount)
    {
        $sum = -abs($amount);
        $this->adminBalanceRepository->addTotalDriverPay($adminId, $sum, 'total_driver_pay_decrease');
        NodeService::callLogsRefresh();
    }

    // Сброс общего заработка администратора
    public function resetTotalEarn($adminId)
    {
        $admin = GpAdmin::find($adminId);
        if (!$admin) {
            return 'Admin not found';
        }

        if ($admin->total_earn == 0) {
            return 'Total earn is already zero';
        }

        try {
            $resetAmount = -$admin->total_earn;
            $this->adminBalanceRepository->addTotalEarn($adminId, $resetAmount, 'total_earn_reset');
            NodeService::callLogsRefresh();

            return true;
        } catch (\Exception $e) {
            return 'Error resetting total earn: ' . $e->getMessage();
        }
    }

    // Сброс общей выплаты водителям
    public function resetTotalDriverPay($adminId)
    {
        $admin = GpAdmin::find($adminId);
        if (!$admin) {
            return 'Admin not found';
        }

        if ($admin->total_driver_pay == 0) {
            return 'Total driver pay is already zero';
        }

        try {
            $resetAmount = -$admin->total_driver_pay;
            $this->adminBalanceRepository->addTotalDriverPay($adminId, $resetAmount, 'total_driver_pay_reset');
            NodeService::callLogsRefresh();

            return true;
        } catch (\Exception $e) {
            return 'Error resetting total driver pay: ' . $e->getMessage();
        }
    }
}
