<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorPost extends Model
{
    use HasFactory;

    protected $table = 'operator_post';

    protected $fillable = [
        'operator_id',
        'rfid_reading_id',
        'sensor_id',
        'modbus_id',
        'count',
    ];

    public $timestamps = true; // Permitir timestamps, pero manejar 'updated_at' manualmente

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
     * Boot method to add logic before creating or updating records.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Buscar registros previos que coincidan en al menos uno de los campos clave y con updated_at en NULL
            $existingRecords = self::where(function ($query) use ($model) {
                // Solo consideramos los campos que no son NULL en el nuevo registro
                if ($model->operator_id !== null) {
                    $query->orWhere('operator_id', $model->operator_id);
                }
                if ($model->rfid_reading_id !== null) {
                    $query->orWhere('rfid_reading_id', $model->rfid_reading_id);
                }
                if ($model->sensor_id !== null) {
                    $query->orWhere('sensor_id', $model->sensor_id);
                }
                if ($model->modbus_id !== null) {
                    $query->orWhere('modbus_id', $model->modbus_id);
                }
            })->whereNull('updated_at')
            ->get();

            // Actualizar todos los registros encontrados
            foreach ($existingRecords as $existingRecord) {
                $existingRecord->updated_at = now();
                $existingRecord->save();
            }

            // Asegurarse de que el nuevo registro tenga `updated_at = null`
            $model->updated_at = null;
        });
    }
}
