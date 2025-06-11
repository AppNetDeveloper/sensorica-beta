<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalProductionLineIdToProductionOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('original_production_line_id')
                  ->nullable()
                  ->after('production_line_id');
                  
            $table->foreign('original_production_line_id')
                  ->references('id')
                  ->on('production_lines')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['original_production_line_id']);
            $table->dropColumn('original_production_line_id');
        });
    }
}
