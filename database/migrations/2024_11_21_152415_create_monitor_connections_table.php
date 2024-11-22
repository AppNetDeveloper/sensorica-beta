<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorConnectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monitor_connections', function (Blueprint $table) {
            $table->id(); // Clave primaria
            $table->string('name'); // Campo 'name'
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade'); // Clave foránea
            $table->string('mqtt_topic'); // Campo 'mqtt_topic'
            $table->string('last_status')->default('unknown'); // Campo 'last_status' con valor predeterminado
            $table->timestamps(); // Campos 'created_at' y 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monitor_connections');
    }
}

