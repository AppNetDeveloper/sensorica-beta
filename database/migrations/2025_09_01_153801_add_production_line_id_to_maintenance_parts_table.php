<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maintenance_parts', function (Blueprint $table) {
            $table->unsignedBigInteger('production_line_id')->nullable()->after('customer_id');
            $table->index(['production_line_id']);
            $table->foreign('production_line_id')->references('id')->on('production_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_parts', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_parts', 'production_line_id')) {
                $table->dropForeign(['production_line_id']);
                $table->dropIndex(['production_line_id']);
                $table->dropColumn('production_line_id');
            }
        });
    }
};
