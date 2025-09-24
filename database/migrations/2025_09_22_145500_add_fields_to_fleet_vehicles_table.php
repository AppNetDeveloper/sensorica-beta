<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_vehicles', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('active');
            $table->float('capacity_kg')->nullable()->after('height_cm');
            $table->string('fuel_type', 50)->nullable()->after('capacity_kg');
            $table->date('itv_expires_at')->nullable()->after('fuel_type');
            $table->date('insurance_expires_at')->nullable()->after('itv_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_vehicles', function (Blueprint $table) {
            $table->dropColumn(['notes', 'capacity_kg', 'fuel_type', 'itv_expires_at', 'insurance_expires_at']);
        });
    }
};
