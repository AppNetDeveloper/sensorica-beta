<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptimalproductionTimeWeightColumnsToProductListsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            $table->float('optimalproductionTime_weight_1')->default(1000)->after('optimalproductionTime_weight');
            $table->float('optimalproductionTime_weight_2')->default(1000)->after('optimalproductionTime_weight_1');
            $table->float('optimalproductionTime_weight_3')->default(1000)->after('optimalproductionTime_weight_2');
            $table->float('optimalproductionTime_weight_4')->default(1000)->after('optimalproductionTime_weight_3');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            $table->dropColumn([
                'optimalproductionTime_weight_1',
                'optimalproductionTime_weight_2',
                'optimalproductionTime_weight_3',
                'optimalproductionTime_weight_4',
            ]);
        });
    }
}
