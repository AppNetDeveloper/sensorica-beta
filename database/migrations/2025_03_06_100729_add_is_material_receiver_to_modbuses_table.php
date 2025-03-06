<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsMaterialReceiverToModbusesTable extends Migration
{
    public function up()
    {
        Schema::table('modbuses', function (Blueprint $table) {
            $table->boolean('is_material_receiver')->default(false);
        });
    }

    public function down()
    {
        Schema::table('modbuses', function (Blueprint $table) {
            $table->dropColumn('is_material_receiver');
        });
    }
}
