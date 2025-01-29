<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rfid_readings', function (Blueprint $table) {
            $table->foreignId('rfid_color_id')->nullable()->after('token')->constrained('rfid_colors');
        });
    }

    public function down()
    {
        Schema::table('rfid_readings', function (Blueprint $table) {
            $table->dropForeign(['rfid_color_id']);
            $table->dropColumn('rfid_color_id');
        });
    }
};
