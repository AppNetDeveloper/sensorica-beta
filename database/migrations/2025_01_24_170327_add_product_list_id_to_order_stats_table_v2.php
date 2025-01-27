<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductListIdToOrderStatsTableV2 extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('product_list_id')->nullable()->after('id'); // Añadimos el campo después del ID
            $table->foreign('product_list_id')
                  ->references('id')
                  ->on('product_lists')
                  ->onDelete('cascade'); // Opcional: ajusta el comportamiento en caso de eliminación
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
            $table->dropForeign(['product_list_id']);
            $table->dropColumn('product_list_id');
        });
    }
}
