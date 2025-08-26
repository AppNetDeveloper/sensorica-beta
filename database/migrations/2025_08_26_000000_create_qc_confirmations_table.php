<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qc_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_line_id');
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('production_line_id');
            $table->index('production_order_id');
            $table->index('original_order_id');
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_confirmations');
    }
};
