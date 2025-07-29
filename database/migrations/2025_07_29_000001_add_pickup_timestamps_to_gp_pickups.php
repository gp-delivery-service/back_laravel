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
        Schema::table('gp_pickups', function (Blueprint $table) {
            $table->timestamp('picked_up_at')->nullable()->after('search_started_at');
            $table->timestamp('closed_at')->nullable()->after('picked_up_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gp_pickups', function (Blueprint $table) {
            $table->dropColumn(['picked_up_at', 'closed_at']);
        });
    }
};
