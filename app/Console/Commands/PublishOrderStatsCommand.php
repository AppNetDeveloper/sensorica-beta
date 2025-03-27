<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use App\Models\OrderStat;
use Illuminate\Support\Facades\Log;
use Exception;

class PublishOrderStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Se ejecuta con: php artisan mqtt:publish-order-stats
     *
     * @var string
     */
    protected $signature = 'mqtt:publish-order-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae datos de barcodes y order_stats y publica un JSON vía MQTT cada 1 segundo';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando el comando mqtt:publish-order-stats...");

        // Bucle infinito. Supervisor se encargará de reiniciar el proceso en caso de error.
        while (true) {
            try {
                // Recupera todas las líneas de la tabla barcodes que tengan definidos mqtt_topic_barcodes y production_line_id
                $barcodes = Barcode::whereNotNull('mqtt_topic_barcodes')
                                    ->whereNotNull('production_line_id')
                                    ->get();

                if ($barcodes->isEmpty()) {
                    $this->info("No se encontraron registros en barcodes con mqtt_topic_barcodes y production_line_id definidos.");
                } else {
                    foreach ($barcodes as $barcode) {
                        $topic = $barcode->mqtt_topic_barcodes;
                        $productionLineId = $barcode->production_line_id;

                        // Busca la última entrada en order_stats para el production_line_id dado
                        $orderStat = OrderStat::where('production_line_id', $productionLineId)
                                                ->orderBy('id', 'desc')
                                                ->first();

                        if ($orderStat) {
                            // Convierte el registro a JSON
                            $message = $orderStat->toJson();

                            // Publica el mensaje vía MQTT (almacena en ambas tablas)
                            $this->publishMqttMessage($topic, $message);
                        } else {
                            $this->info("No se encontró un registro en order_stats para production_line_id: {$productionLineId}");
                        }
                    }
                }
            } catch (Exception $e) {
                // Registra el error y continúa el ciclo
                Log::error("Error en mqtt:publish-order-stats: " . $e->getMessage());
            }

            // Espera 1 segundo antes de la siguiente iteración
            sleep(1);
        }

        return 0;
    }

    /**
     * Función para publicar el mensaje en MQTT mediante inserción en dos tablas.
     *
     * @param string $topic
     * @param string $message
     */
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Preparar los datos a almacenar, agregando la fecha y hora
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];
        
            // Convertir a JSON
            $jsonData = json_encode($data);
        
            // Sanitizar el topic para evitar creación de subcarpetas
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generar un identificador único (por ejemplo, usando microtime)
            $uniqueId = round(microtime(true) * 1000); // milisegundos
        
            // Guardar en servidor 1
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            file_put_contents($fileName1, $jsonData . PHP_EOL);
            Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Guardar en servidor 2
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}

