<?php

namespace App\Constants;

class AdminFundLogTags
{
    // Стандартные теги для логов фонда админа
    public const ADMIN_TOP_UP_FUND = 'admin_top_up_fund';
    public const ADMIN_ADD_CASH = 'admin_add_cash';
    public const ADMIN_CLOSE_CASH = 'admin_close_cash';
    
    // Стандартные теги для обновления балансов
    public const FUND_UPDATE = 'fund_update';
    public const FUND_DYNAMIC_UPDATE = 'fund_dynamic_update';
    public const TOTAL_EARN_UPDATE = 'total_earn_update';
    public const TOTAL_DRIVER_PAY_UPDATE = 'total_driver_pay_update';
    
    // Стандартные теги для операторов
    public const CASH_UPDATE = 'cash_update';
    
    // Стандартные теги для водителей
    public const BALANCE_UPDATE = 'balance_update';
    public const CASH_CLIENT_UPDATE = 'cash_client_update';
    public const CASH_SERVICE_UPDATE = 'cash_service_update';
    public const CASH_GOODS_UPDATE = 'cash_goods_update';
    public const CASH_COMPANY_BALANCE_UPDATE = 'cash_company_balance_update';
    public const EARNING_UPDATE = 'earning_update';
    public const EARNING_PENDING_UPDATE = 'earning_pending_update';
    public const CASH_WALLET_UPDATE = 'cash_wallet_update';
    public const EARNING_RESET = 'earning_reset';
    
    // Стандартные теги для компаний
    public const AGREGATOR_SIDE_BALANCE_UPDATE = 'agregator_side_balance_update';
    public const CREDIT_BALANCE_UPDATE = 'credit_balance_update';
    public const CREDIT_BALANCE_INCREASE = 'credit_balance_increase';
    public const CREDIT_BALANCE_CLOSE = 'credit_balance_close';
    
    // Стандартные теги для клиентов
    public const WALLET_UPDATE = 'wallet_update';
    
    /**
     * Получить человекочитаемое название тега
     */
    public static function getDisplayName(string $tag): string
    {
        $tagMap = [
            // Фонд админа
            self::ADMIN_TOP_UP_FUND => 'Пополнение фонда',
            self::ADMIN_ADD_CASH => 'Выдача кассы оператору',
            self::ADMIN_CLOSE_CASH => 'Закрытие кассы оператора',
            
            // Обновления баланса админа
            self::FUND_UPDATE => 'Обновление фонда',
            self::FUND_DYNAMIC_UPDATE => 'Обновление динамичного фонда',
            self::TOTAL_EARN_UPDATE => 'Обновление общего заработка',
            self::TOTAL_DRIVER_PAY_UPDATE => 'Обновление выплат водителям',
            
            // Операторы
            self::CASH_UPDATE => 'Обновление кассы оператора',
            
            // Водители
            self::BALANCE_UPDATE => 'Обновление баланса водителя',
            self::CASH_CLIENT_UPDATE => 'Обновление кассы клиента',
            self::CASH_SERVICE_UPDATE => 'Обновление кассы услуг',
            self::CASH_GOODS_UPDATE => 'Обновление кассы товаров',
            self::CASH_COMPANY_BALANCE_UPDATE => 'Обновление баланса компании',
            self::EARNING_UPDATE => 'Обновление заработка',
            self::EARNING_PENDING_UPDATE => 'Обновление ожидающего заработка',
            self::CASH_WALLET_UPDATE => 'Обновление кошелька',
            self::EARNING_RESET => 'Сброс заработка',
            
            // Компании
            self::AGREGATOR_SIDE_BALANCE_UPDATE => 'Обновление баланса агрегатора',
            self::CREDIT_BALANCE_UPDATE => 'Обновление кредитного баланса',
            self::CREDIT_BALANCE_INCREASE => 'Пополнение кредита компании',
            self::CREDIT_BALANCE_CLOSE => 'Закрытие кредита компании',
            
            // Клиенты
            self::WALLET_UPDATE => 'Обновление кошелька клиента',
        ];
        
        return $tagMap[$tag] ?? $tag;
    }
    
    /**
     * Получить все доступные теги с их отображаемыми названиями
     */
    public static function getAllTags(): array
    {
        return [
            self::ADMIN_TOP_UP_FUND => self::getDisplayName(self::ADMIN_TOP_UP_FUND),
            self::ADMIN_ADD_CASH => self::getDisplayName(self::ADMIN_ADD_CASH),
            self::ADMIN_CLOSE_CASH => self::getDisplayName(self::ADMIN_CLOSE_CASH),
            self::FUND_UPDATE => self::getDisplayName(self::FUND_UPDATE),
            self::FUND_DYNAMIC_UPDATE => self::getDisplayName(self::FUND_DYNAMIC_UPDATE),
            self::TOTAL_EARN_UPDATE => self::getDisplayName(self::TOTAL_EARN_UPDATE),
            self::TOTAL_DRIVER_PAY_UPDATE => self::getDisplayName(self::TOTAL_DRIVER_PAY_UPDATE),
            self::CASH_UPDATE => self::getDisplayName(self::CASH_UPDATE),
            self::BALANCE_UPDATE => self::getDisplayName(self::BALANCE_UPDATE),
            self::CASH_CLIENT_UPDATE => self::getDisplayName(self::CASH_CLIENT_UPDATE),
            self::CASH_SERVICE_UPDATE => self::getDisplayName(self::CASH_SERVICE_UPDATE),
            self::CASH_GOODS_UPDATE => self::getDisplayName(self::CASH_GOODS_UPDATE),
            self::CASH_COMPANY_BALANCE_UPDATE => self::getDisplayName(self::CASH_COMPANY_BALANCE_UPDATE),
            self::EARNING_UPDATE => self::getDisplayName(self::EARNING_UPDATE),
            self::EARNING_PENDING_UPDATE => self::getDisplayName(self::EARNING_PENDING_UPDATE),
            self::CASH_WALLET_UPDATE => self::getDisplayName(self::CASH_WALLET_UPDATE),
            self::EARNING_RESET => self::getDisplayName(self::EARNING_RESET),
            self::AGREGATOR_SIDE_BALANCE_UPDATE => self::getDisplayName(self::AGREGATOR_SIDE_BALANCE_UPDATE),
            self::CREDIT_BALANCE_UPDATE => self::getDisplayName(self::CREDIT_BALANCE_UPDATE),
            self::CREDIT_BALANCE_INCREASE => self::getDisplayName(self::CREDIT_BALANCE_INCREASE),
            self::CREDIT_BALANCE_CLOSE => self::getDisplayName(self::CREDIT_BALANCE_CLOSE),
            self::WALLET_UPDATE => self::getDisplayName(self::WALLET_UPDATE),
        ];
    }
}
