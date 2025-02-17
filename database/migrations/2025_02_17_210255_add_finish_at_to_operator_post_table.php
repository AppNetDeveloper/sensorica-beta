<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinishAtToOperatorPostTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up()
    {
        Schema::table('operator_post', function (Blueprint $table) {
            // Agrega la columna finish_at (puedes usar finished_at si lo prefieres)
            $table->timestamp('finish_at')->nullable()->after('modbus_id');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down()
    {
        Schema::table('operator_post', function (Blueprint $table) {
            $table->dropColumn('finish_at');
        });
    }
}
