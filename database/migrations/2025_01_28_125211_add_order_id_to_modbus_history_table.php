<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToModbusHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modbus_history', function (Blueprint $table) {
            $table->unsignedBigInteger('orderId')->nullable()->after('id')->comment('ID de la orden asociado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modbus_history', function (Blueprint $table) {
            $table->dropColumn('orderId');
        });
    }
}

