<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCorrectionPercentagesToModbusesTable extends Migration
{
    public function up()
    {
        Schema::table('modbuses', function (Blueprint $table) {
            // Columna para el porcentaje mínimo de corrección, por ejemplo, un umbral del 20%
            $table->decimal('min_correction_percentage', 5, 2)
                  ->default(20.00)
                  ->comment('Minimum correction percentage threshold (e.g., 20%)');

            // Columna para el porcentaje máximo de corrección,
            // que se utilizará para ajustar el tiempo óptimo en base al tiempo actual (por ejemplo, tiempo actual - 2%)
            $table->decimal('max_correction_percentage', 5, 2)
                  ->default(98.00)
                  ->comment('Maximum correction factor percentage (e.g., 98% to represent current time minus 2%)');
        });
    }

    public function down()
    {
        Schema::table('modbuses', function (Blueprint $table) {
            $table->dropColumn('min_correction_percentage');
            $table->dropColumn('max_correction_percentage');
        });
    }
}
