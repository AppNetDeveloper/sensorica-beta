<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->string('address')->nullable()->after('client_number');
            $table->string('phone')->nullable()->after('address');
            $table->string('cif_nif')->nullable()->after('phone');
            $table->string('ref_order')->nullable()->after('cif_nif');
        });
    }

    public function down(): void
    {
        Schema::table('original_orders', function (Blueprint $table) {
            $table->dropColumn(['ref_order', 'cif_nif', 'phone', 'address']);
        });
    }
};
