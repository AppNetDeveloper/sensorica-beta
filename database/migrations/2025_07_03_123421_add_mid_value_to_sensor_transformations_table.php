<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMidValueToSensorTransformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sensor_transformations', function (Blueprint $table) {
            $table->float('mid_value')->nullable()->after('min_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sensor_transformations', function (Blueprint $table) {
            $table->dropColumn('mid_value');
        });
    }
}
