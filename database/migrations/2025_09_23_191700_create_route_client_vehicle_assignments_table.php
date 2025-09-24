<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_client_vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_name_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fleet_vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('assignment_date');
            $table->tinyInteger('day_of_week'); // 0=Monday, 1=Tuesday, ..., 6=Sunday
            $table->integer('sort_order')->default(0); // Orden de carga/entrega
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['customer_id', 'assignment_date']);
            $table->index(['route_name_id', 'assignment_date']);
            $table->index(['fleet_vehicle_id', 'assignment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_client_vehicle_assignments');
    }
};
