<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProducedUnitsToOperatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->unsignedInteger('produced_units_turn')->default(0)->after('phone')->comment('Unidades producidas en el turno actual')->nullable();
            $table->unsignedInteger('produced_units_order')->default(0)->after('produced_units_turn')->comment('Unidades producidas en la orden actual')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn(['produced_units_turn', 'produced_units_order']);
        });
    }
}
