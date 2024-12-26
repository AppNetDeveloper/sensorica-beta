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
            $table->string('orderId')->nullable(); // Reemplaza 'some_existing_column' con la columna previa
            $table->integer('quantity')->nullable()->after('orderId');
            $table->integer('uds')->nullable()->after('quantity');
            $table->string('productName')->nullable()->after('uds')->default('123456');
            $table->string('count_week_0')->nullable()->after('productName')->default('0');
            $table->string('count_week_1')->nullable()->after('count_week_0')->default('0');
        });

        // Agregar campos a la tabla sensors
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('orderId')->nullable();
            $table->integer('quantity')->nullable()->after('orderId');
            $table->integer('uds')->nullable()->after('quantity');
            $table->string('productName')->nullable()->after('uds')->default('123456');
            $table->string('count_week_0')->nullable()->default('0');
            $table->string('count_week_1')->nullable()->default('0');
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
