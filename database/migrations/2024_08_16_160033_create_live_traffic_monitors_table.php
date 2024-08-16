<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveTrafficMonitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('live_traffic_monitors', function (Blueprint $table) {
            $table->id(); // Columna ID autoincremental
            $table->unsignedBigInteger('modbus_id'); // ID de la tabla modbuses
            $table->float('value'); // Campo para almacenar el valor
            $table->timestamps();

            // Definimos la clave foránea
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
        Schema::dropIfExists('live_traffic_monitors');
    }
}
