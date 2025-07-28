<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ScadaOrder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scada_order';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'scada_id',
        'production_line_id',
        'barcoder_id',
        'order_id',
        'json',
        'status',
        'box',
        'units_box',
        'units',
        'orden',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'json' => 'array', // Decodificar automáticamente el JSON a un array
        'status' => 'integer',
    ];

    /**
     * Get the scada record associated with the order.
     */
    public function scada()
    {
        return $this->belongsTo(Scada::class, 'scada_id');
    }

    /**
     * Get the production line associated with the order.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }

    /**
     * Get the barcode associated with the order.
     */
    public function barcode()
    {
        return $this->belongsTo(Barcode::class, 'barcoder_id');
    }

    /**
     * Boot method to observe changes in the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Only trigger if the status attribute is being updated
            if ($model->isDirty('status')) {
                $model->handleStatusChange();
            }
        });
    }

    /**
     * Handle logic when status changes.
     */
    protected function handleStatusChange()
    {
        if ($this->barcoder_id) {
            $barcode = $this->barcode;

            if ($barcode && $barcode->mqtt_topic_barcodes) {
                $newTopic = $barcode->mqtt_topic_barcodes . '/prod_order_mac';

                // Si el estado es 5, enviamos otro mensaje a /order_error
                if ($this->status == 5) {
                    // Dividir en dos partes: antes y después del primer '-'
                    $orderIdParts = explode('-', $this->order_id, 2);
                    $cleanOrderId = $orderIdParts[0]; // Parte limpia antes del primer '-'

                    // Obtener la parte eliminada (después del primer '-')
                    $removedPart = isset($orderIdParts[1]) ? $orderIdParts[1] : '';

                    // Comprobar si contiene "Order-" y eliminarlo
                    if (str_starts_with($removedPart, 'Order-')) {
                        $removedPart = substr($removedPart, strlen('Order-'));
                    }

                    // Extraer la segunda palabra
                    $msgParts = explode('-', $removedPart);
                    $msg = isset($msgParts[0]) ? $msgParts[0] : ''; // La segunda palabra después de "Order-"

                    // Preparar el mensaje JSON
                    $finishMessage = json_encode([
                        'orderId' => $cleanOrderId,
                        'msg' => $msg, // Segunda palabra después de "Order-"
                        'time' => now()->toDateTimeString(), // Fecha y hora actuales
                    ], JSON_UNESCAPED_SLASHES); // Evitar escapar barras normales

                    // Preparar el tópico y publicar el mensaje
                    $finishTopic = $barcode->mqtt_topic_barcodes . '/order_error';
                    $this->publishMqttMessage($finishTopic, $finishMessage);
                }else{
                    $cleanOrderId=$this->order_id;
                }

                $message = json_encode([
                    'action' => $this->status,
                    'orderId' => $cleanOrderId,
                    'time' => now()->toDateTimeString(), //anadimos aqui time: con fecha y hora
                ]);

                $this->publishMqttMessage($newTopic, $message);
                 // Si el estado es 2, enviamos otro mensaje a /order_finish
                if ($this->status == 2) {
                    $finishTopic = $barcode->mqtt_topic_barcodes . '/order_finish';
                    $finishMessage = json_encode([
                        'orderId' => $this->order_id,
                    ]);

                    $this->publishMqttMessage($finishTopic, $finishMessage);
                }
                 // Si el estado es 4, enviamos otro mensaje a /order_cancel
                 if ($this->status == 4) {
                    $finishTopic = $barcode->mqtt_topic_barcodes . '/order_cancel';
                    $finishMessage = json_encode([
                        'orderId' => $this->order_id,
                    ]);

                    $this->publishMqttMessage($finishTopic, $finishMessage);
                }
                // Si el estado es 3, enviamos otro mensaje a /order_paused
                if ($this->status == 3) {
                    $finishTopic = $barcode->mqtt_topic_barcodes . '/order_paused';
                    $finishMessage = json_encode([
                        'orderId' => $this->order_id,
                    ]);

                    $this->publishMqttMessage($finishTopic, $finishMessage);
                }
                // Si el estado es 1, enviamos otro mensaje a /order_started
                if ($this->status == 1) {
                    $finishTopic = $barcode->mqtt_topic_barcodes . '/order_started';
                    $finishMessage = json_encode([
                        'orderId' => $this->order_id,
                    ]);

                    $this->publishMqttMessage($finishTopic, $finishMessage);
                }
                // Si el estado es 0, enviamos otro mensaje a /order_pending
                if ($this->status == 0) {
                    $finishTopic = $barcode->mqtt_topic_barcodes . '/order_pending';
                    $finishMessage = json_encode([
                        'orderId' => $this->order_id,
                    ]);

                    $this->publishMqttMessage($finishTopic, $finishMessage);
                }

            }
        }
    }

    /**
     * Publish an MQTT message.
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
            //Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Guardar en servidor 2
            //$fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            //if (!file_exists(dirname($fileName2))) {
            //    mkdir(dirname($fileName2), 0755, true);
            //}
            //file_put_contents($fileName2, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}

