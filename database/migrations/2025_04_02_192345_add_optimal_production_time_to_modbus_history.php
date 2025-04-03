<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptimalProductionTimeToModbusHistory extends Migration
{
    public function up()
    {
        Schema::table('modbus_history', function (Blueprint $table) {
            $table->integer('optimal_production_time')->nullable(); // Ajusta el tipo o las restricciones según lo necesites
        });
    }

    public function down()
    {
        Schema::table('modbus_history', function (Blueprint $table) {
            $table->dropColumn('optimal_production_time');
        });
    }
}

