<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnTimeToOrderStatsTable extends Migration
{
    public function up()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            // Agregar la columna on_time como integer (int(11)) con valor por defecto 0.
            // En Laravel, al usar integer() se crea por defecto un int(11) en MySQL.
            $table->integer('on_time')->default(0)->after('id');
        });
    }

    public function down()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            $table->dropColumn('on_time');
        });
    }
}
