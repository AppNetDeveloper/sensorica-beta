<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRssiMinToRfidAntsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rfid_ants', function (Blueprint $table) {
            $table->integer('rssi_min')->default(75)->after('id'); // Ajusta 'after' según convenga
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rfid_ants', function (Blueprint $table) {
            $table->dropColumn('rssi_min');
        });
    }
}
