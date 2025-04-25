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
        Schema::create('gp_operator_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('operator_id')->constrained('gp_operators')->onDelete('cascade');
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
        Schema::dropIfExists('gp_operator_refresh_tokens');
    }
};
