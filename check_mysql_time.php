<?php
// Script para verificar la hora de MySQL/Percona

require_once __DIR__ . '/vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    echo "Hora del sistema: " . date('Y-m-d H:i:s') . "\n";
    echo "Hora de PHP (date): " . date('Y-m-d H:i:s') . "\n";
    echo "Hora de Laravel (now): " . now()->format('Y-m-d H:i:s') . "\n";
    
    // Consultar la hora de MySQL directamente usando Laravel
    $dbTime = DB::select('SELECT NOW() as db_time')[0]->db_time;
    echo "Hora de MySQL/Percona: " . $dbTime . "\n";
    
    // Mostrar la zona horaria configurada en Laravel
    echo "Zona horaria configurada en Laravel: " . config('app.timezone') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
