<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnsNullableInOperatorPostTable extends Migration
{
    public function up()
    {
        Schema::table('operator_post', function (Blueprint $table) {
            $table->bigInteger('rfid_reading_id')->unsigned()->nullable()->change();
            $table->bigInteger('operator_id')->unsigned()->nullable()->change();
        });
    }

    public function down()
    {
        // En el método down, volvemos a NO NULL si quieres revertir
        Schema::table('operator_post', function (Blueprint $table) {
            $table->bigInteger('rfid_reading_id')->unsigned()->nullable(false)->change();
            $table->bigInteger('operator_id')->unsigned()->nullable(false)->change();
        });
    }
}
