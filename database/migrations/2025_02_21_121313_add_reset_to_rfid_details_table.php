<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResetToRfidDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('rfid_details', function (Blueprint $table) {
            // Agregamos el campo "reset" tipo booleano, con valor por defecto 0.
            $table->boolean('reset')->default(0)->after('send_alert');
        });
    }

    public function down()
    {
        Schema::table('rfid_details', function (Blueprint $table) {
            $table->dropColumn('reset');
        });
    }
}
