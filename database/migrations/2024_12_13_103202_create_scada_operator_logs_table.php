<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScadaOperatorLogsTable extends Migration
{
    public function up()
    {
        Schema::create('scada_operator_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('scada_id')->nullable();
            $table->timestamps();

            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('scada_id')->references('id')->on('scada')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('scada_operator_logs');
    }
}

