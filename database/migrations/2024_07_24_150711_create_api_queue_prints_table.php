<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_queue_prints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modbus_id'); // Clave foránea a modbuses
            $table->string('value');
            $table->boolean('used')->default(false); // Para marcar si el valor ya se usó
            $table->string('url_back');
            $table->string('token_back');
            $table->timestamps();

            $table->foreign('modbus_id')->references('id')->on('modbuses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_queue_prints');
    }
};
