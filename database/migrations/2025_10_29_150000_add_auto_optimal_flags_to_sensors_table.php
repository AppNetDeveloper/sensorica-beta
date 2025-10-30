<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->boolean('auto_optimal_time_enabled')->default(true)->after('max_correction_percentage');
            $table->boolean('auto_update_sensor_optimal_time')->default(true)->after('auto_optimal_time_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn(['auto_optimal_time_enabled', 'auto_update_sensor_optimal_time']);
        });
    }
};
