<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ia_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clave única para identificar el prompt, ej: individual_worker');
            $table->string('name')->comment('Nombre descriptivo del prompt');
            $table->text('content')->comment('El contenido completo del prompt');
            $table->string('model_name')->nullable()->comment('Nombre del modelo de IA a usar con este prompt');
            $table->boolean('is_active')->default(true)->comment('Indica si el prompt está activo');
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ia_prompts');
    }
};