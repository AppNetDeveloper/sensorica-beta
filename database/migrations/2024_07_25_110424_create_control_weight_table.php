<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateControlWeightTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('control_weights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modbus_id');
            $table->float('last_control_weight')->nullable();
            $table->float('last_dimension')->nullable();
            $table->integer('last_box_number')->nullable();
            $table->string('last_barcoder')->nullable();
            $table->string('last_final_barcoder')->nullable();
            $table->timestamps();

            // Clave foránea
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
        Schema::dropIfExists('control_weight');
    }
}
