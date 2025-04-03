<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOptimalProductionTimeInSensorsTable extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->decimal('optimal_production_time', 8, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            // Asumiendo que antes era tipo integer o float, cambia según tu caso:
            $table->float('optimal_production_time')->change();
        });
    }
}

