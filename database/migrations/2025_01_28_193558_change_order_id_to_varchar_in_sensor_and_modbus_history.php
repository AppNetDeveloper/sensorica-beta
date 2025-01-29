<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrderIdToVarcharInSensorAndModbusHistory extends Migration
{
    public function up()
    {
        // Cambiar orderId a varchar en sensor_history
        Schema::table('sensor_history', function (Blueprint $table) {
            $table->string('orderId', 255)->change(); // Cambia a VARCHAR con longitud máxima de 255
        });

        // Cambiar orderId a varchar en modbus_history
        Schema::table('modbus_history', function (Blueprint $table) {
            $table->string('orderId', 255)->change(); // Cambia a VARCHAR con longitud máxima de 255
        });
    }

    public function down()
    {
        // Revertir el cambio: devolver orderId a int en sensor_history
        Schema::table('sensor_history', function (Blueprint $table) {
            $table->integer('orderId')->change(); // Cambia de vuelta a INT
        });

        // Revertir el cambio: devolver orderId a int en modbus_history
        Schema::table('modbus_history', function (Blueprint $table) {
            $table->integer('orderId')->change(); // Cambia de vuelta a INT
        });
    }
}

