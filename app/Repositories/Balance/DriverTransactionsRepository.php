<?php

namespace App\Repositories\Balance;

use App\Models\GpCompany;
use App\Models\GpDriver;
use App\Models\GpOrder;
use App\Models\GpPickup;
use App\Models\GpPickupOrder;
use App\Models\GpSettings;
use App\Services\NodeService;

class DriverTransactionsRepository
{
    protected $driverBalanceRepository;

    public function __construct(DriverBalanceRepository $driverBalanceRepository)
    {
        $this->driverBalanceRepository = $driverBalanceRepository;
        NodeService::callLogsRefresh();
    }

    // Пополнение баланса водителя (кошелька) наличными от водителя
    public function balance_increase($driverId, $amount)
    {
        $sum = abs($amount);
        $this->driverBalanceRepository->addBalance($driverId, $sum, 'balance_increase');
        NodeService::callLogsRefresh();
    }

    // Снятие с баланса водителя (кошелька)
    public function balance_decrease($driverId, $amount)
    {
        $sum = -abs($amount);
        $this->driverBalanceRepository->addBalance($driverId, $sum, 'balance_increase');
        NodeService::callLogsRefresh();
    }

    // Начисление суммы в кассу по сумме от клиента
    public function cash_client_increase($driverId, $amount)
    {
        $sum = abs($amount);
        $this->driverBalanceRepository->addCashClient($driverId, $sum, 'cash_client_increase');
        NodeService::callLogsRefresh();
    }

    // Начисление суммы в кассу по сумме за услугу
    public function cash_service_increase($driverId, $amount)
    {
        $sum = abs($amount);
        $this->driverBalanceRepository->addCashService($driverId, $sum, 'cash_service_increase');
        NodeService::callLogsRefresh();
    }

    // Начисление суммы в заработок
    public function earning_increase($driverId, $amount)
    {
        $sum = abs($amount);
        $this->driverBalanceRepository->addEarning($driverId, $sum, 'earning_increase');
        NodeService::callLogsRefresh();
    }

    // Подтверждение заработка
    public function earning_decrease($driverId, $amount)
    {
        $sum = -abs($amount);
        $this->driverBalanceRepository->addEarning($driverId, $sum, 'earning_decrease');
        NodeService::callLogsRefresh();
    }

    // Начисление суммы в заработок в систему
    public function earning_pending_increase($driverId, $amount)
    {
        $sum = abs($amount);
        $this->driverBalanceRepository->addEarningPending($driverId, $sum, 'earning_pending_increase');
        NodeService::callLogsRefresh();
    }

    // Подтверждение заработка
    public function earning_pending_decrease($driverId, $amount)
    {
        $sum = -abs($amount);
        $this->driverBalanceRepository->addEarningPending($driverId, $sum, 'earning_pending_decrease');
        NodeService::callLogsRefresh();
    }

    // Пополнение cash_wallet водителя
    public function cash_wallet_increase($driverId, $amount)
    {
        $sum = abs($amount);
        $this->driverBalanceRepository->addCashWallet($driverId, $sum, 'cash_wallet_increase');
        NodeService::callLogsRefresh();
    }

    // Списание с cash_wallet водителя
    public function cash_wallet_decrease($driverId, $amount)
    {
        $sum = -abs($amount);
        $this->driverBalanceRepository->addCashWallet($driverId, $sum, 'cash_wallet_decrease');
        NodeService::callLogsRefresh();
    }

    // Вызов принят водителем
    public function pickup_as_picked_up_price_check($pickupId, $driverId)
    {
        $pickup = GpPickup::where('id', $pickupId)->where('driver_id', $driverId)->first();

        if (!$pickup) {
            return null;
        }

        $pickupOrdersIds = GpPickupOrder::where('pickup_id', $pickup->id)->pluck('order_id')->toArray();
        $driverFee = GpSettings::driverFee();

        if (!$driverFee) {
            return null;
        }

        // Находим сумму всех заказов (sum) по пикапу
        $sumOrders = GpOrder::whereIn('id', $pickupOrdersIds)->sum('sum');
        $company = GpCompany::find($pickup->company_id);
        $companyBalanceRepository = new CompanyBalanceRepository();

        // Если у компании credit_balance больше sum, то отнимаем sum с credit_balance
        if ($company->credit_balance >= $sumOrders) {
            $negativeSum = -abs($sumOrders);
            $companyBalanceRepository->addCreditBalance($company->id, $negativeSum, 'picked_up_cash');
        } else {
            // Иначе добавляем сумму в долг агрегатора
            $positiveSum = abs($sumOrders);
            $companyBalanceRepository->addAgregatorSideBalance($company->id, $positiveSum, 'picked_up_cash');
        }

        // Начисляем сумму товаров в cash_goods водителя
        $positiveSumOrders = abs($sumOrders);
        $this->driverBalanceRepository->addCashGoods($driverId, $positiveSumOrders, 'picked_up_cash');

        // Ищем те заказы где указано что компания заплатит наличными за доставку
        $sum = GpOrder::whereIn('id', $pickupOrdersIds)
            ->where('delivery_pay', 'cash')
            ->sum('delivery_price');

        if ($sum == null || $sum == 0) {
            NodeService::callLogsRefresh();
            return true;
        }

        $sum = abs($sum);
        $servicePart = ($sum * ($driverFee / 100));
        $driverPart = $sum - $servicePart;
        $this->driverBalanceRepository->addEarning($driverId, $driverPart, 'picked_up_cash');
        $this->driverBalanceRepository->addCashService($driverId, $servicePart, 'picked_up_cash');


        NodeService::callLogsRefresh();
        return true;
    }

    // Заказ закрыт водителем
    public function order_as_closed_transaction($orderId, $driverId)
    {
        $driver = GpDriver::find($driverId);
        $order = GpOrder::find($orderId);
        if (!$driver || !$order) {
            return 'Order or driver not found';
        }
        $company = GpCompany::find($order->company_id);
        if (!$company) {
            return 'Company not found';
        }
        $driverFee = GpSettings::driverFee();

        if (!$driverFee) {
            return 'Driver fee not set';
        }

        try {
            $companyBalanceRepository = new CompanyBalanceRepository();
            // Если deliveryPay == client, то добавляем delivery_price комиссию (на основе driverFee) в cash_service водителя
            if ($order->delivery_pay === 'client') {
                $a = abs($order->delivery_price);
                $servicePart = ($a * ($driverFee / 100));
                $driverPart = $a - $servicePart;
                // throw new \RuntimeException("Delivery pay is client, cannot process order: sp " . $servicePart . " dp " . $driverPart);
                $this->driverBalanceRepository->addCashService($driverId, $servicePart, 'order_closed');
                $this->driverBalanceRepository->addEarning($driverId, $driverPart, 'order_closed');
            }
            // Если deliveryPay == balance
            if ($order->delivery_pay === 'balance') {
                // Вычитаем всю стоимость услуги с balance компании
                $negativeSum = -abs($order->delivery_price);
                $companyBalanceRepository->addBalance($company->id, $negativeSum, 'order_closed');
                // Начисляем чать водителя в earning_pending
                $a = abs($order->delivery_price);
                $servicePart = ($a * ($driverFee / 100));
                $driverPart = $a - $servicePart;
                $this->driverBalanceRepository->addEarningPending($driverId, $driverPart, 'order_closed');
            }
            // Если deliverPay == cash, ничего не трогаем, обработан при начале пикапа

            // Вычитаем сумму заказа из cash_goods водителя
            $this->driverBalanceRepository->addCashGoods($driverId, -abs($order->sum), 'order_closed');
            // Начисляем сумму в cash_client водителя
            $sum = abs($order->sum);
            $this->driverBalanceRepository->addCashClient($driverId, $sum, 'order_closed');
            NodeService::callLogsRefresh();

            return true;
        } catch (\Exception $e) {
            return 'Error processing order: ' . $e->getMessage();
        }
    }

    // Закрытие кассы водителя
    public function cash_close($driverId, $amount)
    {
        $sum = abs($amount);

        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return null;
        }

        $remaining = $sum;

        $totalDebt = $driver->cash_client + $driver->cash_service + $driver->cash_company_balance;

        if ($sum > $totalDebt) {
            throw new \RuntimeException("Сумма {$sum} превышает общий долг водителя ({$totalDebt})");
        }

        // Закрываем cash_client
        if ($driver->cash_client > 0) {
            $toClose = min($remaining, $driver->cash_client);
            $this->driverBalanceRepository->addCashClient($driverId, -$toClose, 'cash_close');
            $remaining -= $toClose;
        }

        // Закрываем cash_service
        if ($remaining > 0 && $driver->cash_service > 0) {
            $toClose = min($remaining, $driver->cash_service);
            $this->driverBalanceRepository->addCashService($driverId, -$toClose, 'cash_close');
            $remaining -= $toClose;
        }

        // Закрываем cash_goods
        if ($remaining > 0 && $driver->cash_goods > 0) {
            $toClose = min($remaining, $driver->cash_goods);
            $this->driverBalanceRepository->addCashGoods($driverId, -$toClose, 'cash_close');
            $remaining -= $toClose;
        }

        // Закрываем cash_company_balance
        if ($remaining > 0 && $driver->cash_company_balance > 0) {
            $toClose = min($remaining, $driver->cash_company_balance);
            $this->driverBalanceRepository->addCashCompanyBalance($driverId, -$toClose, 'cash_close');
            $remaining -= $toClose;
        }

        // Закрываем cash_wallet
        if ($remaining > 0 && $driver->cash_wallet > 0) {
            $toClose = min($remaining, $driver->cash_wallet);
            $this->driverBalanceRepository->addCashWallet($driverId, -$toClose, 'cash_close');
            $remaining -= $toClose;
        }

        NodeService::callLogsRefresh();
        return true;
    }

    public function resetEarning($driverId)
    {
        $driver = GpDriver::find($driverId);
        if (!$driver) {
            return 'Driver not found';
        }

        if ($driver->earning == 0) {
            return 'Earning is already zero';
        }

        try {
            $this->driverBalanceRepository->resetEarning($driverId, 'earning_reset');
            NodeService::callLogsRefresh();

            return true;
        } catch (\Exception $e) {
            return 'Error resetting earning: ' . $e->getMessage();
        }
    }
}
