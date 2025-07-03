<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInStockToOriginalOrderTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add to original_order_articles
        Schema::table('original_order_articles', function (Blueprint $table) {
            $table->tinyInteger('in_stock')
                  ->nullable()
                  ->default(1)
                  ->comment('0 = Sin stock, 1 = Con stock, NULL = No especificado');
        });

        // Add to original_order_processes
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->tinyInteger('in_stock')
                  ->nullable()
                  ->default(1)
                  ->comment('0 = Sin stock, 1 = Con stock, NULL = No especificado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove from original_order_articles
        Schema::table('original_order_articles', function (Blueprint $table) {
            $table->dropColumn('in_stock');
        });

        // Remove from original_order_processes
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->dropColumn('in_stock');
        });
    }
}
