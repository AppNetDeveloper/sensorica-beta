<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarcodeScansAfterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barcode_scans_after', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barcode_scan_id')->constrained('barcode_scans')->cascadeOnDelete();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('production_line_id')->constrained('production_lines')->cascadeOnDelete();
            $table->foreignId('barcoder_id')->nullable()->constrained('barcodes')->nullOnDelete();
            $table->foreignId('original_order_id')->nullable()->constrained('original_orders')->nullOnDelete();
            $table->foreignId('original_order_process_id')->nullable()->constrained('original_order_processes')->nullOnDelete();
            $table->string('order_id')->nullable()->index();
            $table->string('grupo_numero')->nullable()->index();
            $table->timestamp('scanned_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barcode_scans_after');
    }
}
