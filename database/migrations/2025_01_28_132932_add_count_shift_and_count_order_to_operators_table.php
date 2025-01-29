<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->integer('count_shift')->default(0)->after('id'); // Cambia 'existing_column_name' por la columna actual después de la cual quieres agregar este campo
            $table->integer('count_order')->default(0)->after('count_shift');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn(['count_shift', 'count_order']);
        });
    }
};
