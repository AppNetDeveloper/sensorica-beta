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
        Schema::table('original_order_processes', function (Blueprint $table) {
            // Añadimos la columna 'grupo_numero' de tipo string (o integer si prefieres)
            // La colocamos después de 'process_id' para mantener el orden.
            // La hacemos 'nullable' por si alguna vez tienes un proceso sin grupo.
            $table->string('grupo_numero')->nullable()->after('process_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            // Esto permite revertir la migración si es necesario
            $table->dropColumn('grupo_numero');
        });
    }
};
