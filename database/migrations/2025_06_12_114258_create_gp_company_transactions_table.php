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
        Schema::create('gp_company_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('type', [
                'credit_increase', // Выдача кредита заведению наличными
                'credit_close_cash', // Закрытие кредита заведения наличными
                'credit_close_balance', // Закрытие кредита заведения с баланса
                'credit_close_order', // Закрытие кредита заведения по заказу
                'aggregator_debt_increase_order', // Увеличение долга агрегатора по заказу
                'aggregator_debt_decrease_cash',  // Уменьшение долга агрегатора наличными
                'aggregator_debt_decrease_credit', // Уменьшение долга агрегатора по кредиту
                'balance_increase_cash', // Увеличение баланса заведения наличными
                'balance_decrease_order' // Уменьшение баланса заведения по заказу
            ]);
            $table->decimal('amount', 12, 2);

            $table->foreignUuid('operator_id')
                ->nullable()
                ->constrained(table: 'gp_operators')
                ->nullOnDelete();

            $table->foreignUuid('admin_id')
                ->nullable()
                ->constrained(table: 'gp_admins')
                ->nullOnDelete();

            $table->foreignUuid('company_id')
                ->constrained(table: 'gp_companies')
                ->onDelete('cascade');

            $table->timestamp('created_at')->useCurrent();

            $table->text('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_company_transactions');
    }
};
