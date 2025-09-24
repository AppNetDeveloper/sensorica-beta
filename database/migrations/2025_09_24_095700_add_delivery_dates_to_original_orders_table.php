<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->date('estimated_delivery_date')->nullable()->after('delivery_date');
            $table->date('actual_delivery_date')->nullable()->after('estimated_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dropColumn(['estimated_delivery_date', 'actual_delivery_date']);
        });
    }
};
