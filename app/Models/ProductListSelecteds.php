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
        'finish_at',
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
}
