<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteToRfidErrorPointsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rfid_error_points', function (Blueprint $table) {
            $table->text('note')->nullable()->after('tid');
            // Puedes usar 'annotation' en vez de 'note' si prefieres
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfid_error_points', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
}
