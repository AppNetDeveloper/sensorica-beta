<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scada', function (Blueprint $table) {
            // Agregar la columna barcoder_id como clave foránea
            $table->foreignId('barcoder_id')
                  ->nullable() // Permite que sea opcional
                  ->constrained('barcodes') // Define la relación con la tabla barcodes
                  ->onDelete('cascade'); // Opcional: comportamiento en cascada al eliminar

            // Agregar la columna mixer_m3
            $table->decimal('mixer_m3', 8, 2)->nullable(); // Permite valores decimales y puede ser nulo

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scada', function (Blueprint $table) {
            // Eliminar la clave foránea y la columna barcoder_id
            $table->dropForeign(['barcoder_id']);
            $table->dropColumn('barcoder_id');

            // Eliminar la columna mixer_m3
            $table->dropColumn('mixer_m3');
            $table->dropColumn('orden');
        });
    }
};
