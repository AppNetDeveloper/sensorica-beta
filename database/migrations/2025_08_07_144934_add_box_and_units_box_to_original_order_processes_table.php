<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBoxAndUnitsBoxToOriginalOrderProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->integer('box')->nullable()->after('time')->comment('Número de cajas');
            $table->integer('units_box')->nullable()->after('box')->comment('Unidades por caja');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->dropColumn(['box', 'units_box']);
        });
    }
}
