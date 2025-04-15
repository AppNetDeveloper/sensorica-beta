<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShiftIdToShiftHistoryTable extends Migration
{
    /**
     * Ejecutar las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_history', function (Blueprint $table) {
            // Se crea la columna shift_list_id permitiendo valores nulos.
            $table->unsignedBigInteger('shift_list_id')->nullable()->after('id');

            // Se define la clave foránea que hace referencia a la columna "id" de la tabla "shift_lists".
            $table->foreign('shift_list_id')
                  ->references('id')
                  ->on('shift_lists')
                  ->onDelete('cascade');
        });
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_history', function (Blueprint $table) {
            $table->dropForeign(['shift_list_id']);
            $table->dropColumn('shift_list_id');
        });
    }
}
