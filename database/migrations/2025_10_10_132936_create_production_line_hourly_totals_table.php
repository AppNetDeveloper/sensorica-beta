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
        Schema::create('production_line_hourly_totals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_line_id');
            $table->unsignedBigInteger('process_id');
            $table->unsignedBigInteger('total_time')->default(0);
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();

            $table->foreign('production_line_id')
                ->references('id')
                ->on('production_lines')
                ->cascadeOnDelete();

            $table->foreign('process_id')
                ->references('id')
                ->on('processes')
                ->restrictOnDelete();

            $table->unique(['production_line_id', 'captured_at'], 'production_line_captured_unique');
            $table->index(['captured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_line_hourly_totals');
    }
};
