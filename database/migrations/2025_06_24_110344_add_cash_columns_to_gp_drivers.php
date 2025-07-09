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
        Schema::table('gp_drivers', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->default(0.00)->after('image');
            $table->decimal('cash_client', 10, 2)->default(0)->after('balance');
            $table->decimal('cash_service', 10, 2)->default(0)->after('cash_client');
            $table->decimal('cash_company_balance', 10, 2)->default(0)->after('cash_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_drivers', function (Blueprint $table) {
            $table->dropColumn(['balance', 'cash_client', 'cash_service', 'cash_company_balance']);
        });
    }
};
