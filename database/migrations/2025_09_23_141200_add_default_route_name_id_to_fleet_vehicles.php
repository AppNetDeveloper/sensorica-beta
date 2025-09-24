<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fleet_vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_vehicles', 'default_route_name_id')) {
                $table->foreignId('default_route_name_id')->nullable()->constrained('route_names')->nullOnDelete()->after('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleet_vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('fleet_vehicles', 'default_route_name_id')) {
                $table->dropConstrainedForeignId('default_route_name_id');
            }
        });
    }
};
