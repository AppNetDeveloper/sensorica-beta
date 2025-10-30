<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorAndPosicionKanbanToProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('factor_correccion'); // Color hexadecimal ej: #FF5733
            $table->integer('posicion_kanban')->nullable()->after('color'); // Posición en el kanban
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn(['color', 'posicion_kanban']);
        });
    }
}
