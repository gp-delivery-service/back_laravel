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
        Schema::create('gp_client_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('old_amount', 10, 2);
            $table->decimal('new_amount', 10, 2);
            $table->string('tag');
            $table->string('column');
            $table->uuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('gp_clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_client_balance_logs');
    }
};