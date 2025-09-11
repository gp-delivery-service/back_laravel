<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gp_admin_fund_logs', function (Blueprint $table) {
            // Добавляем поля для связи с компанией, водителем и заказом
            $table->foreignUuid('company_id')
                ->nullable()
                ->constrained('gp_companies')
                ->nullOnDelete();
            
            $table->foreignUuid('driver_id')
                ->nullable()
                ->constrained('gp_drivers')
                ->nullOnDelete();
            
            $table->foreignId('pickup_id')
                ->nullable()
                ->constrained('gp_pickups')
                ->nullOnDelete();
            
            // Добавляем поля для отслеживания изменений total_earn
            $table->decimal('old_total_earn', 12, 2)->nullable();
            $table->decimal('new_total_earn', 12, 2)->nullable();
            
            // Добавляем индексы для оптимизации запросов
            $table->index(['company_id']);
            $table->index(['driver_id']);
            $table->index(['pickup_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_admin_fund_logs', function (Blueprint $table) {
            $table->dropIndex(['pickup_id']);
            $table->dropIndex(['driver_id']);
            $table->dropIndex(['company_id']);
            
            $table->dropForeign(['pickup_id']);
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['company_id']);
            
            $table->dropColumn([
                'company_id',
                'driver_id', 
                'pickup_id',
                'old_total_earn',
                'new_total_earn'
            ]);
        });
    }
};
