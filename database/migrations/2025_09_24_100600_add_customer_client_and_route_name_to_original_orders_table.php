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
            $table->unsignedBigInteger('customer_client_id')->nullable()->after('customer_id');
            $table->unsignedBigInteger('route_name_id')->nullable()->after('customer_client_id');

            $table->foreign('customer_client_id')
                ->references('id')
                ->on('customer_clients')
                ->nullOnDelete();

            $table->foreign('route_name_id')
                ->references('id')
                ->on('route_names')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_client_id']);
            $table->dropForeign(['route_name_id']);
            $table->dropColumn(['customer_client_id', 'route_name_id']);
        });
    }
};
