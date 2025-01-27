<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameOperatorRfidToOperatorPost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Renombrar la tabla de operator_rfid a operator_post
        Schema::rename('operator_rfid', 'operator_post');

        // Modificar la estructura de la tabla
        Schema::table('operator_post', function (Blueprint $table) {
            $table->unsignedBigInteger('sensor_id')->nullable()->after('id'); // Nueva columna sensor_id
            $table->unsignedBigInteger('modbus_id')->nullable()->after('sensor_id'); // Nueva columna modbus_id
            $table->unsignedBigInteger('count')->default(0)->after('modbus_id'); // Nueva columna count (contador)

            // Agregar claves foráneas
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('set null');
            $table->foreign('modbus_id')->references('id')->on('modbuses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operator_post', function (Blueprint $table) {
            // Eliminar las claves foráneas y columnas nuevas
            $table->dropForeign(['sensor_id']);
            $table->dropForeign(['modbus_id']);
            $table->dropColumn(['sensor_id', 'modbus_id', 'count']);
        });

        // Renombrar la tabla de vuelta a operator_rfid
        Schema::rename('operator_post', 'operator_rfid');
    }
}
