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
        Schema::create('gp_company_manager_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('manager_id')->constrained('gp_company_managers')->onDelete('cascade');
            $table->string('device_id');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_company_manager_refresh_tokens');
    }
};
