<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id');
            $table->string('name');
            $table->string('token')->unique();
            $table->string('mqtt_topic_barcodes')->nullable();
            $table->string('mqtt_topic_orders')->nullable();
            $table->string('mqtt_topic_finish')->nullable();
            $table->string('mqtt_topic_pause')->nullable();
            $table->string('mqtt_topic_shift')->nullable();
            $table->string('machine_id')->nullable(); // Permite valores nulos
            $table->string('ope_id')->nullable();    // Permite valores nulos
            $table->json('order_notice')->nullable(); // Almacena datos JSON
            $table->string('last_barcode')->nullable();
            $table->string('ip_zerotier')->nullable();
            $table->string('user_ssh')->nullable();
            $table->string('port_ssh')->nullable();
            $table->string('user_ssh_password')->nullable();
            $table->string('ip_barcoder')->nullable();
            $table->string('port_barcoder')->nullable();
            $table->string('conexion_type')->nullable();
            $table->timestamps();

            $table->foreign('production_line_id')->references('id')->on('production_lines');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barcodes');
    }
}
