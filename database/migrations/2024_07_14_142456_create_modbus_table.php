<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('modbuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('production_line_id');
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');

            $table->unsignedBigInteger('barcoder_id')->nullable();
            $table->foreign('barcoder_id')->references('id')->on('barcodes')->onDelete('cascade');

            $table->text('json_api')->nullable();
            $table->string('mqtt_topic_modbus')->nullable();
            $table->string('mqtt_topic')->nullable();
            $table->string('token')->nullable();
            $table->string('dimension_id')->nullable();
            $table->string('dimension')->nullable();
            $table->string('max_kg')->nullable();
            $table->string('rep_number')->nullable();
            $table->string('tara')->default('0')->nullable();
            $table->string('tara_calibrate')->default('0')->nullable();
            $table->string('calibration_type')->default('0')->nullable();
            $table->string('conversion_factor')->default('10')->nullable();
            $table->string('total_kg_order')->default('0')->nullable();
            $table->string('total_kg_shift')->default('0')->nullable();
            $table->string('min_kg')->nullable();
            $table->string('last_kg')->nullable();
            $table->string('last_rep')->nullable();
            $table->string('rec_box')->nullable();
            $table->string('rec_box_shift')->nullable();
            $table->string('rec_box_unlimited')->nullable();
            $table->string('last_value')->nullable();
            $table->string('variacion_number')->nullable();
            $table->string('model_name')->nullable();
            $table->string('dimension_default')->nullable();
            $table->string('dimension_max')->nullable();
            $table->string('dimension_variacion')->nullable();
            $table->string('offset_meter')->default('0')->nullable();
            $table->string('printer_id')->nullable();
            $table->string('unic_code_order')->nullable();
            $table->string('shift_type')->nullable();
            $table->string('event')->nullable();
            $table->integer('downtime_count')->default(0);
            $table->integer('optimal_production_time')->nullable();
            $table->integer('reduced_speed_time_multiplier')->nullable();
            $table->string('box_type')->nullable();
            $table->integer('model_type')->default(0); // Nueva columna añadida
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('modbuses');
    }
};
