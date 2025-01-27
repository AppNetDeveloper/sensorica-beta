<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDownTimeToOrderStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            $table->integer('down_time')->default(0)->after('units_made_real')->comment('Total downtime in seconds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            $table->dropColumn('down_time');
        });
    }
}
