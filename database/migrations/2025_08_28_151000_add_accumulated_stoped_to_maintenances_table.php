<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->unsignedBigInteger('accumulated_maintenance_seconds_stoped')->default(0)->after('accumulated_maintenance_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn('accumulated_maintenance_seconds_stoped');
        });
    }
};
