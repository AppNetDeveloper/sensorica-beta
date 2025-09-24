<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Método más agresivo: recrear la tabla sin el constraint problemático
        
        // 1. Crear tabla temporal con la estructura correcta
        Schema::create('route_day_assignments_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_name_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fleet_vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('assignment_date');
            $table->tinyInteger('day_of_week');
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Solo el constraint correcto que permite múltiples vehículos
            $table->unique(['customer_id', 'route_name_id', 'fleet_vehicle_id', 'assignment_date', 'day_of_week'], 'unique_customer_route_vehicle_day');
        });
        
        // 2. Copiar datos existentes
        DB::statement('INSERT INTO route_day_assignments_temp SELECT * FROM route_day_assignments');
        
        // 3. Eliminar tabla original
        Schema::dropIfExists('route_day_assignments');
        
        // 4. Renombrar tabla temporal
        Schema::rename('route_day_assignments_temp', 'route_day_assignments');
        
        echo "Tabla route_day_assignments recreada exitosamente sin constraint problemático.\n";
    }

    public function down(): void
    {
        // El rollback sería complejo, mejor no hacerlo automáticamente
        echo "Rollback no implementado por seguridad. Restaurar desde backup si es necesario.\n";
    }
};
