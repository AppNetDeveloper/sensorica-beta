<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('process_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('source_field')->comment('Campo en la respuesta de la API de detalle (ej: ProcessCode)');
            $table->string('target_field')->comment('Campo en la tabla original_order_processes (ej: process_id)');
            $table->json('transformations')->nullable()->comment('Transformaciones a aplicar (ej: trim, uppercase)');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            // Asegurarse de que no haya mapeos duplicados para el mismo campo de destino por cliente
            $table->unique(['customer_id', 'target_field']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('process_field_mappings');
    }
};
