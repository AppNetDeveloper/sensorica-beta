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
        'product_list_selected_id',
        'product_list_id',
        'finish_at', // Agregado para asignación masiva
    ];

    public $timestamps = true; // Se seguirán manejando created_at y updated_at

    protected $casts = [
        'finish_at' => 'datetime',
    ];

    /**
     * Relación con Operator.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Relación con Sensor.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Relación con Modbus.
     */
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

    public function productList()
    {
        return $this->belongsTo(ProductList::class, 'product_list_id', 'id');
    }


    // Se ha removido el método boot para que la lógica se maneje en el controlador o en otro lugar
}
