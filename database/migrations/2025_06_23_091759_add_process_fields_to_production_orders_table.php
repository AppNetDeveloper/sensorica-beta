<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('grupo_numero')->nullable()->after('original_order_id');
            $table->integer('processes_to_do')->default(0)->after('grupo_numero');
            $table->integer('processes_done')->default(0)->after('processes_to_do');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn(['grupo_numero', 'processes_to_do', 'processes_done']);
        });
    }
};
