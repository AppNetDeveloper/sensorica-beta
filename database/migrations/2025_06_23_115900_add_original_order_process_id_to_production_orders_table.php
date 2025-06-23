<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalOrderProcessIdToProductionOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('original_order_process_id')->nullable()->after('original_order_id');
            
            $table->foreign('original_order_process_id')
                  ->references('id')
                  ->on('original_order_processes')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['original_order_process_id']);
            $table->dropColumn('original_order_process_id');
        });
    }
}
