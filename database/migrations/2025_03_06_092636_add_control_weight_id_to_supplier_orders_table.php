<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddControlWeightIdToSupplierOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('control_weight_id')->nullable()->after('barcode');
            $table->foreign('control_weight_id')
                  ->references('id')
                  ->on('control_weights')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropForeign(['control_weight_id']);
            $table->dropColumn('control_weight_id');
        });
    }
}
