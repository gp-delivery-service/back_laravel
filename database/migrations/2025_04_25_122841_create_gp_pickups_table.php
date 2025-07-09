<?php

use App\Constants\GpPickupStatus;
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
        Schema::create('gp_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('driver_id')->nullable()->constrained('gp_drivers')->onDelete('set null');
            $table->foreignUuid('company_id')->nullable()->constrained('gp_companies')->onDelete('set null');
            $table->string('status')->default(GpPickupStatus::PREPARING->value);
            $table->string('note')->nullable();
            $table->string('system_note')->nullable();
            $table->integer('preparing_time')->nullable();
            $table->integer('closed_time')->nullable();
            $table->boolean('archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_pickups');
    }
};
