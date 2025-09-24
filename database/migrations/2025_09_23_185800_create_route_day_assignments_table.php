<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('route_day_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_name_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fleet_vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('assignment_date');
            $table->tinyInteger('day_of_week'); // 0=Monday, 1=Tuesday, ..., 6=Sunday
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Evitar duplicados: una ruta-día-fecha solo puede tener un vehículo asignado
            $table->unique(['route_name_id', 'assignment_date', 'day_of_week'], 'unique_route_day_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_day_assignments');
    }
};
