<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOrderMacsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_macs', function (Blueprint $table) {
            $table->unsignedTinyInteger('action')->default(1); // Campo action
            $table->string('orderId')->nullable();            // Campo orderId
            $table->integer('quantity')->default(0);          // Campo quantity
            $table->string('machineId')->nullable();          // Campo machineId
            $table->string('opeId')->nullable();              // Campo opeId
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_macs', function (Blueprint $table) {
            $table->dropColumn(['action', 'orderId', 'quantity', 'machineId', 'opeId']);
        });
    }
}
