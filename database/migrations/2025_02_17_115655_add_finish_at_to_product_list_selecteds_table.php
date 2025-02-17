<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinishAtToProductListSelectedsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_list_selecteds', function (Blueprint $table) {
            $table->timestamp('finish_at')->nullable()->after('updated_at')->comment('Indica la fecha y hora de finalización');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_list_selecteds', function (Blueprint $table) {
            $table->dropColumn('finish_at');
        });
    }
}
