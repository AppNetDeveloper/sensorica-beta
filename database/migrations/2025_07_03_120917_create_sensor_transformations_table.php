<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSensorTransformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sensor_transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')->constrained()->onDelete('cascade');
            $table->float('min_value')->nullable();
            $table->float('max_value')->nullable();
            $table->string('input_topic');
            $table->string('output_topic');
            $table->boolean('active')->default(true);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sensor_transformations');
    }
}
