<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\MonitorOee;
use Carbon\Carbon;
use App\Models\OrderStat;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftHistory; // Asegúrate de que la ruta del modelo sea la correcta
use App\Services\OrderTimeService;
use App\Models\ProductList;
use App\Models\OptimalProductionTime;
use App\Models\OptimalSensorTime;
use App\Models\ProductionOrder;
use App\Models\ProductionLine;
use App\Models\SensorCount;
use App\Models\DowntimeSensor;

class CalculateProductionMonitorOeev2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-monitor-oee';
    protected $orderTimeService;

    public function __construct(OrderTimeService $orderTimeService)
    {
        parent::__construct();
        $this->orderTimeService = $orderTimeService;
    }
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcular y gestionar el monitoreo de la producción para sensores y modbuses basado en las reglas de la tabla monitor_oee.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $startTime = microtime(true);
            
            // Llamar a la API para realizar todos los cálculos
            $this->calculateProductionMonitorOee();
            
            // Calcular tiempo transcurrido y esperar hasta completar 1 segundo
            $elapsed = microtime(true) - $startTime;
            $sleepTime = 1 - $elapsed;
            
            if ($sleepTime > 0) {
                $this->info("[" . Carbon::now()->toDateTimeString() . "] Esperando " . round($sleepTime, 3) . " segundos antes de la siguiente ejecución...");
                usleep($sleepTime * 1000000);
            } else {
                $this->info("[" . Carbon::now()->toDateTimeString() . "] La iteración tomó {$elapsed} segundos, iniciando la siguiente sin pausa.");
            }
        }

        return 0;
    }

    /**
     * Llamar a la API para calcular el OEE de producción
     */
    private function calculateProductionMonitorOee()
    {
        // Construir la URL de la API
        $appUrl = rtrim(env('LOCAL_SERVER'), '/');
        $apiUrl = $appUrl . '/api/calculate-production-monitor-oee';

        // Configurar el cliente HTTP (Guzzle)
        $client = new \GuzzleHttp\Client([
            'timeout'     => 0.9,
            'http_errors' => false,
            'verify'      => false,
        ]);

        try {
            // Realizar la llamada POST asíncrona
            $promise = $client->postAsync($apiUrl);

            $promise->then(
                function ($response) {
                    $responseBody = $response->getBody()->getContents();
                    $statusCode = $response->getStatusCode();
                    
                    if ($statusCode === 200) {
                        $data = json_decode($responseBody, true);
                        if ($data && isset($data['success']) && $data['success']) {
                            $this->info("[" . Carbon::now()->toDateTimeString() . "] ✅ API OEE ejecutada correctamente - Monitores procesados: {$data['data']['processed_monitors']}/{$data['data']['total_monitors']}");
                        } else {
                            $this->error("[" . Carbon::now()->toDateTimeString() . "] ❌ API OEE falló: " . ($data['message'] ?? 'Error desconocido'));
                        }
                    } else {
                        $this->error("[" . Carbon::now()->toDateTimeString() . "] ❌ API OEE respondió con código {$statusCode}: {$responseBody}");
                    }
                },
                function ($exception) {
                    $this->error("[" . Carbon::now()->toDateTimeString() . "] ❌ Error en llamada a API OEE: " . $exception->getMessage());
                }
            );

            // Resolver la promesa sin bloquear la ejecución
            $promise->wait(false);
            
        } catch (Exception $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "] ❌ Error al intentar llamar a la API OEE: " . $e->getMessage());
        }
    }
}
