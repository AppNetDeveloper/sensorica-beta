<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOriginalOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('original_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique()->comment('ID de la orden del sistema externo');
            $table->string('client_number')->comment('Número de cliente asociado');
            $table->json('order_details')->comment('Detalles completos de la orden en formato JSON');
            $table->boolean('processed')->default(false)->comment('Indica si la orden ha sido procesada');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('original_orders');
    }
}
