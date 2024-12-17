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
        Schema::table('control_weights', function (Blueprint $table) {
            $table->decimal('box_m3', 10, 3)->nullable()->after('last_dimension'); // Reemplaza 'existing_column' si quieres un orden específico.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('control_weights', function (Blueprint $table) {
            $table->dropColumn('box_m3');
        });
    }
};
