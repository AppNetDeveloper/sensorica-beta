<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryDateToProductionOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Añadimos la nueva columna 'delivery_date'
            // Usamos el tipo 'date' para almacenar solo la fecha (YYYY-MM-DD)
            $table->date('delivery_date')
                  ->nullable() // ¡Importante! Para que no falle en las filas que ya existen
                  ->after('id'); // Opcional: para colocarla después de otra columna, como el 'id'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
}
