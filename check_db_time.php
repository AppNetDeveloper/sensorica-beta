<?php
// Script para verificar la hora del servidor y de la base de datos

echo "Hora del servidor: " . date('Y-m-d H:i:s') . "\n";

// Intentar conectar a la base de datos usando la configuración de Laravel
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Cargar variables de entorno desde .env
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    // Obtener credenciales de la base de datos desde .env
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $database = $_ENV['DB_DATABASE'] ?? '';
    $username = $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consultar la hora de la base de datos
    $stmt = $pdo->query("SELECT NOW() AS db_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Hora de la base de datos: " . $result['db_time'] . "\n";
    
    // Calcular la diferencia
    $serverTime = new DateTime(date('Y-m-d H:i:s'));
    $dbTime = new DateTime($result['db_time']);
    $diff = $serverTime->diff($dbTime);
    
    echo "Diferencia: ";
    if ($diff->h > 0) echo $diff->h . " horas, ";
    if ($diff->i > 0) echo $diff->i . " minutos, ";
    echo $diff->s . " segundos\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
