<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionLineProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_line_process', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('process_id')->constrained('processes')->onDelete('cascade');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            
            // Asegurar que no haya duplicados de la misma relación
            $table->unique(['production_line_id', 'process_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_line_process');
    }
}
