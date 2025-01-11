<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductListRfidTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_list_rfid', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_list_id'); // Clave foránea a product_lists
            $table->unsignedBigInteger('rfid_reading_id'); // Clave foránea a rfid_readings
            $table->timestamps();

            // Relación con product_lists
            $table->foreign('product_list_id')
                ->references('id')
                ->on('product_lists')
                ->onDelete('cascade');

            // Relación con rfid_readings
            $table->foreign('rfid_reading_id')
                ->references('id')
                ->on('rfid_readings')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_list_rfid');
    }
}
