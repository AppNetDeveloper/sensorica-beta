<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorPost extends Model
{
    use HasFactory;

    protected $table = 'operator_rfid';

    protected $fillable = [
        'operator_id',
        'rfid_reading_id',
        'sensor_id',
        'modbus_id',
        'count',
        'rfid',
    ];

    public $timestamps = true; // Permitir timestamps, pero manejarlos de forma personalizada

    /**
     * Relación con Operator.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
    // Relación con Sensor
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    // Relación con Modbus
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }

    /**
     * Relación con RfidReading.
     */
    public function rfidReading()
    {
        return $this->belongsTo(RfidReading::class);
    }

    /**
     * Boot method to add logic before creating a new record.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Actualizar updated_at del registro anterior con el mismo operator_id
            $existingOperator = self::where('operator_id', $model->operator_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingOperator) {
                $existingOperator->updated_at = now(); // Actualizar updated_at manualmente
                $existingOperator->save();
            }

            // Actualizar updated_at del registro anterior con el mismo rfid_reading_id
            $existingRfid = self::where('rfid_reading_id', $model->rfid_reading_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingRfid) {
                $existingRfid->updated_at = now(); // Actualizar updated_at manualmente
                $existingRfid->save();
            }

            // Prevenir que el registro actual tenga un valor en updated_at
            $model->updated_at = null; // Establecer null para mostrar que está "en uso"
        });
    }
}
