<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionOrdersTopflowApiTable extends Migration
{
    public function up()
    {
        Schema::create('production_orders_topflow_api', function (Blueprint $table) {
            // _id: Identificador único del JSON, usaremos este como primary key.
            $table->string('_id')->primary();
            
            // client_id: Es el campo que reemplaza al antiguo "id" (ej. "LineaPedido.X")
            $table->string('client_id');
            
            // customerOrderId: Número de Pedido del Cliente (opcional)
            $table->string('customerOrderId')->nullable();
            
            // clientId: Referencia del Pedido del Cliente (si se mantiene)
            $table->string('clientId')->nullable();
            
            // code: Código de Barras / RFID
            $table->string('code');
            
            // deliveryDate: Fecha de Expedición
            $table->date('deliveryDate');
            
            // referId: Referencia o Artículo
            $table->string('referId');
            
            // quantity: Cantidad
            $table->integer('quantity');
            
            // paletsQtty: Número de palets
            $table->integer('paletsQtty');
            
            // Timestamps: created_at y updated_at
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_orders_topflow_api');
    }
}
