<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modbuses', function (Blueprint $table) {
            $table->string('dosage_order')->nullable();
            $table->decimal('box_width', 8, 2)->nullable();
            $table->decimal('box_length', 8, 2)->nullable();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modbuses', function (Blueprint $table) {
            $table->dropColumn(['dosage_order', 'box_width', 'box_length']);
        });
    }
};
