<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id(); // ID autoincremental
            $table->unsignedBigInteger('production_line_id'); // Clave foránea a production_lines
            $table->unsignedBigInteger('barcoder_id'); // Clave foránea a barcodes
            $table->unsignedBigInteger('order_id'); // ID de la orden
            $table->json('json'); // Campo JSON para guardar datos adicionales de la orden
            $table->string('status'); // Estado de la orden 0 es pendiente, 1 es en proceso, 2 es terminado,  3 es pausa, 4 es cancelado, 5 con incidencias
            $table->integer('box'); // Cantidad de cajas
            $table->integer('units_box'); // Unidades por caja
            $table->integer('units'); // Total de unidades
            $table->integer('orden'); // el orden de fabricacion
            $table->timestamps(); // Campos created_at y updated_at

            // Relaciones
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');
            $table->foreign('barcode_id')->references('id')->on('barcodes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_orders');
    }
}
