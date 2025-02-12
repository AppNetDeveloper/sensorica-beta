<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveToShiftListsTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_lists', function (Blueprint $table) {
            // Se agrega la columna 'active' de tipo booleano, nullable, con valor por defecto null.
            $table->boolean('active')->nullable()->default(null);
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_lists', function (Blueprint $table) {
            // Se elimina la columna 'active'
            $table->dropColumn('active');
        });
    }
}
