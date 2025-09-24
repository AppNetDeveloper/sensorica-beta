<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('driver')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['customer_id', 'date']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('fleet_vehicles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_plans');
    }
};
