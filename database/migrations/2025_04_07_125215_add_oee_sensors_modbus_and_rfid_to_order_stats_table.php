<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOeeSensorsModbusAndRfidToOrderStatsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            // Se añade el campo 'oee_sensors' justo después de 'oee'
            $table->string('oee_sensors')->nullable()->after('oee');
            // Se añade el campo 'oee_modbus' justo después de 'oee_sensors'
            $table->string('oee_modbus')->nullable()->after('oee_sensors');
            // Se añade el campo 'oee_rfid' justo después de 'oee_modbus'
            $table->string('oee_rfid')->nullable()->after('oee_modbus');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            $table->dropColumn(['oee_sensors', 'oee_modbus', 'oee_rfid']);
        });
    }
}
