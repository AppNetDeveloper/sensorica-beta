<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFactorCorreccionToProcessesTable extends Migration
{
    public function up()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->decimal('factor_correccion', 8, 2)->default(1.00)->after('description');
        });
    }

    public function down()
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn('factor_correccion');
        });
    }
}
