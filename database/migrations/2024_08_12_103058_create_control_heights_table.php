<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateControlHeightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('control_heights', function (Blueprint $table) {
            $table->id(); // ID autoincremental
            $table->unsignedBigInteger('modbus_id'); // Columna para la clave foránea modbus_id
            $table->string('height_value'); // Columna para almacenar el valor de altura
            $table->timestamps(); // Columnas created_at y updated_at automáticas

            // Definir la relación con la tabla modbuses
            $table->foreign('modbus_id')->references('id')->on('modbuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('control_heights');
    }
}
