<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOriginalOrderArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('original_order_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_order_process_id')->constrained('original_order_processes')->onDelete('cascade');
            $table->string('codigo_articulo', 50)->comment('Código del artículo');
            $table->string('descripcion_articulo', 255)->comment('Descripción del artículo');
            $table->string('grupo_articulo', 100)->comment('Grupo del artículo');
            $table->timestamps();
            
            // Índice para búsquedas por código de artículo
            $table->index('codigo_articulo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('original_order_articles');
    }
}
