<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('vehicle_type', 50)->nullable(); // furgoneta, camión, etc
            $table->string('plate', 50); // matrícula
            $table->float('weight_kg')->nullable();
            $table->float('length_cm')->nullable();
            $table->float('width_cm')->nullable();
            $table->float('height_cm')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['customer_id', 'plate']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicles');
    }
};
