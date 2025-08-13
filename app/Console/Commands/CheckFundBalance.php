<?php

namespace App\Console\Commands;

use App\Models\GpAdmin;
use App\Models\GpCompany;
use App\Models\GpDriver;
use App\Models\GpOperator;
use Illuminate\Console\Command;

class CheckFundBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fund:check-balance {--detailed : Show detailed breakdown}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check admin fund balance and show detailed breakdown';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admin = GpAdmin::first();
        
        if (!$admin) {
            $this->error('Admin not found!');
            return 1;
        }

        $this->info('=== ФОНД АДМИНА ===');
        $this->info("Fund (статичный): {$admin->fund} TMT");
        $this->info("Fund Dynamic: {$admin->fund_dynamic} TMT");
        $this->info("Fund Current: {$admin->fund_current} TMT");
        $this->info("Total Earn: {$admin->total_earn} TMT");
        
        $difference = $admin->getFundDifference();
        $isBalanced = $admin->isFundBalanced();
        
        if ($isBalanced) {
            $this->info('✅ Баланс сходится!');
        } else {
            $this->error("❌ Баланс НЕ сходится! Разница: {$difference} TMT");
        }

        if ($this->option('detailed')) {
            $this->showDetailedBreakdown();
        }

        return $isBalanced ? 0 : 1;
    }

    private function showDetailedBreakdown()
    {
        $this->newLine();
        $this->info('=== ДЕТАЛЬНАЯ РАЗБИВКА ФОНДА ===');

        // Фонд админа
        $admin = GpAdmin::first();
        $this->info("Фонд админа:");
        $this->line("  - fund_dynamic (доступные деньги): {$admin->fund_dynamic} TMT");

        // Кредиты компаний (деньги из фонда)
        $companiesCreditBalance = GpCompany::sum('credit_balance');
        $this->info("Кредиты компаний (деньги из фонда): {$companiesCreditBalance} TMT");

        // Расчет fund_current
        $fundCurrent = $admin->fund_dynamic + $companiesCreditBalance;
        $this->info("fund_current = fund_dynamic + credit_balance = {$fundCurrent} TMT");

        $this->newLine();
        $this->info('=== ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ ===');

        // Операторы (не входят в расчет фонда)
        $operatorsCash = GpOperator::sum('cash');
        $this->info("Кассы операторов (не входят в фонд): {$operatorsCash} TMT");

        // Компании - другие балансы
        $companiesBalance = GpCompany::sum('balance');
        $companiesAgregatorSideBalance = GpCompany::sum('agregator_side_balance');
        $this->info("Другие балансы компаний:");
        $this->line("  - balance: {$companiesBalance} TMT");
        $this->line("  - agregator_side_balance: {$companiesAgregatorSideBalance} TMT");

        // Водители (не входят в расчет фонда)
        $driversBalance = GpDriver::sum('balance');
        $driversCashClient = GpDriver::sum('cash_client');
        $driversCashGoods = GpDriver::sum('cash_goods');
        $this->info("Балансы водителей (не входят в фонд):");
        $this->line("  - balance: {$driversBalance} TMT");
        $this->line("  - cash_client: {$driversCashClient} TMT");
        $this->line("  - cash_goods: {$driversCashGoods} TMT");
    }
}
