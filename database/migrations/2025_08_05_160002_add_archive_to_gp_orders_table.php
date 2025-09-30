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
            $table->boolean('archive')->default(false)->after('delivery_pay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_orders', function (Blueprint $table) {
            $table->dropColumn('archive');
        });
    }
};
