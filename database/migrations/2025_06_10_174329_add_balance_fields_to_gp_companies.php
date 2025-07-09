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
        Schema::table('gp_companies', function (Blueprint $table) {
            $table->decimal('credit_balance', 15, 2)->default(0.00)->after('address');
            $table->decimal('balance', 15, 2)->default(0.00)->after('credit_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_companies', function (Blueprint $table) {
            $table->dropColumn('credit_balance');
            $table->dropColumn('balance');
        });
    }
};
