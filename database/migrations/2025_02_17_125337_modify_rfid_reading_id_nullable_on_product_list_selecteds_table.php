<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyRfidReadingIdNullableOnProductListSelectedsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_list_selecteds', function (Blueprint $table) {
            $table->unsignedBigInteger('rfid_reading_id')->nullable()->change();
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
            $table->unsignedBigInteger('rfid_reading_id')->nullable(false)->change();
        });
    }
}
