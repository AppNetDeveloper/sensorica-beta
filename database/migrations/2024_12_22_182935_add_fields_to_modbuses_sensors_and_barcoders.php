<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToModbusesSensorsAndBarcoders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Agregar campos a la tabla modbuses
        Schema::table('modbuses', function (Blueprint $table) {
            $table->string('orderId')->nullable()->after('name'); // Reemplaza 'some_existing_column' con la columna previa
            $table->integer('quantity')->nullable()->after('orderId');
            $table->integer('uds')->nullable()->after('quantity');
            $table->string('productName')->nullable()->after('uds');
        });

        // Agregar campos a la tabla sensors
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('orderId')->nullable()->after('name');
            $table->integer('quantity')->nullable()->after('orderId');
            $table->integer('uds')->nullable()->after('quantity');
            $table->string('productName')->nullable()->after('uds');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar campos de la tabla modbuses
        Schema::table('modbuses', function (Blueprint $table) {
            $table->dropColumn(['orderId', 'quantity', 'uds']);
        });

        // Eliminar campos de la tabla sensors
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn(['orderId', 'quantity', 'uds']);
        });
    }
}