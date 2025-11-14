<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionOrderTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_order_transfers', function (Blueprint $table) {
            $table->id();

            // IDs de las production_orders (tarjetas)
            $table->unsignedBigInteger('production_order_id_source')->nullable()->comment('Tarjeta original del customer origen');
            $table->unsignedBigInteger('production_order_id_target')->nullable()->comment('Tarjeta creada en customer destino');

            // Customers
            $table->unsignedBigInteger('from_customer_id')->comment('Customer origen');
            $table->unsignedBigInteger('to_customer_id')->comment('Customer destino');

            // Original Orders
            $table->unsignedBigInteger('original_order_id_source')->comment('OriginalOrder del customer origen');
            $table->unsignedBigInteger('original_order_id_target')->comment('OriginalOrder clonado para customer destino');

            // Original Order Processes
            $table->unsignedBigInteger('original_order_process_id_source')->comment('Proceso origen');
            $table->unsignedBigInteger('original_order_process_id_target')->comment('Proceso clonado');

            // Auditoría
            $table->unsignedBigInteger('transferred_by')->comment('Usuario que realizó la transferencia');
            $table->timestamp('transferred_at')->useCurrent()->comment('Fecha y hora de la transferencia');
            $table->text('notes')->nullable()->comment('Notas adicionales de la transferencia');

            // Estado de la transferencia
            $table->enum('status', ['active', 'returned', 'completed', 'cancelled'])
                  ->default('active')
                  ->comment('Estado: active=activa, returned=devuelta, completed=completada, cancelled=cancelada');

            $table->timestamps();

            // Foreign keys (con nombres cortos para evitar límite de MySQL)
            $table->foreign('production_order_id_source', 'pot_po_source_fk')->references('id')->on('production_orders')->onDelete('set null');
            $table->foreign('production_order_id_target', 'pot_po_target_fk')->references('id')->on('production_orders')->onDelete('set null');
            $table->foreign('from_customer_id', 'pot_from_customer_fk')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('to_customer_id', 'pot_to_customer_fk')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('original_order_id_source', 'pot_oo_source_fk')->references('id')->on('original_orders')->onDelete('cascade');
            $table->foreign('original_order_id_target', 'pot_oo_target_fk')->references('id')->on('original_orders')->onDelete('cascade');
            $table->foreign('original_order_process_id_source', 'pot_oop_source_fk')->references('id')->on('original_order_processes')->onDelete('cascade');
            $table->foreign('original_order_process_id_target', 'pot_oop_target_fk')->references('id')->on('original_order_processes')->onDelete('cascade');
            $table->foreign('transferred_by', 'pot_user_fk')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index('from_customer_id');
            $table->index('to_customer_id');
            $table->index('status');
            $table->index('transferred_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_order_transfers');
    }
}
