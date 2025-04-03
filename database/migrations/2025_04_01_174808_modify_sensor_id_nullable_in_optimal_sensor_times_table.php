<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySensorIdNullableInOptimalSensorTimesTable extends Migration
{
    public function up()
    {
        Schema::table('optimal_sensor_times', function (Blueprint $table) {
            // Si sensor_id ya existe y deseas modificarlo
            $table->unsignedBigInteger('sensor_id')->nullable()->default(null)->change();
            
            // Si aún no existe sensor_id, descomenta lo siguiente para agregarlo:
            // $table->unsignedBigInteger('sensor_id')->nullable()->default(null)->after('optimal_time');
            // $table->foreign('sensor_id')
            //       ->references('id')
            //       ->on('sensors')
            //       ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('optimal_sensor_times', function (Blueprint $table) {
            // Revertir la modificación: sensor_id no acepta nulos
            $table->unsignedBigInteger('sensor_id')->nullable(false)->default(null)->change();
            
            // Si agregaste la columna en este migration, podrías eliminarla en el down():
            // $table->dropForeign(['sensor_id']);
            // $table->dropColumn('sensor_id');
        });
    }
}
