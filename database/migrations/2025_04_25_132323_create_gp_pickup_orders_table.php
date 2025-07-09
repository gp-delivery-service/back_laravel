<?php

use App\Constants\GpPickupOrderStatus;
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
        Schema::create('gp_pickup_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_id')->nullable()->constrained('gp_pickups')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('gp_orders')->onDelete('set null');
            $table->string('status')->default(GpPickupOrderStatus::INHERITED->value);
            $table->string('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('system_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_pickup_orders');
    }
};
