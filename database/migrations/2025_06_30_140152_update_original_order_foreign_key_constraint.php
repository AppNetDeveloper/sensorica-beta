<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOriginalOrderForeignKeyConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Eliminar la restricción de clave foránea existente
            $table->dropForeign(['original_order_id']);
            
            // Volver a crear la restricción con ON DELETE CASCADE
            $table->foreign('original_order_id')
                  ->references('id')
                  ->on('original_orders')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Eliminar la restricción con CASCADE
            $table->dropForeign(['original_order_id']);
            
            // Volver a crear la restricción sin ON DELETE CASCADE (comportamiento original)
            $table->foreign('original_order_id')
                  ->references('id')
                  ->on('original_orders');
        });
    }
}
