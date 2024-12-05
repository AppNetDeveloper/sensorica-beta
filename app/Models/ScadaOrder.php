<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
