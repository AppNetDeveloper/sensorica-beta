<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_order_id'); // Identificador del pedido del proveedor
            $table->string('order_line');         // Línea del pedido (e.g. "231167.1")
            $table->integer('quantity');          // Cantidad solicitada
            $table->string('unit');               // Unidad (e.g. "Palet")
            $table->string('barcode');            // Código de barras o RFID
            $table->string('refer_id');           // Clave foránea a supplier_order_references
            $table->foreign('refer_id')->references('id')->on('supplier_order_references')->onDelete('cascade');
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('supplier_orders');
    }
}
