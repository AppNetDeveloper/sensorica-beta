<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderMacsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_macs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barcoder_id'); // Clave foránea a barcodes
            $table->unsignedBigInteger('production_line_id'); // Clave foránea a production_lines
            $table->json('json'); // JSON para guardar el contenido recibido
            $table->timestamps();

            $table->foreign('barcoder_id')->references('id')->on('barcodes')->onDelete('cascade');
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_macs');
    }
}
