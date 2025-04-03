<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerIdToProductionOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Agregar el campo customerId como string (texto), permitiendo valores nulos.
            $table->string('customerId')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Eliminar el campo customerId en caso de rollback.
            $table->dropColumn('customerId');
        });
    }
}

