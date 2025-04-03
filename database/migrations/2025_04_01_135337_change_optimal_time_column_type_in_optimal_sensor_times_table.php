<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOptimalTimeColumnTypeInOptimalSensorTimesTable extends Migration
{
    public function up()
    {
        Schema::table('optimal_sensor_times', function (Blueprint $table) {
            // Cambiar el campo de entero a decimal (10,2)
            $table->decimal('optimal_time', 10, 2)->change();
        });
    }

    public function down()
    {
        Schema::table('optimal_sensor_times', function (Blueprint $table) {
            // Volver a entero (ten en cuenta que los decimales se perderán)
            $table->integer('optimal_time')->change();
        });
    }
}
