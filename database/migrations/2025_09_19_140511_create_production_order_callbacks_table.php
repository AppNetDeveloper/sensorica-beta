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
        Schema::create('production_order_callbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('callback_url', 500);
            $table->json('payload'); // JSON generado según mappings
            $table->tinyInteger('status')->default(0); // 0: pendiente, 1: éxito, 2: error/reintentar
            $table->integer('attempts')->default(0); // Número de intentos realizados
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('success_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index(['status', 'attempts']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_callbacks');
    }
};
