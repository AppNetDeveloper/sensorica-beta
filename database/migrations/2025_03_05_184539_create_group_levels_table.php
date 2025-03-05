<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupLevelsTable extends Migration
{
    public function up()
    {
        Schema::create('group_levels', function (Blueprint $table) {
            $table->id();
            // Clave foránea que relaciona con la tabla 'references'
            $table->string('reference_id');
            $table->foreign('reference_id')->references('id')->on('references')->onDelete('cascade');
            
            $table->string('id_group'); // Identificador único del grupo (ejemplo: "refer1")
            $table->integer('level');
            $table->integer('uds');
            $table->string('total'); // Ejemplo: "2 * 12 = 24"
            $table->string('measure');
            $table->string('eanCode');
            $table->string('envase');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_levels');
    }
}
