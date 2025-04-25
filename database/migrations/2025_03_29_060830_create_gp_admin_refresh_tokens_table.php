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
        Schema::create('gp_admin_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('admin_id')->constrained('gp_admins')->onDelete('cascade');
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
        Schema::dropIfExists('gp_admin_refresh_tokens');
    }
};
