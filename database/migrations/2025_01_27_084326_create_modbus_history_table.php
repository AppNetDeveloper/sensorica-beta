<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModbusHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modbus_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('modbus_id'); // Clave foránea con modbuses
            $table->unsignedInteger('rec_box_shift')->default(0);
            $table->unsignedInteger('rec_box')->default(0);
            $table->unsignedInteger('downtime_count')->default(0);
            $table->string('unic_code_order', 255);
            $table->unsignedDecimal('total_kg_order', 10, 2)->default(0);
            $table->unsignedDecimal('total_kg_shift', 10, 2)->default(0);
            $table->timestamps();

            // Relaciones
            $table->foreign('modbus_id')->references('id')->on('modbuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modbus_history');
    }
}
