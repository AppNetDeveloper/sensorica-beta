<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quality_issues', function (Blueprint $table) {
            $table->foreignId('original_order_id_qc')
                ->nullable()
                ->after('original_order_id')
                ->constrained('original_orders')
                ->nullOnDelete();

            $table->index('original_order_id_qc');
        });
    }

    public function down(): void
    {
        Schema::table('quality_issues', function (Blueprint $table) {
            if (Schema::hasColumn('quality_issues', 'original_order_id_qc')) {
                $table->dropConstrainedForeignId('original_order_id_qc');
                $table->dropIndex(['original_order_id_qc']);
            }
        });
    }
};
