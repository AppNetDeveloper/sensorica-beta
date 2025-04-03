<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDecimalColumnsInSensorHistoryAndProductLists extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        // Actualización en sensor_history
        Schema::table('sensor_history', function (Blueprint $table) {
            $table->decimal('optimal_production_time', 8, 2)->change();
        });

        // Actualización en product_lists
        Schema::table('product_lists', function (Blueprint $table) {
            $table->decimal('optimal_production_time', 8, 2)->change();
            $table->decimal('optimalproductionTime_sensorType_3', 8, 2)->change();
            $table->decimal('box_kg', 8, 2)->change();
            $table->decimal('optimalproductionTime_sensorType_0', 8, 2)->change();
            $table->decimal('optimalproductionTime_sensorType_1', 8, 2)->change();
            $table->decimal('optimalproductionTime_sensorType_2', 8, 2)->change();
            $table->decimal('optimalproductionTime_sensorType_4', 8, 2)->change();
            $table->decimal('optimalproductionTime_rfid', 8, 2)->change();
            $table->decimal('optimalproductionTime_weight', 8, 2)->change();
            $table->decimal('optimalproductionTime_weight_1', 8, 2)->change();
            $table->decimal('optimalproductionTime_weight_2', 8, 2)->change();
            $table->decimal('optimalproductionTime_weight_3', 8, 2)->change();
            $table->decimal('optimalproductionTime_weight_4', 8, 2)->change();
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        // Para revertir, se vuelve a cambiar al tipo original (double en este caso)
        Schema::table('sensor_history', function (Blueprint $table) {
            $table->double('optimal_production_time', 8, 2)->change();
        });

        Schema::table('product_lists', function (Blueprint $table) {
            $table->double('optimal_production_time', 8, 2)->change();
            $table->double('optimalproductionTime_sensorType_3', 8, 2)->change();
            // Si 'box_kg' originalmente era decimal, no es necesario revertirlo.
            $table->double('optimalproductionTime_sensorType_0', 8, 2)->change();
            $table->double('optimalproductionTime_sensorType_1', 8, 2)->change();
            $table->double('optimalproductionTime_sensorType_2', 8, 2)->change();
            $table->double('optimalproductionTime_sensorType_4', 8, 2)->change();
            $table->double('optimalproductionTime_rfid', 8, 2)->change();
            $table->double('optimalproductionTime_weight', 8, 2)->change();
            $table->double('optimalproductionTime_weight_1', 8, 2)->change();
            $table->double('optimalproductionTime_weight_2', 8, 2)->change();
            $table->double('optimalproductionTime_weight_3', 8, 2)->change();
            $table->double('optimalproductionTime_weight_4', 8, 2)->change();
        });
    }
}
