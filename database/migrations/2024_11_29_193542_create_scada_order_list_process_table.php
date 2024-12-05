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
        Schema::create('scada_order_list_process', function (Blueprint $table) {
            $table->id(); // ID principal
            $table->foreignId('scada_order_list_id')
                  ->constrained('scada_order_list')
                  ->onDelete('cascade');
            $table->foreignId('scada_material_type_id')
                  ->constrained('scada_material_type')
                  ->onDelete('cascade');
            $table->integer('orden');
            $table->string('measure', 20);
            $table->decimal('value', 10, 2)->nullable(); // Nuevo campo para almacenar el valor
            $table->tinyInteger('used')->default(0)->comment('0 = No usado, 1 = Usado');
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
        Schema::dropIfExists('scada_order_list_process');
    }
};
