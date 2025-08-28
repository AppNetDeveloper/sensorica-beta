<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_part_maintenance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_id');
            $table->unsignedBigInteger('maintenance_part_id');
            $table->timestamps();

            $table->foreign('maintenance_id')->references('id')->on('maintenances')->onDelete('cascade');
            $table->foreign('maintenance_part_id')->references('id')->on('maintenance_parts')->onDelete('cascade');
            $table->unique(['maintenance_id', 'maintenance_part_id'], 'uniq_maint_part');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_part_maintenance');
    }
};
