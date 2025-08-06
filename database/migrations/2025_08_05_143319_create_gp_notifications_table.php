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
        Schema::create('gp_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id')->nullable()->constrained('gp_clients')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('gp_orders')->onDelete('cascade');
            $table->string('type'); // order_status, system, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // дополнительные данные
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->string('fcm_token')->nullable(); // токен для Firebase
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['client_id', 'is_read']);
            $table->index(['order_id']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_notifications');
    }
};
