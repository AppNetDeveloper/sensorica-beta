<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_clients', 'route_name_id')) {
                $table->foreignId('route_name_id')->nullable()->constrained('route_names')->nullOnDelete()->after('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_clients', function (Blueprint $table) {
            if (Schema::hasColumn('customer_clients', 'route_name_id')) {
                $table->dropConstrainedForeignId('route_name_id');
            }
        });
    }
};
