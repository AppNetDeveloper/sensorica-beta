<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOriginalOrderProcessesTable extends Migration
{
    public function up()
    {
        Schema::create('original_order_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_order_id')->constrained('original_orders')->onDelete('cascade');
            $table->foreignId('process_id')->constrained('processes')->onDelete('cascade');
            $table->boolean('created')->default(false)->comment('Indica si se ha creado la orden de producción');
            $table->boolean('finished')->default(false)->comment('Indica si el proceso ha finalizado');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('original_order_processes');
    }
}
