<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumberOfPalletsToOriginalOrderProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            // Add nullable integer column for number of pallets
            // Placed after 'units_box' to keep related numeric fields grouped
            $table->unsignedInteger('number_of_pallets')->nullable()->after('units_box');
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
            $table->dropColumn('number_of_pallets');
        });
    }
}
