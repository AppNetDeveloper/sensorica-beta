<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatorRfidTable extends Migration
{
    public function up()
    {
        Schema::create('operator_rfid', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('rfid_reading_id');
            $table->timestamps();

            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('rfid_reading_id')->references('id')->on('rfid_readings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('operator_rfid');
    }
}

