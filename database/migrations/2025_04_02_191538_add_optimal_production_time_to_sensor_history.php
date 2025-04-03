<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptimalProductionTimeToSensorHistory extends Migration
{
    public function up()
    {
        Schema::table('sensor_history', function (Blueprint $table) {
            $table->integer('optimal_production_time')->nullable(); // Puedes ajustar el tipo y restricciones
        });
    }

    public function down()
    {
        Schema::table('sensor_history', function (Blueprint $table) {
            $table->dropColumn('optimal_production_time');
        });
    }
}

