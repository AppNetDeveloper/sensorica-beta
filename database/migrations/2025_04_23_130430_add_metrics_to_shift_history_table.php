<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMetricsToShiftHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_history', function (Blueprint $table) {
            $table->unsignedInteger('on_time')->default(0)
                  ->comment('Tiempo en segundos en que la máquina estuvo en funcionamiento');
            $table->unsignedInteger('down_time')->default(0)
                  ->comment('Tiempo en segundos de parada no planificada');
            $table->unsignedInteger('production_stops_time')->default(0)
                  ->comment('Tiempo en segundos de paradas de producción');
            $table->unsignedInteger('slow_time')->default(0)
                  ->comment('Tiempo en segundos en modo lento');
            $table->unsignedInteger('theoretical_end_time')->default(0)
                  ->comment('Tiempo teórico de finalización en segundos');
            $table->unsignedInteger('real_end_time')->default(0)
                  ->comment('Tiempo real de finalización en segundos');
            $table->decimal('oee', 5, 2)->default(0.00)
                  ->comment('Overall Equipment Effectiveness, con dos decimales');
            $table->unsignedInteger('prepair_time')->default(0)
                  ->comment('Tiempo de preparación en segundos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_history', function (Blueprint $table) {
            $table->dropColumn([
                'on_time',
                'down_time',
                'production_stops_time',
                'slow_time',
                'theoretical_end_time',
                'real_end_time',
                'oee',
                'prepair_time',
            ]);
        });
    }
}
