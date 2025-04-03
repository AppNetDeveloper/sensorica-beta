<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModbusIdToOptimalSensorTimesTable extends Migration
{
    public function up()
    {
        Schema::table('optimal_sensor_times', function (Blueprint $table) {
            // Agrega la columna modbus_id, en este ejemplo se define como unsignedBigInteger y se permite que sea nula
            $table->unsignedBigInteger('modbus_id')->nullable()->after('id');
            
            // Define la relación foránea con la tabla modbuses (se asume que su clave primaria es 'id')
            $table->foreign('modbus_id')->references('id')->on('modbuses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('optimal_sensor_times', function (Blueprint $table) {
            // Primero se elimina la restricción foránea y luego la columna en caso de rollback
            $table->dropForeign(['modbus_id']);
            $table->dropColumn('modbus_id');
        });
    }
}
