<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('qc_confirmations', function (Blueprint $table) {
            // Agregar claves foráneas a columnas existentes
            $table->foreign('production_line_id')
                ->references('id')->on('production_lines')
                ->restrictOnDelete();

            $table->foreign('production_order_id')
                ->references('id')->on('production_orders')
                ->cascadeOnDelete();

            $table->foreign('original_order_id')
                ->references('id')->on('original_orders')
                ->cascadeOnDelete();

            $table->foreign('operator_id')
                ->references('id')->on('operators')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qc_confirmations', function (Blueprint $table) {
            $table->dropForeign(['production_line_id']);
            $table->dropForeign(['production_order_id']);
            $table->dropForeign(['original_order_id']);
            $table->dropForeign(['operator_id']);
        });
    }
};
