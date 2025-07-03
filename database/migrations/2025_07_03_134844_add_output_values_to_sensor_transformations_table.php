<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOutputValuesToSensorTransformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sensor_transformations', function (Blueprint $table) {
            $table->string('below_min_value_output')->nullable()->comment('Valor a enviar cuando el valor es menor o igual al mínimo');
            $table->string('min_to_mid_value_output')->nullable()->comment('Valor a enviar cuando el valor está entre mínimo y medio');
            $table->string('mid_to_max_value_output')->nullable()->comment('Valor a enviar cuando el valor está entre medio y máximo');
            $table->string('above_max_value_output')->nullable()->comment('Valor a enviar cuando el valor es mayor o igual al máximo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sensor_transformations', function (Blueprint $table) {
            $table->dropColumn([
                'below_min_value_output',
                'min_to_mid_value_output',
                'mid_to_max_value_output',
                'above_max_value_output'
            ]);
        });
    }
}
