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
        Schema::create('production_line_wait_time_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_line_id');
            $table->integer('order_count')->default(0)->comment('Número de órdenes consideradas');
            $table->decimal('wait_time_mean', 10, 2)->nullable()->comment('WT: Tiempo medio de espera en minutos');
            $table->decimal('wait_time_median', 10, 2)->nullable()->comment('WTM: Tiempo mediano de espera en minutos');
            $table->decimal('wait_time_min', 10, 2)->nullable()->comment('Tiempo mínimo de espera en minutos');
            $table->decimal('wait_time_max', 10, 2)->nullable()->comment('Tiempo máximo de espera en minutos');
            $table->timestamp('captured_at')->useCurrent()->comment('Momento de la captura');
            $table->timestamps();

            $table->foreign('production_line_id')
                ->references('id')
                ->on('production_lines')
                ->cascadeOnDelete();

            $table->unique(['production_line_id', 'captured_at'], 'plwt_line_captured_unique');
            $table->index(['captured_at'], 'plwt_captured_idx');
            $table->index(['production_line_id', 'captured_at'], 'plwt_line_captured_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_line_wait_time_history');
    }
};
