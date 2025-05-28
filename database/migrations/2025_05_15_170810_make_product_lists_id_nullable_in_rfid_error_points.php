<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeProductListsIdNullableInRfidErrorPoints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rfid_error_points', function (Blueprint $table) {
            $table->unsignedBigInteger('product_lists_id')->nullable()->change();
        });
    }
    public function down()
    {
        Schema::table('rfid_error_points', function (Blueprint $table) {
            $table->unsignedBigInteger('product_lists_id')->nullable(false)->change();
        });
    }
    
}
