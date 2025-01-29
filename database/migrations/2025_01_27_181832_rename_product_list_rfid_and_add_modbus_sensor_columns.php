<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameProductListRfidAndAddModbusSensorColumns extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Renombrar la tabla de product_list_rfid a product_list_selecteds
        Schema::rename('product_list_rfid', 'product_list_selecteds');

        // 2. Agregar columnas modbus_id y sensor_id (ambas nullable)
        Schema::table('product_list_selecteds', function (Blueprint $table) {
            $table->unsignedBigInteger('modbus_id')->nullable()->after('rfid_reading_id');
            $table->unsignedBigInteger('sensor_id')->nullable()->after('modbus_id');

            // Opcional: Agregar claves foráneas
            $table->foreign('modbus_id')
                  ->references('id')
                  ->on('modbuses')
                  ->onDelete('set null');

            $table->foreign('sensor_id')
                  ->references('id')
                  ->on('sensors')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // 1. Quitar las FK y columnas agregadas
        Schema::table('product_list_selecteds', function (Blueprint $table) {
            // Primero se dropean las FK
            $table->dropForeign(['modbus_id']);
            $table->dropForeign(['sensor_id']);
            // Luego se dropean las columnas
            $table->dropColumn(['modbus_id', 'sensor_id']);
        });

        // 2. Renombrar la tabla de vuelta a product_list_rfid
        Schema::rename('product_list_selecteds', 'product_list_rfid');
    }
}
