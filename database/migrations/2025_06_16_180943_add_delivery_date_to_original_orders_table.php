<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryDateToOriginalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dateTime('delivery_date')->nullable()->comment('The expected delivery date of the order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
}
