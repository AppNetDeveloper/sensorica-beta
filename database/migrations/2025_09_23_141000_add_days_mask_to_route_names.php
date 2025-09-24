<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('route_names', function (Blueprint $table) {
            if (!Schema::hasColumn('route_names', 'days_mask')) {
                $table->unsignedTinyInteger('days_mask')->default(0)->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_names', function (Blueprint $table) {
            if (Schema::hasColumn('route_names', 'days_mask')) {
                $table->dropColumn('days_mask');
            }
        });
    }
};
