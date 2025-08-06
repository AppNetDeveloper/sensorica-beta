<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstimatedDatetimeFieldsToProductionOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dateTime('estimated_start_datetime')->nullable()->comment('Fecha y hora estimada de inicio de la orden');
            $table->dateTime('estimated_end_datetime')->nullable()->comment('Fecha y hora estimada de finalización de la orden');
            $table->index('estimated_start_datetime');
            $table->index('estimated_end_datetime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn(['estimated_start_datetime', 'estimated_end_datetime']);
        });
    }
}
