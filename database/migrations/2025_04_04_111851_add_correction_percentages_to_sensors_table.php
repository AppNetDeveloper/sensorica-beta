<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCorrectionPercentagesToSensorsTable extends Migration
{
    public function up()
    {
        Schema::table('sensors', function (Blueprint $table) {
            // Agrega la columna para el porcentaje mínimo de corrección
            $table->decimal('min_correction_percentage', 5, 2)
                  ->default(20.00)
                  ->comment('Minimum correction percentage threshold (e.g., 20%)');

            // Agrega la columna para el porcentaje máximo de corrección
            $table->decimal('max_correction_percentage', 5, 2)
                  ->default(98.00)
                  ->comment('Maximum correction factor percentage (e.g., 98% to represent current time minus 2%)');
        });
    }

    public function down()
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn('min_correction_percentage');
            $table->dropColumn('max_correction_percentage');
        });
    }
}
