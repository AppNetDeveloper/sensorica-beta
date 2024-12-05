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
        Schema::create('scada_order_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scada_order_id')
                  ->constrained('scada_order') // Define la relación con la tabla scada_order
                  ->onDelete('cascade'); // Elimina en cascada si scada_order se elimina
            $table->tinyInteger('process')->default(0); // 0 = automático, 1 = manual
            $table->timestamps(); // Para created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scada_order_list');
    }
};
