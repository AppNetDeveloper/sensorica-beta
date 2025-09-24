<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_plans', function (Blueprint $table) {
            // Add weekly-based field if not exists
            if (!Schema::hasColumn('route_plans', 'week_start_date')) {
                $table->date('week_start_date')->nullable()->after('customer_id');
            }
        });

        Schema::table('route_plans', function (Blueprint $table) {
            // Drop obsolete columns (we move to weekly plan + child days)
            if (Schema::hasColumn('route_plans', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('route_plans', 'vehicle_id')) {
                // First drop FK if present
                try { $table->dropForeign('route_plans_vehicle_id_foreign'); } catch (\Throwable $e) {}
                $table->dropColumn('vehicle_id');
            }
            if (Schema::hasColumn('route_plans', 'driver')) {
                $table->dropColumn('driver');
            }
            if (Schema::hasColumn('route_plans', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('route_plans', function (Blueprint $table) {
            // Create new useful index for weekly plans (best-effort)
            try {
                $table->index(['customer_id', 'week_start_date']);
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_plans', function (Blueprint $table) {
            // Remove new index
            try { $table->dropIndex(['customer_id', 'week_start_date']); } catch (\Throwable $e) {}
        });

        Schema::table('route_plans', function (Blueprint $table) {
            // Re-add previous columns
            if (!Schema::hasColumn('route_plans', 'date')) {
                $table->date('date')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('route_plans', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')->nullable()->after('name');
            }
            if (!Schema::hasColumn('route_plans', 'driver')) {
                $table->string('driver')->nullable()->after('vehicle_id');
            }
            if (!Schema::hasColumn('route_plans', 'notes')) {
                $table->text('notes')->nullable()->after('driver');
            }
        });

        Schema::table('route_plans', function (Blueprint $table) {
            // Restore previous index and FK names (best-effort)
            try { $table->index(['customer_id', 'date']); } catch (\Throwable $e) {}
            try { $table->foreign('vehicle_id')->references('id')->on('fleet_vehicles')->nullOnDelete(); } catch (\Throwable $e) {}
        });

        Schema::table('route_plans', function (Blueprint $table) {
            // Finally drop the weekly column
            if (Schema::hasColumn('route_plans', 'week_start_date')) {
                $table->dropColumn('week_start_date');
            }
        });
    }
};
