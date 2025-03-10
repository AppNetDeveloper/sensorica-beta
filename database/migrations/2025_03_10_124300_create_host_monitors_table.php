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
        Schema::create('host_monitors', function (Blueprint $table) {
            $table->id();
            // Clave foránea a host_lists
            $table->foreignId('id_host')->constrained('host_lists')->onDelete('cascade');
            $table->bigInteger('total_memory');
            $table->bigInteger('memory_free');
            $table->bigInteger('memory_used');
            $table->float('memory_used_percent');
            $table->integer('disk'); // Espacio en disco utilizado (ajusta el tipo si es necesario)
            $table->float('cpu'); // Uso de CPU
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('host_monitors');
    }
};
