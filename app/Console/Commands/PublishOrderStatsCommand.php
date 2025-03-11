<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use App\Models\OrderStat;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
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
            // Inserta en la tabla mqtt_send_server1
            MqttSendServer1::createRecord($topic, $message);

            // Inserta en la tabla mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);

            $this->info("Mensaje almacenado en ambas tablas para el tópico: {$topic}");
        } catch (Exception $e) {
            Log::error("Error almacenando el mensaje en las tablas MQTT: " . $e->getMessage());
        }
    }
}

