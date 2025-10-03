<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('label_code');
            $table->string('rfid_epc')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'slug']);
            $table->unique(['customer_id', 'label_code']);
            $table->unique(['customer_id', 'rfid_epc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
