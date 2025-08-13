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
        Schema::table('gp_admins', function (Blueprint $table) {
            $table->decimal('fund', 12, 2)->default(0.00)->after('password');
            $table->decimal('fund_dynamic', 12, 2)->default(0.00)->after('fund');
            $table->decimal('total_earn', 12, 2)->default(0.00)->after('fund_dynamic');
            $table->decimal('total_driver_pay', 12, 2)->default(0.00)->after('total_earn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_admins', function (Blueprint $table) {
            $table->dropColumn(['fund', 'fund_dynamic', 'total_earn', 'total_driver_pay']);
        });
    }
};
