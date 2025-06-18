<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleFieldMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('source_field')->comment('Campo de origen en el JSON (ej: grupos[*].articulos[*].CodigoArticulo)');
            $table->string('target_field')->comment('Campo destino en OriginalOrderArticle (codigo_articulo, descripcion_articulo, grupo_articulo)');
            $table->text('transformation')->nullable()->comment('Transformación a aplicar al valor (JSON)');
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['customer_id', 'target_field']);
            $table->index('source_field');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_field_mappings');
    }
}
