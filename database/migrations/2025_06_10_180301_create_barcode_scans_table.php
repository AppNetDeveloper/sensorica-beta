<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarcodeScansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barcode_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('operator_id')->nullable()->constrained('operators')->onDelete('set null');
            $table->string('barcode')->index();
            $table->text('barcode_data')->nullable()->comment('Datos adicionales del código de barras en formato JSON');
            $table->timestamp('scanned_at')->useCurrent();
            $table->timestamps();
            
            // Índices adicionales para mejorar el rendimiento de las consultas
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barcode_scans');
    }
}
