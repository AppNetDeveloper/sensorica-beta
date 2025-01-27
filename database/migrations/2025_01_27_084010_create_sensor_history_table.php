<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSensorHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sensor_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sensor_id'); // Clave foránea con sensors
            $table->unsignedInteger('count_shift_1')->default(0);
            $table->unsignedInteger('count_shift_0')->default(0);
            $table->unsignedInteger('count_order_0')->default(0);
            $table->unsignedInteger('count_order_1')->default(0);
            $table->unsignedInteger('downtime_count')->default(0);
            $table->string('unic_code_order', 255);
            $table->string('orderId', 255);
            $table->timestamps();

            // Relaciones
            $table->foreign('sensor_id')->references('id')->on('sensors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sensor_history');
    }
}
