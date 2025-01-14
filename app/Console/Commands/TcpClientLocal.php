<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ApiQueuePrint;
use App\Models\Modbus;

class TcpClientlocal extends Command
{
    protected $signature = 'tcp:client-local';
    protected $description = 'Connect to TCP server using .env values and log messages in a loop';

    public function handle()
    {
        while (true) {
            $this->connectAndListen();
            sleep(5); // Espera 5 segundos antes de intentar reconectar
        }
    }

    protected function connectAndListen()
    {
        $host = env('TCP_SERVER', '127.0.0.1'); // Default to 127.0.0.1 if not set
        $port = env('TCP_PORT', 8000); // Default to 8000 if not set

        $this->info("Attempting to connect to TCP server at {$host}:{$port}");

        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $this->error("Error al crear el socket: " . socket_strerror(socket_last_error()));
            return;
        }

        $result = @socket_connect($socket, $host, $port);
        if ($result === false) {
            $this->error("Error al conectar al servidor: " . socket_strerror(socket_last_error($socket)));
            socket_close($socket);
            return;
        }

        $this->info("Conectado al servidor TCP en {$host}:{$port}");

        // Leer mensajes continuamente hasta que haya un error o se cierre la conexión
        while (true) {
            $response = @socket_read($socket, 2048, PHP_NORMAL_READ);
            if ($response === false) {
                $this->error("Error al leer del servidor: " . socket_strerror(socket_last_error($socket)));
                break;
            }

            // Verifica si el servidor ha cerrado la conexión
            if ($response === '') {
                $this->info("El servidor ha cerrado la conexión.");
                break;
            }

            // Procesar el mensaje recibido y guardarlo en logs
            $this->processMessage($response);
        }

        socket_close($socket);
        $this->info("Conexión cerrada, intentando reconectar en 5 segundos...");
    }

    protected function processMessage($message)
    {
        $trimmedMessage = trim($message);
        if ($trimmedMessage !== '') {
            $this->info("Mensaje recibido: {$trimmedMessage}");
            Log::info("Mensaje recibido del servidor TCP: {$trimmedMessage}");

            // Parse the message to extract the key-value pairs
            $pattern = "/'([^']+)':\s*('([^']*)'|(\d+))/";
            preg_match_all($pattern, $trimmedMessage, $matches, PREG_SET_ORDER);

            $data = [];
            foreach ($matches as $match) {
                $key = $match[1];
                $value = isset($match[3]) ? $match[3] : $match[4]; // $match[3] for strings, $match[4] for numbers
                $data[$key] = $value;
            }

            // Insert into the database using the model
            if (isset($data['model']) && $data['model'] == "api_queue_print") {
                $this->insertIntoQueueUsingModel($data);
            } else {
                $this->info("Mensaje no válido o sin modelo 'api_queue_print', ignorando.");
            }
        } else {
            $this->info("Mensaje vacío recibido, ignorando.");
        }
    }

    private function insertIntoQueueUsingModel($data)
    {
        try {
            // Buscar el modbus_id usando el token
            $modbus = Modbus::where('token', $data['token'])->first();
    
            if (!$modbus) {
                throw new \Exception("No se encontró ningún modbus con el token proporcionado.");
            }
    
            $queuePrint = new ApiQueuePrint([
                'modbus_id' => $modbus->id, // Usamos el id del modbus encontrado
                'value' => $data['value'], // Assuming 'value' is a required field
                'used' => 0, // 0 por defecto
                'url_back' => $data['url_back'], // Assuming 'url_back' is a required field
                'token_back' => $data['token_back'] // Assuming 'token_back' is a required field
            ]);
    
            $queuePrint->save();
    
            $this->info("Datos insertados en api_queue_prints usando modelo.");
        } catch (\Exception $e) {
            $this->error("Error al insertar en la base de datos: " . $e->getMessage());
            Log::error("Error al insertar en api_queue_prints usando modelo: " . $e->getMessage());
        }
    }
}