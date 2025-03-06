<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierOrderIdToControlWeightsTable extends Migration
{
    public function up()
    {
        Schema::table('control_weights', function (Blueprint $table) {
            // Agregamos la columna supplier_order_id, opcionalmente puedes hacerla nullable
            $table->unsignedBigInteger('supplier_order_id')->nullable()->after('box_m3');
            // Definimos la clave foránea
            $table->foreign('supplier_order_id')
                  ->references('id')
                  ->on('supplier_orders')
                  ->onDelete('set null'); // O cascade según tu necesidad
        });
    }

    public function down()
    {
        Schema::table('control_weights', function (Blueprint $table) {
            $table->dropForeign(['supplier_order_id']);
            $table->dropColumn('supplier_order_id');
        });
    }
}
