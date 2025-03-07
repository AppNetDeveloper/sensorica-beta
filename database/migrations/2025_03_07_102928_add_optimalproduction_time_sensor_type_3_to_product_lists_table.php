<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptimalproductionTimeSensorType3ToProductListsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            // Se agrega la columna como flotante, puede ser nula.
            $table->float('optimalproductionTime_sensorType_3')->nullable()->after('optimal_production_time');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            $table->dropColumn('optimalproductionTime_sensorType_3');
        });
    }
}
