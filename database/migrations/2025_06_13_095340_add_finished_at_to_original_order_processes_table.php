<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinishedAtToOriginalOrderProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->timestamp('finished_at')->nullable()->after('finished');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('original_order_processes', function (Blueprint $table) {
            $table->dropColumn('finished_at');
        });
    }
}
