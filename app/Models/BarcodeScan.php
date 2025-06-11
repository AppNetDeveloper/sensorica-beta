<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeScan extends Model
{
    use HasFactory;
    
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'production_order_id',
        'production_line_id',
        'operator_id',
        'barcode',
        'barcode_data',
        'scanned_at',
    ];
    
    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'barcode_data' => 'array',
        'scanned_at' => 'datetime',
    ];
    
    /**
     * Obtener la orden de producción asociada con este escaneo.
     */
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }
    
    /**
     * Obtener la línea de producción asociada con este escaneo.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
    
    /**
     * Obtener el operador asociado con este escaneo.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
