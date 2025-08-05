<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('calendar_date');
            $table->string('name')->nullable();
            $table->string('type')->default('holiday'); // holiday, maintenance, vacation, etc.
            $table->boolean('is_working_day')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento de las consultas
            $table->index('calendar_date');
            $table->index('is_working_day');
            $table->unique(['customer_id', 'calendar_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_calendars');
    }
}
