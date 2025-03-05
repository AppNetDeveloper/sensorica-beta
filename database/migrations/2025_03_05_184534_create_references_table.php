<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferencesTable extends Migration
{
    public function up()
    {
        Schema::create('references', function (Blueprint $table) {
            // Asumimos que 'id' es un identificador alfanumérico
            $table->string('id')->primary();
            $table->string('customerId');
            $table->string('families');
            $table->string('eanCode');
            $table->string('rfidCode');
            $table->string('description');
            $table->integer('value');
            $table->string('magnitude');
            $table->string('measure');
            $table->string('envase');
            $table->decimal('tolerancia_min', 8, 2);
            $table->decimal('tolerancia_max', 8, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('references');
    }
}

