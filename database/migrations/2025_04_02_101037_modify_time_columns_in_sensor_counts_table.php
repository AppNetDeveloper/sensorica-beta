<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTimeColumnsInSensorCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sensor_counts', function (Blueprint $table) {
            $table->decimal('time_00', 8, 2)->nullable()->change();
            $table->decimal('time_01', 8, 2)->nullable()->change();
            $table->decimal('time_11', 8, 2)->nullable()->change();
            $table->decimal('time_10', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sensor_counts', function (Blueprint $table) {
            $table->bigInteger('time_00')->nullable()->change();
            $table->bigInteger('time_01')->nullable()->change();
            $table->bigInteger('time_11')->nullable()->change();
            $table->bigInteger('time_10')->nullable()->change();
        });
    }
}
