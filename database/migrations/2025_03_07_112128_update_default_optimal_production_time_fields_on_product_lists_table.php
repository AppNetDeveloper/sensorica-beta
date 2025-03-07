<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDefaultOptimalProductionTimeFieldsOnProductListsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            // Se establece el valor por defecto de 1000 para cada uno de los campos
            $table->float('optimalproductionTime_sensorType_0')->default(1000)->change();
            $table->float('optimalproductionTime_sensorType_1')->default(1000)->change();
            $table->float('optimalproductionTime_sensorType_2')->default(1000)->change();
            $table->float('optimalproductionTime_sensorType_3')->default(1000)->change();
            $table->float('optimalproductionTime_sensorType_4')->default(1000)->change();
            $table->float('optimalproductionTime_rfid')->default(1000)->change();
            $table->float('optimalproductionTime_weight')->default(1000)->change();
            $table->float('optimal_production_time')->default(1000)->change();
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
            // En la reversión se quita el valor por defecto (se establece a null)
            $table->float('optimalproductionTime_sensorType_0')->default(null)->change();
            $table->float('optimalproductionTime_sensorType_1')->default(null)->change();
            $table->float('optimalproductionTime_sensorType_2')->default(null)->change();
            $table->float('optimalproductionTime_sensorType_3')->default(null)->change();
            $table->float('optimalproductionTime_sensorType_4')->default(null)->change();
            $table->float('optimalproductionTime_rfid')->default(null)->change();
            $table->float('optimalproductionTime_weight')->default(null)->change();
            $table->float('optimal_production_time')->default(null)->change();
        });
    }
}
