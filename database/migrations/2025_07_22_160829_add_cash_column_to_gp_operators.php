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
        Schema::table('gp_operators', function (Blueprint $table) {
            $table->decimal('cash', 10, 2)->default(0)->after('email');
            $table->boolean('cashier')->default(false)->after('cash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_operators', function (Blueprint $table) {
            $table->dropColumn('cash');
            $table->dropColumn('cashier');
        });
    }
};
