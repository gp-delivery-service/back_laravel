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
            $table->string('car_name')->nullable()->after('phone');
            $table->string('car_number')->nullable()->after('car_name');
            $table->string('image')->nullable()->after('car_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_drivers', function (Blueprint $table) {
            $table->dropColumn('car_name');
            $table->dropColumn('car_number');
            $table->dropColumn('image');
        });
    }
};
