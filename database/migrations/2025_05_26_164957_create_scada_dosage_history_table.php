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
        Schema::create('scada_dosage_history', function (Blueprint $table) {
            $table->id(); // Columna ID autoincremental y clave primaria
            $table->string('operator_name');
            $table->unsignedBigInteger('orderId'); // Asumiendo que orderId se relaciona con otra tabla, o es un número grande
            $table->decimal('dosage_kg', 8, 3); // Por ejemplo, hasta 99999.999 kg
            $table->string('material_name');
            $table->timestamps(); // Columnas created_at y updated_at (opcional, pero buena práctica)

            // Opcional: Si 'orderId' es una clave foránea a otra tabla (por ejemplo, 'orders')
            // $table->foreign('orderId')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scada_dosage_history');
    }
};
