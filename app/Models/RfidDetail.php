<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidDetail extends Model
{
    use HasFactory;

    protected $table = 'rfid_details';

    protected $fillable = [
        'name',           // Nombre del RFID casi siempre uso el mismo TID para cada RFID siendo nombre unico
        'token',               // Token único para cada entrada
        'production_line_id',  // ID de la línea de producción
        'rfid_reading_id',     // Relación con el modelo RfidReading
        'rfid_type',           // Tipo de RFID
        'count_total',         // Contador total de lecturas  a dar de alta 0 por defecto
        'count_total_0',       // Contador total de lecturas inactivas a dar de alta 0 por defecto
        'count_total_1',       // Contador total de lecturas activas a dar de alta 0 por defecto
        'count_shift_0',       // Contador de lecturas inactivas por turno a dar de alta 0 por defecto
        'count_shift_1',       // Contador de lecturas activas por turno a dar de alta 0 por defecto
        'count_order_0',       // Contador de lecturas inactivas por orden a dar de alta 0 por defecto
        'count_order_1',       // Contador de lecturas activas por orden a dar de alta 0 por defecto
        'mqtt_topic_1',        // Otro tópico MQTT por defecto se puede poner rfid/
        'function_model_0',    // Función del modelo 0 por defecto se puede poner none o si quieres que avise sendMqttValue0
        'function_model_1',    // Función del modelo 1 por defecto se puede poner sendMqttValue1 o si no necesitas avisar none
        'invers_sensors',      // Indicador de inversión de sensores por defecto 0 o 1 si quieres que se invierta el sensor
        'unic_code_order',     // Código único de orden por defecto que se genere un codigo aleatorio
        'shift_type',          // Tipo de turno  por defecto shift
        'event',               // Evento relacionado por efecto start 
        'downtime_count',      // Contador de inactividad por defecto 0 
        'optimal_production_time', // Tiempo óptimo de producción por defecto 50 
        'reduced_speed_time_multiplier', // Multiplicador para velocidad reducida por defecto 5

        // Campos específicos de RFID
        'epc',                 // EPC del grupo RFID
        'tid',                 // TID único
        'rssi',                // Intensidad de la señal RSSI que a dar de alta lo podemos dejar 0
        'serialno',             // Número de serie del dispositivo 
        'send_alert', //avisar si sale de alerta o entra   por defecto 0 o 1 si quieres que se envie un alerta
        'search_out', // buscar siempre si sale de perimetro por defecto 0 o 1 si quieres que se busque siempre
        'last_ant_detect',        // Nuevo campo
        'last_status_detect',     // Nuevo campo
    ];

    /**
     * Relación con el modelo RfidReading.
     */
    public function rfidReading()
    {
        return $this->belongsTo(RfidReading::class);
    }
}
