<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInStockToOriginalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->boolean('in_stock')->default(false)->after('delivery_date')->comment('Indica si el material está en stock');
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
            $table->dropColumn('in_stock');
        });
    }
}
