<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->foreignId('asset_cost_center_id')->nullable()->constrained('asset_cost_centers')->nullOnDelete();
            $table->foreignId('asset_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->foreignId('vendor_supplier_id')->nullable()->constrained('vendor_suppliers')->nullOnDelete();
            $table->string('article_code');
            $table->string('label_code');
            $table->string('description');
            $table->string('status')->default('active');
            $table->boolean('has_rfid_tag')->default(false);
            $table->string('rfid_tid')->nullable();
            $table->string('rfid_epc')->nullable();
            $table->date('acquired_at')->nullable();
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'article_code']);
            $table->unique(['customer_id', 'label_code']);
            $table->unique(['customer_id', 'rfid_tid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
