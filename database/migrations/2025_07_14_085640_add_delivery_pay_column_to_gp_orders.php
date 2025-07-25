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
        Schema::table('gp_orders', function (Blueprint $table) {
            $table->string('delivery_pay')->nullable(false)->default('balance')->after('delivery_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_pay');
        });
    }
};
