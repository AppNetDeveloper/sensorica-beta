<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderStatsOperatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_stats_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_stat_id')->constrained('order_stats')->onDelete('cascade');
            $table->foreignId('shift_history_id')->constrained('shift_history')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('operators')->onDelete('cascade');
            $table->integer('time_spent')->nullable()->comment('Tiempo en segundos que el operario dedicó a esta orden');
            $table->text('notes')->nullable()->comment('Notas adicionales sobre la participación del operario');
            $table->timestamps();
            
            // Índices para mejorar el rendimiento de las consultas
            $table->index(['order_stat_id', 'operator_id']);
            $table->index(['shift_history_id', 'operator_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_stats_operators');
    }
}
