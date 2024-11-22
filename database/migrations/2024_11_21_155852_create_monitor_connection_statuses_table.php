<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorConnectionStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_connection_statuses', function (Blueprint $table) {
            $table->id(); // Clave primaria
            $table->foreignId('monitor_connection_id')->constrained('monitor_connections')->onDelete('cascade'); // Clave foránea con monitor_connections
            $table->string('status'); // El estado recibido
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitor_connection_statuses');
    }
}
