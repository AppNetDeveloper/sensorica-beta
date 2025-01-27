<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderMac extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_macs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'barcoder_id',
        'production_line_id',
        'json',
        'orderId',
        'action',
        'quantity',
        'machineId',
        'opeId',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'json' => 'array', // Decodifica automáticamente el JSON a un array
    ];

    /**
     * Get the barcode associated with the order_mac.
     */
    public function barcode()
    {
        return $this->belongsTo(Barcode::class, 'barcoder_id');
    }

    /**
     * Get the production line associated with the order_mac.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }
}
