<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeToOriginalOrderProcessesTable extends Migration
{
    public function up()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->decimal('time', 10, 2)->nullable()->after('process_id')
                  ->comment('Tiempo calculado: cantidad * factor_correccion');
        });
    }

    public function down()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->dropColumn('time');
        });
    }
}
