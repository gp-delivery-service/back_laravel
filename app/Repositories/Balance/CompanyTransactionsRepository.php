<?php

namespace App\Repositories\Balance;

use App\Models\GpCompany;
use App\Services\NodeService;
use Illuminate\Support\Facades\DB;

class CompanyTransactionsRepository
{
    protected $companyBalanceRepository;

    public function __construct(CompanyBalanceRepository $companyBalanceRepository)
    {
        $this->companyBalanceRepository = $companyBalanceRepository;
    }

    // Выдача кредита заведению наличными
    public function credit_increase($companyId, $amount)
    {
        $this->companyBalanceRepository->addCreditBalance($companyId, $amount, 'credit_increase');
        NodeService::callLogsRefresh();
    }
    
    // Запрос возврата кредита наличными заведения (обнуление)
    public function credit_close_cash($companyId, $amount)
    {
        $this->companyBalanceRepository->addCreditBalance($companyId, $amount, 'credit_close_cash');
        NodeService::callLogsRefresh();
    }
    
    // Погашение кредита балансом заведения
    public function credit_close_balance($companyId, $amount)
    {
        $negativeAmount = -abs($amount);
        $this->companyBalanceRepository->addBalance($companyId, $negativeAmount, 'credit_close_balance');
        $this->companyBalanceRepository->addCreditBalance($companyId, $negativeAmount, 'credit_close_balance');
        NodeService::callLogsRefresh();
    }
    
    // Погашения кредита наличными клиента при доставленом заказе
    public function credit_close_order($companyId, $amount)
    {
        $negativeSum = -abs($amount);
        $this->companyBalanceRepository->addCreditBalance($companyId, $negativeSum, 'credit_close_order');
        NodeService::callLogsRefresh();
    }
    
    // Увеличение долга агрегатора при заказе, если кредит отрицательный
    public function aggregator_debt_increase_order($companyId, $amount)
    {
        $positiveSum = abs($amount);
        $this->companyBalanceRepository->addAgregatorSideBalance($companyId, $positiveSum, 'aggregator_debt_increase_order');
        NodeService::callLogsRefresh();
    }
    
    // Уменьшение долга агрегатора отправкой наличных
    public function aggregator_debt_decrease_cash($companyId, $amount)
    {
        $sum = -abs($amount);
        $this->companyBalanceRepository->addAgregatorSideBalance($companyId, $sum, 'aggregator_debt_decrease_cash');
        NodeService::callLogsRefresh();
    }
    
    // Уменьшение долга агрегатора с кредита заведения
    public function aggregator_debt_decrease_credit($companyId, $amount)
    {
        $sum = -abs($amount);
        $this->companyBalanceRepository->addAgregatorSideBalance($companyId, $sum, 'aggregator_debt_decrease_credit');
        $this->companyBalanceRepository->addCreditBalance($companyId, $sum, 'aggregator_debt_decrease_credit');
        NodeService::callLogsRefresh();
    }
    
    // Пополнение баланса заведения наличными
    public function balance_increase_cash($companyId, $amount)
    {
        $sum = abs($amount);
        $this->companyBalanceRepository->addBalance($companyId, $sum, 'balance_increase_cash');
        NodeService::callLogsRefresh();
    }
    
    // Списание с баланса заведения оплаты за услугу доставки
    public function balance_decrease_order($companyId, $amount)
    {
        $sum = -abs($amount);
        $this->companyBalanceRepository->addBalance($companyId, $sum, 'balance_decrease_order');
        NodeService::callLogsRefresh();
    }
    
    
}
