<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quality_issues', function (Blueprint $table) {
            $table->bigIncrements('id');
            // FK a la línea de producción (obligatorio)
            $table->foreignId('production_line_id')
                ->constrained('production_lines')
                ->restrictOnDelete();
            // FK al pedido de producción con borrado en cascada
            $table->foreignId('production_order_id')
                ->constrained('production_orders')
                ->cascadeOnDelete();
            // FK al operario (operators): si se borra, dejar NULL
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('operators')
                ->nullOnDelete();
            $table->text('texto');
            $table->timestamps();

            $table->index('production_line_id');
            $table->index('production_order_id');
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_issues');
    }
};
