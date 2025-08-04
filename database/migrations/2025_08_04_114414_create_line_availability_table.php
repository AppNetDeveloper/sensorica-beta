<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineAvailabilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('line_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('shift_list_id')->constrained('shift_lists')->onDelete('cascade');
            $table->tinyInteger('day_of_week')->comment('1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Índice único para evitar duplicados de línea+turno+día
            $table->unique(['production_line_id', 'shift_list_id', 'day_of_week'], 'line_shift_day_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('line_availability');
    }
}
