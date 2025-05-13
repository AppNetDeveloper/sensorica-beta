<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This adds a new column to store the minimum time interval
     * (in milliseconds) between readings of RFID tags with the
     * same rssi_min but different TID.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('rfid_ants', function (Blueprint $table) {
            // Add an unsigned integer column to store minimum interval in ms
            // Placed after the 'rssi_min' column — adjust if needed
            $table->unsignedInteger('min_read_interval_ms')
                  ->default(0)
                  ->after('rssi_min')
                  ->comment('Minimum read interval for same rssi_min different TID in milliseconds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('rfid_ants', function (Blueprint $table) {
            $table->dropColumn('min_read_interval_ms');
        });
    }
};