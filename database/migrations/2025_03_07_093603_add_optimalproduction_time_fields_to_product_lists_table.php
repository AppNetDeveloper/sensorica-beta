<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptimalproductionTimeFieldsToProductListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            $table->decimal('optimalproductionTime_sensorType_0', 8, 2)->nullable();
            $table->decimal('optimalproductionTime_sensorType_1', 8, 2)->nullable();
            $table->decimal('optimalproductionTime_sensorType_2', 8, 2)->nullable();
            $table->decimal('optimalproductionTime_sensorType_4', 8, 2)->nullable();
            $table->decimal('optimalproductionTime_rfid', 8, 2)->nullable();
            $table->decimal('optimalproductionTime_weight', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_lists', function (Blueprint $table) {
            $table->dropColumn([
                'optimalproductionTime_sensorType_0',
                'optimalproductionTime_sensorType_1',
                'optimalproductionTime_sensorType_2',
                'optimalproductionTime_sensorType_4',
                'optimalproductionTime_rfid',
                'optimalproductionTime_weight',
            ]);
        });
    }
}
