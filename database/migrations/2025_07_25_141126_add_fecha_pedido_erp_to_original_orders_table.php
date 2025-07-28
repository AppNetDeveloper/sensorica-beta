<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFechaPedidoErpToOriginalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dateTime('fecha_pedido_erp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dropColumn('fecha_pedido_erp');
        });
    }
}
