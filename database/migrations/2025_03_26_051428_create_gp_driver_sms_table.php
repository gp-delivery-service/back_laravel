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
        Schema::create('gp_driver_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')
                ->nullable(false)
                ->constrained(table: 'gp_drivers', column: 'id')
                ->onDelete('cascade');
            $table->string('sms');
            $table->string('salt');
            $table->boolean('active');
            $table->dateTime('expired_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_driver_sms');
    }
};
