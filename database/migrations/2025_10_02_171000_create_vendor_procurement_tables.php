<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('payment_terms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'name']);
        });

        Schema::create('vendor_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_supplier_id')->nullable()->constrained('vendor_suppliers')->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit_of_measure', 50)->default('unit');
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'name']);
        });

        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_supplier_id')->constrained('vendor_suppliers')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'sent', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->string('currency', 3)->default('EUR');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'vendor_supplier_id']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('vendor_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->cascadeOnDelete();
            $table->foreignId('vendor_item_id')->nullable()->constrained('vendor_items')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity_ordered', 14, 4);
            $table->decimal('quantity_received', 14, 4)->default(0);
            $table->decimal('unit_price', 12, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->enum('status', ['open', 'partially_received', 'received', 'cancelled'])->default('open');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_order_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_order_documents');
        Schema::dropIfExists('vendor_order_lines');
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('vendor_items');
        Schema::dropIfExists('vendor_suppliers');
    }
};
