<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('shift_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id');
            $table->string('type');
            $table->string('action');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('production_line_id')
                  ->references('id')
                  ->on('production_lines')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_history');
    }
}
