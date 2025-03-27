<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Exception;

class CalculateProductionDowntime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-production-downtime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the production downtime for each sensor and handle downtime counts per shift, and send MQTT messages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $downtimeStartTimes = []; // Array para guardar tiempos de inicio por línea de producción

    // Propiedades para almacenar los tiempos de inicio
    private $productionStopStartTimes = [];
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $startTime = microtime(true); // Capturamos el inicio de la iteración
    
            $this->info("Starting the calculation of production downtime...");
            //extraemos de la tabla production_line todas las lineas de producción
            $this->calculateProductionDowntime();
    
            // Calculamos el tiempo transcurrido
            $elapsed = microtime(true) - $startTime;
            $sleepTime = 1 - $elapsed;
    
            if ($sleepTime > 0) {
                $this->info("Waiting for " . round($sleepTime, 3) . " seconds before the next run...");
                usleep($sleepTime * 1000000); // usleep trabaja con microsegundos
            } else {
                $this->info("La iteración tomó {$elapsed} segundos, iniciando la siguiente sin pausa.");
            }
        }
    
        return 0;
    }
    
    private function calculateProductionDowntime()
    {
        // Construir la URL de la API
        $appUrl = rtrim(env('LOCAL_SERVER'), '/');
        $apiUrl = $appUrl . '/api/calculate-production-downtime';
    
        // Configurar el cliente HTTP (Guzzle)
        $client = new \GuzzleHttp\Client([
            'timeout'     => 0.9,
            'http_errors' => false,
            'verify'      => false,
        ]);
    
        try {
            // Realizamos la llamada POST sin enviar datos en el body
            $promise = $client->postAsync($apiUrl);
    
            $promise->then(
                function ($response) {
                    $responseBody = $response->getBody()->getContents();
                    $this->info("[" . Carbon::now()->toDateTimeString() . "] Respuesta de la API: {$responseBody}");
                },
                function ($exception) {
                    $this->error("[" . Carbon::now()->toDateTimeString() . "] Error en la llamada a la API: " . $exception->getMessage());
                }
            );
    
            // Se resuelve la promesa sin bloquear la ejecución
            $promise->wait(false);
        } catch (Exception $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "] Error al intentar llamar a la API: " . $e->getMessage());
        }
    }
    
}
