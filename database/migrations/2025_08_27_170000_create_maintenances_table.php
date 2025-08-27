<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('production_line_id');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->text('annotations')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['customer_id']);
            $table->index(['production_line_id']);
            $table->index(['operator_id']);
            $table->index(['user_id']);

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('production_line_id')->references('id')->on('production_lines')->onDelete('cascade');
            // operator_id: referencia a operators (si existe) o dejar sin FK si es opcional entre sistemas
            if (Schema::hasTable('operators')) {
                $table->foreign('operator_id')->references('id')->on('operators')->nullOnDelete();
            }
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
