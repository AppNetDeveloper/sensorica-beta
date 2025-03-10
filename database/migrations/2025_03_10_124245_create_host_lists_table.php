<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostListsTable extends Migration
{
    public function up()
    {
        Schema::create('host_lists', function (Blueprint $table) {
            $table->id();
            $table->string('host')->unique(); // Dirección IP o nombre de host, único
            $table->string('token')->unique(); // Token único para cada host
            $table->string('name'); // Nombre descriptivo del host (opcional)
            // Campo user_id sin el método after()
            $table->unsignedBigInteger('user_id')->nullable();
            // Definición de la llave foránea (opcional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Nuevos campos de tipo string para emails, phones y telegrams
            $table->string('emails')->nullable();
            $table->string('phones')->nullable();
            $table->string('telegrams')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('host_lists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('host_lists');
    }
}
