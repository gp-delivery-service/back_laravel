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
        Schema::create('gp_map_district_geos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('gp_map_districts')->onDelete('cascade');
            $table->integer('order');
            $table->string('lat');
            $table->string('lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gp_map_district_geos');
    }
};
