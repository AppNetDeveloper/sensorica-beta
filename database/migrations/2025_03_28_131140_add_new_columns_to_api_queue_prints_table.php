<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToApiQueuePrintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('api_queue_prints', function (Blueprint $table) {
            $table->float('control_weight')->nullable()->after('used');
            $table->float('control_height')->nullable()->after('control_weight');
            $table->string('barcoder')->nullable()->after('control_height');
            $table->integer('box_number')->nullable()->after('barcoder');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_queue_prints', function (Blueprint $table) {
            $table->dropColumn(['control_weight', 'control_height', 'barcoder', 'box_number']);
        });
    }
}
