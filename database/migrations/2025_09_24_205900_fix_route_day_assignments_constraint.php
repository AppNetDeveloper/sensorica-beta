<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        try {
            // Eliminar el constraint problemático usando SQL directo
            DB::statement('ALTER TABLE route_day_assignments DROP INDEX unique_route_day_assignment');
            echo "Constraint unique_route_day_assignment eliminado exitosamente.\n";
        } catch (\Exception $e) {
            // Si el constraint no existe, no es un error
            echo "Constraint unique_route_day_assignment no existe o ya fue eliminado: " . $e->getMessage() . "\n";
        }
    }

    public function down(): void
    {
        // En el rollback, recrear el constraint original (puede fallar si hay múltiples vehículos)
        try {
            Schema::table('route_day_assignments', function (Blueprint $table) {
                $table->unique(['route_name_id', 'assignment_date', 'day_of_week'], 'unique_route_day_assignment');
            });
        } catch (\Exception $e) {
            echo "No se pudo recrear el constraint original: " . $e->getMessage() . "\n";
        }
    }
};
