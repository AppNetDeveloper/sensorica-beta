<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalOrderIdToProductionOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Añadir la columna que permite valores nulos
            $table->unsignedBigInteger('original_order_id')->nullable()->after('customerId');
            
            // Crear la clave foránea sin ON DELETE SET NULL
            $table->foreign('original_order_id')
                  ->references('id')
                  ->on('original_orders');
                  // Sin onDelete para que falle si se intenta eliminar un original_order referenciado
        });
    }

    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Eliminar la restricción de clave foránea primero
            $table->dropForeign(['original_order_id']);
            // Luego eliminar la columna
            $table->dropColumn('original_order_id');
        });
    }
}
