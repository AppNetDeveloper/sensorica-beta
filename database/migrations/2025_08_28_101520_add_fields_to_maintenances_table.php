<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            // Tiempo acumulado de mantenimiento en segundos
            $table->unsignedBigInteger('accumulated_maintenance_seconds')->default(0)->after('end_datetime');
            // Indica si el mantenimiento para la línea de producción (1) o no (0)
            $table->boolean('production_line_stop')->default(false)->after('accumulated_maintenance_seconds');
            // Índices útiles para consultas/filtrado
            $table->index(['production_line_stop']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'production_line_stop')) {
                $table->dropIndex(['production_line_stop']);
                $table->dropColumn('production_line_stop');
            }
            if (Schema::hasColumn('maintenances', 'accumulated_maintenance_seconds')) {
                $table->dropColumn('accumulated_maintenance_seconds');
            }
        });
    }
};
