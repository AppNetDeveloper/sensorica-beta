<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Por ahora, solo comentamos el constraint problemático y usamos el código para manejar duplicados
        // El constraint original impide múltiples vehículos, así que lo manejamos en el código
        
        // Verificar si ya existe el nuevo constraint
        $indexExists = DB::select("SHOW INDEX FROM route_day_assignments WHERE Key_name = 'unique_customer_route_vehicle_day'");
        
        if (empty($indexExists)) {
            Schema::table('route_day_assignments', function (Blueprint $table) {
                // Crear nuevo constraint que permite múltiples vehículos
                $table->unique(['customer_id', 'route_name_id', 'fleet_vehicle_id', 'assignment_date', 'day_of_week'], 'unique_customer_route_vehicle_day');
            });
        }
    }

    public function down(): void
    {
        Schema::table('route_day_assignments', function (Blueprint $table) {
            // Revertir: eliminar el nuevo constraint
            $table->dropUnique('unique_customer_route_vehicle_day');
            
            // Restaurar el constraint original (esto podría fallar si hay múltiples vehículos)
            $table->unique(['route_name_id', 'assignment_date', 'day_of_week'], 'unique_route_day_assignment');
        });
    }
};
