<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceTypeToScadaMaterialTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scada_material_type', function (Blueprint $table) {
            $table->tinyInteger('service_type')->default(0)->after('density');
            $table->string('client_id')->default(0)->after('service_type');
        });
    }

    public function down()
    {
        Schema::table('scada_material_type', function (Blueprint $table) {
            $table->dropColumn('service_type');
            $table->dropColumn('cliente_id');
        });
    }

}
