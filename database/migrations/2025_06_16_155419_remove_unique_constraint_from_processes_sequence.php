<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraintFromProcessesSequence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            // Eliminar el índice único de la columna sequence
            $table->dropUnique('processes_sequence_unique');
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
            // Volver a agregar la restricción de unicidad si es necesario hacer rollback
            $table->unique('sequence', 'processes_sequence_unique');
        });
    }
}
