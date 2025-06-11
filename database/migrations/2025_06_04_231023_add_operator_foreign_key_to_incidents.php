<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperatorForeignKeyToIncidents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Primero, asegurarnos de que la columna created_by exista y sea del tipo correcto
        Schema::table('production_order_incidents', function (Blueprint $table) {
            // Asegurarse de que la columna exista y sea del tipo correcto
            $table->unsignedBigInteger('created_by')->nullable()->change();
            
            // Agregar la clave foránea
            $table->foreign('created_by')
                  ->references('id')
                  ->on('operators')
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
        Schema::table('production_order_incidents', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['created_by']);
        });
    }
}
