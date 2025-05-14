<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfid_error_points', function (Blueprint $table) {
            $table->id();

            /* -------- Datos del error -------- */
            $table->string('name');
            $table->string('value');
            $table->string('rfid_ant_name');
            $table->string('model_product')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedInteger('count_total')->default(0);
            $table->unsignedInteger('count_total_1')->default(0);
            $table->unsignedInteger('count_shift_1')->default(0);
            $table->unsignedInteger('count_order_1')->default(0);
            $table->timestamp('time_11')->nullable();
            $table->string('epc')->nullable();
            $table->string('tid')->nullable();
            $table->string('rssi')->nullable();
            $table->string('serialno')->nullable();
            $table->unsignedTinyInteger('ant')->nullable();
            $table->string('unic_code_order')->nullable();

            /* -------- Relaciones -------- */
            $table->foreignId('production_line_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_lists_id')
                  ->constrained('product_lists')
                  ->cascadeOnDelete();

            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('operator_post_id')            // FK correcta
                  ->nullable()
                  ->constrained('operator_post')             // tabla singular
                  ->nullOnDelete();

            $table->foreignId('rfid_detail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rfid_reading_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_error_points');
    }
};
