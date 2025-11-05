<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeSettingsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('api_timeout')->default(30)->comment('Timeout para llamadas API en segundos');
            $table->integer('lock_timeout')->default(30)->comment('Timeout máximo de bloqueo en minutos');
            $table->integer('search_delay')->default(100)->comment('Delay entre procesamientos en milisegundos');
            $table->boolean('enable_parallel_processing')->default(true)->comment('Permitir procesamiento paralelo');
            $table->decimal('lock_timeout_tolerance', 5, 2)->default(0.10)->comment('Tolerancia del 10% para evitar sincronización (0.10 = 10%)');
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
            $table->dropColumn([
                'api_timeout',
                'lock_timeout',
                'search_delay',
                'enable_parallel_processing',
                'lock_timeout_tolerance'
            ]);
        });
    }
}
