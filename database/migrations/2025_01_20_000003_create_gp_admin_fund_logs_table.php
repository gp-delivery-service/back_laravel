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
        Schema::create('gp_admin_fund_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('admin_id')
                ->constrained('gp_admins')
                ->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('old_fund_dynamic', 12, 2);
            $table->decimal('new_fund_dynamic', 12, 2);
            $table->string('tag');
            $table->foreignUuid('operator_id')
                ->nullable()
                ->constrained('gp_operators')
                ->nullOnDelete();
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('gp_admins')
                ->nullOnDelete();
            $table->string('user_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_admin_fund_logs');
    }
};
