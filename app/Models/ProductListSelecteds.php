<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductListSelecteds extends Model
{
    use HasFactory;

    protected $table = 'product_list_selecteds';

    protected $fillable = [
        'product_list_id',
        'rfid_reading_id',
        'modbus_id',
        'sensor_id',
    ];

    /**
     * Relación con ProductList.
     */
    public function productList()
    {
        return $this->belongsTo(ProductList::class);
    }

    /**
     * Relación con RfidReading.
     */
    public function rfidReading()
    {
        return $this->belongsTo(RfidReading::class);
    }

    /**
     * Relación con Modbus.
     */
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }

    /**
     * Relación con Sensor.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Boot method to add logic before creating a new record.
     */
    protected static function boot()
    {
        parent::boot();
    
        static::creating(function ($model) {
            // Suponemos que modify_all se pasa como atributo del modelo
            $modifyAll = $model->modify_all ?? false;
    
            $query = self::whereNull('updated_at');
    
            // Si modify_all es verdadero, ignoramos product_list_id en la búsqueda
            $conditions = [];
            if (!$modifyAll || $model->product_list_id !== null) {
                $conditions[] = ['product_list_id', $model->product_list_id];
            }
            if ($model->rfid_reading_id !== null) {
                $conditions[] = ['rfid_reading_id', $model->rfid_reading_id];
            }
            if ($model->modbus_id !== null) {
                $conditions[] = ['modbus_id', $model->modbus_id];
            }
            if ($model->sensor_id !== null) {
                $conditions[] = ['sensor_id', $model->sensor_id];
            }
    
            // Solo si hay condiciones para buscar
            if (!empty($conditions)) {
                // Buscar el registro anterior más reciente que coincida con al menos una condición
                $existingRecords = $query->where(function ($q) use ($conditions) {
                    foreach ($conditions as $condition) {
                        $q->orWhere($condition[0], $condition[1]);
                    }
                })->orderBy('created_at', 'desc')->get();
    
                // Si se encuentran registros, actualizar su updated_at
                foreach ($existingRecords as $existingRecord) {
                    $existingRecord->updated_at = now();
                    $existingRecord->save();
                }
            }
    
            // Prevenir que el registro actual tenga un valor en updated_at
            $model->updated_at = null;
        });
    }
}