<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('original_order_process_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_order_process_id');
            $table->string('token', 50)->unique();
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('extension', 20)->nullable();
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->timestamps();

            $table->foreign('original_order_process_id')
                ->references('id')->on('original_order_processes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('original_order_process_files');
    }
};
