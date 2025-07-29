<?php

namespace App\Repositories\Balance;

use App\Services\NodeService;

class ClientTransactionsRepository
{
    protected $clientBalanceRepository;

    public function __construct(ClientBalanceRepository $clientBalanceRepository)
    {
        $this->clientBalanceRepository = $clientBalanceRepository;
    }

    // Пополнение кошелька клиента
    public function wallet_increase($clientId, $amount)
    {
        $sum = abs($amount);
        $this->clientBalanceRepository->addWallet($clientId, $sum, 'wallet_increase');
        NodeService::callLogsRefresh();
    }

    // Списание с кошелька клиента
    public function wallet_decrease($clientId, $amount)
    {
        $sum = -abs($amount);
        $this->clientBalanceRepository->addWallet($clientId, $sum, 'wallet_decrease');
        NodeService::callLogsRefresh();
    }
}