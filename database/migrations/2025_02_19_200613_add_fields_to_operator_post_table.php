<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOperatorPostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operator_post', function (Blueprint $table) {
            // Agregar campo product_list_selected_id que referencia a product_list_selecteds
            $table->unsignedBigInteger('product_list_selected_id')->nullable()->after('id');
            $table->foreign('product_list_selected_id')
                  ->references('id')
                  ->on('product_list_selecteds')
                  ->onDelete('set null');

            // Agregar campo product_list_id que referencia a product_lists
            $table->unsignedBigInteger('product_list_id')->nullable()->after('product_list_selected_id');
            $table->foreign('product_list_id')
                  ->references('id')
                  ->on('product_lists')
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
        Schema::table('operator_post', function (Blueprint $table) {
            // Eliminar las restricciones de llave foránea y luego las columnas
            $table->dropForeign(['product_list_selected_id']);
            $table->dropColumn('product_list_selected_id');

            $table->dropForeign(['product_list_id']);
            $table->dropColumn('product_list_id');
        });
    }
}
