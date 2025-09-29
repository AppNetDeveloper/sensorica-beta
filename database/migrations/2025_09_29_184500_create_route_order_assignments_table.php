<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_order_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_client_vehicle_assignment_id');
            $table->unsignedBigInteger('original_order_id');
            $table->boolean('active')->default(true)->comment('Si está activo para cargar en el camión');
            $table->integer('sort_order')->default(0)->comment('Orden de carga');
            $table->timestamps();

            $table->foreign('route_client_vehicle_assignment_id', 'fk_route_order_rcva')
                ->references('id')
                ->on('route_client_vehicle_assignments')
                ->onDelete('cascade');

            $table->foreign('original_order_id')
                ->references('id')
                ->on('original_orders')
                ->onDelete('cascade');

            // Índices para consultas frecuentes
            $table->index(['route_client_vehicle_assignment_id', 'active'], 'idx_route_order_rcva_active');
            $table->index('original_order_id', 'idx_route_order_original_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_order_assignments');
    }
};
