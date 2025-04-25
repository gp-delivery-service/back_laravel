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
        Schema::create('gp_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('client_phone')->nullable();
            $table->double('sum');
            $table->double('delivery_price');
            $table->foreignId('district_id')->nullable()->constrained('gp_map_districts')->onDelete('set null');
            $table->foreignId('street_id')->nullable()->constrained('gp_map_streets')->onDelete('set null');
            $table->foreignId('second_street_id')->nullable()->constrained('gp_map_streets')->onDelete('set null');
            $table->string('geo_comment')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->foreignUuid('company_id')->constrained('gp_companies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_orders');
    }
};
