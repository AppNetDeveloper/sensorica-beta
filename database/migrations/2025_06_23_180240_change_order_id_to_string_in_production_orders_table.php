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
        Schema::table('production_orders', function (Blueprint $table) {
            // Primero, si la columna era única, considera eliminar el índice único temporalmente
            // para evitar errores si hay duplicados actuales o si el cambio de tipo causa problemas.
            // Si no hay índice único o si ya lo manejas, puedes omitir esto.
            // $table->dropUnique(['order_id']); // Solo si tenías un índice único

            // Cambiar el tipo de columna. Necesitarás 'doctrine/dbal' para esto.
            $table->string('order_id', 255)->change(); // Ajusta la longitud según necesites
            // Si necesitas que sea más largo: $table->text('order_id')->change();

            // Si tenías un índice único, puedes volver a agregarlo aquí si es necesario
            // $table->unique('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Define cómo revertir el cambio, por si acaso
            // Esto podría ser problemático si tus strings actuales no se pueden convertir a BIGINT.
            // Generalmente, no se revierte un tipo de string a un tipo numérico si los datos ya no coinciden.
            $table->bigInteger('order_id')->change();
        });
    }
};