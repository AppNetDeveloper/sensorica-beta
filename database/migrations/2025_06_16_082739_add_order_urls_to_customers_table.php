<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderUrlsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('order_listing_url')->nullable()->after('token_zerotier')->comment('URL for fetching the list of orders');
            $table->string('order_detail_url')->nullable()->after('order_listing_url')->comment('URL template for fetching order details, use {order_id} as placeholder');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['order_listing_url', 'order_detail_url']);
        });
    }
}
