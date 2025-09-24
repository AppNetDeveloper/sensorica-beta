<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_plan_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_plan_id');
            $table->tinyInteger('day_of_week'); // 1=Lunes ... 7=Domingo
            $table->date('date')->nullable(); // semana + offset
            $table->string('name')->nullable(); // opcional por si se quiere nombrar el día
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('route_plan_id')->references('id')->on('route_plans')->onDelete('cascade');
            $table->unique(['route_plan_id', 'day_of_week']);
            $table->index(['route_plan_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_plan_days');
    }
};
