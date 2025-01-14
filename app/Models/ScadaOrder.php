<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;

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

                $message = json_encode([
                    'action' => $this->status,
                    'orderId' => $this->order_id,
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
            // Insert into mqtt_send_server1
            MqttSendServer1::createRecord($topic, $message);

            // Insert into mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);

            Log::info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables from ScadaOrder.");
        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }
}

