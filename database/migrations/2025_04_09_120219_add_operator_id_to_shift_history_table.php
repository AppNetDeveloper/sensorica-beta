<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperatorIdToShiftHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_history', function (Blueprint $table) {
            // Agregar el campo operator_id como unsignedBigInteger y que permite nulos si es opcional
            $table->unsignedBigInteger('operator_id')->nullable()->after('id');

            // Establecer la clave foránea referenciando el id en la tabla operators
            $table->foreign('operator_id')
                  ->references('id')
                  ->on('operators')
                  ->onDelete('set null'); // O puedes usar cascade, según tus necesidades
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_history', function (Blueprint $table) {
            // Para eliminar la clave foránea primero
            $table->dropForeign(['operator_id']);
            // Luego eliminar la columna
            $table->dropColumn('operator_id');
        });
    }
}
