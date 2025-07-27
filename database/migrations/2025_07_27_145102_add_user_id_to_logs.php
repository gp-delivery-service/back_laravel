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
        // Добавляем user_id и user_type в gp_company_balance_logs
        Schema::table('gp_company_balance_logs', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->index(['user_id', 'user_type']);
        });

        // Добавляем user_id и user_type в gp_driver_balance_logs
        Schema::table('gp_driver_balance_logs', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->index(['user_id', 'user_type']);
        });

        // Добавляем user_id и user_type в gp_operator_balance_logs
        Schema::table('gp_operator_balance_logs', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->index(['user_id', 'user_type']);
        });

        // Добавляем user_id и user_type в gp_pickup_logs
        Schema::table('gp_pickup_logs', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->index(['user_id', 'user_type']);
        });

        // Добавляем user_id и user_type в gp_pickup_order_logs
        Schema::table('gp_pickup_order_logs', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->index(['user_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем user_id и user_type из gp_company_balance_logs
        Schema::table('gp_company_balance_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'user_type']);
            $table->dropColumn(['user_id', 'user_type']);
        });

        // Удаляем user_id и user_type из gp_driver_balance_logs
        Schema::table('gp_driver_balance_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'user_type']);
            $table->dropColumn(['user_id', 'user_type']);
        });

        // Удаляем user_id и user_type из gp_operator_balance_logs
        Schema::table('gp_operator_balance_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'user_type']);
            $table->dropColumn(['user_id', 'user_type']);
        });

        // Удаляем user_id и user_type из gp_pickup_logs
        Schema::table('gp_pickup_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'user_type']);
            $table->dropColumn(['user_id', 'user_type']);
        });

        // Удаляем user_id и user_type из gp_pickup_order_logs
        Schema::table('gp_pickup_order_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'user_type']);
            $table->dropColumn(['user_id', 'user_type']);
        });
    }
};
