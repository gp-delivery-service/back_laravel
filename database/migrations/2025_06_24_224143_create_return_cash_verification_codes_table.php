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
        Schema::create('return_cash_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6);
            $table->foreignUuid('operator_id')
                ->constrained('gp_operators')
                ->onDelete('cascade');
            $table->foreignUuid('driver_id')
                ->constrained('gp_drivers')
                ->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_cash_verification_codes');
    }
};
