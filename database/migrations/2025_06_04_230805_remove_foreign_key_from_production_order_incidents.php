<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveForeignKeyFromProductionOrderIncidents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_order_incidents', function (Blueprint $table) {
            // Eliminar la restricción de clave foránea
            $table->dropForeign(['created_by']);
            
            // Cambiar la columna a nullable para evitar problemas
            $table->unsignedBigInteger('created_by')->nullable()->change();
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
            // Revertir los cambios si es necesario
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
