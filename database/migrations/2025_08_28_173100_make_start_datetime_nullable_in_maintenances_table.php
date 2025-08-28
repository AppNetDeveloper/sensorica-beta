<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Make start_datetime nullable with default NULL to avoid '0000-00-00 00:00:00'
        // Using raw SQL to avoid requiring doctrine/dbal for column change.
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        if ($driver === 'mysql') {
            // Detect table name and modify column
            DB::statement("ALTER TABLE `maintenances` MODIFY `start_datetime` DATETIME NULL DEFAULT NULL");
        } else {
            // Fallback for other drivers: try a portable change
            // Note: For some drivers this may need doctrine/dbal installed to use Schema::table()->change().
            try {
                Schema::table('maintenances', function ($table) {
                    // @phpstan-ignore-next-line
                    $table->dateTime('start_datetime')->nullable()->default(null)->change();
                });
            } catch (\Throwable $e) {
                // As a last resort, attempt a generic statement
                DB::statement('ALTER TABLE maintenances ALTER COLUMN start_datetime DROP NOT NULL');
            }
        }
    }

    public function down(): void
    {
        // Revert to NOT NULL; prior installs sometimes used the invalid zero-date as a sentinel.
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `maintenances` MODIFY `start_datetime` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
        } else {
            try {
                Schema::table('maintenances', function ($table) {
                    // @phpstan-ignore-next-line
                    $table->dateTime('start_datetime')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                // Fallback generic (may vary per driver)
                DB::statement("ALTER TABLE maintenances ALTER COLUMN start_datetime SET NOT NULL");
            }
        }
    }
};
