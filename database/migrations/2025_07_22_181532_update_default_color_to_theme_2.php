<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateDefaultColorToTheme2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Actualizar todos los registros existentes de color a theme-2
        DB::table('settings')->where('name', 'color')->update(['value' => 'theme-2']);

        // También actualizar el valor predeterminado en el código
        // Esto ya lo hicimos manualmente en setting.blade.php
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Si se quiere revertir, se puede establecer de nuevo a theme-1 (el valor original)
        DB::table('settings')->where('name', 'color')->update(['value' => 'theme-1']);
    }
}
