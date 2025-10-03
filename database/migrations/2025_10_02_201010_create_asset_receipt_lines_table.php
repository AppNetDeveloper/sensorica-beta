<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_receipt_id')->constrained('asset_receipts')->cascadeOnDelete();
            $table->foreignId('vendor_order_line_id')->constrained('vendor_order_lines')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->decimal('quantity_received', 12, 4);
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['asset_receipt_id', 'vendor_order_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_receipt_lines');
    }
};
