<?php

namespace App\Repositories\Balance;

use App\Services\NodeService;

class OperatorTransactionsRepository
{
    protected $operatorBalanceRepository;

    public function __construct(OperatorBalanceRepository $operatorBalanceRepository)
    {
        $this->operatorBalanceRepository = $operatorBalanceRepository;
    }

    // Пополнение баланса оператора наличными
    public function cash_increase($operatorId, $amount)
    {
        $sum = abs($amount);
        NodeService::callUserRefresh();
        return $this->operatorBalanceRepository->addCash($operatorId, $sum, 'cash_increase');
    }

    // Снятие наличных с баланса оператора
    public function cash_decrease($operatorId, $amount)
    {
        $sum = -abs($amount);
        NodeService::callUserRefresh();
        return $this->operatorBalanceRepository->addCash($operatorId, $sum, 'cash_decrease');
    }
}
