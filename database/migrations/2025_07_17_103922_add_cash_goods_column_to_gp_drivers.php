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
            $table->decimal('cash_goods', 10, 2)->default(0.00)->after('cash_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_drivers', function (Blueprint $table) {
            $table->dropColumn('cash_goods');
        });
    }
};
