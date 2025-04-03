<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrepairTimeToOrderStatsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            // Se añade la columna prepair_time de tipo integer con un comentario
            $table->integer('prepair_time')->comment('Tiempo en segundos');
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
            // Se elimina la columna prepair_time
            $table->dropColumn('prepair_time');
        });
    }
}
