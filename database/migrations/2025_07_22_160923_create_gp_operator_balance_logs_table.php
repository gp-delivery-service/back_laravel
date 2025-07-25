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
        Schema::create('gp_operator_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('operator_id')->constrained('gp_operators')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('old_amount', 10, 2);
            $table->decimal('new_amount', 10, 2);
            $table->string('tag')->nullable(false);
            $table->string('column')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_operator_balance_logs');
    }
};
