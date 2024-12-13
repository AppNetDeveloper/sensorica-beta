<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperatorIdToScadaOrderListProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scada_order_list_process', function (Blueprint $table) {
            $table->foreignId('operator_id') // Añade la columna operator_id
                  ->nullable() // Permite que sea opcional
                  ->constrained('operators') // Configura la clave foránea
                  ->onDelete('cascade'); // Elimina en cascada
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scada_order_list_process', function (Blueprint $table) {
            $table->dropForeign(['operator_id']); // Elimina la clave foránea
            $table->dropColumn('operator_id'); // Elimina la columna
        });
    }

}
