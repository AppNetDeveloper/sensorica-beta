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
        Schema::create('scada_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scada_id')->constrained('scada')->onDelete('cascade');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('barcoder_id')->constrained('barcodes')->onDelete('cascade');
            $table->string('order_id'); // Almacena el ID del pedido extraído del JSON
            $table->json('json'); // Almacena el JSON completo
            $table->tinyInteger('status')->default(0); // Estado inicial: 0 (en espera) 1 iniciado 2 finalizado , 3 pausado, 4 cancelado, 5 con incidencias.
            $table->string('box');
            $table->string('units_box');
            $table->string('units');
            $table->decimal('orden', 8)->nullable(); // Permite valores decimales y puede ser nulo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scada_order');
    }
};
