<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateArticleFieldMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_field_mappings', function (Blueprint $table) {
            // Verificar si la columna transformation existe
            if (Schema::hasColumn('article_field_mappings', 'transformation')) {
                // Renombrar la columna transformation a transformations
                $table->renameColumn('transformation', 'transformations');
            } else if (!Schema::hasColumn('article_field_mappings', 'transformations')) {
                // Si no existe ninguna de las dos, crear transformations
                $table->json('transformations')->nullable();
            }
            
            // Añadir la columna is_required si no existe
            if (!Schema::hasColumn('article_field_mappings', 'is_required')) {
                $table->boolean('is_required')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_field_mappings', function (Blueprint $table) {
            // Si estamos revirtiendo y existe transformations pero no transformation
            if (Schema::hasColumn('article_field_mappings', 'transformations') && 
                !Schema::hasColumn('article_field_mappings', 'transformation')) {
                // Renombrar de vuelta a transformation
                $table->renameColumn('transformations', 'transformation');
            }
            
            // Eliminar la columna is_required si existe
            if (Schema::hasColumn('article_field_mappings', 'is_required')) {
                $table->dropColumn('is_required');
            }
        });
    }
}
