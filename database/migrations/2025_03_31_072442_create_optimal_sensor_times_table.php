<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOptimalSensorTimesTable extends Migration
{
    public function up()
    {
        Schema::create('optimal_sensor_times', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sensor_id');
            $table->integer('sensor_type');
            $table->string('model_product');
            $table->unsignedBigInteger('product_list_id')->nullable();
            $table->unsignedBigInteger('production_line_id')->nullable();

            $table->integer('optimal_time');
            $table->string('tipo_analisis', 10); // 'time_11' o 'time_00'

            $table->integer('muestras_validas');
            $table->integer('repeticiones');

            $table->timestamps();

            $table->unique(['sensor_id', 'model_product']);

            // Claves foráneas
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');
            $table->foreign('product_list_id')->references('id')->on('product_lists')->onDelete('set null');
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('optimal_sensor_times');
    }
}
